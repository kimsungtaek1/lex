<?php
include_once '../../config.php';

header('Content-Type: application/json');

try {
	// 연도와 월 파라미터 받기
	$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
	$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
	
	// 선택된 월의 일수 계산
	$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
	
	// 사무장 목록 조회
	$managerQuery = "SELECT employee_no, name FROM employee WHERE position = '사무장' AND status = '재직' ORDER BY employee_no";
	$managerStmt = $pdo->prepare($managerQuery);
	$managerStmt->execute();
	$managers = $managerStmt->fetchAll();
	
	$result = [];
	
	// 선택된 월의 각 날짜에 대한 데이터 조회
	for ($day = 1; $day <= $daysInMonth; $day++) {
		$date = sprintf("%04d-%02d-%02d", $year, $month, $day);
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