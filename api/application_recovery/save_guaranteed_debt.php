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
$debt_no = $_POST['debt_no'] ?? 0;

// 필수 필드 확인
$guarantor_name = $_POST['guarantor_name'] ?? '';
$guarantor_address = $_POST['guarantor_address'] ?? '';
$guarantee_amount = floatval($_POST['guarantee_amount'] ?? 0);
$guarantee_date = $_POST['guarantee_date'] ?? null;

if (!$case_no || !$creditor_count) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();

	if ($debt_no) {
		// 수정
		$stmt = $pdo->prepare("
			UPDATE application_recovery_creditor_guaranteed_debts 
			SET guarantor_name = ?, 
				guarantor_address = ?,
				guarantee_amount = ?,
				guarantee_date = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE debt_no = ? AND case_no = ? AND creditor_count = ?
		");
		$stmt->execute([
			$guarantor_name,
			$guarantor_address,
			$guarantee_amount,
			$guarantee_date,
			$debt_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$stmt = $pdo->prepare("
			INSERT INTO application_recovery_creditor_guaranteed_debts 
			(case_no, creditor_count, guarantor_name, guarantor_address, guarantee_amount, 
			 guarantee_date, created_at, updated_at)
			VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
		");
		$stmt->execute([
			$case_no,
			$creditor_count,
			$guarantor_name,
			$guarantor_address,
			$guarantee_amount,
			$guarantee_date
		]);
		$debt_no = $pdo->lastInsertId();
	}

	$pdo->commit();
	echo json_encode([
		'success' => true,
		'message' => '저장되었습니다.',
		'debt_no' => $debt_no
	]);

} catch (Exception $e) {
	$pdo->rollBack();
	error_log("보증인채무 저장 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '저장 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}