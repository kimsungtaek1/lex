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
	
	// 비고 내부 컬럼 설정
	$note_col1_width = 25; // 비고 제목 컬럼
	$note_col2_width = $col4_width - $note_col1_width; // 비고 내용 컬럼
	
	// 행 높이
	$row_height = 8;

	// 헤더 출력
	$pdf->MultiCell($col1_width, 10, "명칭", 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
	$pdf->MultiCell($col2_width, 10, "금액 또는 시가\n(단위: 원)", 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
	$pdf->MultiCell($col3_width, 10, "압류 등\n유무", 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
	$pdf->MultiCell($col4_width, 10, "비고", 1, 'C', true, 1, '', '', true, 0, false, true, 10, 'M');
		
	try {
		// 현금
		$pdf->Cell($col1_width, $row_height, '현금', 1, 0, 'C');
		
		// 현금 데이터 조회
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total, 
				   MAX(is_seized) as is_seized,
				   GROUP_CONCAT(property_detail SEPARATOR ', ') as details
			FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$cash_total = $cash['total'] ?? 0;
		$cash_seized = $cash['is_seized'] ?? 'N';
		$cash_details = $cash['details'] ?? '';
		
		$pdf->Cell($col2_width, $row_height, number_format($cash_total), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, $cash_seized, 1, 0, 'C');
		
		// 비고 컬럼 나누기
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->Cell($note_col1_width, $row_height, '상세정보', 1, 0, 'C');
		$pdf->Cell($note_col2_width, $row_height, $cash_details, 1, 1, 'L');
		
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
				$cell_height = 25 / 3;
				
				// 첫 번째 행 - 금융기관명
				$pdf->Cell($note_col1_width, $cell_height, '금융기관명', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $deposit['bank_name'] ?? '', 1, 1, 'L');
				
				// 두 번째 행 - 계좌번호
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '계좌번호', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $deposit['account_number'] ?? '상세내역 별첨', 1, 1, 'L');
				
				// 세 번째 행 - 잔고
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($note_col1_width, $cell_height, '잔고', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($deposit['deposit_amount']).'원', 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 25);
			}
		} else {
			// 예금 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '예금', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
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
				$cell_height = 32 / 4;
				
				// 보장성 보험여부
				$pdf->Cell($note_col1_width, $cell_height, '보장성보험여부', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $insurance['is_coverage'] ?? '', 1, 1, 'L');

				// 보험회사명
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '보험회사명', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $insurance['company_name'] ?? '', 1, 1, 'L');

				// 증권번호
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($note_col1_width, $cell_height, '증권번호', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $insurance['securities_number'] ?? '상세내역 별첨', 1, 1, 'L');

				// 해약환급금
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($note_col1_width, $cell_height, '해약환급금', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($insurance['refund_amount']).'원', 1, 1, 'L');
								
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 32);
			}
		} else {
			// 보험 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '보험', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
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
				if ($pdf->GetY() + 48 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 48, '자동차 #'.($index+1)."\n(오토바이 포함)", 1, 'C', false, 0, '', '', true, 0, false, true, 48, 'M');
				$pdf->MultiCell($col2_width, 48, number_format($vehicle['liquidation_value']), 1, 'R', false, 0, '', '', true, 0, false, true, 48, 'M');
				$pdf->MultiCell($col3_width, 48, $vehicle['is_seized'] ?? 'N', 1, 'C', false, 0, '', '', true, 0, false, true, 48, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산 (비고 내 항목명을 위한 공간 할당)
				$cell_height = 48 / 7; // 7개 항목을 넣기 위해 높이 조정
				
				// 차량정보
				$pdf->Cell($note_col1_width, $cell_height, '차량정보', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $vehicle['vehicle_info'] ?? '', 1, 1, 'L');
				
				// 담보권종류
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '담보권종류', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $vehicle['security_type'] ?? '', 1, 1, 'L');
				
				// 채권(최고)액
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($note_col1_width, $cell_height, '채권(최고)액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, ($vehicle['max_bond'] ? number_format($vehicle['max_bond']).'원' : ''), 1, 1, 'L');
				
				// 환가예상액
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($note_col1_width, $cell_height, '환가예상액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($vehicle['expected_value']).'원', 1, 1, 'L');
				
				// 채무잔액
				$pdf->SetXY($x, $y + ($cell_height * 4));
				$pdf->Cell($note_col1_width, $cell_height, '채무잔액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, ($vehicle['financial_balance'] ? number_format($vehicle['financial_balance']).'원' : ''), 1, 1, 'L');
				
				// 청산가치판단금액
				$pdf->SetXY($x, $y + ($cell_height * 5));
				$pdf->Cell($note_col1_width, $cell_height, '청산가치판단금액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($vehicle['liquidation_value']).'원', 1, 1, 'L');
				
				// 내용
				$pdf->SetXY($x, $y + ($cell_height * 6));
				$pdf->Cell($note_col1_width+$note_col2_width, $cell_height, $vehicle['explanation'], 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 48);
			}
		} else {
			// 자동차 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '자동차', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
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
				if ($pdf->GetY() + 60 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 60, "임차보증금 #".($index+1)."\n(반환받을 금액을 금액란에 적는다.)", 1, 'C', false, 0, '', '', true, 0, false, true, 60, 'M');
				$pdf->MultiCell($col2_width, 60, number_format($rent['liquidation_value']), 1, 'R', false, 0, '', '', true, 0, false, true, 60, 'M');
				$pdf->MultiCell($col3_width, 60, $rent['is_seized'] ?? 'N', 1, 'C', false, 0, '', '', true, 0, false, true, 60, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산 (비고 내 항목명을 위한 공간 할당)
				$cell_height = 60 / 9; // 9개 항목을 넣기 위해 높이 조정
				
				// 임차지
				$pdf->Cell($note_col1_width, $cell_height, '임차지', 1, 0, 'C');
				$isBusinessUse = isset($rent['is_business_place']) && $rent['is_business_place'] == 'Y';
				$checkBox = $isBusinessUse ? '[ V]' : '[   ]';
				$pdf->Cell($note_col2_width, $cell_height, $rent['rent_location'] . "   영업장{$checkBox}", 1, 1, 'L');
				
				// 보증금 및 월세 (4개 컬럼으로 나누기)
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '보증금', 1, 0, 'C');
				
				// 4개 컬럼으로 나누기 위한 너비 계산
				$sub_col_width = $note_col2_width / 4;
				$start_x = $x + $note_col1_width;
				
				$isSpouseOwned = isset($rent['is_deposit_spouse']) && $rent['is_deposit_spouse'] == 1;
				$spouseCheckBox = $isSpouseOwned ? ' 배우자명의[ V]' : '';
				
				// 1 번째 컬럼: 보증금 금액
				$pdf->SetXY($start_x, $y + $cell_height);
				$pdf->Cell($sub_col_width * 2, $cell_height, number_format($rent['contract_deposit']).'원'.$spouseCheckBox, 1, 0, 'R');
				
				// 2 번째 컬럼: "월세" 라벨
				$pdf->SetXY($start_x + $sub_col_width * 2, $y + $cell_height);
				$pdf->Cell($sub_col_width * 0.5, $cell_height, '월세', 1, 0, 'C');
				
				// 3 번째 컬럼: 월세 금액
				$pdf->SetXY($start_x + $sub_col_width * 2.5, $y + $cell_height);
				$pdf->Cell($sub_col_width * 1.5, $cell_height, number_format($rent['monthly_rent']).'원', 1, 0, 'R');
				
				// Y 위치 재설정
				$pdf->SetXY($x, $y + $cell_height * 2);
				
				// 반환받을 보증금
				$pdf->Cell($note_col1_width, $cell_height, '반환받을 보증금', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($rent['refund_deposit'] ?? 0).'원', 1, 1, 'L');
				
				// 차이나는 이유
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($note_col1_width, $cell_height * 2, '차이나는 이유', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height * 2, $rent['difference_reason'] ?? '', 1, 1, 'L');
				
				// 압류할 수 없는 최우선 변제 보증금
				$pdf->SetXY($x, $y + ($cell_height * 5));
				$pdf->Cell($note_col1_width, $cell_height, '최우선변제보증금', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($rent['priority_deposit'] ?? 0).'원', 1, 1, 'L');
				
				// 청산가치 판단금액
				$pdf->SetXY($x, $y + ($cell_height * 6));
				$pdf->Cell($note_col1_width, $cell_height, '청산가치판단금액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($rent['liquidation_value']).'원', 1, 1, 'L');
				
				// 부연설명
				$pdf->SetXY($x, $y + ($cell_height * 7));
				$pdf->Cell($note_col1_width, $cell_height * 2, '부연설명', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height * 2, $rent['explanation'] ?? '', 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 60);
			}
		} else {
			// 임차보증금 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '임차보증금', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 부동산
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_real_estate
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estates = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$real_estate_total = 0;
		
		if (count($real_estates) > 0) {
			foreach ($real_estates as $index => $real_estate) {
				$real_estate_total += $real_estate['property_liquidation_value'];
				
				// 새 페이지 확인
				if ($pdf->GetY() + 72 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 72, "부동산 #".($index+1)."\n(환가 예상액에서 피담보채권을 뺀 금액을 금액란에 적는다.)", 1, 'C', false, 0, '', '', true, 0, false, true, 72, 'M');
				$pdf->MultiCell($col2_width, 72, number_format($real_estate['property_liquidation_value']), 1, 'R', false, 0, '', '', true, 0, false, true, 72, 'M');
				$pdf->MultiCell($col3_width, 72, $real_estate['is_seized'] ?? 'N', 1, 'C', false, 0, '', '', true, 0, false, true, 72, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$cell_height = 72 / 9; // 9개 항목을 넣기 위해 높이 조정
				
				$isSpouseOwned = isset($real_estate['is_spouse']) && $real_estate['is_spouse'] == 1;
				$spouseCheckBox = $isSpouseOwned ? ' 배우자명의[ V]' : '';
				
				// 소재지
				$pdf->Cell($note_col1_width, $cell_height, '소재지', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $real_estate['property_location'].$spouseCheckBox, 1, 1, 'L');
				
				// 면적
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '면적', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($real_estate['property_area']) . "㎡", 1, 1, 'L');
				
				// 부동산의 종류
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($note_col1_width, $cell_height, '부동산의 종류', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $real_estate['property_type'] ?? '', 1, 1, 'L');
				
				// 권리의 종류
				$pdf->SetXY($x, $y + ($cell_height * 3));
				$pdf->Cell($note_col1_width, $cell_height, '권리의 종류', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $real_estate['property_right_type'] ?? '', 1, 1, 'L');
				
				// 환가 예상액
				$pdf->SetXY($x, $y + ($cell_height * 4));
				$pdf->Cell($note_col1_width, $cell_height, '환가 예상액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($real_estate['property_expected_value'] ?? 0)."원", 1, 1, 'L');
				
				// 담보권이 설정된 경우 그 종류 및 담보액
				$pdf->SetXY($x, $y + ($cell_height * 5));
				$pdf->MultiCell($note_col1_width, $cell_height*2, "담보권이\n설정된 경우\n그 종류 및 담보액", 1, 'C', false, 0, '', '', true, 0, false, true, 16, 'M');
				$securityInfo = "";
				
				$pdf->Cell($note_col2_width * 0.2, $cell_height * 2, $real_estate['property_security_type'], 1, 1, 'L');
				$pdf->SetXY($start_x + $note_col2_width * 0.2, $y + ($cell_height * 5));
				$pdf->Cell($note_col2_width * 0.8, $cell_height * 2, number_format($real_estate['property_secured_debt'] ?? 0) . "원", 1, 1, 'L');
				
				// 부연설명
				$pdf->SetXY($x, $y + ($cell_height * 7));
				$pdf->Cell($note_col1_width, $cell_height * 2, '부연설명', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height * 2, $rent['explanation'] ?? '', 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 72);
			}
		} else {
			// 부동산 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '부동산', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 사업용 설비 - 수정된 코드
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_business
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business_equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$business_total = 0;

		// 각 사업용 설비 데이터별로 개별 테이블 행 생성
		if (count($business_equipments) > 0) {
			foreach ($business_equipments as $index => $equipment) {
				$business_total += $equipment['total'];
				
				// 새 페이지 확인 - 현재 페이지에 충분한 공간이 없으면 새 페이지 추가
				if ($pdf->GetY() + 25 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->MultiCell($col1_width, 25, "사업용 설비,\n재고품, 비품 등 #".($index+1), 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
				$pdf->MultiCell($col2_width, 25, number_format($equipment['used_price'] * $equipment['quantity']), 1, 'R', false, 0, '', '', true, 0, false, true, 25, 'M');
				$pdf->MultiCell($col3_width, 25, '', 1, 'C', false, 0, '', '', true, 0, false, true, 25, 'M');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$cell_height = 25 / 3; // 3개 항목을 넣기 위해 높이 조정
				
				// 품목, 개수
				$pdf->Cell($note_col1_width, $cell_height, '품목, 개수', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $equipment['item_name'] . " (" . $equipment['quantity'] . "개)", 1, 1, 'L');
				
				// 구입 시기
				$pdf->SetXY($x, $y + $cell_height);
				$pdf->Cell($note_col1_width, $cell_height, '구입 시기', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, $equipment['purchase_date'], 1, 1, 'L');
				
				// 평가액
				$pdf->SetXY($x, $y + ($cell_height * 2));
				$pdf->Cell($note_col1_width, $cell_height, '평가액', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $cell_height, number_format($equipment['used_price'] * $equipment['quantity']) . "원", 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 25);
			}
		} else {
			// 사업용 설비 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '사업용 설비', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 대여금 채권
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_loan_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$loan_receivables = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$loan_total = 0;
		foreach ($loan_receivables as $loan) {
			$loan_total += $loan['liquidation_value'] ?? 0;
		}
		
		// 대여금 채권 출력
		if (count($loan_receivables) > 0) {
			foreach ($loan_receivables as $index => $loan) {
				// 새 페이지 확인
				if ($pdf->GetY() + 15 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->Cell($col1_width, 15, '대여금 채권 #'.($index+1), 1, 0, 'C');
				$pdf->Cell($col2_width, 15, number_format($loan['liquidation_value'] ?? 0), 1, 0, 'R');
				$pdf->Cell($col3_width, 15, $loan['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$cell_height = 15;
				
				// 상대방 채무자
				$pdf->Cell($note_col1_width, $cell_height, '상대방 채무자', 1, 0, 'C');
				$evidence_text = ($loan['has_evidence'] == 'Y') ? '[ V] 소명자료 별첨' : '[   ] 소명자료 별첨';
				$pdf->Cell($note_col2_width, $cell_height, $loan['debtor_name'] . " " . $evidence_text, 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 15);
			}
		} else {
			// 대여금 채권 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '대여금 채권', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 매출금 채권
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_sales_receivables
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$sales_receivables = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$sales_total = 0;
		foreach ($sales_receivables as $sales) {
			$sales_total += $sales['liquidation_value'] ?? 0;
		}
		
		// 매출금 채권 출력
		if (count($sales_receivables) > 0) {
			foreach ($sales_receivables as $index => $sales) {
				// 새 페이지 확인
				if ($pdf->GetY() + 15 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->Cell($col1_width, 15, '매출금 채권 #'.($index+1), 1, 0, 'C');
				$pdf->Cell($col2_width, 15, number_format($sales['liquidation_value'] ?? 0), 1, 0, 'R');
				$pdf->Cell($col3_width, 15, $sales['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 비고 셀 시작 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 열 너비 계산
				$cell_height = 15;
				
				// 상대방 채무자
				$pdf->Cell($note_col1_width, $cell_height, '상대방 채무자', 1, 0, 'C');
				$evidence_text = ($sales['has_evidence'] == 'Y') ? '[ V] 소명자료 별첨' : '[   ] 소명자료 별첨';
				$pdf->Cell($note_col2_width, $cell_height, $sales['debtor_name'] . " " . $evidence_text, 1, 1, 'L');
				
				// Y 위치 조정하여 다음 항목 출력 준비
				$pdf->SetY($y + 15);
			}
		} else {
			// 매출금 채권 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '매출금 채권', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 예상 퇴직금
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_severance
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$severance_pays = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$severance_total = 0;
		$workplaces = [];
		$severance_seized = 'N';

		foreach ($severance_pays as $severance) {
			$severance_total += $severance['liquidation_value'] ?? 0;
			if ($severance['workplace']) {
				$workplaces[] = $severance['workplace'];
			}
			if ($severance['is_seized'] == 'Y') {
				$severance_seized = 'Y';
			}
		}

		// 예상 퇴직금 출력
		if (count($severance_pays) > 0) {
			// 새 페이지 확인
			if ($pdf->GetY() + $row_height * 2 > $pdf->getPageHeight() - 20) {
				$pdf->AddPage();
			}
			
			$pdf->Cell($col1_width, $row_height * 2, '예상 퇴직금', 1, 0, 'C');
			$pdf->Cell($col2_width, $row_height * 2, number_format($severance_total), 1, 0, 'R');
			$pdf->Cell($col3_width, $row_height * 2, $severance_seized, 1, 0, 'C');
			
			// 비고 셀 시작 위치 저장
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			
			// 근무처 정보 - 첫 번째 줄
			$pdf->Cell($note_col1_width, $row_height, '근무처', 1, 0, 'C');
			$pdf->Cell($note_col2_width, $row_height, implode(', ', $workplaces), 1, 1, 'L');
			
			// 퇴직금 정보 - 두 번째 줄
			$pdf->SetXY($x, $y + $row_height);
			$pdf->Cell($note_col1_width, $row_height, '퇴직금', 1, 0, 'C');
			
			$severance_info = '';
			if (!empty($severance_pays[0])) {
				$expectedAmount = $severance_pays[0]['expected_severance'] ?? 0;
				$deductionAmount = $severance_pays[0]['deduction_amount'] ?? 0;
				$severance_info = number_format($expectedAmount) . '원';
				
				if ($deductionAmount > 0) {
					$severance_info .= ' (압류할 수 없는 퇴직금 ' . number_format($deductionAmount) . '원 제외)';
				}
			}
			
			$pdf->Cell($note_col2_width, $row_height, $severance_info, 1, 1, 'L');
			
			// Y 위치 조정
			$pdf->SetY($y + $row_height * 2);
			
		} else {
			// 예상 퇴직금 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '예상 퇴직금', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// (가)압류 적립금
		$stmt = $pdo->prepare("
		  SELECT *
		  FROM application_recovery_asset_seizure_deposit 
		  WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$seizure_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (count($seizure_deposits) > 0) {
		  foreach ($seizure_deposits as $index => $seizure_deposit) {
			// 새 페이지 확인
			if ($pdf->GetY() + $row_height > $pdf->getPageHeight() - 20) {
			  $pdf->AddPage();
			}
			
			$pdf->Cell($col1_width, $row_height, '(가)압류 적립금 #'.($index+1), 1, 0, 'C');
			$pdf->Cell($col2_width, $row_height, number_format($seizure_deposit['liquidation_value'] ?? 0), 1, 0, 'R');
			$pdf->Cell($col3_width, $row_height, '', 1, 0, 'C');
			
			// 비고 셀 출력
			$pdf->Cell($note_col1_width, $row_height, '내용', 1, 0, 'C');
			
			// 추가 정보 문자열 구성
			$additionalInfo = $seizure_deposit['seizure_content'] ?? '';
			
			// 모든 정보를 순차적으로 추가
			if (!empty($seizure_deposit['keeper'])) {
			  $additionalInfo .= " [보관자: " . $seizure_deposit['keeper'] . "]";
			}
			
			// 체크박스 정보 추가
			if (isset($seizure_deposit['exclude_liquidation']) && $seizure_deposit['exclude_liquidation'] == 'Y') {
			  $additionalInfo .= " [청산가치 제외]";
			}
			
			if (isset($seizure_deposit['repayment_input']) && $seizure_deposit['repayment_input'] == 'Y') {
			  $additionalInfo .= " [가용소득 1회 투입]";
			}
			
			$pdf->Cell($note_col2_width, $row_height, $additionalInfo, 1, 1, 'L');
		  }
		} else {
		  // (가)압류 적립금 데이터가 없는 경우
		  $pdf->Cell($col1_width, 8, '(가)압류 적립금', 1, 0, 'C');
		  $pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
		  $pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		  $pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
		  $pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}

		// 공탁금
		$stmt = $pdo->prepare("
		  SELECT *
		  FROM application_recovery_asset_seizure_reserve 
		  WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$seizure_reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (count($seizure_reserves) > 0) {
		  foreach ($seizure_reserves as $index => $seizure_reserve) {
			// 새 페이지 확인
			if ($pdf->GetY() + $row_height > $pdf->getPageHeight() - 20) {
			  $pdf->AddPage();
			}
			
			$pdf->Cell($col1_width, $row_height, '공탁금 #'.($index+1), 1, 0, 'C');
			$pdf->Cell($col2_width, $row_height, number_format($seizure_reserve['liquidation_value'] ?? 0), 1, 0, 'R');
			$pdf->Cell($col3_width, $row_height, '', 1, 0, 'C');
			
			// 비고 셀 출력
			$pdf->Cell($note_col1_width, $row_height, '내용', 1, 0, 'C');
			
			// 추가 정보 문자열 구성
			$additionalInfo = $seizure_reserve['seizure_reserve_content'] ?? '';
			
			// 모든 정보를 순차적으로 추가
			if (!empty($seizure_reserve['keeper'])) {
			  $additionalInfo .= " [공탁된 법원: " . $seizure_reserve['keeper'] . "]";
			}
			
			// 체크박스 정보 추가
			if (isset($seizure_reserve['reserve_exclude_liquidation']) && $seizure_reserve['reserve_exclude_liquidation'] == 'Y') {
			  $additionalInfo .= " [청산가치 제외]";
			}
			
			if (isset($seizure_reserve['repayment_input']) && $seizure_reserve['repayment_input'] == 'Y') {
			  $additionalInfo .= " [가용소득 1회 투입]";
			}
			
			$pdf->Cell($note_col2_width, $row_height, $additionalInfo, 1, 1, 'L');
		  }
		} else {
		  // 공탁금 데이터가 없는 경우
		  $pdf->Cell($col1_width, 8, '공탁금', 1, 0, 'C');
		  $pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
		  $pdf->Cell($col3_width, 8, '', 1, 0, 'C');
		  $pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
		  $pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 기타 자산
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_other
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$other_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$other_total = 0;

		// 각 기타 자산 데이터별로 개별 테이블 행 생성
		if (count($other_assets) > 0) {
			foreach ($other_assets as $index => $other) {
				$other_total += $other['liquidation_value'] ?? 0;
				
				// 새 페이지 확인
				if ($pdf->GetY() + $row_height > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
				}
				
				$pdf->Cell($col1_width, $row_height, '기타 #'.($index+1), 1, 0, 'C');
				$pdf->Cell($col2_width, $row_height, number_format($other['liquidation_value'] ?? 0), 1, 0, 'R');
				$pdf->Cell($col3_width, $row_height, $other['is_seized'] ?? 'N', 1, 0, 'C');
				
				// 비고 셀 출력
				$pdf->Cell($note_col1_width, $row_height, '재산 내용', 1, 0, 'C');
				$pdf->Cell($note_col2_width, $row_height, $other['asset_content'] ?? '', 1, 1, 'L');
			}
		} else {
			// 기타 자산 데이터가 없는 경우
			$pdf->Cell($col1_width, 8, '기타', 1, 0, 'C');
			$pdf->Cell($col2_width, 8, '0', 1, 0, 'R');
			$pdf->Cell($col3_width, 8, '', 1, 0, 'C');
			$pdf->Cell($note_col1_width, 8, '해당 여부', 1, 0, 'C');
			$pdf->Cell($note_col2_width, 8, '해당 없음', 1, 1, 'L');
		}
		
		// 합계 계산
		$total_assets = $cash_total + $deposit_total + $insurance_total + $vehicle_total + 
						$rent_total + $real_estate_total + $business_total + 
						$loan_total + $sales_total + $severance_total + $other_total;
		
		// 합계 출력
		$pdf->Cell($col1_width, $row_height, '합계', 1, 0, 'C');
		$pdf->Cell($col2_width, $row_height, number_format($total_assets), 1, 0, 'R');
		$pdf->Cell($col3_width, $row_height, '', 1, 0, 'C');
		$pdf->Cell($note_col1_width, $row_height, '', 1, 0, 'C');
		$pdf->Cell($note_col2_width, $row_height, '', 1, 1, 'L');
		
		
		
		// 면제재산 - 임차보증금반환청구권
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_exemption1
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption1 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$exemption1_total = $exemption1['exemption_amount'] ?? 0;
		
		// 면제재산 - 6개월간 생계비
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_recovery_asset_exemption2
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$exemption2 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$exemption2_total = $exemption2['exemption_amount'] ?? 0;
		$exemption2_contents = $exemption2['special_property_content'] ?? '';
		
		// 면제재산 - 임차보증금반환청구권 출력 (2줄로 수정)
		if ($exemption1_total > 0) {
			// 새 페이지 확인
			if ($pdf->GetY() + $row_height*2 > $pdf->getPageHeight() - 20) {
				$pdf->AddPage();
			}
			
			// 면제재산 결정신청 금액을 2줄로 표시
			$pdf->MultiCell($col1_width, $row_height*2, "면제재산\n결정신청 금액", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($col2_width, $row_height*2, number_format($exemption1_total), 1, 'R', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($col3_width, $row_height*2, '', 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			
			// 비고 셀 출력
			$pdf->MultiCell($note_col1_width, $row_height*2, "면제재산\n종류", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($note_col2_width, $row_height*2, '1. 주거용 임차보증금 반환청구권', 1, 'L', false, 1, '', '', true, 0, false, true, $row_height*2, 'M');
		}

		// 면제재산 - 6개월간 생계비 출력 (2줄로 수정)
		if ($exemption2_total > 0) {
			// 새 페이지 확인
			if ($pdf->GetY() + $row_height*2 > $pdf->getPageHeight() - 20) {
				$pdf->AddPage();
			}
			
			// 면제재산 결정신청 금액을 2줄로 표시
			$pdf->MultiCell($col1_width, $row_height*2, "면제재산\n결정신청 금액", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($col2_width, $row_height*2, number_format($exemption2_total), 1, 'R', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($col3_width, $row_height*2, '', 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			
			// 비고 셀 출력
			$pdf->MultiCell($note_col1_width, $row_height*2, "면제재산\n종류", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*2, 'M');
			$pdf->MultiCell($note_col2_width, $row_height*2, '2. 6개월간 생계비에 사용할 특정재산', 1, 'L', false, 1, '', '', true, 0, false, true, $row_height*2, 'M');
		}
		
		
		// 청산가치 출력 (3줄 멀티셀로 수정)
		// 청산가치 계산
		$total_exemption = $exemption1_total + $exemption2_total;
		$liquidation_value = $total_assets - $total_exemption;

		// 청산가치 출력 - 3줄짜리 멀티셀로 수정
		$pdf->MultiCell($col1_width, $row_height*3, "청산가치\n\n(총 재산에서\n면제재산 제외)", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*3, 'M');
		$pdf->MultiCell($col2_width, $row_height*3, number_format($liquidation_value), 1, 'R', false, 0, '', '', true, 0, false, true, $row_height*3, 'M');
		$pdf->MultiCell($col3_width, $row_height*3, '', 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*3, 'M');

		// 비고 셀 - 예금과 보험 청산가치 계산 내역
		$pdf->MultiCell($note_col1_width, $row_height*3, "청산가치\n계산내역", 1, 'C', false, 0, '', '', true, 0, false, true, $row_height*3, 'M');

		// 예금 청산가치 계산
		$deposit_exemption = 1850000; // 압류금지 예금 (185만원)
		$deposit_liquidation = $deposit_total - $deposit_exemption;
		if ($deposit_liquidation < 0) $deposit_liquidation = 0;

		// 보험 청산가치 계산
		$insurance_coverage_total = 0;
		foreach ($insurances as $insurance) {
			if ($insurance['is_coverage'] == 'Y') {
				$insurance_coverage_total += $insurance['refund_amount'] ?? 0;
			}
		}
		$insurance_exemption = 1500000; // 압류금지 보장성보험금 (150만원)
		$insurance_liquidation = $insurance_coverage_total - $insurance_exemption;
		if ($insurance_liquidation < 0) $insurance_liquidation = 0;
		$insurance_liquidation += ($insurance_total - $insurance_coverage_total);

		$calculation_note = "*예금(청산가치) : " . number_format($deposit_liquidation) . "원\n";
		$calculation_note .= "=[예치금액 합계: " . number_format($deposit_total) . " - 공제금액: " . number_format($deposit_exemption) . "]\n";
		$calculation_note .= "*보험(청산가치): " . number_format($insurance_liquidation) . "원\n";
		$calculation_note .= "=[{보장성: " . number_format($insurance_coverage_total) . " - 공제금액: " . number_format($insurance_exemption) . "} + " . number_format($insurance_total - $insurance_coverage_total) . "]";

		$pdf->MultiCell($note_col2_width, $row_height*3, $calculation_note, 1, 'L', false, 1, '', '', true, 0, false, true, $row_height*3, 'T');
		
		
	} catch (Exception $e) {
		$pdf->MultiCell(0, $row_height, 
			"데이터 조회 중 오류가 발생했습니다:\n" . 
			$e->getMessage() . 
			"\n\n관리자에게 문의해 주시기 바랍니다.", 
			0, 
			'C'
		);
	}
	
	// 면제재산 결정신청서 추가
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 8, '면제재산 결정신청서', 0, 1, 'C');
	$pdf->Ln(5);
	
	// 사건 정보
	$pdf->SetFont('cid0kr', '', 10);
	
	// 사건 정보 조회
	$stmt = $pdo->prepare("
		SELECT r.case_no, r.court_name, r.name, c.case_number
		FROM application_recovery r
		JOIN case_management c ON r.case_no = c.case_no
		WHERE r.case_no = ?
	");
	$stmt->execute([$case_no]);
	$case_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
	$pdf->Cell(40, 8, '사                     건', 0, 0, 'L');
	$pdf->Cell(160, 8, $case_info['case_number'].' 개인회생', 0, 1, 'L');
	
	$pdf->Cell(40, 8, '신 청 인(채 무 자)', 0, 0, 'L');
	$pdf->Cell(120, 8, $case_info['name'] ?? '', 0, 1, 'L');
	
	$pdf->Ln(5);
	
	$pdf->MultiCell(170, 8, 
		"신청인은 채무자 회생 및 파산에 관한 법률 제580조 제3항,제1항 제1호,제383조 제2항에 따라 채무자 소유의 별지 목록 기재  재산을 면제재산으로 정한다는 결정을 구합니다. \n(※아래 해당되는 부분에 ∨ 표를 하고, 면제재산결정 신청을 하는 재산목록 및 소명자료를 첨부하시기 바랍니다.)", 
		0, 'L'
	);
	
	$pdf->Ln(5);
	
	// 임차보증금 면제재산 표시
	$checkbox1 = ($exemption1_total > 0) ? "[ V]" : "[   ]";
	$pdf->Cell(20, 8, $checkbox1.'    1. 주거용건물 임차보증금반환청구권에 대한 면제재산결정 신청', 0, 1, 'L');
	$pdf->Cell(20, 8, '(법 제580조 제3항, 제1항 제1호, 제383조 제2항 제1호)', 0, 1, 'L');
	
	$pdf->Cell(170, 8, '※ 첨부서류', 0, 1, 'L');
	$pdf->Cell(170, 8, '가. 별지 면제재산목록 (채권자수 + 3부)', 0, 1, 'L');
	$pdf->Cell(25, 8, '나. 소명자료 : ', 0, 0, 'L');
	
	// 소명자료 체크박스
	$contract_check = ($exemption1['lease_contract'] ?? 'N') == 'Y' ? '[ V]' : '[   ]';
	$contract_check_num = ($exemption1['lease_contract'] ?? 'N') == 'Y' ? '1' : '0';
	$resident_check = ($exemption1['resident_registration'] ?? 'N') == 'Y' ? '[ V]' : '[   ]';
	$resident_check_num = ($exemption1['resident_registration'] ?? 'N') == 'Y' ? '1' : '0';
	$other_check = ($exemption1['other_evidence'] ?? 'N') == 'Y' ? '[ V]' : '[   ]';
	$other_check_num = ($exemption1['other_evidence'] ?? 'N') == 'Y' ? $exemption1['other_evidence_detail'] : '';
	
	$pdf->Cell(7, 8, $contract_check, 0, 0, 'L');
	$pdf->Cell(35, 8, '임대차계약서                  '.$contract_check_num.'부', 0, 1, 'L');
	$pdf->Cell(25, 8, '', 0, 0, 'L');
	$pdf->Cell(7, 8, $resident_check, 0, 0, 'L');
	$pdf->Cell(35, 8, '주민등록등본                  '.$resident_check_num.'통', 0, 1, 'L');
	$pdf->Cell(25, 8, '', 0, 0, 'L');
	$pdf->Cell(7, 8, $other_check, 0, 0, 'L');
	$pdf->Cell(10, 8, '기타 [', 0, 0, 'L');
	$pdf->Cell(30, 8, $other_check_num, 0, 0, 'L');
	$pdf->Cell(5, 8, '] 통', 0, 1, 'L');
	
	$pdf->Ln(5);
	
	// 6개월간 생계비 면제재산 표시
	$checkbox2 = ($exemption2_total > 0) ? "[ V]" : "[   ]";
	$pdf->Cell(170, 8, $checkbox2.'    2. 6개월간의 생계비에 사용할 특정재산에 대한 면제재산결정 신청', 0, 1, 'L');
	$pdf->Cell(170, 8, '(법 제580조 제3항, 제1항 제1호, 제383조 제2항 제2호)', 0, 1, 'L');
	
	$pdf->Cell(170, 8, '※ 첨부서류', 0, 1, 'L');
	$pdf->Cell(170, 8, '가. 별지 면제재산목록 (채권자수 + 3부)', 0, 1, 'L');
	$pdf->Cell(22, 8, '나. 소명자료 : ', 0, 0, 'L');
	
	// 소명자료 체크박스
	$special_property_content_check = !empty($exemption2['special_property_content'] ?? '') ? '[ V]' : '[   ]';
	$pdf->Cell(7, 8, $special_property_content_check, 0, 0, 'L');
	$pdf->Cell(10, 8, '[', 0, 0, 'L');
	$pdf->Cell(10, 8, '', 0, 0, 'L');
	$pdf->Cell(20, 8, '] '.$exemption2['evidence1'].' 통', 0, 1, 'L');
	$pdf->Cell(22, 8, '', 0, 0, 'L');
	$pdf->Cell(7, 8, '[   ]', 0, 0, 'L');
	$pdf->Cell(50, 8, '기타 [             ]   통', 0, 1, 'L');
	
	$pdf->Ln(20);
	
	// 날짜
	$today = date('Y. m. d');
	$pdf->Cell(0, 8, $today, 0, 1, 'R');
	
	$pdf->Ln(10);
	
	// 서명
	$pdf->Cell(100, 8, '', 0, 0, 'L');
	$pdf->Cell(45, 8, '신 청 인(채 무 자)', 0, 0, 'L');
	$pdf->Cell(20, 8, $case_info['name'] ?? '', 0, 0, 'L');
	$pdf->Cell(25, 8, '(인)', 0, 1, 'L');
	
	$pdf->Ln(10);
	
	$pdf->Cell(0, 8, '서울회생법원 귀중', 0, 1, 'C');
	
	// 면제재산 목록 추가
	if ($exemption1_total > 0 || $exemption2_total > 0) {
		$pdf->AddPage();
		$pdf->SetFont('cid0kr', 'B', 14);
		$pdf->Cell(0, 8, '목 록', 0, 1, 'C');
		$pdf->Ln(5);
		
		$pdf->SetFont('cid0kr', '', 10);
		
		// 임차보증금 면제재산
		if ($exemption1_total > 0) {
			// 쿼리 수정: 하나의 레코드만 가져오기
			$stmt = $pdo->prepare("
				SELECT *
				FROM application_recovery_asset_exemption1
				WHERE case_no = ?
				LIMIT 1
			");
			$stmt->execute([$case_no]);
			$exemption1_item = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// 테이블 헤더 - 면제재산 금액
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(40, 10, '면제재산 금액', 1, 0, 'C');
			$pdf->Cell(140, 10, '금 ' . number_format($exemption1_total) . ' 원', 1, 1, 'L');
			
			// 주택임대차계약의 내용 타이틀
			$pdf->Cell(40, 80, '주택임대차계약의 내용', 1, 0, 'C');
			
			// 8개 항목 정보를 담을 셀 생성
			$pdf->SetFont('cid0kr', '', 10);
			
			// 계약 정보를 담을 변수
			$contract_info = '';
			
			// ① 임대차계약일자
			$contract_info .= "①임대차계약일자\t\t( " . ($exemption1_item['contract_date'] ?? '') . " )\n\n";
			
			// ② 임대차기간
			$contract_info .= "②임대차기간\t\t( " . ($exemption1_item['lease_start_date'] ?? '') . " 부터 " . ($exemption1_item['lease_end_date'] ?? '') . " 까지 )\n\n";
			
			// ③ 임차목적물의 소재지
			$contract_info .= "③임차목적물의 소재지\t( " . ($exemption1_item['lease_location'] ?? '') . " )\n\n";
			
			// ④ 임차보증금
			$contract_info .= "④임차보증금\t\t( " . number_format($exemption1_item['lease_deposit'] ?? 0) . " 원 )\n\n";
			
			// ⑤ 임료의 액수 및 연체기간
			$contract_info .= "⑤임료의 액수 및 연체기간\t(월 " . number_format($exemption1_item['rent_fee'] ?? 0) . " 원," . ($exemption1_item['overdue_months'] ?? '0') . " 개월간 연체 )\n\n";
			
			// ⑥ 임대인의 성명
			$contract_info .= "⑥임대인의 성명\t\t( " . ($exemption1_item['lessor_name'] ?? '') . " )\n\n";
			
			// ⑦ 주민등록일자
			$contract_info .= "⑦주민등록일자\t\t( " . ($exemption1_item['registration_date'] ?? '') . " )\n\n";
			
			// ⑧ 확정일자
			$contract_info .= "⑧확정일자\t\t( " . ($exemption1_item['fixed_date'] ?? '') . " 확정일자받음 )";
			
			// MultiCell로 계약 정보 출력
			$pdf->MultiCell(140, 80, $contract_info, 1, 'L');
			
			// 소명자료 체크박스
			$contract_check = ($exemption1_item['lease_contract'] ?? 'N') == 'Y' ? '[ V]' : '[  ]';
			$resident_check = ($exemption1_item['resident_registration'] ?? 'N') == 'Y' ? '[ V]' : '[  ]';
			$other_check = ($exemption1_item['other_evidence'] ?? 'N') == 'Y' ? '[ V]' : '[  ]';
			
			// 소명자료 테이블 생성
			$pdf->Cell(40, 10, '소명자료', 1, 0, 'C');
			
			// 소명자료 정보
			$evidence_info = $contract_check . " 임대차계약서 1부 / ";
			$evidence_info .= $resident_check . " 주민등록등본 1통 / ";
			$evidence_info .= $other_check . " 기타 [" . ($exemption1_item['other_evidence_detail'] ?? '') . "] 통";
			
			$pdf->MultiCell(140, 10, $evidence_info, 1, 'L');
			
			$pdf->Ln(5);
		}

		// 6개월간 생계비 면제재산
		if ($exemption2_total > 0) {
			$pdf->Cell(40, 8, '[   ]  6개월간의 생계비에 사용할 특정재산에 대한 면제재산결정 신청(법 제383조제2항 제2호)', 0, 1, 'L');
			
			$pdf->SetFont('cid0kr', '', 8);
			
			// 특정재산 테이블 헤더
			$pdf->Cell(10, 8, '순번', 1, 0, 'C', true);
			$pdf->Cell(40, 8, '특정재산의내용', 1, 0, 'C', true);
			$pdf->Cell(60, 8, '소재지', 1, 0, 'C', true);
			$pdf->Cell(30, 8, '추정시가', 1, 0, 'C', true);
			$pdf->Cell(40, 8, '면제재산결정의 사유', 1, 1, 'C', true);
			// 특정재산 테이블 내용
			$pdf->Cell(10, 8, '', 1, 0, 'C');
			$pdf->Cell(40, 8, $exemption2['special_property_content'] ?? '', 1, 0, 'L');
			$pdf->Cell(60, 8, '', 1, 0, 'C');
			$pdf->Cell(30, 8, number_format($exemption2['exemption_amount'] ?? 0) . '원', 1, 0, 'R');
			$pdf->Cell(40, 8, '', 1, 1, 'C');
			
			$pdf->Ln(5);
			
			// 소명자료
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(20, 8, '※ 소명자료:', 0, 0, 'L');
			
			$evidence1_check = !empty($exemption2['evidence1'] ?? '') ? '[ V]' : '[   ]';
			$evidence2_check = !empty($exemption2['evidence2'] ?? '') ? '[ V]' : '[   ]';
			$evidence3_check = !empty($exemption2['evidence3'] ?? '') ? '[ V]' : '[   ]';
			
			$pdf->Cell(10, 8, $evidence1_check, 0, 0, 'L');
			$pdf->Cell(30, 8, '( ' . ($exemption2['evidence1'] ?? '') . ' )보증서 1통', 0, 0, 'L');
			$pdf->Cell(10, 8, $evidence2_check, 0, 0, 'L');
			$pdf->Cell(30, 8, '사진 1장', 0, 0, 'L');
			$pdf->Cell(10, 8, $evidence3_check, 0, 0, 'L');
			$pdf->Cell(30, 8, '기타 [' . ($exemption2['evidence3'] ?? '') . '] 통', 0, 1, 'L');
		}
	}
}
?>