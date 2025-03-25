<?php
include '../../config.php';

$case_no = $_GET['case_no'];
$mortgage_no = $_GET['mortgage_no'] ?? null;

try {
    $sql = "SELECT 
                m.property_detail,
                m.expected_value,
                m.evaluation_rate,
                m.max_claim,
                m.registration_date,
                m.secured_expected_claim,
                m.unsecured_remaining_claim,
                m.rehabilitation_secured_claim,
                m.mortgage_no,
                c.financial_institution AS creditor_name
            FROM application_recovery_creditor_appendix m
            LEFT JOIN application_recovery_creditor c
              ON m.case_no = c.case_no
              AND m.mortgage_no = c.creditor_count
            WHERE m.case_no = ? 
              AND (m.mortgage_no = ? OR ? IS NULL)
            ORDER BY m.mortgage_no";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$case_no, $mortgage_no, $mortgage_no]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No data found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
