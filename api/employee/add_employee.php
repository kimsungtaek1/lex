<<<<<<< HEAD
<?php
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 필드 검증
    $required_fields = ['name', 'department', 'position'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('필수 항목이 누락되었습니다: ' . $field);
        }
    }

    // 날짜 형식 변환
    $hire_date = !empty($_POST['hire_date']) ? 
        date('Y-m-d', strtotime($_POST['hire_date'])) : null;
    $access_date = !empty($_POST['access_date']) ? 
        date('Y-m-d', strtotime($_POST['access_date'])) : null;

    // 기본값 설정
    $status = $_POST['status'] ?? '재직';
    $auth = $_POST['auth'] ?? '1';
    
    // 임시 employee_id 생성 (실제 환경에 맞게 수정 필요)
    $employee_id = 'EMP' . date('ymd') . rand(1000, 9999);
    
    // 초기 비밀번호 설정 (실제 환경에 맞게 수정 필요)
    $initial_password = password_hash('1234', PASSWORD_DEFAULT);

    $sql = "INSERT INTO employee (
        employee_id,
        password,
        name,
        department,
        position,
        phone,
        email,
        hire_date,
        access_date,
        status,
        auth
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $employee_id,
        $initial_password,
        $_POST['name'],
        $_POST['department'],
        $_POST['position'],
        $_POST['phone'] ?? '',
        $_POST['email'] ?? '',
        $hire_date,
        $access_date,
        $status,
        $auth
    ]);

    if (!$result) {
        throw new Exception('데이터 저장 실패: ' . implode(', ', $stmt->errorInfo()));
    }

    echo json_encode([
        'success' => true, 
        'message' => '사원이 추가되었습니다.',
        'employee_id' => $employee_id
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.'
    ]);
}
=======
<?php
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 필드 검증
    $required_fields = ['name', 'department', 'position'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('필수 항목이 누락되었습니다: ' . $field);
        }
    }

    // 날짜 형식 변환
    $hire_date = !empty($_POST['hire_date']) ? 
        date('Y-m-d', strtotime($_POST['hire_date'])) : null;
    $access_date = !empty($_POST['access_date']) ? 
        date('Y-m-d', strtotime($_POST['access_date'])) : null;

    // 기본값 설정
    $status = $_POST['status'] ?? '재직';
    $auth = $_POST['auth'] ?? '1';
    
    // 임시 employee_id 생성 (실제 환경에 맞게 수정 필요)
    $employee_id = 'EMP' . date('ymd') . rand(1000, 9999);
    
    // 초기 비밀번호 설정 (실제 환경에 맞게 수정 필요)
    $initial_password = password_hash('1234', PASSWORD_DEFAULT);

    $sql = "INSERT INTO employee (
        employee_id,
        password,
        name,
        department,
        position,
        phone,
        email,
        hire_date,
        access_date,
        status,
        auth
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $employee_id,
        $initial_password,
        $_POST['name'],
        $_POST['department'],
        $_POST['position'],
        $_POST['phone'] ?? '',
        $_POST['email'] ?? '',
        $hire_date,
        $access_date,
        $status,
        $auth
    ]);

    if (!$result) {
        throw new Exception('데이터 저장 실패: ' . implode(', ', $stmt->errorInfo()));
    }

    echo json_encode([
        'success' => true, 
        'message' => '사원이 추가되었습니다.',
        'employee_id' => $employee_id
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.'
    ]);
}
>>>>>>> 719d7c8 (Delete all files)
?>