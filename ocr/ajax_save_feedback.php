<?php
require_once 'config.php';
require_once 'document_learning.php';

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 메소드입니다.'
    ]);
    exit;
}

// JSON 요청 데이터 파싱
$postData = json_decode(file_get_contents('php://input'), true);

// 필수 데이터 확인
if (!isset($postData['job_id']) || !isset($postData['file_id']) || !isset($postData['corrections'])) {
    echo json_encode([
        'success' => false,
        'message' => '필수 데이터가 누락되었습니다.'
    ]);
    exit;
}

$jobId = (int)$postData['job_id'];
$fileId = (int)$postData['file_id'];
$corrections = $postData['corrections'];

try {
    // 문서 학습 시스템 초기화
    $learningSystem = new DocumentLearningSystem();
    
    // 피드백 저장
    $feedbackId = $learningSystem->saveFeedback($jobId, $fileId, $corrections);
    
    if ($feedbackId) {
        echo json_encode([
            'success' => true,
            'feedback_id' => $feedbackId,
            'message' => '피드백이 저장되었습니다.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '피드백 저장에 실패했습니다.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '피드백 저장 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

/**
 * 파일 이름 정리
 * @param string $fileName 원본 파일명
 * @return string 정리된 파일명
 */
function sanitizeFileName($fileName) {
    // 한글 및 특수문자 지원을 위해 URL인코딩 사용 안함
    // 대신 경로 구분자 및 위험한 문자만 제거
    $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fileName);
    return $fileName;
}
?>
