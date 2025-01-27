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
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM application_recovery_creditor_settings 
        WHERE case_no = ?
    ");
    
    $stmt->execute([$case_no]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // 명시적으로 principal_interest_sum을 정수로 변환
    if ($settings) {
        $settings['principal_interest_sum'] = (int)$settings['principal_interest_sum'];
    }

    echo json_encode([
        'success' => true,
        'data' => $settings ?: [
            'principal_interest_sum' => 0,
            'list_creation_date' => null,
            'claim_calculation_date' => null
        ]
    ]);

} catch (Exception $e) {
    error_log("채권자 설정 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}