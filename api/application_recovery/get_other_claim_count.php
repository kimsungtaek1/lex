<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_GET['case_no'] ?? 0;
$creditor_count = $_GET['creditor_count'] ?? 0;

if (!$case_no || !$creditor_count) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM application_recovery_creditor_other_claims
        WHERE case_no = ? AND creditor_count = ?
    ");
    
    $stmt->execute([$case_no, $creditor_count]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => $result['count']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다.'
    ]);
}