<?php
// 모든 출력 버퍼링 시작
ob_start();

// 오류 표시 끄기 (중요: JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

// ajax_process_file.php
require_once 'ajax_handler.php';
require_once 'process_monitor.php';

// sendJsonResponse 함수 재정의 (기존 함수가 없는 경우 대비)
if (!function_exists('sendJsonResponse')) {
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
}

// validateAjaxRequest 함수 재정의 (기존 함수가 없는 경우 대비)
if (!function_exists('validateAjaxRequest')) {
    function validateAjaxRequest($method = 'POST', $requiredParams = []) {
        // 메소드 검증
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            sendJsonResponse(false, '잘못된 요청 메소드입니다.');
            return false;
        }
        
        // 필수 파라미터 검증
        $params = ($method === 'POST') ? $_POST : $_GET;
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                sendJsonResponse(false, "{$param} 파라미터가 필요합니다.");
                return false;
            }
        }
        
        return true;
    }
}

// 요청 검증
if (!validateAjaxRequest('POST', ['job_id'])) {
    exit;
}

try {
    $jobId = (int)$_POST['job_id'];
    $processMonitor = new OCRProcessMonitor();
    $result = $processMonitor->processNextFile($jobId);
    
    if (isset($result['success']) && $result['success']) {
        sendJsonResponse(true, $result['message'] ?? '파일 처리가 진행 중입니다.', $result);
    } else {
        sendJsonResponse(false, $result['message'] ?? '파일 처리 중 오류가 발생했습니다.', $result);
    }
} catch (Exception $e) {
    sendJsonResponse(false, '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage());
}