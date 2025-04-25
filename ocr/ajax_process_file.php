<?php
/**
 * ajax_process_file.php
 * 대기 중인 파일 하나를 처리
 */

require_once 'config.php';
require_once 'process_monitor.php';

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 메소드입니다.'
    ]);
    exit;
}

// 작업 ID 확인
if (empty($_POST['job_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '작업 ID가 필요합니다.'
    ]);
    exit;
}

$jobId = (int)$_POST['job_id'];

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
    // 다음 파일 처리
    $result = $processMonitor->processNextFile($jobId);
    
    // 진행 상태 확인
    if ($result['status'] === 'completed') {
        echo json_encode([
            'success' => true,
            'message' => '파일 처리가 완료되었습니다.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '파일 처리 중 오류가 발생했습니다: ' . $result['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>