<<<<<<< HEAD
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

    $schedule_no = (int)$_POST['schedule_no'];
    if ($schedule_no <= 0) {
        throw new Exception('유효하지 않은 일정 번호입니다.');
    }

    $sql = "DELETE FROM schedule WHERE schedule_no = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$schedule_no]);

    if ($result) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => '삭제되었습니다.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('해당 일정을 찾을 수 없습니다.');
        }
    } else {
        throw new Exception('일정 삭제 실패');
    }
    
} catch(Exception $e) {
    error_log('Error in delete_schedule.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
=======
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

    $schedule_no = (int)$_POST['schedule_no'];
    if ($schedule_no <= 0) {
        throw new Exception('유효하지 않은 일정 번호입니다.');
    }

    $sql = "DELETE FROM schedule WHERE schedule_no = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$schedule_no]);

    if ($result) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => '삭제되었습니다.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('해당 일정을 찾을 수 없습니다.');
        }
    } else {
        throw new Exception('일정 삭제 실패');
    }
    
} catch(Exception $e) {
    error_log('Error in delete_schedule.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> 719d7c8 (Delete all files)
}