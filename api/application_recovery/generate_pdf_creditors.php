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
	// 기본 설정
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 16);
	
	// 문서 제목
	$pdf->Cell(0, 10, '개인회생채권자목록', 0, 1, 'C');
	$pdf->Ln(5);
	
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
		$pdf->Cell(100, 8, '채권현재액 산정기준일: '.$calc_date, 0, 0, 'L');
		$pdf->Cell(90, 8, '목록 작성일: '.$list_date, 0, 1, 'R');
		$pdf->Ln(2);
		
		// 1행: 채권자정보, 은행 구분
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(80, 10, '채권자정보', 1, 0, 'C');
		$pdf->Cell(60, 10, '담보부 채권 채권자의 일체', 1, 0, 'C');
		$pdf->Cell(60, 10, '무담보 채권 채권자의 일체', 1, 1, 'C');
		
		// 2행: 일반의 일체, 기타의 일체
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(80, 10, '일반의 일체', 1, 0, 'C');
		$pdf->Cell(60, 10, '', 1, 0, 'C');
		$pdf->Cell(60, 10, '', 1, 1, 'C');
		
		// 3행: 기타의 일체
		$pdf->Cell(80, 10, '기타의 일체', 1, 0, 'C');
		$pdf->Cell(60, 10, '', 1, 0, 'C');
		$pdf->Cell(60, 10, '', 1, 1, 'C');
		
		// 법률 관련 참고사항
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Cell(0, 5, '※ 개시 후 이자 등: 이자 및 지연손해금 개시결정일 이후의 이자, 지연손해료 등은 채무자 회생 및 파산에 관한', 0, 1, 'L');
		$pdf->Cell(0, 5, '   법률 제581조제2항, 제449조제1항제1호제2조의 준용에 해당됩니다.', 0, 1, 'L');
		$pdf->Ln(2);
		
		// 채권자 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 10);
		
		// 채권자 테이블 - 제목 행
		$pdf->Cell(15, 15, '채권번호', 1, 0, 'C');
		$pdf->Cell(25, 15, '채권자', 1, 0, 'C');
		$pdf->Cell(80, 15, '채권의 원인', 1, 0, 'C');
		$pdf->Cell(80, 15, '주소 및 연락 가능한 전화번호', 1, 1, 'C');
		
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
			$pdf->Cell(200, 10, '등록된 채권자 정보가 없습니다.', 1, 1, 'C');
		} else {
			// 각 채권자 정보 출력
			$pdf->SetFont('cid0kr', '', 9);
			
			foreach ($creditors as $creditor) {
				// 높이 설정
				$rowHeight = 7;
				$contentRows = 6; // 내용 행 수
				$totalHeight = $rowHeight * $contentRows;
				
				// 채권자정보 (왼쪽 2칸)
				$pdf->Cell(15, $totalHeight, $creditor['creditor_count'], 1, 0, 'C');
				$pdf->Cell(25, $totalHeight, $creditor['financial_institution'], 1, 0, 'C');
				
				// 채권의 원인 열 - 여러 행으로 구성
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				$width = 80;
				
				// 원인 칸 그리기
				$pdf->Cell($width, $totalHeight, '', 1, 0);
				
				// 원인 내용 넣기
				$pdf->SetXY($x, $y);
				
				// 채권현재액(원금) 행
				$pdf->Cell($width, $rowHeight, '채권현재액(원금): '.number_format($creditor['principal']).'원', 0, 2, 'L');
				$pdf->SetX($x);
				$pdf->Cell($width, $rowHeight, '채권현재액(원금) 산정근거', 0, 2, 'L');
				
				// 채권현재액(이자) 행
				$pdf->SetX($x);
				$pdf->Cell($width, $rowHeight, '채권현재액(이자): '.number_format($creditor['interest']).'원', 0, 2, 'L');
				$pdf->SetX($x);
				$pdf->Cell($width, $rowHeight, '채권현재액(이자) 산정근거', 0, 2, 'L');
				
				// 수수료 및 담보 정보
				$pdf->SetX($x);
				$pdf->Cell($width/2, $rowHeight, '(수수)', 0, 0, 'C');
				$pdf->Cell($width/2, $rowHeight, '(팩스)', 0, 2, 'C');
				$pdf->SetX($x);
				$pdf->Cell($width/2, $rowHeight, '(전화)', 0, 0, 'C');
				$pdf->Cell($width/2, $rowHeight, '', 'B', 2, 'C'); // 밑줄 추가
				
				// 주소 및 연락처 칸
				$x = $pdf->GetX();
				$y = $pdf->GetY() - $rowHeight * 5; // 원래 Y 위치로 돌아가기
				$pdf->SetXY($x, $y);
				$width = 80;
				
				// 주소 칸 그리기
				$pdf->Cell($width, $totalHeight, '', 1, 0);
				
				// 주소 및 연락처 내용 넣기
				$pdf->SetXY($x, $y);
				$pdf->MultiCell($width, $rowHeight * 4, $creditor['address']."\n전화: ".formatPhoneNumber($creditor['phone']), 0, 'L');
				
				// 부속서류 유무 체크박스
				$pdf->SetXY($x, $y + $rowHeight * 4);
				$pdf->Cell($width, $rowHeight * 2, '□ 부속서류 (1, 2, 3, 4)', 0, 0, 'R');
				
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