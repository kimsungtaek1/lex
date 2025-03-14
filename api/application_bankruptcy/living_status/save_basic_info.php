<?php
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
	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
	$stmt->execute(['case_no' => $case_no]);
	$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$data = [
		'case_no' => $case_no,
		'job_type' => $_POST['job_type'] ?? '',
		'job_industry' => $_POST['job_industry'] ?? '',
		'company_name' => $_POST['company_name'] ?? '',
		'employment_period' => $_POST['employment_period'] ?? ''
	];
	
	if ($existingData) {
		// UPDATE
		$sql = "UPDATE application_bankruptcy_living_status_basic 
				SET job_type = :job_type, 
					job_industry = :job_industry, 
					company_name = :company_name, 
					employment_period = :employment_period 
				WHERE case_no = :case_no";
	} else {
		// INSERT
		$sql = "INSERT INTO application_bankruptcy_living_status_basic 
				(case_no, job_type, job_industry, company_name, employment_period) 
				VALUES (:case_no, :job_type, :job_industry, :company_name, :employment_period)";
	}
	
	$stmt = $pdo->prepare($sql);
	$stmt->execute($data);
	
	echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
} catch (PDOException $e) {
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>