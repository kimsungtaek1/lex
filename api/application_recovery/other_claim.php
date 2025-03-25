<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}
include '../../config.php';

$case_no = $_GET['case_no'] ?? '';
$creditor_count = $_GET['creditor_count'] ?? '';

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM application_recovery_creditor_other_claims
        WHERE case_no = ? AND creditor_count = ?
        ORDER BY claim_no
    ");
    $stmt->execute([$case_no, $creditor_count]);
    $claims = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("기타미확정채권 조회 오류: " . $e->getMessage());
}
?>
<link rel="stylesheet" href="../../css/appendix.css">