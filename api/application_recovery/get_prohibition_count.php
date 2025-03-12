<?php
header('Content-Type: application/json');
require_once '../../config.php';

$case_no = $_GET['case_no'] ?? '';

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM application_recovery_prohibition_orders 
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
        'message' => $e->getMessage()
    ]);
}