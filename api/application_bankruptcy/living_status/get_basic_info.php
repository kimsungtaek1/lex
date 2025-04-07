<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

$case_no = $_GET['case_no'] ?? null;

if (!$case_no) {
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
	$stmt->execute(['case_no' => $case_no]);
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>