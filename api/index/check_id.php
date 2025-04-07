<?php
require_once '../../config.php';

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userid'])) {
	$userid = trim($_POST['userid']);
	
	// 입력값 검증
	if (strlen($userid) < 4) {
		echo 'invalid'; // 아이디가 너무 짧은 경우
		exit;
	}
	
	try {
		// 데이터베이스에서 아이디 중복 확인
		$query = "SELECT COUNT(*) FROM employee WHERE employee_id = ?";
		$stmt = $pdo->prepare($query);
		$stmt->execute([$userid]);
		
		$count = $stmt->fetchColumn();
		
		if ($count > 0) {
			echo 'unavailable'; // 이미 사용 중인 아이디
		} else {
			echo 'available'; // 사용 가능한 아이디
		}
	} catch (PDOException $e) {
		// 오류 발생 시 처리
		error_log('아이디 중복확인 중 오류 발생: ' . $e->getMessage());
		echo 'error';
	}
} else {
	// 잘못된 요청 처리
	echo 'error';
}
?>