<<<<<<< HEAD
<?php
session_start();

// 로그 함수 정의
function writeLog($type, $message, $data = []) {
    $logFile = dirname(__FILE__) . '/logs/' . date('Y-m') . '_login.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $logMessage = sprintf(
        "[%s] [%s] [IP: %s] [UA: %s] %s - %s\n",
        $timestamp,
        $type,
        $ip,
        $userAgent,
        $message,
        json_encode($data, JSON_UNESCAPED_UNICODE)
    );
    
    error_log($logMessage, 3, $logFile);
}

// 로그아웃 전에 사용자 정보 저장
$employee_id = $_SESSION['employee_id'] ?? 'unknown';
$employee_no = $_SESSION['employee_no'] ?? 'unknown';

// 세션 파괴
session_destroy();

// 로그아웃 로그 기록
writeLog('LOGOUT', '로그아웃 성공', [
    'employee_id' => $employee_id,
    'employee_no' => $employee_no
]);

// JSON 응답
$response = [
    'success' => true,
    'message' => '로그아웃되었습니다.',
    'redirect' => '/adm/index.php'
];

header('Content-Type: application/json; charset=utf-8');
=======
<?php
session_start();

// 로그 함수 정의
function writeLog($type, $message, $data = []) {
    $logFile = dirname(__FILE__) . '/logs/' . date('Y-m') . '_login.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $logMessage = sprintf(
        "[%s] [%s] [IP: %s] [UA: %s] %s - %s\n",
        $timestamp,
        $type,
        $ip,
        $userAgent,
        $message,
        json_encode($data, JSON_UNESCAPED_UNICODE)
    );
    
    error_log($logMessage, 3, $logFile);
}

// 로그아웃 전에 사용자 정보 저장
$employee_id = $_SESSION['employee_id'] ?? 'unknown';
$employee_no = $_SESSION['employee_no'] ?? 'unknown';

// 세션 파괴
session_destroy();

// 로그아웃 로그 기록
writeLog('LOGOUT', '로그아웃 성공', [
    'employee_id' => $employee_id,
    'employee_no' => $employee_no
]);

// JSON 응답
$response = [
    'success' => true,
    'message' => '로그아웃되었습니다.',
    'redirect' => '/adm/index.php'
];

header('Content-Type: application/json; charset=utf-8');
>>>>>>> 719d7c8 (Delete all files)
echo json_encode($response, JSON_UNESCAPED_UNICODE);