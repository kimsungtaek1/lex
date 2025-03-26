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

// 필드 값 추출
$property_detail = $_POST['property_detail'] ?? '';
$expected_value = floatval($_POST['expected_value'] ?? 0);
$evaluation_rate = $_POST['evaluation_rate'] ?? '';
$trust_property_details = $_POST['trust_property_details'] ?? '';
$priority_certificate_amount = floatval($_POST['priority_certificate_amount'] ?? 0);
$registration_date = $_POST['registration_date'] ?? '';
$expected_payment = floatval($_POST['expected_payment'] ?? 0);
$unpaid_amount = floatval($_POST['unpaid_amount'] ?? 0);

if (!$case_no || !$creditor_count) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();

	if ($claim_no) {
		// 수정
		$stmt = $pdo->prepare("
			UPDATE application_recovery_additional_claims 
			SET claim_type = '기타미확정채권', 
				property_detail = ?,
				expected_value = ?,
				evaluation_rate = ?,
				trust_property_details = ?,
				priority_certificate_amount = ?,
				registration_date = ?,
				expected_payment = ?,
				unpaid_amount = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
		");
		$stmt->execute([
			$property_detail,
			$expected_value,
			$evaluation_rate,
			$trust_property_details,
			$priority_certificate_amount,
			$registration_date,
			$expected_payment,
			$unpaid_amount,
			$claim_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$stmt = $pdo->prepare("
			INSERT INTO application_recovery_additional_claims 
			(case_no, creditor_count, claim_type, property_detail, expected_value, evaluation_rate, 
			trust_property_details, priority_certificate_amount, registration_date, expected_payment, 
			unpaid_amount, created_at, updated_at)
			VALUES (?, ?, '기타미확정채권', ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
		");
		$stmt->execute([
			$case_no,
			$creditor_count,
			$property_detail,
			$expected_value,
			$evaluation_rate,
			$trust_property_details,
			$priority_certificate_amount,
			$registration_date,
			$expected_payment,
			$unpaid_amount
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
	error_log("기타미확정채권 저장 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '저장 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}