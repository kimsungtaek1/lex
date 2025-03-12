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
    $name = $_POST['name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
	$hire_date = !empty($_POST['hire_date']) ? 
		date('Y-m-d', strtotime(str_replace(['-', '.'], '-', $_POST['hire_date']))) : 
		null;
	$access_date = !empty($_POST['access_date']) ? 
		date('Y-m-d', strtotime(str_replace(['-', '.'], '-', $_POST['access_date']))) : 
		null;
    $status = $_POST['status'] ?? '재직';
    $auth = $_POST['auth'] ?? 0;

    $stmt = $pdo->prepare("UPDATE employee SET 
        name = :name, 
        department = :department, 
        position = :position, 
        phone = :phone, 
        email = :email, 
        hire_date = :hire_date, 
		access_date = :access_date,
        status = :status, 
        auth = :auth 
        WHERE employee_no = :id");
        
    $result = $stmt->execute([
        ':name' => $name,
        ':department' => $department,
        ':position' => $position,
        ':phone' => $phone,
        ':email' => $email,
        ':hire_date' => $hire_date,
		':access_date' => $access_date,
        ':status' => $status,
        ':auth' => $auth,
        ':id' => $id
    ]);

    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    echo json_encode(['success' => true, 'message' => '저장되었습니다.'], JSON_UNESCAPED_UNICODE);

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
    $name = $_POST['name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
	$hire_date = !empty($_POST['hire_date']) ? 
		date('Y-m-d', strtotime(str_replace(['-', '.'], '-', $_POST['hire_date']))) : 
		null;
	$access_date = !empty($_POST['access_date']) ? 
		date('Y-m-d', strtotime(str_replace(['-', '.'], '-', $_POST['access_date']))) : 
		null;
    $status = $_POST['status'] ?? '재직';
    $auth = $_POST['auth'] ?? 0;

    $stmt = $pdo->prepare("UPDATE employee SET 
        name = :name, 
        department = :department, 
        position = :position, 
        phone = :phone, 
        email = :email, 
        hire_date = :hire_date, 
		access_date = :access_date,
        status = :status, 
        auth = :auth 
        WHERE employee_no = :id");
        
    $result = $stmt->execute([
        ':name' => $name,
        ':department' => $department,
        ':position' => $position,
        ':phone' => $phone,
        ':email' => $email,
        ':hire_date' => $hire_date,
		':access_date' => $access_date,
        ':status' => $status,
        ':auth' => $auth,
        ':id' => $id
    ]);

    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    echo json_encode(['success' => true, 'message' => '저장되었습니다.'], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}