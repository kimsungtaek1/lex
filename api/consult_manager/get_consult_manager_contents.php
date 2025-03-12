<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {

    $sql = "SELECT 
        c.*,
        e.name as manager_name
        FROM consult_manager_content c
        LEFT JOIN employee e ON c.manager_id = e.employee_no
        WHERE c.consult_no = ?
        ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['consult_no']]);
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
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {

    $sql = "SELECT 
        c.*,
        e.name as manager_name
        FROM consult_manager_content c
        LEFT JOIN employee e ON c.manager_id = e.employee_no
        WHERE c.consult_no = ?
        ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['consult_no']]);
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
>>>>>>> 719d7c8 (Delete all files)
}