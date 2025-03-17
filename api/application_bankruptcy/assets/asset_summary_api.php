<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

require_once '../../../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
	$case_no = $_GET['case_no'] ?? null;
	if (!$case_no) {
		echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
		exit;
	}
	
	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_summary WHERE case_no = :case_no");
	$stmt->execute(['case_no' => $case_no]);
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
	
	echo json_encode(['success' => true, 'data' => $data]);
} elseif ($method === 'POST') {
	$case_no = $_POST['case_no'] ?? null;
	if (!$case_no) {
		echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
		exit;
	}
	
	// 기존 데이터 확인
	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_summary WHERE case_no = :case_no");
	$stmt->execute(['case_no' => $case_no]);
	$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
	
	try {
		if ($existingData) {
			// UPDATE
			$sql = "UPDATE application_bankruptcy_asset_summary SET 
					cash_exists = :cash_exists,
					deposit_exists = :deposit_exists,
					insurance_exists = :insurance_exists,
					rent_deposit_exists = :rent_deposit_exists,
					loan_receivables_exists = :loan_receivables_exists,
					sales_receivables_exists = :sales_receivables_exists,
					severance_pay_exists = :severance_pay_exists,
					real_estate_exists = :real_estate_exists,
					vehicle_exists = :vehicle_exists,
					other_assets_exists = :other_assets_exists,
					disposed_assets_exists = :disposed_assets_exists,
					received_deposit_exists = :received_deposit_exists,
					divorce_property_exists = :divorce_property_exists,
					inherited_property_exists = :inherited_property_exists,
					updated_at = NOW()
					WHERE case_no = :case_no";
		} else {
			// INSERT
			$sql = "INSERT INTO application_bankruptcy_asset_summary (
					case_no, 
					cash_exists, 
					deposit_exists, 
					insurance_exists, 
					rent_deposit_exists, 
					loan_receivables_exists, 
					sales_receivables_exists, 
					severance_pay_exists, 
					real_estate_exists, 
					vehicle_exists, 
					other_assets_exists, 
					disposed_assets_exists, 
					received_deposit_exists, 
					divorce_property_exists, 
					inherited_property_exists, 
					created_at, 
					updated_at
				) VALUES (
					:case_no, 
					:cash_exists, 
					:deposit_exists, 
					:insurance_exists, 
					:rent_deposit_exists, 
					:loan_receivables_exists, 
					:sales_receivables_exists, 
					:severance_pay_exists, 
					:real_estate_exists, 
					:vehicle_exists, 
					:other_assets_exists, 
					:disposed_assets_exists, 
					:received_deposit_exists, 
					:divorce_property_exists, 
					:inherited_property_exists, 
					NOW(), 
					NOW()
				)";
		}
		
		$stmt = $pdo->prepare($sql);
		$params = [
			'case_no' => $case_no,
			'cash_exists' => $_POST['cash_exists'] ?? 'N',
			'deposit_exists' => $_POST['deposit_exists'] ?? 'N',
			'insurance_exists' => $_POST['insurance_exists'] ?? 'N',
			'rent_deposit_exists' => $_POST['rent_deposit_exists'] ?? 'N',
			'loan_receivables_exists' => $_POST['loan_receivables_exists'] ?? 'N',
			'sales_receivables_exists' => $_POST['sales_receivables_exists'] ?? 'N',
			'severance_pay_exists' => $_POST['severance_pay_exists'] ?? 'N',
			'real_estate_exists' => $_POST['real_estate_exists'] ?? 'N',
			'vehicle_exists' => $_POST['vehicle_exists'] ?? 'N',
			'other_assets_exists' => $_POST['other_assets_exists'] ?? 'N',
			'disposed_assets_exists' => $_POST['disposed_assets_exists'] ?? 'N',
			'received_deposit_exists' => $_POST['received_deposit_exists'] ?? 'N',
			'divorce_property_exists' => $_POST['divorce_property_exists'] ?? 'N',
			'inherited_property_exists' => $_POST['inherited_property_exists'] ?? 'N'
		];
		$stmt->execute($params);
		
		echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	}
} else {
	echo json_encode(['success' => false, 'message' => '지원되지 않는 메소드입니다.']);
}
?>