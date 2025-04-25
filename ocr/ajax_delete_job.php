<?php
/**
 * ajax_delete_job.php
 * 작업을 삭제하는 AJAX 엔드포인트
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
    
    // 작업 삭제
    $result = $processMonitor->deleteJob($jobId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '작업이 삭제되었습니다.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '작업 삭제에 실패했습니다.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '작업 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>