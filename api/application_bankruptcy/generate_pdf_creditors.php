<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfCreditors($pdf, $pdo, $case_no) {
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
		
		// 채권자 목록 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_creditor
			WHERE case_no = ?
			ORDER BY creditor_count
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 채권자 목록 페이지 생성
		generateCreditorList($pdf, $basic_info, $creditors);
		
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

function generateCreditorList($pdf, $basic_info, $creditors) {
	// 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '채권자목록', 0, 1, 'C');
	
	// 신청인 정보
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(20, 10, '신청인:', 0, 0);
	$pdf->Cell(80, 10, $basic_info['name'], 0, 0);
	
	$pdf->Cell(20, 10, '사건번호:', 0, 0);
	$pdf->Cell(80, 10, $basic_info['case_number'] ?? '', 0, 1);
	
	$pdf->Cell(20, 10, '채권자수:', 0, 0);
	$pdf->Cell(80, 10, count($creditors) . '명', 0, 0);
	
	$pdf->Cell(20, 10, '총 채무액:', 0, 0);
	
	// 총 채무액 계산
	$total_debt = 0;
	foreach($creditors as $creditor) {
		$total_debt += (int)($creditor['initial_claim'] ?? 0);
	}
	
	$pdf->Cell(80, 10, number_format($total_debt) . '원', 0, 1);
	
	$pdf->Ln(5);
	
	// 채권자 목록 테이블 헤더
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->Cell(10, 10, '번호', 1, 0, 'C');
	$pdf->Cell(50, 10, '금융기관명', 1, 0, 'C');
	$pdf->Cell(30, 10, '연락처', 1, 0, 'C');
	$pdf->Cell(30, 10, '차용일자', 1, 0, 'C');
	$pdf->Cell(30, 10, '최초채권액', 1, 0, 'C');
	$pdf->Cell(30, 10, '잔존원금', 1, 0, 'C');
	$pdf->Cell(20, 10, '발생원인', 1, 1, 'C');
	
	// 채권자 목록 데이터
	$pdf->SetFont('cid0kr', '', 8);
	
	foreach($creditors as $index => $creditor) {
		// 줄 높이 설정
		$lineHeight = 8;
		
		// 금융기관명이 길면 줄바꿈 가능하도록 MultiCell 사용
		$pdf->Cell(10, $lineHeight, $creditor['creditor_count'], 1, 0, 'C');
		
		// 시작 Y 위치 저장
		$startY = $pdf->GetY();
		
		// 금융기관명 - MultiCell을 사용하여 자동 줄바꿈
		$pdf->MultiCell(50, $lineHeight, $creditor['financial_institution'] ?? '', 1, 'L');
		$endY = $pdf->GetY();
		
		// 다음 셀의 위치 조정
		$pdf->SetXY($pdf->GetX() + 60, $startY);
		
		// 나머지 필드 출력
		$pdf->Cell(30, $endY - $startY, $creditor['phone'] ?? '', 1, 0, 'L');
		
		// 차용일자 포맷팅
		$borrowing_date = !empty($creditor['borrowing_date']) ? date('Y-m-d', strtotime($creditor['borrowing_date'])) : '';
		$pdf->Cell(30, $endY - $startY, $borrowing_date, 1, 0, 'C');
		
		// 금액 필드 포맷팅
		$initial_claim = !empty($creditor['initial_claim']) ? number_format($creditor['initial_claim']) : '0';
		$remaining_principal = !empty($creditor['remaining_principal']) ? number_format($creditor['remaining_principal']) : '0';
		
		$pdf->Cell(30, $endY - $startY, $initial_claim, 1, 0, 'R');
		$pdf->Cell(30, $endY - $startY, $remaining_principal, 1, 0, 'R');
		$pdf->Cell(20, $endY - $startY, $creditor['separate_bond'] ?? '', 1, 1, 'C');
		
		// 페이지가 넘어갈 경우 헤더 다시 출력
		if ($pdf->GetY() > 250) {
			$pdf->AddPage();
			
			// 제목 (계속)
			$pdf->SetFont('cid0kr', 'B', 16);
			$pdf->Cell(0, 10, '채권자목록 (계속)', 0, 1, 'C');
			$pdf->Ln(5);
			
			// 테이블 헤더 다시 출력
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(10, 10, '번호', 1, 0, 'C');
			$pdf->Cell(50, 10, '금융기관명', 1, 0, 'C');
			$pdf->Cell(30, 10, '연락처', 1, 0, 'C');
			$pdf->Cell(30, 10, '차용일자', 1, 0, 'C');
			$pdf->Cell(30, 10, '최초채권액', 1, 0, 'C');
			$pdf->Cell(30, 10, '잔존원금', 1, 0, 'C');
			$pdf->Cell(20, 10, '발생원인', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 8);
		}
	}
	
	// 채권자가 없는 경우
	if (count($creditors) == 0) {
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(0, 10, '등록된 채권자가 없습니다.', 1, 1, 'C');
	}
	
	// 하단 서명
	$pdf->Ln(10);
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '위 기재 내용은 사실과 다름이 없습니다.', 0, 1, 'C');
	$pdf->Ln(5);
	$pdf->Cell(0, 10, date('Y년 m월 d일'), 0, 1, 'R');
	$pdf->Cell(0, 10, '신청인: ' . $basic_info['name'] . ' (인)', 0, 1, 'R');
	
	$pdf->Ln(10);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}
?>