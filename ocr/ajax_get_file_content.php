<?php
// 모든 출력 버퍼링 시작
ob_start();

// 오류 표시 끄기 (중요: JSON 응답에 영향을 주지 않도록)
error_reporting(0);
ini_set('display_errors', 0);

// ajax_get_file_content.php
require_once 'config.php';

// 요청 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다.');
    exit;
}

// 파일 경로 확인
if (empty($_GET['path'])) {
    sendJsonResponse(false, '파일 경로가 필요합니다.');
    exit;
}

$filePath = $_GET['path'];
$raw = isset($_GET['raw']) && $_GET['raw'] === 'true';

// 보안 검증: 경로가 적절한 디렉토리에 있는지 확인
if (!isValidPath($filePath)) {
    sendJsonResponse(false, '유효하지 않은 파일 경로입니다.');
    exit;
}

// JSON 응답 반환 함수
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

// 경로 유효성 검증
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

try {
    // 파일 존재 여부 확인
    if (!file_exists($filePath)) {
        sendJsonResponse(false, '파일이 존재하지 않습니다.');
        exit;
    }
    
    // 파일 크기 확인
    $fileSize = filesize($filePath);
    if ($fileSize > 5 * 1024 * 1024) { // 5MB 제한
        sendJsonResponse(false, '파일이 너무 큽니다. 최대 5MB까지 지원합니다.');
        exit;
    }
    
    // 파일 내용 읽기
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        sendJsonResponse(false, '파일 읽기 실패');
        exit;
    }
    
    // HTML 파일 내용을 raw로 반환하는 경우 (테이블 미리보기용)
    if ($raw && pathinfo($filePath, PATHINFO_EXTENSION) === 'html') {
        sendJsonResponse(true, '파일을 성공적으로 읽었습니다.', [
            'content' => $content,
            'file_name' => basename($filePath),
            'file_size' => $fileSize
        ]);
        exit;
    }
    
    // HTML이 아닌 경우 특수 문자 이스케이프
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    // 성공 응답
    sendJsonResponse(true, '파일을 성공적으로 읽었습니다.', [
        'content' => $content,
        'file_name' => basename($filePath),
        'file_size' => $fileSize
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(false, '파일 읽기 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>