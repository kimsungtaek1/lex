<?php
session_start();

// 로그아웃 전에 사용자 정보 저장
$employee_id = $_SESSION['employee_id'] ?? 'unknown';
$employee_no = $_SESSION['employee_no'] ?? 'unknown';

// 세션 파괴
session_destroy();

// JSON 응답
$response = [
    'success' => true,
    'message' => '로그아웃되었습니다.',
    'redirect' => '/adm/index.php'
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);