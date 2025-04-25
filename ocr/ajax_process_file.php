<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ajax_process_file.php
require_once 'ajax_handler.php';
require_once 'process_monitor.php';

// 요청 검증
if (!validateAjaxRequest('POST', ['job_id'])) {
    exit;
}

try {
    $jobId = (int)$_POST['job_id'];
    $processMonitor = new OCRProcessMonitor();
    $result = $processMonitor->processNextFile($jobId);
    
    if ($result['status'] === 'completed') {
        sendJsonResponse(true, '파일 처리가 완료되었습니다.', $result);
    } else {
        sendJsonResponse(false, '파일 처리 중 오류가 발생했습니다: ' . $result['message'], $result);
    }
} catch (Exception $e) {
    sendJsonResponse(false, '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage());
}