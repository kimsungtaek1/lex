<?php
// generate_pdf_creditors.php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

/**
 * 채권자 목록 출력 함수
 * @param TCPDF $pdf PDF 객체
 * @param PDO $pdo 데이터베이스 연결 객체
 * @param int $case_no 사건 번호
 */
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
			$pdf->SetFont('cid0kr', '', 12);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 날짜 정보 출력
		$pdf->SetFont('cid0kr', '', 10);
		$date_format = 'Y년 m월 d일';
		$calc_date = isset($settings['claim_calculation_date']) ? date($date_format, strtotime($settings['claim_calculation_date'])) : '______년__월__일';
		$list_date = isset($settings['list_creation_date']) ? date($date_format, strtotime($settings['list_creation_date'])) : '______년__월__일';
		
		// 날짜 출력 (테이블 형식)
		$pdf->SetLineWidth(0.1);
		$pdf->Cell(90, 8, '채권현재액 산정기준일: '.$calc_date, 0, 0, 'L');
		$pdf->Cell(80, 8, '목록 작성일: '.$list_date, 0, 1, 'R');
		$pdf->Ln(1);
		
		// A4 용지에 맞는 테이블 너비 계산 (총 너비 180mm)
		$colWidth1 = 70; // 채권자정보 
		$colWidth2 = 55; // 담보부 채권
		$colWidth3 = 55; // 무담보 채권
		
		// 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 10);
		// 채권번호 수직 병합
		$pdf->MultiCell(10, 56, "채\n권\n번\n호", 1, 'C', false, 0);
		// 채권자 수직 병합
		$pdf->MultiCell(20, 56, "채\n권\n자", 1, 'C', false, 0);
		
		// 체권의 원인 및 주소/연락처
		$pdf->MultiCell(50, 14, "채권의 원인", 1, 'C', false, 0);
		$pdf->MultiCell(50, 14, "주소 및 연락 가능한 전화번호", 1, 'C', false, 1);
		// 체권의 원인 및 주소/연락처
		$pdf->MultiCell(50, 14, "채권의 내용", 1, 'C', false, 0);
		$pdf->MultiCell(50, 14, "부속서류 유무", 1, 'C', false, 1);
		// 두 번째 행
		$pdf->Cell(30, 14, '', 0, 0);
		$pdf->MultiCell(50, 14, "채권현재액(원금)", 1, 'C', false, 0);
		$pdf->MultiCell(50, 14, "채권현재액(원금) 산정근거", 1, 'C', false, 1);
		// 세 번째 행
		$pdf->Cell(30, 14, '', 0, 0);
		$pdf->MultiCell(50, 14, "채권현재액(이자)", 1, 'C', false, 0);
		$pdf->MultiCell(50, 14, "채권현재액(이자) 산정근거", 1, 'C', false, 1);
		

		
		// 법률 관련 참고사항
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Cell(0, 5, '※ 개시 후 이자 등: 이자 및 지연손해금 개시결정일 이후의 이자, 지연손해료 등은 채무자 회생 및 파산에 관한', 0, 1, 'L');
		$pdf->Cell(0, 5, '   법률 제581조제2항, 제449조제1항제1호제2조의 준용에 해당됩니다.', 0, 1, 'L');
		$pdf->Ln(1);
		
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