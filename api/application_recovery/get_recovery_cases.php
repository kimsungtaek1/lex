<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    $sql = "SELECT 
        c.*,
        ar.*,
        e.name as paper_name
        FROM case_management c
        LEFT JOIN application_recovery ar ON c.case_no = ar.case_no
        LEFT JOIN employee e ON c.paper = e.employee_no
        WHERE c.category IN ('개인회생급여', '개인회생영업')
        ORDER BY c.case_no DESC";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('쿼리 실행 실패: ' . print_r($stmt->errorInfo(), true));
    }

    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $cases], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}