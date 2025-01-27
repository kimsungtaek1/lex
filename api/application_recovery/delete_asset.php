<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_POST['case_no'] ?? 0;
$asset_no = $_POST['asset_no'] ?? 0;

if (!$case_no || !$asset_no) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 재산 데이터 삭제
    $stmt = $pdo->prepare("
        DELETE FROM application_recovery_assets 
        WHERE case_no = ? AND asset_no = ?
    ");
    
    $result = $stmt->execute([$case_no, $asset_no]);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => '삭제되었습니다.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    writeLog("재산 삭제 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '삭제 중 오류가 발생했습니다.'
    ]);
}