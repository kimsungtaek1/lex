<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// 세션 체크
if (!isset($_SESSION['employee_no'])) {
	writeLog("권한 없음: employee_no가 세션에 없음");
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

// POST 데이터 검증
$case_no = $_POST['case_no'] ?? null;
$creditor_count = $_POST['creditor_count'] ?? null;

if (!$case_no || !$creditor_count) {
	writeLog("필수 데이터 누락 - case_no: {$case_no}, creditor_count: {$creditor_count}");
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// 금액 데이터 전처리
	$initial_claim = !empty($_POST['initial_claim']) ? str_replace(',', '', $_POST['initial_claim']) : 0;
	$remaining_principal = !empty($_POST['remaining_principal']) ? str_replace(',', '', $_POST['remaining_principal']) : 0;
	$remaining_interest = !empty($_POST['remaining_interest']) ? str_replace(',', '', $_POST['remaining_interest']) : 0;

	// 숫자 유효성 검사
	if (!is_numeric($initial_claim) || !is_numeric($remaining_principal) || !is_numeric($remaining_interest)) {
		throw new Exception("유효하지 않은 금액이 입력되었습니다.");
	}

	// 기존 데이터 확인
	$stmt = $pdo->prepare("
		SELECT creditor_no 
		FROM application_bankruptcy_creditor 
		WHERE case_no = ? AND creditor_count = ?
	");
	$stmt->execute([$case_no, $creditor_count]);
	$existingData = $stmt->fetch(PDO::FETCH_ASSOC);

	// 일자 처리
	$borrowing_date = !empty($_POST['borrowing_date']) ? $_POST['borrowing_date'] : null;

	if ($existingData && isset($existingData['creditor_no'])) {
		// 수정 쿼리
		$sql = "
			UPDATE application_bankruptcy_creditor SET
				financial_institution = ?,
				address = ?,
				phone = ?,
				fax = ?,
				borrowing_date = ?,
				separate_bond = ?,
				reason_detail = ?,
				usage_detail = ?,
				initial_claim = ?,
				remaining_principal = ?,
				remaining_interest = ?,
				updated_at = CURRENT_TIMESTAMP
			WHERE case_no = ? AND creditor_count = ?
		";
		$params = [
			$_POST['financial_institution'] ?? '',
			$_POST['address'] ?? '',
			$_POST['phone'] ?? '',
			$_POST['fax'] ?? '',
			$borrowing_date,
			$_POST['separate_bond'] ?? '금원차용',
			$_POST['reason_detail'] ?? '',
			$_POST['usage_detail'] ?? '',
			$initial_claim,
			$remaining_principal,
			$remaining_interest,
			$case_no,
			$creditor_count
		];
	} else {
		// 신규 등록 쿼리
		$sql = "
			INSERT INTO application_bankruptcy_creditor (
				case_no,
				creditor_count,
				financial_institution,
				address,
				phone,
				fax,
				borrowing_date,
				separate_bond,
				reason_detail,
				usage_detail,
				initial_claim,
				remaining_principal,
				remaining_interest,
				created_at,
				updated_at
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
			)
		";
		$params = [
			$case_no,
			$creditor_count,
			$_POST['financial_institution'] ?? '',
			$_POST['address'] ?? '',
			$_POST['phone'] ?? '',
			$_POST['fax'] ?? '',
			$borrowing_date,
			$_POST['separate_bond'] ?? '금원차용',
			$_POST['reason_detail'] ?? '',
			$_POST['usage_detail'] ?? '',
			$initial_claim,
			$remaining_principal,
			$remaining_interest
		];
	}

	$stmt = $pdo->prepare($sql);
	$result = $stmt->execute($params);

	if ($result) {
		$pdo->commit();
		writeLog("채권자 저장 성공 - case_no: {$case_no}, creditor_count: {$creditor_count}");
		echo json_encode([
			'success' => true,
			'message' => '채권자 정보가 저장되었습니다.',
			'creditor_no' => $existingData ? $existingData['creditor_no'] : $pdo->lastInsertId()
		]);
	} else {
		throw new Exception("쿼리 실행 실패");
	}

} catch (Exception $e) {
	$pdo->rollBack();
	$errorInfo = [
		'message' => $e->getMessage(),
		'file' => $e->getFile(),
		'line' => $e->getLine(),
		'trace' => $e->getTraceAsString(),
		'post_data' => $_POST
	];
	
	writeLog("채권자 저장 오류: " . json_encode($errorInfo, JSON_UNESCAPED_UNICODE));
	
	echo json_encode([
		'success' => false,
		'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage(),
		'error' => $errorInfo
	]);
}