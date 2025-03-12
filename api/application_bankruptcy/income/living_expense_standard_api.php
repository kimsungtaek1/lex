<?php
require_once '../../../config.php';

header('Content-Type: application/json');

try {
    // 연도 목록 요청
    if (isset($_GET['action']) && $_GET['action'] === 'get_years') {
        $yearQuery = "SELECT DISTINCT year FROM application_recovery_income_living_expense_standard ORDER BY year DESC";
        $yearStmt = $pdo->query($yearQuery);
        $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'years' => $years
        ]);
        exit;
    }

    // 특정 연도의 생계비 기준 데이터 요청
    $year = $_GET['year'] ?? date('Y');

    $query = "SELECT family_members, standard_amount 
              FROM application_recovery_income_living_expense_standard 
              WHERE year = :year 
              ORDER BY family_members";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['year' => $year]);
    $standards = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        'data' => $standards
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '데이터 조회 중 오류가 발생했습니다.'
    ]);
}
?>