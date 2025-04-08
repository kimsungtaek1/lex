<?php
// 접근 권한 확인
session_start();
if (!isset($_SESSION['employee_no'])) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

// 데이터베이스 연결 설정
require_once($_SERVER['DOCUMENT_ROOT'].'/adm/db/db_connect.php');

// JSON 데이터 수신
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 데이터 유효성 검사
if (!isset($data['case_no']) || empty($data['case_no'])) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// 기존 데이터 삭제 (덮어쓰기 방식)
	$stmt = $pdo->prepare("
		DELETE FROM application_recovery_salary_calculation 
		WHERE case_no = :case_no
	");
	$stmt->execute(['case_no' => $data['case_no']]);
	
	// 기본 정보 저장
	$stmt = $pdo->prepare("
		INSERT INTO application_recovery_salary_calculation 
		(case_no, year, calculation_type, monthly_average, yearly_amount, created_at, updated_at) 
		VALUES 
		(:case_no, :year, :calculation_type, :monthly_average, :yearly_amount, NOW(), NOW())
	");
	
	$stmt->execute([
		'case_no' => $data['case_no'],
		'year' => $data['year'],
		'calculation_type' => $data['calculation_type'],
		'monthly_average' => $data['monthly_average'],
		'yearly_amount' => $data['yearly_amount']
	]);
	
	$calculationId = $pdo->lastInsertId();
	
	// 소득 행 저장
	if (isset($data['income_rows']) && is_array($data['income_rows'])) {
		foreach ($data['income_rows'] as $index => $row) {
			$stmt = $pdo->prepare("
				INSERT INTO application_recovery_salary_calculation_rows 
				(calculation_id, row_type, row_order, row_name, 
				month1, month2, month3, month4, month5, month6, 
				month7, month8, month9, month10, month11, month12, row_total) 
				VALUES 
				(:calculation_id, 'income', :row_order, :row_name, 
				:month1, :month2, :month3, :month4, :month5, :month6, 
				:month7, :month8, :month9, :month10, :month11, :month12, :row_total)
			");
			
			$stmt->execute([
				'calculation_id' => $calculationId,
				'row_order' => $index + 1,
				'row_name' => $row['name'],
				'month1' => $row['monthly_data']['month1'] ?? 0,
				'month2' => $row['monthly_data']['month2'] ?? 0,
				'month3' => $row['monthly_data']['month3'] ?? 0,
				'month4' => $row['monthly_data']['month4'] ?? 0,
				'month5' => $row['monthly_data']['month5'] ?? 0,
				'month6' => $row['monthly_data']['month6'] ?? 0,
				'month7' => $row['monthly_data']['month7'] ?? 0,
				'month8' => $row['monthly_data']['month8'] ?? 0,
				'month9' => $row['monthly_data']['month9'] ?? 0,
				'month10' => $row['monthly_data']['month10'] ?? 0,
				'month11' => $row['monthly_data']['month11'] ?? 0,
				'month12' => $row['monthly_data']['month12'] ?? 0,
				'row_total' => $row['total']
			]);
		}
	}
	
	// 공제 행 저장
	if (isset($data['deduction_rows']) && is_array($data['deduction_rows'])) {
		foreach ($data['deduction_rows'] as $index => $row) {
			$stmt = $pdo->prepare("
				INSERT INTO application_recovery_salary_calculation_rows 
				(calculation_id, row_type, row_order, row_name, 
				month1, month2, month3, month4, month5, month6, 
				month7, month8, month9, month10, month11, month12, row_total) 
				VALUES 
				(:calculation_id, 'deduction', :row_order, :row_name, 
				:month1, :month2, :month3, :month4, :month5, :month6, 
				:month7, :month8, :month9, :month10, :month11, :month12, :row_total)
			");
			
			$stmt->execute([
				'calculation_id' => $calculationId,
				'row_order' => $index + 1,
				'row_name' => $row['name'],
				'month1' => $row['monthly_data']['month1'] ?? 0,
				'month2' => $row['monthly_data']['month2'] ?? 0,
				'month3' => $row['monthly_data']['month3'] ?? 0,
				'month4' => $row['monthly_data']['month4'] ?? 0,
				'month5' => $row['monthly_data']['month5'] ?? 0,
				'month6' => $row['monthly_data']['month6'] ?? 0,
				'month7' => $row['monthly_data']['month7'] ?? 0,
				'month8' => $row['monthly_data']['month8'] ?? 0,
				'month9' => $row['monthly_data']['month9'] ?? 0,
				'month10' => $row['monthly_data']['month10'] ?? 0,
				'month11' => $row['monthly_data']['month11'] ?? 0,
				'month12' => $row['monthly_data']['month12'] ?? 0,
				'row_total' => $row['total']
			]);
		}
	}
	
	// 급여 수입 업데이트 (월 소득과 연간 소득)
	$stmt = $pdo->prepare("
		INSERT INTO application_recovery_income_salary 
		(case_no, monthly_income, yearly_income, created_at, updated_at) 
		VALUES 
		(:case_no, :monthly_income, :yearly_income, NOW(), NOW())
		ON DUPLICATE KEY UPDATE 
		monthly_income = :monthly_income, 
		yearly_income = :yearly_income, 
		updated_at = NOW()
	");
	
	$stmt->execute([
		'case_no' => $data['case_no'],
		'monthly_income' => $data['monthly_average'],
		'yearly_income' => $data['yearly_amount']
	]);
	
	$pdo->commit();
	
	header('Content-Type: application/json');
	echo json_encode(['success' => true, 'message' => '데이터가 성공적으로 저장되었습니다.']);
	
} catch (PDOException $e) {
	$pdo->rollBack();
	error_log('월평균소득계산기 저장 오류: ' . $e->getMessage());
	
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
}
?>