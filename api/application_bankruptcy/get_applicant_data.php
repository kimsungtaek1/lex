<?php
// api/application_bankruptcy/get_applicant_data.php
header('Content-Type: application/json');
session_start();

// 세션 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
	echo json_encode([
		'success' => false,
		'message' => '접근 권한이 없습니다.'
	]);
	exit;
}

// 데이터베이스 연결
include_once '../../config.php';

// 사건번호 확인
if (!isset($_GET['case_no']) || empty($_GET['case_no'])) {
	echo json_encode([
		'success' => false,
		'message' => '사건번호가 필요합니다.'
	]);
	exit;
}

$case_no = $_GET['case_no'];

try {
	// 권한 확인 (관리자가 아닌 경우 자신의 사건만 조회 가능)
	if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 10) {
		$authQuery = "SELECT consultant FROM case_management WHERE case_no = :case_no";
		$authStmt = $pdo->prepare($authQuery);
		$authStmt->bindParam(':case_no', $case_no, PDO::PARAM_INT);
		$authStmt->execute();
		$authRow = $authStmt->fetch();
		
		if ($authRow && $authRow['consultant'] != $_SESSION['employee_no']) {
			echo json_encode([
				'success' => false,
				'message' => '권한이 없습니다. 해당 사건은 다른 직원에게 배정되었습니다.'
			]);
			exit;
		}
	}
	
	// 기본 파산 신청 데이터 조회
	$query = "SELECT b.*, c.case_number, c.court_name 
			FROM application_bankruptcy b
			JOIN case_management c ON b.case_no = c.case_no
			WHERE b.case_no = :case_no";
	
	$stmt = $pdo->prepare($query);
	$stmt->bindParam(':case_no', $case_no, PDO::PARAM_INT);
	$stmt->execute();
	
	if ($stmt->rowCount() === 0) {
		// 데이터가 없는 경우 기본 데이터 구조만 반환
		echo json_encode([
			'success' => true,
			'data' => [
				'case_no' => $case_no,
				'name' => '',
				'resident_number' => '',
				'registered_address' => '',
				'now_address' => '',
				'base_address' => '',
				'phone' => '',
				'work_phone' => '',
				'email' => '',
				'application_date' => date('Y-m-d'),
				'court_name' => '',
				'case_number' => '',
				'creditor_count' => 0,
				'stay_order_apply' => 0,
				'exemption_apply' => 0,
				'support_org' => '',
				'support_details' => ''
			]
		]);
		exit;
	}
	
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// 응답 데이터 포맷
	echo json_encode([
		'success' => true,
		'data' => $data
	]);
	
} catch(PDOException $e) {
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류: ' . $e->getMessage()
	]);
}
?>