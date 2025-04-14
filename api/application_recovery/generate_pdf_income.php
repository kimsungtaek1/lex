<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfIncome($pdf, $pdo, $case_no) {
	// 새 페이지 추가
	$pdf->AddPage();
	
	// 폰트 및 스타일 설정
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '수입 및 지출에 관한 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 기본 회생 신청 정보 조회
		$stmt = $pdo->prepare("
			SELECT ar.*, cm.name, cm.case_number 
			FROM application_recovery ar
			JOIN case_management cm ON ar.case_no = cm.case_no
			WHERE ar.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$basic_info) {
			$pdf->Cell(0, 10, '사건 정보를 찾을 수 없습니다.', 0, 1, 'C');
			return;
		}

		// I. 현재의 수입 목록
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'I. 현재의 수입목록', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Cell(0, 5, '(단위 : 원)', 0, 1, 'R');

		// 급여 소득 정보
		$salary_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_salary 
			WHERE case_no = ?
		");
		$salary_stmt->execute([$case_no]);
		$salary_info = $salary_stmt->fetch(PDO::FETCH_ASSOC);

		// 사업 소득 정보
		$business_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_business 
			WHERE case_no = ?
		");
		$business_stmt->execute([$case_no]);
		$business_info = $business_stmt->fetch(PDO::FETCH_ASSOC);

		// 월급여 소득 계산 정보
		$salary_calc_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_salary_calculation
			WHERE case_no = ? ORDER BY id DESC LIMIT 1
		");
		$salary_calc_stmt->execute([$case_no]);
		$salary_calc = $salary_calc_stmt->fetch(PDO::FETCH_ASSOC);

		// 월급여 계산 행 정보
		$salary_rows_stmt = null;
		$income_rows = [];
		$deduction_rows = [];
		
		if ($salary_calc) {
			$salary_rows_stmt = $pdo->prepare("
				SELECT * FROM application_recovery_salary_calculation_rows
				WHERE calculation_id = ? ORDER BY row_type, row_order
			");
			$salary_rows_stmt->execute([$salary_calc['id']]);
			
			while ($row = $salary_rows_stmt->fetch(PDO::FETCH_ASSOC)) {
				if ($row['row_type'] == 'income') {
					$income_rows[] = $row;
				} else {
					$deduction_rows[] = $row;
				}
			}
		}

		// 수입 목록 테이블 설정
		$col_width1 = 25; // 수입상황
		$col_width2 = 40; // 자영(상호)
		$col_width3 = 40; // 고용(직장명)
		$col_width4 = 75; // 연간 환산 금액 및 압류 유무
		
		// 수입 목록 테이블 헤더
		$pdf->SetFillColor(240, 240, 240);
		$pdf->Cell($col_width1, 10, '수입상황', 1, 0, 'C', true);
		$pdf->Cell($col_width2, 10, '자영(상호)', 1, 0, 'C', true);
		$pdf->Cell($col_width3, 10, '고용(직장명)', 1, 0, 'C', true);
		$pdf->Cell($col_width4, 10, '연간 환산 금액 및 압류 유무', 1, 1, 'C', true);
		
		// 두 번째 행 (업종, 직위)
		$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
		$pdf->Cell($col_width2, 10, '업종', 1, 0, 'C');
		$pdf->Cell($col_width3, 10, '직위', 1, 0, 'C');
		$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
		
		// 세 번째 행 (종사경력, 근무기간)
		$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
		$pdf->Cell($col_width2, 10, '종사경력', 1, 0, 'C');
		$pdf->Cell($col_width3, 10, '근무기간', 1, 0, 'C');
		$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
		
		// 네 번째 행 (명목, 기간구분, 금액, 연간환산금액)
		$pdf->Cell($col_width1, 10, '명목', 1, 0, 'C');
		$pdf->Cell($col_width2 / 2, 10, '기간구분', 1, 0, 'C');
		$pdf->Cell($col_width2 / 2, 10, '금액', 1, 0, 'C');
		$pdf->Cell($col_width3, 10, '연간환산금액', 1, 0, 'C');
		$pdf->Cell($col_width4, 10, '압류, 가압류 등 유무', 1, 1, 'C');

		// 급여 소득 데이터
		$pdf->SetFont('cid0kr', '', 8);
		if ($salary_info) {
			$pdf->Cell($col_width1, 10, '급여소득', 1, 0, 'C');
			$pdf->Cell($col_width2 / 2, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2 / 2, 10, number_format($salary_info['monthly_income']), 1, 0, 'R');
			$pdf->Cell($col_width3, 10, number_format($salary_info['yearly_income']), 1, 0, 'R');
			$pdf->Cell($col_width4, 10, $salary_info['is_seized'] == 'Y' ? '유' : '무', 1, 1, 'C');
			
			// 자영(업종) - 빈칸
			$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width3, 10, $salary_info['company_name'] ?? '', 1, 0, 'C');
			$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
			
			// 종사경력, 근무기간
			$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width3, 10, $salary_info['work_period'] ?? '', 1, 0, 'C');
			$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
		}

		// 사업 소득 데이터
		if ($business_info) {
			$pdf->Cell($col_width1, 10, $business_info['type'] ?? '사업소득', 1, 0, 'C');
			$pdf->Cell($col_width2 / 2, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2 / 2, 10, number_format($business_info['monthly_income']), 1, 0, 'R');
			$pdf->Cell($col_width3, 10, number_format($business_info['yearly_income']), 1, 0, 'R');
			$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
			
			// 자영업 정보
			$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2, 10, $business_info['sector'] ?? '', 1, 0, 'C');
			$pdf->Cell($col_width3, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
			
			// 종사경력
			$pdf->Cell($col_width1, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width2, 10, $business_info['career'] ?? '', 1, 0, 'C');
			$pdf->Cell($col_width3, 10, '', 1, 0, 'C');
			$pdf->Cell($col_width4, 10, '', 1, 1, 'C');
		}
		// 연 수입, 월 평균 소득 요약
		$total_yearly = ($salary_info['yearly_income'] ?? 0) + ($business_info['yearly_income'] ?? 0);
		$total_monthly = ($salary_info['monthly_income'] ?? 0) + ($business_info['monthly_income'] ?? 0);
		
		$pdf->Cell(0, 10, '연 수입 '.number_format($total_yearly).'원     월 평균소득 '.number_format($total_monthly).'원', 0, 1, 'R');
		$pdf->Ln(5);

		// II. 변제계획 수행시의 예상지출목록
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'II. 변제계획 수행시의 예상지출목록', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Cell(0, 5, '(해당란에 [ v] 표시)', 0, 1, 'R');

		// 소득 지출 정보
		$income_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_expenditure 
			WHERE case_no = ?
		");
		$income_stmt->execute([$case_no]);
		$income_info = $income_stmt->fetch(PDO::FETCH_ASSOC);

		// 생계비 정보
		$living_expenses_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_living_expenses 
			WHERE case_no = ? ORDER BY expense_no
		");
		$living_expenses_stmt->execute([$case_no]);
		$living_expenses = $living_expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

		// 가족 구성원 수 확인
		$family_stmt = $pdo->prepare("
			SELECT COUNT(*) as count FROM application_recovery_family_members 
			WHERE case_no = ?
		");
		$family_stmt->execute([$case_no]);
		$family_count = $family_stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1; // 본인 포함
		
		$household_size = $income_info['household_size'] ?? $family_count;
		$living_expense_type = $income_info['living_expense_type'] ?? 'Y';
		$living_expense_amount = $income_info['living_expense_amount'] ?? 0;
		$income_percentage = $income_info['income_percentage'] ?? 0;

		// 생계비 표시 - 60% 이하인 경우
		$pdf->SetFont('cid0kr', '', 10);
		$checkbox1 = ($living_expense_type == 'Y' || $living_expense_type == '') ? '[ v]' : '[  ]';
		$checkbox2 = ($living_expense_type == 'N') ? '[ v]' : '[  ]';
		
		$pdf->Cell(5, 10, $checkbox1, 0, 0, 'C');
		$pdf->Cell(0, 10, '채무자가 예상하는 생계비가 보건복지부 공표 기준 중위소득의 100분의 60 이하인 경우', 0, 1, 'L');
		
		// 중위소득 관련 데이터
		$median_income_stmt = $pdo->prepare("
			SELECT standard_amount FROM application_income_living_expense_standard
			WHERE year = ? AND family_members = ?
		");
		
		$current_year = date('Y');
		$median_income_stmt->execute([$current_year, $household_size]);
		$median_income = $median_income_stmt->fetch(PDO::FETCH_ASSOC);
		$median_income_amount = $median_income['standard_amount'] ?? 0;
		
		// 생계비 계산 표시
		$pdf->Cell(10, 10, '', 0, 0, 'L');
		$pdf->Cell(0, 10, '보건복지부 공표 ( '.$household_size.' )인 가구 기준 중위 소득 ( '.number_format($median_income_amount).' )원의 약 ( '.$income_percentage.' )%인 ( '.number_format($living_expense_amount).' )원을 지출할', 0, 1, 'L');
		$pdf->Cell(10, 10, '', 0, 0, 'L');
		$pdf->Cell(0, 10, '것으로 예상됩니다.', 0, 1, 'L');
		
		// 생계비 표시 - 60% 초과인 경우
		$pdf->Cell(5, 10, $checkbox2, 0, 0, 'C');
		$pdf->Cell(0, 10, '채무자가 예상하는 생계비가 보건복지부 공표 기준 중위소득의 100분의 60을 초과하는 경우', 0, 1, 'L');
		
		if ($living_expense_type == 'N') {
			$pdf->Cell(10, 10, '', 0, 0, 'L');
			$pdf->Cell(0, 10, '보건복지부 공표 ( '.$household_size.' )인 가구 기준 중위 소득 ( '.number_format($median_income_amount).' )원의 약 ( '.$income_percentage.' )%인 ( '.number_format($living_expense_amount).' )원을 지출할 것으로', 0, 1, 'L');
			$pdf->Cell(10, 10, '', 0, 0, 'L');
			$pdf->Cell(0, 10, '예상됩니다.(뒷면 표의 내역과 사유를 상세히 기재하십시오)', 0, 1, 'L');
		} else {
			$pdf->Cell(10, 10, '', 0, 0, 'L');
			$pdf->Cell(0, 10, '보건복지부 공표 (    )인 가구 기준 중위 소득 (           )원의 약 (    )%인 (           )원을 지출할 것으로', 0, 1, 'L');
			$pdf->Cell(10, 10, '', 0, 0, 'L');
			$pdf->Cell(0, 10, '예상됩니다.(뒷면 표의 내역과 사유를 상세히 기재하십시오)', 0, 1, 'L');
		}

		// III. 가족관계
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Ln(5);
		$pdf->Cell(0, 10, 'III. 가족관계', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 8);

		// 가족 구성원 정보
		$family_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_family_members 
			WHERE case_no = ? ORDER BY member_no
		");
		$family_stmt->execute([$case_no]);
		$family_members = $family_stmt->fetchAll(PDO::FETCH_ASSOC);

		// 가족 관계 테이블
		$pdf->SetFillColor(240, 240, 240);
		$pdf->Cell(15, 10, '관계', 1, 0, 'C', true);
		$pdf->Cell(20, 10, '성명', 1, 0, 'C', true);
		$pdf->Cell(15, 10, '연령', 1, 0, 'C', true);
		$pdf->Cell(40, 10, '동거여부 및 기간', 1, 0, 'C', true);
		$pdf->Cell(20, 10, '직업', 1, 0, 'C', true);
		$pdf->Cell(25, 10, '월 수입', 1, 0, 'C', true);
		$pdf->Cell(25, 10, '재산총액', 1, 0, 'C', true);
		$pdf->Cell(20, 10, '부양유무', 1, 1, 'C', true);

		foreach ($family_members as $member) {
			$live_together = $member['live_together'] == 'Y' ? '동거' : '별거';
			$live_period = $member['live_period'] ? $member['live_period'] : '';
			$live_info = $live_together . ($live_period ? ' ' . $live_period : '');
			
			$pdf->Cell(15, 10, $member['relation'] ?? '', 1, 0, 'C');
			$pdf->Cell(20, 10, $member['name'] ?? '', 1, 0, 'C');
			$pdf->Cell(15, 10, $member['age'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 10, $live_info, 1, 0, 'C');
			$pdf->Cell(20, 10, $member['job'] ?? '', 1, 0, 'C');
			$pdf->Cell(25, 10, $member['income'] > 0 ? number_format($member['income']).'원' : '', 1, 0, 'R');
			$pdf->Cell(25, 10, $member['assets'] > 0 ? number_format($member['assets']).'원' : '', 1, 0, 'R');
			$pdf->Cell(20, 10, $member['support'] == 'Y' ? '유' : '무', 1, 1, 'C');
		}
		
		// 생계비 초과 지출 내역 - 새 페이지에 표시 (가족관계 다음 페이지)
		if ($living_expense_type == 'N' && !empty($living_expenses)) {
			$pdf->AddPage();
			$pdf->SetFont('cid0kr', 'B', 14);
			$pdf->Cell(0, 10, '채무자가 예상하는 생계비 지출 내역', 0, 1, 'C');
			$pdf->Ln(5);
			
			// 기준 중위소득 계산 정보 표시
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '1. 기준 중위소득 정보', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 10);
			
			$pdf->Cell(100, 10, '가구원 수: '.$household_size.'명', 0, 0, 'L');
			$pdf->Cell(0, 10, '기준 중위소득: '.number_format($median_income_amount).'원', 0, 1, 'L');
			$pdf->Cell(100, 10, '생계비 비율: '.$income_percentage.'%', 0, 0, 'L');
			$pdf->Cell(0, 10, '생계비 금액: '.number_format($living_expense_amount).'원', 0, 1, 'L');
			$pdf->Ln(5);
			
			// 생계비 항목별 내역 테이블
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '2. 생계비 항목별 내역', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 10);
			
			// 테이블 헤더
			$pdf->SetFillColor(240, 240, 240);
			$pdf->Cell(50, 10, '비 목', 1, 0, 'C', true);
			$pdf->Cell(40, 10, '지출 예상 금액', 1, 0, 'C', true);
			$pdf->Cell(90, 10, '추가 지출 사유', 1, 1, 'C', true);
			// 생계비 내역 데이터
			$total_expense = 0;
			foreach ($living_expenses as $expense) {
				$amount = $expense['amount'] ?? 0;
				$total_expense += $amount;
				
				$pdf->Cell(50, 10, $expense['type'] ?? '', 1, 0, 'L');
				$pdf->Cell(40, 10, number_format($amount).'원', 1, 0, 'R');
				
				// 추가 지출 사유 (내용이 길 경우 여러 줄로 표시)
				$reason = $expense['reason'] ?? '';
				if (mb_strlen($reason, 'UTF-8') > 60) {
					$pdf->MultiCell(90, 10, $reason, 1, 'L');
				} else {
					$pdf->Cell(90, 10, $reason, 1, 1, 'L');
				}
			}
			
			// 합계 행
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(50, 10, '합계', 1, 0, 'C');
			$pdf->Cell(40, 10, number_format($total_expense).'원', 1, 0, 'R');
			$pdf->Cell(90, 10, '', 1, 1, 'C');
			
			$pdf->Ln(5);
			
			// 생계비 초과 지출 사유 섹션
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '3. 생계비 초과 지출 종합 사유', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 10);
			
			// 종합 사유 조회
			$expense_reason_stmt = $pdo->prepare("
				SELECT additional_note FROM application_recovery_living_expenses 
				WHERE case_no = ?
			");
			$expense_reason_stmt->execute([$case_no]);
			$expense_reason_info = $expense_reason_stmt->fetch(PDO::FETCH_ASSOC);
			
			$additional_note = $expense_reason_info['additional_note'] ?? '';
			
			// 종합 사유 표시
			$pdf->MultiCell(0, 10, $additional_note, 1, 'L');
			
			$pdf->Ln(5);
			
			// 관련 법규 및 안내 사항
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 6, '■ 관련 법규: 개인회생규칙 제74조(변제계획의 내용) 제3항', 0, 1, 'L');
			$pdf->MultiCell(0, 6, '※ 채무자가 제출한 예상 생계비가 보건복지부 공표 기준 중위소득의 100분의 60을 초과하는 경우에는 초과 사유와 내역을 객관적 자료로 증명하여야 합니다.', 0, 'L');
			$pdf->MultiCell(0, 6, '※ 초과 생계비에 대한 증빙자료를 첨부하여 주시기 바랍니다.', 0, 'L');
		}
		
		// 월 평균 소득 계산 내역 - 가로 방향으로 변경
		if (!empty($income_rows) || !empty($deduction_rows)) {
			// 가로 방향 페이지 추가
			$pdf->AddPage('L');
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '월 평균소득 계산내역', 0, 1, 'C');
			$pdf->SetFont('cid0kr', '', 8);
			
			// 계산 기간 정보
			if ($salary_calc) {
				$year = $salary_calc['year'] ?? date('Y');
				// 계산 기간 표시
				$pdf->Cell(20, 10, '( '.$year.' 년', 0, 0, 'R');
				
				// 계산에 사용된 월 계산
				$usedMonths = 0;
				for ($i=1; $i<=12; $i++) {
					if (isset($income_rows[0]['month'.$i]) && $income_rows[0]['month'.$i] > 0) {
						$usedMonths++;
					}
				}
				
				// 시작월
				$start_month = 0;
				for ($i=1; $i<=12; $i++) {
					if (isset($income_rows[0]['month'.$i]) && $income_rows[0]['month'.$i] > 0) {
						$start_month = $i;
						break;
					}
				}
				
				// 종료월
				$end_month = 0;
				for ($i=12; $i>=1; $i--) {
					if (isset($income_rows[0]['month'.$i]) && $income_rows[0]['month'.$i] > 0) {
						$end_month = $i;
						break;
					}
				}
				
				if ($start_month > 0 && $end_month > 0) {
					$pdf->Cell(30, 10, ' '.$start_month.' 월부터 ~ '.$year.' 년 '.$end_month.'월까지)', 0, 1, 'L');
				} else {
					$pdf->Cell(30, 10, ')', 0, 1, 'L');
				}
			} else {
				$pdf->Cell(0, 10, '( ______ 년 _____ 월부터 ~ ______ 년 _____ 월까지)', 0, 1, 'C');
			}
			
			// 가로 방향에 맞게 셀 너비 조정
			$leftCol_width = 30; // 지급내역 열 너비
			$month_width = 17; // 월별 열 너비
			$total_width = 30; // 합계 열 너비
			
			// A 소득 헤더
			$pdf->SetFont('cid0kr', 'B', 8);
			$pdf->Cell(10, 10, '■ A 소득', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			
			// 소득 테이블 헤더
			$pdf->Cell($leftCol_width, 10, '지급내역', 1, 0, 'C');
			
			for ($i=1; $i<=12; $i++) {
				$pdf->Cell($month_width, 10, $i.'월', 1, 0, 'C');
			}
			
			$pdf->Cell($total_width, 10, '합계', 1, 1, 'C');
			
			// 소득 데이터 행
			$total_income = 0;
			foreach ($income_rows as $row) {
				$pdf->Cell($leftCol_width, 10, $row['row_name'], 1, 0, 'L');
				$row_total = 0;
				for ($i=1; $i<=12; $i++) {
					$month_key = 'month'.$i;
					$month_value = $row[$month_key] ?? 0;
					$row_total += $month_value;
					
					$pdf->Cell($month_width, 10, $month_value > 0 ? number_format($month_value) : '', 1, 0, 'R');
				}
				
				$pdf->Cell($total_width, 10, number_format($row_total), 1, 1, 'R');
				$total_income += $row_total;
			}
			
			// 소득 합계
			$pdf->Cell($leftCol_width, 10, '소득합계', 1, 0, 'L');
			
			$monthly_income_totals = array_fill(1, 12, 0);
			
			// 각 월별 소득 합계 계산
			foreach ($income_rows as $row) {
				for ($i=1; $i<=12; $i++) {
					$month_key = 'month'.$i;
					$monthly_income_totals[$i] += $row[$month_key] ?? 0;
				}
			}
			
			// 월별 소득 합계 출력
			for ($i=1; $i<=12; $i++) {
				$pdf->Cell($month_width, 10, $monthly_income_totals[$i] > 0 ? number_format($monthly_income_totals[$i]) : '', 1, 0, 'R');
			}
			
			$pdf->Cell($total_width, 10, number_format($total_income), 1, 1, 'R');
			
			// B 공제 헤더
			$pdf->SetFont('cid0kr', 'B', 8);
			$pdf->Cell(10, 10, '■ B 공제', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			
			// 공제 테이블 헤더
			$pdf->Cell($leftCol_width, 10, '지급내역', 1, 0, 'C');
			
			for ($i=1; $i<=12; $i++) {
				$pdf->Cell($month_width, 10, $i.'월', 1, 0, 'C');
			}
			
			$pdf->Cell($total_width, 10, '합계', 1, 1, 'C');
			
			// 공제 데이터 행
			$total_deduction = 0;
			foreach ($deduction_rows as $row) {
				$pdf->Cell($leftCol_width, 10, $row['row_name'], 1, 0, 'L');
				
				$row_total = 0;
				for ($i=1; $i<=12; $i++) {
					$month_key = 'month'.$i;
					$month_value = $row[$month_key] ?? 0;
					$row_total += $month_value;
					
					$pdf->Cell($month_width, 10, $month_value > 0 ? number_format($month_value) : '', 1, 0, 'R');
				}
				
				$pdf->Cell($total_width, 10, number_format($row_total), 1, 1, 'R');
				$total_deduction += $row_total;
			}
			
			// 공제 합계
			$pdf->Cell($leftCol_width, 10, '공제합계', 1, 0, 'L');
			
			$monthly_deduction_totals = array_fill(1, 12, 0);
			
			// 각 월별 공제 합계 계산
			foreach ($deduction_rows as $row) {
				for ($i=1; $i<=12; $i++) {
					$month_key = 'month'.$i;
					$monthly_deduction_totals[$i] += $row[$month_key] ?? 0;
				}
			}
			
			// 월별 공제 합계 출력
			for ($i=1; $i<=12; $i++) {
				$pdf->Cell($month_width, 10, $monthly_deduction_totals[$i] > 0 ? number_format($monthly_deduction_totals[$i]) : '', 1, 0, 'R');
			}
			
			$pdf->Cell($total_width, 10, number_format($total_deduction), 1, 1, 'R');
			
			// 실수령액 행
			$pdf->Cell($leftCol_width, 10, '실수령액', 1, 0, 'L');
			
			$monthly_net_totals = array_fill(1, 12, 0);
			$net_total = 0;
			
			// 각 월별 실수령액 계산
			for ($i=1; $i<=12; $i++) {
				$monthly_net_totals[$i] = $monthly_income_totals[$i] - $monthly_deduction_totals[$i];
				$net_total += $monthly_net_totals[$i];
			}
			
			// 월별 실수령액 출력
			for ($i=1; $i<=12; $i++) {
				$pdf->Cell($month_width, 10, $monthly_net_totals[$i] > 0 ? number_format($monthly_net_totals[$i]) : '', 1, 0, 'R');
			}
			
			$pdf->Cell($total_width, 10, number_format($net_total), 1, 1, 'R');
			
			$pdf->Ln(5);
			
			// 요약 행 - 가로 형식에 맞게 재조정
			// 연 소득 요약
			$yearly_income = $total_income;
			$yearly_deduction = $total_deduction;
			$yearly_net = $net_total;
			
			// 계산에 사용된 월수
			$used_months_count = 0;
			for ($i=1; $i<=12; $i++) {
				if ($monthly_income_totals[$i] > 0) {
					$used_months_count++;
				}
			}
			
			// 월평균 소득 계산
			$monthly_average = 0;
			if ($used_months_count > 0) {
				$monthly_average = round($net_total / $used_months_count);
			}
			
			// 연간 환산 금액
			$annual_amount = $monthly_average * 12;
			
			// 요약 테이블을 가로로 배치
			$summary_col_width = 65;
			
			$pdf->Cell($summary_col_width, 10, '연 소득총액(A)', 1, 0, 'L');
			$pdf->Cell($summary_col_width, 10, number_format($yearly_income), 1, 0, 'R');
			$pdf->Cell($summary_col_width, 10, '연 공제총액(B)', 1, 0, 'L');
			$pdf->Cell($summary_col_width, 10, number_format($yearly_deduction), 1, 1, 'R');
			
			$pdf->Cell($summary_col_width, 10, '연 실수령액(A-B)', 1, 0, 'L');
			$pdf->Cell($summary_col_width, 10, number_format($yearly_net), 1, 0, 'R');
			$pdf->Cell($summary_col_width, 10, '월평균소득금액', 1, 0, 'L');
			$pdf->Cell($summary_col_width, 10, number_format($monthly_average), 1, 1, 'R');
			
			$pdf->Cell(0, 10, '연간환산금액     '.number_format($annual_amount), 1, 1, 'R');
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