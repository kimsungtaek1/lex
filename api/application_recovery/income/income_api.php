<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php';
require_once 'base_income_api.php';

// DELETE 요청일 경우 본문을 파싱
$deleteData = [];
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
}

// 테이블 매핑 설정
$tableName = 'application_recovery_income_expenditure';

// BaseIncomeApi 인스턴스 생성
$api = new BaseIncomeApi($pdo, $tableName);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $case_no = $_GET['case_no'] ?? null;
    $year = $_GET['year'] ?? date('Y');
    
    if (!$case_no) {
        echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
        exit;
    }
    
    $result = $api->get($case_no, $year);
    echo json_encode($result);

} elseif ($method === 'POST') {
    $case_no = $_POST['case_no'] ?? null;
    if (!$case_no) {
        echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
        exit;
    }
    
    // 데이터 유효성 검사
    $livingExpenseAmount = filter_var($_POST['living_expense_amount'] ?? 0, FILTER_VALIDATE_INT);
    $livingExpensePeriod = filter_var($_POST['living_expense_period'] ?? 36, FILTER_VALIDATE_INT);
    $otherExemptAmount = filter_var($_POST['other_exempt_amount'] ?? 0, FILTER_VALIDATE_INT);

    if ($livingExpenseAmount === false || $livingExpensePeriod === false || $otherExemptAmount === false) {
        echo json_encode(['success' => false, 'message' => '잘못된 금액이 입력되었습니다.']);
        exit;
    }

    $result = $api->save($_POST);
    echo json_encode($result);

} elseif ($method === 'DELETE') {
    $case_no = $deleteData['case_no'] ?? null;
    $year = $deleteData['year'] ?? null;
    
    if (!$case_no || !$year) {
        echo json_encode(['success' => false, 'message' => '사건 번호와 연도가 필요합니다.']);
        exit;
    }
    
    $result = $api->delete($case_no, $year);
    echo json_encode($result);

} else {
    echo json_encode(['success' => false, 'message' => '지원되지 않는 메소드입니다.']);
}
?>