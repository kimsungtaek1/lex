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
		$pdf->Cell(90, 8, '목록 작성일: '.$list_date, 0, 1, 'R');
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
				// 보증채무 정보 가져오기
				$stmt_guaranteed = $pdo->prepare("
					SELECT * FROM application_recovery_creditor_guaranteed_debts
					WHERE case_no = ? AND creditor_count = ?
					ORDER BY debt_number ASC
				");
				$stmt_guaranteed->execute([$case_no, $creditor['creditor_count']]);
				$guaranteed_debts = $stmt_guaranteed->fetchAll(PDO::FETCH_ASSOC);

				$pdf->SetFont('cid0kr', '', 7);
				// 새 페이지 확인 - 현재 페이지에 공간이 충분하지 않으면 새 페이지 추가
				// 보증채무 포함 높이 계산 (기본 40 + 보증채무 개수 * 40)
				$requiredHeight = 40 + (count($guaranteed_debts) * 40);
				if ($pdf->GetY() + $requiredHeight > $pdf->getPageHeight() - 20) {
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
				$stmt_appendix = $pdo->prepare("
					SELECT COUNT(*) as count FROM application_recovery_creditor_appendix 
					WHERE case_no = ? AND creditor_count = ?
				");
				$stmt_appendix->execute([$case_no, $creditor['creditor_count']]);
				$appendixCount = $stmt_appendix->fetch(PDO::FETCH_ASSOC)['count'];

				$stmt_disputed = $pdo->prepare("
					SELECT COUNT(*) as count FROM application_recovery_creditor_other_claims 
					WHERE case_no = ? AND creditor_count = ?
				");
				$stmt_disputed->execute([$case_no, $creditor['creditor_count']]);
				$disputedCount = $stmt_disputed->fetch(PDO::FETCH_ASSOC)['count'];

				$stmt_assigned = $pdo->prepare("
					SELECT COUNT(*) as count FROM application_recovery_creditor_assigned_claims 
					WHERE case_no = ? AND creditor_count = ?
				");
				$stmt_assigned->execute([$case_no, $creditor['creditor_count']]);
				$assignedCount = $stmt_assigned->fetch(PDO::FETCH_ASSOC)['count'];

				$stmt_other = $pdo->prepare("
					SELECT COUNT(*) as count FROM application_recovery_creditor_other_debts 
					WHERE case_no = ? AND creditor_count = ?
				");
				$stmt_other->execute([$case_no, $creditor['creditor_count']]);
				$otherCount = $stmt_other->fetch(PDO::FETCH_ASSOC)['count'];

				// 체크박스 라인 구성
				$hasAppendix = ($appendixCount > 0 || $disputedCount > 0 || $assignedCount > 0 || $otherCount > 0);
				$checkBox = $hasAppendix ? "[ V]  부속서류\n" : "[  ]  부속서류\n";

				// 선택된 채권 유형에 따라 해당 번호 강조
				$num1 = $appendixCount > 0 ? "①" : "1";
				$num2 = $disputedCount > 0 ? "②" : "2";
				$num3 = $assignedCount > 0 ? "③" : "3";
				$num4 = $otherCount > 0 ? "④" : "4";

				$checkBox .= "[ {$num1}, {$num2}, {$num3}, {$num4} ]";

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

				// 보증채무 정보 출력
				if (!empty($guaranteed_debts)) {
					foreach ($guaranteed_debts as $guaranteed_debt) {
						// 새 페이지 확인 (개별 보증채무 테이블 높이 고려)
						if ($pdf->GetY() + 40 > $pdf->getPageHeight() - 20) {
							$pdf->AddPage();
							$pdf->SetFont('cid0kr', '', 7);
						}

						// 테이블 높이 설정
						$tableHeight = 40;

						// 1. 채권번호 열 (수정: 채권자번호-debt_number)
						$debtNumber = $creditor['creditor_count'] . '-' . $guaranteed_debt['debt_number'];
						$pdf->MultiCell($col1_width, $tableHeight, $debtNumber, 1, 'C', false, 0, '', '', true, 0, false, true, $tableHeight, 'M');

						// 2. 채권자 열 (수정: 채권자 이름 표시)
						$pdf->MultiCell($col2_width, $tableHeight, $creditor['financial_institution'], 1, 'C', false, 0, '', '', true, 0, false, true, $tableHeight, 'M');

						// 현재 위치 저장
						$x = $pdf->GetX();
						$y = $pdf->GetY();

						// 3. 채권의 원인 열
						$pdf->MultiCell($col3_width, 8, ($guaranteed_debt['claim_reason'] ?? '') . "\n", 1, 'L', false, 0, '', '', true, 0, false, true, 8, 'M');

						// 4. 주소 및 연락처 열 (수정: 주소, 전화, 팩스 표시)
						$address = $guaranteed_debt['address'] ?? '';
						$phone = isset($guaranteed_debt['phone']) ? formatPhoneNumber($guaranteed_debt['phone']) : '';
						$fax = $guaranteed_debt['fax'] ?? '';
						$contactInfo = "(주소) " . $address . "\n(전화) " . $phone . "           (팩스) " . $fax;
						$pdf->MultiCell($col4_width, 8, $contactInfo, 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');

						// 5. 채권의 내용 행
						$pdf->Cell($col1_width + $col2_width, 16, '', 0, 0);
						$pdf->MultiCell($col5_width, 16, $guaranteed_debt['claim_content'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, 16, 'M');

						// 6. 부속서류 유무 행 (보증채무는 해당 없음 - 빈칸 처리)
						$pdf->MultiCell($col6_width, 16, '', 1, 'C', false, 1, '', '', true, 0, false, true, 16, 'M');

						// 7. 채권현재액(원금) 행
						$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0);
						$principalText = '';
						$principalAlign = 'R'; // 기본 오른쪽 정렬
						// 대위변제선택 컬럼명을 'subrogation_type'로 가정하고, 원금과 이자가 0인지 확인
						if (($guaranteed_debt['principal'] ?? 0) == 0 && ($guaranteed_debt['interest'] ?? 0) == 0) {
							$principalText = '장래구상권 미발생';
						} else {
							$principalText = number_format($guaranteed_debt['principal'] ?? 0) . '원';
						}
						$pdf->MultiCell($col7_width, 8, $principalText, 1, $principalAlign, false, 0, '', '', true, 0, false, true, 8, 'M');

						// 8. 채권현재액(원금) 산정근거 행
						$pdf->MultiCell($col8_width, 8, $guaranteed_debt['principal_calculation'] ?? '', 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');

						// 9. 채권현재액(이자) 행
						$pdf->Cell($col1_width + $col2_width, 8, '', 0, 0);
						$interestText = '';
						$interestAlign = 'R'; // 기본 오른쪽 정렬
						// 대위변제선택 컬럼명을 'subrogation_type'로 가정하고, 원금과 이자가 0인지 확인
						if (($guaranteed_debt['principal'] ?? 0) == 0 && ($guaranteed_debt['interest'] ?? 0) == 0) {
							$interestText = '미발생';
						} else {
							$interestText = number_format($guaranteed_debt['interest'] ?? 0) . '원';
						}
						$pdf->MultiCell($col7_width, 8, $interestText, 1, $interestAlign, false, 0, '', '', true, 0, false, true, 8, 'M');

						// 10. 채권현재액(이자) 산정근거 행
						$pdf->MultiCell($col8_width, 8, $guaranteed_debt['interest_calculation'] ?? '', 1, 'L', false, 1, '', '', true, 0, false, true, 8, 'M');
					}
				}
			}
		}

	} catch (Exception $e) {
		$pdf->MultiCell(0, 10, 
			"데이터 조회 중 오류가 발생했습니다:\n" . 
			$e->getMessage() . 
			"\n\n관리자에게 문의해 주시기 바랍니다.", 
			0, 
			'C'
		);
	}

	// --- 별제권부채권 및 이에 준하는 채권의 내역 표 추가 ---
	try {
		// 별제권 관련 데이터 조회 (appendix 테이블 활용)
		$stmt_appendix_details = $pdo->prepare("
			SELECT 
				c.creditor_count,
				c.financial_institution,
				c.principal,
				c.interest,
				a.appendix_type,
				a.property_detail,
				a.expected_value,          -- 담보물의 예상 가액
				a.secured_expected_claim,  -- 별제권행사등으로 변제가 예상되는 채권액 (③)
				a.unsecured_remaining_claim, -- 별제권행사등으로도 변제를 받을 수 없을 채권액 (④)
				a.rehabilitation_secured_claim, -- 담보부 회생채권액 (⑤)
				a.updated_at,              -- 순위 결정을 위한 업데이트 시간
				a.max_claim,    -- 채권최고액 (가정)
				a.evaluation_rate          -- 평가비율 (가정)
			FROM application_recovery_creditor c
			JOIN application_recovery_creditor_appendix a 
				ON c.case_no = a.case_no AND c.creditor_count = a.creditor_count
			WHERE c.case_no = ? 
			  AND a.appendix_type IN ('(근)저당권설정', '질권설정/채권양도(전세보증금)', '최우선변제임차권', '우선변제임차권') -- 담보 관련 유형 필터링
			ORDER BY 
				CASE 
					WHEN a.appendix_type = '(근)저당권설정' THEN a.updated_at 
					ELSE NULL -- 근저당권 외에는 정렬 우선순위 없음
				END ASC, 
				c.creditor_count ASC -- 근저당권 내에서는 updated_at, 그 외에는 creditor_count로 정렬
		");
		$stmt_appendix_details->execute([$case_no]);
		$appendix_details = $stmt_appendix_details->fetchAll(PDO::FETCH_ASSOC);

		// 근저당권 순위 계산을 위한 변수
		$mortgage_rank = 1;

		if (!empty($appendix_details)) {
			$pdf->AddPage(); // 새 페이지에서 시작
			if ($pdf->GetY() + 50 > $pdf->getPageHeight() - 20) { // 표 높이 예상치
				$pdf->AddPage();
			} else {
				$pdf->Ln(10); // 기존 내용과 간격 띄우기
			}

			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '부속서류 1. 별제권부채권 및 이에 준하는 채권의 내역', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 5, '(단위 : 원)', 0, 1, 'R');
			$pdf->Ln(1);

			// 테이블 헤더 너비 설정
			$h_col1 = 10; // 채권번호
			$h_col2 = 20; // 채권자
			$h_col3 = 6; // ①, ②
			$h_col4 = 42; // ③
			$h_col5 = 42; // ④
			$h_col6 = 42; // ⑤
			$h_col7 = 42; // ⑥
			$total_width = $h_col1 + $h_col2 + $h_col3 + $h_col4 + $h_col5 + $h_col6 + $h_col7; // 전체 너비 계산

			// 헤더 그리기
			$pdf->SetFont('cid0kr', 'B', 7);
			$pdf->SetFillColor(255, 255, 255); // 헤더 배경색
			$pdf->SetLineWidth(0.1);

			// 현재 Y 위치 저장
			$startY = $pdf->GetY();

			// 첫 번째 행 - 3행 병합 셀
			$pdf->MultiCell($h_col1, 21, "채권\n번호", 1, 'C', true, 0, '', '', true, 0, false, true, 21, 'M');
			$pdf->MultiCell($h_col2, 21, "채권자", 1, 'C', true, 0, '', '', true, 0, false, true, 21, 'M');

			// 첫 번째 행 - 4열 병합
			$x_pos = $pdf->GetX();
			$pdf->MultiCell($h_col3 * 4, 7, "①채권현재액(원금)", 1, 'C', true, 1, '', '', true, 0, false, true, 7, 'M');

			// 첫 번째 행 - 2행 병합 셀들
			$pdf->SetXY($x_pos + $h_col3 * 4, $startY);
			$pdf->MultiCell($h_col4, 14, "③별제권행사등으로\n변제가 예상되는\n채권액", 1, 'C', true, 0, '', '', true, 0, false, true, 14, 'M');
			$pdf->MultiCell($h_col5, 14, "④별제권행사등으로도\n변제받을 수 없을\n채권액", 1, 'C', true, 0, '', '', true, 0, false, true, 14, 'M');
			$pdf->MultiCell($h_col6, 14, "⑤담보부\n회생채권액", 1, 'C', true, 1, '', '', true, 0, false, true, 14, 'M');

			// 두 번째 행 - 4열 병합
			$pdf->SetXY($x_pos, $startY + 7);
			$pdf->MultiCell($h_col3 * 4, 7, "②채권현재액(이자)", 1, 'C', true, 1, '', '', true, 0, false, true, 7, 'M');

			// 세 번째 행 - 7열 병합
			$pdf->SetXY($x_pos, $startY + 14);
			$pdf->MultiCell($h_col3 * 4 + $h_col4 + $h_col5 + $h_col6, 7, "⑥별제권 등의 내용 및 목적물", 1, 'C', true, 1, '', '', true, 0, false, true, 7, 'M');

			// A4 용지 크기에 맞게 열 너비 조정
			$pageWidth = $pdf->getPageWidth() - 50; // 좌우 여백 25mm씩 제외
			$h_col7 = $pageWidth - ($h_col1 + $h_col2 + $h_col3 + $h_col4 + $h_col5 + $h_col6); // 나머지 공간

			// 데이터 행 출력
			$pdf->SetFont('cid0kr', '', 7);

			$total_principal = 0;
			$total_interest = 0;
			$total_secured_expected = 0;
			$total_unsecured_remaining = 0;
			$total_rehabilitation_secured = 0;

			foreach ($appendix_details as $key => $detail) { // $key 추가하여 순위 계산에 사용
				// 새 페이지 확인
				if ($pdf->GetY() + 24 > $pdf->getPageHeight() - 20) { // 높이 여유있게 조정 필요 시 수정
					$pdf->AddPage();
					$startY = $pdf->GetY();
					
					// 헤더 다시 그리기 (이전 헤더 코드와 동일)
					$pdf->SetFont('cid0kr', 'B', 7);

					// 3행 병합 셀
					$pdf->MultiCell($h_col1, 24, "채권\n번호", 1, 'C', true, 0, '', '', true, 0, false, true, 24, 'M');
					$pdf->MultiCell($h_col2, 24, "채권자", 1, 'C', true, 0, '', '', true, 0, false, true, 24, 'M');

					$x_pos = $pdf->GetX();
					// 첫 번째 행
					$pdf->MultiCell($h_col3 * 4, 8, "①채권현재액(원금)", 1, 'C', true, 1, '', '', true, 0, false, true, 8, 'M');
					
					// 2행 병합 셀들
					$pdf->SetXY($x_pos + $h_col3 * 4, $startY);
					$pdf->MultiCell($h_col4, 16, "③별제권행사등으로\n변제가 예상되는\n채권액", 1, 'C', true, 0, '', '', true, 0, false, true, 16, 'M');
					$pdf->MultiCell($h_col5, 16, "④별제권행사등으로도\n변제받을 수 없을\n채권액", 1, 'C', true, 0, '', '', true, 0, false, true, 16, 'M');
					$pdf->MultiCell($h_col6, 16, "⑤담보부\n회생채권액", 1, 'C', true, 1, '', '', true, 0, false, true, 16, 'M');

					// 두 번째 행
					$pdf->SetXY($x_pos, $startY + 8);
					$pdf->MultiCell($h_col3 * 4, 8, "②채권현재액(이자)", 1, 'C', true, 1, '', '', true, 0, false, true, 8, 'M');

					// 세 번째 행
					$pdf->SetXY($x_pos, $startY + 16);
					$pdf->MultiCell($h_col3 * 4 + $h_col4 + $h_col5 + $h_col6, 8, "⑥별제권 등의 내용 및 목적물", 1, 'C', true, 1, '', '', true, 0, false, true, 8, 'M');
					
					$pdf->SetFont('cid0kr', '', 7);
				}

				$startY = $pdf->GetY();

				// 3행 병합 셀
				$pdf->MultiCell($h_col1, 32, $detail['creditor_count'], 1, 'C', false, 0, '', '', true, 0, false, true, 32, 'M');
				$pdf->MultiCell($h_col2, 32, $detail['financial_institution'], 1, 'L', false, 0, '', '', true, 0, false, true, 32, 'M');

				$x_pos = $pdf->GetX();
				// 첫 번째 행 - 원금
				$pdf->MultiCell($h_col3 * 4, 8, number_format($detail['principal'] ?? 0).'원', 1, 'R', false, 0, '', '', true, 0, false, true, 8, 'M');
				$pdf->MultiCell($h_col4, 16, number_format($detail['secured_expected_claim'] ?? 0).'원', 1, 'R', false, 0, '', '', true, 0, false, true, 16, 'M');
				$pdf->MultiCell($h_col5, 16, number_format($detail['unsecured_remaining_claim'] ?? 0).'원', 1, 'R', false, 0, '', '', true, 0, false, true, 16, 'M');
				$pdf->MultiCell($h_col6, 16, number_format($detail['rehabilitation_secured_claim'] ?? 0).'원', 1, 'R', false, 1, '', '', true, 0, false, true, 16, 'M');

				// 두 번째 행 - 이자
				$pdf->SetXY($x_pos, $startY + 8);
				$pdf->MultiCell($h_col3 * 4, 8, number_format($detail['interest'] ?? 0).'원', 1, 'R', false, 1, '', '', true, 0, false, true, 8, 'M');

				// 세 번째 행 - 내용 및 목적물 (수정된 내용 적용)
				$pdf->SetXY($x_pos, $startY + 16);
				
				// 순위 문자열 생성
				$rank_str = '';
				if ($detail['appendix_type'] == '(근)저당권설정') {
					// 순차적으로 순위 부여 (SQL ORDER BY 결과 활용)
					$current_rank = 1;
					for ($i = 0; $i < $key; $i++) {
						// 이전 항목들 중 같은 타입이 있으면 순위 증가
						if (isset($appendix_details[$i]) && $appendix_details[$i]['appendix_type'] == '(근)저당권설정') {
							$current_rank++;
						}
					}
					$rank_str = "   (제".$current_rank."순위)";
				} elseif (in_array($detail['appendix_type'], ['질권설정/채권양도(전세보증금)', '최우선변제임차권', '우선변제임차권'])) {
					// 기타 담보 유형은 1순위로 표시
					$rank_str = "   (제1순위)";
				}

				// 채권최고액, 환가예상액, 평가비율 가져오기 (null일 경우 0으로 처리)
				$max_claim = $detail['max_claim'] ?? 0;
				$expected_val = $detail['expected_value'] ?? 0;
				$ratio = $detail['evaluation_rate'] ?? 0; // 평가비율 (0~1 사이 값)

				// 계산
				$liquidation_value = $expected_val * $ratio * 0.01; // (환가예상액 X 평가비율)계산한 값
				$remaining_value = $liquidation_value - $max_claim; // ((환가예상액 X 평가비율)계산한 값 - 채권최고액(담보액))계산 한 값

				// 표시될 내용 구성 (요청사항 반영)
				$content = $detail['appendix_type'] . $rank_str . "\n";
				$content .= "채권최고액 : " . number_format($max_claim) . " 원\n";
				$content .= "목적물 : " . ($detail['property_detail'] ?? 'N/A') . "\n";
				// 환가예상액 라인 포맷 수정: 숫자 포맷팅 적용 및 평가비율 직접 표시
				$content .= "환가예상액 : " . number_format($expected_val) . " X " . ($ratio * 0.01) . " = " . number_format($liquidation_value) . " - " . number_format($max_claim) . " = " . number_format($remaining_value);

				// MultiCell 호출 (높이 자동 조절을 위해 0으로 설정, 또는 내용 길이에 맞게 조정 필요)
				// 기존 높이 24 유지, 내용 길이에 따라 자동 줄바꿈 되도록 함
				// ishtml 파라미터를 false로 변경하여 \n이 정상적으로 처리되도록 수정
				$pdf->MultiCell($h_col3 * 4 + $h_col4 + $h_col5 + $h_col6, 16, $content, 1, 'L', false, 1, '', '', true, 0, false, true, 0, 'T'); // ishtml: false, 수직 정렬 T(Top)

				// 합계 계산
				$total_principal += $detail['principal'] ?? 0;
				$total_interest += $detail['interest'] ?? 0;
				$total_secured_expected += $detail['secured_expected_claim'] ?? 0;
				$total_unsecured_remaining += $detail['unsecured_remaining_claim'] ?? 0;
				$total_rehabilitation_secured += $detail['rehabilitation_secured_claim'] ?? 0;
			}

			// 합계 행
			$pdf->SetFont('cid0kr', 'B', 7);

			$startY = $pdf->GetY();

			// 3행 병합 셀
			$pdf->MultiCell($h_col1 + $h_col2, 16, "합 계", 1, 'C', true, 0, '', '', true, 0, false, true, 16, 'M');

			$x_pos = $pdf->GetX();
			// 첫 번째 행 - 원금
			$pdf->MultiCell($h_col3 * 4, 8, number_format($total_principal).'원', 1, 'R', true, 0, '', '', true, 0, false, true, 8, 'M');
			$pdf->MultiCell($h_col4, 16, number_format($total_secured_expected).'원', 1, 'R', true, 0, '', '', true, 0, false, true, 16, 'M');
			$pdf->MultiCell($h_col5, 16, number_format($total_unsecured_remaining).'원', 1, 'R', true, 0, '', '', true, 0, false, true, 16, 'M');
			$pdf->MultiCell($h_col6, 16, number_format($total_rehabilitation_secured).'원', 1, 'R', true, 1, '', '', true, 0, false, true, 16, 'M');

			// 두 번째 행 - 이자
			$pdf->SetXY($x_pos, $startY + 8);
			$pdf->MultiCell($h_col3 * 4, 8, number_format($total_interest).'원', 1, 'R', true, 1, '', '', true, 0, false, true, 8, 'M');

		}

	} catch (Exception $e) {
		// 오류 발생 시 PDF에 메시지 출력 (선택 사항)
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->MultiCell(0, 10, "별제권부 채권 내역 생성 중 오류 발생: " . $e->getMessage(), 0, 'C');
	}
	// --- 별제권부채권 표 추가 끝 ---

	// --- 부속서류 2. 다툼이 있거나 예상되는 채권의 내역 표 추가 ---
	try {
		// 다툼이 있는 채권 데이터 조회
		$stmt_disputed = $pdo->prepare("
			SELECT 
				oc.creditor_count,
				c.financial_institution,
				c.principal AS list_principal,
				c.interest AS list_interest,
				oc.creditor_principal, 
				oc.creditor_interest,
				oc.undisputed_principal,
				oc.undisputed_interest,
				oc.difference_principal,
				oc.difference_interest,
				oc.dispute_reason,
				oc.litigation_status
			FROM application_recovery_creditor_other_claims oc
			JOIN application_recovery_creditor c 
				ON oc.case_no = c.case_no AND oc.creditor_count = c.creditor_count
			WHERE oc.case_no = ?
			ORDER BY oc.creditor_count ASC
		");
		$stmt_disputed->execute([$case_no]);
		$disputed_claims = $stmt_disputed->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($disputed_claims)) {
			$pdf->AddPage(); // 새 페이지에서 시작
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '부속서류 2. 다툼이 있거나 예상되는 채권의 내역', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 5, '(단위 : 원)', 0, 1, 'R');
			$pdf->Ln(1);

			// 테이블 너비 설정
			$colWidth1 = 10; // 채권번호
			$colWidth2 = 25; // 채권자
			$colWidth3a = 10; // 원금/이자
			$colWidth3b = 31; // 금액(목록상)
			$colWidth3 = 15; // 사이값
			$colWidth4 = 26; // 채권자주장채권현재액
			$colWidth5 = 26; // 다툼이 없는 부분
			$colWidth6 = 26; // 차이나는 부분
			$colWidth7 = 26; // 다툼의 원인

			// 테이블 헤더 - 첫 번째 행
			$pdf->SetFont('cid0kr', 'B', 8);
			
			// 채권번호와 채권자는 2행 병합
			$rowHeight1 = 10; // 첫 번째 행 높이
			$rowHeight2 = 8;  // 두 번째 행 높이
			
			$pdf->MultiCell($colWidth1, $rowHeight1 + $rowHeight2, "채권\n번호", 1, 'C', false, 0);
			$pdf->MultiCell($colWidth2, $rowHeight1 + $rowHeight2, "채권자", 1, 'C', false, 0);
			
			// ①~⑤ 항목들
			$currentX = $pdf->GetX();
			$currentY = $pdf->GetY();
			
			$pdf->MultiCell($colWidth3a + $colWidth3b, $rowHeight1, "①채권자목록상\n채권현재액", 1, 'C', false, 0);
			$pdf->MultiCell($colWidth4, $rowHeight1, "②채권자 주장 채권현재액", 1, 'C', false, 0);
			$pdf->MultiCell($colWidth5, $rowHeight1, "③다툼이 없는\n부분", 1, 'C', false, 0);
			$pdf->MultiCell($colWidth6, $rowHeight1, "④차이나는 부분\n(② - ①)", 1, 'C', false, 0);
			$pdf->MultiCell($colWidth7, $rowHeight1, "⑤다툼의 원인", 1, 'C', false, 1);
			
			// 두 번째 행 - 소송제기여부 항목
			$pdf->SetXY($currentX, $currentY + $rowHeight1);
			$pdf->MultiCell($colWidth3a + $colWidth3b + $colWidth4 + $colWidth5 + $colWidth6 + $colWidth7, $rowHeight2, "⑥소송제기여부 및 진행경과", 1, 'C', false, 1);
			
			// 각 채권자별 데이터 출력
			$pdf->SetFont('cid0kr', '', 8);

			foreach ($disputed_claims as $claim) {
				$rowHeight3 = 10; // 데이터 행 높이
				
				// 기본 정보 (채권번호, 채권자) - 3행 병합으로 변경
				$startY = $pdf->GetY();
				$pdf->MultiCell($colWidth1, $rowHeight3 * 3, $claim['creditor_count'], 1, 'C', false, 0);
				$pdf->MultiCell($colWidth2, $rowHeight3 * 3, $claim['financial_institution'], 1, 'C', false, 0);
				
				// 원금 행과 이자 행을 위치시킬 x 좌표 저장
				$startX = $pdf->GetX();
				
				// 원금 행
				$pdf->MultiCell($colWidth3a, $rowHeight3, "원금", 1, 'C', false, 0);
				$pdf->MultiCell($colWidth3b, $rowHeight3, number_format($claim['list_principal'])."원", 1, 'R', false, 0);
				$pdf->MultiCell($colWidth4, $rowHeight3, number_format($claim['creditor_principal'])."원", 1, 'R', false, 0);
				$pdf->MultiCell($colWidth5, $rowHeight3, number_format($claim['undisputed_principal'])."원", 1, 'R', false, 0); // 다툼 없는 부분
				$pdf->MultiCell($colWidth6, $rowHeight3, number_format($claim['difference_principal'])."원", 1, 'R', false, 0); // 차이나는 부분
				
				// 다툼의 원인 위치 저장
				$disputeX = $pdf->GetX();
				$disputeY = $pdf->GetY();
				
				// 원금 행 종료 - 줄바꿈 없이
				$pdf->MultiCell($colWidth7, $rowHeight3 * 2, $claim['dispute_reason'], 1, 'L', false, 0);
				
				// 이자 행 시작 위치로 이동 (원금 행 바로 아래)
				$pdf->SetXY($colWidth1 + $colWidth2 + $colWidth3, $disputeY + $rowHeight3);
				
				// 이자 행
				$pdf->MultiCell($colWidth3a, $rowHeight3, "이자", 1, 'C', false, 0);
				$pdf->MultiCell($colWidth3b, $rowHeight3, number_format($claim['list_interest'])."원", 1, 'R', false, 0);
				$pdf->MultiCell($colWidth4, $rowHeight3, number_format($claim['creditor_interest'])."원", 1, 'R', false, 0);
				$pdf->MultiCell($colWidth5, $rowHeight3, number_format($claim['undisputed_interest'])."원", 1, 'R', false, 0);
				$pdf->MultiCell($colWidth6, $rowHeight3, number_format($claim['difference_interest'])."원", 1, 'R', false, 0);
				
				// 이자 행 종료 후 소송제기여부 행 시작 위치로 이동
				$pdf->SetXY($colWidth1 + $colWidth2 + $colWidth3, $disputeY + $rowHeight3 * 2);
				
				// 소송제기여부 행
				$pdf->MultiCell($colWidth3a + $colWidth3b + $colWidth4 + $colWidth5 + $colWidth6 + $colWidth7, $rowHeight3, $claim['litigation_status'], 1, 'L', false, 1);
			}
		}
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->MultiCell(0, 10, "다툼이 있는 채권 내역 생성 중 오류 발생: " . $e->getMessage(), 0, 'C');
	}
	// --- 부속서류 2 표 추가 끝 ---

	// --- 부속서류 3. 전부명령의 내역 표 추가 ---
	try {
		// 전부명령된 채권 조회
		$stmt_assigned = $pdo->prepare("
			SELECT 
				ac.claim_no,
				ac.creditor_count,
				c.financial_institution,
				c.principal,
				c.interest,
				c.claim_content,
				ac.court_case_number,
				ac.debtor_name,
				ac.service_date,
				ac.confirmation_date,
				ac.claim_range
			FROM application_recovery_creditor_assigned_claims ac
			JOIN application_recovery_creditor c 
				ON ac.case_no = c.case_no AND ac.creditor_count = c.creditor_count
			WHERE ac.case_no = ?
			ORDER BY ac.creditor_count ASC
		");
		$stmt_assigned->execute([$case_no]);
		$assigned_claims = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($assigned_claims)) {
			$pdf->AddPage(); // 새 페이지에서 시작
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '부속서류 3. 전부명령의 내역', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 5, '(단위 : 원)', 0, 1, 'R');
			$pdf->Ln(1);

			// 테이블 너비 설정
			$colWidth1 = 10; // 채권번호
			$colWidth2 = 25; // 채권자
			$colWidth3 = 40; // 채권의 내용
			$colWidth4 = 100; // 전부명령의 내역
			
			// 테이블 헤더
			$pdf->SetFont('cid0kr', 'B', 8);
			$pdf->Cell($colWidth1, 8, '채권번호', 1, 0, 'C');
			$pdf->Cell($colWidth2, 8, '채권자', 1, 0, 'C');
			$pdf->Cell($colWidth3, 8, '채권의 내용', 1, 0, 'C');
			$pdf->Cell($colWidth4, 8, '전부명령의 내역', 1, 1, 'C');
			
			// 데이터 행
			$pdf->SetFont('cid0kr', '', 8);
			
			foreach ($assigned_claims as $claim) {
				// 전부명령 내용 작성
				$assigned_content = "1. 사건번호 : " . $claim['court_case_number'] . "\n";
				$assigned_content .= "2. 제3채무자명 : " . $claim['debtor_name'] . "\n";
				$assigned_content .= "3. 송달일자 : " . ($claim['service_date'] ? date('Y-m-d', strtotime($claim['service_date'])) : '') . "\n";
				$assigned_content .= "4. 확정일자 : " . ($claim['confirmation_date'] ? date('Y-m-d', strtotime($claim['confirmation_date'])) : '') . "\n";
				$assigned_content .= "5. 전부명령의 내용 : " . $claim['claim_range'];
				
				// 채권 내용
				$claim_content = "";
				if ($claim['principal'] > 0) {
					$claim_content .= "원금 " . number_format($claim['principal']) . "원";
					if ($claim['interest'] > 0) {
						$claim_content .= " 및 그 중 원금 " . number_format($claim['principal']) . "원에 대한";
					}
				}
				if ($claim['interest'] > 0) {
					if (empty($claim_content)) {
						$claim_content = "원금에 대한";
					}
					// 채권 내용에서 날짜와 이율 정보 추출 시도
					$date_pattern = '/(\d{4}-\d{2}-\d{2})/';
					$rate_pattern = '/연\s*(\d+(?:\.\d+)?)%/';
					$date_match = [];
					$rate_match = [];
					
					if (preg_match($date_pattern, $claim['claim_content'], $date_match) && 
						preg_match($rate_pattern, $claim['claim_content'], $rate_match)) {
						$claim_content .= " " . $date_match[1] . "부터 완제일까지 연 " . $rate_match[1] . "%의 비율에 의한 금원.";
					} else {
						$claim_content .= " 지연이자.";
					}
				}
				
				// 내용이 비어있으면 원본 내용 사용
				if (empty($claim_content)) {
					$claim_content = $claim['claim_content'];
				}
				
				// 내용을 기준으로 행 높이 계산
				$assigned_lines = count(explode("\n", $assigned_content));
				$claim_lines = 1;  // 최소 1줄은 필요
				
				// 채권 내용의 줄 수 계산 (대략 문자 길이 / 컬럼 너비 * 1.5)
				$claim_string_width = $pdf->GetStringWidth($claim_content);
				$claim_lines = ceil($claim_string_width / ($colWidth3 - 2) * 1.5);
				
				// 두 내용 중 더 많은 줄 수로 높이 결정
				$lines = max($assigned_lines, $claim_lines);
				$cell_height = max(8, $lines * 5);  // 최소 8포인트, 줄당 5포인트
				
				// 채권번호와 채권자는 일반 Cell로 출력
				$pdf->Cell($colWidth1, $cell_height, $claim['creditor_count'], 1, 0, 'C');
				$pdf->Cell($colWidth2, $cell_height, $claim['financial_institution'], 1, 0, 'C');
				
				// 현재 위치 저장
				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// 채권 내용 MultiCell (높이를 전체 셀 높이로 지정)
				$pdf->MultiCell($colWidth3, $cell_height, $claim_content, 1, 'L', false, 0);
				
				// 전부명령 내역 MultiCell (높이를 전체 셀 높이로 지정)
				$pdf->MultiCell($colWidth4, $cell_height, $assigned_content, 1, 'L', false, 1);
			}
		}
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->MultiCell(0, 10, "전부명령 채권 내역 생성 중 오류 발생: " . $e->getMessage(), 0, 'C');
	}
	// --- 부속서류 3 표 추가 끝 ---

	// --- 부속서류 4. 기타(보증선 채무등) 표 추가 ---
	try {
		// 기타 채무 데이터 조회
		$stmt_other_debts = $pdo->prepare("
			SELECT 
				od.creditor_count,
				c.financial_institution,
				od.debt_description,
				od.has_mortgage
			FROM application_recovery_creditor_other_debts od
			JOIN application_recovery_creditor c 
				ON od.case_no = c.case_no AND od.creditor_count = c.creditor_count
			WHERE od.case_no = ?
			ORDER BY od.creditor_count ASC
		");
		$stmt_other_debts->execute([$case_no]);
		$other_debts = $stmt_other_debts->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($other_debts)) {
			$pdf->AddPage(); // 새 페이지에서 시작
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(0, 10, '부속서류 4. 기타(보증선 채무등)', 0, 1, 'L');
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Ln(5);

			// 테이블 헤더 및 컬럼 너비 설정
			$colWidth1 = 15; // 채권번호
			$colWidth2 = 30; // 채권자
			$colWidth3 = 100; // 기타 내역
			$colWidth4 = 30; // 근저당권 설정 여부

			// 테이블 헤더
			$pdf->SetFont('cid0kr', 'B', 8);
			$pdf->Cell($colWidth1, 10, '채권번호', 1, 0, 'C');
			$pdf->Cell($colWidth2, 10, '채권자', 1, 0, 'C');
			$pdf->Cell($colWidth3, 10, '기타내역', 1, 0, 'C');
			$pdf->Cell($colWidth4, 10, '근저당권 설정 여부', 1, 1, 'C');

			// 테이블 데이터
			$pdf->SetFont('cid0kr', '', 8);
			
			foreach ($other_debts as $debt) {
				// 각 행의 높이를 텍스트 길이에 따라 조정
				$description = $debt['debt_description'];
				// 내용에 따라 줄 높이 계산 (대략적인 계산)
				$lineHeight = max(8, ceil(strlen($description) / 80) * 8);
				
				$pdf->Cell($colWidth1, $lineHeight, $debt['creditor_count'], 1, 0, 'C');
				$pdf->Cell($colWidth2, $lineHeight, $debt['financial_institution'], 1, 0, 'C');
				
				// MultiCell을 사용하여 긴 텍스트 처리
				$xPos = $pdf->GetX();
				$yPos = $pdf->GetY();
				$pdf->MultiCell($colWidth3, $lineHeight, $description, 1, 'L');
				
				// 다음 셀 위치 조정
				$pdf->SetXY($xPos + $colWidth3, $yPos);
				
				// 근저당권 설정 여부
				$hasMortgage = $debt['has_mortgage'] ? 'O' : 'X';
				$pdf->Cell($colWidth4, $lineHeight, $hasMortgage, 1, 1, 'C');
			}
		}
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->MultiCell(0, 10, "기타(보증선 채무등) 내역 생성 중 오류 발생: " . $e->getMessage(), 0, 'C');
	}
	// --- 부속서류 4 표 추가 끝 ---
	
	// --- 주택담보대출채권 채무재조정 프로그램 신청서 추가 ---
	try {
		// 주택담보대출 신청서 관련 데이터 조회
		$stmt_mortgage = $pdo->prepare("
			SELECT 
				c.creditor_count,
				c.financial_institution,
				c.address,
				c.phone,
				c.principal,
				c.default_rate,
				c.mortgage_restructuring,
				r.name,
				r.now_address,
				cm.case_number,
				a.max_claim
			FROM
				application_recovery_creditor c
			JOIN
				application_recovery r ON c.case_no = r.case_no
			JOIN
				case_management cm ON r.case_no = cm.case_no
			JOIN
				application_recovery_creditor_appendix a ON r.case_no = cm.case_no
			WHERE 
				c.case_no = ? AND c.mortgage_restructuring = 1
			ORDER BY 
				c.creditor_count ASC
		");
		$stmt_mortgage->execute([$case_no]);
		$mortgage_creditors = $stmt_mortgage->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($mortgage_creditors)) {
			foreach ($mortgage_creditors as $creditor) {
				// 부동산 정보 조회 (첫번째 부동산 정보 사용)
				$stmt_property = $pdo->prepare("
					SELECT 
						property_location
					FROM 
						application_recovery_asset_real_estate
					WHERE 
						case_no = ?
					LIMIT 1
				");
				$stmt_property->execute([$case_no]);
				$property = $stmt_property->fetch(PDO::FETCH_ASSOC);
				
				$pdf->AddPage();
				
				// 제목
				$pdf->SetFont('cid0kr', 'B', 14);
				$pdf->Cell(0, 10, '주택담보대출채권 채무재조정 프로그램 신청서', 0, 1, 'C');
				$pdf->Ln(3);
				
				// 사건 정보
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(25, 10, '사 건', 1, 0, 'C');
				$pdf->SetFont('cid0kr', '', 10);
				$pdf->Cell(155, 10, $creditor['case_number'].'    개인회생', 1, 1, 'L');
				
				// 신청인(채무자) 정보
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->MultiCell(25, 30, "신청인\n(채무자)", 1, 'C', false, 0); // 여러 줄로 표시되도록 수정
				$pdf->SetFont('cid0kr', '', 10);
				
				if($creditor['phone']==''){
					$phone = '                                  ';
				}else{
					$phone = $creditor['phone'];
				}
				
				$email = '                                  ';
				
				// 주소 줄바꿈 처리
				$address_parts = explode(',', $creditor['now_address']);
				$address1 = isset($address_parts[0]) ? trim($address_parts[0]) : '';
				$address2 = isset($address_parts[1]) ? trim($address_parts[1]) : '';

				// 신청인 정보 출력
				$pdf->MultiCell(155, 30, 
					"성명 : " . $creditor['name'] . "\n" .
					"주소 : " . $address1 . "\n" . $address2 . "\n" .
					"전화 : " . $phone . " (이메일 : ".$email.")", 
					1, 'L', false, 1);

				// 상대방(금융기관) 정보
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->MultiCell(25, 30, "상대방\n(금융기관)", 1, 'C', false, 0); // 여러 줄로 표시되도록 수정
				$pdf->SetFont('cid0kr', '', 10);

				// 금융기관 주소 줄바꿈 처리
				$creditor_address_parts = explode(',', $creditor['address']);
				$creditor_address1 = isset($creditor_address_parts[0]) ? trim($creditor_address_parts[0]) : '';
				$creditor_address2 = isset($creditor_address_parts[1]) ? trim($creditor_address_parts[1]) : '';
				$creditor_address3 = isset($creditor_address_parts[2]) ? trim($creditor_address_parts[2]) : '';

				$creditor_address = $creditor_address1;
				if (!empty($creditor_address2)) {
					$creditor_address .= "\n      " . $creditor_address2;
				}
				if (!empty($creditor_address3)) {
					$creditor_address .= "\n      " . $creditor_address3;
				}

				// 상대방 정보 출력
				$pdf->MultiCell(155, 30, 
					"성명 : " . $creditor['financial_institution'] . "\n" .
					"주소 : " . $creditor_address . "\n" .
					"전화 : " . $phone . " (이메일 : ".$email.")", 
					1, 'L', false, 1);

				// 조정대상 주택담보대출의 내역
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->MultiCell(25, 50, "조정대상\n주택담보대출의\n내역", 1, 'C', false, 0); // 여러 줄로 표시되도록 수정
				$pdf->SetFont('cid0kr', '', 10);

				// 현재 날짜 포맷
				$now = date('Y.m.d');

				// 부동산 위치
				$property_location = isset($property['property_location']) ? $property['property_location'] : 'xxx';

				// 원금 포맷팅
				$principal = number_format($creditor['principal']) . '원';
				$max_claim = number_format($creditor['max_claim']) . '원';

				// 대출 정보 출력
				$pdf->MultiCell(155, 50, 
					"1. 대상 주택의 표시 :\n" .
					$property_location . "\n\n" .
					"2. 대상 주택담보대출채권액, 이율 및 채권최고액 :\n" .
					"채권액(피담보채무금액) : " . $principal . " (" . $now . " 기준)\n" .
					"이율 : ". $creditor['default_rate'] ."%\n" .
					"채권최고액 : ". $max_claim, 
					1, 'L', false, 1);
				
				// 신청 문구
				$pdf->Ln(5);
				$pdf->MultiCell(0, 10, "위와 같이 주택담보대출채권 채무재조정 프로그램을 신청합니다.", 0, 'L');
				
				// 서명란
				$pdf->Ln(10);
				$pdf->SetFont('cid0kr', '', 10);
				$pdf->Cell(0, 10, '20   .    .    .', 0, 1, 'C');
				$pdf->Cell(0, 10, '신청인 ' . $creditor['name'] . '   (인)', 0, 1, 'R');
			}
		}
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->AddPage();
		$pdf->MultiCell(0, 10, "주택담보대출채권 채무재조정 프로그램 신청서 생성 중 오류 발생: " . $e->getMessage(), 0, 'C');
	}
	// --- 주택담보대출채권 채무재조정 프로그램 신청서 추가 끝 ---
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
