<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT cp.*, e.name as manager_name 
            FROM consult_paper cp
            LEFT JOIN employee e ON cp.manager_id = e.employee_no
            ORDER BY cp.datetime DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $papers
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>