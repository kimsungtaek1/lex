<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfAssets($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '재산 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	// 현금 및 예금
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '1. 현금 및 예금', 0, 1, 'L');
	
	try {
		// 현금
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_cash 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$cash_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 예금
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_deposits 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$deposit_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($cash_assets) && empty($deposit_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 현금 및 예금 정보가 없습니다.', 0, 1, 'L');
		} else {
			// 현금 출력
			if (!empty($cash_assets)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(0, 7, '현금', 0, 1, 'L');
				$pdf->Cell(100, 7, '내용', 1, 0, 'C');
				$pdf->Cell(30, 7, '가액', 1, 0, 'C');
				$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
				
				$pdf->SetFont('cid0kr', '', 10);
				foreach ($cash_assets as $asset) {
					$pdf->Cell(100, 7, $asset['property_detail'], 1, 0, 'L');
					$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
					$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
				}
				$pdf->Ln(5);
			}
			
			// 예금 출력
			if (!empty($deposit_assets)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(0, 7, '예금', 0, 1, 'L');
				$pdf->Cell(50, 7, '은행명', 1, 0, 'C');
				$pdf->Cell(60, 7, '계좌번호', 1, 0, 'C');
				$pdf->Cell(30, 7, '금액', 1, 0, 'C');
				$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
				
				$pdf->SetFont('cid0kr', '', 10);
				foreach ($deposit_assets as $asset) {
					$pdf->Cell(50, 7, $asset['bank_name'], 1, 0, 'L');
					$pdf->Cell(60, 7, $asset['account_number'], 1, 0, 'L');
					$pdf->Cell(30, 7, number_format($asset['deposit_amount']), 1, 0, 'R');
					$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
				}
			}
		}
		
		// 보험
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '2. 보험', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_insurance 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$insurance_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($insurance_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 보험 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(40, 7, '보험사', 1, 0, 'C');
			$pdf->Cell(50, 7, '증권번호', 1, 0, 'C');
			$pdf->Cell(30, 7, '환급금액', 1, 0, 'C');
			$pdf->Cell(20, 7, '보장성여부', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($insurance_assets as $asset) {
				$pdf->Cell(40, 7, $asset['company_name'], 1, 0, 'L');
				$pdf->Cell(50, 7, $asset['securities_number'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['refund_amount']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_coverage'], 1, 0, 'C');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 자동차
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '3. 자동차', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_vehicles 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$vehicle_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($vehicle_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 자동차 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(90, 7, '차량정보', 1, 0, 'C');
			$pdf->Cell(30, 7, '시가', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($vehicle_assets as $asset) {
				$pdf->Cell(90, 7, $asset['vehicle_info'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['expected_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 부동산
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '4. 부동산', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_real_estate 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$real_estate_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($real_estate_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 부동산 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(30, 7, '권리종류', 1, 0, 'C');
			$pdf->Cell(30, 7, '부동산종류', 1, 0, 'C');
			$pdf->Cell(70, 7, '소재지', 1, 0, 'C');
			$pdf->Cell(30, 7, '평가액', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($real_estate_assets as $asset) {
				$pdf->Cell(30, 7, $asset['property_right_type'], 1, 0, 'L');
				$pdf->Cell(30, 7, $asset['property_type'], 1, 0, 'L');
				$pdf->Cell(70, 7, $asset['property_location'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['property_expected_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['property_liquidation_value']), 1, 1, 'R');
			}
		}
		
		// 임차보증금
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '5. 임차보증금', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_rent_deposits 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$rent_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($rent_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 임차보증금 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(70, 7, '임차지', 1, 0, 'C');
			$pdf->Cell(30, 7, '계약상보증금', 1, 0, 'C');
			$pdf->Cell(30, 7, '반환보증금', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($rent_assets as $asset) {
				$pdf->Cell(70, 7, $asset['rent_location'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['contract_deposit']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['refund_deposit']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 사업용 설비, 재고, 비품 등
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '6. 사업용 설비, 재고, 비품 등', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_business 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$business_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($business_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 사업용 설비 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(70, 7, '품목', 1, 0, 'C');
			$pdf->Cell(30, 7, '구입시기', 1, 0, 'C');
			$pdf->Cell(20, 7, '수량', 1, 0, 'C');
			$pdf->Cell(30, 7, '중고시세', 1, 0, 'C');
			$pdf->Cell(30, 7, '합계', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($business_assets as $asset) {
				$pdf->Cell(70, 7, $asset['item_name'], 1, 0, 'L');
				$pdf->Cell(30, 7, $asset['purchase_date'], 1, 0, 'C');
				$pdf->Cell(20, 7, $asset['quantity'], 1, 0, 'C');
				$pdf->Cell(30, 7, number_format($asset['used_price']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['total']), 1, 1, 'R');
			}
		}
		
		// 대여금 채권
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '7. 대여금 채권', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_loan_receivables 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$loan_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($loan_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 대여금 채권 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(70, 7, '채무자', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(30, 7, '소명자료', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($loan_assets as $asset) {
				$pdf->Cell(70, 7, $asset['debtor_name'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, $asset['has_evidence'], 1, 0, 'C');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 매출금 채권
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '8. 매출금 채권', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_sales_receivables 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$sales_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($sales_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 매출금 채권 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(70, 7, '채무자', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(30, 7, '소명자료', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($sales_assets as $asset) {
				$pdf->Cell(70, 7, $asset['debtor_name'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, $asset['has_evidence'], 1, 0, 'C');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 예상 퇴직금
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '9. 예상 퇴직금', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_severance 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$severance_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($severance_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 예상 퇴직금 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(70, 7, '근무지', 1, 0, 'C');
			$pdf->Cell(30, 7, '예상퇴직금', 1, 0, 'C');
			$pdf->Cell(30, 7, '공제금액', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($severance_assets as $asset) {
				$pdf->Cell(70, 7, $asset['workplace'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['expected_severance']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['deduction_amount']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 기타 자산
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '10. 기타 자산', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_other 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$other_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($other_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 기타 자산 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(130, 7, '재산 내용', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($other_assets as $asset) {
				$pdf->Cell(130, 7, $asset['asset_content'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
		// 면제재산 - 주거용 임차보증금
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '11. 면제재산 - 주거용 임차보증금', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_exemption1 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$exemption1_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($exemption1_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 면제재산(임차보증금) 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(90, 7, '임차 소재지', 1, 0, 'C');
			$pdf->Cell(30, 7, '임차보증금', 1, 0, 'C');
			$pdf->Cell(30, 7, '임료', 1, 0, 'C');
			$pdf->Cell(30, 7, '면제금액', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($exemption1_assets as $asset) {
				$pdf->Cell(90, 7, $asset['lease_location'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['lease_deposit']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['rent_fee']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['exemption_amount']), 1, 1, 'R');
			}
		}
		
		// 면제재산 - 6개월간 생계비
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '12. 면제재산 - 6개월간 생계비', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_exemption2 
			WHERE case_no = ?
			ORDER BY property_no ASC
		");
		$stmt->execute([$case_no]);
		$exemption2_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($exemption2_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 면제재산(생계비) 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(130, 7, '특정재산의 내용', 1, 0, 'C');
			$pdf->Cell(50, 7, '면제신청 금액', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($exemption2_assets as $asset) {
				$pdf->Cell(130, 7, $asset['special_property_content'], 1, 0, 'L');
				$pdf->Cell(50, 7, number_format($asset['exemption_amount']), 1, 1, 'R');
			}
		}
		
		// 총 재산 가치 계산
		$pdf->Ln(10);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '재산 총액 요약', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as cash_total
			FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash_total = $stmt->fetch(PDO::FETCH_ASSOC)['cash_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(deposit_amount), 0) as deposit_total
			FROM application_recovery_asset_deposits 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$deposit_total = $stmt->fetch(PDO::FETCH_ASSOC)['deposit_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(refund_amount), 0) as insurance_total
			FROM application_recovery_asset_insurance 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$insurance_total = $stmt->fetch(PDO::FETCH_ASSOC)['insurance_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as vehicle_total
			FROM application_recovery_asset_vehicles 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicle_total = $stmt->fetch(PDO::FETCH_ASSOC)['vehicle_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(property_liquidation_value), 0) as real_estate_total
			FROM application_recovery_asset_real_estate 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estate_total = $stmt->fetch(PDO::FETCH_ASSOC)['real_estate_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as rent_total
			FROM application_recovery_asset_rent_deposits 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$rent_total = $stmt->fetch(PDO::FETCH_ASSOC)['rent_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(total), 0) as business_total
			FROM application_recovery_asset_business 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business_total = $stmt->fetch(PDO::FETCH_ASSOC)['business_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as loan_total
			FROM application_recovery_asset_loan_receivables 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$loan_total = $stmt->fetch(PDO::FETCH_ASSOC)['loan_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as sales_total
			FROM application_recovery_asset_sales_receivables 
			WHERE case_no = ?
		");
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as sales_total
			FROM application_recovery_asset_sales_receivables 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$sales_total = $stmt->fetch(PDO::FETCH_ASSOC)['sales_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as severance_total
			FROM application_recovery_asset_severance 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$severance_total = $stmt->fetch(PDO::FETCH_ASSOC)['severance_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(liquidation_value), 0) as other_total
			FROM application_recovery_asset_other 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$other_total = $stmt->fetch(PDO::FETCH_ASSOC)['other_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(exemption_amount), 0) as exemption1_total
			FROM application_recovery_asset_exemption1 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption1_total = $stmt->fetch(PDO::FETCH_ASSOC)['exemption1_total'];
		
		$stmt = $pdo->prepare("
			SELECT 
				COALESCE(SUM(exemption_amount), 0) as exemption2_total
			FROM application_recovery_asset_exemption2 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption2_total = $stmt->fetch(PDO::FETCH_ASSOC)['exemption2_total'];
		
		// 총 자산 계산
		$total_assets = $cash_total + $deposit_total + $insurance_total + $vehicle_total + 
						$real_estate_total + $rent_total + $business_total + $loan_total + 
						$sales_total + $severance_total + $other_total;
		
		// 총 면제 재산 계산
		$total_exemptions = $exemption1_total + $exemption2_total;
		
		// 청산 가치 계산
		$total_liquidation_value = $total_assets - $total_exemptions;
		
		// 총액 테이블 출력
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(120, 7, '구분', 1, 0, 'C');
		$pdf->Cell(60, 7, '금액', 1, 1, 'C');
		
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(120, 7, '1. 현금', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($cash_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '2. 예금', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($deposit_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '3. 보험', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($insurance_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '4. 자동차', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($vehicle_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '5. 부동산', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($real_estate_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '6. 임차보증금', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($rent_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '7. 사업용 설비 등', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($business_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '8. 대여금채권', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($loan_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '9. 매출금채권', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($sales_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '10. 예상퇴직금', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($severance_total) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '11. 기타자산', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($other_total) . ' 원', 1, 1, 'R');
		
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(120, 7, '총 자산', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($total_assets) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '면제재산 합계', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($total_exemptions) . ' 원', 1, 1, 'R');
		
		$pdf->Cell(120, 7, '총 청산가치', 1, 0, 'L');
		$pdf->Cell(60, 7, number_format($total_liquidation_value) . ' 원', 1, 1, 'R');
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}
?>