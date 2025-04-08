<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// 접근 권한 확인
session_start();
if (!isset($_SESSION['employee_no'])) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

// 데이터베이스 연결 설정
require_once($_SERVER['DOCUMENT_ROOT'].'/adm/config.php');

// 파라미터 확인
$case_no = isset($_POST['case_no']) ? $_POST['case_no'] : '';
if (empty($case_no)) {
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// 먼저 기존 calculation_id 찾기
	$stmt = $pdo->prepare("
		SELECT id 
		FROM application_recovery_salary_calculation 
		WHERE case_no = :case_no
	");
	$stmt->execute(['case_no' => $case_no]);
	$calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 관련 행 데이터 먼저 삭제
	foreach ($calculations as $calculation) {
		$stmt = $pdo->prepare("
			DELETE FROM application_recovery_salary_calculation_rows 
			WHERE calculation_id = :calculation_id
		");
		$stmt->execute(['calculation_id' => $calculation['id']]);
	}
	
	// 기본 계산 데이터 삭제
	$stmt = $pdo->prepare("
		DELETE FROM application_recovery_salary_calculation 
		WHERE case_no = :case_no
	");
	$stmt->execute(['case_no' => $case_no]);
	
	$pdo->commit();
	
	header('Content-Type: application/json');
	echo json_encode(['success' => true, 'message' => '데이터가 성공적으로 삭제되었습니다.']);
	
} catch (PDOException $e) {
	$pdo->rollBack();
	error_log('월평균소득계산기 데이터 삭제 오류: ' . $e->getMessage());
	
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.', 'error' => $e->getMessage()]);
}
?>