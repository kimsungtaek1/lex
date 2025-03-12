<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['case_no']) || !isset($_POST['content'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $case_no = (int)$_POST['case_no'];
    $content = trim($_POST['content']);
    $checker_id = !empty($_POST['checker_id']) ? (int)$_POST['checker_id'] : null;

    // checker_id가 유효한 직원번호인지 확인
    if ($checker_id !== null) {
        $stmt = $pdo->prepare("SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'");
        $stmt->execute([$checker_id]);
        if (!$stmt->fetch()) {
            throw new Exception('유효하지 않은 확인자입니다.');
        }
    }

    // 해당 사건이 존재하는지 확인
    $stmt = $pdo->prepare("SELECT case_no FROM case_management WHERE case_no = ?");
    $stmt->execute([$case_no]);
    if (!$stmt->fetch()) {
        throw new Exception('존재하지 않는 사건입니다.');
    }

    $sql = "INSERT INTO case_management_content (
        case_no,
        content,
        checker_id,
        created_at
    ) VALUES (
        :case_no,
        :content,
        :checker_id,
        NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':case_no' => $case_no,
        ':content' => $content,
        ':checker_id' => $checker_id
    ]);

    if (!$result) {
        throw new Exception('데이터 저장에 실패했습니다.');
    }

    echo json_encode([
        'success' => true,
        'message' => '메모가 저장되었습니다.'
    ]);

} catch(Exception $e) {
    error_log('Error in add_case_management_content.php: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    
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
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['case_no']) || !isset($_POST['content'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $case_no = (int)$_POST['case_no'];
    $content = trim($_POST['content']);
    $checker_id = !empty($_POST['checker_id']) ? (int)$_POST['checker_id'] : null;

    // checker_id가 유효한 직원번호인지 확인
    if ($checker_id !== null) {
        $stmt = $pdo->prepare("SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'");
        $stmt->execute([$checker_id]);
        if (!$stmt->fetch()) {
            throw new Exception('유효하지 않은 확인자입니다.');
        }
    }

    // 해당 사건이 존재하는지 확인
    $stmt = $pdo->prepare("SELECT case_no FROM case_management WHERE case_no = ?");
    $stmt->execute([$case_no]);
    if (!$stmt->fetch()) {
        throw new Exception('존재하지 않는 사건입니다.');
    }

    $sql = "INSERT INTO case_management_content (
        case_no,
        content,
        checker_id,
        created_at
    ) VALUES (
        :case_no,
        :content,
        :checker_id,
        NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':case_no' => $case_no,
        ':content' => $content,
        ':checker_id' => $checker_id
    ]);

    if (!$result) {
        throw new Exception('데이터 저장에 실패했습니다.');
    }

    echo json_encode([
        'success' => true,
        'message' => '메모가 저장되었습니다.'
    ]);

} catch(Exception $e) {
    error_log('Error in add_case_management_content.php: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}