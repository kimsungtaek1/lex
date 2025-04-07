<?php
// api/application_bankruptcy/living_status/save_income_info.php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

$case_no = $_POST['case_no'] ?? null;

if (!$case_no) {
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	// 기존 데이터 확인
	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_income WHERE case_no = :case_no");
	$stmt->execute(['case_no' => $case_no]);
	$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$data = [
		'case_no' => $case_no,
		'self_income' => (int)($_POST['self_income'] ?? 0),
		'monthly_salary' => (int)($_POST['monthly_salary'] ?? 0),
		'pension' => (int)($_POST['pension'] ?? 0),
		'living_support' => (int)($_POST['living_support'] ?? 0),
		'other_income' => (int)($_POST['other_income'] ?? 0)
	];
	
	if ($existingData) {
		// UPDATE
		$sql = "UPDATE application_bankruptcy_living_status_income 
				SET self_income = :self_income, 
					monthly_salary = :monthly_salary, 
					pension = :pension, 
					living_support = :living_support,
					other_income = :other_income
				WHERE case_no = :case_no";
	} else {
		// INSERT
		$sql = "INSERT INTO application_bankruptcy_living_status_income 
				(case_no, self_income, monthly_salary, pension, living_support, other_income) 
				VALUES (:case_no, :self_income, :monthly_salary, :pension, :living_support, :other_income)";
	}
	
	$stmt = $pdo->prepare($sql);
	$result = $stmt->execute($data);
	
	if ($result) {
		echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
	} else {
		echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
	}
} catch (PDOException $e) {
	error_log('Database error: ' . $e->getMessage());
	echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()]);
}
?>