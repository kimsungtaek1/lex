<<<<<<< HEAD
<?php
define('ADMIN_DIRECTORY', dirname(dirname(dirname(__FILE__))));
require_once ADMIN_DIRECTORY . '/config.php';

header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }
    
    // 기본 쿼리 작성
    $sql = "
        SELECT 
            d.no,
            d.name,
            d.phone,
            d.category,
            DATE_FORMAT(d.datetime, '%Y-%m-%d %H:%i:%s') as datetime,
            d.content,
            d.inflow_page,
            d.inflow,
            d.region,
            d.birth_date,
            d.debt_amount,
            d.consultation_time,
            d.manager,
            e.name as manager_name
        FROM inflow d
        LEFT JOIN employee e ON d.manager = e.employee_no
        ORDER BY d.datetime DESC
    ";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $inflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $inflows], JSON_UNESCAPED_UNICODE);

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
    
    // 기본 쿼리 작성
    $sql = "
        SELECT 
            d.no,
            d.name,
            d.phone,
            d.category,
            DATE_FORMAT(d.datetime, '%Y-%m-%d %H:%i:%s') as datetime,
            d.content,
            d.inflow_page,
            d.inflow,
            d.region,
            d.birth_date,
            d.debt_amount,
            d.consultation_time,
            d.manager,
            e.name as manager_name
        FROM inflow d
        LEFT JOIN employee e ON d.manager = e.employee_no
        ORDER BY d.datetime DESC
    ";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $inflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $inflows], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}