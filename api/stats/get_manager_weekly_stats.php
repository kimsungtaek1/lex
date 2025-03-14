<?php
include_once '../../config.php';

header('Content-Type: application/json');

try {
	// 파라미터 받기
	$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
	$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');

	// 해당 월의 주차 계산
	$firstDayOfMonth = new DateTime("$year-$month-01");
	$lastDayOfMonth = new DateTime($firstDayOfMonth->format('Y-m-t'));
	
	$weeks = [];
	$currentWeek = 1;
	$weekStart = clone $firstDayOfMonth;
	
	// 첫째 주 시작일이 일요일이 아니면 이전 주 일요일로 설정
	if ($weekStart->format('w') != 0) {
		$weekStart->modify('last Sunday');
	}
	
	while ($weekStart <= $lastDayOfMonth) {
		$weekEnd = clone $weekStart;
		$weekEnd->modify('+6 days');
		
		$displayStart = clone $weekStart;
		$displayEnd = clone $weekEnd;
		
		// 주간 날짜 범위 문자열 생성
		$dateRange = $displayStart->format('Y. m. d.') . ' ~ ' . $displayEnd->format('Y. m. d.');
		
		$weeks[] = [
			'week' => $currentWeek,
			'date_range' => $dateRange,
			'start_date' => $weekStart->format('Y-m-d'),
			'end_date' => $weekEnd->format('Y-m-d')
		];
		
		$weekStart->modify('+7 days');
		$currentWeek++;
	}
	
	// 사무장 목록 조회
	$managerQuery = "SELECT employee_no, name FROM employee WHERE position = '사무장' AND status = '재직' ORDER BY employee_no";
	$managerStmt = $pdo->prepare($managerQuery);
	$managerStmt->execute();
	$managers = $managerStmt->fetchAll(PDO::FETCH_ASSOC);
	
	$result = [];
	
	// 각 주차별 데이터 수집
	foreach ($weeks as $week) {
		$weekData = [
			'week' => $week['week'],
			'date_range' => $week['date_range'],
			'managers' => [],
			'total' => ['inflow' => 0, 'contract' => 0]
		];
		
		// 각 사무장별 데이터 조회
		foreach ($managers as $manager) {
			// 유입 건수 조회
			$inflowQuery = "SELECT COUNT(*) as count FROM inflow WHERE DATE(datetime) BETWEEN :start_date AND :end_date AND manager = :manager";
			$inflowStmt = $pdo->prepare($inflowQuery);
			$inflowStmt->bindParam(':start_date', $week['start_date']);
			$inflowStmt->bindParam(':end_date', $week['end_date']);
			$inflowStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
			$inflowStmt->execute();
			$inflowCount = $inflowStmt->fetch(PDO::FETCH_COLUMN);
			
			// 계약 건수 조회
			$contractQuery = "SELECT COUNT(*) as count FROM consult_manager WHERE DATE(datetime) BETWEEN :start_date AND :end_date AND consultant = :manager AND contract = 1";
			$contractStmt = $pdo->prepare($contractQuery);
			$contractStmt->bindParam(':start_date', $week['start_date']);
			$contractStmt->bindParam(':end_date', $week['end_date']);
			$contractStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
			$contractStmt->execute();
			$contractCount = $contractStmt->fetch(PDO::FETCH_COLUMN);
			
			$managerData = [
				'inflow' => (int)$inflowCount,
				'contract' => (int)$contractCount
			];
			
			$weekData['managers'][] = $managerData;
			$weekData['total']['inflow'] += $managerData['inflow'];
			$weekData['total']['contract'] += $managerData['contract'];
		}
		
		$result[] = $weekData;
	}
	
	// 주간 추세를 위한 데이터 가공
	$trendData = [];
	foreach ($result as $weekData) {
		$trendData[] = [
			'week' => $weekData['week'] . '주차',
			'inflow' => $weekData['total']['inflow'],
			'contract' => $weekData['total']['contract']
		];
	}
	
	echo json_encode([
		'success' => true,
		'data' => $result,
		'trend' => $trendData,
		'managers' => $managers
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