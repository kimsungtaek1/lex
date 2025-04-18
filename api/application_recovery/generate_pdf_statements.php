<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfStatements($pdf, $pdo, $case_no) {
	// 새 페이지 추가
	$pdf->AddPage('P');
	
	// 제목 및 기본 서식
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '진 술 서', 0, 1, 'C');
	$pdf->Ln(5);
	
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
		$pdf->Cell(0, 8, '1. 최종학력', 0, 1, 'L');
		
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
				date('Y년', strtotime($education['graduation_date'])) : '____년';
			$school_name = $education['school_name'] ?: 'ㅁㄴㅇㄹ';
			$graduation_status = $education['graduation_status'] ?: '졸업';
			
			$edu_text = $graduation_date . ' : ' . $school_name . ' ( ' . $graduation_status . ' )';
			$pdf->Cell(0, 8, $edu_text, 0, 1, 'L');
		} else {
			$pdf->Cell(0, 8, '____년 : _______ ( 졸업 )', 0, 1, 'L');
		}
		
		// 2. 과거 경력
		$pdf->Cell(0, 8, '2. 과거 경력 (최근 경력부터 기재하여 주십시오)', 0, 1, 'L');
		
		// 경력 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_career 
			WHERE case_no = ? 
			ORDER BY work_start_date DESC
		");
		$stmt->execute([$case_no]);
		$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 경력 테이블 생성
		$pdf->SetLineWidth(0.1);
		
		// 테이블 헤더
		$pdf->Cell(30, 8, '기간', 1, 0, 'C');
		$pdf->Cell(30, 8, '업종', 1, 0, 'C');
		$pdf->Cell(60, 8, '직장명', 1, 0, 'C');
		$pdf->Cell(60, 8, '직위', 1, 1, 'C');
		
		if (!empty($careers)) {
			foreach ($careers as $idx => $career) {
				// 시작일, 종료일 포맷
				$start_date = !empty($career['work_start_date']) ? 
					date('Y', strtotime($career['work_start_date'])) : '____';
				
				$end_date = '';
				if (!empty($career['work_end_date'])) {
					$end_date = date('Y', strtotime($career['work_end_date']));
				} else {
					$end_date = '현재';
				}
				
				$company_type = $career['company_type'] ?: '근무';
				$business_type = $career['business_type'] ?: '제철';
				$company_name = $career['company_name'] ?: '테스트';
				$position = $career['position'] ?: '대리';
				
				$period = $start_date . '부터 ' . $end_date . '까지 ( ' . $company_type . ' )';
				
				$pdf->Cell(30, 8, $period, 1, 0, 'L');
				$pdf->Cell(30, 8, $business_type, 1, 0, 'L');
				$pdf->Cell(60, 8, $company_name, 1, 0, 'L');
				$pdf->Cell(60, 8, $position, 1, 1, 'L');
			}
		} else {
			// 경력 정보가 없을 경우 빈 테이블 출력
			$pdf->Cell(30, 8, '____부터 ____까지 ( ___ )', 1, 0, 'L');
			$pdf->Cell(30, 8, '', 1, 0, 'L');
			$pdf->Cell(60, 8, '', 1, 0, 'L');
			$pdf->Cell(60, 8, '', 1, 1, 'L');
		}
		
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
					date('Y', strtotime($marriage['marriage_date'])) : '____';
				$spouse_name = $marriage['spouse_name'] ?: '김이혼';
				$marriage_status = $marriage['marriage_status'] ?: '결혼';
				
				$marriage_text = $marriage_date . ' ' . $spouse_name . ' 와( ' . $marriage_status . ' )';
				$pdf->Cell(0, 8, $marriage_text, 0, 1, 'L');
			}
		} else {
			$pdf->Cell(0, 8, '____년 ______와 (결혼, 이혼)', 0, 1, 'L');
		}
		
		$pdf->Ln(5);
		
		// II. 현재 주거 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'II. 현재 주거상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 주거 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_housing 
			WHERE case_no = ? 
			LIMIT 1
		");
		$stmt->execute([$case_no]);
		$housing = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 주거 시작 시점
		$residence_start_date = ($housing && !empty($housing['residence_start_date'])) ? 
			date('Y년 m월 d일', strtotime($housing['residence_start_date'])) : '';
		$pdf->Cell(0, 8, '거주를 시작한 시점 ( '.$residence_start_date.' )', 0, 1, 'L');
		
		// 주거 상황 테이블
		$col1_width = 70; // 거주 상황 열 너비
		$col2_width = 120; // 상세 내역 열 너비
		
		$pdf->Cell($col1_width, 8, '거주관계(해당란에 표시)', 1, 0, 'C');
		$pdf->Cell($col2_width, 8, '상세한 내역', 1, 1, 'C');
		
		// 주거 유형 정보
		$housing_type = $housing ? $housing['housing_type'] : '';
		$is_owned = $housing_type == '㉠ 신청인 소유주택';
		$is_company = $housing_type == '㉡ 사택 또는 기숙사';
		$is_rent = $housing_type == '㉢ 임차(전월세)주택';
		$is_family = $housing_type == '㉣ 친족 소유 주택에 무상거주';
		$is_nonfamily = $housing_type == '㉤ 진족 외 소유 주택에 무상거주';
		$is_other = $housing_type == '㉥ 기타';
		
		// ㉠ 신청인 소유의 주택
		$owned_check = $is_owned ? '[ v]' : '[  ]';
		$pdf->Cell($col1_width, 8, $owned_check.' ㉠ 신청인 소유의 주택', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, '소재지', 1, 1, 'L');
		
		// ㉡ 사택 또는 기숙사 & ㉢ 임차(전월세) 주택
		$company_check = $is_company ? '[ v]' : '[  ]';
		$rent_check = $is_rent ? '[ v]' : '[  ]';
		$deposit_amount = ($housing && !empty($housing['deposit_amount'])) ? number_format($housing['deposit_amount']) : '';
		$monthly_rent = ($housing && !empty($housing['monthly_rent'])) ? number_format($housing['monthly_rent']) : '';
		$overdue_amount = ($housing && !empty($housing['overdue_amount'])) ? number_format($housing['overdue_amount']) : '';
		$tenant_name = ($housing && !empty($housing['tenant_name'])) ? $housing['tenant_name'] : '';
		
		$pdf->Cell($col1_width, 16, $company_check." ㉡ 사택 또는 기숙사\n\n".$rent_check." ㉢ 임차(전․월세) 주택", 1, 0, 'L');
		$pdf->MultiCell($col2_width, 16, "임대보증금 (".$deposit_amount." 원)\n임대료 (월 ".$monthly_rent." 원), 연체액(".$overdue_amount." 원)\n임차인 성명(".$tenant_name.")\n부연설명", 1, 'L');
		
		// ㉣ 친족 소유 주택에 무상 거주 & ㉤ 친족 외 소유 주택에 무상 거주
		$family_check = $is_family ? '[ v]' : '[  ]';
		$nonfamily_check = $is_nonfamily ? '[ v]' : '[  ]';
		$owner_name = ($housing && !empty($housing['owner_name'])) ? $housing['owner_name'] : '';
		$relationship = ($housing && !empty($housing['relationship'])) ? $housing['relationship'] : '';
		
		$pdf->Cell($col1_width, 16, $family_check." ㉣ 친족 소유 주택에 무상 거주\n\n".$nonfamily_check." ㉤ 친족외 소유 주택에 무상 거주", 1, 0, 'L');
		$pdf->MultiCell($col2_width, 16, "소유자 성명(".$owner_name.")\n신청인과의 관계(".$relationship.")", 1, 'L');
		
		// ㉥ 기타
		$other_check = $is_other ? '[ v]' : '[  ]';
		$etc_description = ($housing && !empty($housing['etc_description'])) ? $housing['etc_description'] : '';
		$pdf->Cell($col1_width, 8, $other_check.' ㉥ 기타('.$etc_description.')', 1, 0, 'L');
		$pdf->Cell($col2_width, 8, '', 1, 1, 'L');
		
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
		$pdf->Cell(0, 8, '1. 채권자로부터 소송․지급명령․전부명령․압류․가압류 등을 받은 경험( 있음 )', 0, 1, 'L');
		
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
		$pdf->Cell(50, 8, '사건번호', 1, 1, 'C');
		
		if (!empty($lawsuits)) {
			foreach ($lawsuits as $lawsuit) {
				$pdf->Cell(50, 8, $lawsuit['lawsuit_type'] ?: '부채상황1', 1, 0, 'L');
				$pdf->Cell(50, 8, $lawsuit['creditor'] ?: '채권자', 1, 0, 'L');
				$pdf->Cell(40, 8, $lawsuit['court'] ?: '서울', 1, 0, 'L');
				$pdf->Cell(50, 8, $lawsuit['case_number'] ?: '123123', 1, 1, 'L');
			}
		} else {
			// 소송 정보가 없을 경우 기본 행 추가
			$pdf->Cell(50, 8, '부채상황1', 1, 0, 'L');
			$pdf->Cell(50, 8, '채권자', 1, 0, 'L');
			$pdf->Cell(40, 8, '서울', 1, 0, 'L');
			$pdf->Cell(50, 8, '123123', 1, 1, 'L');
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
		
		$reasons = [  ];
		$detail = '';
		
		if ($bankruptcy_reason) {
			if (!empty($bankruptcy_reason['reasons'])) {
				$reasons = json_decode($bankruptcy_reason['reasons'], true);
			}
			$detail = $bankruptcy_reason['detail'] ?: '기타내용';
		}
		
		// 사유 체크박스
		$pdf->Cell(0, 6, '[ v] 생활비 부족               [ v] 병원비 과다지출', 0, 1, 'L');
		$pdf->Cell(0, 6, '[ v] 교육비 과다지출           [ v] 음식,음주,여행,도박 또는 취미활동', 0, 1, 'L');
		$pdf->Cell(0, 6, '[ v] 점포 운영의 실패          [ v] 타인 채무의 보증', 0, 1, 'L');
		$pdf->Cell(0, 6, '[ v] 주식투자 실패             [ v] 사기 피해', 0, 1, 'L');
		$pdf->Cell(0, 6, '[ v] 기타 ( '.$detail.' )', 0, 1, 'L');
		
		$pdf->Ln(3);
		
		// 3. 채무자가 많은 채무를 부담하게 된 사정
		$pdf->Cell(0, 8, '3. 채무자가 많은 채무를 부담하게 된 사정 및 개인회생절차 개시의 신청에 이르게 된 사정에 관하여 구체적으로 기재하여 주십시오(추가 기재 시에는 별지를 이용하시면 됩니다).', 0, 1, 'L');
		
		// 테두리 있는 여러 줄의 텍스트 상자 (내용 포함)
		$pdf->Cell(0, 20, '부채상황3', 1, 1, 'L');
		
		$pdf->Ln(5);
		
		// IV. 과거 면책절차 등의 이용 상황
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, 'IV. 과거 면책절차 등의 이용 상황', 0, 1, 'L');
		$pdf->SetFont('cid0kr', '', 10);
		
		// 면책절차 테이블
		$pdf->Cell(50, 8, '절차', 1, 0, 'C');
		$pdf->Cell(50, 8, '법원 또는 기관', 1, 0, 'C');
		$pdf->Cell(40, 8, '신청시기', 1, 0, 'C');
		$pdf->Cell(50, 8, '현재까지 진행상황', 1, 1, 'C');
		
		// 과거 면책절차 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_debt_relief 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$debt_reliefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 파산, 면책절차
		$bankruptcy_relief = null;
		$recovery_relief = null;
		$workout_relief = null;
		$badbank_relief = null;
		
		if (!empty($debt_reliefs)) {
			foreach ($debt_reliefs as $relief) {
				if ($relief['relief_type'] == '파산_면책') {
					$bankruptcy_relief = $relief;
				} else if ($relief['relief_type'] == '화의_회생') {
					$recovery_relief = $relief;
				} else if ($relief['relief_type'] == '워크아웃') {
					$workout_relief = $relief;
				} else if ($relief['relief_type'] == '배드뱅크') {
					$badbank_relief = $relief;
				}
			}
		}
		
		// 파산, 면책절차 (기본으로 체크된 상태로 표시)
		$bankruptcy_institution = $bankruptcy_relief ? $bankruptcy_relief['institution'] : '서울법원 또는 기관';
		$bankruptcy_date = $bankruptcy_relief && !empty($bankruptcy_relief['application_date']) ? 
			date('Y', strtotime($bankruptcy_relief['application_date'])) : '2022';
		$bankruptcy_status = $bankruptcy_relief ? $bankruptcy_relief['current_status'] : '현재까지 상황';
		
		$pdf->Cell(50, 8, '[ v] 파산·면책절차', 1, 0, 'L');
		$pdf->Cell(50, 8, $bankruptcy_institution, 1, 0, 'L');
		$pdf->Cell(40, 8, $bankruptcy_date, 1, 0, 'L');
		$pdf->Cell(50, 8, $bankruptcy_status, 1, 1, 'L');
		
		// 화의, 회생, 개인회생 절차 (기본으로 체크된 상태로 표시)
		$recovery_institution = $recovery_relief ? $recovery_relief['institution'] : '';
		$recovery_date = $recovery_relief && !empty($recovery_relief['application_date']) ? 
			date('Y', strtotime($recovery_relief['application_date'])) : '';
		$recovery_status = $recovery_relief ? $recovery_relief['current_status'] : '';
		
		$pdf->Cell(50, 8, '[ v] 화의·회생·개인회생절차', 1, 0, 'L');
		$pdf->Cell(50, 8, $recovery_institution, 1, 0, 'L');
		$pdf->Cell(40, 8, $recovery_date, 1, 0, 'L');
		$pdf->Cell(50, 8, $recovery_status, 1, 1, 'L');
		
		// 신용회복위원회 워크아웃 & 배드뱅크 (기본으로 체크된 상태로 표시)
		$workout_institution = $workout_relief ? $workout_relief['institution'] : '서울법원 또는 기관';
		$workout_date = $workout_relief && !empty($workout_relief['application_date']) ? 
			date('Y', strtotime($workout_relief['application_date'])) : '2021';
		$workout_status = $workout_relief ? $workout_relief['current_status'] : '1';
		$badbank_status = $badbank_relief ? $badbank_relief['current_status'] : '10,000,000';
		
		$pdf->Cell(50, 16, "[ v] 신용회복위원회 워크아웃\n[ v] 배드뱅크", 1, 0, 'L');
		$pdf->Cell(50, 16, $workout_institution, 1, 0, 'L');
		$pdf->Cell(40, 16, $workout_date, 1, 0, 'L');
		$pdf->MultiCell(50, 8, "( ".$workout_status." )회\n( ".$badbank_status." )원 변제", 1, 'L');
		
		// 면책절차 관련 설명
		$pdf->SetFont('cid0kr', '', 8);
		$pdf->Ln(2);
		$pdf->Cell(0, 4, '☆ 과거에 면책절차 등을 이용하였다면 해당란에 [ v]표시 후 기재합니다.', 0, 1, 'L');
		$pdf->Cell(0, 4, '☆ 신청일 전 10년 내에 회생사건․화의사건․파산사건 또는 개인회생사건을 신청한 사실이 있는 때에는 관련서류 1통을 제출하여야 합니다.', 0, 1, 'L');
		
	} catch (Exception $e) {
		$pdf->SetTextColor(255, 0, 0);
		$pdf->Cell(0, 10, '오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
		$pdf->SetTextColor(0, 0, 0);
	}
}
?>