<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

$claim_no = $_POST['claim_no'] ?? 0;

if (!$claim_no) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$stmt = $pdo->prepare("
		DELETE FROM application_recovery_additional_claims 
		WHERE claim_no = ?
	");
	$stmt->execute([$claim_no]);

	echo json_encode([
		'success' => true,
		'message' => '삭제되었습니다.'
	]);

} catch (Exception $e) {
	error_log("기타미확정채권 삭제 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '삭제 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}