<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['dept_id']) || empty($_POST['dept_id'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $dept_id = $_POST['dept_id'];
    
    // 해당 부서를 참조하는 직원이 있는지 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE department = (SELECT dept_name FROM employee_department WHERE dept_id = :dept_id)");
    $stmt->execute([':dept_id' => $dept_id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('소속 직원이 있는 부서는 삭제할 수 없습니다.');
    }
    
    $stmt = $pdo->prepare("DELETE FROM employee_department WHERE dept_id = :dept_id");
    $result = $stmt->execute([':dept_id' => $dept_id]);

    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    echo json_encode(['success' => true, 'message' => '삭제되었습니다.'], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['dept_id']) || empty($_POST['dept_id'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $dept_id = $_POST['dept_id'];
    
    // 해당 부서를 참조하는 직원이 있는지 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE department = (SELECT dept_name FROM employee_department WHERE dept_id = :dept_id)");
    $stmt->execute([':dept_id' => $dept_id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('소속 직원이 있는 부서는 삭제할 수 없습니다.');
    }
    
    $stmt = $pdo->prepare("DELETE FROM employee_department WHERE dept_id = :dept_id");
    $result = $stmt->execute([':dept_id' => $dept_id]);

    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    echo json_encode(['success' => true, 'message' => '삭제되었습니다.'], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}