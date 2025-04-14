<?php
if (!defined('INCLUDED_FROM_MAIN')) {
	die('이 파일은 직접 실행할 수 없습니다.');
}

function generatePdfApplication($pdf, $pdo, $case_no) {
	// 기본 정보 조회
	try {
		$stmt = $pdo->prepare("
			SELECT ab.*, cm.name, cm.case_number, cm.court_name, cf.*
			FROM application_bankruptcy ab
			JOIN case_management cm ON ab.case_no = cm.case_no
			JOIN config cf
			WHERE ab.case_no = ?
		");
		$stmt->execute([$case_no]);
		$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$basic_info) {
			$pdf->SetFont('cid0kr', '', 8);
			$pdf->Cell(0, 10, '사건 정보가 존재하지 않습니다.', 0, 1, 'C');
			return;
		}
		
		// 표지 생성
		generateCoverPage($pdf, $basic_info);
		
		// 신청서 생성
		generateApplicationForm($pdf, $pdo, $case_no, $basic_info);
		
		// 송달장소 및 송달영수인 신고서 생성
		generateDeliveryAddressForm($pdf, $basic_info);
		
		// 중지명령 신청서 (stay_order_apply가 1일 경우에만)
		if ($basic_info['stay_order_apply'] == 1) {
			generateStayOrderForm($pdf, $pdo, $case_no, $basic_info);
		}
		
		// 면제재산 결정신청서 (exemption_apply가 1일 경우에만)
		if ($basic_info['exemption_apply'] == 1) {
			generateExemptPropertyForm($pdf, $pdo, $case_no, $basic_info);
		}
		
		// 위임장 생성
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
	$pdf->Cell(0, 20, '파산 및 면책 신청서', 0, 1, 'C');
	$pdf->Ln(20);
	
	// 신청인 및 대리인 정보
	$pdf->SetFont('cid0kr', '', 12);
	$pdf->Cell(120, 10, '신청인', 0, 0, 'R');
	$pdf->Cell(5, 10, '', 0, 0);
	$pdf->Cell(120, 10, $basic_info['name'], 0, 1, 'L');
	
	$pdf->Ln(20);
	
	// 인지 및 송달료
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(20, 10, '인 지', 0, 0, 'L');
	$pdf->Cell(60, 10, '2,000원', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '파산송달료', 0, 0, 'L');
	$pdf->Cell(60, 10, '72,800원', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '면책송달료', 0, 0, 'L');
	$pdf->Cell(60, 10, '67,600원', 0, 1, 'L');
	
	// 사건 정보 표
	$pdf->Ln(80);
	$pdf->SetFont('cid0kr', '', 8);
	
	// 테이블 생성
	$pageWidth = $pdf->GetPageWidth() - 30; // 페이지 너비 (좌우 여백 제외)
	$tableWidth = $pageWidth * 0.7; // 테이블 너비는 페이지 너비의 70%
	$cellWidth = $tableWidth / 2; // 각 셀 너비
	$xStart = ($pageWidth - $tableWidth) / 2 + 15; // 테이블 시작 X좌표 (중앙 정렬)
	
	$pdf->SetX($xStart);
	$pdf->Cell($cellWidth, 10, '파산사건번호', 1, 0, 'C');
	$pdf->Cell($cellWidth, 10, '', 1, 1, 'C');
	
	$pdf->SetX($xStart);
	$pdf->Cell($cellWidth, 10, '면책사건번호', 1, 0, 'C');
	$pdf->Cell($cellWidth, 10, '', 1, 1, 'C');
	
	$pdf->SetX($xStart);
	$pdf->Cell($cellWidth, 10, '배당순위번호', 1, 0, 'C');
	$pdf->Cell($cellWidth, 10, '', 1, 1, 'C');
	
	$pdf->SetX($xStart);
	$pdf->Cell($cellWidth, 10, '재    판    부', 1, 0, 'C');
	$pdf->Cell($cellWidth, 10, '', 1, 1, 'C');
	
	// 하단 법원 정보
	$pdf->Ln(20);
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}

function generateApplicationForm($pdf, $pdo, $case_no, $basic_info) {
	// 신청서 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '파산 및 면책 신청서', 0, 1, 'C');
	
	// 신청인 정보 (채무자)
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(0, 10, '신 청 인(채 무 자) : '.$basic_info['name'].'                 주민등록번호: '.$basic_info['resident_number'], 0, 1, 'L');
		
	// 주소 정보
	$pdf->Cell(0, 4, '주 소 : '.$basic_info['registered_address'] . ' (우편번호: '.($basic_info['reg_zipcode'] ?? '').')', 0, 1, 'L');
	
	$pdf->Cell(0, 4, '거 소 : '.$basic_info['now_address'] . ' (우편번호: '.($basic_info['now_zipcode'] ?? '').')', 0, 1, 'L');
	
	$pdf->Cell(0, 4, '송달장소 : '.$basic_info['registered_address'] . ' (우편번호: '.($basic_info['reg_zipcode'] ?? '').')', 0, 1, 'L');
	
	$pdf->Cell(20, 4, '송달영수인 : ', 0, 0, 'L');
	
	// 연락처 정보
	$pdf->Cell(20, 4, '연락처 :'.'  휴대전화(' . $basic_info['phone'] . ')'.'  집전화(' . ($basic_info['work_phone'] ?? '') . ')'.'  e-mail(' . ($basic_info['email'] ?? '') . ')', 0, 0, 'L');
	
	// 신청 취지
	$pdf->Ln(5);
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '신 청 취 지', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->MultiCell(0, 6, '1. 신청인에 대하여 파산을 선고한다.', 0, 'L');
	$pdf->MultiCell(0, 6, '2. 채무자를 면책한다. 라는 결정을 구합니다.', 0, 'L');
	
	// 신청 이유
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '신 청 이 유', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->MultiCell(0, 6, "1. 신청인에게는 법환한 진술서 기재와 같이 지급하여야 할 채무가 존재합니다.\n2. 그런데 위 진술서 기재와 같은 신청인의 현재 자산, 수입의 상황 하에서는 채무를 지급할 수 없는 상태에 있습니다.\n3. 따라서 신청인에 대하여 파산을 선고하며, 채무자를 면책한다. 라는 결정을 구합니다.", 0, 'L');
	
	// 첨부 서류
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '첨 부 서 류', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(5, 8, '1.', 0, 0, 'L');
	$pdf->Cell(0, 8, '가족관계증명서(상세증명서), 혼인관계증명서(상세증명서) 각 1부', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '2.', 0, 0, 'L');
	$pdf->Cell(0, 8, '주민등록초본[주소변동내역(과거 주소 전체) 및 개명, 주민등록번호 변동사항 포함] 및 주민등록본 각 1부', 0, 1, 'L');
	$pdf->Cell(5, 8, '', 0, 0, 'L');
	$pdf->MultiCell(0, 6, "※ 가족관계증명서, 혼인관계증명서, 주민등록등본은 신청인 외 제3자의 주민등록번호 뒷자리가 표기되지 아니한 것을 제출\n     (신청인 본인의 주민등록번호는 전체 표기)", 0, 'L');
	
	$pdf->Cell(5, 8, '3.', 0, 0, 'L');
	$pdf->Cell(0, 8, '진술서(채권자목록, 재산목록, 현재의 생활 상황, 수입 및 지출에 관한 목록 포함) 1부', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '4.', 0, 0, 'L');
	$pdf->Cell(0, 8, '자료제출목록 1부', 0, 1, 'L');
	
	// 휴대전화 정보수신 신청서
	$pdf->Ln(5);
	$pdf->SetDrawColor(0, 0, 0);
	$pdf->Rect(15, $pdf->GetY(), 180, 40);
	
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->Cell(0, 10, '휴대전화를 통한 정보수신 신청서', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->SetX(20);
	$pdf->MultiCell(170, 6, '위 사건에 관한 파산선고결정, 면책결정 등 정보를 예납의무자가 납부한 송달료 잔액 범위 내에서 휴대전화를 통하여 알려주실 것을 신청합니다.', 0, 'L');
	
	$pdf->SetX(20);
	$pdf->Cell(0, 10, '휴대전화 번호 :', 0, 0, 'L');
	
	$pdf->Cell(0, 10, '신청인 채무자 ' . $basic_info['name'] . ' (날인 또는 서명)   ', 0, 1, 'R');
	
	$pdf->SetX(20);
	$pdf->MultiCell(170, 6, '파산선고 및 이의기간지정 결정(또는 면책심문기일 결정), 면책결정이 있으면 신속하게 위 휴대전화로 문자메시지가 발송됩니다. 문자메시지 서비스 이용금액은 메시지 1건당 17원씩 납부된 송달료에서 지급됩니다(송달료가 부족하면 문자메시지가 발송되지 않습니다). 추후 서비스 대상 정보, 이용금액 등이 변동될 수 있습니다.', 0, 'L');
	
	
	
	// 법원 타기관을 통한 개인파산
	$pdf->Ln(5);
	$pdf->SetDrawColor(0, 0, 0);
	$pdf->Rect(15, $pdf->GetY(), 180, 40);
	
	$pdf->SetFont('cid0kr', 'B', 10);
	$pdf->MultiCell(0, 6, "법원의 타기관을 통한 개인파산 신청에 대한 지원 여부(해당사항 있을시 기재)", 0, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(5, 8, '1.', 0, 0, 'L');
	$pdf->Cell(0, 8, '지원기관 (1.신용회복위원회 2.          ) (예)신용회복위원회, 서울시복지재단, 법률구조공단 등', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '2.', 0, 0, 'L');
	$pdf->Cell(0, 8, '지원내역과 지원금액(1.)신청서작성지원 2.          )', 0, 1, 'L');
	
	$pdf->SetX(20);
	$pdf->Cell(0, 8, '(예)신청서 작성 지원, 변호사 수임료 지원, 송달료 지원, 파산관재인 보수 지원 등', 0, 1, 'L');
	$pdf->Cell(0, 8, '서울시복지재단 - 파산관재인 보수 지원(30만원)', 0, 1, 'L');
	
	// 날짜와 서명
	$pdf->Ln(10);
	$pdf->Cell(0, 10, date('Y. m. d') . ' .', 0, 1, 'R');
	
	$pdf->Cell(0, 10, '신 청 인    ' . $basic_info['name'] . '    (인)', 0, 1, 'R');
	
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}

function generateDeliveryAddressForm($pdf, $basic_info) {
	// 송달장소 및 송달영수인 신고서 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '송달장소 및 송달영수인 신고서', 0, 1, 'C');
	$pdf->Ln(20);
	
	// 사건 정보
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(20, 10, '사    건', 0, 0, 'L');
	$pdf->Cell(80, 10, date('Y').'하단', 0, 0, 'L');
	$pdf->Cell(20, 10, '파산', 0, 1, 'L');
	
	$pdf->Cell(100, 10, '', 0, 0);
	$pdf->Cell(80, 10, date('Y').'하면', 0, 0, 'L');
	$pdf->Cell(20, 10, '면책', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '신 청 인', 0, 0, 'L');
	$pdf->Cell(0, 10, $basic_info['name'], 0, 1, 'L');
	$pdf->Ln(20);
	
	// 본문
	$pdf->MultiCell(0, 10, '위 사건에 관하여 신청인은 민사소송법 제184조에 따라 다음과 같이 송달장소 및 송달영수인을 신고합니다.', 0, 'L');
	$pdf->Ln(20);
	
	// 다음
	$pdf->Cell(0, 10, '다    음', 0, 1, 'C');
	$pdf->Ln(10);
	
	// 송달 정보
	$pdf->Cell(0, 10, '신청인의 송달장소 및 송달영수인', 0, 1, 'L');
	$pdf->Ln(5);
	
	$pdf->Cell(20, 10, '송 달 장 소', 0, 0, 'L');
	$pdf->Cell(0, 10, $basic_info['registered_address'].' '.$basic_info['now_address'] . ' 4층 (대치동)', 0, 1, 'L');
	$pdf->Ln(5);
	
	$pdf->Cell(20, 10, '송 달 영 수 인', 0, 0, 'L');
	$pdf->Cell(0, 10, '', 0, 1, 'L');
	$pdf->Ln(30);
	
	// 날짜와 서명
	$pdf->Cell(0, 10, date('Y.m.d'), 0, 1, 'C');
	$pdf->Ln(20);
	
	$pdf->Cell(0, 10, '위 신청인    ' . $basic_info['name'] . ' (서명 또는 날인)', 0, 1, 'C');
	$pdf->Ln(20);
	
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}

function generateStayOrderForm($pdf, $pdo, $case_no, $basic_info) {
	// 중지명령 신청서 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '중지명령 신청서', 0, 1, 'C');
	$pdf->Ln(10);
	
	// 사건 정보
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(20, 10, '사    건', 0, 0, 'L');
	$pdf->Cell(0, 10, date('Y').' 하단 12312호 파산선고', 0, 1, 'L');
	
	$pdf->Cell(40, 10, '신청인(채무자) ', 0, 0, 'L');
	$pdf->Cell(0, 10, $basic_info['name'].'(주민등록번호: 700000-1000000)', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '주소 : ', 0, 0, 'L');
	$pdf->Cell(0, 10, $basic_info['registered_address'] . ' (상상동, 상상동센트럴아이파크)', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '채 권 자', 0, 0, 'L');
	$pdf->Cell(0, 10, '', 0, 1, 'L');
	
	$pdf->Cell(20, 10, '주소 :', 0, 0, 'L');
	$pdf->Cell(0, 10, '', 0, 1, 'L');
	$pdf->Ln(10);
	
	// 신청 취지
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '신 청 취 지', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->MultiCell(0, 6, "신청인(채무자)에 대한 파산선고가 있을 때까지 채무자 소유의 법지목록 기재 재산에 대하여 (지방법원) 집행관이 한 (20   년    호) 유체동산강제집행 절차를 중지한다.\n라는 결정을 구합니다.", 0, 'L');
	$pdf->Ln(10);
	
	// 신청 원인
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '신 청 원 인', 0, 1, 'C');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->MultiCell(0, 6, "1. 신청인(채무자)은 귀원 (20   하단    호) 파산사건의 신청채무자입니다.\n\n2. 신청인은 파산선고 신청시 신고한 위 채권자의 강제집행신청에 의하여 신청인 소유의 별지목록 기재 유체동산이 (   지방법원) 집행관실 (20   년    호)에 의하여 압류되었습니다. 이건 유체동산은 신청인 및 피부양자들이 기본적인 생활을 유지하기 위한 최소한의 재산인데 이에 대하여 강제집행이 계속된다면 신청인 및 피부양자의 생계유지에 막대한 어려움이 초래될 것이므로 이를 신청인 및 피부양자의 6개월간 생계비에 사용할 특정재산으로서 파산재단에서 면제하는 면제재산결정신청을 하였습니다.\n\n3. 따라서 신청인에 대한 파산선고가 있기까지 채무자 회생 및 파산에 관한 법률 제383조 8항에 의하여 위 강제집행의 집행중지의 결정을 구하기 위하여 이 신청에 이른 것입니다", 0, 'L');
	$pdf->Ln(10);
	
	// 첨부 서류
	$pdf->SetFont('cid0kr', '', 10);
	$pdf->Cell(0, 10, '첨 부 서 류', 0, 1, 'L');
	
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(5, 8, '1.', 0, 0, 'L');
	$pdf->Cell(0, 8, '유체동산압류조서등본(집행관) 1 부', 0, 1, 'L');
	
	$pdf->Cell(5, 8, '1.', 0, 0, 'L');
	$pdf->Cell(0, 8, '위임장 1 부', 0, 1, 'L');
	
	// 날짜와 서명
	$pdf->Ln(10);
	$pdf->Cell(0, 10, date('Y. m. d') . ' .', 0, 1, 'R');
	
	$pdf->Cell(0, 10, '신청인(채무자)    ' . $basic_info['name'] . ' (서명 또는 날인)', 0, 1, 'R');
	
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}

function generateExemptPropertyForm($pdf, $pdo, $case_no, $basic_info) {
	// 면제재산 결정신청서 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 10, '면제재산 결정신청서', 0, 1, 'C');
	$pdf->Ln(10);
	
	// 사건 정보
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(20, 10, '사    건', 0, 0, 'L');
	$pdf->Cell(0, 10, date('Y').' 하단        파산선고', 0, 1, 'L');
	
	$pdf->Cell(30, 10, '신청인(채무자)', 0, 0, 'L');
	$pdf->Cell(0, 10, $basic_info['name'], 0, 1, 'L');
	$pdf->Ln(10);
	
	// 신청 내용
	$pdf->MultiCell(0, 6, "신청인은 채무자 회생 및 파산에 관한 법률 제383조 제2항에 따라 아래의 재산을 면제재산으로 정한다는 결정을 구합니다.", 0, 'L');
	$pdf->Ln(5);
	
	// 면제재산 체크박스
	$pdf->SetFont('cid0kr', '', 8);
	$pdf->Cell(5, 10, '□', 0, 0, 'L');
	$pdf->Cell(0, 10, '1.주거용 건물 임차보증금반환청구권에 대한 면제재산결정 신청(법 제383조 제2항 제1호)', 0, 1, 'L');
	
	// 면제재산 테이블
	$pdf->Cell(30, 10, '면제재산 금액', 1, 0, 'C');
	$pdf->Cell(0, 10, '금                원', 1, 1, 'C');
	
	// 면제재산 내용 테이블 1
	$pdf->Cell(40, 10, '①임대차계약일자', 1, 0, 'C');
	$pdf->Cell(0, 10, '(                부터                까지)', 1, 1, 'C');
	
	// 면제재산 내용 테이블 2
	$pdf->Cell(40, 10, '②임차목적물 소재지(', 1, 0, 'C');
	$pdf->Cell(0, 10, '', 1, 1, 'C');
	
	// 면제재산 내용 테이블 3
	$pdf->Cell(40, 10, '③임차보증금의 액수 및 연체기간(', 1, 0, 'C');
	$pdf->Cell(0, 10, '원,                개월간 연체)', 1, 1, 'C');
	
	// 면제재산 내용 테이블 4
	$pdf->Cell(40, 10, '④확정일자(', 1, 0, 'C');
	$pdf->Cell(0, 10, '', 1, 1, 'C');
	
	// 면제재산 내용 테이블 5
	$pdf->Cell(40, 10, '⑤학정일자보유여부', 1, 0, 'C');
	$pdf->Cell(0, 10, '확정일자 있음 □   확정일자 없음 □', 1, 1, 'C');
	
	// 면제재산 내용 테이블 6
	$pdf->Cell(40, 10, '⑥최우선변제금 액수(', 1, 0, 'C');
	$pdf->Cell(0, 10, '원', 1, 1, 'C');
	
	// 면제재산 내용 테이블 7
	$pdf->Cell(40, 10, '⑦소명자료', 1, 0, 'C');
	$pdf->Cell(0, 10, '임대차계약서 □   주민등록등본 □   기타증빙 □ [               ]', 1, 1, 'C');
	
	// 면제재산 2번째 체크박스
	$pdf->Ln(5);
	$pdf->Cell(5, 10, '□', 0, 0, 'L');
	$pdf->Cell(0, 10, '2.6개월간의 생계비에 사용할 특정재산에 대한 면제재산결정 신청(법 제383조 제2항 제2호)', 0, 1, 'L');
	
	// 면제재산 생계비 테이블
	$pdf->Cell(30, 10, '순번', 1, 0, 'C');
	$pdf->Cell(60, 10, '특정재산의 내용\n(구체적으로 기재)', 1, 0, 'C');
	$pdf->Cell(40, 10, '소재지', 1, 0, 'C');
	$pdf->Cell(30, 10, '추정시가', 1, 0, 'C');
	$pdf->Cell(0, 10, '면제재산결정의 사유', 1, 1, 'C');
	
	// 빈 행 (필요한 만큼 추가)
	for ($i = 0; $i < 5; $i++) {
		$pdf->Cell(30, 10, '', 1, 0, 'C');
		$pdf->Cell(60, 10, '', 1, 0, 'C');
		$pdf->Cell(40, 10, '', 1, 0, 'C');
		$pdf->Cell(30, 10, '', 1, 0, 'C');
		$pdf->Cell(0, 10, '', 1, 1, 'C');
	}
	
	// 소명자료
	$pdf->Cell(30, 10, '※ 소명자료 :', 0, 0, 'L');
	$pdf->Cell(0, 10, '□ (         )보증서 1통/ □ 사진 1장/ □ 기타 [                ]', 0, 1, 'L');
	
	// 날짜와 서명
	$pdf->Ln(10);
	$pdf->Cell(0, 10, date('Y. m. d') . '.', 0, 1, 'R');
	
	$pdf->Cell(0, 10, '신청인(채무자)    ' . $basic_info['name'] . ' (인)', 0, 1, 'R');
	
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}

function generatePowerOfAttorney($pdf, $basic_info) {
	// 위임장 페이지 추가
	$pdf->AddPage();
	
	// 제목
	$pdf->SetFont('cid0kr', 'B', 16);
	$pdf->Cell(0, 20, '위 임 장', 0, 1, 'C');
	
	// 전체 테이블 설정
	$pdf->SetFont('cid0kr', '', 8);
	
	// 테이블 시작 - 전체를 테이블로 구성
	$tableWidth = 180; // 테이블 너비
	$leftColumnWidth = 30; // 좌측 컬럼 너비
	$rightColumnWidth = $tableWidth - $leftColumnWidth; // 우측 컬럼 너비
	
	// 1. 사건 정보
	$pdf->Cell($leftColumnWidth, 10, '사 건', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, '파산 및 면책', 1, 1, 'L');
	
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
	$pdf->Cell($rightColumnWidth / 2, 10, '전화 : ' . ($basic_info['customer_phone'] ?? ''), 0, 0);
	$pdf->Cell($rightColumnWidth / 2, 10, '팩스 : ' . ($basic_info['customer_fax'] ?? ''), 0, 1);
	
	// 5. 수권사항
	$pdf->Cell($leftColumnWidth, 20, '수권사항', 1, 0, 'C');
	$pdf->MultiCell($rightColumnWidth, 20, '(「채무자 회생 및 파산에 관한 법률」에 따른 파산 및 면책신청의 대리. 다만, 각종 기일에서의 진술의 대리는 제외한다.)', 1, 'L');
	
	// 6. 날짜
	$pdf->Cell($leftColumnWidth, 10, '날짜', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, date('Y년 m월 d일'), 1, 1, 'L');
	
	// 7. 위임인 정보
	$pdf->Cell($leftColumnWidth, 10, '위임인', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, ($basic_info['name'] ?? '') . ' (' . ($basic_info['resident_number'] ?? '') . ')', 1, 1, 'L');
	
	// 8. 위임인 주소
	$pdf->Cell($leftColumnWidth, 10, '주소', 1, 0, 'C');
	$pdf->Cell($rightColumnWidth, 10, $basic_info['registered_address'] ?? '', 1, 1, 'L');

	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, $basic_info['court_name'] . ' 귀중', 0, 1, 'C');
}
?>