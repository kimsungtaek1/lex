<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php';
require_once 'base_asset_api.php';

// DELETE 요청일 경우 본문을 파싱
$deleteData = [];
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
}

$assetMapping = [
    'cash'                    => 'application_bankruptcy_asset_cash',
    'deposit'                 => 'application_bankruptcy_asset_deposits',
    'insurance'               => 'application_bankruptcy_asset_insurance',
    'vehicle'                 => 'application_bankruptcy_asset_vehicles',
    'rent_deposit'            => 'application_bankruptcy_asset_rent_deposits',
    'real_estate'             => 'application_bankruptcy_asset_real_estate',
    'loan_receivables'        => 'application_bankruptcy_asset_loan_receivables',
    'sales_receivables'       => 'application_bankruptcy_asset_sales_receivables',
    'severance_pay'           => 'application_bankruptcy_asset_severance',
    'other_assets'            => 'application_bankruptcy_asset_other',
    
    // 새로 추가할 asset_type 매핑
    'disposed_assets'         => 'application_bankruptcy_asset_disposed',
    'received_deposit'        => 'application_bankruptcy_asset_received_deposit',
    'divorce_property'        => 'application_bankruptcy_asset_divorce',
    'inherited_property'      => 'application_bankruptcy_asset_inherited'
];

$asset_type = $_GET['asset_type'] ?? $_POST['asset_type'] ?? $deleteData['asset_type'] ?? null;
if (!$asset_type || !isset($assetMapping[$asset_type])) {
    echo json_encode(['success' => false, 'message' => '유효한 asset_type이 필요합니다.']);
    exit;
}

$tableName = $assetMapping[$asset_type];
$api = new BaseAssetApi($pdo, $tableName, 'property_no');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $case_no = $_GET['case_no'] ?? null;
    $id = $_GET['property_no'] ?? null;
    if (!$case_no) {
        echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
        exit;
    }
    $result = $api->get($case_no, $id);
    echo json_encode($result);
} elseif ($method === 'POST') {
    $case_no = $_POST['case_no'] ?? null;
    $update_list = json_decode(file_get_contents("php://input"), true)['update_list'] ?? null;

    if (!$case_no) {
        echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
        exit;
    }

    if ($update_list) {
        // 🔹 `update_list`가 있으면, property_no를 업데이트하는 로직 실행
        try {
            $pdo->beginTransaction();

            foreach ($update_list as $data) {
                // 각 자산 타입에 맞는 필드로 UPDATE 문 구성
                switch ($asset_type) {
                    case 'cash':
                        $stmt = $pdo->prepare("
                            UPDATE $tableName 
                            SET property_no = :property_no,
                                property_detail = :property_detail,
                                liquidation_value = :liquidation_value,
                                is_seized = :is_seized
                            WHERE case_no = :case_no AND asset_no = :asset_no
                        ");
                        $stmt->execute([
                            ':property_no' => $data['property_no'],
                            ':property_detail' => $data['property_detail'] ?? '',
                            ':liquidation_value' => $data['liquidation_value'] ?? 0,
                            ':is_seized' => $data['is_seized'] ?? 'N',
                            ':case_no' => $case_no,
                            ':asset_no' => $data['asset_no']
                        ]);
                        break;
                    
                    case 'deposit':
                        $stmt = $pdo->prepare("
                            UPDATE $tableName 
                            SET property_no = :property_no,
                                bank_name = :bank_name,
                                account_number = :account_number,
                                deposit_amount = :deposit_amount,
                                deduction_amount = :deduction_amount,
                                is_seized = :is_seized
                            WHERE case_no = :case_no AND asset_no = :asset_no
                        ");
                        $stmt->execute([
                            ':property_no' => $data['property_no'],
                            ':bank_name' => $data['bank_name'] ?? '',
                            ':account_number' => $data['account_number'] ?? '',
                            ':deposit_amount' => $data['deposit_amount'] ?? 0,
                            ':deduction_amount' => $data['deduction_amount'] ?? 0,
                            ':is_seized' => $data['is_seized'] ?? 'N',
                            ':case_no' => $case_no,
                            ':asset_no' => $data['asset_no']
                        ]);
                        break;
                    
                    // 다른 자산 타입에 대한 케이스 추가...
                    
                    default:
                        // 기본 처리 - 더 간단한 방식으로 진행
                        $fields = [];
                        $params = [':case_no' => $case_no, ':property_no' => $data['property_no']];
                        
                        if (isset($data['asset_no'])) {
                            $params[':asset_no'] = $data['asset_no'];
                            $whereClause = "case_no = :case_no AND asset_no = :asset_no";
                        } else {
                            $whereClause = "case_no = :case_no AND property_no = :old_property_no";
                            $params[':old_property_no'] = $data['old_property_no'] ?? $data['property_no'];
                        }
                        
                        $fields[] = "property_no = :property_no";
                        
                        foreach ($data as $key => $value) {
                            if ($key !== 'property_no' && $key !== 'case_no' && $key !== 'asset_no' && $key !== 'old_property_no') {
                                $fields[] = "$key = :$key";
                                $params[":$key"] = $value;
                            }
                        }
                        
                        $sql = "UPDATE $tableName SET " . implode(', ', $fields) . " WHERE $whereClause";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        break;
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'property_no 재정렬 완료']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        // 🔹 `update_list`가 없으면 기존 데이터 저장 로직 실행
        $result = $api->save($_POST);
        echo json_encode($result);
    }
} elseif ($method === 'DELETE') {
    $case_no = $deleteData['case_no'] ?? null;
    $id = $deleteData['property_no'] ?? null;
    if (!$case_no || !$id) {
        echo json_encode(['success' => false, 'message' => '사건 번호와 property_no가 필요합니다.']);
        exit;
    }

    // 🔹 기존 데이터 삭제
    $result = $api->delete($case_no, $id);

    if ($result['success']) {
        // 🔹 삭제 후 property_no 재정렬
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT * FROM $tableName 
                WHERE case_no = :case_no
                ORDER BY property_no ASC
            ");
            $stmt->execute([':case_no' => $case_no]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($assets as $index => $asset) {
                $newPropertyNo = $index + 1;
                $stmt = $pdo->prepare("
                    UPDATE $tableName 
                    SET property_no = :new_property_no
                    WHERE case_no = :case_no
                    AND property_no = :old_property_no
                ");
                $stmt->execute([
                    ':new_property_no' => $newPropertyNo,
                    ':case_no' => $case_no,
                    ':old_property_no' => $asset['property_no']
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => '삭제 후 property_no 재정렬 완료']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode($result);
    }
} else {
    echo json_encode(['success' => false, 'message' => '지원되지 않는 메소드입니다.']);
}
?>