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
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '채권자 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 설정 정보 가져오기
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor_settings 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($settings) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '목록작성일: ' . ($settings['list_creation_date'] ? date('Y년 m월 d일', strtotime($settings['list_creation_date'])) : ''), 0, 1, 'R');
			$pdf->Cell(0, 7, '채권현재액산정기준일: ' . ($settings['claim_calculation_date'] ? date('Y년 m월 d일', strtotime($settings['claim_calculation_date'])) : ''), 0, 1, 'R');
			$pdf->Ln(3);
		}
		
		// 채권자 정보 가져오기
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor 
			WHERE case_no = ? 
			ORDER BY creditor_count ASC
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($creditors)) {
			$pdf->SetFont('cid0kr', '', 12);
			$pdf->Cell(0, 10, '등록된 채권자 정보가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 합계 계산 변수
		$total_principal = 0;
		$total_interest = 0;
		$total_amount = 0;
		$secured_total = 0;
		$unsecured_total = 0;
		
		// 각 채권자 정보 출력
		foreach ($creditors as $index => $creditor) {
			// 페이지 넘침 확인 및 새 페이지 추가
			if ($pdf->GetY() > 230 && $index > 0) {
				$pdf->AddPage();
				$pdf->SetFont('cid0kr', 'B', 12);
				$pdf->Cell(0, 10, '채권자 목록 (계속)', 0, 1, 'C');
				$pdf->Ln(5);
			}
			
			// 채권자 기본 정보 헤더
			$pdf->SetFont('cid0kr', 'B', 12);
			$pdf->Cell(0, 10, ($index + 1) . '. ' . $creditor['financial_institution'], 0, 1, 'L');
			
			// 채권자 상세 정보
			$pdf->SetFont('cid0kr', '', 10);
			
			// 채권자 정보 테이블
			$pdf->SetFillColor(235, 235, 235);
			
			// 기본 정보 행 (주소, 연락처)
			$pdf->Cell(30, 7, '주소', 1, 0, 'C', true);
			$pdf->Cell(150, 7, $creditor['address'], 1, 1, 'L');
			
			$pdf->Cell(30, 7, '연락처', 1, 0, 'C', true);
			$contact_info = '전화: ' . (empty($creditor['phone']) ? '-' : formatPhoneNumber($creditor['phone']));
			if (!empty($creditor['fax'])) {
				$contact_info .= ', 팩스: ' . $creditor['fax'];
			}
			$pdf->Cell(150, 7, $contact_info, 1, 1, 'L');
			
			// 채권 정보 행 (원금, 이자, 총액)
			$pdf->Cell(30, 7, '원금', 1, 0, 'C', true);
			$pdf->Cell(150, 7, number_format($creditor['principal']) . '원', 1, 1, 'L');
			
			$pdf->Cell(30, 7, '이자', 1, 0, 'C', true);
			$pdf->Cell(150, 7, number_format($creditor['interest']) . '원', 1, 1, 'L');
			
			$total = $creditor['principal'] + $creditor['interest'];
			$pdf->Cell(30, 7, '총액', 1, 0, 'C', true);
			$pdf->Cell(150, 7, number_format($total) . '원', 1, 1, 'L');
			
			// 채권 원인 및 내용
			$pdf->Cell(30, 7, '채권원인', 1, 0, 'C', true);
			$pdf->Cell(150, 7, $creditor['claim_reason'], 1, 1, 'L');
			
			// MultiCell로 변경하여 긴 내용 처리
			$pdf->Cell(30, 7, '채권내용', 1, 0, 'C', true);
			$content_x = $pdf->GetX();
			$content_y = $pdf->GetY();
			$content_w = 150;
			$pdf->MultiCell($content_w, 7, $creditor['claim_content'], 1, 'L');
			
			// 연체이율
			$pdf->Cell(30, 7, '연체이율', 1, 0, 'C', true);
			$pdf->Cell(150, 7, '연 ' . $creditor['default_rate'] . '%', 1, 1, 'L');
			
			// 구분 (담보/무담보)
			$security_type = $creditor['priority_payment'] == 1 ? '담보채권' : '무담보채권';
			$pdf->Cell(30, 7, '구분', 1, 0, 'C', true);
			$pdf->Cell(150, 7, $security_type, 1, 1, 'L');
			
			// 추가 옵션 체크사항
			$options = [];
			if ($creditor['undetermined_claim'] == 1) $options[] = '미확정채권';
			if ($creditor['pension_debt'] == 1) $options[] = '연금채무';
			if ($creditor['mortgage_restructuring'] == 1) $options[] = '주택담보대출채권';
			
			if (!empty($options)) {
				$pdf->Cell(30, 7, '추가 구분', 1, 0, 'C', true);
				$pdf->Cell(150, 7, implode(', ', $options), 1, 1, 'L');
			}
			
			// 별제권부채권 정보 가져오기
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_appendix 
				WHERE case_no = ? AND creditor_count = ? AND appendix_type = '별제권부채권'
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$separateBonds = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($separateBonds)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 별제권부채권 정보', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				foreach ($separateBonds as $bond) {
					$pdf->MultiCell(180, 7, $bond['content'], 1, 'L');
				}
			}
			
			// 다툼있는채권 정보 가져오기
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_appendix 
				WHERE case_no = ? AND creditor_count = ? AND appendix_type = '다툼있는채권'
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$disputedClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($disputedClaims)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 다툼있는채권 정보', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				foreach ($disputedClaims as $claim) {
					$pdf->MultiCell(180, 7, $claim['content'], 1, 'L');
				}
			}
			
			// 전부명령된 채권
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_appendix 
				WHERE case_no = ? AND creditor_count = ? AND appendix_type = '전부명령된채권'
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$garnishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($garnishments)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 전부명령된채권 정보', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				foreach ($garnishments as $garnishment) {
					$pdf->MultiCell(180, 7, $garnishment['content'], 1, 'L');
				}
			}
			
			// 기타 부속서류
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_appendix 
				WHERE case_no = ? AND creditor_count = ? AND appendix_type = '기타'
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$otherAppendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($otherAppendices)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 기타 부속서류', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				foreach ($otherAppendices as $appendix) {
					$pdf->MultiCell(180, 7, $appendix['content'], 1, 'L');
				}
			}
			
			// 기타미확정채권
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_other_claims 
				WHERE case_no = ? AND creditor_count = ?
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$otherClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($otherClaims)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 기타미확정채권', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				foreach ($otherClaims as $claim) {
					$pdf->Cell(40, 7, '채권종류: ' . $claim['claim_type'], 1, 0, 'L');
					$pdf->Cell(50, 7, '금액: ' . number_format($claim['amount']) . '원', 1, 0, 'L');
					$pdf->Cell(90, 7, '변제기: ' . $claim['payment_term'], 1, 1, 'L');
					if (!empty($claim['description'])) {
						$pdf->Cell(30, 7, '설명:', 1, 0, 'L');
						$pdf->MultiCell(150, 7, $claim['description'], 1, 'L');
					}
				}
			}
			
			// 보증인이 있는 채무
			$stmt = $pdo->prepare("
				SELECT * FROM application_recovery_creditor_guaranteed_debts 
				WHERE case_no = ? AND creditor_count = ?
			");
			$stmt->execute([$case_no, $creditor['creditor_count']]);
			$guaranteedDebts = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($guaranteedDebts)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(180, 7, '▶ 보증인이 있는 채무', 1, 1, 'L', true);
				$pdf->SetFont('cid0kr', '', 9);
				
				$pdf->Cell(50, 7, '보증인', 1, 0, 'C', true);
				$pdf->Cell(80, 7, '주소', 1, 0, 'C', true);
				$pdf->Cell(50, 7, '보증금액', 1, 1, 'C', true);
				
				foreach ($guaranteedDebts as $debt) {
					$pdf->Cell(50, 7, $debt['guarantor_name'], 1, 0, 'L');
					$pdf->Cell(80, 7, $debt['guarantor_address'], 1, 0, 'L');
					$pdf->Cell(50, 7, number_format($debt['guarantee_amount']) . '원', 1, 1, 'R');
				}
			}
			
			// 합계 계산
			$total_principal += $creditor['principal'];
			$total_interest += $creditor['interest'];
			$total_amount += ($creditor['principal'] + $creditor['interest']);
			
			if ($creditor['priority_payment'] == 1) {
				$secured_total += ($creditor['principal'] + $creditor['interest']);
			} else {
				$unsecured_total += ($creditor['principal'] + $creditor['interest']);
			}
			
			// 구분선 추가
			$pdf->Ln(10);
		}
		
		// 새 페이지에 합계 정보 추가
		$pdf->AddPage();
		$pdf->SetFont('cid0kr', 'B', 14);
		$pdf->Cell(0, 10, '채권자 총 현황', 0, 1, 'C');
		$pdf->Ln(5);
		
		// 채권자수, 채권총액 요약
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(180, 10, '채권자 수: ' . count($creditors) . '명', 0, 1, 'L');
		$pdf->Ln(5);
		
		// 채권총액 테이블
		$pdf->SetFillColor(235, 235, 235);
		$pdf->SetFont('cid0kr', 'B', 11);
		
		// 테이블 헤더
		$pdf->Cell(60, 10, '구분', 1, 0, 'C', true);
		$pdf->Cell(60, 10, '금액', 1, 0, 'C', true);
		$pdf->Cell(60, 10, '비율', 1, 1, 'C', true);
		
		// 원금 합계
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(60, 8, '원금 합계', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($total_principal) . '원', 1, 0, 'R');
		$principal_ratio = $total_amount > 0 ? ($total_principal / $total_amount * 100) : 0;
		$pdf->Cell(60, 8, number_format($principal_ratio, 2) . '%', 1, 1, 'R');
		
		// 이자 합계
		$pdf->Cell(60, 8, '이자 합계', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($total_interest) . '원', 1, 0, 'R');
		$interest_ratio = $total_amount > 0 ? ($total_interest / $total_amount * 100) : 0;
		$pdf->Cell(60, 8, number_format($interest_ratio, 2) . '%', 1, 1, 'R');
		
		// 전체 합계
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(60, 8, '채권 총액', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($total_amount) . '원', 1, 0, 'R');
		$pdf->Cell(60, 8, '100.00%', 1, 1, 'R');
		
		$pdf->Ln(10);
		
		// 담보/무담보 구분 테이블
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(180, 10, '담보/무담보 구분', 0, 1, 'L');
		$pdf->Ln(2);
		
		$pdf->SetFont('cid0kr', 'B', 11);
		$pdf->Cell(60, 10, '구분', 1, 0, 'C', true);
		$pdf->Cell(60, 10, '금액', 1, 0, 'C', true);
		$pdf->Cell(60, 10, '비율', 1, 1, 'C', true);
		
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(60, 8, '담보채권', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($secured_total) . '원', 1, 0, 'R');
		$secured_ratio = $total_amount > 0 ? ($secured_total / $total_amount * 100) : 0;
		$pdf->Cell(60, 8, number_format($secured_ratio, 2) . '%', 1, 1, 'R');
		
		$pdf->Cell(60, 8, '무담보채권', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($unsecured_total) . '원', 1, 0, 'R');
		$unsecured_ratio = $total_amount > 0 ? ($unsecured_total / $total_amount * 100) : 0;
		$pdf->Cell(60, 8, number_format($unsecured_ratio, 2) . '%', 1, 1, 'R');
		
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(60, 8, '합계', 1, 0, 'L');
		$pdf->Cell(60, 8, number_format($total_amount) . '원', 1, 0, 'R');
		$pdf->Cell(60, 8, '100.00%', 1, 1, 'R');
		
		$pdf->Ln(10);
		
		// 유의사항
		$pdf->SetFont('cid0kr', 'B', 11);
		$pdf->Cell(0, 10, '◈ 유의사항', 0, 1, 'L');
		
		$pdf->SetFont('cid0kr', '', 10);
		$notice = "1. 본 채권자 목록은 신청인이 제출한 자료를 기반으로 작성되었습니다.\n";
		$notice .= "2. 담보채권 15억원 이상 또는 무담보채권 10억원 이상인 경우 개인회생신청 대상에서 제외될 수 있습니다.\n";
		$notice .= "3. 채권자의 주소, 연락처 등 정보가 정확하지 않을 경우 회생 절차에 지장이 있을 수 있습니다.\n";
		$notice .= "4. 본 목록에 기재되지 않은 채권은 면책 대상에서 제외될 수 있으므로, 모든 채권을 정확히 기재해야 합니다.";
		
		$pdf->MultiCell(0, 7, $notice, 0, 'L');
		
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