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

	// 채권자 목록 테이블 헤더 (첫 번째 줄)
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->Cell(15, 8, '번호', 1, 0, 'C');
	$pdf->Cell(35, 8, '채권자명', 1, 0, 'C');
	$pdf->Cell(25, 8, '차용/구입일자', 1, 0, 'C');
	$pdf->Cell(30, 8, '발생원인', 1, 0, 'C');
	$pdf->Cell(30, 8, '최초 채권액', 1, 0, 'C');
	$pdf->Cell(40, 8, '사용처', 1, 0, 'C');
	$pdf->Cell(15, 8, '보증인', 1, 1, 'C');
	
	// 두 번째 줄 헤더
	$pdf->SetFont('cid0kr', 'B', 9);
	$pdf->Cell(15, 8, '', 0, 0, 'C'); // 빈 칸
	$pdf->Cell(35, 8, '', 0, 0, 'C'); // 빈 칸
	$pdf->Cell(25, 8, '', 0, 0, 'C'); // 빈 칸
	$pdf->Cell(30, 8, '잔존 채권액', 1, 0, 'C');
	$pdf->Cell(30, 8, '잔존 원금', 1, 0, 'C');
	$pdf->Cell(40, 8, '잔존 이자·지연손해금', 1, 0, 'C');
	$pdf->Cell(15, 8, '', 0, 1, 'C'); // 빈 칸

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
		
		// 채권자 정보 출력 (첫 번째 줄)
		$lineHeight = 8;
		
		// 채권자 순번
		$pdf->Cell(15, $lineHeight, $creditor['creditor_count'], 1, 0, 'C');
		$startX = $pdf->GetX();
		$startY = $pdf->GetY();
		
		// 채권자명
		$pdf->Cell(35, $lineHeight, $creditor['financial_institution'] ?? '', 1, 0, 'L');
		
		// 차용/구입일자
		$borrowing_date = !empty($creditor['borrowing_date']) ? date('Y-m-d', strtotime($creditor['borrowing_date'])) : '';
		$pdf->Cell(25, $lineHeight, $borrowing_date, 1, 0, 'C');
		
		// 발생원인
		$pdf->Cell(30, $lineHeight, $creditor['separate_bond'] ?? '', 1, 0, 'L');
		
		// 최초 채권액
		$pdf->Cell(30, $lineHeight, number_format($creditor['initial_claim'] ?? 0), 1, 0, 'R');
		
		// 사용처
		$pdf->Cell(40, $lineHeight, $creditor['usage_detail'] ?? '', 1, 0, 'L');
		
		// 보증인 수
		$guarantorCount = count($guarantors);
		$pdf->Cell(15, $lineHeight, $guarantorCount > 0 ? $guarantorCount . '명' : '-', 1, 1, 'C');
		
		// 채권자 정보 출력 (두 번째 줄)
		$pdf->Cell(15, $lineHeight, '', 0, 0, 'C'); // 빈 칸
		$pdf->Cell(35, $lineHeight, '', 0, 0, 'C'); // 빈 칸
		$pdf->Cell(25, $lineHeight, '', 0, 0, 'C'); // 빈 칸
		
		// 잔존 채권액 (원금 + 이자)
		$total_remaining = (int)($creditor['remaining_principal'] ?? 0) + (int)($creditor['remaining_interest'] ?? 0);
		$pdf->Cell(30, $lineHeight, number_format($total_remaining), 1, 0, 'R');
		
		// 잔존 원금
		$pdf->Cell(30, $lineHeight, number_format($creditor['remaining_principal'] ?? 0), 1, 0, 'R');
		
		// 잔존 이자·지연손해금
		$pdf->Cell(40, $lineHeight, number_format($creditor['remaining_interest'] ?? 0), 1, 0, 'R');
		
		$pdf->Cell(15, $lineHeight, '', 0, 1, 'C'); // 빈 칸
		
		// 보증인 정보 출력
		if ($guarantorCount > 0) {
			foreach($guarantors as $guarantorIndex => $guarantor) {
				// 보증인 순번 (예: 1-1, 1-2)
				$subNum = $creditor['creditor_count'] . '-' . ($guarantorIndex + 1);
				
				// 첫 번째 줄
				$pdf->Cell(15, $lineHeight, $subNum, 1, 0, 'C');
				$guarantorStartX = $pdf->GetX();
				$guarantorStartY = $pdf->GetY();
				
				// 보증인명
				$pdf->Cell(35, $lineHeight, $guarantor['guarantor_name'] ?? '', 1, 0, 'L');
				
				// 보증일자
				$guarantee_date = !empty($guarantor['guarantee_date']) ? date('Y-m-d', strtotime($guarantor['guarantee_date'])) : '';
				$pdf->Cell(25, $lineHeight, $guarantee_date, 1, 0, 'C');
				
				// 발생원인 (보증인의 경우 분쟁 사유 표시)
				$pdf->Cell(30, $lineHeight, $guarantor['dispute_reason'] ?? '보증', 1, 0, 'L');
				
				// 보증금액
				$pdf->Cell(30, $lineHeight, number_format($guarantor['guarantee_amount'] ?? 0), 1, 0, 'R');
				
				// 분쟁사유 상세
				$pdf->Cell(40, $lineHeight, $guarantor['dispute_reason_content'] ?? '', 1, 0, 'L');
				
				// 보증인 표시
				$pdf->Cell(15, $lineHeight, '보증인', 1, 1, 'C');
				
				// 두 번째 줄
				$pdf->Cell(15, $lineHeight, '', 0, 0, 'C'); // 빈 칸
				$pdf->Cell(35, $lineHeight, '', 0, 0, 'C'); // 빈 칸
				$pdf->Cell(25, $lineHeight, '', 0, 0, 'C'); // 빈 칸
				
				// 잔존 채권액 (보증인의 경우 공백)
				$pdf->Cell(30, $lineHeight, '', 1, 0, 'C');
				
				// 잔존 원금 (보증인의 경우 공백)
				$pdf->Cell(30, $lineHeight, '', 1, 0, 'C');
				
				// 이자 차액
				$pdf->Cell(40, $lineHeight, $guarantor['difference_interest'] > 0 ? number_format($guarantor['difference_interest']) : '-', 1, 0, 'R');
				
				$pdf->Cell(15, $lineHeight, '', 0, 1, 'C'); // 빈 칸
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
	
	$pdf->Ln(10);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
	
	// 두 번째 페이지 - 채권자 주소 정보
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '2. 채권자 주소', 0, 1, 'L');
	
	// 기재요령 추가
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '※기재요령※', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 9);
	$pdf->Cell(0, 8, '1. 채권자의 주소는 신청일 당시의 주소로 번지까지 정확하게 기재하고, 채무자를 위하여', 0, 1, 'L');
	$pdf->Cell(0, 8, '   보증을 한 존재 있으면 그 보증인의 주소까지 정확히 기재하여야 합니다.', 0, 1, 'L');
	$pdf->Cell(0, 8, '2. 채권자가 금융기관이나 기타 법인인 경우에는 본점 소재지 또는 거래지점의 소재지를 정확하게 기재하여야', 0, 1, 'L');
	$pdf->Cell(0, 8, '   합니다.', 0, 1, 'L');
	
	// 테이블 헤더
	$pdf->SetFont('cid0kr', 'B', 9);
	
	// 테이블 너비 및 열 너비 설정
	$tableWidth = 190;
	$colWidths = [12, 35, 70, 25, 25, 23]; // 순번, 채권자명, 주소, 전화번호, 팩스, 비고
	
	// 테이블 헤더
	$pdf->Cell($colWidths[0], 10, '순번', 1, 0, 'C');
	$pdf->Cell($colWidths[1], 10, '채권자명', 1, 0, 'C');
	$pdf->Cell($colWidths[2], 10, '주소', 1, 0, 'C');
	$pdf->Cell($colWidths[3], 10, '전화번호', 1, 0, 'C');
	$pdf->Cell($colWidths[4], 10, '팩스', 1, 0, 'C');
	$pdf->Cell($colWidths[5], 10, '비고', 1, 1, 'C');
	$pdf->Cell($colWidths[5], 10, '(우편번호)', 1, 1, 'C');
	
	// 채권자 및 보증인 주소 목록
	$pdf->SetFont('cid0kr', '', 9);
	
	foreach($creditors as $creditor) {
		// 채권자 행
		// 우편번호 추출 (실제로는 우편번호 필드가 있을 것이나, 여기서는 가정)
		$postalCode = ''; // 실제 데이터에 우편번호 필드가 있다면 여기서 가져옴
		
		// 주소가 너무 길면 자동으로 줄바꿈 처리되도록 MultiCell 사용
		$lineHeight = 10;
		
		// 순번
		$pdf->Cell($colWidths[0], $lineHeight, $creditor['creditor_count'], 1, 0, 'C');
		
		// 채권자명
		$pdf->Cell($colWidths[1], $lineHeight, $creditor['financial_institution'] ?? '', 1, 0, 'L');
		
		// 주소
		$startY = $pdf->GetY();
		$currentX = $pdf->GetX();
		$pdf->MultiCell($colWidths[2], $lineHeight/2, $creditor['address'] ?? '', 1, 'L');
		$endY = $pdf->GetY();
		$rowHeight = $endY - $startY;
		
		// MultiCell 다음 위치 조정
		$pdf->SetXY($currentX + $colWidths[2], $startY);
		
		// 전화번호 
		$pdf->Cell($colWidths[3], $rowHeight, $creditor['phone'] ?? '', 1, 0, 'C');
		
		// 팩스
		$pdf->Cell($colWidths[4], $rowHeight, $creditor['fax'] ?? '', 1, 0, 'C');
		
		// 비고(우편번호)
		$pdf->Cell($colWidths[5], $rowHeight, $postalCode, 1, 1, 'C');
		
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
			
			// 순번
			$pdf->Cell($colWidths[0], $lineHeight, $subNum, 1, 0, 'C');
			
			// 보증인명
			$pdf->Cell($colWidths[1], $lineHeight, $guarantor['guarantor_name'] ?? '', 1, 0, 'L');
			
			// 주소
			$startY = $pdf->GetY();
			$currentX = $pdf->GetX();
			$pdf->MultiCell($colWidths[2], $lineHeight/2, $guarantor['guarantor_address'] ?? '', 1, 'L');
			$endY = $pdf->GetY();
			$rowHeight = $endY - $startY;
			
			// MultiCell 다음 위치 조정
			$pdf->SetXY($currentX + $colWidths[2], $startY);
			
			// 전화번호
			$pdf->Cell($colWidths[3], $rowHeight, $guarantor['guarantor_phone'] ?? '', 1, 0, 'C');
			
			// 팩스
			$pdf->Cell($colWidths[4], $rowHeight, $guarantor['guarantor_fax'] ?? '', 1, 0, 'C');
			
			// 비고(우편번호)
			$postalCode = ''; // 보증인의 우편번호가 있다면 여기서 가져옴
			$pdf->Cell($colWidths[5], $rowHeight, $postalCode, 1, 1, 'C');
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
	$pdf->Cell(0, 8, '※ \'신청서\'를 제출한 경우, 법원 홈페이지 \"나의 사건검색\" 에서 본 채권자목록의 반영여부를 확인할 수', 0, 1, 'L');
	$pdf->Cell(0, 8, '있습니다.', 0, 1, 'L');
}
?>