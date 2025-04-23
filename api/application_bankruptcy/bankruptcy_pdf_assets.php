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
		$pdf->AddPage();
		
		// 제목
		$pdf->SetFont('cid0kr', 'B', 16);
		$pdf->Cell(0, 10, '재산목록', 0, 1, 'C');
		
		// 신청인 정보
		$pdf->SetFont('cid0kr', '', 10);
		$pdf->Cell(20, 10, '신청인:', 0, 0);
		$pdf->Cell(80, 10, $basic_info['name'], 0, 0);
		
		$pdf->Cell(20, 10, '사건번호:', 0, 0);
		$pdf->Cell(80, 10, $basic_info['case_number'] ?? '', 0, 1);
		
		$pdf->Ln(5);
		
		// 재산 요약 정보 표시
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '보유 재산 요약', 0, 1, 'L');
		
		$pdf->SetFont('cid0kr', '', 10);
		
		// 각 재산 유형별로 보유 여부 표시
		if (!empty($asset_summary)) {
			$asset_types = [
				'현금' => $asset_summary['cash_exists'] ?? 'N',
				'예금' => $asset_summary['deposit_exists'] ?? 'N',
				'보험' => $asset_summary['insurance_exists'] ?? 'N',
				'임차보증금' => $asset_summary['rent_deposit_exists'] ?? 'N',
				'대여금채권' => $asset_summary['loan_receivables_exists'] ?? 'N',
				'매출채권' => $asset_summary['sales_receivables_exists'] ?? 'N',
				'퇴직금' => $asset_summary['severance_pay_exists'] ?? 'N',
				'부동산' => $asset_summary['real_estate_exists'] ?? 'N',
				'자동차' => $asset_summary['vehicle_exists'] ?? 'N',
				'기타재산' => $asset_summary['other_assets_exists'] ?? 'N',
				'처분재산' => $asset_summary['disposed_assets_exists'] ?? 'N',
				'임대차보증금(수령)' => $asset_summary['received_deposit_exists'] ?? 'N',
				'이혼재산분할' => $asset_summary['divorce_property_exists'] ?? 'N',
				'상속재산' => $asset_summary['inherited_property_exists'] ?? 'N'
			];
			
			// 테이블 형식으로 표시
			$pdf->SetFont('cid0kr', 'B', 9);
			$pdf->Cell(40, 10, '재산 유형', 1, 0, 'C');
			$pdf->Cell(20, 10, '보유 여부', 1, 0, 'C');
			$pdf->Cell(40, 10, '재산 유형', 1, 0, 'C');
			$pdf->Cell(20, 10, '보유 여부', 1, 0, 'C');
			$pdf->Cell(40, 10, '재산 유형', 1, 0, 'C');
			$pdf->Cell(20, 10, '보유 여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 9);
			$i = 0;
			foreach ($asset_types as $type => $exists) {
				// 3개의 컬럼씩 표시
				if ($i % 3 == 0 && $i > 0) {
					$pdf->Ln();
				}
				
				$pdf->Cell(40, 10, $type, 1, 0, 'L');
				$pdf->Cell(20, 10, ($exists == 'Y' ? '있음' : '없음'), 1, 0, 'C');
				
				$i++;
				
				// 마지막 행이고, 아직 컬럼이 채워지지 않은 경우 빈 셀로 채움
				if ($i == count($asset_types) && $i % 3 != 0) {
					for ($j = 0; $j < 3 - ($i % 3); $j++) {
						$pdf->Cell(40, 10, '', 1, 0, 'L');
						$pdf->Cell(20, 10, '', 1, 0, 'C');
					}
				}
				
				if ($i % 3 == 0) {
					$pdf->Ln();
				}
			}
			
			if ($i % 3 != 0) {
				$pdf->Ln();
			}
		} else {
			$pdf->Cell(0, 10, '등록된 재산 요약 정보가 없습니다.', 1, 1, 'C');
		}
		
		$pdf->Ln(5);
		
		// 각 재산 유형별 상세 정보 출력
		generateAssetDetails($pdf, $pdo, $case_no, $asset_summary);
		
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

function generateAssetDetails($pdf, $pdo, $case_no, $asset_summary) {
	// 재산 유형 목록
	$asset_categories = [
		['현금', 'cash_exists', 'application_bankruptcy_asset_cash', 'property_detail', 'liquidation_value'],
		['예금', 'deposit_exists', 'application_bankruptcy_asset_deposits', 'bank_name, account_number', 'deposit_amount'],
		['보험', 'insurance_exists', 'application_bankruptcy_asset_insurance', 'company_name, securities_number', 'refund_amount'],
		['임차보증금', 'rent_deposit_exists', 'application_bankruptcy_asset_rent_deposits', 'rent_location', 'rent_deposit'],
		['대여금채권', 'loan_receivables_exists', 'application_bankruptcy_asset_loan_receivables', 'debtor_name', 'claim_amount'],
		['매출채권', 'sales_receivables_exists', 'application_bankruptcy_asset_sales_receivables', 'debtor_name', 'claim_amount'],
		['퇴직금', 'severance_pay_exists', 'application_bankruptcy_asset_severance', 'workplace', 'expected_amount'],
		['부동산', 'real_estate_exists', 'application_bankruptcy_asset_real_estate', 'property_location, property_type', 'market_value'],
		['자동차', 'vehicle_exists', 'application_bankruptcy_asset_vehicles', 'vehicle_info, registration_number', 'market_value'],
		['기타재산', 'other_assets_exists', 'application_bankruptcy_asset_other', 'asset_content', 'liquidation_value']
	];
	
	// 각 재산 유형별로 상세 정보 출력
	foreach ($asset_categories as $category) {
		[$title, $exists_key, $table, $desc_fields, $value_field] = $category;
		
		if (!empty($asset_summary) && $asset_summary[$exists_key] == 'Y') {
			$pdf->AddPage();
			$pdf->SetFont('cid0kr', 'B', 14);
			$pdf->Cell(0, 10, $title . ' 상세 정보', 0, 1, 'C');
			$pdf->Ln(5);
			
			// 재산 상세 정보 조회
			$stmt = $pdo->prepare("SELECT * FROM $table WHERE case_no = ? ORDER BY property_no");
			$stmt->execute([$case_no]);
			$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (count($assets) > 0) {
				// 테이블 헤더
				$pdf->SetFont('cid0kr', 'B', 9);
				$pdf->Cell(10, 10, '번호', 1, 0, 'C');
				$pdf->Cell(100, 10, '상세 정보', 1, 0, 'C');
				$pdf->Cell(30, 10, '금액', 1, 0, 'C');
				$pdf->Cell(20, 10, '압류 여부', 1, 1, 'C');
				
				$pdf->SetFont('cid0kr', '', 8);
				$total_value = 0;
				
				foreach ($assets as $index => $asset) {
					// 상세 정보 필드가 여러 개인 경우 처리
					$desc_field_arr = explode(',', $desc_fields);
					$desc_value = '';
					
					foreach ($desc_field_arr as $field) {
						$field = trim($field);
						if (!empty($asset[$field])) {
							$desc_value .= ($desc_value ? ', ' : '') . $asset[$field];
						}
					}
					
					// 가치 금액 추출
					$value = !empty($asset[$value_field]) ? (int)$asset[$value_field] : 0;
					$total_value += $value;
					
					// 압류 여부
					$is_seized = !empty($asset['is_seized']) && $asset['is_seized'] == 'Y' ? '있음' : '없음';
					
					// 데이터 행 출력
					$lineHeight = 8;
					$pdf->Cell(10, $lineHeight, ($index + 1), 1, 0, 'C');
					
					// 시작 Y 위치 저장
					$startY = $pdf->GetY();
					
					// 상세 정보 - MultiCell을 사용하여 자동 줄바꿈
					$pdf->MultiCell(100, $lineHeight, $desc_value, 1, 'L');
					$endY = $pdf->GetY();
					
					// 다음 셀의 위치 조정
					$pdf->SetXY($pdf->GetX() + 110, $startY);
					
					// 금액, 압류 여부 출력
					$pdf->Cell(30, $endY - $startY, number_format($value), 1, 0, 'R');
					$pdf->Cell(20, $endY - $startY, $is_seized, 1, 1, 'C');
					
					// 페이지가 넘어갈 경우 헤더 다시 출력
					if ($pdf->GetY() > 250 && ($index + 1) < count($assets)) {
						$pdf->AddPage();
						
						// 제목 (계속)
						$pdf->SetFont('cid0kr', 'B', 14);
						$pdf->Cell(0, 10, $title . ' 상세 정보 (계속)', 0, 1, 'C');
						$pdf->Ln(5);
						
						// 테이블 헤더 다시 출력
						$pdf->SetFont('cid0kr', 'B', 9);
						$pdf->Cell(10, 10, '번호', 1, 0, 'C');
						$pdf->Cell(100, 10, '상세 정보', 1, 0, 'C');
						$pdf->Cell(30, 10, '금액', 1, 0, 'C');
						$pdf->Cell(20, 10, '압류 여부', 1, 1, 'C');
						
						$pdf->SetFont('cid0kr', '', 8);
					}
				}
				
				// 합계 출력
				$pdf->SetFont('cid0kr', 'B', 9);
				$pdf->Cell(110, 10, '합계', 1, 0, 'R');
				$pdf->Cell(30, 10, number_format($total_value), 1, 0, 'R');
				$pdf->Cell(20, 10, '', 1, 1);
			} else {
				$pdf->SetFont('cid0kr', '', 10);
				$pdf->Cell(0, 10, '등록된 ' . $title . ' 정보가 없습니다.', 1, 1, 'C');
			}
		}
	}
}