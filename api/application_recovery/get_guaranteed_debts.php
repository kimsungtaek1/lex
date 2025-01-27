<?php
// get_guaranteed_debts.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_GET['case_no'] ?? 0;
$creditor_count = $_GET['creditor_count'] ?? 0;

if (!$case_no || !$creditor_count) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM application_recovery_creditor_guaranteed_debts
        WHERE case_no = ? AND creditor_count = ?
        ORDER BY debt_no ASC
    ");
    
    $stmt->execute([$case_no, $creditor_count]);
    $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 금액 포맷팅
    foreach ($debts as &$debt) {
        $debt['guarantee_amount'] = number_format($debt['guarantee_amount']);
        $debt['guarantee_date'] = date('Y-m-d', strtotime($debt['guarantee_date']));
    }

    echo json_encode([
        'success' => true,
        'data' => $debts
    ]);

} catch (Exception $e) {
    error_log("보증인채무 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

// save_guaranteed_debt.php
<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$case_no = $_POST['case_no'] ?? 0;
$creditor_count = $_POST['creditor_count'] ?? 0;
$debt_no = $_POST['debt_no'] ?? 0;
$data = [
    'guarantor_name' => $_POST['guarantor_name'] ?? '',
    'guarantor_address' => $_POST['guarantor_address'] ?? '',
    'guarantee_amount' => str_replace(',', '', $_POST['guarantee_amount'] ?? 0),
    'guarantee_date' => $_POST['guarantee_date'] ?? null
];

if (!$case_no || !$creditor_count || !$data['guarantor_name']) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($debt_no) {
        // 수정
        $stmt = $pdo->prepare("
            UPDATE application_recovery_creditor_guaranteed_debts 
            SET 
                guarantor_name = ?,
                guarantor_address = ?,
                guarantee_amount = ?,
                guarantee_date = ?
            WHERE debt_no = ? AND case_no = ? AND creditor_count = ?
        ");
        $params = array_values($data);
        $params[] = $debt_no;
        $params[] = $case_no;
        $params[] = $creditor_count;
        $stmt->execute($params);
    } else {
        // 신규 등록
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_creditor_guaranteed_debts 
            (case_no, creditor_count, guarantor_name, guarantor_address, guarantee_amount, guarantee_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $params = [$case_no, $creditor_count];
        $params = array_merge($params, array_values($data));
        $stmt->execute($params);
        $debt_no = $pdo->lastInsertId();
    }

    $pdo->commit();

    // 저장된 데이터 조회
    $stmt = $pdo->prepare("
        SELECT * 
        FROM application_recovery_creditor_guaranteed_debts 
        WHERE debt_no = ?
    ");
    $stmt->execute([$debt_no]);
    $savedData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => '저장되었습니다.',
        'debt_no' => $debt_no,
        'data' => $savedData
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("보증인채무 저장 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

// delete_guaranteed_debt.php
<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$debt_no = $_POST['debt_no'] ?? 0;

if (!$debt_no) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM application_recovery_creditor_guaranteed_debts 
        WHERE debt_no = ?
    ");
    $stmt->execute([$debt_no]);

    echo json_encode([
        'success' => true,
        'message' => '삭제되었습니다.'
    ]);

} catch (Exception $e) {
    error_log("보증인채무 삭제 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '삭제 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}