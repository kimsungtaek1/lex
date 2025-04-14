<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfApplication($pdf, $pdo, $case_no) {
	// 기본 정보 조회
	try {
		$stmt = $pdo->prepare("
			SELECT ar.*, cm.name, cm.case_number, cm.court_name, cf.*
			FROM application_recovery ar
			JOIN case_management cm ON ar.case_no = cm.case_no
			JOIN config cf
			WHERE ar.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 표지 생성
		generateCoverPage($pdf, $basic_info);
		
		// 신청서 생성
		generateApplicationForm($pdf, $pdo, $case_no, $basic_info);
		
		// 위임장 생성 (테이블 형식으로 변경)
		generatePowerOfAttorney($pdf, $basic_info);
		
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

function generateCoverPage($pdf, $basic_info) {
	// 표지 페이지 추가
	$pdf->AddPage();
	
	// A4 용지에 맞게 여백 설정
	$pdf->SetMargins(15, 15, 15);
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 22);
	$pdf->Cell(0, 20, '개인회생절차 개시신청서', 0, 1, 'C');
	$pdf->Ln(10);
	
	// 신청인 및 대리인 정보
	$pdf->SetFont('cid0kr', '', 12);
	$pdf->Cell(120, 10, '신청인', 0, 0, 'R');
	$pdf->Cell(5, 10, '', 0, 0);
	$pdf->Cell(120, 10, $basic_info['name'], 0, 1, 'L');
	
	$pdf->Cell(120, 10, '대리인', 0, 0, 'R');
	$pdf->Cell(5, 10, '', 0, 0);
	$pdf->Cell(120, 10, $basic_info['customer_name'], 0, 1, 'L');
	
	$pdf->Cell(120, 10, '', 0, 0, 'R');
	$pdf->Cell(5, 10, '', 0, 0);
	$pdf->Cell(120, 10, $basic_info['customer_representative'], 0, 1, 'L');
	
	$pdf->Ln(10);
	
	// 인지 및 송달료
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(20, 10, '인 지', 0, 0, 'L');
	$pdf->Cell(60, 10, '30,000원', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '송달료', 0, 0, 'L');
	$pdf->Cell(60, 10, '           원', 0, 1, 'L');
	
	// 사건 정보 표
	$pdf->Ln(20);
	$pdf->SetFont('cid0kr', '', 10);
	
	// 표 제작 - 우측 정렬 및 동일한 너비로 조정
	$tableWidth = 100; // 전체 표 너비 설정
	$leftColumnWidth = 30; // 좌측 컬럼 너비
	$rightColumnWidth = $tableWidth - $leftColumnWidth; // 우측 컬럼 너비

	// 오른쪽 정렬을 위한 여백 계산 (A4 페이지 너비는 210mm, 여백을 뺀 값)
	$pageWidth = 180;
	$marginLeft = ($pageWidth - $tableWidth) / 2 + 50; // 기본 15mm 여백 + 추가 정렬 여백
	$pdf->SetLeftMargin($marginLeft);

	$pdf->Cell($leftColumnWidth, 10, '사 건 번 호', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '', 1, 1, 'C');

	$pdf->Cell($leftColumnWidth, 10, '해당순위번호', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '', 1, 1, 'C');

	$pdf->Cell($leftColumnWidth, 10, '재 판 부', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '', 1, 1, 'C');

	$pdf->Cell($leftColumnWidth, 10, '주 심', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '', 1, 1, 'C');

	// 최초면담기일통지 (동일한 너비로 조정)
	$pdf->Ln(5);
	$pdf->Cell(50, 10, '최초면담기일통지', 1, 0, 'C');
	$pdf->Cell(50, 10, '영 수 인', 1, 1, 'C');

	$pdf->Cell(50, 20, '20   .   .   .  :', 1, 0, 'C');
	$pdf->Cell(50, 20, '', 1, 1, 'C');

	// 당일면담희망여부 표 - 상단 셀과 하단 셀로 구성
	$pdf->Ln(5);

	// 너비와 위치 설정
	$tableWidth = 30; // 표 너비
	$marginLeft = ($pageWidth - $tableWidth) / 2 + 15;
	$pdf->SetLeftMargin($marginLeft);

	// 상단 셀 (당일면담\n희망여부)
	$pdf->SetX($marginLeft);
	$pdf->MultiCell($tableWidth, 10, "당일면담\n희망여부", 1, 'C');

	// 하단 셀 (빈 셀)
	$pdf->SetX($marginLeft);
	$pdf->Cell($tableWidth, 20, '', 1, 1, 'C');

	// 오리지널 마진으로 복원
	$pdf->SetLeftMargin(15);
	
	// 하단 법원 정보
	$pdf->Ln(30);
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '서울회생법원 귀중', 0, 1, 'C');
}

function generateApplicationForm($pdf, $pdo, $case_no, $basic_info) {
	// 신청서 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '개인회생절차 개시신청서', 0, 1, 'C');
	
	// 신청인 정보
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(30, 10, '신청인', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(30, 10, '성 명', 1, 0, 'C');
	$pdf->Cell(50, 10, $basic_info['name'] ?? '', 1, 0, 'L');
	$pdf->Cell(40, 10, '주민등록번호', 1, 0, 'C');
	$pdf->Cell(60, 10, $basic_info['resident_number'] ?? '', 1, 1, 'L');
	
	$pdf->Cell(30, 10, '주민등록상주소', 1, 0, 'C');
	$pdf->Cell(150, 10, ($basic_info['registered_address'] ?? '') . ' (우편번호: '.$basic_info['reg_zipcode'] ?? ''.')', 1, 1, 'L');
	
	$pdf->Cell(30, 10, '현 주 소', 1, 0, 'C');
	$pdf->Cell(150, 10, ($basic_info['now_address'] ?? '') . ' (우편번호: ' . ($basic_info['now_zipcode'] ?? '') . ')', 1, 1, 'L');
	
	$pdf->Cell(30, 10, '직장 주소', 1, 0, 'C');
	$pdf->Cell(150, 10, ($basic_info['work_address'] ?? '') . ' (우편번호: ' . ($basic_info['work_zipcode'] ?? '') . ')', 1, 1, 'L');

	$pdf->Cell(30, 20, '송달 장소', 1, 0, 'C');
	$pdf->Cell(150, 10, ''.' (우편번호: '.''.')', 1, 1, 'L');
	$pdf->Cell(30, 10, '', 0, 0);
	$pdf->Cell(150, 10, '송달영수인:', 1, 1);
	
	$pdf->Cell(40, 10, '전화번호(집ㆍ직장)', 1, 0, 'C');
	$pdf->Cell(60, 10, '', 1, 0, 'L');
	$pdf->Cell(40, 10, '전화번호(휴대전화)', 1, 0, 'C');
	$pdf->Cell(40, 10, $basic_info['customer_phone'] ?? '', 1, 1, 'L');
	
	// 대리인 정보
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(30, 10, '대리인', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(30, 10, '성 명', 1, 0, 'C');
	$pdf->MultiCell(150, 10, $basic_info['customer_name']."\n".$basic_info['customer_representative'], 1, 'L');
	
	$pdf->Cell(30, 10, '사무실 주소', 1, 0, 'C');
	$pdf->Cell(150, 10, $basic_info['customer_address'].'(우편번호: '.$basic_info['customer_zipcode'].')', 1, 1, 'L');
	
	$pdf->Cell(30, 10, '전화번호(사무실)', 1, 0, 'C');
	$pdf->Cell(150, 10, $basic_info['customer_phone'], 1, 1, 'L');
	$pdf->Cell(30, 10, '이메일 주소', 1, 0);
	$pdf->Cell(60, 10, $basic_info['customer_email'], 1, 0);
	$pdf->Cell(30, 10, 'FAX번호', 1, 0);
	$pdf->Cell(60, 10, $basic_info['customer_fax'], 1, 1);
	
	// 주채무자 정보
	$pdf->Ln(5);
	$pdf->MultiCell(0, 10, '주채무자가(또는 보증채무자가, 연대채무자가, 배우자가) 이미 귀 법원에 파산신청 또는 개인회생절차 개시신청을 하였으므로 그 사실을 아래와 같이 기재합니다.', 1, 'L');
	
	$pdf->Cell(30, 10, '성 명', 1, 0, 'C');
	$pdf->Cell(60, 10, '', 1, 0, 'L');
	$pdf->Cell(30, 10, '사건번호', 1, 0, 'C');
	$pdf->Cell(60, 10, '', 1, 1, 'L');
	
	// 신청 취지
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '신 청 취 지', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->MultiCell(0, 10, '「신청인에 대하여 개인회생절차를 개시한다.」라는 결정을 구합니다.', 0, 'L');
	
	// 신청 이유
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '신 청 이 유', 0, 1, 'C');
	$pdf->SetFont('cid0kr', '', 10);
	$incomeTypeText = "";
	if (isset($basic_info['is_company']) && $basic_info['is_company'] == 1) {
		// 영업소득자인 경우
		$incomeTypeText = "[   ] 신청인은 정기적이고 확실한 수입을 얻을 것으로 예상되고, 또한 채무자 회생 및 파산에 관한 법률 제595조에 해당하는 개시신청 기각사유는 없습니다(급여소득자).\n[ V] 신청인은 부동산임대소득.사업소득.농업소득.임업소득 그 밖에 이와 유사한 수입을 장래에 계속적으로 또는 반복하여 얻을 것으로 예상되고, 또한 채무자 회생 및 파산에 관한 법률 제595조에 해당하는 개시신청 기각사유는 없습니다(영업소득자).";
	} else {
		// 급여소득자인 경우
		$incomeTypeText = "[ V] 신청인은 정기적이고 확실한 수입을 얻을 것으로 예상되고, 또한 채무자 회생 및 파산에 관한 법률 제595조에 해당하는 개시신청 기각사유는 없습니다(급여소득자).\n[   ] 신청인은 부동산임대소득.사업소득.농업소득.임업소득 그 밖에 이와 유사한 수입을 장래에 계속적으로 또는 반복하여 얻을 것으로 예상되고, 또한 채무자 회생 및 파산에 관한 법률 제595조에 해당하는 개시신청 기각사유는 없습니다(영업소득자).";
	}

	$pdf->SetFont('cid0kr', '', 10);
	$pdf->MultiCell(0, 6, "1. 신청인은, 첨부한 개인회생채권자목록 기재와 같은 채무를 부담하고 있으나, 수입 및 재산이 별지 수입 및 지출에 관한 목록과 재산목록에 기재된 바와 같으므로, 파산의 원인사실이 발생 하였습니다(파산의 원인사실이 생길 염려가 있습니다).\n\n{$incomeTypeText}\n\n2. 신청인은, 각 회생채권자에 대한 채무 전액의 변제가 곤란하므로, 그 일부를 분할하여 지급할 계획입니다.\n 즉 현시점에서 계획하고 있는 총 변제예정액은 [ 13,627,872]원 이고, 제1회부터 제36회까지 월[378,552]원으로 예정하고 있으며, 이 변제의 준비 및 절차비용지급의 준비를 위하여, 개시결정이 내려지는 경우 을 제1회로 하여, 이후 매월 일에 개시결정시 통지되는 개인회생위원의 은행계좌에 동액의 금전을 입금하겠습니다.\n\n3. 이 사건 개인회생절차에서 적립금을 반환받을 신청인의 예금계좌는 이며, 신청인의 계좌가 변경되거나 어떤 사유로든 사용할 수 없게 된 경우에는 신청인은 사건담당 회생위원에게 즉시 변경된 예금계좌를 신청인의 통장사본을 첨부하여 신고하겠습니다.\n\n4. 개인회생채권자목록 부본(개인회생채권자목록상의 채권자수 + 2통)은 개시결정 전 회생위원의 지시에 따라 지정하는 일자까지 반드시 제출하겠습니다.", 0, 'L');
	
	// 첨부 서류
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 5, '첨 부 서 류', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(5, 8, '1.', 0, 0, 'L');
	$pdf->Cell(0, 8, '개인회생채권자목록 1통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '2.', 0, 0, 'L');
	$pdf->Cell(0, 8, '재산목록 1통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '3.', 0, 0, 'L');
	$pdf->Cell(0, 8, '수입 및 지출에 관한 목록 1 통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '4.', 0, 0, 'L');
	$pdf->Cell(0, 8, '진술서 1통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '5.', 0, 0, 'L');
	$pdf->Cell(0, 8, '신청서 부본 1통(위 1 내지 4의 첨부서류 및 소명방법을 모두 포함한 것)', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '6.', 0, 0, 'L');
	$pdf->Cell(0, 8, '수입인지 1통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '7.', 0, 0, 'L');
	$pdf->Cell(0, 8, '송달료납부서 1통', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '8.', 0, 0, 'L');
	$pdf->Cell(0, 8, '신청인 본인의 예금계좌 사본 1통(대리인의 예금계좌 사본 아님)', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '9.', 0, 0, 'L');
	$pdf->Cell(0, 8, '위임장 1통(대리인에 의하여 신청하는 경우)', 0, 1, 'L');

	$pdf->Ln(5);
	
	// 박스 형태로 휴대전화 정보수신 신청서
	$pdf->Rect(15, $pdf->GetY(), 180, 60); // 박스 그리기 (x, y, width, height)
	
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '휴대전화를 통한 정보수신 신청서', 0, 1, 'C');
	
	// 내용
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->SetXY(20, $pdf->GetY() + 5);
	$pdf->MultiCell(170, 6, '위 사건에 관한 개인회생절차 개시결정,폐지결정,면책결정, 월 변제액 3개월분 연체의 정보를 예납의무자가 납부한 송달료 잔액 범위 내에서 휴대전화를 통하여 알려주실 것을 신청합니다.', 0, 'L');
	$pdf->SetXY(20, $pdf->GetY());
	$pdf->Cell(0, 10, '휴대전화 번호:', 0, 1, 'L');
	$pdf->SetXY(20, $pdf->GetY());
	$pdf->Cell(170, 10, '신청인 채무자 ' . ($basic_info['name'] ?? '') . ' (날인 또는 서명)', 0, 1, 'C');
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->SetXY(20, $pdf->GetY());
	$pdf->MultiCell(170, 5, "※ 개인회생절차 개시결정,폐지결정,면책결정이 있거나, 변제계획 인가결정 후 월 변제액 3개월분 이상 연체시 위 휴대전화로 문자메시지가 발송됩니다.\n※ 문자메시지 서비스 이용금액은 메시지 1건당 17원씩 납부된 송달료에서 지급됩니다(송달료가 부족하면 문자메시지가 발송되지 않습니다). 추후 서비스 대상 정보, 이용금액 등이 변동될 수 있습니다.", 0, 'L');
	
	// Y 위치 업데이트
	$pdf->SetY($pdf->GetY() + 10);
	
	// 날짜와 서명
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '20   .   .   .', 0, 1, 'R');
	
	$pdf->Cell(0, 10, '신청인 ' . ($basic_info['name'] ?? ''), 0, 1, 'R');
	$pdf->Cell(0, 10, '위 대리인 '.$basic_info['customer_name'] ?? '', 0, 1, 'R');
	$pdf->Cell(0, 10, $basic_info['customer_representative'] ?? ''.' (인)', 0, 1, 'R');
	
	$pdf->Cell(0, 10, '서울회생법원 귀중', 0, 1, 'C');
}

function generatePowerOfAttorney($pdf, $basic_info) {
	// 위임장 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 20, '위 임 장', 1, 1, 'C');
	
	
	// 전체 테이블 설정
	$pdf->SetFont('cid0kr', '', 10);
	
	// 테이블 시작 - 전체를 테이블로 구성
	$tableWidth = 180; // 테이블 너비
	$leftColumnWidth = 30; // 좌측 컬럼 너비
	$rightColumnWidth = $tableWidth - $leftColumnWidth; // 우측 컬럼 너비
	
	// 1. 사건 정보
	$pdf->Cell($leftColumnWidth, 10, '사 건', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '개인회생', 1, 1, 'L');
	
	// 2. 당사자 정보
	$pdf->Cell($leftColumnWidth, 10, '당사자', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, $basic_info['name'] ?? '', 1, 1, 'L');
	
	// 3. 위임장 본문
	$pdf->Cell($tableWidth, 15, '위 사건에 관하여 ' . ($basic_info['name'] ?? '') . '(은)는 아래 수임인을 대리인으로 선임하고, 다음 표시 권한을 수여합니다.', 1, 1, 'C');
	
	// 4. 수임인 정보
	$pdf->Cell($leftColumnWidth, 40, '수 임 인', 1, 0, 'C');
	
	// 오른쪽 셀 내용 작성을 위한 위치 저장
	$startY = $pdf->GetY();
	$pdf->Cell($rightColumnWidth, 40, '', 1, 1, 'L'); // 빈 셀 생성
	
	// 오른쪽 셀 내부에 내용 작성
	$pdf->SetXY($pdf->GetX() + $leftColumnWidth, $startY);
	$pdf->Cell($rightColumnWidth, 10, $basic_info['customer_name'] ?? '', 0, 1, 'L');
	$pdf->SetX($pdf->GetX() + $leftColumnWidth);
	$pdf->Cell($rightColumnWidth, 10, $basic_info['customer_representative'] ?? '', 0, 1, 'L');
	$pdf->SetX($pdf->GetX() + $leftColumnWidth);
	$pdf->Cell($rightColumnWidth, 10, $basic_info['customer_address'] ?? '', 0, 1, 'L');
	$pdf->SetX($pdf->GetX() + $leftColumnWidth);
	$pdf->Cell($rightColumnWidth / 2, 10, '전화 : '.$basic_info['customer_phone'], 0, 0);
	$pdf->Cell($rightColumnWidth / 2, 10, '팩스 : '.$basic_info['customer_fax'], 0, 1);
	
	// 5. 수권사항
	$pdf->Cell($leftColumnWidth, 20, '수권사항', 1, 0, 'C');
	$pdf->MultiCell($rightColumnWidth, 20, '(「채무자 회생 및 파산에 관한 법률」에 따른 개인회생사건 신청의 대리. 다만, 각종 기일에서의 진술의 대리는 제외한다.)', 1, 'L');
	
	// 6. 날짜
	$pdf->Cell($leftColumnWidth, 10, '날짜', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '20   .   .   .', 1, 1, 'L');
	
	// 7. 위임인 정보
	$pdf->Cell($leftColumnWidth, 10, '위임인', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, $basic_info['name'] ?? ' (' . ($basic_info['resident_number'] ?? '') . ')', 1, 1, 'L');
	
	// 8. 위임인 주소
	$pdf->Cell($leftColumnWidth, 10, '주소', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, $basic_info['registered_address'] ?? '', 1, 1, 'L');

	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 30, '서울회생법원 귀중', 1, 1, 'C');
}
?>