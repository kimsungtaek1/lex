<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_GET['case_no'] ?? 0;

if (!$case_no) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            asset_type,
            SUM(amount) as total_amount,
            SUM(liquidation_value) as total_liquidation
        FROM application_recovery_assets
        WHERE case_no = ?
        GROUP BY asset_type
    ");
    
    $stmt->execute([$case_no]);
    $totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [
        'total_amount' => 0,
        'total_liquidation' => 0,
        'by_type' => []
    ];

    foreach ($totals as $total) {
        $result['total_amount'] += $total['total_amount'];
        $result['total_liquidation'] += $total['total_liquidation'];
        $result['by_type'][$total['asset_type']] = [
            'amount' => $total['total_amount'],
            'liquidation' => $total['total_liquidation']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    writeLog("청산가치 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '청산가치를 계산하는 중 오류가 발생했습니다.'
    ]);
}