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

// 필수 필드
$court_case_number = $_POST['court_case_number'] ?? '';
$debtor_name = $_POST['debtor_name'] ?? '';
$service_date = $_POST['service_date'] ?? null;
$confirmation_date = $_POST['confirmation_date'] ?? null;
$claim_range = $_POST['claim_range'] ?? '';

if (!$case_no || !$creditor_count) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();

	// 먼저 다른 채권 유형 데이터를 모두 삭제
	// 1. 별제권부채권 삭제
	$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_appendix WHERE case_no = ? AND creditor_count = ?");
	$stmt->execute([$case_no, $creditor_count]);
	
	// 2. 다툼있는 채권 삭제
	$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_claims WHERE case_no = ? AND creditor_count = ?");
	$stmt->execute([$case_no, $creditor_count]);
	
	// 3. 기타 채무 삭제
	$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_debts WHERE case_no = ? AND creditor_count = ?");
	$stmt->execute([$case_no, $creditor_count]);

	if ($claim_no) {
		// 수정
		$sql = "
			UPDATE application_recovery_creditor_assigned_claims 
			SET court_case_number = ?,
				debtor_name = ?,
				service_date = ?,
				confirmation_date = ?,
				claim_range = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
		";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			$court_case_number,
			$debtor_name,
			$service_date,
			$confirmation_date,
			$claim_range,
			$claim_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$sql = "
			INSERT INTO application_recovery_creditor_assigned_claims 
			(case_no, creditor_count, court_case_number, debtor_name, service_date, 
			 confirmation_date, claim_range, created_at, updated_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
		";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			$case_no,
			$creditor_count,
			$court_case_number,
			$debtor_name,
			$service_date,
			$confirmation_date,
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