<?php
// /adm/api/stats/get_document_stats.php
header('Content-Type: application/json');
require_once '../../config.php';

try {
	// 서류담당자 통계 데이터 가져오기
	$query = "
		SELECT 
			e.employee_no,
			e.name,
			e.position,
			e.department,
			
			/* 담당사건 통계 */
			COUNT(CASE WHEN c.status = '접수' AND c.paper = e.employee_no THEN 1 END) AS pre_receipt,
			COUNT(CASE WHEN c.status = '신건접수' AND c.paper = e.employee_no THEN 1 END) AS new_receipt,
			COUNT(CASE WHEN c.status = '개시전' AND c.paper = e.employee_no THEN 1 END) AS pre_start,
			COUNT(CASE WHEN c.paper = e.employee_no THEN 1 END) AS case_total,
			
			/* 개시 및 면책 통계 */
			COUNT(CASE 
				WHEN (c.status IN ('개시', '인가') AND YEAR(c.start_date) = YEAR(CURRENT_DATE) AND MONTH(c.start_date) = MONTH(CURRENT_DATE))
				OR (c.status = '면책' AND YEAR(c.approval_date) = YEAR(CURRENT_DATE) AND MONTH(c.approval_date) = MONTH(CURRENT_DATE))
				AND c.paper = e.employee_no THEN 1 
			END) AS current_month,
			
			COUNT(CASE 
				WHEN (c.status IN ('개시', '인가') AND YEAR(c.start_date) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND MONTH(c.start_date) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)))
				OR (c.status = '면책' AND YEAR(c.approval_date) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND MONTH(c.approval_date) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)))
				AND c.paper = e.employee_no THEN 1 
			END) AS one_month_ago,
			
			COUNT(CASE 
				WHEN (c.status IN ('개시', '인가') AND YEAR(c.start_date) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)) AND MONTH(c.start_date) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)))
				OR (c.status = '면책' AND YEAR(c.approval_date) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)) AND MONTH(c.approval_date) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)))
				AND c.paper = e.employee_no THEN 1 
			END) AS two_months_ago
			
		FROM 
			employee e
		LEFT JOIN 
			case_management c ON c.paper = e.employee_no
		WHERE 
			e.status = '재직'
			AND e.department LIKE '%팀%'
		GROUP BY 
			e.employee_no, e.name, e.position, e.department
		ORDER BY 
			e.department ASC, e.position DESC, e.name ASC
	";
	
	$stmt = $pdo->prepare($query);
	$stmt->execute();
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 부서별로 데이터 그룹화
	$departments = [];
	foreach ($results as $row) {
		$department = $row['department'];
		if (!isset($departments[$department])) {
			$departments[$department] = [
				'name' => $department,
				'employees' => [],
				'totals' => [
					'pre_receipt' => 0,
					'new_receipt' => 0,
					'pre_start' => 0,
					'case_total' => 0,
					'current_month' => 0,
					'one_month_ago' => 0,
					'two_months_ago' => 0,
					'approval_total' => 0,
					'approval_avg' => 0,
					'employee_count' => 0
				]
			];
		}
		
		// 승인 합계 계산
		$approval_total = $row['current_month'] + $row['one_month_ago'] + $row['two_months_ago'];
		
		// 개별 직원 데이터 추가
		$departments[$department]['employees'][] = [
			'employee_no' => $row['employee_no'],
			'name' => $row['name'],
			'position' => $row['position'],
			'pre_receipt' => $row['pre_receipt'],
			'new_receipt' => $row['new_receipt'],
			'pre_start' => $row['pre_start'],
			'case_total' => $row['case_total'],
			'current_month' => $row['current_month'],
			'one_month_ago' => $row['one_month_ago'],
			'two_months_ago' => $row['two_months_ago'],
			'approval_total' => $approval_total,
			'approval_avg' => $approval_total > 0 ? round($approval_total / 3, 1) : 0
		];
		
		// 부서 합계에 추가
		$departments[$department]['totals']['pre_receipt'] += $row['pre_receipt'];
		$departments[$department]['totals']['new_receipt'] += $row['new_receipt'];
		$departments[$department]['totals']['pre_start'] += $row['pre_start'];
		$departments[$department]['totals']['case_total'] += $row['case_total'];
		$departments[$department]['totals']['current_month'] += $row['current_month'];
		$departments[$department]['totals']['one_month_ago'] += $row['one_month_ago'];
		$departments[$department]['totals']['two_months_ago'] += $row['two_months_ago'];
		$departments[$department]['totals']['approval_total'] += $approval_total;
		$departments[$department]['totals']['employee_count']++;
	}
	
	// 부서별 평균 계산
	foreach ($departments as &$dept) {
		if ($dept['totals']['employee_count'] > 0) {
			$dept['totals']['approval_avg'] = round($dept['totals']['approval_total'] / (3 * $dept['totals']['employee_count']), 1);
		}
	}
	
	echo json_encode([
		'success' => true,
		'data' => array_values($departments)
	]);
	
} catch (PDOException $e) {
	writeLog('Document Stats API Error: ' . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()
	]);
}
?>