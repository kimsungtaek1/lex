<?php
/**
 * AJAX 요청 처리 공통 함수
 */
require_once 'config.php';

/**
 * AJAX 요청 유효성 검증
 */
function validateAjaxRequest($method = 'POST', $requiredParams = []) {
    // 메소드 검증
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        sendJsonResponse(false, '잘못된 요청 메소드입니다.');
        return false;
    }
    
    // 필수 파라미터 검증
    $params = ($method === 'POST') ? $_POST : $_GET;
    foreach ($requiredParams as $param) {
        if (empty($params[$param])) {
            sendJsonResponse(false, "{$param} 파라미터가 필요합니다.");
            return false;
        }
    }
    
    return true;
}

/**
 * JSON 응답 반환
 */
function sendJsonResponse($success, $message, $data = []) {
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