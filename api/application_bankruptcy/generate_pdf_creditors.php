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
	
	// 컬럼 너비 비율 조정 (총합 100%) - 8개 컬럼으로 수정
	$colWidths = [
		'순번' => $tableWidth * 0.07,      // 7%
		'채권자명' => $tableWidth * 0.08,  // 8% (수정됨)
		'차용일' => $tableWidth * 0.11,    // 11%
		'발생원인' => $tableWidth * 0.08,  // 8%
		'최초채권액' => $tableWidth * 0.12,// 12%
		'사용처' => $tableWidth * 0.14,    // 14% (수정됨)
		'보증인' => $tableWidth * 0.10,    // 10% (수정됨)
		'잔존채권액' => $tableWidth * 0.30  // 30% (확대됨)
	];
	
	// 잔존 채권액 하위 컬럼 너비
	$remainingColWidths = [
		'잔존원금' => $colWidths['잔존채권액'] * 0.5,     // 50%
		'잔존이자' => $colWidths['잔존채권액'] * 0.5      // 50%
	];

	// 테이블 헤더 - 모든 헤더를 멀티셀로 처리
	$pdf->SetFont('cid0kr', 'B', 8); // 폰트 크기 8로 변경

	// 헤더 행 높이 설정
	$headerRowHeight = 16; // 전체 헤더 행 높이
	$subRowHeight = 8;    // 서브 헤더 행 높이
	
	// 첫 번째 행 시작 위치 저장
	$startX = $pdf->GetX();
	$startY = $pdf->GetY();
	
	// 헤더 텍스트 및 위치 설정
	$headerTexts = [
		'순번' => "순번",
		'채권자명' => "채권자명",
		'차용일' => "차용 또는\n구입일자",
		'발생원인' => "발생원인",
		'최초채권액' => "최초 채권액",
		'사용처' => "사용처", 
		'보증인' => "보증인"
	];
	
	// TCPDF에서는 FontSize에 직접 접근할 수 없으므로 상수 값 사용
	$lineHeight = 4; // 폰트 크기 8에 대한 적절한 라인 높이
	
	// 각 컬럼 출력
	$currentX = $startX;
	foreach ($headerTexts as $key => $text) {
		// 텍스트 줄 수 계산
		$lines = $pdf->GetNumLines($text, $colWidths[$key]);
		
		// 텍스트 전체 높이
		$textHeight = $lines * $lineHeight;
		
		// 세로 정렬을 위한 Y 오프셋 계산 (중앙 정렬)
		$yOffset = ($headerRowHeight - $textHeight) / 2;
		
		// 셀 테두리 그리기
		$pdf->Rect($currentX, $startY, $colWidths[$key], $headerRowHeight);
		
		// 텍스트 출력 위치 설정 (세로 중앙)
		$pdf->SetXY($currentX, $startY + $yOffset);
		
		// 텍스트 출력 (테두리 없이)
		$pdf->MultiCell($colWidths[$key], $lineHeight, $text, 0, 'C');
		
		// 다음 셀 X 위치로 이동
		$currentX += $colWidths[$key];
		$pdf->SetXY($currentX, $startY);
	}
	
	// 잔존 채권액 메인 헤더 (첫 번째 행)
	$pdf->Rect($currentX, $startY, $colWidths['잔존채권액'], $subRowHeight);
	
	// 잔존 채권액 텍스트 세로 중앙 정렬
	$mainHeaderText = "잔존 채권액";
	$mainLines = $pdf->GetNumLines($mainHeaderText, $colWidths['잔존채권액']);
	$mainTextHeight = $mainLines * $lineHeight;
	$mainYOffset = ($subRowHeight - $mainTextHeight) / 2;
	
	$pdf->SetXY($currentX, $startY + $mainYOffset);
	$pdf->MultiCell($colWidths['잔존채권액'], $lineHeight, $mainHeaderText, 0, 'C');
	
	// 잔존 채권액 하위 헤더 위치 설정
	$subHeaderY = $startY + $subRowHeight;
	
	// 잔존 원금 하위 헤더
	$pdf->Rect($currentX, $subHeaderY, $remainingColWidths['잔존원금'], $subRowHeight);
	$subText1 = "잔존 원금";
	$subLines1 = $pdf->GetNumLines($subText1, $remainingColWidths['잔존원금']);
	$subTextHeight1 = $subLines1 * $lineHeight;
	$subYOffset1 = ($subRowHeight - $subTextHeight1) / 2;
	
	$pdf->SetXY($currentX, $subHeaderY + $subYOffset1);
	$pdf->MultiCell($remainingColWidths['잔존원금'], $lineHeight, $subText1, 0, 'C');
	
	// 잔존 이자 하위 헤더
	$pdf->Rect($currentX + $remainingColWidths['잔존원금'], $subHeaderY, $remainingColWidths['잔존이자'], $subRowHeight);
	$subText2 = "잔존 이자\n지연손해금";
	$subLines2 = $pdf->GetNumLines($subText2, $remainingColWidths['잔존이자']);
	$subTextHeight2 = $subLines2 * $lineHeight;
	$subYOffset2 = ($subRowHeight - $subTextHeight2) / 2;
	
	$pdf->SetXY($currentX + $remainingColWidths['잔존원금'], $subHeaderY + $subYOffset2);
	$pdf->MultiCell($remainingColWidths['잔존이자'], $lineHeight, $subText2, 0, 'C');
	
	// 다음 행의 시작 위치 설정 (헤더 아래)
	$pdf->SetXY($startX, $startY + $headerRowHeight);
	
	// 잔존 채권액 메인 헤더 (첫 번째 행)
	$pdf->Rect($currentX, $startY, $colWidths['잔존채권액'], $subRowHeight);
	
	// 잔존 채권액 텍스트 세로 중앙 정렬
	$mainHeaderText = "잔존 채권액";
	$mainLines = $pdf->GetNumLines($mainHeaderText, $colWidths['잔존채권액']);
	$mainTextHeight = $mainLines * $lineHeight;
	$mainYOffset = ($subRowHeight - $mainTextHeight) / 2;
	
	$pdf->SetXY($currentX, $startY + $mainYOffset);
	$pdf->MultiCell($colWidths['잔존채권액'], $lineHeight, $mainHeaderText, 0, 'C');
	
	// 잔존 채권액 하위 헤더 위치 설정
	$subHeaderY = $startY + $subRowHeight;
	
	// 잔존 원금 하위 헤더
	$pdf->Rect($currentX, $subHeaderY, $remainingColWidths['잔존원금'], $subRowHeight);
	$subText1 = "잔존 원금";
	$subLines1 = $pdf->GetNumLines($subText1, $remainingColWidths['잔존원금']);
	$subTextHeight1 = $subLines1 * $lineHeight;
	$subYOffset1 = ($subRowHeight - $subTextHeight1) / 2;
	
	$pdf->SetXY($currentX, $subHeaderY + $subYOffset1);
	$pdf->MultiCell($remainingColWidths['잔존원금'], $lineHeight, $subText1, 0, 'C');
	
	// 잔존 이자 하위 헤더
	$pdf->Rect($currentX + $remainingColWidths['잔존원금'], $subHeaderY, $remainingColWidths['잔존이자'], $subRowHeight);
	$subText2 = "잔존 이자\n지연손해금";
	$subLines2 = $pdf->GetNumLines($subText2, $remainingColWidths['잔존이자']);
	$subTextHeight2 = $subLines2 * $lineHeight;
	$subYOffset2 = ($subRowHeight - $subTextHeight2) / 2;
	
	$pdf->SetXY($currentX + $remainingColWidths['잔존원금'], $subHeaderY + $subYOffset2);
	$pdf->MultiCell($remainingColWidths['잔존이자'], $lineHeight, $subText2, 0, 'C');
	
	// 다음 행의 시작 위치 설정 (헤더 아래)
	$pdf->SetXY($startX, $startY + $headerRowHeight);
	
	// 다음 행의 시작 위치 설정 (헤더 아래)
	$pdf->SetXY($startX, $startY + $headerRowHeight);

	// 채권자 목록 데이터
	$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 8로 통일

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
		
		// 채권자 행의 높이 계산을 위한 텍스트 저장
		$texts = [
			'채권자명' => $creditor['financial_institution'] ?? '',
			'발생원인' => $creditor['separate_bond'] ?? '',
			'사용처' => $creditor['usage_detail'] ?? ''
		];
		
		// 보증인 이름 목록
		$guarantorCount = count($guarantors);
		$guarantorNames = '';
		
		if ($guarantorCount > 0) {
			$nameList = [];
			foreach ($guarantors as $g) {
				if (!empty($g['guarantor_name'])) {
					$nameList[] = $g['guarantor_name'];
				}
			}
			$guarantorNames = implode(', ', $nameList);
			$texts['보증인'] = $guarantorNames;
		} else {
			$texts['보증인'] = '-';
		}
		
		// 각 셀의 필요 높이 계산
		$maxLines = 1;
		$lineHeightBase = 7; // 기본 라인 높이
		
		foreach ($texts as $key => $text) {
			$lines = $pdf->GetNumLines($text, $colWidths[$key]);
			$maxLines = max($maxLines, $lines);
		}
		
		// 행 높이 설정 (가장 많은 줄 수를 기준으로)
		$rowHeight = $lineHeightBase * $maxLines;
		
		// 행의 시작 위치 저장
		$rowStartX = $pdf->GetX();
		$rowStartY = $pdf->GetY();
		
		// 순번
		$pdf->MultiCell($colWidths['순번'], $rowHeight, $creditor['creditor_count'], 1, 'C');
		$pdf->SetXY($rowStartX + $colWidths['순번'], $rowStartY);
		
		// 채권자명
		$financial_institution = $texts['채권자명'];
		$pdf->MultiCell($colWidths['채권자명'], $rowHeight, $financial_institution, 1, 'L');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'], $rowStartY);
		
		// 차용/구입일자
		$borrowing_date = !empty($creditor['borrowing_date']) ? date('Y-m-d', strtotime($creditor['borrowing_date'])) : '';
		$pdf->MultiCell($colWidths['차용일'], $rowHeight, $borrowing_date, 1, 'C');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'], $rowStartY);
		
		// 발생원인
		$separate_bond = $texts['발생원인'];
		$pdf->MultiCell($colWidths['발생원인'], $rowHeight, $separate_bond, 1, 'L');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'], $rowStartY);
		
		// 최초 채권액
		$pdf->MultiCell($colWidths['최초채권액'], $rowHeight, number_format($creditor['initial_claim'] ?? 0), 1, 'R');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'], $rowStartY);
		
		// 사용처
		$usage_detail = $texts['사용처'];
		$pdf->MultiCell($colWidths['사용처'], $rowHeight, $usage_detail, 1, 'L');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'], $rowStartY);
		
		// 보증인
		$pdf->MultiCell($colWidths['보증인'], $rowHeight, $texts['보증인'], 1, 'L');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'] + $colWidths['보증인'], $rowStartY);
		
		// 잔존 원금
		$pdf->MultiCell($remainingColWidths['잔존원금'], $rowHeight, number_format($creditor['remaining_principal'] ?? 0), 1, 'R');
		$pdf->SetXY($rowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'] + $colWidths['보증인'] + $remainingColWidths['잔존원금'], $rowStartY);
		
		// 잔존 이자·지연손해금
		$pdf->MultiCell($remainingColWidths['잔존이자'], $rowHeight, number_format($creditor['remaining_interest'] ?? 0), 1, 'R');
		
		// 다음 행 시작 위치 설정
		$pdf->SetXY($rowStartX, $rowStartY + $rowHeight);
		
		// 보증인 정보 출력
		if ($guarantorCount > 0) {
			foreach($guarantors as $guarantorIndex => $guarantor) {
				// 보증인 행의 높이 계산을 위한 텍스트 저장
				$guarantorTexts = [
					'채권자명' => $guarantor['guarantor_name'] ?? '',
					'발생원인' => $guarantor['dispute_reason'] ?? '보증',
					'사용처' => $guarantor['dispute_reason_content'] ?? ''
				];
				
				// 각 셀의 필요 높이 계산
				$guarantorMaxLines = 1;
				
				foreach ($guarantorTexts as $key => $text) {
					$lines = $pdf->GetNumLines($text, $colWidths[$key]);
					$guarantorMaxLines = max($guarantorMaxLines, $lines);
				}
				
				// 행 높이 설정 (가장 많은 줄 수를 기준으로)
				$guarantorRowHeight = $lineHeightBase * $guarantorMaxLines;
				
				// 행의 시작 위치 저장
				$guarantorRowStartX = $pdf->GetX();
				$guarantorRowStartY = $pdf->GetY();
				
				// 보증인 순번 (예: 1-1, 1-2)
				$subNum = $creditor['creditor_count'] . '-' . ($guarantorIndex + 1);
				$pdf->MultiCell($colWidths['순번'], $guarantorRowHeight, $subNum, 1, 'C');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'], $guarantorRowStartY);
				
				// 보증인명
				$guarantor_name = $guarantorTexts['채권자명'];
				$pdf->MultiCell($colWidths['채권자명'], $guarantorRowHeight, $guarantor_name, 1, 'L');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'], $guarantorRowStartY);
				
				// 보증일자
				$guarantee_date = !empty($guarantor['guarantee_date']) ? date('Y-m-d', strtotime($guarantor['guarantee_date'])) : '';
				$pdf->MultiCell($colWidths['차용일'], $guarantorRowHeight, $guarantee_date, 1, 'C');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'], $guarantorRowStartY);
				
				// 발생원인 (보증인의 경우 분쟁 사유 표시)
				$dispute_reason = $guarantorTexts['발생원인'];
				$pdf->MultiCell($colWidths['발생원인'], $guarantorRowHeight, $dispute_reason, 1, 'L');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'], $guarantorRowStartY);
				
				// 보증금액
				$pdf->MultiCell($colWidths['최초채권액'], $guarantorRowHeight, number_format($guarantor['guarantee_amount'] ?? 0), 1, 'R');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'], $guarantorRowStartY);
				
				// 분쟁사유 상세
				$dispute_content = $guarantorTexts['사용처'];
				$pdf->MultiCell($colWidths['사용처'], $guarantorRowHeight, $dispute_content, 1, 'L');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'], $guarantorRowStartY);
				
				// 보증인 표시
				$pdf->MultiCell($colWidths['보증인'], $guarantorRowHeight, '보증인', 1, 'C');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'] + $colWidths['보증인'], $guarantorRowStartY);
				
				// 잔존 원금 부분에는 공백
				$pdf->MultiCell($remainingColWidths['잔존원금'], $guarantorRowHeight, '', 1, 'C');
				$pdf->SetXY($guarantorRowStartX + $colWidths['순번'] + $colWidths['채권자명'] + $colWidths['차용일'] + $colWidths['발생원인'] + $colWidths['최초채권액'] + $colWidths['사용처'] + $colWidths['보증인'] + $remainingColWidths['잔존원금'], $guarantorRowStartY);
				
				// 이자 차액 표시
				$pdf->MultiCell($remainingColWidths['잔존이자'], $guarantorRowHeight, $guarantor['difference_interest'] > 0 ? number_format($guarantor['difference_interest']) : '-', 1, 'R');
				
				// 다음 행 시작 위치 설정
				$pdf->SetXY($guarantorRowStartX, $guarantorRowStartY + $guarantorRowHeight);
			}
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
	
	// 테이블 헤더 - 헤더 색상 제거
	$pdf->SetFont('cid0kr', 'B', 8); // 폰트 크기 8로 변경
	
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
	
	// 테이블 헤더
	$headerStartX = $pdf->GetX();
	$headerStartY = $pdf->GetY();
	$headerHeight = 7;
	
	// 헤더 텍스트 정의
	$headerTexts = [
		'순번' => '순번',
		'채권자명' => '채권자명',
		'주소' => '주소',
		'전화번호' => '전화번호',
		'팩스' => '팩스',
		'비고' => '비고'
	];
	
	// 헤더 출력 - 세로 중앙 정렬 적용
	$currentX = $headerStartX;
	foreach ($headerTexts as $key => $text) {
		// 테두리만 그리기
		$pdf->Rect($currentX, $headerStartY, $addressColWidths[$key], $headerHeight);
		
		// 텍스트 세로 중앙 정렬
		$lines = $pdf->GetNumLines($text, $addressColWidths[$key]);
		$textHeight = $lines * ($headerHeight / 2);
		$verticalPadding = ($headerHeight - $textHeight) / 2;
		
		$pdf->SetXY($currentX, $headerStartY + $verticalPadding);
		$pdf->MultiCell($addressColWidths[$key], $headerHeight / $lines, $text, 0, 'C');
		
		// 다음 셀 위치 계산
		$currentX += $addressColWidths[$key];
		$pdf->SetXY($currentX, $headerStartY);
	}
	
	// 다음 행으로 이동
	$pdf->SetXY($headerStartX, $headerStartY + $headerHeight);
	$pdf->SetFont('cid0kr', '', 8); // 폰트 크기 8로 변경
	
	// 채권자 및 보증인 주소 목록
	foreach($creditors as $creditor) {
		// 주소 길이에 따른 행 높이 계산
		$address = $creditor['address'] ?? '';
		$financial_institution = $creditor['financial_institution'] ?? '';
		
		// 각 셀의 필요 높이 계산
		$addressLines = $pdf->GetNumLines($address, $addressColWidths['주소']);
		$nameLines = $pdf->GetNumLines($financial_institution, $addressColWidths['채권자명']);
		$maxLines = max($addressLines, $nameLines, 1);
		
		// 행 높이 계산
		$lineHeight = 7 * $maxLines;
		
		// 행의 시작 위치 저장
		$rowStartX = $pdf->GetX();
		$rowStartY = $pdf->GetY();
		
		// 순번
		$pdf->MultiCell($addressColWidths['순번'], $lineHeight, $creditor['creditor_count'], 1, 'C');
		$pdf->SetXY($rowStartX + $addressColWidths['순번'], $rowStartY);
		
		// 채권자명
		$pdf->MultiCell($addressColWidths['채권자명'], $lineHeight, $financial_institution, 1, 'L');
		$pdf->SetXY($rowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'], $rowStartY);
		
		// 주소
		$pdf->MultiCell($addressColWidths['주소'], $lineHeight, $address, 1, 'L');
		$pdf->SetXY($rowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'], $rowStartY);
		
		// 전화번호
		$pdf->MultiCell($addressColWidths['전화번호'], $lineHeight, $creditor['phone'] ?? '', 1, 'C');
		$pdf->SetXY($rowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'] + $addressColWidths['전화번호'], $rowStartY);
		
		// 팩스
		$pdf->MultiCell($addressColWidths['팩스'], $lineHeight, $creditor['fax'] ?? '', 1, 'C');
		$pdf->SetXY($rowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'] + $addressColWidths['전화번호'] + $addressColWidths['팩스'], $rowStartY);
		
		// 비고(우편번호)
		$pdf->MultiCell($addressColWidths['비고'], $lineHeight, '', 1, 'C');
		
		// 다음 행 시작 위치 설정
		$pdf->SetXY($rowStartX, $rowStartY + $lineHeight);
		
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
			
			// 보증인 주소 및 이름 텍스트
			$guarantorAddress = $guarantor['guarantor_address'] ?? '';
			$guarantor_name = $guarantor['guarantor_name'] ?? '';
			
			// 각 셀의 필요 높이 계산
			$guarantorAddressLines = $pdf->GetNumLines($guarantorAddress, $addressColWidths['주소']);
			$guarantorNameLines = $pdf->GetNumLines($guarantor_name, $addressColWidths['채권자명']);
			$guarantorMaxLines = max($guarantorAddressLines, $guarantorNameLines, 1);
			
			// 행 높이 계산
			$guarantorLineHeight = 7 * $guarantorMaxLines;
			
			// 행의 시작 위치 저장
			$guarantorRowStartX = $pdf->GetX();
			$guarantorRowStartY = $pdf->GetY();
			
			// 순번
			$pdf->MultiCell($addressColWidths['순번'], $guarantorLineHeight, $subNum, 1, 'C');
			$pdf->SetXY($guarantorRowStartX + $addressColWidths['순번'], $guarantorRowStartY);
			
			// 보증인명
			$pdf->MultiCell($addressColWidths['채권자명'], $guarantorLineHeight, $guarantor_name, 1, 'L');
			$pdf->SetXY($guarantorRowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'], $guarantorRowStartY);
			
			// 보증인 주소
			$pdf->MultiCell($addressColWidths['주소'], $guarantorLineHeight, $guarantorAddress, 1, 'L');
			$pdf->SetXY($guarantorRowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'], $guarantorRowStartY);
			
			// 전화번호
			$pdf->MultiCell($addressColWidths['전화번호'], $guarantorLineHeight, $guarantor['guarantor_phone'] ?? '', 1, 'C');
			$pdf->SetXY($guarantorRowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'] + $addressColWidths['전화번호'], $guarantorRowStartY);
			
			// 팩스
			$pdf->MultiCell($addressColWidths['팩스'], $guarantorLineHeight, $guarantor['guarantor_fax'] ?? '', 1, 'C');
			$pdf->SetXY($guarantorRowStartX + $addressColWidths['순번'] + $addressColWidths['채권자명'] + $addressColWidths['주소'] + $addressColWidths['전화번호'] + $addressColWidths['팩스'], $guarantorRowStartY);
			
			// 비고
			$pdf->MultiCell($addressColWidths['비고'], $guarantorLineHeight, '보증인', 1, 'C');
			
			// 다음 행 시작 위치 설정
			$pdf->SetXY($guarantorRowStartX, $guarantorRowStartY + $guarantorLineHeight);
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