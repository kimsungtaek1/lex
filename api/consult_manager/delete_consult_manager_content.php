<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['content_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $sql = "DELETE FROM consult_manager_content WHERE content_no = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$_POST['content_no']]);

    if (!$result) {
        throw new Exception('삭제 실패');
    }

    echo json_encode([
        'success' => true,
        'message' => '삭제되었습니다.'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}