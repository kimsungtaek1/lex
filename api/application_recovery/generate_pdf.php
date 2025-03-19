<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// 파일 시작 부분에 아래 코드 추가
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'utf-8');
session_start();
require_once '../../config.php';
require_once '../tcpdf/tcpdf.php'; // TCPDF 라이브러리 경로로 변경

// 권한 체크
if (!isset($_SESSION['employee_no'])) {
	die('권한이 없습니다.');
}

// POST 파라미터 확인
$case_no = isset($_POST['case_no']) ? intval($_POST['case_no']) : 0;
$print_items = isset($_POST['print_items']) ? $_POST['print_items'] : [];

if ($case_no <= 0 || empty($print_items)) {
	die('필수 정보가 누락되었습니다.');
}

// 기본 정보 조회
try {
	$stmt = $pdo->prepare("
		SELECT ar.*, cm.name, cm.case_number, cm.court_name  
		FROM application_recovery ar
		JOIN case_management cm ON ar.case_no = cm.case_no
		WHERE ar.case_no = ?
	");
	$stmt->execute([$case_no]);
	$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$basic_info) {
		die('해당 사건 정보를 찾을 수 없습니다.');
	}
} catch (Exception $e) {
	die('데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage());
}

// TCPDF 객체 생성 (P: 세로 방향, mm: 측정 단위, A4: 용지 크기)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// 문서 정보 설정
$pdf->SetCreator('개인회생 시스템');
$pdf->SetAuthor('개인회생 시스템');
$pdf->SetTitle($basic_info['name'] . ' - 개인회생 신청서 자료');
$pdf->SetSubject('개인회생 신청서 자료');
$pdf->SetKeywords('개인회생, 신청서, 자료');

// 기본 설정
$pdf->SetHeaderData('', 0, '', '', array(0, 0, 0), array(255, 255, 255));
$pdf->setHeaderFont(Array('helvetica', '', 9));
$pdf->setFooterFont(Array('helvetica', '', 8));
$pdf->SetDefaultMonospacedFont('helvetica');
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

// 한글 지원을 위해 유니코드 폰트 사용
$pdf->SetFont('cid0kr', '', 12); // 한글 지원 폰트

// 기본 정보 페이지 추가
$pdf->AddPage();
$pdf->SetFont('cid0kr', 'B', 16);
$pdf->Cell(0, 10, '개인회생 신청서 자료', 0, 1, 'C');

$pdf->SetFont('cid0kr', '', 12);
$pdf->Cell(0, 10, '신청인: ' . $basic_info['name'], 0, 1, 'C');
$pdf->Cell(0, 10, '사건번호: ' . $basic_info['case_number'], 0, 1, 'C');
$pdf->Cell(0, 10, '법원: ' . $basic_info['court_name'], 0, 1, 'C');
$pdf->Cell(0, 10, '출력일자: ' . date('Y년 m월 d일'), 0, 1, 'C');
$pdf->Ln(10);

// 선택한 항목에 따라 데이터 추가
foreach ($print_items as $item) {
	switch ($item) {
		case '채권자목록 열람':
			addCreditorList($pdf, $pdo, $case_no);
			break;
		case '재산목록 열람':
			addAssetList($pdf, $pdo, $case_no);
			break;
		case '수입지출목록 열람':
			addIncomeExpenditureList($pdf, $pdo, $case_no);
			break;
		case '진술서 열람':
			addStatementList($pdf, $pdo, $case_no);
			break;
	}
}

// 출력
$filename = $basic_info['name'] . '_개인회생신청서_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'I');
exit;

// 채권자 목록 출력 함수
function addCreditorList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '채권자 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_creditor 
			WHERE case_no = ? 
			ORDER BY creditor_count ASC
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($creditors)) {
			$pdf->SetFont('cid0kr', '', 12);
			$pdf->Cell(0, 10, '등록된 채권자가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 테이블 헤더
		$pdf->SetFont('cid0kr', 'B', 10);
		$pdf->Cell(10, 7, '번호', 1, 0, 'C');
		$pdf->Cell(50, 7, '금융기관명', 1, 0, 'C');
		$pdf->Cell(30, 7, '원금', 1, 0, 'C');
		$pdf->Cell(30, 7, '이자', 1, 0, 'C');
		$pdf->Cell(70, 7, '채권원인', 1, 1, 'C');
		
		// 테이블 데이터
		$pdf->SetFont('cid0kr', '', 10);
		foreach ($creditors as $creditor) {
			$pdf->Cell(10, 7, $creditor['creditor_count'], 1, 0, 'C');
			$pdf->Cell(50, 7, $creditor['financial_institution'], 1, 0, 'L');
			$pdf->Cell(30, 7, number_format($creditor['principal']), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($creditor['interest']), 1, 0, 'R');
			$pdf->Cell(70, 7, $creditor['claim_reason'], 1, 1, 'L');
		}
		
		// 합계 행
		$pdf->SetFont('cid0kr', 'B', 10);
		$stmt = $pdo->prepare("
			SELECT SUM(principal) as total_principal, 
			       SUM(interest) as total_interest
			FROM application_recovery_creditor 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$totals = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$pdf->Cell(10, 7, '', 1, 0, 'C');
		$pdf->Cell(50, 7, '합계', 1, 0, 'C');
		$pdf->Cell(30, 7, number_format($totals['total_principal']), 1, 0, 'R');
		$pdf->Cell(30, 7, number_format($totals['total_interest']), 1, 0, 'R');
		$pdf->Cell(70, 7, '', 1, 1, 'C');
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 재산 목록 출력 함수
function addAssetList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '재산 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	// 현금 및 예금
	$pdf->SetFont('cid0kr', 'B', 12);
	$pdf->Cell(0, 10, '1. 현금 및 예금', 0, 1, 'L');
	
	try {
		// 현금
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_cash 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$cash_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// 예금
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_deposits 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$deposit_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($cash_assets) && empty($deposit_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 현금 및 예금 정보가 없습니다.', 0, 1, 'L');
		} else {
			// 현금 출력
			if (!empty($cash_assets)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(0, 7, '현금', 0, 1, 'L');
				$pdf->Cell(100, 7, '내용', 1, 0, 'C');
				$pdf->Cell(30, 7, '가액', 1, 0, 'C');
				$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
				
				$pdf->SetFont('cid0kr', '', 10);
				foreach ($cash_assets as $asset) {
					$pdf->Cell(100, 7, $asset['property_detail'], 1, 0, 'L');
					$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
					$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
				}
				$pdf->Ln(5);
			}
			
			// 예금 출력
			if (!empty($deposit_assets)) {
				$pdf->SetFont('cid0kr', 'B', 10);
				$pdf->Cell(0, 7, '예금', 0, 1, 'L');
				$pdf->Cell(50, 7, '은행명', 1, 0, 'C');
				$pdf->Cell(60, 7, '계좌번호', 1, 0, 'C');
				$pdf->Cell(30, 7, '금액', 1, 0, 'C');
				$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
				
				$pdf->SetFont('cid0kr', '', 10);
				foreach ($deposit_assets as $asset) {
					$pdf->Cell(50, 7, $asset['bank_name'], 1, 0, 'L');
					$pdf->Cell(60, 7, $asset['account_number'], 1, 0, 'L');
					$pdf->Cell(30, 7, number_format($asset['deposit_amount']), 1, 0, 'R');
					$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
				}
			}
		}
		
		// 부동산
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '2. 부동산', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_real_estate 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$real_estate_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($real_estate_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 부동산 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(50, 7, '종류', 1, 0, 'C');
			$pdf->Cell(80, 7, '소재지', 1, 0, 'C');
			$pdf->Cell(30, 7, '평가액', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($real_estate_assets as $asset) {
				$pdf->Cell(50, 7, $asset['property_type'], 1, 0, 'L');
				$pdf->Cell(80, 7, $asset['property_location'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['property_expected_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['property_liquidation_value']), 1, 1, 'R');
			}
		}
		
		// 자동차
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '3. 자동차', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_asset_vehicles 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$vehicle_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($vehicle_assets)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 자동차 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(100, 7, '차량정보', 1, 0, 'C');
			$pdf->Cell(30, 7, '시가', 1, 0, 'C');
			$pdf->Cell(30, 7, '청산가치', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($vehicle_assets as $asset) {
				$pdf->Cell(100, 7, $asset['vehicle_info'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($asset['expected_value']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['liquidation_value']), 1, 0, 'R');
				$pdf->Cell(20, 7, $asset['is_seized'], 1, 1, 'C');
			}
		}
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 수입/지출 목록 출력 함수
function addIncomeExpenditureList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '수입/지출 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 급여 소득
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '1. 급여 소득', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_salary 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$salary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($salary_data)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 급여 소득 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(60, 7, '회사명', 1, 0, 'C');
			$pdf->Cell(40, 7, '직위', 1, 0, 'C');
			$pdf->Cell(40, 7, '근무기간', 1, 0, 'C');
			$pdf->Cell(30, 7, '월소득', 1, 0, 'C');
			$pdf->Cell(20, 7, '압류여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($salary_data as $salary) {
				$pdf->Cell(60, 7, $salary['company_name'], 1, 0, 'L');
				$pdf->Cell(40, 7, $salary['position'], 1, 0, 'L');
				$pdf->Cell(40, 7, $salary['work_period'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($salary['monthly_income']), 1, 0, 'R');
				$pdf->Cell(20, 7, $salary['is_seized'], 1, 1, 'C');
			}
		}
		
		// 사업 소득
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '2. 사업 소득', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_income_business 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$business_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($business_data)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 사업 소득 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(60, 7, '상호명', 1, 0, 'C');
			$pdf->Cell(60, 7, '업종', 1, 0, 'C');
			$pdf->Cell(40, 7, '경력', 1, 0, 'C');
			$pdf->Cell(30, 7, '월소득', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($business_data as $business) {
				$pdf->Cell(60, 7, $business['business_name'], 1, 0, 'L');
				$pdf->Cell(60, 7, $business['sector'], 1, 0, 'L');
				$pdf->Cell(40, 7, $business['career'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($business['monthly_income']), 1, 1, 'R');
			}
		}
		
		// 생계비 지출
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '3. 생계비 지출', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_living_expenses 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$expense_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($expense_data)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 생계비 지출 정보가 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(40, 7, '비목', 1, 0, 'C');
			$pdf->Cell(30, 7, '금액', 1, 0, 'C');
			$pdf->Cell(120, 7, '추가사유', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($expense_data as $expense) {
				$pdf->Cell(40, 7, $expense['type'], 1, 0, 'L');
				$pdf->Cell(30, 7, number_format($expense['amount']), 1, 0, 'R');
				$pdf->Cell(120, 7, $expense['reason'], 1, 1, 'L');
			}
		}
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 진술서 목록 출력 함수
function addStatementList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('cid0kr', 'B', 14);
	$pdf->Cell(0, 10, '진술서', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 경력 사항
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '1. 경력 사항', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_career 
			WHERE case_no = ?
			ORDER BY work_start_date DESC
		");
		$stmt->execute([$case_no]);
		$career_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($career_data)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 경력 사항이 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(30, 7, '구분', 1, 0, 'C');
			$pdf->Cell(50, 7, '회사/상호명', 1, 0, 'C');
			$pdf->Cell(30, 7, '직위', 1, 0, 'C');
			$pdf->Cell(40, 7, '시작일', 1, 0, 'C');
			$pdf->Cell(40, 7, '종료일', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($career_data as $career) {
				$pdf->Cell(30, 7, $career['company_type'], 1, 0, 'L');
				$pdf->Cell(50, 7, $career['company_name'], 1, 0, 'L');
				$pdf->Cell(30, 7, $career['position'], 1, 0, 'L');
				$pdf->Cell(40, 7, $career['work_start_date'], 1, 0, 'C');
				$pdf->Cell(40, 7, $career['work_end_date'] ?: '현재', 1, 1, 'C');
			}
		}
		
		// 학력 사항
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '2. 학력 사항', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_education 
			WHERE case_no = ?
			ORDER BY graduation_date DESC
		");
		$stmt->execute([$case_no]);
		$education_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($education_data)) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 학력 사항이 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', 'B', 10);
			$pdf->Cell(100, 7, '학교명', 1, 0, 'C');
			$pdf->Cell(50, 7, '졸업일', 1, 0, 'C');
			$pdf->Cell(40, 7, '졸업여부', 1, 1, 'C');
			
			$pdf->SetFont('cid0kr', '', 10);
			foreach ($education_data as $education) {
				$pdf->Cell(100, 7, $education['school_name'], 1, 0, 'L');
				$pdf->Cell(50, 7, $education['graduation_date'], 1, 0, 'C');
				$pdf->Cell(40, 7, $education['graduation_status'], 1, 1, 'C');
			}
		}
		
		// 혼인 사항
		$pdf->Ln(5);
		$pdf->SetFont('cid0kr', 'B', 12);
		$pdf->Cell(0, 10, '3. 혼인 사항', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_recovery_statement_marriage 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$marriage_data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$marriage_data) {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(0, 7, '등록된 혼인 사항이 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('cid0kr', '', 10);
			$pdf->Cell(40, 7, '혼인상태:', 0, 0, 'L');
			$pdf->Cell(150, 7, $marriage_data['marriage_status'], 0, 1, 'L');
			
			$pdf->Cell(40, 7, '일자:', 0, 0, 'L');
			$pdf->Cell(150, 7, $marriage_data['marriage_date'], 0, 1, 'L');
			
			if ($marriage_data['marriage_status'] == '결혼') {
				$pdf->Cell(40, 7, '배우자:', 0, 0, 'L');
				$pdf->Cell(150, 7, $marriage_data['spouse_name'], 0, 1, 'L');
			}
		}
		
	} catch (Exception $e) {
		$pdf->SetFont('cid0kr', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}
?>