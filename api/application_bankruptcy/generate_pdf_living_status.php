<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfLivingStatus($pdf, $pdo, $case_no) {
	// PDF 기본 설정 - A4 용지에 맞게 여백 조정
	$pdf->SetMargins(15, 15, 15); // 좌, 상, 우 여백 설정
	$pdf->SetAutoPageBreak(true, 15); // 하단 여백 15mm 설정
	
	// 기본 정보 조회
	try {
		// 파산 신청인의 기본 정보
		$stmt = $pdo->prepare("
			SELECT ab.*, cm.name, cm.case_number, cm.court_name
			FROM application_bankruptcy ab
			JOIN case_management cm ON ab.case_no = cm.case_no
			WHERE ab.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 생활상황 정보 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_living_status_basic
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$living_basic = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 수입 정보 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_living_status_income
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$living_income = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 세금 정보 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_living_status_tax
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$living_tax = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 가족 정보 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_living_status_family
			WHERE case_no = ?
			ORDER BY member_id
		");
		$stmt->execute([$case_no]);
		$family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 추가 정보 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_living_status_additional
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$living_additional = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 생활상황 정보 페이지 생성
		generateLivingStatusPage($pdf, $basic_info, $living_basic, $living_income, $living_tax, $family_members, $living_additional);
		
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

function generateLivingStatusPage($pdf, $basic_info, $living_basic, $living_income, $living_tax, $family_members, $living_additional) {
	// 첫 페이지 추가
	$pdf->AddPage();
	
	// 유효 페이지 너비 계산 (A4 너비 - 좌우 여백)
	$pageWidth = $pdf->getPageWidth() - 30;
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '현재의 생활상황', 0, 1, 'C');
	$pdf->Ln(3);
	
	// 1. 현재의 직업
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '1. 현재의 직업 【   ' . ($living_basic['job_type'] ?? '') . '   】', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->SetFont('cid0kr', '', 10);
	
	// 업종/직업과 직장/회사명 행
	$pdf->Cell(35, 7, '업종 또는 직업(', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_basic['job_industry'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(30, 7, '직장 또는 회사명(', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_basic['company_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 지위와 취직시기 행
	$pdf->Cell(20, 7, '지 위(', 0, 0, 'L');
	$pdf->Cell(65, 7, $living_basic['job_position'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(25, 7, '취 직 시 기(', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_basic['employment_period'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	$pdf->Ln(3);
	
	// 2. 수입의 상황
	$monthly_total = (int)($living_income['self_income'] ?? 0) + 
					 (int)($living_income['monthly_salary'] ?? 0) + 
					 (int)($living_income['pension'] ?? 0) + 
					 (int)($living_income['living_support'] ?? 0) + 
					 (int)($living_income['other_income'] ?? 0);
	
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '2. 수입의 상황(이 사건 신청일이 속한 달의 직전 달인 2025년 03월 기준으로 신청인의 월', 0, 1, 'L');
	$pdf->Cell(0, 8, '수입 합계          ' . number_format($monthly_total) . '원)', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->SetFont('cid0kr', '', 10);
	
	// 자영수입 행
	$pdf->Cell(25, 7, '자영수입 (', 0, 0, 'L');
	$pdf->Cell(35, 7, number_format($living_income['self_income'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(0, 7, ' → 종합소득세 확정신고서(최근 2년분)를 첨부하여 주십시오.', 0, 1, 'L');
	
	// 월급여 행 (길이가 길어 두 줄로 나눔)
	$pdf->Cell(25, 7, '월 급여 (', 0, 0, 'L');
	$pdf->Cell(35, 7, number_format($living_income['monthly_salary'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(0, 7, ' → 근어증명서(최근 2년분)와 근로소득세 원천징수영수증의 사본을 첨부하여', 0, 1, 'L');
	$pdf->Cell(65, 7, '주십시오.', 0, 1, 'L');
	
	// 연금 행
	$pdf->Cell(25, 7, '연    금 (', 0, 0, 'L');
	$pdf->Cell(35, 7, number_format($living_income['pension'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(0, 7, ' → 수급증명서를 첨부하여 주십시오.', 0, 1, 'L');
	
	// 생활보조 행
	$pdf->Cell(25, 7, '생활보조 (', 0, 0, 'L');
	$pdf->Cell(35, 7, number_format($living_income['living_support'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(0, 7, ' → 수급증명서를 첨부하여 주십시오.', 0, 1, 'L');
	
	// 기타 행
	$pdf->Cell(25, 7, '기    타 (', 0, 0, 'L');
	$pdf->Cell(35, 7, number_format($living_income['other_income'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 0, 'L');
	$pdf->Cell(0, 7, ' → 구체적으로 기재하고 수입원을 나타내는 자료를 첨부하여 주십시오.', 0, 1, 'L');
	$pdf->Ln(3);
	
	// 3. 동거하는 가족의 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '3. 동거하는 가족의 상황(월수입 부분은 이 사건 신청일이 속한 달의 직전 달인 2025년 03월', 0, 1, 'L');
	$pdf->Cell(0, 8, '기준)', 0, 1, 'L');
	
	// 가족 테이블 헤더 - 열 너비 균등하게 조정
	$columnWidth = $pageWidth / 5; // 5개의 열로 나누기
	
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell($columnWidth, 8, '성명', 1, 0, 'C');
	$pdf->Cell($columnWidth, 8, '신청인과의 관계', 1, 0, 'C');
	$pdf->Cell($columnWidth, 8, '연령', 1, 0, 'C');
	$pdf->Cell($columnWidth, 8, '직업', 1, 0, 'C');
	$pdf->Cell($columnWidth, 8, '월수입', 1, 1, 'C');
	
	// 가족 목록
	$pdf->SetFont('cid0kr', '', 10);
	if (count($family_members) > 0) {
		foreach ($family_members as $member) {
			$pdf->Cell($columnWidth, 8, $member['name'] ?? '', 1, 0, 'C');
			$pdf->Cell($columnWidth, 8, $member['relation'] ?? '', 1, 0, 'C');
			$pdf->Cell($columnWidth, 8, $member['age'] ?? '', 1, 0, 'C');
			$pdf->Cell($columnWidth, 8, $member['job'] ?? '', 1, 0, 'C');
			$pdf->Cell($columnWidth, 8, number_format($member['income'] ?? 0) . ' 원', 1, 1, 'R');
		}
	} else {
		$pdf->Cell($pageWidth, 8, '등록된 가족 정보가 없습니다.', 1, 1, 'C');
	}
	$pdf->Ln(3);
	
	// 4. 주거의 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '4. 주거의 상황', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(40, 7, '거주를 시작한 시점(', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_additional['living_start_date'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 가구관계 설정
	$family_status = $living_additional['family_status'] ?? '';
	$pdf->Cell(40, 7, '가구관계 : 아래 ① - ⑤중선택 (', 0, 0, 'L');
	$pdf->Cell(5, 7, $family_status === '임대주택' ? '①' : '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '●', 0, 0, 'L');
	$pdf->Cell(0, 7, '임대 주택(신청인 이외의 자가 임차한 경우 표함)', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '①', 0, 0, 'L');
	$pdf->Cell(0, 7, '사택 또는 기숙사', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '②', 0, 0, 'L');
	$pdf->Cell(0, 7, '신청인 소유의 주택', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '③', 0, 0, 'L');
	$pdf->Cell(0, 7, '친척 소유의 주택에 무상으로 거주', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '④', 0, 0, 'L');
	$pdf->Cell(0, 7, '친척 이외의 자 소유의 주택에 무상으로 거주', 0, 1, 'L');
	
	$pdf->Cell(10, 7, '⑤', 0, 0, 'L');
	$pdf->Cell(25, 7, '기타 (', 0, 0, 'L');
	$pdf->Cell(130, 7, $living_additional['family_status_etc'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->Cell(0, 7, '①, ②항을 선택한 분에 대하여,', 0, 1, 'L');
	
	$pdf->Cell(25, 7, '월 차임 (', 0, 0, 'L');
	$pdf->Cell(30, 7, number_format($living_additional['monthly_rent'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(20, 7, ') 임대보증금 (', 0, 0, 'L');
	$pdf->Cell(30, 7, number_format($living_additional['rent_deposit'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	$pdf->Cell(25, 7, '연체액', 0, 0, 'L');
	$pdf->Cell(30, 7, '(' . number_format($living_additional['rent_arrears'] ?? 0) . '원)', 0, 1, 'L');
	
	// 신청인 이외의 자가 임차인인 경우 - 한 줄이 너무 길어 여러 줄로 나눔
	$pdf->Cell(50, 7, '신청인 이외의 자가 임차인인 경우 임차인 성명(', 0, 0, 'L');
	$pdf->Cell(45, 7, $living_additional['tenant_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	$pdf->Cell(25, 7, '신청인과의 관계(', 0, 0, 'L');
	$pdf->Cell(45, 7, $living_additional['tenant_relation'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->Cell(0, 7, '③, ④항을 선택한 분에 대하여,', 0, 1, 'L');
	
	$pdf->Cell(30, 7, '소유자 성명 (', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_additional['owner_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	$pdf->Cell(30, 7, '신청인과의 관계(', 0, 0, 'L');
	$pdf->Cell(50, 7, $living_additional['owner_relation'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	$pdf->MultiCell(0, 7, '신청인 이외의 자가 소유자이거나 임차인인데 함께 거주하지 않는 경우 그 경위를 기재하십시오.', 0, 'L');
	$pdf->Cell(5, 7, '(', 0, 0, 'L');
	
	// 긴 텍스트는 MultiCell로 처리하여 자동 줄바꿈
	$x = $pdf->GetX();
	$y = $pdf->GetY();
	$width = $pageWidth - 10; // 여백 고려
	$pdf->MultiCell($width, 7, $living_additional['residence_reason'] ?? '', 0, 'L');
	$pdf->SetXY($x + $width, $y);
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	$pdf->Ln(3);
	
	// 내용이 많아 페이지를 넘길 수 있으므로 페이지 확인
	if ($pdf->GetY() > 230) {
		$pdf->AddPage();
	}
	
	// 5. 조세 등 공과금의 납부 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '5. 조세 등 공과금의 납부 상황(체납 조세가 있는 경우 세목 및 미납액을 기재하십시오)', 0, 1, 'L');
	$pdf->Ln(1);
	
	$pdf->SetFont('cid0kr', '', 10);
	// 소득세
	$pdf->Cell(20, 7, '소득세', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_income = $living_tax['income_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_income === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['income_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 주민세
	$pdf->Cell(20, 7, '주민세', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_residence = $living_tax['residence_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_residence === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['residence_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 재산세
	$pdf->Cell(20, 7, '재산세', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_property = $living_tax['property_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_property === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['property_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 의료보험료
	$pdf->Cell(20, 7, '의료보험료', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_health = $living_tax['health_insurance_status'] ?? '';
	$pdf->Cell(20, 7, $selected_health === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['health_insurance_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 국민연금
	$pdf->Cell(20, 7, '국민연금', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_pension = $living_tax['pension_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_pension === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['pension_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 자동차세
	$pdf->Cell(20, 7, '자동차세', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_car = $living_tax['car_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_car === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['car_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 기타 세금
	$pdf->Cell(20, 7, '기타 세금', 0, 0, 'L');
	$pdf->Cell(25, 7, '미납분 (', 0, 0, 'L');
	$selected_other = $living_tax['other_tax_status'] ?? '';
	$pdf->Cell(20, 7, $selected_other === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 7, '- ' . number_format($living_tax['other_tax_amount'] ?? 0), 0, 0, 'R');
	$pdf->Cell(5, 7, '원', 0, 0, 'R');
	$pdf->Cell(5, 7, ')', 0, 1, 'L');
	
	// 하단 서명 - 남은 공간 확인
	$remainingHeight = $pdf->getPageHeight() - $pdf->GetY() - 30; // 30mm는 안전 여백
	
	if ($remainingHeight < 40) { // 서명을 위한 최소 공간 (mm)
		$pdf->AddPage();
	}
	
	$pdf->Ln(15);
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 7, '위 기재 내용은 사실과 다름이 없습니다.', 0, 1, 'C');
	$pdf->Ln(5);
	$pdf->Cell(0, 7, date('Y년 m월 d일'), 0, 1, 'R');
	$pdf->Cell(0, 7, '신청인: ' . ($basic_info['name'] ?? '') . ' (인)', 0, 1, 'R');
	
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 7, ($basic_info['court_name'] ?? '') . ' 귀중', 0, 1, 'C');
}
?>