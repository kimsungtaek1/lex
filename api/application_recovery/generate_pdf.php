<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// 파일 시작 부분에 아래 코드 추가
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'utf-8');
session_start();
require_once '../../config.php';
require_once '../tcpdf/tcpdf.php'; // TCPDF 라이브러리 경로로 변경

// 외부 파일 포함 전에 이 파일이 메인 파일임을 표시
define('INCLUDED_FROM_MAIN', true);

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

// '모두 선택' 항목이 있는지 확인하고, 있으면 모든 항목을 포함
if (in_array('모두 선택', $print_items)) {
	$print_items = ['채권자목록 열람', '재산목록 열람', '수입지출목록 열람', '진술서 열람', '개인회생신청서'];
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
$pdf->setHeaderFont(Array('helvetica', '', 9)); // 헤더 폰트 변경
$pdf->setFooterFont(Array('helvetica', '', 8)); // 푸터 폰트 변경
$pdf->SetDefaultMonospacedFont('helvetica'); // 기본 고정폭 폰트 변경
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

// 한글 지원을 위해 유니코드 폰트 사용
$pdf->SetFont('cid0kr', '', 12); // 한글 지원 폰트

// 선택한 항목에 따라 데이터 추가
foreach ($print_items as $item) {
	switch ($item) {
		case '채권자목록 열람':
			// 외부 파일 포함
			require_once 'generate_pdf_creditors.php';
			generatePdfCreditors($pdf, $pdo, $case_no);
			break;
		case '재산목록 열람':
			// 외부 파일 포함
			require_once 'generate_pdf_assets.php';
			generatePdfAssets($pdf, $pdo, $case_no);
			break;
		case '수입지출목록 열람':
			// 외부 파일 포함
			require_once 'generate_pdf_income.php';
			generatePdfIncome($pdf, $pdo, $case_no);
			break;
		case '진술서 열람':
			// 외부 파일 포함
			require_once 'generate_pdf_statements.php';
			generatePdfStatements($pdf, $pdo, $case_no);
			break;
		case '개인회생신청서':
			// 외부 파일 포함
			require_once 'generate_pdf_application.php';
			generatePdfApplication($pdf, $pdo, $case_no);
			break;
	}
}

// 출력
$filename = $basic_info['name'] . '_개인회생신청서_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'I');
exit;
?>