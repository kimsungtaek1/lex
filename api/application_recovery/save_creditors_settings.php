<?php 
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// 권한 체크
if (!isset($_SESSION['employee_no'])) {
    echo json_encode([
        'success' => false, 
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

// POST 데이터 확인
$case_no = $_POST['case_no'] ?? 0;
$settings = [
    'principal_interest_sum' => isset($_POST['principal_interest_sum']) ? (int)$_POST['principal_interest_sum'] : 0,
    'list_creation_date' => $_POST['list_creation_date'] ?? null,
    'claim_calculation_date' => $_POST['claim_calculation_date'] ?? null
];

if (!$case_no) {
    echo json_encode([
        'success' => false, 
        'message' => '필수 데이터가 누락되었습니다.'
    ]);
    exit;
}

try {
    // 기존 설정 확인
    $stmt = $pdo->prepare("
        SELECT setting_no 
        FROM application_recovery_creditor_settings 
        WHERE case_no = ?
    ");
    $stmt->execute([$case_no]);
    $exists = $stmt->fetch();

    if ($exists) {
        // 기존 설정 업데이트
        $stmt = $pdo->prepare("
            UPDATE application_recovery_creditor_settings 
            SET 
                principal_interest_sum = ?,
                list_creation_date = ?,
                claim_calculation_date = ?,
                updated_at = NOW()
            WHERE case_no = ?
        ");
        
        $result = $stmt->execute([
            $settings['principal_interest_sum'],
            $settings['list_creation_date'],
            $settings['claim_calculation_date'],
            $case_no
        ]);
    } else {
        // 새로운 설정 생성
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_creditor_settings 
            (case_no, principal_interest_sum, list_creation_date, claim_calculation_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $case_no,
            $settings['principal_interest_sum'],
            $settings['list_creation_date'],
            $settings['claim_calculation_date']
        ]);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '설정이 저장되었습니다.'
        ]);
    } else {
        throw new Exception('데이터베이스 저장 실패');
    }

} catch (Exception $e) {
    error_log("채권자 설정 저장 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
?>