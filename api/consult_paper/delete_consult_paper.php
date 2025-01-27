<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['paper_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $pdo->beginTransaction();

    try {
        $paper_no = (int)$_POST['paper_no'];

        // 1. 존재하는지 확인
        $stmt = $pdo->prepare("SELECT paper_no FROM consult_paper WHERE paper_no = ?");
        $stmt->execute([$paper_no]);
        if (!$stmt->fetch()) {
            throw new Exception('존재하지 않는 상담입니다.');
        }

        // 2. case_management 업데이트 (paper_no 참조 제거)
        $stmt = $pdo->prepare("UPDATE case_management SET paper_no = NULL WHERE paper_no = ?");
        $stmt->execute([$paper_no]);

        // 3. consult_paper_content 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_paper_content WHERE paper_no = ?");
        $stmt->execute([$paper_no]);

        // 4. consult_paper 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_paper WHERE paper_no = ?");
        $result = $stmt->execute([$paper_no]);

        if (!$result) {
            throw new Exception('삭제 실패');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error in delete_consult_paper.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}