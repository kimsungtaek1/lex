<?php
include '../../config.php';

// 기본 필드 설정
$data = [
	'case_no' => (int)$_POST['case_no'],
	'creditor_count' => (int)$_POST['creditor_count'],
	'appendix_no' => isset($_POST['appendix_no']) && $_POST['appendix_no'] !== '' ? (int)$_POST['appendix_no'] : null,
	'appendix_type' => $_POST['appendix_type'] ?? '(근)저당권설정',
	'property_detail' => $_POST['property_detail'],
	'expected_value' => isset($_POST['expected_value']) ? (float)$_POST['expected_value'] : null,
	'evaluation_rate' => isset($_POST['evaluation_rate']) ? (float)$_POST['evaluation_rate'] : null,
	'secured_expected_claim' => isset($_POST['secured_expected_claim']) ? (float)$_POST['secured_expected_claim'] : null,
	'unsecured_remaining_claim' => isset($_POST['unsecured_remaining_claim']) ? (float)$_POST['unsecured_remaining_claim'] : null,
	'rehabilitation_secured_claim' => isset($_POST['rehabilitation_secured_claim']) ? (float)$_POST['rehabilitation_secured_claim'] : null
];

// 타입에 따른 추가 필드 설정
switch ($data['appendix_type']) {
	case '(근)저당권설정':
		$data['max_claim'] = isset($_POST['max_claim']) ? (float)$_POST['max_claim'] : null;
		$data['registration_date'] = $_POST['registration_date'] ?? null;
		break;
	case '질권설정/채권양도(전세보증금)':
		$data['pledge_deposit'] = isset($_POST['pledge_deposit']) ? (float)$_POST['pledge_deposit'] : null;
		$data['pledge_amount'] = isset($_POST['pledge_amount']) ? (float)$_POST['pledge_amount'] : null;
		$data['lease_start_date'] = $_POST['lease_start_date'] ?? null;
		$data['lease_end_date'] = $_POST['lease_end_date'] ?? null;
		break;
	case '최우선변제임차권':
		$data['first_mortgage_date'] = $_POST['first_mortgage_date'] ?? null;
		$data['region'] = $_POST['region'] ?? null;
		$data['lease_deposit'] = isset($_POST['lease_deposit']) ? (float)$_POST['lease_deposit'] : null;
		$data['top_priority_amount'] = isset($_POST['top_priority_amount']) ? (float)$_POST['top_priority_amount'] : null;
		$data['top_lease_start_date'] = $_POST['top_lease_start_date'] ?? null;
		$data['top_lease_end_date'] = $_POST['top_lease_end_date'] ?? null;
		break;
	case '우선변제임차권':
		$data['priority_deposit'] = isset($_POST['priority_deposit']) ? (float)$_POST['priority_deposit'] : null;
		$data['priority_lease_start_date'] = $_POST['priority_lease_start_date'] ?? null;
		$data['priority_lease_end_date'] = $_POST['priority_lease_end_date'] ?? null;
		$data['fixed_date'] = $_POST['fixed_date'] ?? null;
		break;
}

try {
	$pdo->beginTransaction();
	
	// 기존 데이터 확인
	$checkSql = "SELECT COUNT(*) FROM application_recovery_creditor_appendix 
				 WHERE case_no = ? AND creditor_count = ?";
	$checkStmt = $pdo->prepare($checkSql);
	$checkStmt->execute([$data['case_no'], $data['creditor_count']]);
	$exists = $checkStmt->fetchColumn();

	// SQL 쿼리 생성 (타입에 따라 다른 필드 포함)
	if ($exists) {
		// 업데이트 쿼리 생성
		$sql = "UPDATE application_recovery_creditor_appendix SET ";
		$updateFields = [];
		$params = [];
		
		foreach ($data as $key => $value) {
			if ($key !== 'case_no' && $key !== 'creditor_count') {
				$updateFields[] = "$key = ?";
				$params[] = $value;
			}
		}
		
		$sql .= implode(', ', $updateFields);
		$sql .= " WHERE case_no = ? AND creditor_count = ?";
		$params[] = $data['case_no'];
		$params[] = $data['creditor_count'];
	} else {
		// 삽입 쿼리 생성
		$fields = array_keys($data);
		$placeholders = array_fill(0, count($fields), '?');
		
		$sql = "INSERT INTO application_recovery_creditor_appendix (" . implode(', ', $fields) . ") 
				VALUES (" . implode(', ', $placeholders) . ")";
		$params = array_values($data);
	}

	// 쿼리 실행
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	
	$pdo->commit();
	
	echo json_encode([
		'status' => 'success',
		'message' => '데이터가 성공적으로 저장되었습니다',
		'data' => $data
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	error_log($e->getMessage());
	echo json_encode([
		'status' => 'error', 
		'message' => $e->getMessage(),
		'data' => $data
	]);
}
?>