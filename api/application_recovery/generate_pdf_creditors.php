<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfCreditors($pdf, $pdo, $case_no) {
	// A4 용지에 맞게 여백 설정
	$pdf->SetMargins(15, 15, 15);
	$pdf->SetAutoPageBreak(true, 15);
	
	// 새 페이지 추가
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	
	// 문서 제목
	$pdf->Cell(0, 10, '개인회생채권자목록', 0, 1, 'C');
	$pdf->Ln(3);
	
	try {
		// 설정 정보 가져오기
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor_settings 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 기본 정보 조회
		$stmt = $pdo->prepare("
			SELECT ar.*, cm.name, cm.case_number 
			FROM application_recovery ar
			JOIN case_management cm ON ar.case_no = cm.case_no
			WHERE ar.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 날짜 정보 출력
		$pdf->SetFont('cid0kr', '', 8);
		$date_format = 'Y년 m월 d일';
		$calc_date = isset($settings['claim_calculation_date']) ? date($date_format, strtotime($settings['claim_calculation_date'])) : '______년__월__일';
		$list_date = isset($settings['list_creation_date']) ? date($date_format, strtotime($settings['list_creation_date'])) : '______년__월__일';
		
		// 날짜 출력 (테이블 형식)
		$pdf->SetLineWidth(0.1);
		$pdf->Cell(90, 8, '채권현재액 산정기준일: '.$calc_date, 0, 0, 'L');
		$pdf->Cell(80, 8, '목록 작성일: '.$list_date, 0, 1, 'R');
		$pdf->Ln(1);
		
		// 법률 관련 참고사항
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Cell(0, 5, '※ 개시 후 이자 등: 이자 및 지연손해금 개시결정일 이후의 이자, 지연손해료 등은 채무자 회생 및 파산에 관한', 0, 1, 'L');
		$pdf->Cell(0, 5, '   법률 제581조제2항, 제449조제1항제1호제2조의 준용에 해당됩니다.', 0, 1, 'L');
		$pdf->Ln(1);
		
		// 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 8);
		// A4 용지에 맞는 열 너비 계산 (여백 제외하고 약 190mm 사용 가능)
		// 열 너비를 비율에 맞게 조정
		$col1_width = 10;
		$col2_width = 20;
		$col3_width = 50;
		$col4_width = 100;
		$col5_width = 120;
		$col6_width = 30;
		$col7_width = 30;
		$col8_width = 120;
		
		// 텍스트 세로 중앙 정렬을 위한 스타일 설정
		$pdf->setCellPaddings(1, 2, 1, 2); // 셀 내부 여백 설정 (좌, 상, 우, 하) - 상하 여백 줄임
		$pdf->SetCellHeightRatio(1.3); // 줄 간격 비율 설정 - 비율 줄임

		// 전체 높이 28mm (기존 56mm의 절반)
		// 각 행 높이 7mm (기존 14mm의 절반)

		// 채권번호 수직 병합 - 세로 중앙 정렬
		$pdf->MultiCell($col1_width, 32, "채\n권\n번\n호", 1, 'C', false, 0, '', '', true, 0, false, true, 32, 'M');
		// 채권자 수직 병합 - 세로 중앙 정렬
		$pdf->MultiCell($col2_width, 32, "채\n권\n자", 1, 'C', false, 0, '', '', true, 0, false, true, 32, 'M');
		// 첫 번째 행 - 세로 중앙 정렬
		$pdf->MultiCell($col3_width, 8, "채권의 원인", 1, 'C', false, 0, '', '', true, 0, false, true, 8, 'M');
		$pdf->MultiCell($col4_width, 8, "주소 및 연락 가능한 전화번호", 1, 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
		// 두 번째 행 - 세로 중앙 정렬
		$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0); // 앞 두 열은 이미 병합됨
		$pdf->MultiCell($col5_width, 8, "채권의 내용", 1, 'C', false, 0, '', '', true, 0, false, true, 8, 'M');
		$pdf->MultiCell($col6_width, 8, "부속서류 유무", 1, 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
		// 세 번째 행 - 세로 중앙 정렬
		$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0); // 앞 두 열은 이미 병합됨
		$pdf->MultiCell($col7_width, 8, "채권현재액(원금)", 1, 'C', false, 0, '', '', true, 0, false, true, 8, 'M');
		$pdf->MultiCell($col8_width, 8, "채권현재액(원금) 산정근거", 1, 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
		// 네 번째 행 - 세로 중앙 정렬
		$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0); // 앞 두 열은 이미 병합됨
		$pdf->MultiCell($col7_width, 8, "채권현재액(이자)", 1, 'C', false, 0, '', '', true, 0, false, true, 8, 'M');
		$pdf->MultiCell($col8_width, 8, "채권현재액(이자) 산정근거", 1, 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
		// 셀 패딩 및 높이 비율 원래대로 복원
		$pdf->setCellPaddings(1, 1, 1, 1);
		$pdf->SetCellHeightRatio(1.25);
		

		
		
		
		// 채권자 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 10);
		
		// 채권자 테이블 - 제목 행 (A4 용지에 맞게 조정된 열 너비)
		$w1 = 14;  // 채권번호
		$w2 = 24;  // 채권자
		$w3 = 72;  // 채권의 원인
		$w4 = 70;  // 주소 및 연락처
		
		// 채권자 목록 테이블 헤더
		$pdf->Cell($w1, 12, '채권번호', 1, 0, 'C');
		$pdf->Cell($w2, 12, '채권자', 1, 0, 'C');
		$pdf->Cell($w3, 12, '채권의 원인', 1, 0, 'C');
		$pdf->Cell($w4, 12, '주소 및 연락 가능한 전화번호', 1, 1, 'C');
		
		// 채권자 정보 가져오기
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor 
			WHERE case_no = ? 
			ORDER BY creditor_count ASC
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($creditors)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell($w1 + $w2 + $w3 + $w4, 10, '등록된 채권자 정보가 없습니다.', 1, 1, 'C');
		} else {
			// 각 채권자 정보 출력
			$pdf->SetFont('cid0kr', '', 9);
			
			foreach ($creditors as $creditor) {
				// 높이 설정 - 셀 높이를 줄여 A4에 맞게 조정
				$rowHeight = 6;
				$contentRows = 6; // 내용 행 수
				$totalHeight = $rowHeight * $contentRows;
				
				// 새 페이지 확인 - 현재 페이지에 공간이 충분하지 않으면 새 페이지 추가
				if ($pdf->GetY() + $totalHeight > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
					$pdf->SetFont('cid0kr', 'B', 10);
					$pdf->Cell($w1, 12, '채권번호', 1, 0, 'C');
					$pdf->Cell($w2, 12, '채권자', 1, 0, 'C');
					$pdf->Cell($w3, 12, '채권의 원인', 1, 0, 'C');
					$pdf->Cell($w4, 12, '주소 및 연락 가능한 전화번호', 1, 1, 'C');
					$pdf->SetFont('cid0kr', '', 9);
				}
				
				// 채권자정보 (왼쪽 2칸)
				$pdf->Cell($w1, $totalHeight, $creditor['creditor_count'], 1, 0, 'C');
				$pdf->Cell($w2, $totalHeight, $creditor['financial_institution'], 1, 0, 'C');
				
				// 채권의 원인 열 - 여러 행으로 구성
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 원인 칸 그리기
				$pdf->Cell($w3, $totalHeight, '', 1, 0);
				
				// 원인 내용 넣기
				$pdf->SetXY($x, $y);
				
				// 채권현재액(원금) 행
				$pdf->Cell($w3, $rowHeight, '채권현재액(원금): '.number_format($creditor['principal']).'원', 0, 2, 'L');
				$pdf->SetX($x);
				$pdf->Cell($w3, $rowHeight, '채권현재액(원금) 산정근거', 0, 2, 'L');
				
				// 채권현재액(이자) 행
				$pdf->SetX($x);
				$pdf->Cell($w3, $rowHeight, '채권현재액(이자): '.number_format($creditor['interest']).'원', 0, 2, 'L');
				$pdf->SetX($x);
				$pdf->Cell($w3, $rowHeight, '채권현재액(이자) 산정근거', 0, 2, 'L');
				
				// 수수료 및 담보 정보
				$pdf->SetX($x);
				$pdf->Cell($w3/2, $rowHeight, '(수수)', 0, 0, 'C');
				$pdf->Cell($w3/2, $rowHeight, '(팩스)', 0, 2, 'C');
				$pdf->SetX($x);
				$pdf->Cell($w3/2, $rowHeight, '(전화)', 0, 0, 'C');
				$pdf->Cell($w3/2, $rowHeight, '', 'B', 2, 'C'); // 밑줄 추가
				
				// 주소 및 연락처 칸
				$x = $pdf->GetX();
				$y = $pdf->GetY() - $rowHeight * 5; // 원래 Y 위치로 돌아가기
				$pdf->SetXY($x, $y);
				
				// 주소 칸 그리기
				$pdf->Cell($w4, $totalHeight, '', 1, 0);
				
				// 주소 및 연락처 내용 넣기
				$pdf->SetXY($x, $y);
				$pdf->MultiCell($w4, $rowHeight * 4, $creditor['address']."\n전화: ".formatPhoneNumber($creditor['phone']), 0, 'L');
				
				// 부속서류 유무 체크박스
				$pdf->SetXY($x, $y + $rowHeight * 4);
				$pdf->Cell($w4, $rowHeight * 2, '□ 부속서류 (1, 2, 3, 4)', 0, 0, 'R');
				
				$pdf->Ln($totalHeight);
			}
		}
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '채권자 정보 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
		error_log('PDF 채권자 목록 생성 오류: ' . $e->getMessage());
	}
}

/**
 * 전화번호 포맷팅 함수
 */
function formatPhoneNumber($phone) {
	if (empty($phone)) return '';
	
	$phone = preg_replace('/[^0-9]/', '', $phone);
	
	if (strlen($phone) === 10) {
		return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
	} elseif (strlen($phone) === 11) {
		return substr($phone, 0, 3) . '-' . substr($phone, 3, 4) . '-' . substr($phone, 7);
	} else {
		return $phone;
	}
}
?>