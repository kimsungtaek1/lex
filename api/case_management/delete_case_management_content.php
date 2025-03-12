<<<<<<< HEAD
<?php 
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['content_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $content_no = (int)$_POST['content_no'];

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

        // 내용 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE content_no = ?");
        $stmt->execute([$content_no]);

        // 남은 납부금 합산
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CAST(content AS DECIMAL(10,0))), 0) as total_payment,
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

        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.',
            'payment_amount' => $payment_amount,
            'unpaid_amount' => $unpaid_amount
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
=======
<?php 
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['content_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $content_no = (int)$_POST['content_no'];

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

        // 내용 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE content_no = ?");
        $stmt->execute([$content_no]);

        // 남은 납부금 합산
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CAST(content AS DECIMAL(10,0))), 0) as total_payment,
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

        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.',
            'payment_amount' => $payment_amount,
            'unpaid_amount' => $unpaid_amount
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
>>>>>>> 719d7c8 (Delete all files)
}