<?php
include_once '../../config.php';

header('Content-Type: application/json');

try {
	// 파라미터 받기
	$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

	// 사무장 목록 조회
	$managerQuery = "SELECT employee_no, name FROM employee WHERE position = '사무장' AND status = '재직' ORDER BY employee_no";
	$managerStmt = $pdo->prepare($managerQuery);
	$managerStmt->execute();
	$managers = $managerStmt->fetchAll(PDO::FETCH_ASSOC);

	$result = [];
	$trendData = [];
	
	// 각 월별 데이터 수집
	for ($month = 1; $month <= 12; $month++) {
		$startDate = sprintf("%04d-%02d-01", $year, $month);
		$endDate = date('Y-m-t', strtotime($startDate));
		
		$formattedMonth = date('Y. m', strtotime($startDate));
		
		$monthlyData = [
			'month' => $month,
			'month_name' => $formattedMonth,
			'managers' => [],
			'total' => ['inflow' => 0, 'contract' => 0]
		];
		
		// 트렌드 데이터용 월 이름
		$monthName = date('n월', strtotime($startDate));
		
		// 각 사무장별 데이터 조회
		foreach ($managers as $manager) {
			// 유입 건수 조회
			$inflowQuery = "SELECT COUNT(*) as count FROM inflow WHERE DATE(datetime) BETWEEN :start_date AND :end_date AND manager = :manager";
			$inflowStmt = $pdo->prepare($inflowQuery);
			$inflowStmt->bindParam(':start_date', $startDate);
			$inflowStmt->bindParam(':end_date', $endDate);
			$inflowStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
			$inflowStmt->execute();
			$inflowCount = $inflowStmt->fetch(PDO::FETCH_COLUMN);
			
			// 계약 건수 조회
			$contractQuery = "SELECT COUNT(*) as count FROM consult_manager WHERE DATE(datetime) BETWEEN :start_date AND :end_date AND consultant = :manager AND contract = 1";
			$contractStmt = $pdo->prepare($contractQuery);
			$contractStmt->bindParam(':start_date', $startDate);
			$contractStmt->bindParam(':end_date', $endDate);
			$contractStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
			$contractStmt->execute();
			$contractCount = $contractStmt->fetch(PDO::FETCH_COLUMN);
			
			$managerData = [
				'inflow' => (int)$inflowCount,
				'contract' => (int)$contractCount
			];
			
			$monthlyData['managers'][] = $managerData;
			$monthlyData['total']['inflow'] += $managerData['inflow'];
			$monthlyData['total']['contract'] += $managerData['contract'];
		}
		
		$result[] = $monthlyData;
		
		// 트렌드 차트 데이터
		$trendData[] = [
			'month' => $monthName,
			'inflow' => $monthlyData['total']['inflow'],
			'contract' => $monthlyData['total']['contract']
		];
	}
	
	echo json_encode([
		'success' => true,
		'data' => $result,
		'trend' => $trendData,
		'managers' => $managers,
		'year' => $year
	], JSON_THROW_ON_ERROR);

} catch (Exception $e) {
	// 모든 오류를 JSON으로 반환
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage(),
		'file' => $e->getFile(),
		'line' => $e->getLine()
	], JSON_THROW_ON_ERROR);
	exit;
}
?>