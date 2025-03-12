<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT * FROM employee_department
    ");
    
    $departments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '부서 정보를 가져오는데 실패했습니다.'
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT * FROM employee_department
    ");
    
    $departments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '부서 정보를 가져오는데 실패했습니다.'
    ]);
>>>>>>> 719d7c8 (Delete all files)
}