<?php
// 모든 출력 버퍼링 시작
ob_start();

// 오류 표시 끄기 (중요: JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

// ajax_job_status.php
require_once 'config.php';
require_once 'process_monitor.php';

// 요청 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다.');
    exit;
}

// 작업 ID 확인
if (empty($_GET['job_id'])) {
    sendJsonResponse(false, '작업 ID가 필요합니다.');
    exit;
}

$jobId = (int)$_GET['job_id'];

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

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
    // 작업 진행 상태 가져오기
    $progress = $processMonitor->getJobProgress($jobId);
    
    if (!$progress) {
        sendJsonResponse(false, '작업을 찾을 수 없습니다.');
    }
    
    // 성공 응답
    sendJsonResponse(true, '작업 상태를 성공적으로 가져왔습니다.', [
        'progress' => $progress
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(false, '작업 상태 조회 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>