<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_POST['case_no'] ?? 0;
$creditor_count = $_POST['creditor_count'] ?? 0;
$exclude_type = $_POST['exclude_type'] ?? ''; // 제외할 채권 유형

if (!$case_no || !$creditor_count) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

// 디버깅 로그 추가
writeLog("clear_other_claims.php 호출: case_no=$case_no, creditor_count=$creditor_count, exclude_type=$exclude_type");

try {
    $pdo->beginTransaction();
    
    $deletedTables = [];
    
    // 별제권부채권 (appendix) 테이블 처리
    if ($exclude_type != 'appendix') {
        $stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_appendix WHERE case_no = ? AND creditor_count = ?");
        $stmt->execute([$case_no, $creditor_count]);
        $affected = $stmt->rowCount();
        $deletedTables['appendix'] = $affected;
        error_log("별제권부채권 삭제: $affected 행");
    }
    
    // 다툼있는 채권 (other_claim) 테이블 처리
    if ($exclude_type != 'other_claim') {
        $stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_claims WHERE case_no = ? AND creditor_count = ?");
        $stmt->execute([$case_no, $creditor_count]);
        $affected = $stmt->rowCount();
        $deletedTables['other_claim'] = $affected;
        error_log("다툼있는채권 삭제: $affected 행");
    }
    
    // 전부명령된 채권 (assigned_claim) 테이블 처리
    if ($exclude_type != 'assigned_claim') {
        $stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_assigned_claims WHERE case_no = ? AND creditor_count = ?");
        $stmt->execute([$case_no, $creditor_count]);
        $affected = $stmt->rowCount();
        $deletedTables['assigned_claim'] = $affected;
        error_log("전부명령된채권 삭제: $affected 행");
    }
    
    // 기타채무 (other_debt) 테이블 처리
    if ($exclude_type != 'other_debt') {
        $stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_debts WHERE case_no = ? AND creditor_count = ?");
        $stmt->execute([$case_no, $creditor_count]);
        $affected = $stmt->rowCount();
        $deletedTables['other_debt'] = $affected;
        error_log("기타채무 삭제: $affected 행");
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '다른 채권 유형 데이터가 삭제되었습니다.',
        'excluded_type' => $exclude_type,
        'deleted_tables' => $deletedTables
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    $errorMessage = "다른 채권 유형 데이터 삭제 오류: " . $e->getMessage();
    error_log($errorMessage);
    
    echo json_encode([
        'success' => false,
        'message' => '데이터 삭제 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
?>