<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_POST['case_no'] ?? 0;
$creditor_ids = $_POST['creditor_ids'] ?? '';

if (!$case_no || empty($creditor_ids)) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // JSON 문자열을 배열로 변환
    $creditorArray = json_decode($creditor_ids, true);
    
    if (!is_array($creditorArray)) {
        throw new Exception('유효하지 않은 채권자 데이터입니다.');
    }

    foreach ($creditorArray as $creditorId) {
        // 채권자 삭제
        $stmt = $pdo->prepare("
            DELETE FROM application_recovery_creditor 
            WHERE case_no = ? AND creditor_count = ?
        ");
        $stmt->execute([$case_no, $creditorId]);
    }

    // 채권자 번호 재정렬
    $stmt = $pdo->prepare("
        SELECT creditor_no, creditor_count 
        FROM application_recovery_creditor 
        WHERE case_no = ? 
        ORDER BY creditor_count ASC
    ");
    $stmt->execute([$case_no]);
    $creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $newCount = 1;
    foreach ($creditors as $creditor) {
        if ($creditor['creditor_count'] != $newCount) {
            $updateStmt = $pdo->prepare("
                UPDATE application_recovery_creditor 
                SET creditor_count = ? 
                WHERE creditor_no = ?
            ");
            $updateStmt->execute([$newCount, $creditor['creditor_no']]);
        }
        $newCount++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("채권자 삭제 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '삭제 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}