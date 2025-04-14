<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// 접근 권한 확인
session_start();
if (!isset($_SESSION['employee_no'])) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

// 데이터베이스 연결 설정
require_once($_SERVER['DOCUMENT_ROOT'].'/adm/config.php');

// 파라미터 확인
$case_no = isset($_GET['case_no']) ? $_GET['case_no'] : '';
if (empty($case_no)) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	// 기본 계산 데이터 조회
	$stmt = $pdo->prepare("
		SELECT *
		FROM application_recovery_salary_calculation
		WHERE case_no = :case_no
		ORDER BY id DESC
		LIMIT 1
	");
	$stmt->execute(['case_no' => $case_no]);
	$calculation = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$calculation) {
		// 데이터가 없는 경우 기본값 반환
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => [
				'year' => date('Y'),
				'monthly_average' => 0,
				'yearly_amount' => 0,
				'income_rows' => [],
				'deduction_rows' => []
			]
		]);
		exit;
	}
	
	// 행 데이터 조회
	$stmt = $pdo->prepare("
		SELECT *
		FROM application_recovery_salary_calculation_rows
		WHERE calculation_id = :calculation_id
		ORDER BY row_type, row_order
	");
	$stmt->execute(['calculation_id' => $calculation['id']]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 결과 데이터 구성
	$income_rows = [];
	$deduction_rows = [];
	
	foreach ($rows as $row) {
		$monthly_data = [];
		for ($i = 1; $i <= 12; $i++) {
			$monthly_data["month{$i}"] = (int)$row["month{$i}"];
		}
		
		$row_data = [
			'id' => $row['id'],
			'name' => $row['row_name'],
			'monthly_data' => $monthly_data,
			'total' => (int)$row['row_total']
		];
		
		if ($row['row_type'] === 'income') {
			$income_rows[] = $row_data;
		} else {
			$deduction_rows[] = $row_data;
		}
	}
	
	// 응답 데이터
	$response_data = [
		'year' => $calculation['year'],
		'month' => $calculation['month'],
		'period' => $calculation['period'],
		'monthly_average' => (int)$calculation['monthly_average'],
		'yearly_amount' => (int)$calculation['yearly_amount'],
		'income_rows' => $income_rows,
		'deduction_rows' => $deduction_rows
	];
	
	header('Content-Type: application/json');
	echo json_encode(['success' => true, 'data' => $response_data]);
	
} catch (PDOException $e) {
	// 에러 로그 기록
	error_log('월평균소득계산기 데이터 조회 오류: ' . $e->getMessage());
	
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.', 'error' => $e->getMessage()]);
}
?>