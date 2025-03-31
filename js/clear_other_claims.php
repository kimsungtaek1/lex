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

try {
	$pdo->beginTransaction();
	
	// 제외할 채권 유형 외의 다른 모든 채권 데이터 삭제
	if ($exclude_type != 'appendix') {
		$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_appendix WHERE case_no = ? AND creditor_count = ?");
		$stmt->execute([$case_no, $creditor_count]);
	}
	
	if ($exclude_type != 'other_claim') {
		$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_claims WHERE case_no = ? AND creditor_count = ?");
		$stmt->execute([$case_no, $creditor_count]);
	}
	
	if ($exclude_type != 'assigned_claim') {
		$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_assigned_claims WHERE case_no = ? AND creditor_count = ?");
		$stmt->execute([$case_no, $creditor_count]);
	}
	
	if ($exclude_type != 'other_debt') {
		$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_debts WHERE case_no = ? AND creditor_count = ?");
		$stmt->execute([$case_no, $creditor_count]);
	}
	
	$pdo->commit();
	
	echo json_encode([
		'success' => true,
		'message' => '다른 채권 유형 데이터가 삭제되었습니다.'
	]);
	
} catch (Exception $e) {
	$pdo->rollBack();
	error_log("다른 채권 유형 데이터 삭제 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '데이터 삭제 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}