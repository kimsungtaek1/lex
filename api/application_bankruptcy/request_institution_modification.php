<?php
// api/application_recovery/request_institution_modification.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../../config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO application_recovery_financial_institution_requests 
        (original_institution_no, name, address, phone, fax, request_type, 
        request_source, source_ip) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
    $stmt->execute([
        $data['original_institution_no'] ?? null,
        $data['name'],
        $data['address'],
        $data['phone'],
        $data['fax'],
        empty($data['original_institution_no']) ? '추가' : '수정',
        $_SERVER['HTTP_ORIGIN'] ?? null,
        $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode(['success' => true, 'request_id' => $pdo->lastInsertId()]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '요청 처리 중 오류가 발생했습니다']);
    error_log($e->getMessage());
}
?>