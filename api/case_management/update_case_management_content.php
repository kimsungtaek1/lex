<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    session_start();
    if (!isset($_SESSION['employee_no'])) {
        throw new Exception('로그인이 필요합니다.');
    }

    if (!isset($_POST['content_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $content_no = (int)$_POST['content_no'];
    $content = isset($_POST['content']) ? preg_replace('/[^0-9]/', '', $_POST['content']) : '0';
    $bank = isset($_POST['bank']) ? trim($_POST['bank']) : null;
    
    // session의 employee_no를 checker_id로 사용
    $checker_id = $_SESSION['employee_no'];

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // case_no 조회
        $stmt = $pdo->prepare("SELECT case_no FROM case_management_content WHERE content_no = ?");
        $stmt->execute([$content_no]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception('존재하지 않는 수임료입니다.');
        }
        
        $case_no = $result['case_no'];

        // case_management_content 업데이트
        $stmt = $pdo->prepare("
            UPDATE case_management_content 
            SET content = :content,
                bank = :bank,
                checker_id = :checker_id,
                updated_at = NOW()
            WHERE content_no = :content_no
        ");
        
        $stmt->execute([
            ':content_no' => $content_no,
            ':content' => $content,
            ':bank' => $bank,
            ':checker_id' => $checker_id
        ]);

        // 해당 case의 모든 납부금 합산
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CAST(content AS DECIMAL(10,0))) as total_payment,
                cm.application_fee
            FROM case_management_content cmc
            JOIN case_management cm ON cm.case_no = cmc.case_no
            WHERE cmc.case_no = ?
            GROUP BY cm.case_no, cm.application_fee
        ");
        
        $stmt->execute([$case_no]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 납부금 및 미납금 계산
        $payment_amount = (int)$result['total_payment'];
        $application_fee = (int)$result['application_fee'];
        $unpaid_amount = $application_fee - $payment_amount;

        // case_management 테이블 업데이트
        $stmt = $pdo->prepare("
            UPDATE case_management 
            SET payment_amount = :payment_amount,
                unpaid_amount = :unpaid_amount,
                updated_at = NOW()
            WHERE case_no = :case_no
        ");

        $stmt->execute([
            ':case_no' => $case_no,
            ':payment_amount' => $payment_amount,
            ':unpaid_amount' => $unpaid_amount
        ]);

        $pdo->commit();

        // employee 테이블에서 확인자 이름 조회
        $stmt = $pdo->prepare("
            SELECT name 
            FROM employee 
            WHERE employee_no = ?
        ");
        $stmt->execute([$checker_id]);
        $checkerResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $checkerName = $checkerResult ? $checkerResult['name'] : '';

        echo json_encode([
            'success' => true,
            'message' => '저장되었습니다.',
            'payment_amount' => $payment_amount,
            'unpaid_amount' => $unpaid_amount,
            'checker_id' => $checker_id,
            'checker_name' => $checkerName
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}