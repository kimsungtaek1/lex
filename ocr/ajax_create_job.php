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
?>