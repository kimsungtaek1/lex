<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfApplication($pdf, $pdo, $case_no) {
	// 새 페이지 추가
	$pdf->AddPage();
	
	// 문서 제목
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '개인회생 신청서', 0, 1, 'C');
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
		$pdf->Ln(3);
		
		// 신청인 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '신청인 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 이름, 주민등록번호, 연락처
		$pdf->Cell(25, 10, '성명', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['name'] ?? '', 1, 0, 'L');
		$pdf->Cell(25, 10, '주민등록번호', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['resident_number'] ?? '', 1, 1, 'L');
		
		$pdf->Cell(25, 10, '전화번호', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['phone'] ?? '', 1, 0, 'L');
		$pdf->Cell(25, 10, '신청일', 1, 0, 'C');
		$pdf->Cell(65, 10, $basic_info['application_date'] ?? '', 1, 1, 'L');
		
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
		
		$pdf->Ln(3);
		
		// 채무 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '채무 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 채무 총액
		$pdf->Cell(35, 10, '채무 총액', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($basic_info['debt_total'] ?? 0) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '자산 총액', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($basic_info['assets_total'] ?? 0) . '원', 1, 1, 'R');
		
		// 채권자 수 조회
		$stmt = $pdo->prepare("
			SELECT COUNT(*) as count FROM application_recovery_creditor 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$creditor_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
		
		$pdf->Cell(35, 10, '채권자 수', 1, 0, 'C');
		$pdf->Cell(40, 10, $creditor_count . '명', 1, 0, 'R');
		$pdf->Cell(35, 10, '채권자 목록', 1, 0, 'C');
		$pdf->Cell(40, 10, '별첨 참조', 1, 1, 'R');
		
		$pdf->Ln(3);
		
		// 소득 및 지출 정보
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '소득 및 지출 정보', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 월 소득 및 지출
		$pdf->Cell(35, 10, '월 소득', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($basic_info['income_monthly'] ?? 0) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '월 지출', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($basic_info['expense_monthly'] ?? 0) . '원', 1, 1, 'R');
		
		// 계산된 월 가용소득
		$disposable_income = ($basic_info['income_monthly'] ?? 0) - ($basic_info['expense_monthly'] ?? 0);
		$pdf->Cell(35, 10, '월 가용소득', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($disposable_income) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '월 변제금', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($basic_info['repayment_monthly'] ?? 0) . '원', 1, 1, 'R');
		
		$pdf->Ln(3);
		
		// 변제 계획
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '변제 계획', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 변제 기간 및 시작일
		$pdf->Cell(35, 10, '변제 기간', 1, 0, 'C');
		$pdf->Cell(40, 10, '36개월', 1, 0, 'R');
		$pdf->Cell(35, 10, '변제 개시일', 1, 0, 'C');
		$pdf->Cell(40, 10, $basic_info['repayment_start_date'] ?? '', 1, 1, 'R');
		
		// 총 변제금
		$total_repayment = ($basic_info['repayment_monthly'] ?? 0) * 36;
		$pdf->Cell(35, 10, '총 변제금', 1, 0, 'C');
		$pdf->Cell(40, 10, number_format($total_repayment) . '원', 1, 0, 'R');
		$pdf->Cell(35, 10, '입금 계좌', 1, 0, 'C');
		$account_info = ($basic_info['bank_name'] ?? '') . ' ' . ($basic_info['account_number'] ?? '');
		$pdf->Cell(40, 10, $account_info, 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// 변제계획 상세 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_plan10 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$plan = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($plan) {
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, '변제계획 상세', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->MultiCell(0, 10, $plan['content'] ?? '', 1, 'L');
		}
		
		$pdf->Ln(10);
		
		// 신청인 서명 및 날짜
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '위와 같이 개인회생을 신청합니다.', 0, 1, 'C');
		$pdf->Ln(10);
		
		// 현재 날짜
		$current_date = date('Y년 m월 d일');
		$pdf->Cell(0, 10, $current_date, 0, 1, 'R');
		$pdf->Ln(5);
		
		// 신청인 서명
		$pdf->Cell(130, 10, '', 0, 0, 'L');
		$pdf->Cell(30, 10, '신청인:', 0, 0, 'L');
		$pdf->Cell(30, 10, $basic_info['name'] ?? '', 0, 1, 'L');
		
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