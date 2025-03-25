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
$claim_no = $_POST['claim_no'] ?? 0;

$court_name = $_POST['court_name'] ?? '';
$case_number = $_POST['case_number'] ?? '';
$original_creditor = $_POST['original_creditor'] ?? '';
$debtor_name = $_POST['debtor_name'] ?? '';
$order_amount = floatval($_POST['order_amount'] ?? 0);
$order_date = $_POST['order_date'] ?? null;
$claim_range = $_POST['claim_range'] ?? '';

if (!$case_no || !$creditor_count) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();

	if ($claim_no) {
		// 수정
		$stmt = $pdo->prepare("
			UPDATE application_recovery_creditor_assigned_claims 
			SET court_name = ?,
				case_number = ?,
				original_creditor = ?,
				debtor_name = ?,
				order_amount = ?,
				order_date = ?,
				claim_range = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
		");
		$stmt->execute([
			$court_name,
			$case_number,
			$original_creditor,
			$debtor_name,
			$order_amount,
			$order_date,
			$claim_range,
			$claim_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$stmt = $pdo->prepare("
			INSERT INTO application_recovery_creditor_assigned_claims 
			(case_no, creditor_count, court_name, case_number, 
			 original_creditor, debtor_name, order_amount, order_date, claim_range)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
		");
		$stmt->execute([
			$case_no,
			$creditor_count,
			$court_name,
			$case_number,
			$original_creditor,
			$debtor_name,
			$order_amount,
			$order_date,
			$claim_range
		]);
		$claim_no = $pdo->lastInsertId();
	}

	$pdo->commit();
	echo json_encode([
		'success' => true,
		'message' => '저장되었습니다.',
		'claim_no' => $claim_no
	]);

} catch (Exception $e) {
	$pdo->rollBack();
	error_log("전부명령된 채권 저장 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '저장 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}
?>