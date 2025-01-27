<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT s.*, 
               DATE_FORMAT(s.date, '%Y-%m-%d') as formatted_date,
               TIME_FORMAT(s.time, '%H:%i') as formatted_time
        FROM schedule s 
        ORDER BY s.date DESC, s.time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'data' => $schedules
    ], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    error_log('Error in get_schedules.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}