<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

require_once '../../../config.php';

// 사건번호(case_no)를 이용하여 부양가족 수(support='Y'인 가족 수 + 본인 1명)를 계산하는 API
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$case_no = $_GET['case_no'] ?? null;
	
	if (!$case_no) {
		echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
		exit;
	}
	
	try {
		// support가 'Y'인 가족 구성원 수 계산
		$sql = "SELECT COUNT(*) as support_count FROM application_recovery_family_members 
				WHERE case_no = :case_no AND support = 'Y'";
		$stmt = $pdo->prepare($sql);
		$stmt->execute(['case_no' => $case_no]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 본인(1명) + 부양가족 수
		$family_count = 1 + ($result['support_count'] ?? 0);
		
		echo json_encode([
			'success' => true, 
			'family_count' => $family_count
		]);
	} catch (PDOException $e) {
		http_response_code(500);
		echo json_encode([
			'success' => false, 
			'message' => '데이터베이스 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
} else {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => '지원하지 않는 메소드입니다.']);
}
?>