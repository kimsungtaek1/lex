<?php
/**
 * OCR 인식률 향상 시스템 - AJAX 피드백 저장 처리
 * 사용자가 제공한 OCR 결과 피드백을 데이터베이스에 저장하고
 * 향후 OCR 인식률 향상에 활용하기 위한 API 엔드포인트
 */

// 모든 출력 버퍼링 시작 (JSON 응답에 다른 출력이 섞이지 않도록)
ob_start();

// 오류 표시 끄기 (JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'document_learning.php';

// JSON 응답 반환 함수
function sendJsonResponse($success, $message, $data = []) {
    // 기존 출력 모두 지우기
    ob_clean();
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 요청 검증 - POST 및 JSON 형식 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다. POST 요청이 필요합니다.');
}

// JSON 컨텐츠 타입 확인
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($contentType, 'application/json') === false) {
    // Content-Type이 application/json이 아니면 폼 데이터로 가정하고 처리 시도
    if (empty($_POST['job_id']) || empty($_POST['file_id'])) {
        sendJsonResponse(false, '요청 형식이 올바르지 않습니다. job_id와 file_id가 필요합니다.');
    }
    
    $jobId = (int)$_POST['job_id'];
    $fileId = (int)$_POST['file_id'];
    $corrections = isset($_POST['corrections']) ? $_POST['corrections'] : [];
    
    if (empty($corrections) && isset($_POST['field']) && isset($_POST['original']) && isset($_POST['corrected'])) {
        // 단일 수정 항목을 배열로 변환
        $corrections = [
            [
                'type' => 'field',
                'field' => $_POST['field'],
                'original' => $_POST['original'],
                'corrected' => $_POST['corrected']
            ]
        ];
    }
} else {
    // JSON 요청 처리
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'JSON 파싱 오류: ' . json_last_error_msg());
    }
    
    if (empty($data['job_id']) || empty($data['file_id']) || empty($data['corrections'])) {
        sendJsonResponse(false, '필수 데이터가 누락되었습니다. job_id, file_id, corrections가 필요합니다.');
    }
    
    $jobId = (int)$data['job_id'];
    $fileId = (int)$data['file_id'];
    $corrections = $data['corrections'];
}

// 데이터 유효성 검증
if ($jobId <= 0 || $fileId <= 0) {
    sendJsonResponse(false, '유효하지 않은 job_id 또는 file_id입니다.');
}

if (!is_array($corrections) || empty($corrections)) {
    sendJsonResponse(false, '수정 내용(corrections)이 비어있거나 형식이 올바르지 않습니다.');
}

try {
    // 문서 학습 시스템 초기화
    $learningSystem = new DocumentLearningSystem();
    
    // 피드백 저장
    $feedbackId = $learningSystem->saveFeedback($jobId, $fileId, $corrections);
    
    // 성공 응답
    sendJsonResponse(true, '피드백이 성공적으로 저장되었습니다. OCR 인식률 향상에 활용됩니다.', [
        'feedback_id' => $feedbackId
    ]);
    
} catch (Exception $e) {
    // 오류 로깅
    logMessage('피드백 저장 오류: ' . $e->getMessage(), 'error');
    
    // 오류 응답
    sendJsonResponse(false, '피드백 저장 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>