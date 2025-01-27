<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_GET['case_no'] ?? 0;

if (!$case_no) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    // 채권자 정보 조회
    $stmt = $pdo->prepare("
        SELECT * FROM application_recovery_creditor 
        WHERE case_no = ? 
        ORDER BY creditor_count ASC
    ");
    $stmt->execute([$case_no]);
    $creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $creditors
    ]);

} catch (Exception $e) {
    error_log("채권자 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '채권자 정보를 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}