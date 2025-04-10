<?php
require_once '../../config.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_no'])) {
    echo json_encode([
        'success' => false,
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

if (!isset($_GET['case_no'])) {
    echo json_encode([
        'success' => false,
        'message' => '필수 파라미터가 누락되었습니다.'
    ]);
    exit;
}

$case_no = intval($_GET['case_no']);

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM application_recovery_stay_orders 
        WHERE case_no = ?
    ");
    $stmt->execute([$case_no]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => $result['count']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}