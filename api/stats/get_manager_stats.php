<?php
// /adm/api/stats/get_managers_stats.php
header('Content-Type: application/json');
require_once '../../config.php';

try {
	// 최근 12개월 기간 설정
	$startDate = date('Y-m-d', strtotime('-12 months'));
	$endDate = date('Y-m-d');
	
	// 사무장 정보 조회
	$query = "
		SELECT 
			e.employee_no, 
			e.name 
		FROM 
			employee e 
		JOIN 
			employee_position p ON e.position = p.position_name 
		WHERE 
			p.position_name = '사무장' 
			AND e.status = '재직'
		ORDER BY 
			e.name ASC
	";
	
	$stmt = $pdo->prepare($query);
	$stmt->execute();
	$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 각 사무장별 수임 건수 및 수임료 조회
	$managerStats = [];
	foreach ($managers as $manager) {
		$managerStats[$manager['employee_no']] = [
			'employee_no' => $manager['employee_no'],
			'name' => $manager['name'],
			'case_count' => 0,
			'avg_fee' => 0,
			'success_rate' => 0,
			'consult_count' => 0,
			'contract_count' => 0
		];
	}
	
	// 사무장별 수임 건수 및 수임료 조회
	$query = "
		SELECT 
			c.consultant AS employee_no,
			COUNT(c.case_no) AS case_count,
			AVG(c.application_fee) AS avg_fee
		FROM 
			case_management c
		WHERE 
			c.consultant IS NOT NULL
			AND c.datetime BETWEEN :start_date AND :end_date
		GROUP BY 
			c.consultant
	";
	
	$stmt = $pdo->prepare($query);
	$stmt->bindParam(':start_date', $startDate);
	$stmt->bindParam(':end_date', $endDate);
	$stmt->execute();
	$caseStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 사무장별 상담 및 계약 건수 조회
	$query = "
		SELECT 
			cm.consultant AS employee_no,
			COUNT(cm.consult_no) AS consult_count,
			SUM(cm.contract) AS contract_count
		FROM 
			consult_manager cm
		WHERE 
			cm.consultant IS NOT NULL
			AND cm.datetime BETWEEN :start_date AND :end_date
		GROUP BY 
			cm.consultant
	";
	
	$stmt = $pdo->prepare($query);
	$stmt->bindParam(':start_date', $startDate);
	$stmt->bindParam(':end_date', $endDate);
	$stmt->execute();
	$contractStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 데이터 병합
	foreach ($caseStats as $case) {
		if (isset($managerStats[$case['employee_no']])) {
			$managerStats[$case['employee_no']]['case_count'] = (int)$case['case_count'];
			$managerStats[$case['employee_no']]['avg_fee'] = (int)$case['avg_fee'];
		}
	}
	
	foreach ($contractStats as $contract) {
		if (isset($managerStats[$contract['employee_no']])) {
			$successRate = $contract['consult_count'] > 0 ? 
				round(($contract['contract_count'] / $contract['consult_count']) * 100, 1) : 0;
			
			$managerStats[$contract['employee_no']]['success_rate'] = $successRate;
			$managerStats[$contract['employee_no']]['consult_count'] = (int)$contract['consult_count'];
			$managerStats[$contract['employee_no']]['contract_count'] = (int)$contract['contract_count'];
		}
	}
	
	// 순위 계산
	$managerData = array_values($managerStats);
	
	// 수임건수 순위
	$caseCountArray = array_column($managerData, 'case_count');
	array_multisort($caseCountArray, SORT_DESC, $managerData);
	foreach ($managerData as $index => $data) {
		$managerStats[$data['employee_no']]['case_count_rank'] = $index + 1;
	}
	
	// 평균 수임료 순위
	usort($managerData, function($a, $b) {
		return $b['avg_fee'] - $a['avg_fee'];
	});
	foreach ($managerData as $index => $data) {
		$managerStats[$data['employee_no']]['avg_fee_rank'] = $index + 1;
	}
	
	// 응답 반환
	echo json_encode([
		'success' => true,
		'data' => array_values($managerStats),
		'start_date' => $startDate,
		'end_date' => $endDate
	]);
	
} catch (PDOException $e) {
	writeLog('Manager Stats API Error: ' . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()
	]);
}
?>