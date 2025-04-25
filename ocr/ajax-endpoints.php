

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

/**
 * ajax_save_feedback.php
 * OCR 결과에 대한 사용자 피드백을 저장하는 AJAX 엔드포인트
 */

