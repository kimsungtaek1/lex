<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php';
require_once 'BaseStatementApi.php';

// DELETE 요청일 경우 본문을 파싱
$deleteData = [];
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
}

$statementMapping = [
    'education' => ['table' => 'application_recovery_statement_education', 'id_field' => 'education_id'],
    'career' => ['table' => 'application_recovery_statement_career', 'id_field' => 'career_id'],
    'marriage' => ['table' => 'application_recovery_statement_marriage', 'id_field' => 'marriage_id'],
    'housing' => ['table' => 'application_recovery_statement_housing', 'id_field' => 'housing_id'],
    'lawsuit' => ['table' => 'application_recovery_statement_lawsuit', 'id_field' => 'lawsuit_id'],
    'bankruptcyReason' => ['table' => 'application_recovery_statement_bankruptcy_reason', 'id_field' => 'bankruptcy_reason_id'],
    'debtRelief' => ['table' => 'application_recovery_statement_debt_relief', 'id_field' => 'debt_relief_id']
];

$statement_type = $_GET['statement_type'] ?? $_POST['statement_type'] ?? $deleteData['statement_type'] ?? null;
if (!$statement_type || !isset($statementMapping[$statement_type])) {
    echo json_encode(['success' => false, 'message' => '유효한 statement_type이 필요합니다.']);
    exit;
}

$tableInfo = $statementMapping[$statement_type];
$api = new BaseStatementApi($pdo, $tableInfo['table'], $tableInfo['id_field']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $case_no = $_GET['case_no'] ?? null;
    $id = $_GET[$tableInfo['id_field']] ?? null;
    if (!$case_no) {
        echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
        exit;
    }
    $result = $api->get($case_no, $id);
    echo json_encode($result);
} elseif ($method === 'POST') {
    $case_no = $_POST['case_no'] ?? null;
    $statement_type = $_POST['statement_type'] ?? null;
    
    if (!$case_no || !$statement_type) {
        echo json_encode(['success' => false, 'message' => '사건 번호와 statement_type이 필요합니다.']);
        exit;
    }
    
    // statement_type은 테이블을 선택하는 용도로만 사용하고 데이터에서는 제거
    unset($_POST['statement_type']);
    
    $result = $api->save($_POST);
    echo json_encode($result);
} elseif ($method === 'DELETE') {
    $case_no = $deleteData['case_no'] ?? null;
    $id = $deleteData[$tableInfo['id_field']] ?? null;
    if (!$case_no || !$id) {
        echo json_encode(['success' => false, 'message' => '사건 번호와 ID가 필요합니다.']);
        exit;
    }
    
    $result = $api->delete($case_no, $id);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => '지원되지 않는 메소드입니다.']);
}
?>