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
	
	// 문서 제목
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '개인회생채권자목록', 0, 1, 'C');
	$pdf->Ln(3);
	$pdf->SetFont('cid0kr', '', 8);
	
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
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 날짜 정보 출력
		$date_format = 'Y년 m월 d일';
		$calc_date = isset($settings['claim_calculation_date']) ? date($date_format, strtotime($settings['claim_calculation_date'])) : '______년__월__일';
		$list_date = isset($settings['list_creation_date']) ? date($date_format, strtotime($settings['list_creation_date'])) : '______년__월__일';
		
		// 날짜 출력 (테이블 형식)
		$pdf->SetLineWidth(0.1);
		$pdf->Cell(90, 8, '채권현재액 산정기준일: '.$calc_date, 0, 0, 'L');
		$pdf->Cell(80, 8, '목록 작성일: '.$list_date, 0, 1, 'R');
		$pdf->Ln(1);
		
		// 법률 관련 참고사항
		$pdf->Cell(0, 5, '※ 개시 후 이자 등: 이자 및 지연손해금 개시결정일 이후의 이자, 지연손해료 등은 채무자 회생 및 파산에 관한', 0, 1, 'L');
		$pdf->Cell(0, 5, '     (법률 제581조제2항, 제449조제1항제1호제2조의 준용에 해당됩니다.', 0, 1, 'L');
		$pdf->Ln(1);
		
		// 총채권액 계산
		$stmt = $pdo->prepare("
			SELECT 
				SUM(principal) as total_principal,
				SUM(interest) as total_interest,
				SUM(CASE WHEN priority_payment = 1 THEN principal + interest ELSE 0 END) as secured_total,
				SUM(CASE WHEN priority_payment = 0 THEN principal + interest ELSE 0 END) as unsecured_total
			FROM application_recovery_creditor 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$totals = $stmt->fetch(PDO::FETCH_ASSOC);

		$total_principal = $totals['total_principal'] ?? 0;
		$total_interest = $totals['total_interest'] ?? 0;
		$total_amount = $total_principal + $total_interest;
		$secured_total = $totals['secured_total'] ?? 0;
		$unsecured_total = $totals['unsecured_total'] ?? 0;

		// 테이블 너비 설정
		$left_col_width1 = 18; // 왼쪽 테이블의 첫 번째 열 너비
		$left_col_width2 = 30; // 왼쪽 테이블의 두 번째 열 너비
		$right_col_width = 26; // 오른쪽 테이블의 각 열 너비
		$table_gap = 2; // 테이블 사이 간격
		$row_height = 7; // 행 높이
		$right_table_height = $row_height * 3; // 오른쪽 테이블 총 높이

		// 왼쪽 테이블 - 첫 번째 행 (채권현재액 총합계) - 줄바꿈 적용
		$pdf->MultiCell($left_col_width1, $row_height, "채권현재액\n총합계", 1, 'C', false, 0);
		$pdf->Cell($left_col_width2, $row_height, number_format($total_amount).'원', 1, 0, 'C');

		// 테이블 사이 간격
		$pdf->Cell($table_gap, $row_height * 2, '', 0, 0, 'C');

		// 오른쪽 테이블 시작 위치 저장
		$x = $pdf->GetX();
		$y = $pdf->GetY();

		// 오른쪽 테이블 테두리와 셀 생성
		$pdf->Cell($right_col_width, $right_table_height, '', 1, 0, 'C');
		$pdf->Cell($right_col_width * 1.5, $right_table_height, '', 1, 0, 'C');
		$pdf->Cell($right_col_width, $right_table_height, '', 1, 0, 'C');
		$pdf->Cell($right_col_width * 1.5, $right_table_height, '', 1, 0, 'C');

		// 오른쪽 테이블 내용 채우기 (세로 중앙 정렬)
		$pdf->SetXY($x, $y);
		$pdf->MultiCell($right_col_width, $right_table_height, "담보부 회생\n채권현재액 합계", 0, 'C', false, 0, '', '', true, 0, false, true, $right_table_height, 'M');
		$pdf->SetXY($x + $right_col_width, $y);
		$pdf->MultiCell($right_col_width * 1.5, $right_table_height, number_format($secured_total)."원", 0, 'C', false, 0, '', '', true, 0, false, true, $right_table_height, 'M');
		$pdf->SetXY($x + $right_col_width * 2.5, $y);
		$pdf->MultiCell($right_col_width, $right_table_height, "무담보 회생\n채권현재액 합계", 0, 'C', false, 0, '', '', true, 0, false, true, $right_table_height, 'M');
		$pdf->SetXY($x + $right_col_width * 3.5, $y);
		$pdf->MultiCell($right_col_width * 1.5, $right_table_height, number_format($unsecured_total)."원", 0, 'C', false, 0, '', '', true, 0, false, true, $right_table_height, 'M');

		// Y 위치 조정
		$pdf->SetY($y + $right_table_height);

		// 왼쪽 테이블 - 두 번째 행 (원금의 합계)
		$pdf->SetY($y + $row_height * 1);
		$pdf->SetX(15); // 왼쪽 여백으로 이동
		$pdf->Cell($left_col_width1, $row_height, '원금의 합계', 1, 0, 'C');
		$pdf->Cell($left_col_width2, $row_height, number_format($total_principal).'원', 1, 1, 'C');

		// 왼쪽 테이블 - 세 번째 행 (이자의 합계)
		$pdf->SetX(15); // 왼쪽 여백으로 이동
		$pdf->Cell($left_col_width1, $row_height, '이자의 합계', 1, 0, 'C');
		$pdf->Cell($left_col_width2, $row_height, number_format($total_interest).'원', 1, 1, 'C');

		$pdf->Ln(5);
		
		// A4 용지에 맞는 열 너비 계산 (여백 제외하고 약 190mm 사용 가능)
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

		// 전체 높이 32mm
		// 각 행 높이 8mm
		$pdf->SetFont('cid0kr', 'B', 8);
		
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
		
		// 채권자 정보 가져오기
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor 
			WHERE case_no = ? 
			ORDER BY creditor_count ASC
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($creditors)) {
			$pdf->SetFont('cid0kr', '', 7);
			$pdf->Cell(180, 7, '등록된 채권자 정보가 없습니다.', 1, 1, 'C');
		} else {
			// 채권자 정보 출력
			foreach ($creditors as $creditor) {
				$pdf->SetFont('cid0kr', '', 7);
				// 새 페이지 확인 - 현재 페이지에 공간이 충분하지 않으면 새 페이지 추가
				if ($pdf->GetY() + 40 > $pdf->getPageHeight() - 20) {
					$pdf->AddPage();
					$pdf->SetFont('cid0kr', '', 7);
				}
				
				// 채권자 테이블 높이 설정
				$tableHeight = 40;
				
				// 1. 채권번호 열
				$pdf->MultiCell($col1_width, $tableHeight, $creditor['creditor_count'], 1, 'C', false, 0, '', '', true, 0, false, true, $tableHeight, 'M');
				
				// 2. 채권자 열
				$pdf->MultiCell($col2_width, $tableHeight, $creditor['financial_institution'], 1, 'C', false, 0, '', '', true, 0, false, true, $tableHeight, 'M');
				
				// 현재 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 3. 채권의 원인 열
				$pdf->MultiCell($col3_width, 8, $creditor['claim_reason']."\n", 1, 'L', false, 0, '', '', true, 0, false, true, 8, 'M');
				
				// 4. 주소 및 연락처 열
				$pdf->MultiCell($col4_width, 8, "(주소) ".$creditor['address']."\n(전화) ".$creditor['phone']."           (팩스) ".$creditor['fax'], 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');
				
				// 5. 채권의 내용 행
				$pdf->Cell($col1_width + $col2_width, 16, '', 0, 0); // 빈 셀 (채권번호, 채권자 자리)
				$pdf->MultiCell($col5_width, 16, $creditor['claim_content'], 1, 'L', false, 0, '', '', true, 0, false, true, 16, 'M');
				
				// 6. 부속서류 유무 행
				// 부속서류 체크 - 실제 데이터 가져오기
				$checkBox = '';
				$stmt = $pdo->prepare("
					SELECT COUNT(*) as count FROM application_recovery_creditor_appendix 
					WHERE case_no = ? AND creditor_count = ?
				");
				$stmt->execute([$case_no, $creditor['creditor_count']]);
				$appendixCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
				
				// 체크박스 표시
				if ($appendixCount > 0) {
					$checkBox = "☑  부속서류\n(1, 2, 3, 4)";
				} else {
					$checkBox = "□  부속서류\n(1, 2, 3, 4)";
				}
				$pdf->MultiCell($col6_width, 16, $checkBox, 1, 'C', false, 1, '', '', true, 0, false, true, 16, 'M');
				
				// 7. 채권현재액(원금) 행
				$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0); // 빈 셀 (채권번호, 채권자 자리)
				$pdf->MultiCell($col7_width, 8, number_format($creditor['principal']).'원', 1, 'R', false, 0, '', '', true, 0, false, true, 8, 'M');
				
				// 8. 채권현재액(원금) 산정근거 행
				$pdf->MultiCell($col8_width, 8, $creditor['principal_calculation'], 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');
				
				// 9. 채권현재액(이자) 행
				$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0); // 빈 셀 (채권번호, 채권자 자리)
				$pdf->MultiCell($col7_width, 8, number_format($creditor['interest']).'원', 1, 'R', false, 0, '', '', true, 0, false, true, 8, 'M');
				
				// 10. 채권현재액(이자) 산정근거 행
				$pdf->MultiCell($col8_width, 8, $creditor['interest_calculation'], 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');
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