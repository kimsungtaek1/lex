<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfIncome($pdf, $pdo, $case_no) {
	// 기본 정보 조회
	try {
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
		
		// 수입 지출 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_income_expenditure
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$income_data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 가족 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_living_status_family
			WHERE case_no = ?
			ORDER BY member_id
		");
		$stmt->execute([$case_no]);
		$family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 수입 지출 목록 페이지 생성
		$pdf->AddPage();
		
		// 제목
		$pdf->SetFont('cid0kr', 'B', 16);
		$pdf->Cell(0, 10, '수입 및 지출에 관한 목록', 0, 1, 'C');
		
		// 신청인 정보
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(20, 10, '신청인:', 0, 0);
		$pdf->Cell(80, 10, $basic_info['name'], 0, 0);
		
		$pdf->Cell(20, 10, '사건번호:', 0, 0);
		$pdf->Cell(80, 10, $basic_info['case_number'] ?? '', 0, 1);
		
		// 가족 구성원 정보 출력
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '가족 구성원 정보', 0, 1, 'L');
		
		if (count($family_members) > 0) {
			// 테이블 헤더
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(10, 10, '번호', 1, 0, 'C');
			$pdf->Cell(30, 10, '이름', 1, 0, 'C');
			$pdf->Cell(30, 10, '관계', 1, 0, 'C');
			$pdf->Cell(20, 10, '나이', 1, 0, 'C');
			$pdf->Cell(40, 10, '직업', 1, 0, 'C');
			$pdf->Cell(50, 10, '월 소득', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 9);
			$total_family_income = 0;
			
			foreach ($family_members as $index => $member) {
				$pdf->Cell(10, 10, ($index + 1), 1, 0, 'C');
				$pdf->Cell(30, 10, $member['name'] ?? '', 1, 0, 'L');
				$pdf->Cell(30, 10, $member['relation'] ?? '', 1, 0, 'L');
				$pdf->Cell(20, 10, $member['age'] ?? '', 1, 0, 'C');
				$pdf->Cell(40, 10, $member['job'] ?? '', 1, 0, 'L');
				
				// 소득 금액 포맷팅
				$income = !empty($member['income']) ? (int)$member['income'] : 0;
				$total_family_income += $income;
				
				$pdf->Cell(50, 10, number_format($income) . '원', 1, 1, 'R');
			}
			
			// 합계 출력
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(130, 10, '가족 소득 합계', 1, 0, 'R');
			$pdf->Cell(50, 10, number_format($total_family_income) . '원', 1, 1, 'R');
		} else {
			$pdf->Cell(0, 10, '등록된 가족 구성원 정보가 없습니다.', 1, 1, 'C');
		}
		
		// 수입 지출 내역 출력
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '수입 및 지출 내역', 0, 1, 'L');
		
		if (!empty($income_data)) {
			// 수입 내역
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '1. 수입 내역', 0, 1, 'L');
			
			$pdf->SetFont('cid0kr', '', 9);
			$income_items = [
				['임금 수입(본인)', 'income_salary_applicant'],
				['임금 수입(배우자)', 'income_salary_spouse'],
				['임금 수입(기타 가족)', 'income_salary_others'],
				['연금 수입(본인)', 'income_pension_applicant'],
				['연금 수입(배우자)', 'income_pension_spouse'],
				['연금 수입(기타 가족)', 'income_pension_others'],
				['지원금', 'income_support'],
				['기타 수입', 'income_others']
			];
			
			$pdf->Cell(90, 10, '항목', 1, 0, 'C');
			$pdf->Cell(90, 10, '금액', 1, 1, 'C');
			
			foreach ($income_items as $item) {
				[$label, $field] = $item;
				$amount = !empty($income_data[$field]) ? (int)$income_data[$field] : 0;
				
				$pdf->Cell(90, 10, $label, 1, 0, 'L');
				$pdf->Cell(90, 10, number_format($amount) . '원', 1, 1, 'R');
			}
			
			// 수입 합계
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(90, 10, '수입 합계', 1, 0, 'R');
			$pdf->Cell(90, 10, number_format($income_data['income_total'] ?? 0) . '원', 1, 1, 'R');
			
			// 지출 내역
			$pdf->Ln(5);
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '2. 지출 내역', 0, 1, 'L');
			
			$pdf->SetFont('cid0kr', '', 9);
			$expense_items = [
				['주거비', 'expense_housing'],
				['식비', 'expense_food'],
				['교육비', 'expense_education'],
				['공과금', 'expense_utilities'],
				['교통비', 'expense_transportation'],
				['통신비', 'expense_communication'],
				['의료비', 'expense_medical'],
				['보험료', 'expense_insurance'],
				['기타 지출', 'expense_others']
			];
			
			$pdf->Cell(90, 10, '항목', 1, 0, 'C');
			$pdf->Cell(90, 10, '금액', 1, 1, 'C');
			
			foreach ($expense_items as $item) {
				[$label, $field] = $item;
				$amount = !empty($income_data[$field]) ? (int)$income_data[$field] : 0;
				
				$pdf->Cell(90, 10, $label, 1, 0, 'L');
				$pdf->Cell(90, 10, number_format($amount) . '원', 1, 1, 'R');
			}
			
			// 지출 합계
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(90, 10, '지출 합계', 1, 0, 'R');
			$pdf->Cell(90, 10, number_format($income_data['expense_total'] ?? 0) . '원', 1, 1, 'R');
			
			// 가용 소득 계산
			$pdf->Ln(5);
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '3. 가용 소득 (월 소득 - 월 지출)', 0, 1, 'L');
			
			$disposable_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);
			
			$pdf->Cell(90, 10, '월 가용 소득', 1, 0, 'L');
			$pdf->Cell(90, 10, number_format($disposable_income) . '원', 1, 1, 'R');
		} else {
			$pdf->Cell(0, 10, '등록된 수입 및 지출 정보가 없습니다.', 1, 1, 'C');
		}
		
		// 하단 서명
		$pdf->Ln(10);
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(0, 10, '위 기재 내용은 사실과 다름이 없습니다.', 0, 1, 'C');
		$pdf->Ln(5);
		$pdf->Cell(0, 10, date('Y년 m월 d일'), 0, 1, 'R');
		$pdf->Cell(0, 10, '신청인: ' . $basic_info['name'] . ' (인)', 0, 1, 'R');
		
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
		
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