<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfStatements($pdf, $pdo, $case_no) {
	// 새 페이지 추가
	$pdf->AddPage();
	
	// 제목 및 기본 서식
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '진 술 서', 0, 1, 'C');
	$pdf->Ln(3);
	$pdf->SetFont('cid0kr', '', 10);
	
	try {
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
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// I. 경력 섹션
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'I. 경력', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 1. 최종 학력
		$pdf->Cell(0, 8, '1. 최종 학력', 0, 1, 'L');
		
		// 최종학력 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_education 
			WHERE case_no = ? 
			LIMIT 1
		");
		$stmt->execute([$case_no]);
		$education = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($education) {
			$graduation_date = !empty($education['graduation_date']) ? 
				date('Y년 m월 d일', strtotime($education['graduation_date'])) : '______년__월__일';
			$school_name = $education['school_name'] ?: '_________________';
			$graduation_status = $education['graduation_status'] ?: '________';
			
			$edu_text = $graduation_date . ' ' . $school_name . ' (' . $graduation_status . ')';
			$pdf->Cell(0, 8, $edu_text, 0, 1, 'L');
		} else {
			$pdf->Cell(0, 8, '________년__월__일 _________________ (________)', 0, 1, 'L');
		}
		
		// 2. 과거 경력
		$pdf->Cell(0, 8, '2. 과거 경력 (최근 경력부터 기재)', 0, 1, 'L');
		
		// 경력 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_career 
			WHERE case_no = ? 
			ORDER BY work_start_date DESC
		");
		$stmt->execute([$case_no]);
		$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 경력 테이블 생성
		$col1_width = 25; // 기간
		$col2_width = 25; // 업종
		$col3_width = 50; // 직장명
		$col4_width = 35; // 직위
		$row_height = 8;
		
		// 경력 데이터 출력
		if (!empty($careers)) {
			foreach ($careers as $idx => $career) {
				// 시작일, 종료일 포맷
				$start_date = !empty($career['work_start_date']) ? 
					date('Y년 m월 d일', strtotime($career['work_start_date'])) : '____년__월__일';
				
				$end_date = '';
				if (!empty($career['work_end_date'])) {
					$end_date = date('Y년 m월 d일', strtotime($career['work_end_date']));
				} else {
					$end_date = '현재까지';
				}
				
				$company_type = $career['company_type'] ?: '____';
				$business_type = $career['business_type'] ?: '____';
				$company_name = $career['company_name'] ?: '____';
				$position = $career['position'] ?: '____';
				
				// 첫 행 - 기간 시작
				$pdf->Cell(25, $row_height, '기간', 1, 0, 'L');
				$pdf->Cell(0, $row_height, $start_date . '부터', 1, 1, 'L');
				
				// 둘째 행 - 기간 끝
				$pdf->Cell(25, $row_height, '', 1, 0, 'L');
				$pdf->Cell(0, $row_height, $end_date, 1, 1, 'L');
				
				// 셋째 행 - 자영/근무
				$pdf->Cell(25, $row_height, '', 1, 0, 'L');
				$pdf->Cell(0, $row_height, '(' . $company_type . ')', 1, 1, 'L');
				
				// 넷째 행 - 업종, 직장명, 직위
				$pdf->Cell(25, $row_height, '업종', 1, 0, 'L');
				$pdf->Cell(50, $row_height, $business_type, 1, 0, 'L');
				$pdf->Cell(50, $row_height, '직장명: ' . $company_name, 1, 0, 'L');
				$pdf->Cell(0, $row_height, '직위: ' . $position, 1, 1, 'L');
				
				// 경력 사이에 간격 추가
				if ($idx < count($careers) - 1) {
					$pdf->Ln(2);
				}
			}
		} else {
			// 경력 정보가 없을 경우 빈 양식 출력
			$pdf->Cell(25, $row_height, '기간', 1, 0, 'L');
			$pdf->Cell(0, $row_height, '____년__월__일부터', 1, 1, 'L');
			
			$pdf->Cell(25, $row_height, '', 1, 0, 'L');
			$pdf->Cell(0, $row_height, '____년__월__일까지', 1, 1, 'L');
			
			$pdf->Cell(25, $row_height, '', 1, 0, 'L');
			$pdf->Cell(0, $row_height, '(자영, 근무)', 1, 1, 'L');
			
			$pdf->Cell(25, $row_height, '업종', 1, 0, 'L');
			$pdf->Cell(50, $row_height, '', 1, 0, 'L');
			$pdf->Cell(50, $row_height, '직장명: ', 1, 0, 'L');
			$pdf->Cell(0, $row_height, '직위: ', 1, 1, 'L');
		}
		
		$pdf->Ln(5);
		
		// 3. 과거 결혼, 이혼 경력
		$pdf->Cell(0, 8, '3. 과거 결혼, 이혼 경력', 0, 1, 'L');
		
		// 결혼/이혼 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_marriage 
			WHERE case_no = ? 
			ORDER BY marriage_date DESC
		");
		$stmt->execute([$case_no]);
		$marriages = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (!empty($marriages)) {
			foreach ($marriages as $marriage) {
				$marriage_date = !empty($marriage['marriage_date']) ? 
					date('Y년 m월 d일', strtotime($marriage['marriage_date'])) : '____년__월__일';
				$spouse_name = $marriage['spouse_name'] ?: '______';
				$marriage_status = $marriage['marriage_status'] ?: '______';
				
				$marriage_text = $marriage_date . ' ' . $spouse_name . '와 (' . $marriage_status . ')';
				$pdf->Cell(0, 8, $marriage_text, 0, 1, 'L');
			}
		} else {
			$pdf->Cell(0, 8, '____년__월__일 ______와 (결혼, 이혼)', 0, 1, 'L');
		}
		
		$pdf->Ln(5);
		
		// II. 현재 주거 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'II. 현재 주거 상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 주거 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_housing 
			WHERE case_no = ? 
			LIMIT 1
		");
		$stmt->execute([$case_no]);
		$housing = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($housing) {
			$housing_type = $housing['housing_type'] ?: '';
			$deposit_amount = !empty($housing['deposit_amount']) ? number_format($housing['deposit_amount']) : '0';
			$monthly_rent = !empty($housing['monthly_rent']) ? number_format($housing['monthly_rent']) : '0';
			$overdue_amount = !empty($housing['overdue_amount']) ? number_format($housing['overdue_amount']) : '0';
		}
		
		// 주거 시작 시점
		$pdf->Cell(0, 8, '거주를 시작한 시점 (____년__월__일)', 0, 1, 'L');
		
		// 주거 상황 테이블
		$pdf->Cell(60, 8, '거주 상황(해당란에 표시)', 1, 0, 'C');
		$pdf->Cell(0, 8, '상세한 내역', 1, 1, 'C');
		
		// 주거 유형별 출력
		$pdf->Cell(60, 8, '㉠ 신청인 소유의 주택', 1, 0, 'L');
		$pdf->Cell(0, 8, '', 1, 1, 'L');
		
		$pdf->Cell(60, 16, "㉡ 사택 또는 기숙사\n\n㉢ 임차(전․월세) 주택", 1, 0, 'L');
		
		$detail_text = "임대보증금 (" . $deposit_amount . "원)\n";
		$detail_text .= "임대료 (월 " . $monthly_rent . "원), 연체액 (" . $overdue_amount . "원)\n";
		$detail_text .= "임차인 성명 (            )";
		
		$pdf->MultiCell(0, 16, $detail_text, 1, 'L');
		
		$pdf->Cell(60, 16, "㉣ 친족 소유 주택에 무상 거주\n\n㉤ 친족 외 소유 주택에 무상 거주", 1, 0, 'L');
		$pdf->MultiCell(0, 16, "소유자 성명 (            )\n신청인과의 관계 (            )", 1, 'L');
		
		$pdf->Cell(60, 8, '㉥ 기타(            )', 1, 0, 'L');
		$pdf->Cell(0, 8, '', 1, 1, 'L');
		
		// 주거 관련 설명
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Ln(2);
		$pdf->Cell(0, 4, '☆ ㉠ 또는 ㉣항을 선택한 분은 주택의 등기부등본을 첨부하여 주십시오.', 0, 1, 'L');
		$pdf->Cell(0, 4, '☆ ㉡ 또는 ㉢항을 선택한 분은 임대차계약서(전월세 계약서) 또는 사용허가서 사본을 첨부하여 주시기 바랍니다.', 0, 1, 'L');
		$pdf->Cell(0, 4, '☆ ㉣ 또는 ㉤항을 선택한 분은 소유자 작성의 거주 증명서를 첨부하여 주십시오.', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		$pdf->Ln(5);
		
		// III. 부채 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'III. 부채 상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 1. 채권자로부터 소송 등 경험
		$pdf->Cell(0, 8, '1. 채권자로부터 소송․지급명령․전부명령․압류․가압류 등을 받은 경험', 0, 1, 'L');
		
		// 소송 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_lawsuit 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$lawsuits = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 소송 테이블 헤더
		$pdf->Cell(50, 8, '내 역', 1, 0, 'C');
		$pdf->Cell(50, 8, '채권자', 1, 0, 'C');
		$pdf->Cell(40, 8, '관할법원', 1, 0, 'C');
		$pdf->Cell(0, 8, '사건번호', 1, 1, 'C');
		
		if (!empty($lawsuits)) {
			foreach ($lawsuits as $lawsuit) {
				$pdf->Cell(50, 8, $lawsuit['lawsuit_type'] ?: '', 1, 0, 'L');
				$pdf->Cell(50, 8, $lawsuit['creditor'] ?: '', 1, 0, 'L');
				$pdf->Cell(40, 8, $lawsuit['court'] ?: '', 1, 0, 'L');
				$pdf->Cell(0, 8, $lawsuit['case_number'] ?: '', 1, 1, 'L');
			}
		} else {
			// 소송 정보가 없을 경우 빈 행 추가
			$pdf->Cell(50, 8, '', 1, 0, 'L');
			$pdf->Cell(50, 8, '', 1, 0, 'L');
			$pdf->Cell(40, 8, '', 1, 0, 'L');
			$pdf->Cell(0, 8, '', 1, 1, 'L');
		}
		
		// 소송 관련 설명
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Ln(2);
		$pdf->Cell(0, 4, '☆ 위 내역란에는 소송, 지급명령, 압류 등으로 그 내용을 기재합니다.', 0, 1, 'L');
		$pdf->Cell(0, 4, '☆ 위 기재 사항에 해당하는 소장․지급명령․전부명령․압류 및 가압류결정의 각 사본을 첨부하여 주십시오.', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		$pdf->Ln(5);
		
		// 2. 개인회생절차에 이르게 된 사정
		$pdf->Cell(0, 8, '2. 개인회생절차에 이르게 된 사정(여러 항목 중복 선택 가능)', 0, 1, 'L');
		
		// 파산 원인 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_bankruptcy_reason 
			WHERE case_no = ? 
			LIMIT 1
		");
		$stmt->execute([$case_no]);
		$bankruptcy_reason = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$reasons = [];
		$detail = '';
		
		if ($bankruptcy_reason) {
			if (!empty($bankruptcy_reason['reasons'])) {
				$reasons = json_decode($bankruptcy_reason['reasons'], true);
			}
			$detail = $bankruptcy_reason['detail'] ?: '';
		}
		
		// 사유 체크박스 행 구성 (2개씩 배치)
		$reason_options = [
			'생활비 부족', '병원비 과다지출', 
			'교육비 과다지출', '음식, 음주, 여행, 도박 또는 취미활동',
			'점포 운영의 실패', '타인 채무의 보증',
			'주식투자 실패', '사기피해',
			'기타'
		];
		
		$row1 = '';
		$row2 = '';
		$row3 = '';
		$row4 = '';
		$row5 = '';
		
		for ($i = 0; $i < count($reason_options); $i++) {
			$option = $reason_options[$i];
			$checked = in_array($option, $reasons) ? '☑' : '□';
			
			$text = $checked . ' ' . $option . ' ';
			
			if ($i == 0 || $i == 1) {
				$row1 .= $text;
			} else if ($i == 2 || $i == 3) {
				$row2 .= $text;
			} else if ($i == 4 || $i == 5) {
				$row3 .= $text;
			} else if ($i == 6 || $i == 7) {
				$row4 .= $text;
			} else {
				$row5 .= $text . ' ( ' . $detail . ' )';
			}
		}
		
		$pdf->Cell(0, 6, $row1, 0, 1, 'L');
		$pdf->Cell(0, 6, $row2, 0, 1, 'L');
		$pdf->Cell(0, 6, $row3, 0, 1, 'L');
		$pdf->Cell(0, 6, $row4, 0, 1, 'L');
		$pdf->Cell(0, 6, $row5, 0, 1, 'L');
		
		$pdf->Ln(3);
		
		// 3. 채무자가 많은 채무를 부담하게 된 사정
		$pdf->Cell(0, 8, '3. 채무자가 많은 채무를 부담하게 된 사정 및 개인회생절차 개시의 신청에 이르게 된 사정에 관하여 구체적으로 기재하여 주십시오(추가 기재 시에는 별지를 이용하시면 됩니다).', 0, 1, 'L');
		
		// 테두리 있는 여러 줄의 텍스트 상자
		$pdf->Cell(0, 20, '', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// IV. 과거 면책절차 등의 이용 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'IV. 과거 면책절차 등의 이용 상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 면책절차 테이블
		$pdf->Cell(50, 8, '절차', 1, 0, 'C');
		$pdf->Cell(50, 8, '법원 또는 기관', 1, 0, 'C');
		$pdf->Cell(40, 8, '신청 시기', 1, 0, 'C');
		$pdf->Cell(0, 8, '현재까지 진행 상황', 1, 1, 'C');
		
		// 세 종류의 면책절차
		$procedures = [
			'□ 파산·면책절차',
			'□ 화의·회생·개인회생절차',
			'□ 신용회복위원회 워크아웃 □ 배드뱅크'
		];
		
		$status_texts = [
			'',
			'',
			'( )회\n( )원 변제'
		];
		
		// 면책절차 행 출력
		foreach ($procedures as $idx => $procedure) {
			$row_height = $idx == 2 ? 16 : 8; // 마지막 행은 더 크게
			
			$pdf->Cell(50, $row_height, $procedure, 1, 0, 'L');
			$pdf->Cell(50, $row_height, '', 1, 0, 'L');
			$pdf->Cell(40, $row_height, '', 1, 0, 'L');
			
			// 진행 상황 셀
			if ($idx == 2) {
				$pdf->MultiCell(0, $row_height, $status_texts[$idx], 1, 'L');
			} else {
				$pdf->Cell(0, $row_height, $status_texts[$idx], 1, 1, 'L');
			}
		}
		
		// 면책절차 관련 설명
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Ln(2);
		$pdf->Cell(0, 4, '☆ 과거에 면책절차 등을 이용하였다면 해당란에 ☑ 표시 후 기재합니다.', 0, 1, 'L');
		$pdf->Cell(0, 4, '☆ 신청일 전 10년 내에 회생사건․화의사건․파산사건 또는 개인회생사건을 신청한 사실이 있는 때에는 관련서류 1통을 제출하여야 합니다.', 0, 1, 'L');
		
	} catch (Exception $e) {
		$pdf->SetTextColor(255, 0, 0);
		$pdf->Cell(0, 10, '오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
		$pdf->SetTextColor(0, 0, 0);
	}
}
?>