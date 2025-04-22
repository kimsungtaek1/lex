<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfCreditors($pdf, $pdo, $case_no) {
	// PDF 기본 설정
	$pdf->SetMargins(15, 15, 15); // 좌, 상, 우 여백 설정
	$pdf->SetAutoPageBreak(true, 15); // 하단 여백 15mm 설정
	
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
		generateCreditorList($pdf, $pdo, $basic_info, $creditors);
		
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

function generateCreditorList($pdf, $pdo, $basic_info, $creditors) {
	// 첫 페이지 추가 (채권자 목록)
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '채권자목록', 0, 1, 'C');
	$pdf->Ln(2);
	
	// 신청인 정보
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(20, 8, '신청인:', 0, 0);
	$pdf->Cell(70, 8, $basic_info['name'], 0, 0);
	
	$pdf->Cell(25, 8, '사건번호:', 0, 0);
	$pdf->Cell(65, 8, $basic_info['case_number'] ?? '', 0, 1);
	
	$pdf->Cell(20, 8, '채권자수:', 0, 0);
	$pdf->Cell(70, 8, count($creditors) . '명', 0, 0);
	
	$pdf->Cell(25, 8, '총 채무액:', 0, 0);
	
	// 총 채무액 계산
	$total_debt = 0;
	foreach($creditors as $creditor) {
		$total_debt += (int)($creditor['initial_claim'] ?? 0);
	}
	
	$pdf->Cell(65, 8, number_format($total_debt) . '원', 0, 1);
	$pdf->Ln(3);

	// A4 용지에 맞는 테이블 너비 설정
	// 페이지 너비에서 좌우 여백을 뺀 값
	$tableWidth = $pdf->GetPageWidth() - 30; // 좌우 여백 각 15mm
	
	// 컬럼 너비 비율 조정 (총합 100%)
	$colWidths = [
		'번호' => $tableWidth * 0.08,  // 8%
		'채권자명' => $tableWidth * 0.17, // 17%
		'차용일' => $tableWidth * 0.12,  // 12%
		'발생원인' => $tableWidth * 0.15, // 15%
		'채권액' => $tableWidth * 0.15,  // 15%
		'사용처' => $tableWidth * 0.23,  // 23%
		'보증인' => $tableWidth * 0.10   // 10%
	];

	// 채권자 목록 테이블 헤더 (첫 번째 줄)
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->SetFillColor(240, 240, 240); // 헤더 배경색
	
	$pdf->Cell($colWidths['번호'], 7, '번호', 1, 0, 'C', true);
	$pdf->Cell($colWidths['채권자명'], 7, '채권자명', 1, 0, 'C', true);
	$pdf->Cell($colWidths['차용일'], 7, '차용/구입일자', 1, 0, 'C', true);
	$pdf->Cell($colWidths['발생원인'], 7, '발생원인', 1, 0, 'C', true);
	$pdf->Cell($colWidths['채권액'], 7, '최초 채권액', 1, 0, 'C', true);
	$pdf->Cell($colWidths['사용처'], 7, '사용처', 1, 0, 'C', true);
	$pdf->Cell($colWidths['보증인'], 7, '보증인', 1, 1, 'C', true);
	
	// 두 번째 줄 헤더
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->Cell($colWidths['번호'], 7, '', 'LRB', 0, 'C');
	$pdf->Cell($colWidths['채권자명'], 7, '', 'LRB', 0, 'C');
	$pdf->Cell($colWidths['차용일'], 7, '', 'LRB', 0, 'C');
	$pdf->Cell($colWidths['발생원인'], 7, '잔존 채권액', 1, 0, 'C', true);
	$pdf->Cell($colWidths['채권액'], 7, '잔존 원금', 1, 0, 'C', true);
	$pdf->Cell($colWidths['사용처'], 7, '잔존 이자·지연손해금', 1, 0, 'C', true);
	$pdf->Cell($colWidths['보증인'], 7, '', 'LRB', 1, 'C');

	// 채권자 목록 데이터
	$pdf->SetFont('cid0kr', '', 8);

	foreach($creditors as $index => $creditor) {
		// 해당 채권자의 보증인 목록 조회
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_creditor_guaranteed_debts
			WHERE case_no = ? AND creditor_count = ?
			ORDER BY guarantor_no
		");
		$stmt->execute([$basic_info['case_no'], $creditor['creditor_count']]);
		$guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 행 높이 설정
		$lineHeight = 7;
		
		// 채권자 순번
		$pdf->Cell($colWidths['번호'], $lineHeight, $creditor['creditor_count'], 1, 0, 'C');
		
		// 채권자명 (이름이 길 경우 줄바꿈 처리)
		$startX = $pdf->GetX();
		$startY = $pdf->GetY();
		
		$financial_institution = $creditor['financial_institution'] ?? '';
		// 문자열 길이에 따라 폰트 크기 자동 조정
		if (mb_strlen($financial_institution, 'UTF-8') > 10) {
			$pdf->SetFont('cid0kr', '', 7);
		}
		$pdf->Cell($colWidths['채권자명'], $lineHeight, $financial_institution, 1, 0, 'L');
		$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
		
		// 차용/구입일자
		$borrowing_date = !empty($creditor['borrowing_date']) ? date('Y-m-d', strtotime($creditor['borrowing_date'])) : '';
		$pdf->Cell($colWidths['차용일'], $lineHeight, $borrowing_date, 1, 0, 'C');
		
		// 발생원인
		$separate_bond = $creditor['separate_bond'] ?? '';
		// 문자열 길이에 따라 폰트 크기 자동 조정
		if (mb_strlen($separate_bond, 'UTF-8') > 10) {
			$pdf->SetFont('cid0kr', '', 7);
		}
		$pdf->Cell($colWidths['발생원인'], $lineHeight, $separate_bond, 1, 0, 'L');
		$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
		
		// 최초 채권액
		$pdf->Cell($colWidths['채권액'], $lineHeight, number_format($creditor['initial_claim'] ?? 0), 1, 0, 'R');
		
		// 사용처
		$usage_detail = $creditor['usage_detail'] ?? '';
		// 문자열 길이에 따라 폰트 크기 자동 조정
		if (mb_strlen($usage_detail, 'UTF-8') > 15) {
			$pdf->SetFont('cid0kr', '', 7);
		}
		$pdf->Cell($colWidths['사용처'], $lineHeight, $usage_detail, 1, 0, 'L');
		$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
		
		// 보증인 수
		$guarantorCount = count($guarantors);
		$pdf->Cell($colWidths['보증인'], $lineHeight, $guarantorCount > 0 ? $guarantorCount . '명' : '-', 1, 1, 'C');
		
		// 채권자 정보 출력 (두 번째 줄)
		$pdf->Cell($colWidths['번호'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
		$pdf->Cell($colWidths['채권자명'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
		$pdf->Cell($colWidths['차용일'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
		
		// 잔존 채권액 (원금 + 이자)
		$total_remaining = (int)($creditor['remaining_principal'] ?? 0) + (int)($creditor['remaining_interest'] ?? 0);
		$pdf->Cell($colWidths['발생원인'], $lineHeight, number_format($total_remaining), 1, 0, 'R');
		
		// 잔존 원금
		$pdf->Cell($colWidths['채권액'], $lineHeight, number_format($creditor['remaining_principal'] ?? 0), 1, 0, 'R');
		
		// 잔존 이자·지연손해금
		$pdf->Cell($colWidths['사용처'], $lineHeight, number_format($creditor['remaining_interest'] ?? 0), 1, 0, 'R');
		
		$pdf->Cell($colWidths['보증인'], $lineHeight, '', 'LRB', 1, 'C'); // 빈 칸
		
		// 보증인 정보 출력
		if ($guarantorCount > 0) {
			foreach($guarantors as $guarantorIndex => $guarantor) {
				// 보증인 순번 (예: 1-1, 1-2)
				$subNum = $creditor['creditor_count'] . '-' . ($guarantorIndex + 1);
				
				// 첫 번째 줄
				$pdf->Cell($colWidths['번호'], $lineHeight, $subNum, 1, 0, 'C');
				
				// 보증인명
				$guarantor_name = $guarantor['guarantor_name'] ?? '';
				// 문자열 길이에 따라 폰트 크기 자동 조정
				if (mb_strlen($guarantor_name, 'UTF-8') > 10) {
					$pdf->SetFont('cid0kr', '', 7);
				}
				$pdf->Cell($colWidths['채권자명'], $lineHeight, $guarantor_name, 1, 0, 'L');
				$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
				
				// 보증일자
				$guarantee_date = !empty($guarantor['guarantee_date']) ? date('Y-m-d', strtotime($guarantor['guarantee_date'])) : '';
				$pdf->Cell($colWidths['차용일'], $lineHeight, $guarantee_date, 1, 0, 'C');
				
				// 발생원인 (보증인의 경우 분쟁 사유 표시)
				$dispute_reason = $guarantor['dispute_reason'] ?? '보증';
				// 문자열 길이에 따라 폰트 크기 자동 조정
				if (mb_strlen($dispute_reason, 'UTF-8') > 10) {
					$pdf->SetFont('cid0kr', '', 7);
				}
				$pdf->Cell($colWidths['발생원인'], $lineHeight, $dispute_reason, 1, 0, 'L');
				$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
				
				// 보증금액
				$pdf->Cell($colWidths['채권액'], $lineHeight, number_format($guarantor['guarantee_amount'] ?? 0), 1, 0, 'R');
				
				// 분쟁사유 상세
				$dispute_content = $guarantor['dispute_reason_content'] ?? '';
				// 문자열 길이에 따라 폰트 크기 자동 조정
				if (mb_strlen($dispute_content, 'UTF-8') > 15) {
					$pdf->SetFont('cid0kr', '', 7);
				}
				$pdf->Cell($colWidths['사용처'], $lineHeight, $dispute_content, 1, 0, 'L');
				$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 복원
				
				// 보증인 표시
				$pdf->Cell($colWidths['보증인'], $lineHeight, '보증인', 1, 1, 'C');
				
				// 두 번째 줄
				$pdf->Cell($colWidths['번호'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
				$pdf->Cell($colWidths['채권자명'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
				$pdf->Cell($colWidths['차용일'], $lineHeight, '', 'LRB', 0, 'C'); // 빈 칸
				
				// 잔존 채권액 (보증인의 경우 공백)
				$pdf->Cell($colWidths['발생원인'], $lineHeight, '', 1, 0, 'C');
				
				// 잔존 원금 (보증인의 경우 공백)
				$pdf->Cell($colWidths['채권액'], $lineHeight, '', 1, 0, 'C');
				
				// 이자 차액
				$pdf->Cell($colWidths['사용처'], $lineHeight, $guarantor['difference_interest'] > 0 ? number_format($guarantor['difference_interest']) : '-', 1, 0, 'R');
				
				$pdf->Cell($colWidths['보증인'], $lineHeight, '', 'LRB', 1, 'C'); // 빈 칸
			}
		}
		
		// 채권자 사이에 간격 추가
		$pdf->Ln(1);
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
	
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
	
	// 두 번째 페이지 - 채권자 주소 정보
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '2. 채권자 주소', 0, 1, 'L');
	
	// 기재요령 추가
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->MultiCell(0, 6, "※기재요령※\n1. 채권자의 주소는 신청일 당시의 주소로 번지까지 정확하게 기재하고, 채무자를 위하여 보증을 한 존재 있으면 그 보증인의 주소까지 정확히 기재하여야 합니다.\n2. 채권자가 금융기관이나 기타 법인인 경우에는 본점 소재지 또는 거래지점의 소재지를 정확하게 기재하여야 합니다.", 0, 'L');
	$pdf->Ln(3);
	
	// 테이블 헤더
	$pdf->SetFont('cid0kr', 'B', 9);
	
	// 테이블 너비 및 열 너비 설정 - A4에 맞게 조정
	$addressTableWidth = $pdf->GetPageWidth() - 30; // 좌우 여백 각 15mm
	$addressColWidths = [
		'순번' => $addressTableWidth * 0.07,   // 7%
		'채권자명' => $addressTableWidth * 0.18,  // 18%
		'주소' => $addressTableWidth * 0.40,    // 40%
		'전화번호' => $addressTableWidth * 0.12,  // 12%
		'팩스' => $addressTableWidth * 0.12,     // 12%
		'비고' => $addressTableWidth * 0.11      // 11%
	];
	
	// 테이블 헤더 (배경색 적용)
	$pdf->SetFillColor(240, 240, 240);
	$pdf->Cell($addressColWidths['순번'], 7, '순번', 1, 0, 'C', true);
	$pdf->Cell($addressColWidths['채권자명'], 7, '채권자명', 1, 0, 'C', true);
	$pdf->Cell($addressColWidths['주소'], 7, '주소', 1, 0, 'C', true);
	$pdf->Cell($addressColWidths['전화번호'], 7, '전화번호', 1, 0, 'C', true);
	$pdf->Cell($addressColWidths['팩스'], 7, '팩스', 1, 0, 'C', true);
	$pdf->Cell($addressColWidths['비고'], 7, '비고', 1, 1, 'C', true);
	$pdf->SetFont('cid0kr', '', 9);
	
	// 채권자 및 보증인 주소 목록
	foreach($creditors as $creditor) {
		// 주소 길이에 따른 행 높이 계산
		$lineHeight = 7;
		$address = $creditor['address'] ?? '';
		$addressLines = $pdf->GetNumLines($address, $addressColWidths['주소']);
		if ($addressLines > 1) {
			$lineHeight = $lineHeight * $addressLines;
		}
		
		// 순번
		$pdf->Cell($addressColWidths['순번'], $lineHeight, $creditor['creditor_count'], 1, 0, 'C');
		
		// 채권자명
		$financial_institution = $creditor['financial_institution'] ?? '';
		if (mb_strlen($financial_institution, 'UTF-8') > 12) {
			$pdf->SetFont('cid0kr', '', 8);
		}
		$pdf->Cell($addressColWidths['채권자명'], $lineHeight, $financial_institution, 1, 0, 'L');
		$pdf->SetFont('cid0kr', '', 9);
		
		// 주소 - MultiCell 사용
		$startX = $pdf->GetX();
		$startY = $pdf->GetY();
		
		// 현재 위치 저장
		$currentX = $pdf->GetX();
		$currentY = $pdf->GetY();
		
		// 실제 출력할 열 너비
		$addressWidth = $addressColWidths['주소'];
		
		// MultiCell로 주소 출력
		$pdf->MultiCell($addressWidth, $lineHeight / $addressLines, $address, 1, 'L');
		
		// 다음 열의 시작 위치 계산
		$nextX = $currentX + $addressWidth;
		$pdf->SetXY($nextX, $currentY);
		
		// 전화번호
		$pdf->Cell($addressColWidths['전화번호'], $lineHeight, $creditor['phone'] ?? '', 1, 0, 'C');
		
		// 팩스
		$pdf->Cell($addressColWidths['팩스'], $lineHeight, $creditor['fax'] ?? '', 1, 0, 'C');
		
		// 비고(우편번호)
		$pdf->Cell($addressColWidths['비고'], $lineHeight, '', 1, 1, 'C');
		
		// 해당 채권자의 보증인 목록 조회 및 출력
		$stmt = $pdo->prepare("
			SELECT *
			FROM application_bankruptcy_creditor_guaranteed_debts
			WHERE case_no = ? AND creditor_count = ?
			ORDER BY guarantor_no
		");
		$stmt->execute([$basic_info['case_no'], $creditor['creditor_count']]);
		$guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($guarantors as $guarantorIndex => $guarantor) {
			// 보증인 순번 (예: 1-1, 1-2)
			$subNum = $creditor['creditor_count'] . '-' . ($guarantorIndex + 1);
			
			// 보증인 주소 행 높이 계산
			$guarantorAddress = $guarantor['guarantor_address'] ?? '';
			$guarantorAddressLines = $pdf->GetNumLines($guarantorAddress, $addressColWidths['주소']);
			$guarantorLineHeight = 7;
			if ($guarantorAddressLines > 1) {
				$guarantorLineHeight = $guarantorLineHeight * $guarantorAddressLines;
			}
			
			// 순번
			$pdf->Cell($addressColWidths['순번'], $guarantorLineHeight, $subNum, 1, 0, 'C');
			
			// 보증인명
			$guarantor_name = $guarantor['guarantor_name'] ?? '';
			if (mb_strlen($guarantor_name, 'UTF-8') > 12) {
				$pdf->SetFont('cid0kr', '', 8);
			}
			$pdf->Cell($addressColWidths['채권자명'], $guarantorLineHeight, $guarantor_name, 1, 0, 'L');
			$pdf->SetFont('cid0kr', '', 9);
			
			// 보증인 주소 - MultiCell 사용
			$currentX = $pdf->GetX();
			$currentY = $pdf->GetY();
			
			// MultiCell로 주소 출력
			$pdf->MultiCell($addressColWidths['주소'], $guarantorLineHeight / $guarantorAddressLines, $guarantorAddress, 1, 'L');
			
			// 다음 열의 시작 위치 계산
			$nextX = $currentX + $addressColWidths['주소'];
			$pdf->SetXY($nextX, $currentY);
			
			// 전화번호
			$pdf->Cell($addressColWidths['전화번호'], $guarantorLineHeight, $guarantor['guarantor_phone'] ?? '', 1, 0, 'C');
			
			// 팩스
			$pdf->Cell($addressColWidths['팩스'], $guarantorLineHeight, $guarantor['guarantor_fax'] ?? '', 1, 0, 'C');
			
			// 비고
			$pdf->Cell($addressColWidths['비고'], $guarantorLineHeight, '보증인', 1, 1, 'C');
		}
	}
	
	// 채권자가 없는 경우
	if (count($creditors) == 0) {
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(0, 10, '등록된 채권자가 없습니다.', 1, 1, 'C');
	}
	
	// 참고 사항 추가
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', '', 9);
	$pdf->MultiCell(0, 6, "※ '신청서'를 제출한 경우, 법원 홈페이지 \"나의 사건검색\" 에서 본 채권자목록의 반영여부를 확인할 수 있습니다.", 0, 'L');
}
?>