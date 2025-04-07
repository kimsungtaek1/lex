<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// 세션 체크
if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

// 요청 메소드와 액션 파라미터 확인
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET 요청 처리 (데이터 조회)
if ($method === 'GET') {
	switch ($action) {
		case 'list':
			getGuarantors();
			break;
		case 'count':
			getGuarantorCount();
			break;
		default:
			echo json_encode(['success' => false, 'message' => '유효하지 않은 액션입니다.']);
	}
}
// POST 요청 처리 (데이터 저장/삭제)
else if ($method === 'POST') {
	switch ($action) {
		case 'save':
			saveGuarantor();
			break;
		case 'delete':
			deleteGuarantor();
			break;
		default:
			echo json_encode(['success' => false, 'message' => '유효하지 않은 액션입니다.']);
	}
}
else {
	echo json_encode(['success' => false, 'message' => '지원하지 않는 메소드입니다.']);
}

// 보증인 목록 조회 함수
function getGuarantors() {
	global $pdo;
	
	$case_no = $_GET['case_no'] ?? 0;
	$creditor_count = $_GET['creditor_count'] ?? 0;
	$debt_no = $_GET['debt_no'] ?? null;

	if (!$case_no || !$creditor_count) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$sql = "SELECT * FROM application_recovery_creditor_guaranteed_debts 
				WHERE case_no = ? AND creditor_count = ?";
		$params = [$case_no, $creditor_count];
		
		// 특정 보증인 조회인 경우
		if ($debt_no) {
			$sql .= " AND debt_no = ?";
			$params[] = $debt_no;
		}
		
		$sql .= " ORDER BY debt_no ASC";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		$guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		echo json_encode([
			'success' => true,
			'data' => $guarantors
		]);

	} catch (Exception $e) {
		error_log("보증인 조회 오류: " . $e->getMessage());
		echo json_encode([
			'success' => false,
			'message' => '보증인 정보를 불러오는 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}

// 보증인 수 조회 함수
function getGuarantorCount() {
	global $pdo;
	
	$case_no = $_GET['case_no'] ?? 0;
	$creditor_count = $_GET['creditor_count'] ?? 0;

	if (!$case_no || !$creditor_count) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("
			SELECT COUNT(*) as count
			FROM application_recovery_creditor_guaranteed_debts
			WHERE case_no = ? AND creditor_count = ?
		");
		
		$stmt->execute([$case_no, $creditor_count]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			'success' => true,
			'count' => $result['count']
		]);

	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'message' => '조회 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}

// 보증인 저장 함수
function saveGuarantor() {
	global $pdo;
	
	$case_no = $_POST['case_no'] ?? null;
	$creditor_count = $_POST['creditor_count'] ?? null;
	$guarantor_name = $_POST['guarantor_name'] ?? null;

	if (!$case_no || !$creditor_count || !$guarantor_name) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$pdo->beginTransaction();
		
		// 금액 데이터 전처리
		$guarantee_amount = !empty($_POST['guarantee_amount']) ? str_replace(',', '', $_POST['guarantee_amount']) : '0';

		// 숫자 유효성 검사
		if (!is_numeric($guarantee_amount)) {
			throw new Exception("유효하지 않은 금액이 입력되었습니다.");
		}

		// 기존 데이터 확인
		$debt_no = $_POST['debt_no'] ?? null;
		
		if ($debt_no) {
			// 수정 쿼리
			$sql = "
				UPDATE application_recovery_creditor_guaranteed_debts SET
					guarantor_name = ?,
					guarantor_address = ?,
					guarantee_amount = ?,
					guarantee_date = ?,
					updated_at = CURRENT_TIMESTAMP
				WHERE debt_no = ?
			";
			
			$params = [
				$guarantor_name,
				$_POST['guarantor_address'] ?? '',
				$guarantee_amount,
				$_POST['guarantee_date'] ?? null,
				$debt_no
			];
			
		} else {
			// 신규 등록 쿼리
			$sql = "
				INSERT INTO application_recovery_creditor_guaranteed_debts (
					case_no,
					creditor_count,
					guarantor_name,
					guarantor_address,
					guarantee_amount,
					guarantee_date,
					created_at,
					updated_at
				) VALUES (
					?, ?, ?, ?, ?, ?, 
					CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
				)
			";
			
			$params = [
				$case_no,
				$creditor_count,
				$guarantor_name,
				$_POST['guarantor_address'] ?? '',
				$guarantee_amount,
				$_POST['guarantee_date'] ?? null
			];
		}

		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute($params);

		if ($result) {
			$pdo->commit();
			echo json_encode([
				'success' => true,
				'message' => '보증인 정보가 저장되었습니다.',
				'debt_no' => $debt_no ? $debt_no : $pdo->lastInsertId()
			]);
		} else {
			throw new Exception("쿼리 실행 실패");
		}

	} catch (Exception $e) {
		$pdo->rollBack();
		error_log("보증인 저장 오류: " . $e->getMessage());
		
		echo json_encode([
			'success' => false,
			'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
		]);
	}
}

// 보증인 삭제 함수
function deleteGuarantor() {
	global $pdo;
	
	$debt_no = $_POST['debt_no'] ?? 0;

	if (!$debt_no) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$pdo->beginTransaction();
		
		// 보증인 삭제
		$stmt = $pdo->prepare("
			DELETE FROM application_recovery_creditor_guaranteed_debts 
			WHERE debt_no = ?
		");
		$stmt->execute([$debt_no]);

		$pdo->commit();
		echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);

	} catch (Exception $e) {
		$pdo->rollBack();
		error_log("보증인 삭제 오류: " . $e->getMessage());
		echo json_encode([
			'success' => false,
			'message' => '삭제 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}