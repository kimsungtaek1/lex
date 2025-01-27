<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// 권한 체크
if (!isset($_SESSION['employee_no'])) {
    writeLog("권한 없음: employee_no가 세션에 없음");
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_POST['case_no'] ?? 0;
$asset_type = $_POST['asset_type'] ?? '';
$asset_data = $_POST['asset_data'] ?? null;

if (!$case_no || !$asset_type || !$asset_data) {
    writeLog("필수 데이터 누락");
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 기존 데이터 확인
    $stmt = $pdo->prepare("
        SELECT asset_no 
        FROM application_recovery_assets 
        WHERE case_no = ? AND asset_no = ?
    ");
    $stmt->execute([$case_no, $asset_data['id'] ?? 0]);
    $exists = $stmt->fetch();

    if ($exists) {
        // 수정
        $stmt = $pdo->prepare("
            UPDATE application_recovery_assets SET
                description = ?,
                memo = ?,
                amount = ?,
                is_seized = ?,
                calculation_date = ?,
                location = ?,
                area = ?,
                is_spouse = ?,
                expected_value = ?,
                secured_debt = ?,
                deposit_debt = ?,
                liquidation_value = ?
            WHERE asset_no = ?
        ");
        
        $params = [
            $asset_data['description'] ?? '',
            $asset_data['memo'] ?? '',
            $asset_data['amount'] ?? 0,
            $asset_data['is_seized'] ?? 'N',
            $asset_data['calculation_date'] ?? null,
            $asset_data['location'] ?? '',
            $asset_data['area'] ?? null,
            $asset_data['is_spouse'] ?? 0,
            $asset_data['expected_value'] ?? 0,
            $asset_data['secured_debt'] ?? 0,
            $asset_data['deposit_debt'] ?? 0,
            $asset_data['liquidation_value'] ?? 0,
            $exists['asset_no']
        ];
        
    } else {
        // 신규 등록
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_assets (
                case_no,
                asset_type,
                description,
                memo,
                amount,
                is_seized,
                calculation_date,
                location,
                area,
                is_spouse,
                expected_value,
                secured_debt,
                deposit_debt,
                liquidation_value
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $params = [
            $case_no,
            $asset_type,
            $asset_data['description'] ?? '',
            $asset_data['memo'] ?? '',
            $asset_data['amount'] ?? 0,
            $asset_data['is_seized'] ?? 'N',
            $asset_data['calculation_date'] ?? null,
            $asset_data['location'] ?? '',
            $asset_data['area'] ?? null,
            $asset_data['is_spouse'] ?? 0,
            $asset_data['expected_value'] ?? 0,
            $asset_data['secured_debt'] ?? 0,
            $asset_data['deposit_debt'] ?? 0,
            $asset_data['liquidation_value'] ?? 0
        ];
    }

    $result = $stmt->execute($params);
    $asset_no = $exists ? $exists['asset_no'] : $pdo->lastInsertId();

    // 부가정보 저장
    if (!empty($asset_data['details'])) {
        // 기존 부가정보 삭제
        $stmt = $pdo->prepare("DELETE FROM application_recovery_asset_details WHERE asset_no = ?");
        $stmt->execute([$asset_no]);

        // 새로운 부가정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_asset_details 
            (asset_no, detail_type, detail_key, detail_value) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($asset_data['details'] as $detail) {
            $stmt->execute([
                $asset_no,
                $detail['type'],
                $detail['key'],
                $detail['value']
            ]);
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => '저장되었습니다.',
        'asset_no' => $asset_no
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    writeLog("재산 저장 오류: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류가 발생했습니다.'
    ]);
}