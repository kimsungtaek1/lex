<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 파라미터 확인
    if (!isset($_POST['paper_no']) || !isset($_POST['content'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $paper_no = (int)$_POST['paper_no'];
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $content = trim($_POST['content']);

    // manager_id가 유효한 직원번호인지 확인
    if ($manager_id !== null) {
        $stmt = $pdo->prepare("SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'");
        $stmt->execute([$manager_id]);
        if (!$stmt->fetch()) {
            throw new Exception('유효하지 않은 직원번호입니다.');
        }
    }

    // 해당 상담건이 존재하는지 확인
    $stmt = $pdo->prepare("SELECT paper_no FROM consult_paper WHERE paper_no = ?");
    $stmt->execute([$paper_no]);
    if (!$stmt->fetch()) {
        throw new Exception('존재하지 않는 상담건입니다.');
    }

    $sql = "INSERT INTO consult_paper_content (
        paper_no,
        manager_id,
        content,
        created_at
    ) VALUES (
        :paper_no,
        :manager_id,
        :content,
        NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':paper_no' => $paper_no,
        ':manager_id' => $manager_id,
        ':content' => $content
    ]);

    if (!$result) {
        throw new Exception('데이터 저장에 실패했습니다.');
    }

    echo json_encode([
        'success' => true,
        'message' => '상담 내용이 저장되었습니다.'
    ]);

} catch(Exception $e) {
    error_log('Error in add_consult_paper_content.php: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}