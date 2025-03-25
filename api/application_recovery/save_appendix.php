<?php
include '../../config.php';

// 기본 필드 설정
$data = [
	'case_no' => (int)$_POST['case_no'],
	'creditor_count' => (int)$_POST['creditor_count'],
	'appendix_type' => $_POST['appendix_type'] ?? '(근)저당권설정',
	'property_detail' => $_POST['property_detail'],
	'expected_value' => isset($_POST['expected_value']) ? (float)str_replace(',', '', $_POST['expected_value']) : null,
	'evaluation_rate' => isset($_POST['evaluation_rate']) ? (float)$_POST['evaluation_rate'] : null,
	'secured_expected_claim' => isset($_POST['secured_expected_claim']) ? (float)str_replace(',', '', $_POST['secured_expected_claim']) : null,
	'unsecured_remaining_claim' => isset($_POST['unsecured_remaining_claim']) ? (float)str_replace(',', '', $_POST['unsecured_remaining_claim']) : null,
	'rehabilitation_secured_claim' => isset($_POST['rehabilitation_secured_claim']) ? (float)str_replace(',', '', $_POST['rehabilitation_secured_claim']) : null
];

// 타입에 따른 추가 필드 설정
switch ($data['appendix_type']) {
	case '(근)저당권설정':
		$data['max_claim'] = isset($_POST['max_claim']) ? (float)str_replace(',', '', $_POST['max_claim']) : null;
		$data['registration_date'] = $_POST['registration_date'] ?? null;
		break;
	case '질권설정/채권양도(전세보증금)':
		$data['pledge_deposit'] = isset($_POST['pledge_deposit']) ? (float)str_replace(',', '', $_POST['pledge_deposit']) : null;
		$data['pledge_amount'] = isset($_POST['pledge_amount']) ? (float)str_replace(',', '', $_POST['pledge_amount']) : null;
		$data['lease_start_date'] = $_POST['lease_start_date'] ?? null;
		$data['lease_end_date'] = $_POST['lease_end_date'] ?? null;
		break;
	case '최우선변제임차권':
		$data['first_mortgage_date'] = $_POST['first_mortgage_date'] ?? null;
		$data['region'] = $_POST['region'] ?? null;
		$data['lease_deposit'] = isset($_POST['lease_deposit']) ? (float)str_replace(',', '', $_POST['lease_deposit']) : null;
		$data['top_priority_amount'] = isset($_POST['top_priority_amount']) ? (float)str_replace(',', '', $_POST['top_priority_amount']) : null;
		$data['top_lease_start_date'] = $_POST['top_lease_start_date'] ?? null;
		$data['top_lease_end_date'] = $_POST['top_lease_end_date'] ?? null;
		break;
	case '우선변제임차권':
		$data['priority_deposit'] = isset($_POST['priority_deposit']) ? (float)str_replace(',', '', $_POST['priority_deposit']) : null;
		$data['priority_lease_start_date'] = $_POST['priority_lease_start_date'] ?? null;
		$data['priority_lease_end_date'] = $_POST['priority_lease_end_date'] ?? null;
		$data['fixed_date'] = $_POST['fixed_date'] ?? null;
		break;
}

try {
	$pdo->beginTransaction();
	
	// 부모 레코드(application_recovery_creditor) 존재 확인
	$checkParentSql = "SELECT creditor_no FROM application_recovery_creditor 
					  WHERE case_no = ? AND creditor_count = ?";
	$checkParentStmt = $pdo->prepare($checkParentSql);
	$checkParentStmt->execute([$data['case_no'], $data['creditor_count']]);
	$parentExists = $checkParentStmt->fetchColumn();
	
	if (!$parentExists) {
		// 부모 레코드가 없는 경우, 에러 반환
		throw new Exception("채권자 정보가 존재하지 않습니다. 먼저 채권자 정보를 저장해주세요.");
	}
	
	// 기존 데이터 확인 (appendix_no 가져오기)
	$checkSql = "SELECT appendix_no FROM application_recovery_creditor_appendix 
				 WHERE case_no = ? AND creditor_count = ? LIMIT 1";
	$checkStmt = $pdo->prepare($checkSql);
	$checkStmt->execute([$data['case_no'], $data['creditor_count']]);
	$existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

	// SQL 쿼리 생성
	if ($existingRecord) {
		// 기존 appendix_no 사용
		$appendix_no = $existingRecord['appendix_no'];
		
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
		$sql .= " WHERE appendix_no = ? AND case_no = ? AND creditor_count = ?";
		$params[] = $appendix_no;
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
		'data' => $data,
		'is_update' => isset($existingRecord)
	]);
} catch (Exception $e) {
	$pdo->rollBack();
	error_log($e->getMessage());
	echo json_encode([
		'status' => 'error', 
		'message' => $e->getMessage(),
		'data' => $data
	]);
}
?>