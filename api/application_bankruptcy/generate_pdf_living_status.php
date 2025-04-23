<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfLivingStatus($pdf, $pdo, $case_no) {
	// PDF 기본 설정
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
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '현재의 생활상황', 0, 1, 'C');
	$pdf->Ln(5);
	
	// 1. 현재의 직업
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(100, 8, '1. 현재의 직업 【   ' . ($living_basic['job_type'] ?? '') . '   】', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(30, 8, '업종 또는 직업(', 0, 0, 'L');
	$pdf->Cell(50, 8, $living_basic['job_industry'] ?? '', 0, 0, 'L');
	$pdf->Cell(30, 8, ')직장 또는 회사명(', 0, 0, 'R');
	$pdf->Cell(50, 8, $living_basic['company_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	$pdf->Cell(15, 8, '지 위(', 0, 0, 'L');
	$pdf->Cell(65, 8, $living_basic['job_position'] ?? '', 0, 0, 'L');
	$pdf->Cell(15, 8, ')취 직 시 기(', 0, 0, 'R');
	$pdf->Cell(65, 8, $living_basic['employment_period'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	$pdf->Ln(5);
	
	// 2. 수입의 상황
	$monthly_total = (int)($living_income['self_income'] ?? 0) + 
					 (int)($living_income['monthly_salary'] ?? 0) + 
					 (int)($living_income['pension'] ?? 0) + 
					 (int)($living_income['living_support'] ?? 0) + 
					 (int)($living_income['other_income'] ?? 0);
	
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '2. 수입의 상황(이 사건 신청일이 속한 달의 직전 달인 2025년 03월 기준으로 신청인의 월', 0, 1, 'L');
	$pdf->Cell(0, 8, '수입 합계           ' . number_format($monthly_total) . '원)', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(20, 8, '자영수입 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_income['self_income'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(60, 8, ') → 종합소득세 확정신고서(최근 2년분)를 첨부하여 주십시오.', 0, 1, 'L');
	
	$pdf->Cell(20, 8, '월 급여 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_income['monthly_salary'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(120, 8, ') → 근어증명서(최근 2년분)와 근로소득세 원천징수영수증의 사본을 첨부하여', 0, 1, 'L');
	$pdf->Cell(0, 8, '주십시오.', 0, 1, 'L');
	
	$pdf->Cell(20, 8, '연    금 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_income['pension'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(45, 8, ') → 수급증명서를 첨부하여 주십시오.', 0, 1, 'L');
	
	$pdf->Cell(20, 8, '생활보조 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_income['living_support'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(45, 8, ') → 수급증명서를 첨부하여 주십시오.', 0, 1, 'L');
	
	$pdf->Cell(20, 8, '기    타 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_income['other_income'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(120, 8, ') → 구체적으로 기재하고 수입원을 나타내는 자료를 첨부하여 주십시오.', 0, 1, 'L');
	$pdf->Ln(5);
	
	// 3. 동거하는 가족의 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '3. 동거하는 가족의 상황(월수입 부분은 이 사건 신청일이 속한 달의 직전 달인 2025년 03월', 0, 1, 'L');
	$pdf->Cell(0, 8, '기준)', 0, 1, 'L');
	
	// 가족 테이블 헤더
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(40, 8, '성명', 1, 0, 'C');
	$pdf->Cell(40, 8, '신청인과의 관계', 1, 0, 'C');
	$pdf->Cell(40, 8, '연령', 1, 0, 'C');
	$pdf->Cell(40, 8, '직업', 1, 0, 'C');
	$pdf->Cell(40, 8, '월수입', 1, 1, 'C');
	
	// 가족 목록
	$pdf->SetFont('cid0kr', '', 10);
	if (count($family_members) > 0) {
		foreach ($family_members as $member) {
			$pdf->Cell(40, 8, $member['name'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 8, $member['relation'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 8, $member['age'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 8, $member['job'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 8, number_format($member['income'] ?? 0) . ' 원', 1, 1, 'R');
		}
	} else {
		$pdf->Cell(200, 8, '등록된 가족 정보가 없습니다.', 1, 1, 'C');
	}
	$pdf->Ln(5);
	
	// 4. 주거의 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '4. 주거의 상황', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(40, 8, '거주를 시작한 시점(', 0, 0, 'L');
	$pdf->Cell(50, 8, $living_additional['living_start_date'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 가구관계 설정
	$family_status = $living_additional['family_status'] ?? '';
	$pdf->Cell(40, 8, '가구관계 : 아래 ① - ⑤중선택 (', 0, 0, 'L');
	$pdf->Cell(5, 8, $family_status ? '①' : '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '●', 0, 0, 'L');
	$pdf->Cell(0, 8, '임대 주택(신청인 이외의 자가 임차한 경우 표함)', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '①', 0, 0, 'L');
	$pdf->Cell(0, 8, '사택 또는 기숙사', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '②', 0, 0, 'L');
	$pdf->Cell(0, 8, '신청인 소유의 주택', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '③', 0, 0, 'L');
	$pdf->Cell(0, 8, '친척 소유의 주택에 무상으로 거주', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '④', 0, 0, 'L');
	$pdf->Cell(0, 8, '친척 이외의 자 소유의 주택에 무상으로 거주', 0, 1, 'L');
	
	$pdf->Cell(10, 8, '⑤', 0, 0, 'L');
	$pdf->Cell(30, 8, '기타 (', 0, 0, 'L');
	$pdf->Cell(130, 8, $living_additional['family_status_etc'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->Cell(20, 8, '①, ②항을 선택한 분에 대하여,', 0, 1, 'L');
	
	$pdf->Cell(25, 8, '월 차임 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_additional['monthly_rent'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(25, 8, ') 임대보증금 (', 0, 0, 'L');
	$pdf->Cell(30, 8, number_format($living_additional['rent_deposit'] ?? 0) . '원', 0, 0, 'R');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	$pdf->Cell(25, 8, '연체액', 0, 0, 'L');
	$pdf->Cell(30, 8, '(' . number_format($living_additional['rent_arrears'] ?? 0) . '원)', 0, 1, 'L');
	
	$pdf->Cell(50, 8, '신청인 이외의 자가 임차인인 경우 임차인 성명(', 0, 0, 'L');
	$pdf->Cell(60, 8, $living_additional['tenant_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(30, 8, ') 신청인과의 관계(', 0, 0, 'L');
	$pdf->Cell(30, 8, $living_additional['tenant_relation'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->Cell(20, 8, '③, ④항을 선택한 분에 대하여,', 0, 1, 'L');
	
	$pdf->Cell(30, 8, '소유자 성명 (', 0, 0, 'L');
	$pdf->Cell(60, 8, $living_additional['owner_name'] ?? '', 0, 0, 'L');
	$pdf->Cell(30, 8, ') 신청인과의 관계(', 0, 0, 'L');
	$pdf->Cell(30, 8, $living_additional['owner_relation'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	$pdf->MultiCell(0, 8, '신청인 이외의 자가 소유자이거나 임차인인데 함께 거주하지 않는 경우 그 경위를 기재하십시오.', 0, 'L');
	$pdf->Cell(5, 8, '(', 0, 0, 'L');
	$pdf->Cell(160, 8, $living_additional['residence_reason'] ?? '', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	$pdf->Ln(5);
	
	// 5. 조세 등 공과금의 납부 상황
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 8, '5. 조세 등 공과금의 납부 상황(체납 조세가 있는 경우 세목 및 미납액을 기재하십시오)', 0, 1, 'L');
	$pdf->Ln(2);
	
	$pdf->SetFont('cid0kr', '', 10);
	// 소득세
	$pdf->Cell(20, 8, '소득세', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_income = $living_tax['income_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_income === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['income_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 주민세
	$pdf->Cell(20, 8, '주민세', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_residence = $living_tax['residence_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_residence === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['residence_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 재산세
	$pdf->Cell(20, 8, '재산세', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_property = $living_tax['property_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_property === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['property_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 의료보험료
	$pdf->Cell(20, 8, '의료보험료', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_health = $living_tax['health_insurance_status'] ?? '';
	$pdf->Cell(20, 8, $selected_health === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['health_insurance_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 국민연금
	$pdf->Cell(20, 8, '국민연금', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_pension = $living_tax['pension_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_pension === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['pension_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 자동차세
	$pdf->Cell(20, 8, '자동차세', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_car = $living_tax['car_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_car === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['car_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 기타 세금
	$pdf->Cell(20, 8, '기타 세금', 0, 0, 'L');
	$pdf->Cell(25, 8, '미납분 (', 0, 0, 'L');
	$selected_other = $living_tax['other_tax_status'] ?? '';
	$pdf->Cell(20, 8, $selected_other === '있음' ? '있음' : '', 0, 0, 'C');
	$pdf->Cell(10, 8, '- ' . number_format($living_tax['other_tax_amount'] ?? 0), 0, 0, 'L');
	$pdf->Cell(20, 8, '원', 0, 0, 'L');
	$pdf->Cell(5, 8, ')', 0, 1, 'L');
	
	// 하단 서명
	$pdf->Ln(20);
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '위 기재 내용은 사실과 다름이 없습니다.', 0, 1, 'C');
	$pdf->Ln(5);
	$pdf->Cell(0, 10, date('Y년 m월 d일'), 0, 1, 'R');
	$pdf->Cell(0, 10, '신청인: ' . ($basic_info['name'] ?? '') . ' (인)', 0, 1, 'R');
	
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, ($basic_info['court_name'] ?? '') . ' 귀중', 0, 1, 'C');
}
?>