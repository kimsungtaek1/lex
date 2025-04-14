<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfApplication($pdf, $pdo, $case_no) {
	// A4 용지에 맞게 여백 설정
	$pdf->SetMargins(15, 15, 15);
	$pdf->SetAutoPageBreak(true, 15);
	
	// 새 페이지 추가
	$pdf->AddPage();
	
	// 문서 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '개인회생절차개시신청서', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 기본 정보 조회
		$stmt = $pdo->prepare("
			SELECT ar.*, cm.name, cm.case_number, cm.court_name 
			FROM application_recovery ar
			JOIN case_management cm ON ar.case_no = cm.case_no
			WHERE ar.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 법원 및 사건 정보
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(25, 10, '관할법원', 1, 0, 'C');
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(65, 10, $basic_info['court_name'] ?? '', 1, 0, 'L');
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(25, 10, '사건번호', 1, 0, 'C');
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(65, 10, $basic_info['case_number'] ?? '', 1, 1, 'L');
		$pdf->Ln(5);
		
		// 신청인 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '신청인 (채무자)', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 이름, 주민등록번호, 연락처
		$pdf->Cell(25, 10, '성명', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['name'] ?? '', 1, 0, 'L');
		$pdf->Cell(25, 10, '주민등록번호', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['resident_number'] ?? '', 1, 1, 'L');
		
		$pdf->Cell(25, 10, '전화번호', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['phone'] ?? '', 1, 0, 'L');
		$pdf->Cell(25, 10, '이메일', 1, 0, 'C');
		$pdf->Cell(65, 10, '', 1, 1, 'L'); // 이메일 정보가 없어 빈칸으로 처리
		
		// 주소
		$pdf->Cell(25, 10, '등록주소', 1, 0, 'C');
		$pdf->Cell(155, 10, $basic_info['registered_address'] ?? '', 1, 1, 'L');
		
		$pdf->Cell(25, 10, '현재주소', 1, 0, 'C');
		$pdf->Cell(155, 10, $basic_info['now_address'] ?? '', 1, 1, 'L');
		
		// 직장 정보 (소득 유형에 따라 다르게 표시)
		$is_company = $basic_info['is_company'] ?? 0;
		if ($is_company == 1) {
			$pdf->Cell(25, 10, '상호', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['workplace'] ?? '', 1, 0, 'L');
			$pdf->Cell(25, 10, '업종', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['position'] ?? '', 1, 1, 'L');
			
			$pdf->Cell(25, 10, '종사경력', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['work_period'] ?? '', 1, 0, 'L');
			$pdf->Cell(25, 10, '영업장주소', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['work_address'] ?? '', 1, 1, 'L');
		} else {
			$pdf->Cell(25, 10, '직장명', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['workplace'] ?? '', 1, 0, 'L');
			$pdf->Cell(25, 10, '직위', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['position'] ?? '', 1, 1, 'L');
			
			$pdf->Cell(25, 10, '근무기간', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['work_period'] ?? '', 1, 0, 'L');
			$pdf->Cell(25, 10, '직장주소', 1, 0, 'C');
			$pdf->Cell(65, 10, $basic_info['work_address'] ?? '', 1, 1, 'L');
		}
		
		$pdf->Ln(5);
		
		// 대리인 정보 (있는 경우)
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '대리인', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 대리인 정보 조회 (추가 필요시)
		$pdf->Cell(25, 10, '성명', 1, 0, 'C');
		$pdf->Cell(65, 10, '', 1, 0, 'L');
		$pdf->Cell(25, 10, '주소', 1, 0, 'C');
		$pdf->Cell(65, 10, '', 1, 1, 'L');
		
		$pdf->Cell(25, 10, '전화번호', 1, 0, 'C');
		$pdf->Cell(65, 10, '', 1, 0, 'L');
		$pdf->Cell(25, 10, '이메일', 1, 0, 'C');
		$pdf->Cell(65, 10, '', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// 신청 취지
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '신청취지', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		$application_purpose = "신청인에 대하여 개인회생절차개시결정을 하여 주시고, 변제계획의 인가를 구합니다.";
		$pdf->MultiCell(0, 10, $application_purpose, 1, 'L');
		
		$pdf->Ln(5);
		
		// 신청 원인
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '신청원인', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 신청 원인 정보 조회
		// 파산 원인 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_bankruptcy_reason
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$bankruptcy_reason = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 기본 신청 원인
		$reason_text = "신청인은 현재 채무의 변제가 곤란한 상태에 놓여 주채무자로서 개인회생 개시를 신청합니다.";
		
		if ($bankruptcy_reason && isset($bankruptcy_reason['reasons']) && !empty($bankruptcy_reason['reasons'])) {
			$reason_text .= "\n\n" . $bankruptcy_reason['reasons'];
		}
		
		if ($bankruptcy_reason && isset($bankruptcy_reason['detail']) && !empty($bankruptcy_reason['detail'])) {
			$reason_text .= "\n\n" . $bankruptcy_reason['detail'];
		}
		
		$pdf->MultiCell(0, 10, $reason_text, 1, 'L');
		
		$pdf->Ln(5);
		
		// 가족 구성원 및 수입 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '가족 및 수입 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 가족 구성원 정보 조회
		$stmt = $pdo->prepare("
			SELECT COUNT(*) as count FROM application_recovery_family_members 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$family_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
		
		// 수입 정보 조회
		$stmt = $pdo->prepare("
			SELECT SUM(monthly_income) as total FROM application_recovery_income_salary 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$salary_income = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		$stmt = $pdo->prepare("
			SELECT SUM(monthly_income) as total FROM application_recovery_income_business 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business_income = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		$total_income = $salary_income + $business_income;
		
		// 가족 구성원 및 수입 정보 표시
		$pdf->Cell(35, 10, '가족 구성원', 1, 0, 'C');
		$pdf->Cell(40, 10, ($family_count + 1) . '명 (본인 포함)', 1, 0, 'L'); // 본인 포함
		$pdf->Cell(35, 10, '월 수입', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($total_income) . '원', 1, 1, 'R');
		
		$pdf->Ln(5);
		
		// 채무 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '채무 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 채무 총액 조회
		$stmt = $pdo->prepare("
			SELECT SUM(principal + interest) as total_debt,
			       COUNT(*) as creditor_count
			FROM application_recovery_creditor 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$debt_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$total_debt = $debt_info['total_debt'] ?? 0;
		$creditor_count = $debt_info['creditor_count'] ?? 0;
		
		// 채무 정보 표시
		$pdf->Cell(35, 10, '채무 총액', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($total_debt) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '채권자 수', 1, 0, 'C');
		$pdf->Cell(40, 10, $creditor_count . '명', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// 자산 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '자산 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 총 재산 조회
		// 현금
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		// 예금
		$stmt = $pdo->prepare("
			SELECT SUM(deposit_amount) as total FROM application_recovery_asset_deposits 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$deposit_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		// 부동산
		$stmt = $pdo->prepare("
			SELECT SUM(property_liquidation_value) as total FROM application_recovery_asset_real_estate 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estate_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		// 자동차
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total FROM application_recovery_asset_vehicles 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicle_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		// 기타 자산
		$stmt = $pdo->prepare("
			SELECT SUM(liquidation_value) as total FROM application_recovery_asset_other 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$other_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
		
		// 총 자산 계산
		$total_assets = $cash_total + $deposit_total + $real_estate_total + $vehicle_total + $other_total;
		
		// 자산 정보 표시
		$pdf->Cell(35, 10, '총 자산', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($total_assets) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '자산 목록', 1, 0, 'C');
		$pdf->Cell(40, 10, '별첨 참조', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// 변제 계획
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '변제 계획', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 변제 관련 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_expenditure 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$income_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 변제 기간 계산 (기본 3년)
		$repayment_period = 36; // 개월 수
		
		// 가용소득 계산
		$disposable_income = $income_info['disposable_income'] ?? 0;
		
		// 총 변제금 계산
		$total_repayment = $disposable_income * $repayment_period;
		
		// 변제 계획 정보 표시
		$pdf->Cell(35, 10, '변제 기간', 1, 0, 'C');
		$pdf->Cell(40, 10, $repayment_period . '개월', 1, 0, 'L');
		$pdf->Cell(35, 10, '월 변제금', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($disposable_income) . '원', 1, 1, 'R');
		
		$pdf->Cell(35, 10, '총 변제금', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($total_repayment) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '변제 시작일', 1, 0, 'C');
		$pdf->Cell(40, 10, $basic_info['repayment_start_date'] ?? '', 1, 1, 'L');
		
		// 입금 계좌 정보
		$pdf->Cell(35, 10, '입금 은행', 1, 0, 'C');
		$pdf->Cell(40, 10, $basic_info['bank_name'] ?? '', 1, 0, 'L');
		$pdf->Cell(35, 10, '계좌번호', 1, 0, 'C');
		$pdf->Cell(40, 10, $basic_info['account_number'] ?? '', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// 변제계획안 상세 내용
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '변제계획안 상세', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 변제계획안 상세 내용 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_plan10 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$plan_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 변제계획안 내용 표시
		$plan_content = $plan_info['content'] ?? "채무자의 가용소득을 최대한 활용하여 변제하겠습니다.";
		$pdf->MultiCell(0, 10, $plan_content, 1, 'L');
		
		$pdf->Ln(10);
		
		// 첨부 서류 목록
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '첨부서류 목록', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 첨부 서류 목록 표시
		$pdf->Cell(10, 8, '1.', 0, 0, 'L');
		$pdf->Cell(0, 8, '개인회생채권자목록 1통 (채권자수 + 3부)', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '2.', 0, 0, 'L');
		$pdf->Cell(0, 8, '재산목록 1통 (채권자수 + 3부)', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '3.', 0, 0, 'L');
		$pdf->Cell(0, 8, '수입 및 지출에 관한 목록 1통 (채권자수 + 3부)', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '4.', 0, 0, 'L');
		$pdf->Cell(0, 8, '진술서(별지 제39호 서식) 1통', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '5.', 0, 0, 'L');
		$pdf->Cell(0, 8, '채무자 소득증빙서류(급여명세서 등) 1통', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '6.', 0, 0, 'L');
		$pdf->Cell(0, 8, '주민등록등본, 주민등록초본(주소변동이력 포함) 각 1통', 0, 1, 'L');
		
		$pdf->Cell(10, 8, '7.', 0, 0, 'L');
		$pdf->Cell(0, 8, '가족관계증명서(상세) 1통', 0, 1, 'L');
		
		$pdf->Ln(10);
		
		// 서명 및 날짜
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '위와 같이 개인회생절차개시를 신청합니다.', 0, 1, 'C');
		$pdf->Ln(10);
		
		// 날짜
		$current_date = '';
		if (isset($basic_info['application_date']) && !empty($basic_info['application_date'])) {
			$date_parts = explode('-', $basic_info['application_date']);
			if (count($date_parts) == 3) {
				$current_date = $date_parts[0] . '년 ' . $date_parts[1] . '월 ' . $date_parts[2] . '일';
			}
		} else {
			$current_date = date('Y년 m월 d일');
		}
		
		$pdf->Cell(0, 10, $current_date, 0, 1, 'R');
		$pdf->Ln(5);
		
		// 신청인 및 대리인 서명
		$pdf->Cell(115, 10, '', 0, 0, 'L');
		$pdf->Cell(35, 10, '신청인', 0, 0, 'L');
		$pdf->Cell(30, 10, $basic_info['name'] ?? '', 0, 1, 'L');
		
		$pdf->Cell(115, 10, '', 0, 0, 'L');
		$pdf->Cell(35, 10, '대리인', 0, 0, 'L');
		$pdf->Cell(30, 10, '', 0, 1, 'L');
		
		$pdf->Ln(10);
		
		// 법원 표시
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