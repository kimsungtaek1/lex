<?php
// 오류 표시 끄기
error_reporting(0);
ini_set('display_errors', 0);

// download.php - 파일 다운로드 처리
require_once 'config.php';

// 파일 경로 확인
if (empty($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo '파일 경로가 필요합니다.';
    exit;
}

$filePath = $_GET['file'];

// 보안 검증: 경로가 적절한 디렉토리에 있는지 확인
function isValidPath($path) {
    global $config;
    
    // 결과 디렉토리인지 확인
    if (strpos($path, $config['output_path']) === 0) {
        return true;
    }
    
    // 기타 허용된 디렉토리 확인
    $allowedDirs = [
        $config['upload_path'],
        $config['temp_path'],
        $config['model_path']
    ];
    
    foreach ($allowedDirs as $dir) {
        if (strpos($path, $dir) === 0) {
            return true;
        }
    }
    
    return false;
}

if (!isValidPath($filePath)) {
    header('HTTP/1.1 403 Forbidden');
    echo '유효하지 않은 파일 경로입니다.';
    exit;
}

// 파일 존재 여부 확인
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo '파일이 존재하지 않습니다.';
    exit;
}

// 파일 읽기 권한 확인
if (!is_readable($filePath)) {
    header('HTTP/1.1 403 Forbidden');
    echo '파일을 읽을 수 없습니다.';
    exit;
}

// 파일 정보 가져오기
$fileInfo = pathinfo($filePath);
$fileName = $fileInfo['basename'];
$fileExt = strtolower($fileInfo['extension']);

// MIME 타입 설정
$mimeTypes = [
    'txt' => 'text/plain',
    'html' => 'text/html',
    'htm' => 'text/html',
    'json' => 'application/json',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip'
];

$contentType = isset($mimeTypes[$fileExt]) ? $mimeTypes[$fileExt] : 'application/octet-stream';

// 다운로드 헤더 설정
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// 출력 버퍼 비우기
ob_clean();
flush();

// 파일 전송
readfile($filePath);
exit;
?>