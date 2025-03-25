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

// 필수 필드 확인
$original_creditor = $_POST['original_creditor'] ?? '';
$debtor_name = $_POST['debtor_name'] ?? '';
$court_name = $_POST['court_name'] ?? '';
$case_number = $_POST['case_number'] ?? '';
$order_amount = floatval($_POST['order_amount'] ?? 0);
$order_date = $_POST['order_date'] ?? null;
$remark = $_POST['remark'] ?? '';

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
			SET original_creditor = ?, 
				debtor_name = ?,
				court_name = ?,
				case_number = ?,
				order_amount = ?,
				order_date = ?,
				remark = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
		");
		$stmt->execute([
			$original_creditor,
			$debtor_name,
			$court_name,
			$case_number,
			$order_amount,
			$order_date,
			$remark,
			$claim_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$stmt = $pdo->prepare("
			INSERT INTO application_recovery_creditor_assigned_claims 
			(case_no, creditor_count, original_creditor, debtor_name, court_name, 
			 case_number, order_amount, order_date, remark, created_at, updated_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
		");
		$stmt->execute([
			$case_no,
			$creditor_count,
			$original_creditor,
			$debtor_name,
			$court_name,
			$case_number,
			$order_amount,
			$order_date,
			$remark
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