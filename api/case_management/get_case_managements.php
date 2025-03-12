<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
	$sql = "SELECT 
			cm.*,
			e1.name as consultant_name,
			e2.name as paper_name,
			cp.accept_date as consult_paper_accept_date,
			cp.start_date as consult_paper_start_date
		FROM case_management cm
		LEFT JOIN employee e1 ON cm.consultant = e1.employee_no
		LEFT JOIN employee e2 ON cm.paper = e2.employee_no
		LEFT JOIN consult_paper cp ON cm.paper_no = cp.paper_no
		ORDER BY cm.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    writeLog("Query results: " . print_r($cases, true));

    // 각 사건의 메모 내용 조회
	foreach($cases as &$case) {
		$sql = "
			SELECT 
				cmc.*
			FROM case_management_content cmc
			WHERE cmc.case_no = ?
			ORDER BY cmc.created_at DESC
		";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$case['case_no']]);
		$case['contents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
    unset($case);

    echo json_encode([
        'success' => true,
        'data' => $cases
    ]);

} catch(Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    writeLog('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
	$sql = "SELECT 
			cm.*,
			e1.name as consultant_name,
			e2.name as paper_name,
			cp.accept_date as consult_paper_accept_date,
			cp.start_date as consult_paper_start_date
		FROM case_management cm
		LEFT JOIN employee e1 ON cm.consultant = e1.employee_no
		LEFT JOIN employee e2 ON cm.paper = e2.employee_no
		LEFT JOIN consult_paper cp ON cm.paper_no = cp.paper_no
		ORDER BY cm.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    writeLog("Query results: " . print_r($cases, true));

    // 각 사건의 메모 내용 조회
	foreach($cases as &$case) {
		$sql = "
			SELECT 
				cmc.*
			FROM case_management_content cmc
			WHERE cmc.case_no = ?
			ORDER BY cmc.created_at DESC
		";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$case['case_no']]);
		$case['contents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
    unset($case);

    echo json_encode([
        'success' => true,
        'data' => $cases
    ]);

} catch(Exception $e) {
    writeLog('Error: ' . $e->getMessage());
    writeLog('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}