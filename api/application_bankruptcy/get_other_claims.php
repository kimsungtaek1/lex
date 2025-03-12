<?php
// get_other_claims.php
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
        FROM application_recovery_creditor_other_claims
        WHERE case_no = ? AND creditor_count = ?
        ORDER BY claim_no ASC
    ");
    
    $stmt->execute([$case_no, $creditor_count]);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $claims
    ]);

} catch (Exception $e) {
    error_log("기타미확정채권 조회 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

// save_other_claim.php
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
$claim_type = $_POST['claim_type'] ?? '';
$amount = $_POST['amount'] ?? 0;
$description = $_POST['description'] ?? '';
$claim_no = $_POST['claim_no'] ?? 0;

if (!$case_no || !$creditor_count || !$claim_type) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($claim_no) {
        // 수정
        $stmt = $pdo->prepare("
            UPDATE application_recovery_creditor_other_claims 
            SET claim_type = ?, amount = ?, description = ?
            WHERE claim_no = ? AND case_no = ? AND creditor_count = ?
        ");
        $stmt->execute([$claim_type, $amount, $description, $claim_no, $case_no, $creditor_count]);
    } else {
        // 신규 등록
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_creditor_other_claims 
            (case_no, creditor_count, claim_type, amount, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$case_no, $creditor_count, $claim_type, $amount, $description]);
        $claim_no = $pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => '저장되었습니다.',
        'claim_no' => $claim_no
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("기타미확정채권 저장 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

// delete_other_claim.php
<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$claim_no = $_POST['claim_no'] ?? 0;

if (!$claim_no) {
    echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM application_recovery_creditor_other_claims 
        WHERE claim_no = ?
    ");
    $stmt->execute([$claim_no]);

    echo json_encode([
        'success' => true,
        'message' => '삭제되었습니다.'
    ]);

} catch (Exception $e) {
    error_log("기타미확정채권 삭제 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '삭제 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}