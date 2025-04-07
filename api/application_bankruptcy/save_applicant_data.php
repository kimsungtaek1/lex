<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config.php';
session_start();

// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isset($_SESSION['employee_no'])) {
	echo json_encode([
		'success' => false,
		'message' => '로그인이 필요합니다.'
	]);
	exit;
}

// POST 요청 체크
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode([
		'success' => false,
		'message' => '잘못된 요청 방식입니다.'
	]);
	exit;
}

// 필수 파라미터 체크
if (!isset($_POST['case_no']) || empty($_POST['case_no'])) {
	echo json_encode([
		'success' => false,
		'message' => '사건번호가 누락되었습니다.'
	]);
	exit;
}

try {
	// PDO 연결 확인
	if (!isset($pdo) || !($pdo instanceof PDO)) {
		throw new Exception("Database connection not established");
	}

	$case_no = intval($_POST['case_no']);
	
	// 트랜잭션 시작
	$pdo->beginTransaction();
	
	// application_bankruptcy 테이블 업데이트/삽입
	$checkBankruptcyQuery = "SELECT bankruptcy_no FROM application_bankruptcy WHERE case_no = ?";
	$checkBankruptcyStmt = $pdo->prepare($checkBankruptcyQuery);
	$checkBankruptcyStmt->execute([$case_no]);
	$existingBankruptcy = $checkBankruptcyStmt->fetch(PDO::FETCH_ASSOC);
	
	if ($existingBankruptcy) {
		// 기존 데이터 업데이트
		$updateBankruptcyQuery = "
			UPDATE application_bankruptcy SET
				name = ?,
				resident_number = ?,
				registered_address = ?,
				now_address = ?,
				base_address = ?,
				phone = ?,
				work_phone = ?,
				email = ?,
				application_date = ?,
				court_name = ?,
				case_number = ?,
				creditor_count = ?,
				stay_order_apply = ?,
				exemption_apply = ?,
				support_org = ?,
				support_details = ?
			WHERE case_no = ?
		";
		
		$params = [
			$_POST['name_b'] ?? '',
			$_POST['residentNumber_b'] ?? '',
			$_POST['registeredAddress_b'] ?? '',
			$_POST['nowAddress_b'] ?? '',
			$_POST['baseAddress_b'] ?? '',
			$_POST['phone_b'] ?? '',
			$_POST['workPhone_b'] ?? '',
			$_POST['email_b'] ?? '',
			!empty($_POST['applicationDate_b']) ? date('Y-m-d', strtotime($_POST['applicationDate_b'])) : null,
			$_POST['court_b'] ?? '',
			$_POST['caseNumber_b'] ?? '',
			intval($_POST['creditorCount_b'] ?? 0),
			isset($_POST['stayOrderApply_b']) ? 1 : 0,
			isset($_POST['exemptionApply_b']) ? 1 : 0,
			$_POST['supportOrg_b'] ?? '',
			$_POST['supportDetails_b'] ?? '',
			$case_no
		];
		
		$updateBankruptcyStmt = $pdo->prepare($updateBankruptcyQuery);
		$updateBankruptcyStmt->execute($params);
	} else {
		// 새 데이터 삽입
		$insertBankruptcyQuery = "
			INSERT INTO application_bankruptcy (
				case_no,
				name,
				resident_number,
				registered_address,
				now_address,
				base_address,
				phone,
				work_phone,
				email,
				application_date,
				court_name,
				case_number,
				creditor_count,
				stay_order_apply,
				exemption_apply,
				support_org,
				support_details
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		";
		
		$params = [
			$case_no,
			$_POST['name_b'] ?? '',
			$_POST['residentNumber_b'] ?? '',
			$_POST['registeredAddress_b'] ?? '',
			$_POST['nowAddress_b'] ?? '',
			$_POST['baseAddress_b'] ?? '',
			$_POST['phone_b'] ?? '',
			$_POST['workPhone_b'] ?? '',
			$_POST['email_b'] ?? '',
			!empty($_POST['applicationDate_b']) ? date('Y-m-d', strtotime($_POST['applicationDate_b'])) : null,
			$_POST['court_b'] ?? '',
			$_POST['caseNumber_b'] ?? '',
			intval($_POST['creditorCount_b'] ?? 0),
			isset($_POST['stayOrderApply_b']) ? 1 : 0,
			isset($_POST['exemptionApply_b']) ? 1 : 0,
			$_POST['supportOrg_b'] ?? '',
			$_POST['supportDetails_b'] ?? ''
		];
		
		$insertBankruptcyStmt = $pdo->prepare($insertBankruptcyQuery);
		$insertBankruptcyStmt->execute($params);
	}
	
	// case_management 테이블 업데이트 (사건번호, 법원, 이름, 전화번호)
	$updateCaseQuery = "
		UPDATE case_management SET
			name = ?,
			phone = ?,
			case_number = ?,
			court_name = ?,
			updated_at = CURRENT_TIMESTAMP
		WHERE case_no = ?
	";
	
	$updateCaseParams = [
		$_POST['name_b'] ?? '',
		$_POST['phone_b'] ?? '',
		$_POST['caseNumber_b'] ?? '',
		$_POST['court_b'] ?? '',
		$case_no
	];
	
	$updateCaseStmt = $pdo->prepare($updateCaseQuery);
	$updateCaseStmt->execute($updateCaseParams);
	
	// income expenditure 테이블에 소득 유형 저장 (필요한 경우)
	if (isset($_POST['incomeType'])) {
		$checkIncomeQuery = "SELECT income_no FROM application_bankruptcy_income_expenditure WHERE case_no = ?";
		$checkIncomeStmt = $pdo->prepare($checkIncomeQuery);
		$checkIncomeStmt->execute([$case_no]);
		$existingIncome = $checkIncomeStmt->fetch(PDO::FETCH_ASSOC);
		
		$incomeType = '';
		switch ($_POST['incomeType']) {
			case '0': $incomeType = '급여'; break;
			case '1': $incomeType = '사업'; break;
			default: $incomeType = '기타';
		}
		
		if ($existingIncome) {
			$updateIncomeQuery = "
				UPDATE application_bankruptcy_income_expenditure SET
					income_type = ?
				WHERE case_no = ?
			";
			$updateIncomeStmt = $pdo->prepare($updateIncomeQuery);
			$updateIncomeStmt->execute([$incomeType, $case_no]);
		} else {
			$insertIncomeQuery = "
				INSERT INTO application_bankruptcy_income_expenditure (
					case_no, 
					income_type
				) VALUES (?, ?)
			";
			$insertIncomeStmt = $pdo->prepare($insertIncomeQuery);
			$insertIncomeStmt->execute([$case_no, $incomeType]);
		}
	}
	
	// 트랜잭션 커밋
	$pdo->commit();
	
	echo json_encode([
		'success' => true,
		'message' => '저장되었습니다.',
		'case_no' => $case_no
	]);

} catch (PDOException $e) {
	// 트랜잭션 롤백
	if (isset($pdo) && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
} catch (Exception $e) {
	// 트랜잭션 롤백
	if (isset($pdo) && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	
	echo json_encode([
		'success' => false,
		'message' => '오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}