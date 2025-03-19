<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once '../../config.php';
require_once '../tfpdf/tfpdf.php';

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
		SELECT ab.*, cm.name, cm.case_number, cm.court_name  
		FROM application_bankruptcy ab
		JOIN case_management cm ON ab.case_no = cm.case_no
		WHERE ab.case_no = ?
	");
	$stmt->execute([$case_no]);
	$basic_info = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$basic_info) {
		die('해당 사건 정보를 찾을 수 없습니다.');
	}
} catch (Exception $e) {
	die('데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage());
}

// PDF 생성
$pdf = new tFPDF();
$pdf->AddFont('NanumGothic', '', 'NanumGothic.ttf', true);
$pdf->AddFont('NanumGothicBold', '', 'NanumGothicBold.ttf', true);
$pdf->SetTitle($basic_info['name'] . ' - 개인파산 신청서 자료');

// 기본 정보 페이지 추가
$pdf->AddPage();
$pdf->SetFont('NanumGothicBold', '', 16);
$pdf->Cell(0, 10, '개인파산 신청서 자료', 0, 1, 'C');
$pdf->SetFont('NanumGothic', '', 12);
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
$pdf->Output('I', $basic_info['name'] . '_개인파산신청서_' . date('Ymd') . '.pdf');
exit;

// 채권자 목록 출력 함수
function addCreditorList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('NanumGothicBold', '', 14);
	$pdf->Cell(0, 10, '채권자 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_creditor 
			WHERE case_no = ? 
			ORDER BY creditor_count ASC
		");
		$stmt->execute([$case_no]);
		$creditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($creditors)) {
			$pdf->SetFont('NanumGothic', '', 12);
			$pdf->Cell(0, 10, '등록된 채권자가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 테이블 헤더
		$pdf->SetFont('NanumGothicBold', '', 10);
		$pdf->Cell(10, 7, '번호', 1, 0, 'C');
		$pdf->Cell(50, 7, '금융기관명', 1, 0, 'C');
		$pdf->Cell(30, 7, '최초채권액', 1, 0, 'C');
		$pdf->Cell(30, 7, '잔존원금', 1, 0, 'C');
		$pdf->Cell(30, 7, '잔존이자', 1, 0, 'C');
		$pdf->Cell(40, 7, '발생원인', 1, 1, 'C');
		
		// 테이블 데이터
		$pdf->SetFont('NanumGothic', '', 10);
		foreach ($creditors as $creditor) {
			$pdf->Cell(10, 7, $creditor['creditor_count'], 1, 0, 'C');
			$pdf->Cell(50, 7, $creditor['financial_institution'], 1, 0, 'L');
			$pdf->Cell(30, 7, number_format($creditor['initial_claim']), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($creditor['remaining_principal']), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($creditor['remaining_interest']), 1, 0, 'R');
			$pdf->Cell(40, 7, $creditor['separate_bond'], 1, 1, 'L');
		}
		
		// 합계 행
		$pdf->SetFont('NanumGothicBold', '', 10);
		$stmt = $pdo->prepare("
			SELECT SUM(initial_claim) as total_initial,
				   SUM(remaining_principal) as total_principal,
				   SUM(remaining_interest) as total_interest
			FROM application_bankruptcy_creditor 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$totals = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$pdf->Cell(10, 7, '', 1, 0, 'C');
		$pdf->Cell(50, 7, '합계', 1, 0, 'C');
		$pdf->Cell(30, 7, number_format($totals['total_initial']), 1, 0, 'R');
		$pdf->Cell(30, 7, number_format($totals['total_principal']), 1, 0, 'R');
		$pdf->Cell(30, 7, number_format($totals['total_interest']), 1, 0, 'R');
		$pdf->Cell(40, 7, '', 1, 1, 'C');
		
	} catch (Exception $e) {
		$pdf->SetFont('NanumGothic', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 재산 목록 출력 함수
function addAssetList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('NanumGothicBold', '', 14);
	$pdf->Cell(0, 10, '재산 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 재산 전체 정보 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_assets 
			WHERE case_no = ?
			ORDER BY asset_type
		");
		$stmt->execute([$case_no]);
		$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($assets)) {
			$pdf->SetFont('NanumGothic', '', 12);
			$pdf->Cell(0, 10, '등록된 재산이 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 재산 유형별 분류
		$asset_types = [];
		foreach ($assets as $asset) {
			if (!isset($asset_types[$asset['asset_type']])) {
				$asset_types[$asset['asset_type']] = [];
			}
			$asset_types[$asset['asset_type']][] = $asset;
		}
		
		// 재산 유형별 출력
		$count = 1;
		foreach ($asset_types as $type => $type_assets) {
			$pdf->SetFont('NanumGothicBold', '', 12);
			$pdf->Cell(0, 10, $count . '. ' . $type, 0, 1, 'L');
			$count++;
			
			$pdf->SetFont('NanumGothicBold', '', 10);
			$pdf->Cell(100, 7, '재산 설명', 1, 0, 'C');
			$pdf->Cell(30, 7, '가치', 1, 0, 'C');
			$pdf->Cell(30, 7, '담보금액', 1, 0, 'C');
			$pdf->Cell(30, 7, '순가치', 1, 1, 'C');
			
			$pdf->SetFont('NanumGothic', '', 10);
			foreach ($type_assets as $asset) {
				// 셀 내용이 너무 길면 나눠서 출력
				if (mb_strlen($asset['asset_description'], 'UTF-8') > 50) {
					$pdf->Cell(100, 7, mb_substr($asset['asset_description'], 0, 50, 'UTF-8') . '...', 1, 0, 'L');
				} else {
					$pdf->Cell(100, 7, $asset['asset_description'], 1, 0, 'L');
				}
				$pdf->Cell(30, 7, number_format($asset['value']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['secured_amount']), 1, 0, 'R');
				$pdf->Cell(30, 7, number_format($asset['net_value']), 1, 1, 'R');
			}
			
			// 유형별 소계
			$type_value = array_sum(array_column($type_assets, 'value'));
			$type_secured = array_sum(array_column($type_assets, 'secured_amount'));
			$type_net = array_sum(array_column($type_assets, 'net_value'));
			
			$pdf->SetFont('NanumGothicBold', '', 10);
			$pdf->Cell(100, 7, '소계', 1, 0, 'C');
			$pdf->Cell(30, 7, number_format($type_value), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($type_secured), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($type_net), 1, 1, 'R');
			
			$pdf->Ln(5);
		}
		
		// 총계
		$pdf->SetFont('NanumGothicBold', '', 12);
		$pdf->Cell(0, 10, '재산 총계', 0, 1, 'L');
		
		$total_value = array_sum(array_column($assets, 'value'));
		$total_secured = array_sum(array_column($assets, 'secured_amount'));
		$total_net = array_sum(array_column($assets, 'net_value'));
		
		$pdf->SetFont('NanumGothicBold', '', 10);
		$pdf->Cell(100, 7, '총계', 1, 0, 'C');
		$pdf->Cell(30, 7, number_format($total_value), 1, 0, 'R');
		$pdf->Cell(30, 7, number_format($total_secured), 1, 0, 'R');
		$pdf->Cell(30, 7, number_format($total_net), 1, 1, 'R');
		
	} catch (Exception $e) {
		$pdf->SetFont('NanumGothic', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 수입/지출 목록 출력 함수
function addIncomeExpenditureList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('NanumGothicBold', '', 14);
	$pdf->Cell(0, 10, '수입/지출 목록', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_income_expenditure 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$income_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($income_data)) {
			$pdf->SetFont('NanumGothic', '', 12);
			$pdf->Cell(0, 10, '등록된 수입/지출 정보가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 소득 유형별 출력
		foreach ($income_data as $income) {
			$pdf->SetFont('NanumGothicBold', '', 12);
			$pdf->Cell(0, 10, $income['income_type'] . ' 소득', 0, 1, 'L');
			
			$pdf->SetFont('NanumGothic', '', 10);
			$pdf->Cell(40, 7, '월 소득:', 0, 0, 'L');
			$pdf->Cell(150, 7, number_format($income['income_monthly']) . '원', 0, 1, 'L');
			
			$pdf->Cell(40, 7, '월 지출:', 0, 0, 'L');
			$pdf->Cell(150, 7, number_format($income['expense_monthly']) . '원', 0, 1, 'L');
			
			// 지출 상세 내역이 있는 경우
			if (!empty($income['expense_details'])) {
				$pdf->SetFont('NanumGothicBold', '', 10);
				$pdf->Cell(0, 7, '지출 상세내역', 0, 1, 'L');
				
				$pdf->SetFont('NanumGothic', '', 10);
				$details = explode("\n", $income['expense_details']);
				foreach ($details as $detail) {
					$pdf->Cell(0, 7, $detail, 0, 1, 'L');
				}
			}
			
			$pdf->Ln(5);
		}
		
		// 종합 통계
		$pdf->SetFont('NanumGothicBold', '', 12);
		$pdf->Cell(0, 10, '소득/지출 종합', 0, 1, 'L');
		
		$total_income = array_sum(array_column($income_data, 'income_monthly'));
		$total_expense = array_sum(array_column($income_data, 'expense_monthly'));
		$balance = $total_income - $total_expense;
		
		$pdf->SetFont('NanumGothic', '', 10);
		$pdf->Cell(40, 7, '총 월 소득:', 0, 0, 'L');
		$pdf->Cell(150, 7, number_format($total_income) . '원', 0, 1, 'L');
		
		$pdf->Cell(40, 7, '총 월 지출:', 0, 0, 'L');
		$pdf->Cell(150, 7, number_format($total_expense) . '원', 0, 1, 'L');
		
		$pdf->Cell(40, 7, '월 수지:', 0, 0, 'L');
		$pdf->Cell(150, 7, number_format($balance) . '원', 0, 1, 'L');
		
	} catch (Exception $e) {
		$pdf->SetFont('NanumGothic', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}

// 진술서 목록 출력 함수
function addStatementList($pdf, $pdo, $case_no) {
	$pdf->AddPage();
	$pdf->SetFont('NanumGothicBold', '', 14);
	$pdf->Cell(0, 10, '진술서', 0, 1, 'C');
	$pdf->Ln(5);
	
	try {
		// 진술서 목록 조회
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_statement 
			WHERE case_no = ?
		");
		$stmt->execute([$case_no]);
		$statements = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($statements)) {
			$pdf->SetFont('NanumGothic', '', 12);
			$pdf->Cell(0, 10, '등록된 진술서가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 진술서 유형별 출력
		foreach ($statements as $stmt_item) {
			$pdf->SetFont('NanumGothicBold', '', 12);
			$pdf->Cell(0, 10, $stmt_item['statement_type'], 0, 1, 'L');
			
			$pdf->SetFont('NanumGothic', '', 10);
			// 내용이 길 경우 여러 줄로 나누어 출력
			$content_lines = explode("\n", $stmt_item['content']);
			foreach ($content_lines as $line) {
				$pdf->MultiCell(0, 7, $line, 0, 'L');
			}
			
			$pdf->Ln(5);
		}
		
		// 학력 사항
		$pdf->SetFont('NanumGothicBold', '', 12);
		$pdf->Cell(0, 10, '학력 사항', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_statement_education 
			WHERE case_no = ?
			ORDER BY graduation_date DESC
		");
		$stmt->execute([$case_no]);
		$education_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($education_data)) {
			$pdf->SetFont('NanumGothic', '', 10);
			$pdf->Cell(0, 7, '등록된 학력 사항이 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('NanumGothicBold', '', 10);
			$pdf->Cell(100, 7, '학교명', 1, 0, 'C');
			$pdf->Cell(50, 7, '졸업시기', 1, 0, 'C');
			$pdf->Cell(40, 7, '졸업여부', 1, 1, 'C');
			
			$pdf->SetFont('NanumGothic', '', 10);
			foreach ($education_data as $education) {
				$pdf->Cell(100, 7, $education['school_name'], 1, 0, 'L');
				$pdf->Cell(50, 7, $education['graduation_date'], 1, 0, 'C');
				$pdf->Cell(40, 7, $education['graduation_status'], 1, 1, 'C');
			}
		}
		
		// 경력 사항
		$pdf->Ln(5);
		$pdf->SetFont('NanumGothicBold', '', 12);
		$pdf->Cell(0, 10, '경력 사항', 0, 1, 'L');
		
		$stmt = $pdo->prepare("
			SELECT * FROM application_bankruptcy_statement_career 
			WHERE case_no = ?
			ORDER BY work_start_date DESC
		");
		$stmt->execute([$case_no]);
		$career_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($career_data)) {
			$pdf->SetFont('NanumGothic', '', 10);
			$pdf->Cell(0, 7, '등록된 경력 사항이 없습니다.', 0, 1, 'L');
		} else {
			$pdf->SetFont('NanumGothicBold', '', 10);
			$pdf->Cell(30, 7, '구분', 1, 0, 'C');
			$pdf->Cell(50, 7, '회사/상호명', 1, 0, 'C');
			$pdf->Cell(30, 7, '직위', 1, 0, 'C');
			$pdf->Cell(40, 7, '시작일', 1, 0, 'C');
			$pdf->Cell(40, 7, '종료일', 1, 1, 'C');
			
			$pdf->SetFont('NanumGothic', '', 10);
			foreach ($career_data as $career) {
				$pdf->Cell(30, 7, $career['company_type'], 1, 0, 'L');
				$pdf->Cell(50, 7, $career['company_name'], 1, 0, 'L');
				$pdf->Cell(30, 7, $career['position'], 1, 0, 'L');
				$pdf->Cell(40, 7, $career['work_start_date'], 1, 0, 'C');
				$pdf->Cell(40, 7, $career['work_end_date'] ?: '현재', 1, 1, 'C');
			}
		}
		
	} catch (Exception $e) {
		$pdf->SetFont('NanumGothic', '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}
?>