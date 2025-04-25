<?php
/**
 * ajax_create_job.php
 * 파일 업로드 및 작업 생성 처리
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

// 작업 이름 확인
if (empty($_POST['job_name'])) {
    echo json_encode([
        'success' => false,
        'message' => '작업 이름은 필수입니다.'
    ]);
    exit;
}

// 파일 확인
if (empty($_FILES['files'])) {
    echo json_encode([
        'success' => false,
        'message' => '업로드할 파일이 없습니다.'
    ]);
    exit;
}

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
    // 작업 이름
    $jobName = $_POST['job_name'];
    
    // 문서 유형 (템플릿)
    $documentType = !empty($_POST['document_type']) ? $_POST['document_type'] : null;
    
    // 옵션 설정
    $options = [
        'preprocess' => isset($_POST['options']['preprocess']),
        'enhance_table' => isset($_POST['options']['enhance_table']),
        'apply_custom_dict' => isset($_POST['options']['apply_custom_dict']),
        'document_type' => $documentType
    ];
    
    // 파일 저장 및 경로 목록 생성
    $uploadedFiles = [];
    $files = $_FILES['files'];
    
    if (is_array($files['name'])) {
        // 다중 파일 업로드
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $tempPath = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];
                
                // 이미지 파일 확인
                if (!preg_match('/^image\//', $fileType)) {
                    continue;
                }
                
                // 저장 경로 생성
                $targetFileName = uniqid() . '_' . sanitizeFileName($fileName);
                $targetPath = $config['upload_path'] . '/' . $targetFileName;
                
                // 파일 이동
                if (move_uploaded_file($tempPath, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                }
            }
        }
    } else {
        // 단일 파일 업로드
        if ($files['error'] === UPLOAD_ERR_OK) {
            $fileName = $files['name'];
            $tempPath = $files['tmp_name'];
            $fileType = $files['type'];
            
            // 이미지 파일 확인
            if (preg_match('/^image\//', $fileType)) {
                // 저장 경로 생성
                $targetFileName = uniqid() . '_' . sanitizeFileName($fileName);
                $targetPath = $config['upload_path'] . '/' . $targetFileName;
                
                // 파일 이동
                if (move_uploaded_file($tempPath, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                }
            }
        }
    }
    
    // 업로드된 파일 확인
    if (empty($uploadedFiles)) {
        echo json_encode([
            'success' => false,
            'message' => '유효한 이미지 파일이 없습니다.'
        ]);
        exit;
    }
    
    // 작업 생성 (userId 없이)
    $jobId = $processMonitor->createJob($jobName, $uploadedFiles, null, $options);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'job_id' => $jobId,
        'file_count' => count($uploadedFiles),
        'message' => '작업이 생성되었습니다.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '작업 생성 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

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
    $progress = $processMonitor->getJobProgress($jobId);
    $result['progress'] = $progress;
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

/**
 * ajax_job_status.php
 * 작업 상태를 확인하는 AJAX 엔드포인트
 */

require_once 'config.php';
require_once 'process_monitor.php';

// GET 파라미터 확인
if (empty($_GET['job_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '작업 ID가 필요합니다.'
    ]);
    exit;
}

$jobId = (int)$_GET['job_id'];

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
    // 작업 진행 상황 가져오기
    $progress = $processMonitor->getJobProgress($jobId);
    
    echo json_encode([
        'success' => true,
        'progress' => $progress
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '작업 상태 확인 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

/**
 * ajax_get_file_content.php
 * 파일 내용을 가져오는 AJAX 엔드포인트
 */

require_once 'config.php';

// GET 파라미터 확인
if (empty($_GET['path'])) {
    echo json_encode([
        'success' => false,
        'message' => '파일 경로가 필요합니다.'
    ]);
    exit;
}

$filePath = $_GET['path'];
$rawOutput = isset($_GET['raw']) && $_GET['raw'] === 'true';

// 경로 검증 (상위 디렉토리 이동 방지)
if (strpos($filePath, '..') !== false) {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 파일 경로입니다.'
    ]);
    exit;
}

// 파일 존재 확인
if (!file_exists($filePath)) {
    echo json_encode([
        'success' => false,
        'message' => '파일이 존재하지 않습니다.'
    ]);
    exit;
}

try {
    // 파일 읽기
    $content = file_get_contents($filePath);
    
    if ($rawOutput) {
        // HTML 파일 등 원본 그대로 반환
        echo json_encode([
            'success' => true,
            'content' => $content
        ]);
    } else {
        // 일반 텍스트 파일은 HTML 인코딩하여 반환
        echo json_encode([
            'success' => true,
            'content' => htmlspecialchars($content)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '파일 읽기 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

/**
 * ajax_cancel_job.php
 * 작업을 취소하는 AJAX 엔드포인트
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
    
    // 작업 취소
    $result = $processMonitor->cancelJob($jobId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '작업이 취소되었습니다.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '작업 취소에 실패했습니다.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '작업 취소 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

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

/**
 * ajax_save_feedback.php
 * OCR 결과에 대한 사용자 피드백을 저장하는 AJAX 엔드포인트
 */

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
