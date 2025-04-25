<?php
/**
 * OCR 인식률 향상 시스템 - AJAX 작업 상태 확인
 * 작업의 현재 상태와 진행 상황을 확인하는 API 엔드포인트
 */

// 모든 출력 버퍼링 시작
ob_start();

// 오류 표시 끄기 (JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'process_monitor.php';

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

// 요청 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다. GET 요청이 필요합니다.');
}

// 작업 ID 확인
if (empty($_GET['job_id'])) {
    sendJsonResponse(false, 'job_id 파라미터가 필요합니다.');
}

$jobId = (int)$_GET['job_id'];

// 데이터 유효성 검증
if ($jobId <= 0) {
    sendJsonResponse(false, '유효하지 않은 job_id입니다.');
}

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
    // 작업 진행 상황 가져오기
    $progress = $processMonitor->getJobProgress($jobId);
    
    if (!$progress) {
        sendJsonResponse(false, '해당 작업을 찾을 수 없습니다.');
    }
    
    // 작업 정보 포맷팅
    $job = $progress['job'];
    if (!empty($job['updated_at'])) {
        $job['updated_at'] = date('Y-m-d H:i:s', strtotime($job['updated_at']));
    }
    
    // 성공 응답
    sendJsonResponse(true, '작업 상태를 성공적으로 가져왔습니다.', [
        'progress' => $progress
    ]);
    
} catch (Exception $e) {
    // 오류 로깅
    logMessage('작업 상태 조회 오류: ' . $e->getMessage(), 'error');
    
    // 오류 응답
    sendJsonResponse(false, '작업 상태 조회 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>