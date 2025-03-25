<?php
include '../../config.php';

header('Content-Type: application/json');

$case_no = isset($_GET['case_no']) ? (int)$_GET['case_no'] : 0;
$creditor_count = isset($_GET['creditor_count']) ? (int)$_GET['creditor_count'] : 0;

if (!$case_no || !$creditor_count) {
	echo json_encode([
		'exists' => false,
		'message' => '필수 파라미터가 누락되었습니다.'
	]);
	exit;
}

try {
	$stmt = $pdo->prepare("
		SELECT COUNT(*) AS count
		FROM application_recovery_creditor
		WHERE case_no = ? AND creditor_count = ?
	");
	$stmt->execute([$case_no, $creditor_count]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	
	echo json_encode([
		'exists' => (int)$result['count'] > 0,
		'message' => (int)$result['count'] > 0 ? '채권자 정보가 존재합니다.' : '채권자 정보가 존재하지 않습니다.'
	]);
} catch (Exception $e) {
	echo json_encode([
		'exists' => false,
		'message' => '오류가 발생했습니다: ' . $e->getMessage()
	]);
}
?>