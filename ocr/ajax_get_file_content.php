<?php
// 모든 출력 버퍼링 시작
ob_start();

// 초기화 시 오류 표시 끄기 (프로덕션 환경 권장)
// 개발 중에는 오류를 확인하는 것이 좋습니다. 필요하다면 다음 주석을 해제하세요.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// JSON 응답 반환 함수 (파일 상단으로 이동하여 어디서든 사용 가능하게 함)
function sendJsonResponse($success, $message, $data = []) {
    // 기존 출력 버퍼 내용 지우기 (오류 메시지나 예기치 않은 출력을 방지)
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        // 데이터가 이미 JSON 문자열인 경우 (JSON 파일 내용 자체)를 처리하지 않도록 주의
        // 여기서는 $data 배열을 response 배열과 병합합니다.
        $response = array_merge($response, $data);
    }

    // Content-Type 헤더 설정 전에 헤더가 이미 전송되었는지 확인
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8'); // UTF-8 명시
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE); // 유니코드 깨짐 방지
    exit; // 응답 후 스크립트 종료
}

// 경로 유효성 검증 함수
function isValidPath($path) {
    global $config; // 전역 설정 변수 사용

    // 실제 경로 확인 및 .. (상위 디렉토리 이동) 방지
    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, '..') !== false) {
        return false;
    }

    // 허용된 기본 경로 목록 정의
    $allowedBasePaths = [
        realpath($config['output_path']),
        realpath($config['upload_path']),
        realpath($config['temp_path']),
        realpath($config['model_path'])
        // 필요에 따라 다른 허용 경로 추가
    ];

    // $realPath가 허용된 기본 경로 중 하나로 시작하는지 확인
    foreach ($allowedBasePaths as $basePath) {
        if ($basePath !== false && strpos($realPath, $basePath) === 0) {
            return true;
        }
    }

    // 허용되지 않은 경로
    return false;
}


// === 요청 처리 시작 ===

// 요청 메소드 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, '잘못된 요청 메소드입니다.');
    // sendJsonResponse 함수에서 exit 처리하므로 별도 exit 필요 없음
}

// 파일 경로 파라미터 확인
if (empty($_GET['path'])) {
    sendJsonResponse(false, '파일 경로가 필요합니다.');
}
$filePath = $_GET['path'];

// raw 파라미터 확인 (주로 HTML 파일 원본 보기용)
$raw = isset($_GET['raw']) && $_GET['raw'] === 'true';

// 보안 검증: 경로 유효성 및 실제 파일 존재 여부 확인
if (!isValidPath($filePath) || !file_exists($filePath) || !is_file($filePath)) {
    // file_exists만으로는 디렉토리도 true를 반환하므로 is_file() 추가
    sendJsonResponse(false, '유효하지 않거나 존재하지 않는 파일 경로입니다.');
}

try {
    // 파일 크기 확인 (과도한 메모리 사용 방지)
    $fileSize = filesize($filePath);
    if ($fileSize === false) {
        sendJsonResponse(false, '파일 크기를 확인할 수 없습니다.');
    }
    if ($fileSize > 10 * 1024 * 1024) { // 10MB 제한 (필요에 따라 조절)
        sendJsonResponse(false, '파일 크기가 너무 큽니다. 최대 10MB까지 지원합니다.');
    }
    if ($fileSize === 0) {
        // 0바이트 파일 처리 (빈 내용으로 성공 처리 또는 오류 처리 선택)
         sendJsonResponse(true, '파일 내용은 비어 있습니다.', ['content' => '', 'file_name' => basename($filePath), 'file_size' => 0]);
    }

    // 파일 내용 읽기
    $content = file_get_contents($filePath);
    if ($content === false) {
        sendJsonResponse(false, '파일을 읽는 데 실패했습니다.');
    }

    // 파일 확장자 확인 (소문자로 변환하여 비교)
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // === 파일 타입별 처리 분기 ===

    // 1. JSON 파일 처리
    if ($fileExtension === 'json') {
        // JSON 파일은 원본 내용 그대로 반환 (htmlspecialchars 적용 안 함)
        // 여기서 $content는 JSON 문자열 자체입니다.
        // sendJsonResponse 함수는 전체 응답 구조를 JSON으로 인코딩합니다.
        sendJsonResponse(true, 'JSON 파일을 성공적으로 읽었습니다.', [
            'content' => $content, // 원본 JSON 문자열
            'file_name' => basename($filePath),
            'file_size' => $fileSize
        ]);
    }
    // 2. HTML 파일을 raw=true로 요청한 경우
    elseif ($raw && $fileExtension === 'html') {
        // 원본 HTML 내용 그대로 반환
        sendJsonResponse(true, 'HTML 파일을 성공적으로 읽었습니다 (raw).', [
            'content' => $content, // 원본 HTML 문자열
            'file_name' => basename($filePath),
            'file_size' => $fileSize
        ]);
    }
    // 3. 그 외 파일 (예: txt) 처리
    else {
        // 다른 텍스트 파일들은 잠재적 XSS 공격 방지를 위해 htmlspecialchars 적용
        // JavaScript에서 원본 문자가 필요하다면, JavaScript 측에서 디코딩 필요
        $escapedContent = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        sendJsonResponse(true, '파일을 성공적으로 읽었습니다.', [
            'content' => $escapedContent, // 이스케이프된 문자열
            'file_name' => basename($filePath),
            'file_size' => $fileSize
        ]);
    }

} catch (Exception $e) {
    // 예외 처리
    // 개발 중에는 $e->getMessage()를 포함하는 것이 디버깅에 도움됨
    // error_log('파일 읽기 오류: ' . $e->getMessage()); // 서버 로그에 기록
    sendJsonResponse(false, '파일 처리 중 오류가 발생했습니다.');
}

// 스크립트 끝에서 버퍼 내용 출력 (정상적인 경우 sendJsonResponse에서 exit되므로 실행되지 않음)
// ob_end_flush();
?>