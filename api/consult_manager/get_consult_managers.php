<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    $sql = "SELECT 
        c.*,
        e.name as manager_name
        FROM consult_manager c
        LEFT JOIN employee e ON c.consultant = e.employee_no
        ORDER BY c.datetime DESC";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $consults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $consults], JSON_UNESCAPED_UNICODE);

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

    $sql = "SELECT 
        c.*,
        e.name as manager_name
        FROM consult_manager c
        LEFT JOIN employee e ON c.consultant = e.employee_no
        ORDER BY c.datetime DESC";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $consults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $consults], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}