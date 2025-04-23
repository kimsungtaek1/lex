<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfAssets($pdf, $pdo, $case_no) {
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
		
		// 재산 요약 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_asset_summary
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$asset_summary = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 재산 목록 페이지 생성
		$pdf->AddPage(); // 반드시 1회는 필요!
		
		// 제목
		$pdf->SetFont('cid0kr', 'B', 16);
		$pdf->Cell(0, 15, '재 산 목 록', 0, 1, 'C');
		
		// 요약표 설명
		$pdf->SetFont('cid0kr', '', 9);
		$pdf->MultiCell(0, 5, "※ 면저, 다음 재산목록 요약표에 해당재산이 있는지 √ 하고, 「□ 있음」에 √ 한 경우에는 아래 해당 항목에서 자세히 기재바랍니다.\n이 양식을 파일형태로 이용할 경우 아래 표 중 에 「□ 있음」에 √ 한 부분만 출력하여 제출하여도 됩니다. 따라서 모두 「□ 있음」에 √ 한 경우에는 아래 표 다음 부분을 생략할 수 있습니다\n(실제로는 재산 처분이 있었음에도 불구하고 「처분물가는 시점의 1년 이전부터 현재까지 재산 처분 여부' 의 '없음' 에 √ 해 놓고는 부동산등기부등본 등 소명자료를 목부문에 면접해놓는 경우가 있는데 이와 같이 재산목록 요약표와 소명자료 또는 진술서의 기재내용이 서로 불일치한 경우에는 허위진술 내지 불성실한 신청으로 간주되어 불이익한 처분을 받을 수 있습니다).", 0, 'L');
		
		$pdf->Ln(3);
		
		// 재산 요약 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '재산목록 요약표', 0, 1, 'C');
		
		// 표를 이미지처럼 각 항목별 셀로, 일부는 MultiCell로(특히 11번 항목)
		$pdf->SetFont('cid0kr', '', 9);
		$cellH = 10;
		$lineH = 5;
		// 1행
		$pdf->Cell(35, $cellH, '1.현금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['cash_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '8.부동산', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['real_estate_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');
		
		// 2행
		$pdf->Cell(35, $cellH, '2.예금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['deposit_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '9.자동차오토바이', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['vehicle_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');
		
		// 3행
		$pdf->Cell(35, $cellH, '3.보험', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['insurance_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '10.기타재산(주식,특허권,귀금속 등)', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['other_assets_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');

		// 4행
		$pdf->Cell(35, $cellH, '4.임차보증금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['rent_deposit_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '11.지급불가능시점의 1년이전부터현재까지재산처분여부', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['disposed_assets_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');

		// 5행
		$pdf->Cell(35, $cellH, '5.대여금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['loan_receivables_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '12.최근2년간받은임차보증금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['received_deposit_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');

		// 6행
		$pdf->Cell(35, $cellH, '6.매출금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['sales_receivables_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '13.이혼재산분할', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['divorce_property_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');
		
		
		// 7행
		$pdf->Cell(35, $cellH, '7.퇴직금', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['severance_pay_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 0, 'C');
		$pdf->Cell(95, $cellH, '14.상속재산', 1, 0, 'L');
		$pdf->Cell(25, $cellH, ($asset_summary['inherited_property_exists']=='Y'?'■있음 □없음':'□있음 ■없음'), 1, 1, 'C');
		
		$pdf->Ln(5);
		
		// 각 재산 유형별 상세 내용 출력
		outputAssetDetails($pdf, $pdo, $case_no, $asset_summary);
		
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

function outputAssetDetails($pdf, $pdo, $case_no, $asset_summary) {
	// 1. 현금
	if ($asset_summary['cash_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '1. 현금 : 금액(123,123원)', 0, 1, 'L');
		
		// 현금 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_cash WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$cash_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($cash_assets) > 0) {
			foreach ($cash_assets as $asset) {
				$pdf->MultiCell(0, 8, $asset['property_detail'] . ' - ' . number_format($asset['liquidation_value']) . '원', 0, 'L');
			}
		} else {
			$pdf->Cell(0, 10, '현금 자산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 2. 예금
	if ($asset_summary['deposit_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '2. 예금', 0, 1, 'L');
		
		// 예금 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_deposits WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$deposit_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($deposit_assets) > 0) {
			foreach ($deposit_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"금융기관명({$asset['bank_name']}) / 계좌번호({$asset['account_number']}) / 잔고(" . number_format($asset['deposit_amount']) . "원)\n\n" .
					"☆ 은행 이외의 금융기관에 대한 것도 포함합니다.\n" .
					"☆ 예금잔고가 소액이라도 반드시 기재하고 파산신청시의 잔고(정기예금통장 포함)와 최종 금융거래내역으로부터 과거 1년간의 입출금이 기재된 통장 사본 또는 예금거래내역서를 첨부하여 주십시오(공과금, 통신료, 카드사용, 급여이체 등이 기재된 통장 사본 또는 예금거래내역서를 제출, 가족명의의 계좌로 거래했다면 그 계좌에 관한 통장 사본 또는 예금거래내역서를 제출).",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '예금 자산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 3. 보험
	if ($asset_summary['insurance_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '3. 보험(생명보험, 화재보험, 자동차보험 등)', 0, 1, 'L');
		
		// 보험 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_insurance WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$insurance_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($insurance_assets) > 0) {
			foreach ($insurance_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"금융기관명({$asset['company_name']}) / 증권번호({$asset['securities_number']}) / 해약반환금(" . number_format($asset['refund_amount']) . "원)\n\n" .
					"☆ 파산신청 당시에 가입하고 있는 보험은 해약반환금이 있는 경우에도 반드시 전부 기재하여 주십시오.\n" .
					"☆ 생명보험협회에서 발급하는 제무자에 대한 생존자 보험가입내역조회를 첨부하여 주시고, 그러한 보험가입내역조회에 기재된 생명보험(손해보험, 자동차보험, 우연자·단체보험, 주말휴일생명보험을 제외)의 해지·실효·종료여부 형명서도 첨부하여 주십시오.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '보험 자산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 4. 임차보증금
	if ($asset_summary['rent_deposit_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '4. 임차보증금', 0, 1, 'L');
		
		// 임차보증금 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_rent_deposits WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$rent_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($rent_assets) > 0) {
			$pdf->Cell(0, 8, '● 임차물건(sdf)', 0, 1, 'L');
			$pdf->Ln(3);
			
			foreach ($rent_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"- 임차보증금(" . number_format($asset['rent_deposit']) . "원) / 반환예상금(" . number_format($asset['expected_refund']) . "원) / 권리금(" . number_format($asset['key_money']) . "원)\n" .
					"( 비고 : {$asset['rent_location']} )\n\n" .
					"☆ 반환예상금란에는 채무자가 파산신청일을 기준으로 임대인에게 임차물건을 명도할 경우 임대인으로부터 반환 받을 수 있는 임차보증금의 예상액을 기재하여 주십시오.\n" .
					"☆ 임대차계약서의 사본 등 임차보증금 중 반환예상액을 알 수 있는 자료를 첨부하여 주십시오.\n" .
					"☆ 상가 임대차의 경우에는 권리금이 있으면 반드시 권리금 액수를 기재해 주시기 바랍니다.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '임차보증금 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 5. 대여금채권
	if ($asset_summary['loan_receivables_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '5. 대여금, 구상금, 손해배상금, 계금 등', 0, 1, 'L');
		
		// 대여금채권 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_loan_receivables WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$loan_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($loan_assets) > 0) {
			foreach ($loan_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"채무자명({$asset['debtor_name']}) ,재권금액(" . number_format($asset['claim_amount']) . "원) , 회수가능금액(" . number_format($asset['collectible_amount']) . "원)\n\n" .
					"☆ 회수가 어렵다고 하더라도 반드시 기재하시고, 대여금뿐만 아니라 구상금, 손해배상금, 계금 등 어떠한 명목으로라도 제3자로부터 받아야 할 돈이 있으면 기재하시기 바랍니다.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '대여금채권 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 6. 매출금
	if ($asset_summary['sales_receivables_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '6. 매출금(개인사업을 경영한 사실이 있는 분은 현재까지 회수하지 못한 매출금 채권)', 0, 1, 'L');
		
		// 매출금채권 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_sales_receivables WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$sales_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($sales_assets) > 0) {
			foreach ($sales_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"채무자명({$asset['debtor_name']}) ,채권금액(" . number_format($asset['claim_amount']) . "원) , 회수가능금액(" . number_format($asset['collectible_amount']) . "원)\n\n" .
					"☆ 영업장부의 사본 등 매출금을 알 수 있는 자료를 첨부하고, 번제 받는 것이 곤란한 경우에는 그 사유를 기재한 진술서 및 소명자료를 첨부하여 주십시오.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '매출금채권 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 7. 퇴직금
	if ($asset_summary['severance_pay_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '7. 퇴직금', 0, 1, 'L');
		
		// 퇴직금 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_severance WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$severance_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($severance_assets) > 0) {
			foreach ($severance_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"근무처명({$asset['workplace']}) 퇴직금예상액(" . number_format($asset['expected_amount']) . "원)\n\n" .
					"☆ 파산신청시에 퇴직하는 경우에 지급 받을 수 있는 퇴직금예상액(퇴직금이 없는 경우에는 그 취지)을 기재하여 주십시오. 만일 퇴직금채권을 담보로 하여 돈을 차용하였기 때문에 취업규칙상의 퇴직금보다 적은 액수를 지급 받게 되는 경우에는 그러한 취지를 기재하여 주시기 바랍니다.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '퇴직금 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 8. 부동산
	if ($asset_summary['real_estate_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '8. 부동산(토지와 건물)', 0, 1, 'L');
		
		// 부동산 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_real_estate WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$real_estate_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($real_estate_assets) > 0) {
			$pdf->Cell(0, 8, '● 종류( 토지 ) 소재지(12312)', 0, 1, 'L');
			$pdf->Ln(3);
			
			foreach ($real_estate_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"- 시가(" . number_format($asset['market_value']) . "원) / 등기된 담보권의 피담보채권 잔액(" . number_format($asset['secured_debt_balance']) . "원)\n" .
					"( 비고 : {$asset['property_location']} )\n\n" .
					"(가)압류 등 내용 | {$asset['seizure_details']}\n\n" .
					"☆ 부동산을 소유하고 있는 경우 부동산등기사항전부증명서를 첨부하여 주십시오\n" .
					"☆ 저당권 등 등기된 담보권에 대하여는 은행 등 담보권자가 작성한 피담보채권의 잔액증명서 등의 증명자료를 첨부하여 주십시오(가압류나 압류는 등기된 담보권이 아니므로 그 가액을 표시할 때는 가압류나 압류임을 명시하여 주시기 바랍니다.)\n" .
					"☆ 경매진행 중일 경우에는 경매절차의 진행상태를 알 수 있는 자료를 제출하여 주십시오.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '부동산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 9. 자동차
	if ($asset_summary['vehicle_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '9. 자동차(오토바이를 포함)', 0, 1, 'L');
		
		// 자동차 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_vehicles WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$vehicle_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($vehicle_assets) > 0) {
			foreach ($vehicle_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"차종 및 연식({$asset['vehicle_info']}) / 등록번호({$asset['registration_number']}) / 시가(" . number_format($asset['market_value']) . "원)\n\n" .
					"등록된 담보권의 피담보채권 잔액(" . number_format($asset['security_debt_balance']) . "원)\n\n" .
					"( 비고 : {$asset['liquidation_explanation']} )\n\n" .
					"☆ 자동차등록원부와 시가 증명자료를 첨부하여 주십시오",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '자동차 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 10. 기타 재산
	if ($asset_summary['other_assets_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '10. 기타 재산적 가치가 있는 중요 재산권(주식, 회원권, 특허권, 귀금속, 미술품 등)', 0, 1, 'L');
		
		// 기타 재산 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_other WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$other_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($other_assets) > 0) {
			foreach ($other_assets as $asset) {
				$pdf->MultiCell(0, 8, 
					"품목명({$asset['asset_content']}) / 시가(" . number_format($asset['liquidation_value']) . "원)\n\n" .
					"☆ 처분의 시기, 대가 및 대가의 사용처를 상세히 기재하여 주시기 바랍니다. 그리고 여기서 말하는 재산의 처분에는 보험의 해약, 정기예금 등의 해약, 퇴직에 따른 퇴직금수령 등도 포함합니다. 주거이전에 따른 임차보증금의 수령에 관하여는 다음의 12항에 기재하여 주시기 바랍니다.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '기타 재산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 11. 진술서 4.(3) 기재 지금 불가능 시점의 1년 이전부터 현재까지 사이에 처분한 1,000만 원 이상의 재산
	if ($asset_summary['disposed_assets_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '11. 진술서 4.(3) 기재 지금 불가능 시점의 1년 이전부터 현재까지 사이에 처분한 1,000만 원 이상의 재산', 0, 1, 'L');
		
		// 처분 재산 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_disposed WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$disposed_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($disposed_assets) > 0) {
			foreach ($disposed_assets as $asset) {
				// 날짜 형식 변환
				$disposal_date = $asset['disposal_date'] ? date('Y년 m월 d일', strtotime($asset['disposal_date'])) : '';
				
				$pdf->MultiCell(0, 8, 
					"sdfaf\n\nsdfasdf\n\n" .
					"☆ 처분의 시기, 대가 및 대가의 사용처를 상세히 기재하여 주시기 바랍니다. 그리고 여기서 말하는 재산의 처분에는 보험의 해약, 정기예금 등의 해약, 퇴직에 따른 퇴직금수령 등도 포함합니다. 주거이전에 따른 임차보증금의 수령에 관하여는 다음의 12항에 기재하여 주시기 바랍니다.\n" .
					"☆ 특히 부동산이나 하나의 재산의 가액이 1,000만 원 이상의 재산을 처분한 경우에는 처분시기와 대가를 증명할 수 있는 부동산등기사항전부증명서, 계약서사본, 영수증사본 등을 첨부하시기 바랍니다. (경매로 처분된 경우에는 배당표 및 사건별수불내역서를 제출하여 주십시오.)",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '처분 재산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 12. 최근 2년 이내에 주거이전에 따른 임차보증금을 수령한 사실
	if ($asset_summary['received_deposit_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '12. 최근 2년 이내에 주거이전에 따른 임차보증금을 수령한 사실', 0, 1, 'L');
		
		// 수령한 임차보증금 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_received_deposit WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$received_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($received_deposits) > 0) {
			foreach ($received_deposits as $asset) {
				// 날짜 형식 변환
				$receipt_date = $asset['receipt_date'] ? date('Y년 m월 d일', strtotime($asset['receipt_date'])) : '';
				
				$pdf->MultiCell(0, 8, 
					"sfdsf\n\nasfsdf\n\n" .
					"☆ 임차물건, 임대차계약상 임차보증금의 액수와 실제로 수령한 임차보증금의 액수, 수령한 임차보증금의 사용처를 기재하여 주시기 바랍니다.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '수령한 임차보증금 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 13. 최근 2년 이내에 이혼에 따라 재산분여(할)한 사실
	if ($asset_summary['divorce_property_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '13. 최근 2년 이내에 이혼에 따라 재산분여(할)한 사실', 0, 1, 'L');
		
		// 이혼 재산분할 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_divorce WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$divorce_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($divorce_assets) > 0) {
			foreach ($divorce_assets as $asset) {
				// 날짜 형식 변환
				$divorce_date = $asset['divorce_date'] ? date('Y년 m월 d일', strtotime($asset['divorce_date'])) : '';
				
				$pdf->MultiCell(0, 8, 
					"asdsdf\n\n" .
					"☆ 분여한 재산과 그 시기를 기재하여 주십시오.\n" .
					"☆ 재산분할, 위자료 등 명목에 관계없이 다음의 자료를 제출하여 주십시오.\n\n" .
					"① 이혼에 따라 배우자에게 분여(할)한 모든 재산의 내역\n" .
					"② 협의이혼 시 미성년 자녀가 있는 경우, 양육비부담조서 제출\n" .
					"③ 재판상이혼의 경우, 판결서, 조정조서 등 재판서 및 확정증명 제출\n\n" .
					"- 이혼을 증명할 소명자료 제출\n" .
					"① 시, 구, 읍(면) 등 가족관계등록관서에 이혼신고를 하여 가족관계등록부에 기록된 경우 | 혼인관계증명서(상세)\n" .
					"② 최근 2년 이내에 재판상 이혼을 한 경우로 아직 시, 구, 읍(면)등 가족관계등록관서에 이혼신고를 하지 않은 경우 | 재판상 이혼과 관련한 재판서의 등본(조정ㆍ화해가 성립된 경우에는 그에 대한 조서 등본) 및 확정증명을 제출",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '이혼에 따른 재산분할 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
	
	// 14. 친족의 사망에 따라 상속한 사실
	if ($asset_summary['inherited_property_exists'] == 'Y') {
		// $pdf->AddPage(); // 새 페이지 추가 제거
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '14. 친족의 사망에 따라 상속한 사실', 0, 1, 'L');
		
		// 상속재산 데이터 조회
		$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_asset_inherited WHERE case_no = ? ORDER BY property_no");
		$stmt->execute([$case_no]);
		$inherited_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$pdf->SetFont('cid0kr', '', 10);
		if (count($inherited_assets) > 0) {
			foreach ($inherited_assets as $asset) {
				// 날짜 형식 변환
				$inheritance_date = $asset['inheritance_date'] ? date('Y년 m월 d일', strtotime($asset['inheritance_date'])) : '';
				
				$pdf->MultiCell(0, 8, 
					"2025-04-15 부 sfdf의 사망에 의한 상속\n상속성향 (ㄷ)\n\n" .
					"☆ ㄷ 또는 ㄹ항을 선택한 분은 주된 상속재산을 기재하여 주시기 바랍니다.\n" .
					"☆ ㄹ항을 선택한 분은 다른 상속인이 주된 상속재산을 취득하게 된 경위를 기재하여 주십시오.",
					0, 
					'L'
				);
				$pdf->Ln(5);
			}
		} else {
			$pdf->Cell(0, 10, '상속재산 정보가 없습니다.', 0, 1, 'L');
		}
		$pdf->Ln(5);
	}
}
?>