<<<<<<< HEAD
<?php
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // employee 테이블과 employee_position 테이블을 JOIN하여 position_order 순서대로 정렬
    $stmt = $pdo->prepare("
        SELECT 
            m.employee_no,
            m.employee_id,
            m.name,
            m.department,
            m.position,
            m.phone,
            m.email,
            DATE_FORMAT(m.hire_date, '%Y-%m-%d') as hire_date,
            DATE_FORMAT(m.access_date, '%Y-%m-%d') as access_date,
            m.status,
            m.auth 
        FROM employee m
        LEFT JOIN employee_position ep ON m.position = ep.position_name
        ORDER BY ep.position_order ASC, m.name ASC
    ");

    $result = $stmt->execute();
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $employees], JSON_UNESCAPED_UNICODE);

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

    // employee 테이블과 employee_position 테이블을 JOIN하여 position_order 순서대로 정렬
    $stmt = $pdo->prepare("
        SELECT 
            m.employee_no,
            m.employee_id,
            m.name,
            m.department,
            m.position,
            m.phone,
            m.email,
            DATE_FORMAT(m.hire_date, '%Y-%m-%d') as hire_date,
            DATE_FORMAT(m.access_date, '%Y-%m-%d') as access_date,
            m.status,
            m.auth 
        FROM employee m
        LEFT JOIN employee_position ep ON m.position = ep.position_name
        ORDER BY ep.position_order ASC, m.name ASC
    ");

    $result = $stmt->execute();
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $employees], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}