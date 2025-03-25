<?php
include '../../config.php';

$case_no = $_GET['case_no'];
$appendix_no = $_GET['appendix_no'] ?? null;

try {
	// 기본 필드와 타입별 모든 필드를 포함한 SELECT 쿼리
	$sql = "SELECT 
				m.*,
				c.financial_institution AS creditor_name
			FROM application_recovery_creditor_appendix m
			LEFT JOIN application_recovery_creditor c
			  ON m.case_no = c.case_no
			  AND m.creditor_count = c.creditor_count
			WHERE m.case_no = ? 
			  AND (m.appendix_no = ? OR ? IS NULL)
			ORDER BY m.creditor_count";
	
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$case_no, $appendix_no, $appendix_no]);
	
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	if (count($results) > 0) {
		echo json_encode([
			'success' => true,
			'data' => $results
		]);
	} else {
		echo json_encode([
			'success' => true,
			'data' => [],
			'message' => '데이터가 없습니다'
		]);
	}
} catch (PDOException $e) {
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
}
?>