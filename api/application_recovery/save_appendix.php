<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
	exit;
}

// 필수 파라미터 확인
$case_no = isset($_POST['case_no']) ? intval($_POST['case_no']) : 0;
$creditor_count = isset($_POST['creditor_count']) ? intval($_POST['creditor_count']) : 0;
$appendix_no = isset($_POST['appendix_no']) ? intval($_POST['appendix_no']) : 0;

if (!$case_no || !$creditor_count) {
	echo json_encode(['status' => 'error', 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

// 채권 유형 및 기본 데이터
$appendix_type = isset($_POST['appendix_type']) ? $_POST['appendix_type'] : '(근)저당권설정';
$property_detail = isset($_POST['property_detail']) ? $_POST['property_detail'] : '';
$expected_value = isset($_POST['expected_value']) ? intval(str_replace(',', '', $_POST['expected_value'])) : 0;
$evaluation_rate = isset($_POST['evaluation_rate']) ? $_POST['evaluation_rate'] : null;
$secured_expected_claim = isset($_POST['secured_expected_claim']) ? intval(str_replace(',', '', $_POST['secured_expected_claim'])) : 0;
$unsecured_remaining_claim = isset($_POST['unsecured_remaining_claim']) ? intval(str_replace(',', '', $_POST['unsecured_remaining_claim'])) : 0;
$rehabilitation_secured_claim = isset($_POST['rehabilitation_secured_claim']) ? intval(str_replace(',', '', $_POST['rehabilitation_secured_claim'])) : 0;

// 유형별 추가 데이터
$max_claim = isset($_POST['max_claim']) ? intval(str_replace(',', '', $_POST['max_claim'])) : null;
$registration_date = isset($_POST['registration_date']) && !empty($_POST['registration_date']) ? $_POST['registration_date'] : null;

$pledge_deposit = isset($_POST['pledge_deposit']) ? intval(str_replace(',', '', $_POST['pledge_deposit'])) : 0;
$pledge_amount = isset($_POST['pledge_amount']) ? intval(str_replace(',', '', $_POST['pledge_amount'])) : 0;
$lease_start_date = isset($_POST['lease_start_date']) && !empty($_POST['lease_start_date']) ? $_POST['lease_start_date'] : null;
$lease_end_date = isset($_POST['lease_end_date']) && !empty($_POST['lease_end_date']) ? $_POST['lease_end_date'] : null;

$first_mortgage_date = isset($_POST['first_mortgage_date']) && !empty($_POST['first_mortgage_date']) ? $_POST['first_mortgage_date'] : null;
$region = isset($_POST['region']) ? $_POST['region'] : null;
$lease_deposit = isset($_POST['lease_deposit']) ? intval(str_replace(',', '', $_POST['lease_deposit'])) : 0;
$top_priority_amount = isset($_POST['top_priority_amount']) ? intval(str_replace(',', '', $_POST['top_priority_amount'])) : 0;
$top_lease_start_date = isset($_POST['top_lease_start_date']) && !empty($_POST['top_lease_start_date']) ? $_POST['top_lease_start_date'] : null;
$top_lease_end_date = isset($_POST['top_lease_end_date']) && !empty($_POST['top_lease_end_date']) ? $_POST['top_lease_end_date'] : null;

$priority_deposit = isset($_POST['priority_deposit']) ? intval(str_replace(',', '', $_POST['priority_deposit'])) : 0;
$priority_lease_start_date = isset($_POST['priority_lease_start_date']) && !empty($_POST['priority_lease_start_date']) ? $_POST['priority_lease_start_date'] : null;
$priority_lease_end_date = isset($_POST['priority_lease_end_date']) && !empty($_POST['priority_lease_end_date']) ? $_POST['priority_lease_end_date'] : null;
$fixed_date = isset($_POST['fixed_date']) && !empty($_POST['fixed_date']) ? $_POST['fixed_date'] : null;

try {
	$pdo->beginTransaction();
	
	// 기존 데이터 확인
	$check_sql = "
		SELECT appendix_no 
		FROM application_recovery_creditor_appendix 
		WHERE case_no = ? AND creditor_count = ?
	";
	$check_stmt = $pdo->prepare($check_sql);
	$check_stmt->execute([$case_no, $creditor_count]);
	$existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($existing_data) {
		// 기존 데이터 업데이트
		$update_sql = "
			UPDATE application_recovery_creditor_appendix
			SET appendix_type = ?,
				property_detail = ?,
				expected_value = ?,
				evaluation_rate = ?,
				max_claim = ?,
				registration_date = ?,
				secured_expected_claim = ?,
				unsecured_remaining_claim = ?,
				rehabilitation_secured_claim = ?,
				pledge_deposit = ?,
				pledge_amount = ?,
				lease_start_date = ?,
				lease_end_date = ?,
				first_mortgage_date = ?,
				region = ?,
				lease_deposit = ?,
				top_priority_amount = ?,
				top_lease_start_date = ?,
				top_lease_end_date = ?,
				priority_deposit = ?,
				priority_lease_start_date = ?,
				priority_lease_end_date = ?,
				fixed_date = ?,
				updated_at = NOW()
			WHERE case_no = ? AND creditor_count = ?
		";
		
		$update_stmt = $pdo->prepare($update_sql);
		$update_stmt->execute([
			$appendix_type,
			$property_detail,
			$expected_value,
			$evaluation_rate,
			$max_claim,
			$registration_date,
			$secured_expected_claim,
			$unsecured_remaining_claim,
			$rehabilitation_secured_claim,
			$pledge_deposit,
			$pledge_amount,
			$lease_start_date,
			$lease_end_date,
			$first_mortgage_date,
			$region,
			$lease_deposit,
			$top_priority_amount,
			$top_lease_start_date,
			$top_lease_end_date,
			$priority_deposit,
			$priority_lease_start_date,
			$priority_lease_end_date,
			$fixed_date,
			$case_no,
			$creditor_count
		]);
		
		$appendix_no = $existing_data['appendix_no'];
		$message = '부속서류가 업데이트되었습니다.';
	} else {
		// 새 데이터 추가
		$insert_sql = "
			INSERT INTO application_recovery_creditor_appendix (
				case_no, 
				creditor_count, 
				appendix_type, 
				property_detail,
				expected_value,
				evaluation_rate,
				max_claim,
				registration_date,
				secured_expected_claim,
				unsecured_remaining_claim,
				rehabilitation_secured_claim,
				pledge_deposit,
				pledge_amount,
				lease_start_date,
				lease_end_date,
				first_mortgage_date,
				region,
				lease_deposit,
				top_priority_amount,
				top_lease_start_date,
				top_lease_end_date,
				priority_deposit,
				priority_lease_start_date,
				priority_lease_end_date,
				fixed_date,
				created_at,
				updated_at
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
				?, ?, ?, ?, ?, NOW(), NOW()
			)
		";
		
		$insert_stmt = $pdo->prepare($insert_sql);
		$insert_stmt->execute([
			$case_no,
			$creditor_count,
			$appendix_type,
			$property_detail,
			$expected_value,
			$evaluation_rate,
			$max_claim,
			$registration_date,
			$secured_expected_claim,
			$unsecured_remaining_claim,
			$rehabilitation_secured_claim,
			$pledge_deposit,
			$pledge_amount,
			$lease_start_date,
			$lease_end_date,
			$first_mortgage_date,
			$region,
			$lease_deposit,
			$top_priority_amount,
			$top_lease_start_date,
			$top_lease_end_date,
			$priority_deposit,
			$priority_lease_start_date,
			$priority_lease_end_date,
			$fixed_date
		]);
		
		$appendix_no = $pdo->lastInsertId();
		$message = '부속서류가 등록되었습니다.';
	}
	
	$pdo->commit();
	
	echo json_encode([
		'status' => 'success',
		'message' => $message,
		'appendix_no' => $appendix_no
	]);
	
} catch (Exception $e) {
	$pdo->rollBack();
	error_log("부속서류 저장 오류: " . $e->getMessage());
	
	echo json_encode([
		'status' => 'error',
		'message' => '저장 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}
?>