<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
		SELECT employee_no, name, department 
		FROM employee 
		WHERE status = '재직' 
		ORDER BY name ASC
	");
	
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $managers
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}