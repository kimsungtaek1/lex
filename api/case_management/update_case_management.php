<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    // 필수 파라미터 체크
    if (!isset($_POST['case_no']) || empty($_POST['case_no'])) {
        throw new Exception('사건 번호가 누락되었습니다.');
    }

    // 기본 데이터 가공
    $case_no = (int)$_POST['case_no'];
    
    // 업데이트할 데이터 준비
    $data = [
        ':case_no' => $case_no,
        ':name' => $_POST['name'] ?? null,
        ':phone' => $_POST['phone'] ?? null,
        ':case_number' => $_POST['case_number'] ?? null,
        ':court_name' => $_POST['court_name'] ?? null,
        ':consultant' => !empty($_POST['consultant']) ? (int)$_POST['consultant'] : null,
        ':paper' => !empty($_POST['paper']) ? (int)$_POST['paper'] : null,
        ':contract_date' => !empty($_POST['contract_date']) ? date('Y-m-d', strtotime($_POST['contract_date'])) : null,
        ':application_fee' => !empty($_POST['application_fee']) ? (int)$_POST['application_fee'] : null,
        ':payment_amount' => isset($_POST['payment_amount']) ? (int)$_POST['payment_amount'] : 0,
        ':unpaid_amount' => isset($_POST['unpaid_amount']) ? (int)$_POST['unpaid_amount'] : 0
    ];

    // 데이터 유효성 검사
    if (empty($data[':name'])) {
        throw new Exception('성명은 필수 입력 항목입니다.');
    }

    if (empty($data[':phone'])) {
        throw new Exception('연락처는 필수 입력 항목입니다.');
    }

    // 전화번호 형식 검사
    if (!preg_match('/^01[016789]-\d{3,4}-\d{4}$/', $data[':phone'])) {
        throw new Exception('올바른 전화번호 형식이 아닙니다. (예: 010-1234-5678)');
    }

    // 사건번호 존재 여부 확인
    $checkSql = "SELECT case_no FROM case_management WHERE case_no = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$case_no]);
    if (!$checkStmt->fetch()) {
        throw new Exception('존재하지 않는 사건번호입니다.');
    }

    // 담당자 ID가 유효한지 확인
    if ($data[':consultant'] !== null) {
        $checkSql = "SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$data[':consultant']]);
        if (!$checkStmt->fetch()) {
            throw new Exception('유효하지 않은 상담자입니다.');
        }
    }

    // 서류담당자 ID가 유효한지 확인
    if ($data[':paper'] !== null) {
        $checkSql = "SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$data[':paper']]);
        if (!$checkStmt->fetch()) {
            throw new Exception('유효하지 않은 서류담당자입니다.');
        }
    }

    // SQL 쿼리 준비
    $sql = "UPDATE case_management SET 
            name = :name,
            phone = :phone,
            case_number = :case_number,
            court_name = :court_name,
            consultant = :consultant,
            paper = :paper,
            contract_date = :contract_date,
            application_fee = :application_fee,
            payment_amount = :payment_amount,
            unpaid_amount = :unpaid_amount,
            updated_at = CURRENT_TIMESTAMP
            WHERE case_no = :case_no";

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // 데이터 업데이트 실행
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);

        if (!$result) {
            throw new Exception('데이터 업데이트에 실패했습니다.');
        }

        // 트랜잭션 커밋
        $pdo->commit();

        // 로그 기록
        writeLog("Case management updated - Case No: {$case_no}");

        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '성공적으로 저장되었습니다.'
        ]);

    } catch (Exception $e) {
        // 트랜잭션 롤백
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    // 에러 로그 기록
    writeLog("Error in update_case_management.php: " . $e->getMessage());
    writeLog("POST data: " . print_r($_POST, true));
    
    // 에러 응답
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
    // 필수 파라미터 체크
    if (!isset($_POST['case_no']) || empty($_POST['case_no'])) {
        throw new Exception('사건 번호가 누락되었습니다.');
    }

    // 기본 데이터 가공
    $case_no = (int)$_POST['case_no'];
    
    // 업데이트할 데이터 준비
    $data = [
        ':case_no' => $case_no,
        ':name' => $_POST['name'] ?? null,
        ':phone' => $_POST['phone'] ?? null,
        ':case_number' => $_POST['case_number'] ?? null,
        ':court_name' => $_POST['court_name'] ?? null,
        ':consultant' => !empty($_POST['consultant']) ? (int)$_POST['consultant'] : null,
        ':paper' => !empty($_POST['paper']) ? (int)$_POST['paper'] : null,
        ':contract_date' => !empty($_POST['contract_date']) ? date('Y-m-d', strtotime($_POST['contract_date'])) : null,
        ':application_fee' => !empty($_POST['application_fee']) ? (int)$_POST['application_fee'] : null,
        ':payment_amount' => isset($_POST['payment_amount']) ? (int)$_POST['payment_amount'] : 0,
        ':unpaid_amount' => isset($_POST['unpaid_amount']) ? (int)$_POST['unpaid_amount'] : 0
    ];

    // 데이터 유효성 검사
    if (empty($data[':name'])) {
        throw new Exception('성명은 필수 입력 항목입니다.');
    }

    if (empty($data[':phone'])) {
        throw new Exception('연락처는 필수 입력 항목입니다.');
    }

    // 전화번호 형식 검사
    if (!preg_match('/^01[016789]-\d{3,4}-\d{4}$/', $data[':phone'])) {
        throw new Exception('올바른 전화번호 형식이 아닙니다. (예: 010-1234-5678)');
    }

    // 사건번호 존재 여부 확인
    $checkSql = "SELECT case_no FROM case_management WHERE case_no = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$case_no]);
    if (!$checkStmt->fetch()) {
        throw new Exception('존재하지 않는 사건번호입니다.');
    }

    // 담당자 ID가 유효한지 확인
    if ($data[':consultant'] !== null) {
        $checkSql = "SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$data[':consultant']]);
        if (!$checkStmt->fetch()) {
            throw new Exception('유효하지 않은 상담자입니다.');
        }
    }

    // 서류담당자 ID가 유효한지 확인
    if ($data[':paper'] !== null) {
        $checkSql = "SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$data[':paper']]);
        if (!$checkStmt->fetch()) {
            throw new Exception('유효하지 않은 서류담당자입니다.');
        }
    }

    // SQL 쿼리 준비
    $sql = "UPDATE case_management SET 
            name = :name,
            phone = :phone,
            case_number = :case_number,
            court_name = :court_name,
            consultant = :consultant,
            paper = :paper,
            contract_date = :contract_date,
            application_fee = :application_fee,
            payment_amount = :payment_amount,
            unpaid_amount = :unpaid_amount,
            updated_at = CURRENT_TIMESTAMP
            WHERE case_no = :case_no";

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // 데이터 업데이트 실행
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);

        if (!$result) {
            throw new Exception('데이터 업데이트에 실패했습니다.');
        }

        // 트랜잭션 커밋
        $pdo->commit();

        // 로그 기록
        writeLog("Case management updated - Case No: {$case_no}");

        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '성공적으로 저장되었습니다.'
        ]);

    } catch (Exception $e) {
        // 트랜잭션 롤백
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    // 에러 로그 기록
    writeLog("Error in update_case_management.php: " . $e->getMessage());
    writeLog("POST data: " . print_r($_POST, true));
    
    // 에러 응답
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}