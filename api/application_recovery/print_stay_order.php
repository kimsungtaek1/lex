<?php
require_once '../../config.php';
require_once '../tfpdf/tfpdf.php';
session_start();

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

if (!isset($_GET['order_no'])) {
    exit("필수 파라미터가 누락되었습니다.");
}

$order_no = intval($_GET['order_no']);

try {
    $stmt = $pdo->prepare("
        SELECT so.*, cm.case_number, cm.name, ar.now_address 
        FROM application_recovery_stay_orders so
        JOIN case_management cm ON so.case_no = cm.case_no
        LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no
        WHERE so.order_no = ?
    ");
    $stmt->execute([$order_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        exit("중지명령서를 찾을 수 없습니다.");
    }

    class StayOrderPDF extends tFPDF {
        function Header() {
            $this->SetFont('NanumGothic', 'B', 16);
            $this->Cell(0, 10, '중 지 명 령 신 청 서', 0, 1, 'C');
            $this->Ln(10);
        }
        
        function ChapterTitle($title) {
            $this->SetFont('NanumGothic', 'B', 12);
            $this->Cell(0, 8, $title, 0, 1, 'L');
            $this->Ln(4);
        }
        
        function ChapterBody($txt) {
            $this->SetFont('NanumGothic', '', 11);
            $this->MultiCell(0, 6, $txt);
            $this->Ln(4);
        }
    }

    // PDF 생성
    $pdf = new StayOrderPDF();
    $pdf->AddFont('NanumGothic', '', 'NanumGothic.ttf', true);
    $pdf->AddFont('NanumGothic', 'B', 'NanumGothicBold.ttf', true);
    $pdf->AddPage();
    
    // 중지명령 신청서
    $pdf->ChapterTitle("중지명령 신청서");
    $pdf->ChapterBody($order['application']);
    
    // 신청취지
    $pdf->ChapterTitle("신청취지");
    $pdf->ChapterBody($order['purpose']);
    
    // 신청원인
    $pdf->ChapterTitle("신청원인");
    $pdf->ChapterBody($order['reason']);
    
    // 소명방법
    $pdf->ChapterTitle("소명방법");
    $pdf->ChapterBody($order['method']);
    
    // PDF 출력
    $pdf->Output('I', '중지명령신청서.pdf');

} catch (Exception $e) {
    exit("오류가 발생했습니다: " . $e->getMessage());
}