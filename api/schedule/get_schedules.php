<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $employee_id = $_SESSION['user_id']; // 세션의 user_id가 employee_id임

    // employee 테이블에서 auth 값 조회
    $auth = 0;
    $stmt_auth = $pdo->prepare("SELECT auth FROM employee WHERE employee_id = :employee_id LIMIT 1");
    $stmt_auth->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
    $stmt_auth->execute();
    $row = $stmt_auth->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['auth'])) {
        $auth = (int)$row['auth'];
    }


    // 관리자: 전체 일정 조회
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
?>