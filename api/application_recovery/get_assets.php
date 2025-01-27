<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_GET['case_no'] ?? 0;
$asset_type = $_GET['asset_type'] ?? '';

if (!$case_no) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
    exit;
}

try {
    $where = "WHERE a.case_no = ?";
    $params = [$case_no];
    
    if ($asset_type) {
        $where .= " AND a.asset_type = ?";
        $params[] = $asset_type;
    }

    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            GROUP_CONCAT(
                CONCAT(d.detail_type, ':', d.detail_key, '=', d.detail_value)
                SEPARATOR '|'
            ) as details
        FROM application_recovery_assets a
        LEFT JOIN application_recovery_asset_details d ON a.asset_no = d.asset_no
        {$where}
        GROUP BY a.asset_no
        ORDER BY a.asset_no ASC
    ");
    
    $stmt->execute($params);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 부가정보 파싱
    foreach ($assets as &$asset) {
        $details = [];
        if ($asset['details']) {
            $detailsArray = explode('|', $asset['details']);
            foreach ($detailsArray as $detail) {
                list($type, $keyValue) = explode(':', $detail);
                list($key, $value) = explode('=', $keyValue);
                $details[] = [
                    'type' => $type,
                    'key' => $key,
                    'value' => $value
                ];
            }
        }
        $asset['details'] = $details;
        unset($asset['details_concat']);
    }

    echo json_encode([
        'success' => true,
        'data' => $assets
    ]);

} catch (Exception $e) {
    writeLog("재산 목록 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '재산 목록을 불러오는 중 오류가 발생했습니다.'
    ]);
}