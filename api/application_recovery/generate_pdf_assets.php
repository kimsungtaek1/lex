<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfAssets($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 8, '재 산 목 록', 0, 1, 'C');
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', '', 8);
	
	// 테이블 헤더 설정
	
	$pdf->SetFillColor(240, 240, 240);
	
	// 열 너비 설정
	$col1_width = 25; // 명칭
	$col2_width = 25; // 금액 또는 시가
	$col3_width = 15; // 압류유무
	$col4_width = 115; // 비고
	
	// 행 높이
	$row_height = 8;

	// 첫 번째 열 - 명칭
	$pdf->MultiCell($col1_width, $row_height, "명칭", 1, 'C', true, 0, '', '', true, 0, false, true, $row_height, 'M');

	// 두 번째 열 - 금액 또는 시가
	$pdf->MultiCell($col2_width, $row_height, "금액 또는 시가\n(단위: 원)", 1, 'C', true, 0, '', '', true, 0, false, true, $row_height, 'M');

	// 세 번째 열 - 압류 등 유무
	$pdf->MultiCell($col3_width, $row_height, "압류 등\n유무", 1, 'C', true, 0, '', '', true, 0, false, true, $row_height, 'M');

	// 네 번째 열 - 비고
	$pdf->MultiCell($col4_width, $row_height, "비고", 1, 'C', true, 1, '', '', true, 0, false, true, $row_height, 'M');
		
	try {
		// 현금
		$pdf->Cell($col1_width, $row_height, '현금', 1, 0, 'C');
		
		// 현금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total, 
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$cash_total = $cash['total'] ?? 0;
		$cash_seized = $cash['is_seized'] ?? 'N';
		
		$pdf->Cell($col2_width, $row_height, number_format($cash_total), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, $cash_seized, 1, 0, 'C');
		$pdf->Cell($col4_width, $row_height, '', 1, 1, 'L');
		
		// 예금 데이터 개별 조회
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
		foreach ($deposits as $deposit) {
			$deposit_total += $deposit['deposit_amount'];
		}

		// 각 예금 데이터별로 개별 테이블 생성
		if (count($deposits) > 0) {
			foreach ($deposits as $index => $deposit) {
				// 새 페이지 확인 - 현재 페이지에 충분한 공간이 없으면 새 페이지 추가
				if ($pdf->GetY() + 25 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->Cell($col1_width, 25, '예금 #'.($index+1), 1, 0, 'C');
				$pdf->Cell($col2_width, 25, number_format($deposit['deposit_amount']), 1, 0, 'R');
				$pdf->Cell($col3_width, 25, $deposit['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$first_col_width = 25;
				$second_col_width = $col4_width - $first_col_width;
				$cell_height = 25 / 3;
				
				// 첫 번째 행 - 금융기관명
				$pdf->Cell($first_col_width, $cell_height, '금융기관명', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $deposit['bank_name'] ?? '', 1, 1, 'L');
				
				// 두 번째 행 - 계좌번호
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($first_col_width, $cell_height, '계좌번호', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $deposit['account_number'] ?? '상세내역 별첨', 1, 1, 'L');
				
				// 세 번째 행 - 잔고
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($first_col_width, $cell_height, '잔고', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($deposit['deposit_amount']).'원', 1, 0, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 25);
			}
		} else {
			// 예금 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '예금', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 보험 데이터 개별 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_insurance
			WHERE case_no = ?
			ORDER BY company_name
		");
		$stmt->execute([$case_no]);
		$insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 보험 총액 계산
		$insurance_total = 0;
		foreach ($insurances as $insurance) {
			$insurance_total += $insurance['refund_amount'];
		}

		// 각 보험 데이터별로 개별 테이블 생성
		if (count($insurances) > 0) {
			foreach ($insurances as $index => $insurance) {
				// 새 페이지 확인 - 현재 페이지에 충분한 공간이 없으면 새 페이지 추가
				if ($pdf->GetY() + 32 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->Cell($col1_width, 32, '보험 #'.($index+1), 1, 0, 'C');
				$pdf->Cell($col2_width, 32, number_format($insurance['refund_amount']), 1, 0, 'R');
				$pdf->Cell($col3_width, 32, $insurance['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$first_col_width = 25;
				$second_col_width = $col4_width - $first_col_width;
				$cell_height = 32 / 4;
				
				// 보장성 보험여부
				$pdf->Cell($first_col_width, $cell_height, '보장성보험여부', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $insurance['is_coverage'] ?? '', 1, 1, 'L');

				// 보험회사명
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($first_col_width, $cell_height, '보험회사명', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $insurance['company_name'] ?? '', 1, 1, 'L');

				// 증권번호
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($first_col_width, $cell_height, '증권번호', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $insurance['securities_number'] ?? '상세내역 별첨', 1, 1, 'L');

				// 해약환급금
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($first_col_width, $cell_height, '해약환급금', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($insurance['refund_amount']).'원', 1, 0, 'L');
								
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 32);
			}
		} else {
			// 보험 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '보험', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 자동차
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_vehicles
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$vehicle_total = 0;
		foreach ($vehicles as $vehicle) {
			$vehicle_total += $vehicle['liquidation_value'];
		}

		// 각 자동차 데이터별로 개별 테이블 행 생성
		if (count($vehicles) > 0) {
			foreach ($vehicles as $index => $vehicle) {
				// 새 페이지 확인 - 현재 페이지에 충분한 공간이 없으면 새 페이지 추가
				if ($pdf->GetY() + 25 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 40, '자동차 #'.($index+1)."\n(오토바이 포함)", 1, 'C', false, 0, '', '', true, 0, false, true, 40, 'M');
				$pdf->MultiCell($col2_width, 40, number_format($vehicle['liquidation_value']), 1, 'R', false, 0, '', '', true, 0, false, true, 40, 'M');
				$pdf->MultiCell($col3_width, 40, $vehicle['is_seized'] ?? 'N', 1, 'C', false, 0, '', '', true, 0, false, true, 40, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산 (비고 내 항목명을 위한 공간 할당)
				$first_col_width = 25;
				$second_col_width = $col4_width - $first_col_width;
				$cell_height = 40 / 6; // 6개 항목을 넣기 위해 높이 조정
				
				// 차량정보
				$pdf->Cell($first_col_width, $cell_height, '차량정보', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $vehicle['vehicle_info'] ?? '', 1, 1, 'L');
				
				// 담보권종류
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($first_col_width, $cell_height, '담보권종류', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $vehicle['security_type'] ?? '', 1, 1, 'L');
				
				// 채권(최고)액
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($first_col_width, $cell_height, '채권(최고)액', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, ($vehicle['max_bond'] ? number_format($vehicle['max_bond']).'원' : ''), 1, 1, 'L');
				
				// 환가예상액
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($first_col_width, $cell_height, '환가예상액', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($vehicle['expected_value']).'원', 1, 1, 'L');
				
				// 채무잔액
				$pdf->SetXY($x, $y + ($cell_height * 4));
				$pdf->Cell($first_col_width, $cell_height, '채무잔액', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, ($vehicle['financial_balance'] ? number_format($vehicle['financial_balance']).'원' : ''), 1, 1, 'L');
				
				// 청산가치판단금액
				$pdf->SetXY($x, $y + ($cell_height * 5));
				$pdf->Cell($first_col_width, $cell_height, '청산가치판단금액', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($vehicle['liquidation_value']).'원', 1, 0, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 40);
			}
		} else {
			// 자동차 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '자동차', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}

		
		// 임차보증금
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_rent_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$rent_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$rent_total = 0;
		foreach ($rent_deposits as $rent) {
			$rent_total += $rent['liquidation_value'];
		}
		
		// 각 임차보증금 데이터별로 개별 테이블 행 생성
		if (count($rent_deposits) > 0) {
			foreach ($rent_deposits as $index => $rent) {
				// 새 페이지 확인 - 현재 페이지에 충분한 공간이 없으면 새 페이지 추가
				if ($pdf->GetY() + 50 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 55, "임차보증금 #".($index+1)."\n(반환받을 금액을 금액란에 적는다.)", 1, 'C', false, 0, '', '', true, 0, false, true, 55, 'M');
				$pdf->MultiCell($col2_width, 55, number_format($rent['liquidation_value']), 1, 'R', false, 0, '', '', true, 0, false, true, 55, 'M');
				$pdf->MultiCell($col3_width, 55, $rent['is_seized'] ?? 'N', 1, 'C', false, 0, '', '', true, 0, false, true, 55, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산 (비고 내 항목명을 위한 공간 할당)
				$first_col_width = 25;
				$second_col_width = $col4_width - $first_col_width;
				$cell_height = 55 / 8; // 8개 항목을 넣기 위해 높이 조정
				
				// 임차지
				$pdf->Cell($first_col_width, $cell_height, '임차지', 1, 0, 'C');
				$isBusinessUse = isset($rent['is_business_location']) && $rent['is_business_location'] == 'Y';
				$checkBox = $isBusinessUse ? '[ V]' : '[  ]';
				$pdf->Cell($second_col_width, $cell_height, $rent['rent_location'] ?? '' . " {$checkBox} 영업장", 1, 1, 'L');
				
				// 계약상 보증금
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($first_col_width, $cell_height, '계약상 보증금', 1, 0, 'C');
				$isSpouseOwned = isset($rent['is_deposit_spouse']) && $rent['is_deposit_spouse'] == 1;
				$checkBox = $isSpouseOwned ? '[ V]' : '[  ]';
				$pdf->Cell($second_col_width, $cell_height, number_format($rent['contract_deposit'] ?? 0).'원'. " {$checkBox} 배우자명의", 1, 1, 'L');
				
				// 월세
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($first_col_width, $cell_height, '월세', 1, 0, 'C');
				$isSpouseRent = isset($rent['is_spouse_rent']) && $rent['is_spouse_rent'] == 1;
				$checkBox = $isSpouseRent ? '[ V]' : '[  ]';
				$pdf->Cell($second_col_width, $cell_height, number_format($rent['monthly_rent'] ?? 0).'원' . " {$checkBox} 배우자명의", 1, 1, 'L');
				
				// 반환받을 보증금
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($first_col_width, $cell_height, '반환받을 보증금', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($rent['refundable_deposit'] ?? 0).'원', 1, 1, 'L');
				
				// 차이나는 이유
				$pdf->SetXY($x, $y + ($cell_height * 4));
				$pdf->Cell($first_col_width, $cell_height, '차이나는 이유', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $rent['difference_reason'] ?? '', 1, 1, 'L');
				
				// 압류할 수 없는 최우선 변제 보증금
				$pdf->SetXY($x, $y + ($cell_height * 5));
				$pdf->Cell($first_col_width, $cell_height, '최우선 변제 보증금', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($rent['priority_deposit'] ?? 0).'원', 1, 1, 'L');
				
				// 청산가치 판단금액
				$pdf->SetXY($x, $y + ($cell_height * 6));
				$pdf->Cell($first_col_width, $cell_height, '청산가치 판단금액', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, number_format($rent['liquidation_value']).'원', 1, 1, 'L');
				
				// 부연설명
				$pdf->SetXY($x, $y + ($cell_height * 7));
				$pdf->Cell($first_col_width, $cell_height, '부연설명', 1, 0, 'C');
				$pdf->Cell($second_col_width, $cell_height, $rent['additional_note'] ?? '', 1, 0, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 55);
			}
		} else {
			// 임차보증금 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '임차보증금', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($col4_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 부동산
		$stmt = $pdo->prepare("
			SELECT 
				SUM(property_liquidation_value) as total,
				GROUP_CONCAT(DISTINCT property_location SEPARATOR ', ') as locations,
				GROUP_CONCAT(DISTINCT property_type SEPARATOR ', ') as types,
				GROUP_CONCAT(DISTINCT property_right_type SEPARATOR ', ') as rights,
				MAX(property_expected_value) as expected_value,
				GROUP_CONCAT(DISTINCT property_security_type SEPARATOR ', ') as securities,
				MAX(property_secured_debt) as secured_debt,
				MAX(is_seized) as is_seized
			FROM application_recovery_asset_real_estate
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estate = $stmt->fetch(PDO::FETCH_ASSOC);

		$real_estate_total = $real_estate['total'] ?? 0;
		$real_estate_locations = $real_estate['locations'] ?? '';
		$real_estate_types = $real_estate['types'] ?? '';
		$real_estate_rights = $real_estate['rights'] ?? '';
		$real_estate_values = $real_estate['expected_value'] ?? 0;
		$real_estate_securities = $real_estate['securities'] ?? '';
		$real_estate_secured_debts = $real_estate['secured_debt'] ?? 0;
		$real_estate_seized = $real_estate['is_seized'] ?? 'N';
				
		$pdf->MultiCell($col1_width, 40, "부동산\n(환가 예상액에서 피담보채권을 뺀 금액을 금액란에 적는다.)", 1, 'C', false, 0, '', '', true, 0, false, true, 40, 'M');
		$pdf->MultiCell($col2_width, 40, number_format($real_estate_total), 1, 'R', false, 0, '', '', true, 0, false, true, 40, 'M');
		$pdf->MultiCell($col3_width, 40, $real_estate_seized, 1, 'C', false, 0, '', '', true, 0, false, true, 40, 'M');
				
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 부동산 비고 내용
		$pdf->MultiCell($col4_width, 8, "소재지, 면적: ".$real_estate_locations, 0, 'L');
		$pdf->SetXY($x, $y + 8);
		$pdf->MultiCell($col4_width, 8, "부동산의 종류: ".$real_estate_types, 0, 'L');
		$pdf->SetXY($x, $y + 16);
		$pdf->MultiCell($col4_width, 8, "권리의 종류: ".$real_estate_rights, 0, 'L');
		$pdf->SetXY($x, $y + 24);
		$pdf->MultiCell($col4_width, 8, "환가 예상액: ".($real_estate_values ? number_format($real_estate_values)."원" : ""), 0, 'L');
		$pdf->SetXY($x, $y + 32);
		$pdf->MultiCell($col4_width, 8, "담보권 설정된 경우 그 종류 및 담보액: "
			.($real_estate_securities ? $real_estate_securities." - ".number_format($real_estate_secured_debts)."원" : ""), 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 40);
		$pdf->SetXY($x + $col4_width, $y + 40);
		
		$pdf->Ln(0);
		
		// 사업용 설비
		$stmt = $pdo->prepare("
			SELECT SUM(total) as total,
				   GROUP_CONCAT(item_name SEPARATOR ', ') as items,
				   GROUP_CONCAT(purchase_date SEPARATOR ', ') as dates,
				   GROUP_CONCAT(quantity SEPARATOR ', ') as quantities,
				   GROUP_CONCAT(used_price SEPARATOR ', ') as prices
			FROM application_recovery_asset_business
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$business_total = $business['total'] ?? 0;
		$business_items = $business['items'] ?? '';
		$business_dates = $business['dates'] ?? '';
		$business_quantities = $business['quantities'] ?? '';
		$business_prices = $business['prices'] ?? '';
		
		$pdf->MultiCell($col1_width, 25, "사업용 설비,\n재고품, 비품 등", 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
		$pdf->MultiCell($col2_width, 25, number_format($business_total), 1, 'R', false, 0, '', '', true, 0, false, true, 25, 'M');
		$pdf->MultiCell($col3_width, 25, '', 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
		
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 사업용 설비 비고 내용
		$pdf->MultiCell($col4_width, 8, "품목, 개수: ".$business_items
			.($business_quantities ? " (".$business_quantities."개)" : ""), 0, 'L');
		$pdf->SetXY($x, $y + 8);
		$pdf->MultiCell($col4_width, 8, "구입 시기: ".$business_dates, 0, 'L');
		$pdf->SetXY($x, $y + 16);
		$pdf->MultiCell($col4_width, 9, "평가액: ".($business_prices ? number_format($business_prices)."원" : ""), 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 25);
		$pdf->SetXY($x + $col4_width, $y + 25);
		
		$pdf->Ln(0);
		
		// 대여금 채권
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(debtor_name SEPARATOR ', ') as debtors,
				   GROUP_CONCAT(has_evidence SEPARATOR ', ') as evidences,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_loan_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$loan = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$loan_total = $loan['total'] ?? 0;
		$loan_debtors = $loan['debtors'] ?? '';
		$loan_evidences = $loan['evidences'] ?? '';
		$loan_seized = $loan['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, 20, '대여금 채권', 1, 0, 'C');
		$pdf->Cell($col2_width, 20, number_format($loan_total), 1, 0, 'R');
		$pdf->Cell($col3_width, 20, $loan_seized, 1, 0, 'C');
		
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 대여금 채권 비고 내용
		$debtor_list = explode(', ', $loan_debtors);
		$evidence_list = explode(', ', $loan_evidences);
		$content = '';
		
		for ($i = 0; $i < min(2, count($debtor_list)); $i++) {
			$debtor = isset($debtor_list[$i]) ? $debtor_list[$i] : '';
			$evidence = isset($evidence_list[$i]) ? $evidence_list[$i] : '';
			
			$content .= "상대방 채무자 ".($i+1).": ".$debtor;
			if ($evidence == 'Y') {
				$content .= " [ V] 소명자료 별첨";
			} else {
				$content .= " [  ] 소명자료 별첨";
			}
			
			if ($i < min(1, count($debtor_list) - 1)) {
				$content .= "\n";
			}
		}
		
		$pdf->MultiCell($col4_width, 20, $content, 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 20);
		$pdf->SetXY($x + $col4_width, $y + 20);
		
		$pdf->Ln(0);
		
		// 매출금 채권
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(debtor_name SEPARATOR ', ') as debtors,
				   GROUP_CONCAT(has_evidence SEPARATOR ', ') as evidences,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_sales_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$sales = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$sales_total = $sales['total'] ?? 0;
		$sales_debtors = $sales['debtors'] ?? '';
		$sales_evidences = $sales['evidences'] ?? '';
		$sales_seized = $sales['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, 20, '매출금 채권', 1, 0, 'C');
		$pdf->Cell($col2_width, 20, number_format($sales_total), 1, 0, 'R');
		$pdf->Cell($col3_width, 20, $sales_seized, 1, 0, 'C');
		
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 매출금 채권 비고 내용
		$debtor_list = explode(', ', $sales_debtors);
		$evidence_list = explode(', ', $sales_evidences);
		$content = '';
		
		for ($i = 0; $i < min(2, count($debtor_list)); $i++) {
			$debtor = isset($debtor_list[$i]) ? $debtor_list[$i] : '';
			$evidence = isset($evidence_list[$i]) ? $evidence_list[$i] : '';
			
			$content .= "상대방 채무자 ".($i+1).": ".$debtor;
			if ($evidence == 'Y') {
				$content .= " [ V] 소명자료 별첨";
			} else {
				$content .= " [  ] 소명자료 별첨";
			}
			
			if ($i < min(1, count($debtor_list) - 1)) {
				$content .= "\n";
			}
		}
		
		$pdf->MultiCell($col4_width, 20, $content, 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 20);
		$pdf->SetXY($x + $col4_width, $y + 20);
		
		$pdf->Ln(0);
		
		// 예상 퇴직금
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(workplace SEPARATOR ', ') as workplaces,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_severance
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$severance = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$severance_total = $severance['total'] ?? 0;
		$severance_workplaces = $severance['workplaces'] ?? '';
		$severance_seized = $severance['is_seized'] ?? 'N';
		
		$pdf->Cell($col1_width, $row_height, '예상 퇴직금', 1, 0, 'C');
		$pdf->Cell($col2_width, $row_height, number_format($severance_total), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, $severance_seized, 1, 0, 'C');
		$pdf->Cell($col4_width, $row_height, "근무처: ".$severance_workplaces." (압류할 수 없는 퇴직금 원제외)", 1, 1, 'L');
		
		// 기타 자산
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
		
		$pdf->Cell($col1_width, $row_height, '기타', 1, 0, 'C');
		$pdf->Cell($col2_width, $row_height, number_format($other_total), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, $other_seized, 1, 0, 'C');
		$pdf->Cell($col4_width, $row_height, $other_contents, 1, 1, 'L');
		
		// 합계
		$total_assets = $cash_total + $deposit_total + $insurance_total + $vehicle_total + 
						$rent_total + $real_estate_total + $business_total + 
						$loan_total + $sales_total + $severance_total + $other_total;
		
		
		$pdf->Cell($col1_width, $row_height, '합계', 1, 0, 'C');
		$pdf->Cell($col2_width, $row_height, number_format($total_assets), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, '', 1, 0, 'C');
		$pdf->Cell($col4_width, $row_height, '', 1, 1, 'L');
		
		// 면제재산
		$stmt = $pdo->prepare("
			SELECT SUM(exemption_amount) as total1
			FROM application_recovery_asset_exemption1
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption1 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $pdo->prepare("
			SELECT SUM(exemption_amount) as total2,
				   GROUP_CONCAT(special_property_content SEPARATOR ', ') as contents
			FROM application_recovery_asset_exemption2
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption2 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$exemption1_total = $exemption1['total1'] ?? 0;
		$exemption2_total = $exemption2['total2'] ?? 0;
		$exemption_contents = $exemption2['contents'] ?? '';
		
		$total_exemption = $exemption1_total + $exemption2_total;
		
		$pdf->MultiCell($col1_width, $row_height, "면제재산 결정\n신청 금액", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col2_width, $row_height, number_format($total_exemption), 1, 'R', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col3_width, $row_height, '', 1, 'C', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col4_width, $row_height, "면제재산 결정신청 내용: ".$exemption_contents, 1, 'L', false, 1, '', '', true, 0, false, true, $row_height, 'M');
		
		// 청산가치
		$liquidation_value = $total_assets - $total_exemption;
		
		
		$pdf->Cell($col1_width, $row_height, '청산가치', 1, 0, 'C');
		$pdf->Cell($col2_width, $row_height, number_format($liquidation_value), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, '', 1, 0, 'C');
		$pdf->Cell($col4_width, $row_height, '', 1, 1, 'L');
		
	} catch (Exception $e) {
		$pdf->MultiCell(0, $row_height, 
			"데이터 조회 중 오류가 발생했습니다:\n" . 
			$e->getMessage() . 
			"\n\n관리자에게 문의해 주시기 바랍니다.", 
			0, 
			'C'
		);
	}
}
?>