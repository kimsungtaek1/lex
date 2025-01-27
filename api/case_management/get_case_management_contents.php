<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['case_no']) || empty($_GET['case_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $case_no = (int)$_GET['case_no'];

    $sql = "SELECT c.*
            FROM case_management_content c
            WHERE c.case_no = :case_no
            ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':case_no' => $case_no]);
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