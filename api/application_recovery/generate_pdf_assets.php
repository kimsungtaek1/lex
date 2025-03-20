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
		
		// 예금
		$stmt = $pdo->prepare("
			SELECT SUM(deposit_amount) as total,
				   GROUP_CONCAT(DISTINCT bank_name SEPARATOR ', ') as banks,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$deposit = $stmt->fetch(PDO::FETCH_ASSOC);

		$deposit_total = $deposit['total'] ?? 0;
		$deposit_banks = $deposit['banks'] ?? '';
		$deposit_seized = $deposit['is_seized'] ?? 'N';

		$pdf->Cell($col1_width, 25, '예금', 1, 0, 'C');
		$pdf->Cell($col2_width, 25, number_format($deposit_total), 1, 0, 'R');
		$pdf->Cell($col3_width, 25, $deposit_seized, 1, 0, 'C');

		// 비고 셀 시작 위치 저장
		$x = $pdf->GetX();
		$y = $pdf->GetY();

		// 열 너비 계산: 첫 번째 열은 25, 두 번째 열은 나머지
		$first_col_width = 25;
		$second_col_width = $col4_width - $first_col_width;
		$cell_height = 25 / 3;

		// 첫 번째 행
		$pdf->Cell($first_col_width, $cell_height, '금융기관명', 1, 0, 'C');
		$pdf->Cell($second_col_width, $cell_height, $deposit_banks, 1, 1, 'L');

		// 두 번째 행의 시작 위치 설정
		$pdf->SetXY($x, $y + $cell_height);

		// 두 번째 행
		$pdf->Cell($first_col_width, $cell_height, '계좌번호', 1, 0, 'C');
		$pdf->Cell($second_col_width, $cell_height, '상세내역 별첨', 1, 1, 'L');

		// 세 번째 행의 시작 위치 설정
		$pdf->SetXY($x, $y + ($cell_height * 2));

		// 세 번째 행
		$pdf->Cell($first_col_width, $cell_height, '잔고', 1, 0, 'C');
		$pdf->Cell($second_col_width, $cell_height, number_format($deposit_total).'원', 1, 0, 'L');

		// Y 위치 조정하여 다음 항목 출력 준비
		$pdf->SetY($y + 25);
		
		// 보험
		$stmt = $pdo->prepare("
			SELECT SUM(refund_amount) as total,
				   GROUP_CONCAT(DISTINCT company_name SEPARATOR ', ') as companies,
				   MAX(is_seized) as is_seized,
				   GROUP_CONCAT(securities_number SEPARATOR ', ') as securities
			FROM application_recovery_asset_insurance
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$insurance = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$insurance_total = $insurance['total'] ?? 0;
		$insurance_companies = $insurance['companies'] ?? '';
		$insurance_seized = $insurance['is_seized'] ?? 'N';
		$insurance_securities = $insurance['securities'] ?? '';
		
		$pdf->Cell($col1_width, 25, '보험', 1, 0, 'C');
		$pdf->Cell($col2_width, 25, number_format($insurance_total), 1, 0, 'R');
		$pdf->Cell($col3_width, 25, $insurance_seized, 1, 0, 'C');
		
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 보험 비고 내용
		$pdf->MultiCell($col4_width, 8, "보험회사명: ".$insurance_companies, 0, 'L');
		$pdf->SetXY($x, $y + 8);
		$pdf->MultiCell($col4_width, 8, "증권번호: ".($insurance_securities ?: "상세내역 별첨"), 0, 'L');
		$pdf->SetXY($x, $y + 16);
		$pdf->MultiCell($col4_width, 9, "해약반환금: ".number_format($insurance_total)."원", 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 25);
		$pdf->SetXY($x + $col4_width, $y + 25);
		
		$pdf->Ln(0);
		
		// 자동차
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(vehicle_info SEPARATOR ', ') as vehicles,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_vehicles
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$vehicle_total = $vehicle['total'] ?? 0;
		$vehicle_info = $vehicle['vehicles'] ?? '';
		$vehicle_seized = $vehicle['is_seized'] ?? 'N';
		
		$pdf->MultiCell($col1_width, $row_height, "자동차\n(오토바이 포함)", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col2_width, $row_height, number_format($vehicle_total), 1, 'R', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col3_width, $row_height, $vehicle_seized, 1, 'C', false, 0, '', '', true, 0, false, true, $row_height, 'M');
		$pdf->MultiCell($col4_width, $row_height, $vehicle_info, 1, 'L', false, 1, '', '', true, 0, false, true, $row_height, 'M');
		
		// 임차보증금
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total,
				   GROUP_CONCAT(rent_location SEPARATOR ', ') as locations,
				   GROUP_CONCAT(contract_deposit SEPARATOR ', ') as deposits,
				   GROUP_CONCAT(monthly_rent SEPARATOR ', ') as rents,
				   GROUP_CONCAT(difference_reason SEPARATOR ', ') as reasons,
				   MAX(is_seized) as is_seized
			FROM application_recovery_asset_rent_deposits
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$rent = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$rent_total = $rent['total'] ?? 0;
		$rent_locations = $rent['locations'] ?? '';
		$rent_deposits = $rent['deposits'] ?? '';
		$rent_rents = $rent['rents'] ?? '';
		$rent_reasons = $rent['reasons'] ?? '';
		$rent_seized = $rent['is_seized'] ?? 'N';
		
		$pdf->MultiCell($col1_width, 25, "임차보증금\n(반환받을 금액을 금액란에 적는다.)", 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
		$pdf->MultiCell($col2_width, 25, number_format($rent_total), 1, 'R', false, 0, '', '', true, 0, false, true, 25, 'M');
		$pdf->MultiCell($col3_width, 25, $rent_seized, 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
		
		// 비고 셀 생성
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		// 임차보증금 비고 내용
		$pdf->MultiCell($col4_width, 8, "임차물건: ".$rent_locations, 0, 'L');
		$pdf->SetXY($x, $y + 8);
		$pdf->MultiCell($col4_width, 8, "보증금 및 월세: ".($rent_deposits ? "보증금 ".number_format($rent_deposits)."원" : "")
			.($rent_rents ? ", 월세 ".number_format($rent_rents)."원" : ""), 0, 'L');
		$pdf->SetXY($x, $y + 16);
		$pdf->MultiCell($col4_width, 9, "차이나는 사유: ".$rent_reasons, 0, 'L');
		
		// 비고 셀 경계선
		$pdf->Rect($x, $y, $col4_width, 25);
		$pdf->SetXY($x + $col4_width, $y + 25);
		
		$pdf->Ln(0);
		
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
				$content .= " ☑ 소명자료 별첨";
			} else {
				$content .= " □ 소명자료 별첨";
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
				$content .= " ☑ 소명자료 별첨";
			} else {
				$content .= " □ 소명자료 별첨";
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