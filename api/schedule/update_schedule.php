<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청 방식입니다.');
    }

    if (empty($_POST['schedule_no'])) {
        throw new Exception('일정 번호가 누락되었습니다.');
    }

    if (empty($_POST['date'])) {
        throw new Exception('일자가 누락되었습니다.');
    }

    $sql = "UPDATE schedule SET 
            category = :category,
            name = :name,
            date = :date,
            time = :time,
            content = :content,
            location = :location,
            memo = :memo,
            updated_at = CURRENT_TIMESTAMP
            WHERE schedule_no = :schedule_no";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':category' => $_POST['category'] ?? null,
        ':name' => $_POST['name'] ?? null,
        ':date' => $_POST['date'],
        ':time' => !empty($_POST['time']) ? $_POST['time'] : null,
        ':content' => $_POST['content'] ?? null,
        ':location' => $_POST['location'] ?? null,
        ':memo' => $_POST['memo'] ?? null,
        ':schedule_no' => $_POST['schedule_no']
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '저장되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('일정 수정 실패');
    }
    
} catch(Exception $e) {
    error_log('Error in update_schedule.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}