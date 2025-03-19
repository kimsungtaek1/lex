<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfStatements($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '진 술 서', 0, 1, 'C');
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', '', 10);

	try {
		// 1. 최종학력 조회
		$stmt = $pdo->prepare("
			SELECT school_name, graduation_date, graduation_status 
			FROM application_recovery_statement_education 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$education = $stmt->fetch(PDO::FETCH_ASSOC);

		// 2. 과거경력 조회
		$stmt = $pdo->prepare("
			SELECT work_start_date, work_end_date, company_type, 
				   business_type, company_name, position
			FROM application_recovery_statement_career 
			WHERE case_no = ? 
			ORDER BY work_start_date DESC
		");
		$stmt->execute([$case_no]);
		$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 3. 결혼/이혼 경력 조회
		$stmt = $pdo->prepare("
			SELECT marriage_date, marriage_status, spouse_name
			FROM application_recovery_statement_marriage 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$marriages = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 4. 주거상황 조회
		$stmt = $pdo->prepare("
			SELECT living_start_date, family_status, monthly_rent, 
				   rent_deposit, rent_arrears, tenant_name, owner_name, 
				   owner_relation, residence_reason
			FROM application_recovery_living_status_additional 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$housing = $stmt->fetch(PDO::FETCH_ASSOC);

		// I. 경력 섹션
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'I. 경력', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

		// 1. 최종학력
		$pdf->Cell(0, 7, "1. 최종 학력: " . 
			($education['school_name'] ?? '') . " " . 
			($education['graduation_date'] ? date('Y년 m월 d일', strtotime($education['graduation_date'])) : '') . 
			" (" . ($education['graduation_status'] ?? '') . ")", 0, 1);

		// 2. 과거 경력
		$pdf->Cell(0, 7, "2. 과거 경력 (최근 경력부터 기재)", 0, 1);
		foreach ($careers as $career) {
			$pdf->Cell(0, 7, 
				"기간: " . 
				($career['work_start_date'] ? date('Y년 m월 d일', strtotime($career['work_start_date'])) : '') . 
				" ~ " . 
				($career['work_end_date'] ? date('Y년 m월 d일', strtotime($career['work_end_date'])) : '현재') . 
				" (" . ($career['company_type'] ?? '') . ")", 0, 1);
			$pdf->Cell(0, 7, 
				"업종: " . ($career['business_type'] ?? '') . 
				", 직장명: " . ($career['company_name'] ?? '') . 
				", 직위: " . ($career['position'] ?? ''), 0, 1);
		}

		// 3. 과거 결혼, 이혼 경력
		$pdf->Cell(0, 7, "3. 과거 결혼, 이혼 경력", 0, 1);
		foreach ($marriages as $marriage) {
			$pdf->Cell(0, 7, 
				date('Y년 m월 d일', strtotime($marriage['marriage_date'])) . 
				" " . ($marriage['spouse_name'] ?? '') . 
				"와 " . ($marriage['marriage_status'] ?? ''), 0, 1);
		}

		// II. 현재 주거 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'II. 현재 주거 상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);

		$pdf->Cell(0, 7, "거주를 시작한 시점: " . 
			($housing['living_start_date'] ? date('Y년 m월 d일', strtotime($housing['living_start_date'])) : ''), 0, 1);

		$pdf->Cell(0, 7, "거주 상황: " . ($housing['family_status'] ?? ''), 0, 1);
		$pdf->Cell(0, 7, "월세: " . number_format($housing['monthly_rent'] ?? 0) . "원", 0, 1);
		$pdf->Cell(0, 7, "보증금: " . number_format($housing['rent_deposit'] ?? 0) . "원", 0, 1);
		$pdf->Cell(0, 7, "연체액: " . number_format($housing['rent_arrears'] ?? 0) . "원", 0, 1);
		$pdf->Cell(0, 7, "임차인 성명: " . ($housing['tenant_name'] ?? ''), 0, 1);
		$pdf->Cell(0, 7, "소유자 성명: " . ($housing['owner_name'] ?? ''), 0, 1);
		$pdf->Cell(0, 7, "소유자와의 관계: " . ($housing['owner_relation'] ?? ''), 0, 1);

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