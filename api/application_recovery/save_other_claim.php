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
$claim_type = $_POST['claim_type'] ?? '다툼있는채권';

// 필수 필드
$creditor_principal = $_POST['creditor_principal'] ?? 0;
$creditor_interest = $_POST['creditor_interest'] ?? 0;
$undisputed_principal = $_POST['undisputed_principal'] ?? 0;
$undisputed_interest = $_POST['undisputed_interest'] ?? 0;
$difference_principal = $_POST['difference_principal'] ?? 0;
$difference_interest = $_POST['difference_interest'] ?? 0;
$dispute_reason = $_POST['dispute_reason'] ?? '';
$litigation_status = $_POST['litigation_status'] ?? '';

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
	
	// 2. 전부명령된 채권 삭제
	$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_assigned_claims WHERE case_no = ? AND creditor_count = ?");
	$stmt->execute([$case_no, $creditor_count]);
	
	// 3. 기타 채무 삭제
	$stmt = $pdo->prepare("DELETE FROM application_recovery_creditor_other_debts WHERE case_no = ? AND creditor_count = ?");
	$stmt->execute([$case_no, $creditor_count]);

	if ($claim_no) {
		// 수정
		$stmt = $pdo->prepare("
			UPDATE application_recovery_creditor_other_claims 
			SET claim_type = ?, 
				creditor_principal = ?,
				creditor_interest = ?,
				undisputed_principal = ?,
				undisputed_interest = ?,
				difference_principal = ?,
				difference_interest = ?,
				dispute_reason = ?,
				litigation_status = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
		");
		$stmt->execute([
			$claim_type,
			$creditor_principal,
			$creditor_interest,
			$undisputed_principal,
			$undisputed_interest,
			$difference_principal,
			$difference_interest,
			$dispute_reason,
			$litigation_status,
			$claim_no,
			$case_no,
			$creditor_count
		]);
	} else {
		// 신규 등록
		$stmt = $pdo->prepare("
			INSERT INTO application_recovery_creditor_other_claims 
			(case_no, creditor_count, claim_type, 
			 creditor_principal, creditor_interest, undisputed_principal, undisputed_interest,
			 difference_principal, difference_interest, dispute_reason, litigation_status)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		");
		$stmt->execute([
			$case_no,
			$creditor_count,
			$claim_type,
			$creditor_principal,
			$creditor_interest,
			$undisputed_principal,
			$undisputed_interest,
			$difference_principal,
			$difference_interest,
			$dispute_reason,
			$litigation_status
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
	error_log("다툼있는 채권 저장 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '저장 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}
?>