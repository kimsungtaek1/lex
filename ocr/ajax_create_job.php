<?php
// 모든 출력 버퍼링 시작
ob_start();
session_start();
/**
 * ajax_create_job.php
 * 파일 업로드 및 작업 생성 처리
 */

// 오류 표시 끄기 (중요: JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'process_monitor.php';
require_once 'utils.php';

// 안전한 파일 업로드 검증
function validateUploadedFile($file) {
    // 업로드 오류 확인
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '파일이 PHP 설정 upload_max_filesize를 초과합니다.',
            UPLOAD_ERR_FORM_SIZE => '파일이 폼에 지정된 MAX_FILE_SIZE를 초과합니다.',
            UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장에 의해 업로드가 중지되었습니다.'
        ];
        
        $errorMessage = isset($errorMessages[$file['error']]) ? 
            $errorMessages[$file['error']] : '알 수 없는 업로드 오류가 발생했습니다.';
        
        return ['success' => false, 'message' => $errorMessage];
    }
    
    // 파일 크기 검증 (10MB 제한)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '파일 크기가 10MB를 초과합니다.'];
    }
    
    // MIME 타입 검증
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/bmp', 'image/tiff', 'image/webp'
    ];
    
    // finfo로 실제 파일 타입 확인 (MIME 스푸핑 방지)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($realMimeType, $allowedTypes)) {
        return ['success' => false, 'message' => '지원하지 않는 파일 형식입니다. 이미지 파일만 업로드 가능합니다.'];
    }
    
    // 이미지 파일 검증
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => '유효한 이미지 파일이 아닙니다.'];
    }
    
    // 이미지 크기 검증 (최소 크기와 최대 크기)
    $minWidth = 100;
    $minHeight = 100;
    $maxWidth = 8000;
    $maxHeight = 8000;
    
    list($width, $height) = $imageInfo;
    
    if ($width < $minWidth || $height < $minHeight) {
        return ['success' => false, 'message' => "이미지 크기가 너무 작습니다. 최소 {$minWidth}x{$minHeight} 픽셀 이상이어야 합니다."];
    }
    
    if ($width > $maxWidth || $height > $maxHeight) {
        return ['success' => false, 'message' => "이미지 크기가 너무 큽니다. 최대 {$maxWidth}x{$maxHeight} 픽셀 이하여야 합니다."];
    }
    
    // 파일 이름 정리
    $fileName = sanitizeFileName($file['name']);
    
    return [
        'success' => true,
        'mime_type' => $realMimeType,
        'size' => $file['size'],
        'width' => $width,
        'height' => $height,
        'tmp_name' => $file['tmp_name'],
        'name' => $fileName
    ];
}

// 파일 이름 정리 (보안 목적)
function sanitizeFileName($fileName) {
    // 경로 정보 제거
    $fileName = basename($fileName);
    // 특수 문자 제거
    $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fileName);
    // 중복된 공백 제거
    $fileName = preg_replace('/\s+/', ' ', $fileName);
    // 공백 제거
    $fileName = trim($fileName);
    
    if (empty($fileName)) {
        return 'unnamed_file';
    }
    
    return $fileName;
}

// JSON 응답 반환
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

// 디렉토리 쓰기 권한 확인
function checkDirectoryWritable($dir) {
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return false;
        }
    }
    
    if (!is_writable($dir)) {
        return false;
    }
    
    return true;
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다.');
}

// 작업 이름 확인
if (empty($_POST['job_name'])) {
    sendJsonResponse(false, '작업 이름은 필수입니다.');
}

// 작업 이름 정규식 검증
$jobName = trim($_POST['job_name']);
if (!preg_match('/^[A-Za-z0-9가-힣\s\-_]{1,100}$/', $jobName)) {
    sendJsonResponse(false, '작업 이름은 한글, 영문, 숫자, 공백, 하이픈, 언더스코어만 포함할 수 있습니다.');
}

// 파일 확인
if (empty($_FILES['files'])) {
    sendJsonResponse(false, '업로드할 파일이 없습니다.');
}

// 업로드 디렉토리 확인
if (!checkDirectoryWritable($config['upload_path'])) {
    sendJsonResponse(false, '업로드 디렉토리에 쓰기 권한이 없습니다.');
}

try {
    // 프로세스 모니터 초기화
    $processMonitor = new OCRProcessMonitor();
    
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
    $invalidFiles = [];
    $files = $_FILES['files'];
    
    if (is_array($files['name'])) {
        // 다중 파일 업로드
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileToValidate = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $validation = validateUploadedFile($fileToValidate);
            
            if ($validation['success']) {
                // 저장 경로 생성
                $targetFileName = uniqid() . '_' . $validation['name'];
                $targetPath = $config['upload_path'] . '/' . $targetFileName;
                
                // 파일 이동
                if (move_uploaded_file($fileToValidate['tmp_name'], $targetPath)) {
                    $uploadedFiles[] = [
                        'path' => $targetPath,
                        'original_name' => $validation['name'],
                        'mime_type' => $validation['mime_type'],
                        'size' => $validation['size'],
                        'width' => $validation['width'],
                        'height' => $validation['height']
                    ];
                } else {
                    $invalidFiles[] = [
                        'name' => $fileToValidate['name'],
                        'reason' => '파일 이동 실패'
                    ];
                    
                    logMessage("파일 이동 실패: {$fileToValidate['name']}", 'error');
                }
            } else {
                $invalidFiles[] = [
                    'name' => $fileToValidate['name'],
                    'reason' => $validation['message']
                ];
            }
        }
    } else {
        // 단일 파일 업로드
        $validation = validateUploadedFile($files);
        
        if ($validation['success']) {
            // 저장 경로 생성
            $targetFileName = uniqid() . '_' . $validation['name'];
            $targetPath = $config['upload_path'] . '/' . $targetFileName;
            
            // 파일 이동
            if (move_uploaded_file($files['tmp_name'], $targetPath)) {
                $uploadedFiles[] = [
                    'path' => $targetPath,
                    'original_name' => $validation['name'],
                    'mime_type' => $validation['mime_type'],
                    'size' => $validation['size'],
                    'width' => $validation['width'],
                    'height' => $validation['height']
                ];
            } else {
                sendJsonResponse(false, '파일 이동 실패');
            }
        } else {
            sendJsonResponse(false, $validation['message']);
        }
    }
    
    // 업로드된 파일 확인
    if (empty($uploadedFiles)) {
        if (!empty($invalidFiles)) {
            $errorMessages = array_map(function($file) {
                return "{$file['name']}: {$file['reason']}";
            }, $invalidFiles);
            
            sendJsonResponse(false, '파일 업로드 실패: ' . implode(', ', $errorMessages));
        } else {
            sendJsonResponse(false, '유효한 이미지 파일이 없습니다.');
        }
    }
    
    // 파일 경로만 추출
    $filePaths = array_column($uploadedFiles, 'path');
    
    // DB 트랜잭션 시작
    $db = getDB();
    $db->beginTransaction();
    
    try {
        // 작업 생성
        $jobId = $processMonitor->createJob($jobName, $filePaths, $options);
        
        // 작업 메타데이터 추가 (파일별 상세 정보)
        $stmt = $db->prepare("
            INSERT INTO ocr_job_metadata 
            (job_id, key_name, value) 
            VALUES (?, ?, ?)
        ");
        
        $fileDetailsJson = json_encode([
            'files' => $uploadedFiles,
            'invalid_files' => $invalidFiles
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt->execute([$jobId, 'file_details', $fileDetailsJson]);
        
        // 성공 메시지
        $message = '작업이 생성되었습니다.';
        
        // 일부 파일만 업로드 성공한 경우
        if (!empty($invalidFiles)) {
            $message .= ' 일부 파일은 업로드되지 않았습니다 (' . count($invalidFiles) . '개).';
        }
        
        $db->commit();
        
        // 성공 응답
        sendJsonResponse(true, $message, [
            'job_id' => $jobId,
            'file_count' => count($uploadedFiles),
            'invalid_files' => $invalidFiles
        ]);
        
    } catch (Exception $e) {
        // 롤백
        $db->rollBack();
        
        // 업로드된 파일 정리
        foreach ($filePaths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        
        sendJsonResponse(false, '작업 생성 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    logMessage("작업 생성 오류: " . $e->getMessage(), 'error');
    sendJsonResponse(false, '작업 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}