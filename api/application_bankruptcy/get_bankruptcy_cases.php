<?php
// api/application_bankruptcy/get_bankruptcy_cases.php
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

try {
	// 파산 사건 목록 조회
	// 관리자(auth=10)는 모든 사건 조회 가능, 일반 직원은 자신의 사건만 조회 가능
	if (isset($_SESSION['auth']) && $_SESSION['auth'] >= 10) {
		// 관리자는 모든 사건 조회 가능
		$query = "SELECT c.case_no, c.case_number, b.name, c.status, c.consultant, e.name as consultant_name
				FROM case_management c
				LEFT JOIN application_bankruptcy b ON c.case_no = b.case_no
				LEFT JOIN employee e ON c.consultant = e.employee_no
				WHERE c.category = '개인파산'
				ORDER BY c.case_no DESC";
		
		$stmt = $pdo->prepare($query);
		$stmt->execute();
	} else {
		// 일반 직원은 자신의 사건만 조회 가능
		// employee_no가 세션에 없을 경우 오류 방지를 위해 기본값 0으로 설정
		$employee_no = isset($_SESSION['employee_no']) ? $_SESSION['employee_no'] : 0;
		
		$query = "SELECT c.case_no, c.case_number, b.name, c.status, c.consultant, e.name as consultant_name
				FROM case_management c
				LEFT JOIN application_bankruptcy b ON c.case_no = b.case_no
				LEFT JOIN employee e ON c.consultant = e.employee_no
				WHERE c.category = '개인파산' AND c.consultant = :employee_no
				ORDER BY c.case_no DESC";
		
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(':employee_no', $employee_no, PDO::PARAM_INT);
		$stmt->execute();
	}
	
	$cases = [];
	
	while ($row = $stmt->fetch()) {
		$cases[] = [
			'case_no' => $row['case_no'],
			'case_number' => $row['case_number'],
			'name' => $row['name'],
			'status' => $row['status'],
			'consultant' => $row['consultant'],
			'consultant_name' => $row['consultant_name']
		];
	}
	
	echo json_encode([
		'success' => true,
		'data' => $cases
	]);
	
} catch(PDOException $e) {
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류: ' . $e->getMessage()
	]);
}
?>