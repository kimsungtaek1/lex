<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 파라미터 확인
    if (!isset($_POST['content_no']) || !isset($_POST['content'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $content_no = (int)$_POST['content_no'];
    $content = trim($_POST['content']);

    // 필수값 검증
    if (empty($content)) {
        throw new Exception('상담 내용은 필수 입력 항목입니다.');
    }

    $sql = "UPDATE consult_manager_content 
            SET content = :content,
                updated_at = NOW()
            WHERE content_no = :content_no";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':content_no' => $content_no,
        ':content' => $content
    ]);

    if (!$result) {
        throw new Exception('데이터 수정에 실패했습니다.');
    }

    echo json_encode([
        'success' => true,
        'message' => '상담 내용이 수정되었습니다.'
    ]);

} catch(Exception $e) {
    error_log('Error in update_consult_manager_content.php: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 파라미터 확인
    if (!isset($_POST['content_no']) || !isset($_POST['content'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $content_no = (int)$_POST['content_no'];
    $content = trim($_POST['content']);

    // 필수값 검증
    if (empty($content)) {
        throw new Exception('상담 내용은 필수 입력 항목입니다.');
    }

    $sql = "UPDATE consult_manager_content 
            SET content = :content,
                updated_at = NOW()
            WHERE content_no = :content_no";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':content_no' => $content_no,
        ':content' => $content
    ]);

    if (!$result) {
        throw new Exception('데이터 수정에 실패했습니다.');
    }

    echo json_encode([
        'success' => true,
        'message' => '상담 내용이 수정되었습니다.'
    ]);

} catch(Exception $e) {
    error_log('Error in update_consult_manager_content.php: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}