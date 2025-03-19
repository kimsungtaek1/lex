<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// 파일 시작 부분에 아래 코드 추가
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'utf-8');
session_start();
require_once '../../config.php';
require_once '../tcpdf/tcpdf.php';

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

// TCPDF 인스턴스 생성 (가로 방향, mm 단위, A4 크기)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

// 문서 정보 설정
$pdf->SetCreator('Your Application');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('개인회생 신청서 자료');
$pdf->SetSubject('개인회생 신청서');
$pdf->SetKeywords('개인회생, 신청서, PDF');
$fontname = $pdf->addTTFfont('/font/NanumGothic.ttf', 'UTF-8', '', 32);

// 기본 폰트 설정
$pdf->SetFont('nanumgothic', '', 11);

// 페이지 여백 설정 (왼쪽, 위쪽, 오른쪽)
$pdf->SetMargins(15, 15, 15);

// 자동 페이지 나눔 설정
$pdf->SetAutoPageBreak(true, 15);

// 새 페이지 추가
$pdf->AddPage();

// 내용 추가
$pdf->SetFont('nanumgothic', 'B', 16);
$pdf->Cell(0, 10, '개인회생 신청서 자료', 0, 1, 'C');

$pdf->SetFont('nanumgothic', '', 12);
$pdf->Cell(0, 10, '신청인: 홍길동', 0, 1, 'C');
$pdf->Cell(0, 10, '사건번호: 2023-123456', 0, 1, 'C');
$pdf->Cell(0, 10, '법원: 서울중앙지방법원', 0, 1, 'C');
$pdf->Cell(0, 10, '출력일자: ' . date('Y년 m월 d일'), 0, 1, 'C');

// PDF 출력
$pdf->Output('example.pdf', 'I');
?>