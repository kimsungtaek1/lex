<?php
require_once '../../config.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

// 로그인 체크
if (!isset($_SESSION['employee_no'])) {
    echo json_encode([
        'success' => false, 
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// POST 데이터 체크
if (empty($_POST['name']) || empty($_POST['residentNumber'])) {
    echo json_encode([
        'success' => false,
        'message' => '필수 입력값이 누락되었습니다.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 기본 데이터 준비
    $name = $_POST['name'];
    $phone = $_POST['phone'] ?? null;
    $courtName = $_POST['court'] ?? null;
    $caseNumber = $_POST['caseNumber'] ?? null;
    $category = isset($_POST['incomeType']) && $_POST['incomeType'] === 'salary' ? '개인회생급여' : '개인회생영업';
    $employeeNo = $_SESSION['employee_no'];
    
    // case_management 테이블 처리
    if (empty($_POST['case_no'])) {
        // 신규 등록
        $stmt = $pdo->prepare("
            INSERT INTO case_management 
            (name, phone, category, court_name, case_number, paper, status)
            VALUES (?, ?, ?, ?, ?, ?, '접수')
        ");
        $stmt->execute([$name, $phone, $category, $courtName, $caseNumber, $employeeNo]);
        $caseNo = $pdo->lastInsertId();
    } else {
        // 기존 데이터 수정
        $caseNo = $_POST['case_no'];
        $stmt = $pdo->prepare("
            UPDATE case_management 
            SET name = ?,
                phone = ?,
                category = ?,
                court_name = ?,
                case_number = ?
            WHERE case_no = ? AND paper = ?
        ");
        $stmt->execute([$name, $phone, $category, $courtName, $caseNumber, $caseNo, $employeeNo]);
    }
    
	// application_recovery 테이블 처리
	$recoveryFields = [
		'name' => $name,
		'phone' => $phone,
		'resident_number' => $_POST['residentNumber'],
		'registered_address' => $_POST['registeredAddress'],
		'now_address' => $_POST['nowAddress'],
		'work_address' => $_POST['workAddress'] ?? null,
		'workplace' => $_POST['workplace'] ?? null,
		'position' => $_POST['position'] ?? null,
		'work_period' => $_POST['workPeriod'] ?? null,
		'other_income' => $_POST['otherIncome'] ? preg_replace('/[^0-9]/', '', $_POST['otherIncome']) : null,
		'other_income_name' => $_POST['otherIncomeName'] ?? null,
		'income_source' => $_POST['incomeSource'] ?? null,
		'debt_total' => $_POST['debt_total'] ? preg_replace('/[^0-9]/', '', $_POST['debt_total']) : null,
		'income_monthly' => $_POST['income_monthly'] ? preg_replace('/[^0-9]/', '', $_POST['income_monthly']) : null,
		'expense_monthly' => $_POST['expense_monthly'] ? preg_replace('/[^0-9]/', '', $_POST['expense_monthly']) : null,
		'repayment_monthly' => $_POST['repayment_monthly'] ? preg_replace('/[^0-9]/', '', $_POST['repayment_monthly']) : null,
		'assets_total' => $_POST['assets_total'] ? preg_replace('/[^0-9]/', '', $_POST['assets_total']) : null,
		'memo' => $_POST['remarks'] ?? null,
		'application_date' => !empty($_POST['applicationDate']) ? $_POST['applicationDate'] : null,
		'unspecified_date' => isset($_POST['unspecifiedDate']) ? $_POST['unspecifiedDate'] : 0,
		'repayment_start_date' => !empty($_POST['repaymentStartDate']) ? $_POST['repaymentStartDate'] : null,
		'court_name' => $courtName,
		'bank_name' => $_POST['bankName'] ?? null,
		'account_number' => $_POST['accountNumber'] ?? null,
		'is_company' => isset($_POST['incomeType']) && $_POST['incomeType'] === '1' ? 1 : 0
	];
    
    // 기존 데이터 확인
    $stmt = $pdo->prepare("SELECT recovery_no FROM application_recovery WHERE case_no = ?");
    $stmt->execute([$caseNo]);
    
    if ($stmt->fetch()) {
        // 기존 데이터 수정
        $sql = "UPDATE application_recovery SET ";
        $updates = [];
        $params = [];
        
        foreach ($recoveryFields as $field => $value) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
        
        $sql .= implode(", ", $updates);
        $sql .= " WHERE case_no = ?";
        $params[] = $caseNo;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        // 신규 데이터 등록
        $fields = array_keys($recoveryFields);
        $placeholders = array_fill(0, count($fields), "?");
        
        $sql = "INSERT INTO application_recovery ";
        $sql .= "(case_no, " . implode(", ", $fields) . ", assigned_employee) ";
        $sql .= "VALUES (?, " . implode(", ", $placeholders) . ", ?)";
        
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$caseNo], array_values($recoveryFields), [$employeeNo]);
        $stmt->execute($params);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'case_no' => $caseNo,
        'message' => '저장되었습니다.'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    writeLog("저장 오류 발생: " . $e->getMessage() . "\n스택 트레이스: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}