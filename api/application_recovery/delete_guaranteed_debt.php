<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

$debt_no = $_POST['debt_no'] ?? 0;

if (!$debt_no) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$stmt = $pdo->prepare("
		DELETE FROM application_recovery_creditor_guaranteed_debts 
		WHERE debt_no = ?
	");
	$stmt->execute([$debt_no]);

	echo json_encode([
		'success' => true,
		'message' => '삭제되었습니다.'
	]);

} catch (Exception $e) {
	error_log("보증인채무 삭제 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '삭제 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}