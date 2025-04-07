<?php
include_once '../../config.php';

header('Content-Type: application/json');

try {
    // 파라미터 받기
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');

    // 해당 월의 마지막 날 계산
    $daysInMonth = date('t', strtotime("$year-$month-01"));

    // 사무장 목록 조회
    $managerQuery = "SELECT employee_no, name FROM employee WHERE position = '사무장' AND status = '재직' ORDER BY employee_no";
    $managerStmt = $pdo->prepare($managerQuery);
    $managerStmt->execute();
    $managers = $managerStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    // 각 날짜별 데이터 수집
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $dayOfWeek = date('w', strtotime($date));
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
            // 유입 건수 조회
            $inflowQuery = "SELECT COUNT(*) as count FROM inflow WHERE DATE(datetime) = :date AND manager = :manager";
            $inflowStmt = $pdo->prepare($inflowQuery);
            $inflowStmt->bindParam(':date', $date);
            $inflowStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
            $inflowStmt->execute();
            $inflowCount = $inflowStmt->fetch(PDO::FETCH_COLUMN);
            
            // 계약 건수 조회
            $contractQuery = "SELECT COUNT(*) as count FROM consult_manager WHERE DATE(datetime) = :date AND consultant = :manager AND contract = 1";
            $contractStmt = $pdo->prepare($contractQuery);
            $contractStmt->bindParam(':date', $date);
            $contractStmt->bindParam(':manager', $manager['employee_no'], PDO::PARAM_INT);
            $contractStmt->execute();
            $contractCount = $contractStmt->fetch(PDO::FETCH_COLUMN);
            
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