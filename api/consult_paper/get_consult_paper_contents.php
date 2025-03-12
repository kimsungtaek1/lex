<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['paper_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $sql = "SELECT 
        c.*,
        e.name as manager_name
        FROM consult_paper_content c
        LEFT JOIN employee e ON c.manager_id = e.employee_no
        WHERE c.paper_no = ?
        ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['paper_no']]);
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $contents
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}