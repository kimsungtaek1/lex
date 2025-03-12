<<<<<<< HEAD
<?php
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $id = $_POST['id'];
    
    $stmt = $pdo->prepare("DELETE FROM employee WHERE employee_no = :id");
    $result = $stmt->execute([':id' => $id]);

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
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $id = $_POST['id'];
    
    $stmt = $pdo->prepare("DELETE FROM employee WHERE employee_no = :id");
    $result = $stmt->execute([':id' => $id]);

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