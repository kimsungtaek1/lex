<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 파일 업로드 처리: process_upload.php

require_once 'config.php';
require_once 'utils.php';
session_start();

header('Content-Type: application/json; charset=UTF-8');

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => '잘못된 접근입니다. (CSRF)']);
    http_response_code(400);
    exit;
}

// 파일 업로드 검증
if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => '업로드된 파일이 없습니다.']);
    http_response_code(400);
    exit;
}

$file = $_FILES['file'];

// validateUploadedFile 함수는 upload.php에 정의되어 있으므로, 중복 정의를 피하려면 utils.php 등 공통 파일로 옮기는 것이 좋음
if (!function_exists('validateUploadedFile')) {
    function validateUploadedFile($file) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '파일이 PHP 설정 upload_max_filesize를 초과합니다.',
            UPLOAD_ERR_FORM_SIZE => '파일이 폼에 지정된 MAX_FILE_SIZE를 초과합니다.',
            UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장에 의해 업로드가 중지되었습니다.'
        ];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : '알 수 없는 업로드 오류가 발생했습니다.';
            return ['success' => false, 'message' => $errorMessage];
        }
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => '파일 크기가 10MB를 초과합니다.'];
        }
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'image/bmp', 'image/tiff', 'image/webp'
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMimeType = $finfo->file($file['tmp_name']);
        if (!in_array($realMimeType, $allowedTypes)) {
            return ['success' => false, 'message' => '지원하지 않는 파일 형식입니다. 이미지 파일만 업로드 가능합니다.'];
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => '유효한 이미지 파일이 아닙니다.'];
        }
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
        $fileName = basename($file['name']);
        $fileName = preg_replace('/[\/\\:*?"<>|]/', '_', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = trim($fileName);
        if (empty($fileName)) {
            $fileName = 'unnamed_file';
        }
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
}

$validation = validateUploadedFile($file);
if (!$validation['success']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    http_response_code(400);
    exit;
}

// 업로드 디렉토리 설정
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 파일 저장
$uniqueName = uniqid('img_', true) . '_' . $validation['name'];
$targetPath = $uploadDir . '/' . $uniqueName;
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
    http_response_code(500);
    exit;
}

// 성공 응답
echo json_encode([
    'success' => true,
    'message' => '업로드 성공',
    'file_name' => $uniqueName,
    'file_path' => 'uploads/' . $uniqueName,
    'mime_type' => $validation['mime_type'],
    'size' => $validation['size'],
    'width' => $validation['width'],
    'height' => $validation['height']
]);
exit;
