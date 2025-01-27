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

if (!isset($_POST['order_no'])) {
    echo json_encode([
        'success' => false,
        'message' => '필수 파라미터가 누락되었습니다.'
    ]);
    exit;
}

$order_no = intval($_POST['order_no']);

try {
    $stmt = $pdo->prepare("DELETE FROM application_recovery_stay_orders WHERE order_no = ?");
    $result = $stmt->execute([$order_no]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '삭제 실패'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}