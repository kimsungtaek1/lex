<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfAssets($pdf, $pdo, $case_no) {
	// A4 용지에 맞게 여백 설정
	$pdf->SetMargins(15, 15, 15);
	$pdf->SetAutoPageBreak(true, 15);
	
	// 새 페이지 추가
	$pdf->AddPage();
	
	// 문서 제목
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '재 산 목 록', 0, 1, 'C');
	$pdf->Ln(5);
	
	// 헤더 및 테이블 설정
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->SetFillColor(240, 240, 240);
	
	// 열 너비 설정
	$col1_width = 40; // 명칭
	$col2_width = 30; // 금액 또는 시가
	$col3_width = 20; // 압류유무
	$col4_width = 85; // 비고
	
	// 테이블 헤더
	$pdf->Cell($col1_width, 8, '명 칭', 1, 0, 'C', true);
	$pdf->Cell($col2_width, 8, '금액 또는 시가', 1, 0, 'C', true);
	$pdf->Cell($col3_width, 8, '압류등 유무', 1, 0, 'C', true);
	$pdf->Cell($col4_width, 8, '비 고', 1, 1, 'C', true);
	
	$pdf->SetFont('cid0kr', '', 9);
	
	try {
		// 현금
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total, 
				   GROUP_CONCAT(property_detail SEPARATOR ', ') as details,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$cash_total = $cash['total'] ?? 0;
		$cash_details = $cash['details'] ?? '';
		$cash_seized = $cash['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, 8, '현금', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($cash_total).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, $cash_seized, 1, 0, 'C');
		$pdf->Cell($col4_width, 8, $cash_details, 1, 1, 'L');
		
		// 예금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_deposits
			WHERE case_no = ?
			ORDER BY bank_name
		");
		$stmt->execute([$case_no]);
		$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 예금 총액 계산
		$deposit_total = 0;
		$deposit_deduction_total = 0;
		foreach ($deposits as $deposit) {
			$deposit_total += $deposit['deposit_amount'] ?? 0;
			$deposit_deduction_total += $deposit['deduction_amount'] ?? 0;
		}
		
		// 청산가치 계산 (예치금액 - 공제금액)
		$deposit_liquidation = max(0, $deposit_total - $deposit_deduction_total);
		
		// 각 예금 데이터별로 개별 테이블 생성
		if (count($deposits) > 0) {
			foreach ($deposits as $index => $deposit) {
				$pdf->Cell($col1_width, 24, '예금', 1, 0, 'L');
				$pdf->Cell($col2_width, 24, number_format($deposit['deposit_amount'] ?? 0).'원', 1, 0, 'R');
				$pdf->Cell($col3_width, 24, $deposit['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 예금 비고 내용 구성
				$notes = '';
				$notes .= "금융기관명 ".$deposit['bank_name']."\n";
				$notes .= "계좌번호 ".$deposit['account_number']."\n";
				$notes .= "잔고 ".number_format($deposit['deposit_amount'] ?? 0)."원";
				
				$pdf->MultiCell($col4_width, 24, $notes, 1, 'L', false, 1);
			}
		} else {
			$pdf->Cell($col1_width, 8, '예금', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, '0원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 보험 데이터 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_insurance
			WHERE case_no = ?
			ORDER BY company_name
		");
		$stmt->execute([$case_no]);
		$insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 보험 총액 및 보장성보험 총액 계산
		$insurance_total = 0;
		$coverage_insurance_total = 0;
		foreach ($insurances as $insurance) {
			$insurance_total += $insurance['refund_amount'] ?? 0;
			if ($insurance['is_coverage'] === 'Y') {
				$coverage_insurance_total += $insurance['refund_amount'] ?? 0;
			}
		}
		
		// 보장성보험 공제금액 (1,500,000원)
		$coverage_deduction = 1500000;
		
		// 보험 청산가치 계산 (보장성보험 총액 - 공제금액) + 일반보험 총액
		$insurance_liquidation = max(0, $coverage_insurance_total - $coverage_deduction) + ($insurance_total - $coverage_insurance_total);
		
		// 각 보험 데이터별로 개별 테이블 생성
		if (count($insurances) > 0) {
			foreach ($insurances as $index => $insurance) {
				$pdf->Cell($col1_width, 24, '보험', 1, 0, 'L');
				$pdf->Cell($col2_width, 24, number_format($insurance['refund_amount'] ?? 0).'원', 1, 0, 'R');
				$pdf->Cell($col3_width, 24, $insurance['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 보험 비고 내용 구성
				$notes = '';
				$notes .= "보장성보험여부 ".($insurance['is_coverage'] === 'Y' ? '예' : '아니오')."\n";
				$notes .= "보험회사명 ".$insurance['company_name']."\n";
				$notes .= "증권번호 ".$insurance['securities_number']."\n";
				$notes .= "해약환급금 ".number_format($insurance['refund_amount'] ?? 0)."원";
				
				$pdf->MultiCell($col4_width, 24, $notes, 1, 'L', false, 1);
			}
		} else {
			$pdf->Cell($col1_width, 8, '보험', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, '0원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 자동차 데이터 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_vehicles
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 자동차 총액 계산
		$vehicle_total = 0;
		foreach ($vehicles as $vehicle) {
			$vehicle_total += $vehicle['liquidation_value'] ?? 0;
		}
		
		// 각 자동차 데이터별로 개별 테이블 행 생성
		if (count($vehicles) > 0) {
			foreach ($vehicles as $index => $vehicle) {
				$pdf->Cell($col1_width, 28, '자동차(오토바이 포함)', 1, 0, 'L');
				$pdf->Cell($col2_width, 28, number_format($vehicle['liquidation_value'] ?? 0).'원', 1, 0, 'R');
				$pdf->Cell($col3_width, 28, $vehicle['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 자동차 비고 내용 구성
				$notes = '';
				$notes .= "차종/연식: ".$vehicle['vehicle_info']."\n";
				$notes .= "채권(최고)액: ".number_format($vehicle['max_bond'] ?? 0)."원 ";
				$notes .= "    담보권종류 : ".$vehicle['security_type']."\n";
				$notes .= "환가예상액: ".number_format($vehicle['expected_value'] ?? 0)."원 ";
				$notes .= "    채무잔액: ".number_format($vehicle['financial_balance'] ?? 0)."원\n";
				$notes .= $vehicle['explanation'] ?? '';
				
				$pdf->MultiCell($col4_width, 28, $notes, 1, 'L', false, 1);
			}
		} else {
			$pdf->Cell($col1_width, 8, '자동차', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, '0원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 임차보증금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_rent_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$rent_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 임차보증금 총액 계산
		$rent_total = 0;
		foreach ($rent_deposits as $rent) {
			$rent_total += $rent['liquidation_value'] ?? 0;
		}
		
		// 각 임차보증금 데이터별로 개별 테이블 행 생성
		if (count($rent_deposits) > 0) {
			foreach ($rent_deposits as $index => $rent) {
				$pdf->Cell($col1_width, 30, "임차보증금\n(반환받을금액\n".number_format($rent['refund_deposit'] ?? 0)."원)", 1, 0, 'L');
				$pdf->Cell($col2_width, 30, number_format($rent['liquidation_value'] ?? 0).'원', 1, 0, 'R');
				$pdf->Cell($col3_width, 30, $rent['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 임차보증금 비고 내용 구성
				$notes = '';
				$notes .= "임차물건 ".$rent['rent_location']."\n";
				$notes .= "보증금 및월세 보증금 ".number_format($rent['contract_deposit'] ?? 0)."원 월세 ".number_format($rent['monthly_rent'] ?? 0)."원\n";
				$notes .= "차이 나는사유 ".$rent['difference_reason']."\n";
				$notes .= "압류할수 없는 보증금 ".number_format($rent['priority_deposit'] ?? 0)."원\n";
				$notes .= $rent['explanation'] ?? '';
				
				$pdf->MultiCell($col4_width, 30, $notes, 1, 'L', false, 1);
			}
		} else {
			$pdf->Cell($col1_width, 8, '임차보증금', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, '0원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 부동산 데이터 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_real_estate
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estates = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 부동산 총액 계산
		$real_estate_total = 0;
		foreach ($real_estates as $real_estate) {
			$real_estate_total += $real_estate['property_liquidation_value'] ?? 0;
		}
		
		// 각 부동산 데이터별로 개별 테이블 행 생성
		if (count($real_estates) > 0) {
			$pdf->Cell($col1_width, 32, "부동산(환가예상액에서\n피담보채권을\n뺀금액을\n금액란에적는다)", 1, 0, 'L');
			$pdf->Cell($col2_width, 32, number_format($real_estate_total).'원', 1, 0, 'R');
			$pdf->Cell($col3_width, 32, ($real_estates[0]['is_seized'] ?? 'N'), 1, 0, 'C');
			
			// 첫 번째 부동산 정보만 표시 (여러 개 있을 경우)
			$real_estate = $real_estates[0];
			
			// 부동산 비고 내용 구성
			$notes = '';
			$notes .= "소재지 ".$real_estate['property_location']."\n";
			$notes .= "부동산의종류 ";
			
			// 부동산 종류 체크박스 표시
			$type = $real_estate['property_type'] ?? '';
			$notes .= "토지(".($type == '토지' ? '✓' : ' ')."), ";
			$notes .= "건물(".($type == '건물' ? '✓' : ' ')."), ";
			$notes .= "토지+건물(".($type == '토지, 건물' ? '✓' : ' ')."), ";
			$notes .= "집합건물(".($type == '집합건물' ? '✓' : ' ').")\n";
			
			$notes .= "면적 ".number_format($real_estate['property_area'] ?? 0)."㎡\n";
			$notes .= "권리의 종류 ".$real_estate['property_right_type']."\n";
			$notes .= "환가예상액 ".number_format($real_estate['property_expected_value'] ?? 0)."원\n";
			
			if (!empty($real_estate['property_security_type'])) {
				$notes .= "담보권 설정된경우 그 종류및 담보액 ".$real_estate['property_security_type'];
			}
			
			$pdf->MultiCell($col4_width, 32, $notes, 1, 'L', false, 1);
		} else {
			$pdf->Cell($col1_width, 8, '부동산', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, '0원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 사업용 설비 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(total) as total_sum,
				   GROUP_CONCAT(item_name SEPARATOR ', ') as items,
				   GROUP_CONCAT(quantity SEPARATOR ', ') as quantities,
				   GROUP_CONCAT(purchase_date SEPARATOR ', ') as dates,
				   GROUP_CONCAT(used_price SEPARATOR ', ') as prices
			FROM application_recovery_asset_business
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$business_total = $business['total_sum'] ?? 0;
		$business_items = $business['items'] ?? '';
		$business_quantities = $business['quantities'] ?? '';
		$business_dates = $business['dates'] ?? '';
		$business_prices = $business['prices'] ?? '';
		
		$pdf->Cell($col1_width, 20, "사업용 설비.재고품.\n비품 등", 1, 0, 'L');
		$pdf->Cell($col2_width, 20, number_format($business_total).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 20, '', 1, 0, 'C');
		
		// 사업용 설비 비고 내용
		$notes = '';
		if (!empty($business_items)) {
			$notes .= "품목,개수 ".$business_items;
			if (!empty($business_quantities)) {
				$notes .= " ".$business_quantities;
			}
			$notes .= "\n";
			$notes .= "구입시기 ".$business_dates."\n";
			$notes .= "평가액 ".number_format($business_prices)."원";
		} else {
			$notes = '해당 없음';
		}
		
		$pdf->MultiCell($col4_width, 20, $notes, 1, 'L', false, 1);
		
		// 대여금 채권 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(debtor_name SEPARATOR ', ') as debtors,
				   GROUP_CONCAT(has_evidence SEPARATOR ', ') as evidences
			FROM application_recovery_asset_loan_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$loan = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$loan_total = $loan['total'] ?? 0;
		$loan_debtors = $loan['debtors'] ?? '';
		$loan_evidences = $loan['evidences'] ?? '';
		
		$pdf->Cell($col1_width, 8, '대여금 채권', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($loan_total).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		
		// 대여금 채권 비고 내용
		$notes = '';
		if (!empty($loan_debtors)) {
			$debtors_array = explode(', ', $loan_debtors);
			$evidences_array = explode(', ', $loan_evidences);
			
			$debtor_text = "상대방 채무자: ".$debtors_array[0];
			$evidence_checked = (isset($evidences_array[0]) && $evidences_array[0] === 'Y') ? '■' : '□';
			$notes .= $debtor_text." ".$evidence_checked." 진술서 별첨";
		} else {
			$notes = '해당 없음';
		}
		
		$pdf->Cell($col4_width, 8, $notes, 1, 1, 'L');
		
		// 매출금 채권 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(debtor_name SEPARATOR ', ') as debtors,
				   GROUP_CONCAT(has_evidence SEPARATOR ', ') as evidences
			FROM application_recovery_asset_sales_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$sales = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$sales_total = $sales['total'] ?? 0;
		$sales_debtors = $sales['debtors'] ?? '';
		$sales_evidences = $sales['evidences'] ?? '';
		
		$pdf->Cell($col1_width, 8, '매출금 채권', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($sales_total).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		
		// 매출금 채권 비고 내용
		$notes = '';
		if (!empty($sales_debtors)) {
			$debtors_array = explode(', ', $sales_debtors);
			$evidences_array = explode(', ', $sales_evidences);
			
			$debtor_text = "상대방 채무자: ".$debtors_array[0];
			$evidence_checked = (isset($evidences_array[0]) && $evidences_array[0] === 'Y') ? '■' : '□';
			$notes .= $debtor_text." ".$evidence_checked." 진술서 별첨";
		} else {
			$notes = '해당 없음';
		}
		
		$pdf->Cell($col4_width, 8, $notes, 1, 1, 'L');
		
		// 예상 퇴직금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(expected_severance) as expected_total,
				   SUM(deduction_amount) as deduction_total,
				   SUM(liquidation_value) as liquidation_total,
				   GROUP_CONCAT(workplace SEPARATOR ', ') as workplaces,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_severance
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$severance = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$severance_expected = $severance['expected_total'] ?? 0;
		$severance_deduction = $severance['deduction_total'] ?? 0;
		$severance_liquidation = $severance['liquidation_total'] ?? 0;
		$severance_workplaces = $severance['workplaces'] ?? '';
		$severance_seized = $severance['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, 8, '예상 퇴직금', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($severance_liquidation).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, $severance_seized, 1, 0, 'C');
		
		// 예상 퇴직금 비고 내용
		$notes = "근무처: ".$severance_workplaces." (압류할 수 없는 퇴직금".number_format($severance_deduction)."원 제외)";
		
		$pdf->Cell($col4_width, 8, $notes, 1, 1, 'L');
		
		// (가)압류 적립금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_attached_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$attached_deposit = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($attached_deposit) {
			$pdf->Cell($col1_width, 8, '(가)압류 적립금', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, number_format($attached_deposit['liquidation_value'] ?? 0).'원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '내용: '.$attached_deposit['seizure_content'], 1, 1, 'L');
		}
		
		// 공탁금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_court_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$court_deposit = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($court_deposit) {
			$pdf->Cell($col1_width, 8, '공탁금', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, number_format($court_deposit['liquidation_value'] ?? 0).'원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '내용: '.$court_deposit['seizure_content'], 1, 1, 'L');
		}
		
		// 기타 자산 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(asset_content SEPARATOR ', ') as contents,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_other
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$other = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$other_total = $other['total'] ?? 0;
		$other_contents = $other['contents'] ?? '';
		$other_seized = $other['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, 8, '기타(  )', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($other_total).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, $other_seized, 1, 0, 'C');
		$pdf->Cell($col4_width, 8, $other_contents, 1, 1, 'L');
		
		// 합계 계산
		$total_assets = 
			$cash_total + 
			$deposit_total + 
			$insurance_total + 
			$vehicle_total + 
			$rent_total + 
			$real_estate_total + 
			$business_total + 
			$loan_total + 
			$sales_total + 
			$severance_liquidation + 
			($attached_deposit['liquidation_value'] ?? 0) + 
			($court_deposit['liquidation_value'] ?? 0) + 
			$other_total;
		
		// 합계 행
		$pdf->SetFont('cid0kr', 'B', 9);
		$pdf->Cell($col1_width, 8, '합계', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($total_assets).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		$pdf->Cell($col4_width, 8, '', 1, 1, 'L');
		
		$pdf->SetFont('cid0kr', '', 9);
		
		// 면제재산 - 주거용 임차보증금 조회
		$stmt = $pdo->prepare("
			SELECT SUM(exemption_amount) as total1,
				   GROUP_CONCAT(lease_location SEPARATOR ', ') as locations
			FROM application_recovery_asset_exemption1
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption1 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$exemption1_total = $exemption1['total1'] ?? 0;
		$exemption1_locations = $exemption1['locations'] ?? '';
		
		// 면제재산 - 생계비 조회
		$stmt = $pdo->prepare("
			SELECT SUM(exemption_amount) as total2,
				   GROUP_CONCAT(special_property_content SEPARATOR ', ') as contents
			FROM application_recovery_asset_exemption2
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption2 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$exemption2_total = $exemption2['total2'] ?? 0;
		$exemption2_contents = $exemption2['contents'] ?? '';
		
		// 면제재산 표시 - 주거용 임차보증금
		// 면제재산 표시 - 주거용 임차보증금
		if ($exemption1_total > 0) {
			$pdf->Cell($col1_width, 8, '면제재산 결정신청 금액', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, number_format($exemption1_total).'원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '1. 주거용 임차보증금 반환청구권', 1, 1, 'L');
		}
		
		// 면제재산 표시 - 생계비
		if ($exemption2_total > 0) {
			$pdf->Cell($col1_width, 8, '면제재산 결정신청 금액', 1, 0, 'L');
			$pdf->Cell($col2_width, 8, number_format($exemption2_total).'원', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '2. 6개월간 생계비에 사용할 특정재산', 1, 1, 'L');
		}
		
		// 청산가치 계산 (합계에서 면제재산 제외)
		$liquidation_value = $total_assets - ($exemption1_total + $exemption2_total);
		
		// 청산가치 행
		$pdf->SetFont('cid0kr', 'B', 9);
		$pdf->Cell($col1_width, 8, '청산가치', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, number_format($liquidation_value).'원', 1, 0, 'R');
		$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		$pdf->Cell($col4_width, 8, '', 1, 1, 'L');
		
		// 예금 및 보험에 대한 청산가치 설명 추가
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Ln(2);
		$pdf->MultiCell(0, 5, '*예금(청산가치) : '.number_format($deposit_liquidation).'원', 0, 'L');
		$pdf->MultiCell(0, 5, '=[예치금액 합계: '.number_format($deposit_total).' - 공제금액: '.number_format($deposit_deduction_total).']', 0, 'L');
		$pdf->MultiCell(0, 5, '*보험(청산가치): '.number_format($insurance_liquidation).'원', 0, 'L');
		$pdf->MultiCell(0, 5, '=[{보장성: '.number_format($coverage_insurance_total).' - 공제금액: '.number_format($coverage_deduction).'} + '.number_format($insurance_total - $coverage_insurance_total).']', 0, 'L');
		
		// 면제재산 결정신청서 생성 (다음 페이지)
		if ($exemption1_total > 0 || $exemption2_total > 0) {
			$pdf->AddPage();
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '면제재산 결정신청서', 0, 1, 'C');
			$pdf->SetFont('cid0kr', '', 10);
			
			// 사건 정보 조회
			$stmt = $pdo->prepare("
				SELECT ar.*, cm.name, cm.case_number 
				FROM application_recovery ar
				JOIN case_management cm ON ar.case_no = cm.case_no
				WHERE ar.case_no = ?
			");
			$stmt->execute([$case_no]);
			$case_info = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// 사건 기본 정보
			$pdf->Cell(30, 8, '사 건', 0, 0, 'R');
			$pdf->Cell(160, 8, (isset($case_info['case_year']) ? $case_info['case_year'].' ' : '').(isset($case_info['case_number']) ? $case_info['case_number'] : '').' 개인회생', 0, 1, 'L');
			
			$pdf->Cell(30, 8, '신 청 인 (채 무 자)', 0, 0, 'R');
			$pdf->Cell(160, 8, $case_info['name'] ?? '', 0, 1, 'L');
			
			$pdf->Ln(5);
			
			// 면제재산 신청 안내문
			$pdf->MultiCell(0, 6, '신청인은 채무자 회생 및 파산에 관한 법률 제580조 제3항,제1항 제1호,제383조 제2항에 따라', 0, 'L');
			$pdf->MultiCell(0, 6, '채무자 소유의 별지 목록 기재 재산을 면제재산으로 정한다는 결정을 구합니다.', 0, 'L');
			$pdf->Ln(2);
			$pdf->MultiCell(0, 6, '(※아래 해당되는 부분에 ∨표를 하고, 면제재산결정 신청을 하는 재산목록 및 소명자료를', 0, 'L');
			$pdf->MultiCell(0, 6, '첨부하시기 바랍니다.)', 0, 'L');
			$pdf->Ln(3);
			
			// 1. 주거용건물 임차보증금 반환청구권 면제재산신청
			$checkbox1 = $exemption1_total > 0 ? '■' : '□';
			$pdf->Cell(5, 8, $checkbox1, 0, 0, 'C');
			$pdf->Cell(5, 8, '1.', 0, 0, 'L');
			$pdf->MultiCell(0, 8, '주거용건물 임차보증금반환청구권에 대한 면제재산결정 신청', 0, 'L');
			$pdf->Cell(10, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '(법 제580조 제3항, 제1항 제1호, 제383조 제2항 제1호)', 0, 'L');
			
			// 첨부 서류 안내
			$pdf->Cell(10, 8, '', 0, 0);
			$pdf->MultiCell(0, 8, '※ 첨부서류', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '가. 별지 면제재산목록 (채권자수 + 3부)', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '나. 소명자료 : □ 임대차계약서 1부', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '                  □ 주민등록등본 1통', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '                  □ 기타 [                  ] 통', 0, 'L');
			
			$pdf->Ln(3);
			
			// 2. 6개월간의 생계비 면제재산신청
			$checkbox2 = $exemption2_total > 0 ? '■' : '□';
			$pdf->Cell(5, 8, $checkbox2, 0, 0, 'C');
			$pdf->Cell(5, 8, '2.', 0, 0, 'L');
			$pdf->MultiCell(0, 8, '6개월간의 생계비에 사용할 특정재산에 대한 면제재산결정 신청', 0, 'L');
			$pdf->Cell(10, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '(법 제580조 제3항, 제1항 제1호, 제383조 제2항 제2호)', 0, 'L');
			
			// 첨부 서류 안내
			$pdf->Cell(10, 8, '', 0, 0);
			$pdf->MultiCell(0, 8, '※ 첨부서류', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '가. 별지 면제재산목록 (채권자수 + 3부)', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '나. 소명자료 : □ [                  ] 1통', 0, 'L');
			$pdf->Cell(15, 6, '', 0, 0);
			$pdf->MultiCell(0, 6, '                  □ 기타 [                  ] 통', 0, 'L');
			
			$pdf->Ln(10);
			
			// 현재 날짜
			$current_date = date('Y. m. d.');
			$pdf->Cell(0, 8, $current_date, 0, 1, 'C');
			$pdf->Ln(10);
			
			// 신청인 서명란
			$pdf->Cell(0, 8, '신 청 인(채 무 자) '.$case_info['name'].' (인)', 0, 1, 'C');
			$pdf->Ln(5);
			
			// 제출처
			$pdf->Cell(0, 8, ($case_info['court_name'] ?? '서울회생법원').' 귀중', 0, 1, 'C');
			
			// 면제재산 세부 목록 (다음 페이지)
			if ($exemption1_total > 0) {
				// 주거용 임차보증금 면제재산 상세 조회
				$stmt = $pdo->prepare("
					SELECT * FROM application_recovery_asset_exemption1
					WHERE case_no = ? LIMIT 1
				");
				$stmt->execute([$case_no]);
				$exempt_detail = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if ($exempt_detail) {
					$pdf->AddPage();
					$pdf->SetFont('cid0kr', 'B', 12);
					$pdf->Cell(0, 10, '목 록', 0, 1, 'C');
					$pdf->SetFont('cid0kr', '', 10);
					
					// 주거용 임차보증금 면제재산 상세 정보 테이블
					$pdf->Ln(5);
					
					// 면제재산 금액
					$pdf->Cell(40, 8, '면제재산 금액', 1, 0, 'L');
					$pdf->Cell(150, 8, '금 '.number_format($exemption1_total).' 원', 1, 1, 'L');
					
					// 주택임대차계약 내용
					$pdf->Cell(40, 8, '주택임대차계약의 내용', 1, 0, 'L');
					
					// 임대차계약 상세 내용 문자열 구성
					$contract_details = '';
					$contract_details .= "①임대차계약일자 (".date('Y.m.d', strtotime($exempt_detail['contract_date'] ?? '')).")\n";
					$contract_details .= "②임대차기간 (".date('Y.m.d', strtotime($exempt_detail['lease_start_date'] ?? ''))." 부터 ".date('Y.m.d', strtotime($exempt_detail['lease_end_date'] ?? ''))." 까지 )\n";
					$contract_details .= "③임차목적물의 소재지(".$exempt_detail['lease_location'].")\n";
					$contract_details .= "④임차보증금 (".number_format($exempt_detail['lease_deposit'] ?? 0)." 원 )\n";
					$contract_details .= "⑤임료의 액수 및 연체기간(월 ".number_format($exempt_detail['rent_fee'] ?? 0)." 원,".$exempt_detail['overdue_months']." 개월간 연체 )\n";
					$contract_details .= "⑥임대인의 성명 (".$exempt_detail['lessor_name'].")\n";
					$contract_details .= "⑦주민등록일자 (".date('Y-m-d', strtotime($exempt_detail['registration_date'] ?? '')).")\n";
					
					// 확정일자 정보 추가
					if ($exempt_detail['has_fixed_date'] === 'Y') {
						$contract_details .= "⑧확정일자 (".date('Y.m.d', strtotime($exempt_detail['fixed_date'] ?? ''))." 확정일자받음 )";
					} else {
						$contract_details .= "⑧확정일자 (확정일자 없음)";
					}
					
					// 소명자료 체크박스
					$lease_contract_checked = $exempt_detail['lease_contract'] === 'Y' ? '■' : '□';
					$resident_registration_checked = $exempt_detail['resident_registration'] === 'Y' ? '■' : '□';
					$other_evidence_checked = $exempt_detail['other_evidence'] === 'Y' ? '■' : '□';
					
					$contract_details .= "\n".$lease_contract_checked." 임대차계약서 1부/ ".$resident_registration_checked." 주민등록등본 1통/ ".$other_evidence_checked." 기타 [".$exempt_detail['other_evidence_detail']."] 통";
					
					$pdf->MultiCell(150, 60, $contract_details, 1, 'L');
				}
			}
			
			if ($exemption2_total > 0) {
				// 생계비 특정재산 면제재산 목록 조회
				$stmt = $pdo->prepare("
					SELECT * FROM application_recovery_asset_exemption2
					WHERE case_no = ?
				");
				$stmt->execute([$case_no]);
				$exempt_special_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				if (!empty($exempt_special_list)) {
					if ($exemption1_total <= 0) {
						// 임차보증금 면제재산이 없는 경우에만 새 페이지에 시작
						$pdf->AddPage();
						$pdf->SetFont('cid0kr', 'B', 12);
						$pdf->Cell(0, 10, '목 록', 0, 1, 'C');
						$pdf->SetFont('cid0kr', '', 10);
					} else {
						// 앞서 임차보증금 면제재산이 있었던 경우 간격 추가
						$pdf->Ln(10);
					}
					
					// 생계비 특정재산 면제재산 신청 체크박스
					$pdf->Cell(5, 8, '□', 0, 0, 'C');
					$pdf->MultiCell(0, 8, '6개월간의 생계비에 사용할 특정재산에 대한 면제재산결정 신청(법 제383조제2항 제2호)', 0, 'L');
					
					// 생계비 특정재산 테이블 헤더
					$pdf->Ln(5);
					$pdf->Cell(15, 8, '순번', 1, 0, 'C');
					$pdf->Cell(60, 8, '특정재산의내용', 1, 0, 'C');
					$pdf->Cell(40, 8, '소재지', 1, 0, 'C');
					$pdf->Cell(30, 8, '추정시가', 1, 0, 'C');
					$pdf->Cell(45, 8, '면제재산결정의 사유', 1, 1, 'C');
					
					// 생계비 특정재산 데이터 행
					foreach ($exempt_special_list as $index => $exempt_special) {
						$pdf->Cell(15, 8, ($index + 1), 1, 0, 'C');
						$pdf->Cell(60, 8, $exempt_special['special_property_content'], 1, 0, 'L');
						$pdf->Cell(40, 8, '', 1, 0, 'C'); // 소재지는 데이터 없음
						$pdf->Cell(30, 8, number_format($exempt_special['exemption_amount']).'원', 1, 0, 'R');
						$pdf->Cell(45, 8, '', 1, 1, 'L'); // 사유는 데이터 없음
					}
					
					// 소명자료 안내
					$evidence1 = !empty($exempt_special_list[0]['evidence1']) ? $exempt_special_list[0]['evidence1'] : '';
					$evidence2 = !empty($exempt_special_list[0]['evidence2']) ? $exempt_special_list[0]['evidence2'] : '';
					$evidence3 = !empty($exempt_special_list[0]['evidence3']) ? $exempt_special_list[0]['evidence3'] : '';
					
					$pdf->Ln(5);
					$pdf->MultiCell(0, 8, "※ 소명자료: □ (".$evidence1.")보증서 1통/ □ 사진 1장/ □ 기타 [".$evidence2."] 통", 0, 'L');
				}
			}
		}
		
	} catch (Exception $e) {
		$pdf->MultiCell(0, 10, 
			"데이터 조회 중 오류가 발생했습니다:\n" . 
			$e->getMessage() . 
			"\n\n관리자에게 문의해 주시기 바랍니다.", 
			0, 
			'C'
		);
	}
}
?>