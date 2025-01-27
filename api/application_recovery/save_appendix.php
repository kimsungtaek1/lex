<?php
include '../../config.php';

    $data = [
        'case_no' => (int)$_POST['case_no'],
        'creditor_count' => (int)$_POST['creditor_count'],
        'mortgage_no' => isset($_POST['mortgage_no']) && $_POST['mortgage_no'] !== '' ? (int)$_POST['mortgage_no'] : null,
        'property_detail' => $_POST['property_detail'],
        'expected_value' => isset($_POST['expected_value']) ? (float)$_POST['expected_value'] : null,
        'evaluation_rate' => isset($_POST['evaluation_rate']) ? (float)$_POST['evaluation_rate'] : null,
        'max_claim' => isset($_POST['max_claim']) ? (float)$_POST['max_claim'] : null,
        'registration_date' => $_POST['registration_date'],
        'secured_expected_claim' => isset($_POST['secured_expected_claim']) ? (float)$_POST['secured_expected_claim'] : null,
        'unsecured_remaining_claim' => isset($_POST['unsecured_remaining_claim']) ? (float)$_POST['unsecured_remaining_claim'] : null,
        'rehabilitation_secured_claim' => isset($_POST['rehabilitation_secured_claim']) ? (float)$_POST['rehabilitation_secured_claim'] : null
    ];

try {
    $pdo->beginTransaction();
    
    // 기존 데이터 확인 (채권당 1개만 존재하도록)
    $checkSql = "SELECT COUNT(*) FROM application_recovery_mortgage 
                 WHERE case_no = ? AND creditor_count = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$data['case_no'], $data['creditor_count']]);
    $exists = $checkStmt->fetchColumn();

        if ($exists) {
            // 업데이트
            $sql = "UPDATE application_recovery_mortgage SET
                    mortgage_no = :mortgage_no,
                    property_detail = :property_detail,
                    expected_value = :expected_value,
                    evaluation_rate = :evaluation_rate,
                    max_claim = :max_claim,
                    registration_date = :registration_date,
                    secured_expected_claim = :secured_expected_claim,
                    unsecured_remaining_claim = :unsecured_remaining_claim,
                    rehabilitation_secured_claim = :rehabilitation_secured_claim
                    WHERE case_no = :case_no 
                      AND creditor_count = :creditor_count";
        } else {
            // 삽입
            $sql = "INSERT INTO application_recovery_mortgage (
                    case_no, creditor_count, mortgage_no, property_detail,
                    expected_value, evaluation_rate, max_claim, registration_date,
                    secured_expected_claim, unsecured_remaining_claim, rehabilitation_secured_claim
                    ) VALUES (
                    :case_no, :creditor_count, :mortgage_no, :property_detail,
                    :expected_value, :evaluation_rate, :max_claim, :registration_date,
                    :secured_expected_claim, :unsecured_remaining_claim, :rehabilitation_secured_claim)";
        }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    
    $pdo->commit();
    
    // 디버깅용 데이터 출력
    error_log(print_r($data, true));
    error_log($sql);
    
    echo json_encode([
        'status' => 'success',
        'message' => '데이터가 성공적으로 저장되었습니다',
        'data' => $data,
        'sql' => $sql,
        'case_no' => $data['case_no'],
        'creditor_count' => $data['creditor_count']
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'sql' => $sql,
        'data' => $data
    ]);
}
?>
