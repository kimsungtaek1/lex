<?php
include '../../config.php';

if (!isset($_POST['case_no']) || !isset($_POST['creditor_count'])) {
	echo json_encode([
		'success' => false,
		'message' => '필수 파라미터가 누락되었습니다.'
	]);
	exit;
}

$case_no = (int)$_POST['case_no'];
$creditor_count = (int)$_POST['creditor_count'];

try {
	$pdo->beginTransaction();
	
	// 삭제 쿼리 준비
	$sql = "DELETE FROM application_recovery_creditor_appendix 
			WHERE case_no = ? AND creditor_count = ?";
	$params = [$case_no, $creditor_count];
	
	$stmt = $pdo->prepare($sql);
	$result = $stmt->execute($params);
	
	if ($result) {
		$pdo->commit();
		echo json_encode([
			'success' => true,
			'message' => '삭제되었습니다.'
		]);
	} else {
		throw new Exception('삭제 중 오류가 발생했습니다.');
	}
} catch (Exception $e) {
	$pdo->rollBack();
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
}
?>