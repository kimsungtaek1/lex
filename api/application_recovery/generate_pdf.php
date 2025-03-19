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

// 폰트 설정
$fontname = $pdf->addTTFfont('../tcpdf/fonts/NanumGothic.ttf', 'TrueTypeUnicode', '', 96);
$fontnameBold = $pdf->addTTFfont('../tcpdf/fonts/NanumGothic.ttf', 'TrueTypeUnicode', '', 96);

// 기본 정보 페이지 추가
$pdf->AddPage();
$pdf->SetFont($fontnameBold, '', 16);
$pdf->Cell(0, 10, '개인회생 신청서 자료', 0, 1, 'C');

$pdf->SetFont($fontname, '', 12);
$pdf->Cell(0, 10, '신청인: ' . $basic_info['name'], 0, 1, 'C');
$pdf->Cell(0, 10, '사건번호: ' . $basic_info['case_number'], 0, 1, 'C');
$pdf->Cell(0, 10, '법원: ' . $basic_info['court_name'], 0, 1, 'C');
$pdf->Cell(0, 10, '출력일자: ' . date('Y년 m월 d일'), 0, 1, 'C');
$pdf->Ln(10);

// 선택한 항목에 따라 데이터 추가
foreach ($print_items as $item) {
	switch ($item) {
		case '채권자목록 열람':
			addCreditorList($pdf, $pdo, $case_no, $fontname, $fontnameBold);
			break;
		case '재산목록 열람':
			addAssetList($pdf, $pdo, $case_no, $fontname, $fontnameBold);
			break;
		case '수입지출목록 열람':
			addIncomeExpenditureList($pdf, $pdo, $case_no, $fontname, $fontnameBold);
			break;
		case '진술서 열람':
			addStatementList($pdf, $pdo, $case_no, $fontname, $fontnameBold);
			break;
	}
}

// 출력
$filename = $basic_info['name'] . '_개인회생신청서_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'I');
exit;

// 채권자 목록 출력 함수
function addCreditorList($pdf, $pdo, $case_no, $fontname, $fontnameBold) {
	$pdf->AddPage();
	$pdf->SetFont($fontnameBold, '', 14);
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
			$pdf->SetFont($fontname, '', 12);
			$pdf->Cell(0, 10, '등록된 채권자가 없습니다.', 0, 1, 'C');
			return;
		}
		
		// 테이블 헤더
		$pdf->SetFont($fontnameBold, '', 10);
		$pdf->Cell(10, 7, '번호', 1, 0, 'C');
		$pdf->Cell(50, 7, '금융기관명', 1, 0, 'C');
		$pdf->Cell(30, 7, '원금', 1, 0, 'C');
		$pdf->Cell(30, 7, '이자', 1, 0, 'C');
		$pdf->Cell(70, 7, '채권원인', 1, 1, 'C');
		
		// 테이블 데이터
		$pdf->SetFont($fontname, '', 10);
		foreach ($creditors as $creditor) {
			$pdf->Cell(10, 7, $creditor['creditor_count'], 1, 0, 'C');
			$pdf->Cell(50, 7, $creditor['financial_institution'], 1, 0, 'L');
			$pdf->Cell(30, 7, number_format($creditor['principal']), 1, 0, 'R');
			$pdf->Cell(30, 7, number_format($creditor['interest']), 1, 0, 'R');
			$pdf->Cell(70, 7, $creditor['claim_reason'], 1, 1, 'L');
		}
		
		// 합계 행
		$pdf->SetFont($fontnameBold, '', 10);
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
		$pdf->SetFont($fontname, '', 12);
		$pdf->Cell(0, 10, '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage(), 0, 1, 'C');
	}
}
?>