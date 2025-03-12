<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';
require_once '../tfpdf/tfpdf.php';

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

$order_no = $_GET['order_no'] ?? '';

if (!$order_no) {
    exit("금지명령 정보를 찾을 수 없습니다.");
}

// 금지명령서 정보 가져오기 
$stmt = $pdo->prepare("
    SELECT po.*, cm.case_number, ar.name, ar.resident_number, ar.now_address, ar.phone,
           e.name as assigned_name, e.position as assigned_position
    FROM application_recovery_prohibition_orders po
    JOIN case_management cm ON po.case_no = cm.case_no
    JOIN application_recovery ar ON cm.case_no = ar.case_no
    JOIN employee e ON ar.assigned_employee = e.employee_no
    WHERE po.order_no = ?
");
$stmt->execute([$order_no]);
$order_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order_info) {
    exit("금지명령서 정보를 찾을 수 없습니다.");
}

class ProhibitionOrderPDF extends tFPDF {
    function Header() {
        $this->SetMargins(10, 10, 10);
        $this->SetFont('NanumGothic', 'B', 20);
        $this->Cell(0, 10, '금 지 명 령 신 청 서', 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-10);
        $this->SetFont('NanumGothic', '', 10);
        $this->Cell(0, 8, '페이지 '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
    
    function ChapterTitle($title) {
        $this->SetFont('NanumGothic', 'B', 12); 
        $this->Cell(0, 8, $title, 0, 1, 'C');
        $this->Ln(5);
    }
    
    function ChapterBody($txt) {
        $this->SetFont('NanumGothic', '', 10);
        
        // 텍스트를 줄 단위로 분리
        $lines = explode("\n", $txt);
        
        foreach($lines as $line) {
            // 날짜, 신청인, 연락처, 대리인 정보 확인
            if(preg_match('/^\d{4}\. \d{1,2}\. \d{1,2}\./', trim($line)) || // 날짜 형식 확인
               strpos($line, "신청인 ") === 0 ||
               strpos($line, "연락처") === 0 ||
               strpos($line, "위 대리인") === 0) {
                // 중앙 정렬로 출력
                $this->Cell(0, 5, trim($line), 0, 1, 'C');
            } else {
                // 일반 텍스트는 기존대로 출력
                $this->MultiCell(0, 5, $line);
            }
        }
        $this->Ln(5);
    }
}

// PDF 생성
$pdf = new ProhibitionOrderPDF();
$pdf->SetMargins(10, 15, 10);  // 좌, 상, 우 여백을 10mm, 15mm, 10mm로 설정
$pdf->SetAutoPageBreak(true, 10);  // 하단 여백을 10mm로 설정
$pdf->AddFont('NanumGothic', '', 'NanumGothic.ttf', true);
$pdf->AddFont('NanumGothic', 'B', 'NanumGothicBold.ttf', true);
$pdf->AliasNbPages();
$pdf->AddPage();

// 신청서 내용
$pdf->ChapterTitle("금지명령 신청서");
$pdf->ChapterBody($order_info['application']);

// 신청취지
$pdf->ChapterTitle("신청취지");
$pdf->ChapterBody($order_info['purpose']);

// 신청원인
$pdf->ChapterTitle("신청원인");
$pdf->ChapterBody($order_info['reason']);

// 파일명 설정
$filename = "prohibition_order_" . substr($order_info['case_number'], -6) . "_" . date('Ymd') . ".pdf";

// PDF 출력
header('Content-Type: application/pdf');
header("Content-Disposition: attachment; " . $filename);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('I', $filename);
?>