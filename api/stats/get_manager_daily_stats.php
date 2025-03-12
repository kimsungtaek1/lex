<?php
include_once '../../config.php';

header('Content-Type: application/json');

try {
	// 현재 월 기준으로 데이터 조회 (예: 3월)
	$currentMonth = date('m');
	$currentYear = date('Y');
	$daysInMonth = date('t');
	
	// 사무장 목록 조회
	$managerQuery = "SELECT employee_no, name FROM employee WHERE position = '사무장' AND status = '재직' ORDER BY employee_no";
	$managerStmt = $pdo->prepare($managerQuery);
	$managerStmt->execute();
	$managers = $managerStmt->fetchAll();
	
	$result = [];
	
	// 월의 각 날짜에 대한 데이터 조회
	for ($day = 1; $day <= $daysInMonth; $day++) {
		$date = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
		$dayOfWeek = date('w', strtotime($date)); // 0(일)~6(토)
		$dayNames = ['일', '월', '화', '수', '목', '금', '토'];
		
		$formattedDate = date('Y. m. d.', strtotime($date));
		$dayName = $dayNames[$dayOfWeek];
		
		$dailyData = [
			'date' => $formattedDate,
			'day' => $dayName,
			'managers' => [],
			'total' => ['inflow' => 0, 'contract' => 0]
		];
		
		// 각 사무장별 데이터 조회
		foreach ($managers as $manager) {
			// 해당 일자, 해당 사무장의 유입 건수
			$inflowQuery = "SELECT COUNT(*) as count FROM inflow WHERE DATE(datetime) = ? AND manager = ?";
			$inflowStmt = $pdo->prepare($inflowQuery);
			$inflowStmt->execute([$date, $manager['employee_no']]);
			$inflowCount = $inflowStmt->fetch()['count'];
			
			// 해당 일자, 해당 사무장의 계약 건수
			$contractQuery = "SELECT COUNT(*) as count FROM consult_manager WHERE DATE(datetime) = ? AND consultant = ? AND contract = 1";
			$contractStmt = $pdo->prepare($contractQuery);
			$contractStmt->execute([$date, $manager['employee_no']]);
			$contractCount = $contractStmt->fetch()['count'];
			
			$managerData = [
				'inflow' => (int)$inflowCount,
				'contract' => (int)$contractCount
			];
			
			$dailyData['managers'][] = $managerData;
			$dailyData['total']['inflow'] += $managerData['inflow'];
			$dailyData['total']['contract'] += $managerData['contract'];
		}
		
		$result[] = $dailyData;
	}
	
	echo json_encode([
		'success' => true,
		'data' => $result
	]);
	
} catch (PDOException $e) {
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류: ' . $e->getMessage()
	]);
}
?>