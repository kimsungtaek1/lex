<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfStatements($pdf, $pdo, $case_no) {
	// 오류 처리 활성화
	$old_error_reporting = error_reporting(E_ALL);
	$old_display_errors = ini_get('display_errors');
	ini_set('display_errors', 0);
	
	// 기본 정보 조회
	try {
		$stmt = $pdo->prepare("
			SELECT cm.name, cm.case_number, cm.court_name, ab.*
			FROM case_management cm
			LEFT JOIN application_bankruptcy ab ON cm.case_no = ab.case_no
			WHERE cm.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 진술서 표지 생성
		generateCoverPage($pdf, $basic_info);
		
		// 학력 및 경력 정보 진술서 생성
		generateEducationCareerPage($pdf, $pdo, $case_no, $basic_info);
		
		// 현재까지의 생활상황 진술서 생성
		generateLifeHistoryPage($pdf, $pdo, $case_no, $basic_info);
		
		// 채권자 상황 진술서 생성
		generateCreditorStatusPage($pdf, $pdo, $case_no, $basic_info);
		
		// 파산신청 사유 진술서 생성
		generateBankruptcyReasonPage($pdf, $pdo, $case_no, $basic_info);
		
		// 지급불능 이후 채무 발생 진술서 생성
		generateDebtAfterInsolvencyPage($pdf, $pdo, $case_no, $basic_info);
		
		// 일부 채권자에게만 변제한 경험 진술서 생성
		generatePartialRepaymentPage($pdf, $pdo, $case_no, $basic_info);
		
	} catch (Exception $e) {
		$pdf->MultiCell(0, 10, 
			"데이터 조회 중 오류가 발생했습니다:\n" . 
			$e->getMessage() . 
			"\n\n관리자에게 문의해 주시기 바랍니다.", 
			0, 
			'C'
		);
	}
	
	// 오류 처리 원래대로 복원
	error_reporting($old_error_reporting);
	ini_set('display_errors', $old_display_errors);
}

/**
 * 진술서 표지 생성
 */
function generateCoverPage($pdf, $basic_info) {
	// 표지 페이지 추가
	$pdf->AddPage();
	
	// A4 용지에 맞게 여백 설정
	$pdf->SetMargins(15, 15, 15);
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 22);
	$pdf->Cell(0, 20, '진 술 서', 0, 1, 'C');
	
	// 법원 정보
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
	
	// 신청인 정보
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '신청인 '.$basic_info['name'].' (인)', 0, 1, 'R');
	
	// 진술서 내용 설명
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->MultiCell(0, 6, "신청인은 다음과 같은 내용을 사실대로 진술합니다.\n또 본인의 현재의 재무, 자산, 생활의 상황 및 수입 지출 등은, 별지 「채권자목록」, 「재산목록」, 「현재의 생활상황」, 「수입 및 지출에 관한 목록」의 각 기재와 같습니다.", 0, 'L');
	
	// 경고 문구
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->MultiCell(0, 6, "위 각 서류에 사실과 다른 내용이 있을 경우 면책불허가될 수 있음을 잘 알고 있습니다.", 0, 'L');
	
}

/**
 * 학력 및 경력 정보 진술서 생성
 */
function generateEducationCareerPage($pdf, $pdo, $case_no, $basic_info) {
	// 제목
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '1. 본인의 과거 경력은 다음과 같습니다.', 0, 1, 'L');
	
	// 최종 학력 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_education
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$education = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// 최종 학력 정보 출력
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(1) 최종 학력', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($education) {
		$year = !empty($education['graduation_date']) ? date('Y', strtotime($education['graduation_date'])) : '';
		$graduation_status = $education['graduation_status'] ?? '';
		$pdf->Cell(30, 8, $year, 0, 0, 'L');
		$pdf->Cell(0, 8, $education['school_name'] . ' ( ' . $graduation_status . ' )', 0, 1, 'L');
	} else {
		$pdf->Cell(0, 8, '-', 0, 1, 'L');
	}
	
	// 과거 경력 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_career
		WHERE case_no = ?
		ORDER BY work_start_date DESC
	");
	$stmt->execute([$case_no]);
	$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 과거 경력 정보 출력
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(2) 과거 경력(최근의 것부터 기재하여 주십시오)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if (count($careers) > 0) {
		foreach ($careers as $career) {
			$start_date = !empty($career['work_start_date']) ? date('Y년 m월 d일', strtotime($career['work_start_date'])) : '';
			$end_date = !empty($career['work_end_date']) ? date('Y년 m월 d일', strtotime($career['work_end_date'])) : '현재까지';
			
			$pdf->Cell(70, 8, $start_date . '부터 ' . $end_date . '까지', 0, 0, 'L');
			$pdf->Cell(30, 8, '( ' . $career['company_type'] . ' )', 0, 0, 'L');
			$pdf->Cell(40, 8, $career['company_name'], 0, 0, 'L');
			$pdf->Cell(0, 8, $career['position'] . ' / ' . $career['business_type'], 0, 1, 'L');
		}
	} else {
		$pdf->Cell(0, 8, '-', 0, 1, 'L');
	}
	
	// 동시 개인파산 신청 가족 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_domestic_court
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$domestic_court = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// 동시 개인파산 신청 가족 정보 출력
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Ln(5);
	$pdf->Cell(0, 10, '2. 동시에 개인파산을 신청할 가족이 있는지 여부', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($domestic_court) {
		$spouse_name = !empty($domestic_court['spouse_name']) ? $domestic_court['spouse_name'] : '없음';
		$pdf->Cell(0, 8, '(1) 배우자(성명 : ' . $spouse_name . ')와 동시에 개인파산을 신청하는 것이 (있음/없음)', 0, 1, 'L');
		
		$has_spouse = !empty($domestic_court['spouse_name']) ? '있음' : '없음';
		
		$pdf->Cell(0, 8, '(2) 배우자 외의 다른 가족과 동시에 개인파산을 신청하는 것이 (' . $has_spouse . ')', 0, 1, 'L');
		
		if (!empty($domestic_court['other_family_members'])) {
			$pdf->MultiCell(0, 8, '(배우자외의 다른 가족과 동시에 개인파산을 신청하는 경우 성명 및 신청인과의 관계를 기재하여 주십시오)', 0, 'L');
			$pdf->MultiCell(0, 8, $domestic_court['other_family_members'], 0, 'L');
		}
	} else {
		$pdf->Cell(0, 8, '(1) 배우자(성명 : 없음)와 동시에 개인파산을 신청하는 것이 (없음)', 0, 1, 'L');
		$pdf->Cell(0, 8, '(2) 배우자 외의 다른 가족과 동시에 개인파산을 신청하는 것이 (없음)', 0, 1, 'L');
	}
	
	// 현재까지의 생활상황 제목
	$pdf->SetFont('cid0kr', 'B', 12);

	$pdf->Cell(0, 10, '3. 본인의 현재까지의 생활상황 등은 다음과 같습니다.', 0, 1, 'L');
}

/**
 * 현재까지의 생활상황 진술서 생성
 */
function generateLifeHistoryPage($pdf, $pdo, $case_no, $basic_info) {
	// 생활 내역 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_life_history
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$life_history = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$life_history) {
		return;
	}
	
	// 파산 내역 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_bankruptcy_history
		WHERE case_no = ?
		ORDER BY date DESC
	");
	$stmt->execute([$case_no]);
	$bankruptcy_histories = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 사기죄 관련 정보 출력
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(1) 사기죄, 사기파산죄, 과태파산죄, 도박죄로 고소되거나 형사재판을 받은 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($life_history['fraud_experience'] == '있음') {
		$pdf->Cell(0, 8, '있음 - ' . $life_history['fraud_reason'], 0, 1, 'L');
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 과거 파산 신청 경험
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(2) 과거에 파산신청을 하였다가 취하하거나 기각당한 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($life_history['past_bankruptcy'] == '있음') {
		$pdf->Cell(0, 8, '있음 - 법원에 파산신청을 하였는데 ( 기각당함 )', 0, 1, 'L');
		
		// 파산 내역 출력
		if (!empty($bankruptcy_histories) && count($bankruptcy_histories) > 0) {
			foreach ($bankruptcy_histories as $history) {
				if (!empty($history['date'])) {
					$date = date('Y년 m월 d일', strtotime($history['date']));
					$court = $history['court'] ?? '';
					$status = is_array($history['status']) ? implode(', ', $history['status']) : $history['status'];
					
					$pdf->Cell(0, 8, $date . ' - ' . $court . ' 법원에서 ' . $status, 0, 1, 'L');
				}
			}
		}
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 파산 선고 경험
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(3) 과거에 파산선고를 받은 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($life_history['past_bankruptcy_declared'] == '있음' && !empty($life_history['bankruptcy_declared_date'])) {
		$declared_date = date('Y년 m월 d일', strtotime($life_history['bankruptcy_declared_date']));
		$pdf->Cell(0, 8, '있음 - ' . $declared_date . ' ' . $life_history['bankruptcy_declared_court'] . ' 법원에서 파산선고를 받음', 0, 1, 'L');
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 면책 경험
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(4) 그 파산선고에 이어서 면책을 받은 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($life_history['past_discharge'] == '있음' && !empty($life_history['discharge_date'])) {
		$discharge_date = date('Y년 m월 d일', strtotime($life_history['discharge_date']));
		$pdf->Cell(0, 8, '있음 - ' . $discharge_date . ' ' . $life_history['discharge_court'] . ' 법원에서 면책결정을 받았고,', 0, 1, 'L');
		
		if (!empty($life_history['discharge_confirmed_date'])) {
			$confirmed_date = date('Y년 m월 d일', strtotime($life_history['discharge_confirmed_date']));
			$pdf->Cell(0, 8, '         ' . $confirmed_date . ' 위 결정이 확정됨', 0, 1, 'L');
		}
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 미지급 물품 관련 정보
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(4) 과거 1년간 물건을 할부나 외상로 구입하고 대금을 전부 지급하지 않은 상태에서 처분(매각, 임질 등)을 한 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if ($life_history['unpaid_sales'] == '있음') {
		$unpaid_info = '';
		
		if (!empty($life_history['unpaid_goods_name'])) {
			$unpaid_info .= '품명: ' . $life_history['unpaid_goods_name'];
		}
		
		if (!empty($life_history['unpaid_purchase_date'])) {
			$unpaid_purchase_date = date('Y년 m월 d일', strtotime($life_history['unpaid_purchase_date']));
			$unpaid_info .= ', 구입시기: ' . $unpaid_purchase_date;
		}
		
		if (!empty($life_history['unpaid_price'])) {
			$unpaid_info .= ', 가격: ' . number_format($life_history['unpaid_price']) . '원';
		}
		
		if (!empty($life_history['unpaid_disposal_date'])) {
			$unpaid_disposal_date = date('Y년 m월 d일', strtotime($life_history['unpaid_disposal_date']));
			$unpaid_info .= ', 처분시기: ' . $unpaid_disposal_date;
		}
		
		if (!empty($life_history['unpaid_disposal_method'])) {
			$unpaid_info .= ', 처분방법: ' . $life_history['unpaid_disposal_method'];
		}
		
		$pdf->MultiCell(0, 8, '있음 - ' . $unpaid_info, 0, 'L');
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 개인 영업 관련 정보
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(5) 이번 항목은 개인 영업을 경영한 경험이 있는 분만 기재하여 주십시오.', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	$pdf->Cell(15, 8, '▶', 0, 0, 'L');
	$pdf->Cell(0, 8, '영업 중 상업장부의 기재', 0, 1, 'L');
	
	// 체크박스 그리기
	$business_record_type = [];
	if (!empty($life_history['business_record_type'])) {
		$business_record_type = is_array($life_history['business_record_type']) ? 
			$life_history['business_record_type'] : 
			explode(',', $life_history['business_record_type']);
	}
	
	$pdf->Rect(30, $pdf->GetY(), 5, 5);
	$pdf->Cell(30, 8, '', 0, 0);
	$pdf->Cell(60, 8, '정확히 기장하였다.', 0, 0, 'L');
	
	$pdf->Rect(130, $pdf->GetY(), 5, 5);
	$pdf->Cell(30, 8, '', 0, 0);
	$pdf->Cell(60, 8, '부정확하게 기장하였다.', 0, 0, 'L');
	
	$pdf->Rect(230, $pdf->GetY(), 5, 5);
	$pdf->Cell(30, 8, '', 0, 0);
	$pdf->Cell(0, 8, '기장하지 아니하였다.', 0, 1, 'L');
	
	// 상업 장부 종류
	$pdf->Cell(15, 8, '▶', 0, 0, 'L');
	$pdf->Cell(0, 8, '영업중에 도산을 면하기 위하여 상품을 부당하게 염가로 매각한 사실 (있음/없음)', 0, 1, 'L');
	
	if (!empty($life_history['unfair_sale']) && $life_history['unfair_sale'] == '있음') {
		$unfair_info = '';
		
		if (!empty($life_history['unfair_sale_product'])) {
			$unfair_info .= '상품명: ' . $life_history['unfair_sale_product'];
		}
		
		if (!empty($life_history['unfair_sale_date'])) {
			$unfair_sale_date = date('Y년 m월 d일', strtotime($life_history['unfair_sale_date']));
			$unfair_info .= ', 매각시기: ' . $unfair_sale_date;
		}
		
		if (!empty($life_history['unfair_discount_rate'])) {
			$unfair_info .= ', 할인율: ' . $life_history['unfair_discount_rate'];
		}
		
		$pdf->MultiCell(0, 8, '있음 - ' . $unfair_info, 0, 'L');
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
}

/**
 * 채권자 상황 진술서 생성
 */
function generateCreditorStatusPage($pdf, $pdo, $case_no, $basic_info) {
	// 채권자 상황 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_creditor_status
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$creditor_status = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$creditor_status) {
		return;
	}
	
	// 법적 조치 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_legal_action
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$legal_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// 채권자 상황 제목
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '4. 채권자와의 상황은 다음과 같습니다.', 0, 1, 'L');
	
	// 채권자 교섭 경험
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(1) 채권자와 채무지급방법에 관하여 교섭한 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if (!empty($creditor_status['negotiation_experience']) && $creditor_status['negotiation_experience'] == '있음') {
		$pdf->Cell(0, 8, '▶ 그 결과 합의가 성립된 채권자수 ' . $creditor_status['agreed_creditors_count'] . '명', 0, 1, 'L');
		
		if (!empty($creditor_status['payment_period_start']) && !empty($creditor_status['payment_period_end'])) {
			$start_date = date('Y년 m월', strtotime($creditor_status['payment_period_start']));
			$end_date = date('Y년 m월', strtotime($creditor_status['payment_period_end']));
			
			$pdf->Cell(0, 8, '▶ 합의에 기하여 지급한 기간', 0, 1, 'L');
			$pdf->Cell(0, 8, '(' . $start_date . '부터 ' . $end_date . '까지)', 0, 1, 'L');
		}
		
		if (!empty($creditor_status['monthly_payment_amount'])) {
			$pdf->Cell(0, 8, '▶ 매월 지급한 총액    ' . number_format($creditor_status['monthly_payment_amount']) . '원 정도', 0, 1, 'L');
		}
		
		if (!empty($creditor_status['creditor_payment_details'])) {
			$pdf->Cell(0, 8, '▶ 지급 내역 (누구에게 얼마를 지급하였는지를 기재하여 주십시오)', 0, 1, 'L');
			$pdf->MultiCell(0, 8, $creditor_status['creditor_payment_details'], 0, 'L');
		}
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
	
	// 소송 압류 경험
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(2) 소송, 지급명령, 압류, 가압류 등을 받은 경험 (있음/없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if (!empty($creditor_status['legal_action']) && $creditor_status['legal_action'] == '있음' && !empty($legal_actions)) {
		foreach ($legal_actions as $action) {
			$legal_info = '';
			
			if (!empty($action['court'])) {
				$legal_info .= $action['court'];
			}
			
			if (!empty($action['case_number'])) {
				$legal_info .= ' / ' . $action['case_number'];
			}
			
			if (!empty($action['creditor'])) {
				$legal_info .= ' / ' . $action['creditor'];
			}
			
			if (!empty($legal_info)) {
				$pdf->Cell(0, 8, $legal_info, 0, 1, 'L');
			}
		}
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
}

/**
 * 파산신청 사유 진술서 생성
 */
function generateBankruptcyReasonPage($pdf, $pdo, $case_no, $basic_info) {
	// 파산 신청 사유 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_bankruptcy_reason
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$bankruptcy_reason = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$bankruptcy_reason) {
		return;
	}
	
	// 파산신청 사유 제목
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '5. 파산신청에 이르게 된 사정 (채무 증대의 경위 및 지급이 불가능하게 된 사정)(구체적으로 표시)', 0, 1, 'L');
	
	// 채무 원인
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(1) 많은 채무(연대보증에 의한 채무나 신용카드 이용에 의한 채무를 포함한다)를 지게 된 이유는 다음과 같습니다(두 가지 이상 선택 가능).', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	
	// 채무 원인 체크박스
	$debt_reasons = [];
	if (!empty($bankruptcy_reason['debt_reason'])) {
		$debt_reasons = is_array($bankruptcy_reason['debt_reason']) ? 
			$bankruptcy_reason['debt_reason'] : 
			explode(',', $bankruptcy_reason['debt_reason']);
	}
	
	$checkboxes = [
		'생활비 부족' => '■생활비 부족 (부양가족수 : ' . ($bankruptcy_reason['dependents_count'] ?? '') . '), (부족한 생활비 : ' . ($bankruptcy_reason['living_expense_shortage_items'] ?? '') . ')',
		'사업 실패' => '■사업의 경영 파탄 (다단계 사업 포함) (사업 시기 : ' . (!empty($bankruptcy_reason['business_start_date']) ? date('Y년 m월', strtotime($bankruptcy_reason['business_start_date'])) : '') . '부터 ' . (!empty($bankruptcy_reason['business_end_date']) ? date('Y년 m월', strtotime($bankruptcy_reason['business_end_date'])) : '') . '까지) (사업 종류 : ' . ($bankruptcy_reason['business_type_detail'] ?? '') . ')',
		'타인(친족, 지인, 회사 등)의 채무 보증' => '■타인(친족, 지인, 회사 등)의 채무 보증',
		'사기 피해를 당함' => '■사기 피해를 당함 (기망을 한 사람 및 채무자와의 관계 : ' . ($bankruptcy_reason['fraud_perpetrator_name'] ?? '') . ', ' . ($bankruptcy_reason['fraud_perpetrator_relationship'] ?? '') . ') (피해액수 : ' . (!empty($bankruptcy_reason['fraud_damage_amount']) ? number_format($bankruptcy_reason['fraud_damage_amount']) : '0') . '원)',
		'그 밖의 사유' => '■그 밖의 사유 : ' . ($bankruptcy_reason['debt_reason_other_detail'] ?? ''),
	];
	
	foreach ($checkboxes as $key => $text) {
		if (in_array($key, $debt_reasons)) {
			$pdf->MultiCell(0, 8, $text, 0, 'L');
		} else {
			// 체크되지 않은 항목은 □로 표시
			$pdf->MultiCell(0, 8, str_replace('■', '□', $text), 0, 'L');
		}
	}
	
	// 지급불능 원인
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(2) 지급이 불가능하게 된 계기는 다음과 같습니다(두 가지 이상 선택 가능)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	
	// 지급불능 원인 체크박스
	$inability_reasons = [];
	if (!empty($bankruptcy_reason['inability_reason'])) {
		$inability_reasons = is_array($bankruptcy_reason['inability_reason']) ? 
			$bankruptcy_reason['inability_reason'] : 
			explode(',', $bankruptcy_reason['inability_reason']);
	}
	
	$checkboxes = [
		'변제하여 할 원리금이 불어나 수입을 초과해 됨' => '■변제하여 할 원리금이 불어나 수입을 초과해 됨',
		'실직함' => '□실직함',
		'경영 사정 악화로 사업 폐업' => '□경영 사정 악화로 사업 폐업',
		'급여 또는 사업 소득이 감소됨' => '□급여 또는 사업 소득이 감소됨',
		'병에 걸려 입원함' => '□병에 걸려 입원함',
		'그 밖의 사유' => '□그 밖의 사유 : ' . ($bankruptcy_reason['inability_reason_other_detail'] ?? ''),
	];
	
	foreach ($checkboxes as $key => $text) {
		if (in_array($key, $inability_reasons)) {
			$pdf->Cell(0, 8, str_replace('□', '■', $text), 0, 1, 'L');
		} else {
			$pdf->Cell(0, 8, $text, 0, 1, 'L');
		}
	}
	
	// 지급불능 시기
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(3) 지급이 불가능하게 된 시점 : ' . (!empty($bankruptcy_reason['inability_reason_other_date']) ? date('Y년 m월 경', strtotime($bankruptcy_reason['inability_reason_other_date'])) : ''), 0, 1, 'L');
	
	// 구체적 사정
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '(4) 구체적 사정', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	// 테이블 생성 - 사정 정보
	$pdf->Cell(30, 10, '시기(연월일)', 1, 0, 'C');
	$pdf->Cell(0, 10, '채권자, 차용(보증) 액수, 차용한 돈의 사용처, 지급이 불가능하게 된 사정 등', 1, 1, 'C');
	
	// 구체적 사정 내용
	if (!empty($bankruptcy_reason['inability_timeline'])) {
		$pdf->MultiCell(0, 8, $bankruptcy_reason['inability_timeline'], 0, 'L');
	}
}

/**
 * 지급불능 이후 채무 발생 진술서 생성
 */
function generateDebtAfterInsolvencyPage($pdf, $pdo, $case_no, $basic_info) {
	// 지급불능 이후 채무 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_debt_after_insolvency
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($debts)) {
		return;
	}
	
	// 지급불능 이후 채무 제목
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '6. 지급이 불가능하게 된 시기 이후에 차용하거나 채무가 발생한 사실 (없음)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	$debt_exists = false;
	
	foreach ($debts as $debt) {
		if (!empty($debt['debt_after_insolvency']) && $debt['debt_after_insolvency'] == '있음') {
			$debt_exists = true;
			
			$debt_info = '';
			if (!empty($debt['date'])) {
				$debt_info .= '시기: ' . date('Y년 m월', strtotime($debt['date']));
			}
			
			if (!empty($debt['reason'])) {
				$debt_info .= ', 원인: ' . $debt['reason'];
			}
			
			if (!empty($debt['amount'])) {
				$debt_info .= ', 금액: ' . number_format($debt['amount']) . '원';
			}
			
			if (!empty($debt['debt_condition'])) {
				$debt_info .= ', 조건: ' . $debt['debt_condition'];
			}
			
			if (!empty($debt_info)) {
				$pdf->Cell(0, 8, $debt_info, 0, 1, 'L');
			}
		}
	}
	
	if (!$debt_exists) {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
}

/**
 * 일부 채권자에게만 변제한 경험 진술서 생성
 */
function generatePartialRepaymentPage($pdf, $pdo, $case_no, $basic_info) {
	// 일부 채권자 변제 정보 조회
	$stmt = $pdo->prepare("
		SELECT * FROM application_bankruptcy_statement_partial_repayment
		WHERE case_no = ?
	");
	$stmt->execute([$case_no]);
	$partial_repayment = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$partial_repayment) {
		return;
	}
	
	// 일부 채권자 변제 제목
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '7. 채무의 지급이 불가능하게 된 시점 이후에 일부 채권자에게만 변제한 경험 (있음) (변제한 채권자의 성명, 변제시기, 금액을 전부 기재하여 주십시오)', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 9);
	if (!empty($partial_repayment['partial_repayment']) && $partial_repayment['partial_repayment'] == '있음') {
		$repayment_info = '';
		
		if (!empty($partial_repayment['date'])) {
			$repayment_info .= '변제일: ' . date('Y년 m월 d일', strtotime($partial_repayment['date']));
		}
		
		if (!empty($partial_repayment['creditor_name'])) {
			$repayment_info .= ', 변제 채권자명: ' . $partial_repayment['creditor_name'];
		}
		
		if (!empty($partial_repayment['amount'])) {
			$repayment_info .= ', 변제 금액: ' . number_format($partial_repayment['amount']) . '원';
		}
		
		if (!empty($repayment_info)) {
			$pdf->Cell(0, 8, $repayment_info, 0, 1, 'L');
		}
	} else {
		$pdf->Cell(0, 8, '없음', 0, 1, 'L');
	}
}
?>