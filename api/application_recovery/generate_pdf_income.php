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
	$pdf->SetFont('cid0kr', '', 10);

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
		$pdf->Cell(0, 10, 'I. 현재의 수입 목록', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

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

		// 수입 목록 테이블 헤더
		$pdf->SetFillColor(240, 240, 240);
		$pdf->Cell(40, 10, '수입 상황', 1, 0, 'C', true);
		$pdf->Cell(40, 10, '자영(상호)', 1, 0, 'C', true);
		$pdf->Cell(40, 10, '고용(직장명)', 1, 0, 'C', true);
		$pdf->Cell(70, 10, '연간 환산 금액 및 압류 유무', 1, 1, 'C', true);

		// 급여 소득 데이터
		if ($salary_info) {
			$pdf->Cell(40, 10, '급여 소득', 1, 0, 'C');
			$pdf->Cell(40, 10, '-', 1, 0, 'C');
			$pdf->Cell(40, 10, $salary_info['company_name'] ?? '-', 1, 0, 'C');
			$pdf->Cell(70, 10, '연간 ' . number_format($salary_info['yearly_income']) . '원 (압류: ' . $salary_info['is_seized'] . ')', 1, 1, 'C');
		}

		// 사업 소득 데이터
		if ($business_info) {
			$pdf->Cell(40, 10, '사업 소득', 1, 0, 'C');
			$pdf->Cell(40, 10, $business_info['business_name'] ?? '-', 1, 0, 'C');
			$pdf->Cell(40, 10, '-', 1, 0, 'C');
			$pdf->Cell(70, 10, '연간 ' . number_format($business_info['yearly_income']) . '원', 1, 1, 'C');
		}

		// II. 변제 계획 수행 시의 예상 지출 목록
		$pdf->Ln(10);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'II. 변제 계획 수행 시의 예상 지출 목록', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

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
			WHERE case_no = ?
		");
		$living_expenses_stmt->execute([$case_no]);
		$living_expenses = $living_expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

		// 가족 구성원 수 확인
		$household_size = $income_info['household_size'] ?? 1;
		$living_expense_amount = $income_info['living_expense_amount'] ?? 0;
		$living_expense_range = $living_expense_amount > 0 ? '초과' : '이하';

		$pdf->Cell(0, 10, '[  ] 채무자가 예상하는 생계비가 보건복지부 공표 기준 중위소득의 100분의 60 ' . $living_expense_range . '인 경우', 0, 1, 'L');
		$pdf->Cell(0, 10, '가구 기준 중위소득 ' . number_format($living_expense_amount) . '원의 약 ' . ($income_info['income_percentage'] ?? 0) . '%', 0, 1, 'L');

		// III. 가족관계
		$pdf->Ln(10);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'III. 가족관계', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

		// 가족 구성원 정보
		$family_stmt = $pdo->prepare("
			SELECT * FROM application_recovery_family_members 
			WHERE case_no = ?
		");
		$family_stmt->execute([$case_no]);
		$family_members = $family_stmt->fetchAll(PDO::FETCH_ASSOC);

		// 가족 관계 테이블
		$pdf->SetFillColor(240, 240, 240);
		$pdf->Cell(25, 10, '관계', 1, 0, 'C', true);
		$pdf->Cell(25, 10, '성명', 1, 0, 'C', true);
		$pdf->Cell(20, 10, '연령', 1, 0, 'C', true);
		$pdf->Cell(40, 10, '동거 여부 및 기간', 1, 0, 'C', true);
		$pdf->Cell(25, 10, '직업', 1, 0, 'C', true);
		$pdf->Cell(25, 10, '월 수입', 1, 0, 'C', true);
		$pdf->Cell(30, 10, '재산 총액', 1, 0, 'C', true);
		$pdf->Cell(20, 10, '부양 유무', 1, 1, 'C', true);

		foreach ($family_members as $member) {
			$pdf->Cell(25, 10, $member['relation'] ?? '', 1, 0, 'C');
			$pdf->Cell(25, 10, $member['name'] ?? '', 1, 0, 'C');
			$pdf->Cell(20, 10, $member['age'] ?? '', 1, 0, 'C');
			$pdf->Cell(40, 10, $member['live_period'] ?? '', 1, 0, 'C');
			$pdf->Cell(25, 10, $member['job'] ?? '', 1, 0, 'C');
			$pdf->Cell(25, 10, number_format($member['income'] ?? 0), 1, 0, 'R');
			$pdf->Cell(30, 10, number_format($member['assets'] ?? 0), 1, 0, 'R');
			$pdf->Cell(20, 10, $member['support'] ?? '', 1, 1, 'C');
		}

		// 생계비 추가 지출 정보
		$pdf->Ln(10);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '생계비 지출 내역', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

		$pdf->SetFillColor(240, 240, 240);
		$pdf->Cell(50, 10, '비 목', 1, 0, 'C', true);
		$pdf->Cell(50, 10, '지출 예상 생계비', 1, 0, 'C', true);
		$pdf->Cell(90, 10, '추가 지출 사유', 1, 1, 'C', true);

		foreach ($living_expenses as $expense) {
			$pdf->Cell(50, 10, $expense['type'], 1, 0, 'C');
			$pdf->Cell(50, 10, number_format($expense['amount']), 1, 0, 'R');
			$pdf->Cell(90, 10, $expense['reason'], 1, 1, 'L');
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