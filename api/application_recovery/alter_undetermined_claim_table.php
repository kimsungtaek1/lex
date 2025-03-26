<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// 기존 컬럼 확인
	$stmt = $pdo->prepare("SHOW COLUMNS FROM application_recovery_additional_claims LIKE 'property_detail'");
	$stmt->execute();
	$property_detail_exists = $stmt->rowCount() > 0;
	
	// 새로운 컬럼들 추가
	if (!$property_detail_exists) {
		// 컬럼 추가
		$pdo->exec("ALTER TABLE application_recovery_additional_claims 
			ADD COLUMN property_detail VARCHAR(255) NULL AFTER claim_type,
			ADD COLUMN expected_value BIGINT(20) DEFAULT 0 AFTER property_detail,
			ADD COLUMN evaluation_rate VARCHAR(20) NULL AFTER expected_value,
			ADD COLUMN trust_property_details TEXT NULL AFTER evaluation_rate,
			ADD COLUMN priority_certificate_amount BIGINT(20) DEFAULT 0 AFTER trust_property_details,
			ADD COLUMN registration_date DATE NULL AFTER priority_certificate_amount,
			ADD COLUMN expected_payment BIGINT(20) DEFAULT 0 AFTER registration_date,
			ADD COLUMN unpaid_amount BIGINT(20) DEFAULT 0 AFTER expected_payment");
	}
	
	$pdo->commit();
	echo json_encode([
		'success' => true,
		'message' => '테이블 구조가 업데이트되었습니다.'
	]);
	
} catch (Exception $e) {
	$pdo->rollBack();
	error_log("테이블 구조 변경 오류: " . $e->getMessage());
	echo json_encode([
		'success' => false,
		'message' => '테이블 구조 변경 중 오류가 발생했습니다.',
		'error' => $e->getMessage()
	]);
}