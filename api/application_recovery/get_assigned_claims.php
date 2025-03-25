<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

$case_no = $_GET['case_no'] ?? 0;
$creditor_count = $_GET['creditor_count'] ?? 0;
$claim_no = $_GET['claim_no'] ?? null;

if (!$case_no || !$creditor_count) {
	echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
	exit;
}

try {
	$sql = "
		SELECT *
		FROM application_recovery_creditor_assigned_claims
		WHERE case_no = ? AND creditor_count = ?
	";
	$params = [$case_no, $creditor_count];
	
	// claim_no가 있는 경우 해당 claim_no에 대한 데이터만 조회
	if ($claim_no) {
		$sql .= " AND claim_no = ?";
		$params[] = $claim_no;
	}
	
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'success' => true,
		'data' => $claims
	]);

} catch (Exception $e) {
	error_log("전부명령된 채권 조회 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '조회 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}
?>