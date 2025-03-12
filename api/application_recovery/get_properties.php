<?php
include '../../config.php';

// case_no 파라미터 필수 체크
if (!isset($_GET['case_no']) || empty($_GET['case_no'])) {
    die(json_encode([
        'success' => false,
        'message' => 'case_no 파라미터가 필요합니다'
    ]));
}

try {
    // 목적물 정보 조회 쿼리
    $sql = "SELECT 
                id,
                address,
                detail,
                expected_value,
                evaluation_rate
            FROM application_recovery_properties
            WHERE case_no = ?
            ORDER BY id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['case_no']]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo json_encode([
            'success' => true,
            'properties' => $results
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'properties' => [],
            'message' => '등록된 목적물이 없습니다'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
