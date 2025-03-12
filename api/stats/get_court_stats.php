<<<<<<< HEAD
<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT * FROM statistics_court 
        ORDER BY id ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '데이터 조회 중 오류가 발생했습니다.'
    ]);
=======
<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT * FROM statistics_court 
        ORDER BY id ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '데이터 조회 중 오류가 발생했습니다.'
    ]);
>>>>>>> 719d7c8 (Delete all files)
}