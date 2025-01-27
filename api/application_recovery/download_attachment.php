<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['employee_no'])) {
    exit('권한이 없습니다.');
}

$case_no = $_GET['case_no'] ?? 0;
$type = $_GET['type'] ?? '';

if (!$case_no || !$type) {
    exit('필수 파라미터가 누락되었습니다.');
}

try {
    // 사건 정보 조회
    $stmt = $pdo->prepare("
        SELECT name, case_number 
        FROM case_management 
        WHERE case_no = ?
    ");
    $stmt->execute([$case_no]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        exit('사건 정보를 찾을 수 없습니다.');
    }

    // 파일명 생성
    $filename = "{$case['name']}_{$case['case_number']}_{$type}_별지.xlsx";
    
    // 엑셀 파일 생성 로직
    require_once 'vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // 헤더 설정
    $headers = getHeadersByType($type);
    foreach ($headers as $col => $header) {
        $sheet->setCellValue($col . '1', $header);
    }
    
    // 데이터 조회 및 입력
    $stmt = $pdo->prepare("
        SELECT * FROM application_recovery_assets 
        WHERE case_no = ? AND asset_type = ?
    ");
    $stmt->execute([$case_no, $type]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $row = 2;
    foreach ($assets as $asset) {
        fillDataByType($sheet, $row++, $asset, $type);
    }
    
    // 파일 다운로드
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    
} catch (Exception $e) {
    writeLog("별지 다운로드 오류: " . $e->getMessage());
    exit('파일 생성 중 오류가 발생했습니다.');
}

function getHeadersByType($type) {
    switch($type) {
        case 'deposits':
            return [
                'A' => '은행명',
                'B' => '계좌번호',
                'C' => '예금종류',
                'D' => '잔액(원)',
                'E' => '비고'
            ];
        
        case 'insurance':
            return [
                'A' => '보험회사',
                'B' => '증권번호',
                'C' => '보험종류',
                'D' => '계약일자',
                'E' => '만기일자',
                'F' => '보험가입금액(원)',
                'G' => '예상해지환급금(원)',
                'H' => '비고'
            ];
            
        case 'businessAssets':
            return [
                'A' => '품목',
                'B' => '제조사',
                'C' => '모델명',
                'D' => '구입시기',
                'E' => '수량',
                'F' => '구입가격(원)',
                'G' => '중고시세(원)',
                'H' => '총평가액(원)',
                'I' => '비고'
            ];
            
        default:
            return [];
    }
}

function fillDataByType($sheet, $row, $asset, $type) {
    $details = json_decode($asset['details'] ?? '[]', true);
    
    switch($type) {
        case 'deposits':
            $sheet->setCellValue('A' . $row, $asset['financial_institution']);
            $sheet->setCellValue('B' . $row, getDetailValue($details, 'account_number'));
            $sheet->setCellValue('C' . $row, getDetailValue($details, 'deposit_type'));
            $sheet->setCellValue('D' . $row, $asset['amount']);
            $sheet->setCellValue('E' . $row, $asset['memo']);
            break;
            
        case 'insurance':
            $sheet->setCellValue('A' . $row, $asset['financial_institution']);
            $sheet->setCellValue('B' . $row, getDetailValue($details, 'policy_number'));
            $sheet->setCellValue('C' . $row, getDetailValue($details, 'insurance_type'));
            $sheet->setCellValue('D' . $row, getDetailValue($details, 'contract_date'));
            $sheet->setCellValue('E' . $row, getDetailValue($details, 'expiry_date'));
            $sheet->setCellValue('F' . $row, getDetailValue($details, 'insured_amount'));
            $sheet->setCellValue('G' . $row, $asset['amount']);
            $sheet->setCellValue('H' . $row, $asset['memo']);
            break;
            
        case 'businessAssets':
            $sheet->setCellValue('A' . $row, $asset['description']);
            $sheet->setCellValue('B' . $row, getDetailValue($details, 'manufacturer'));
            $sheet->setCellValue('C' . $row, getDetailValue($details, 'model'));
            $sheet->setCellValue('D' . $row, getDetailValue($details, 'purchase_date'));
            $sheet->setCellValue('E' . $row, getDetailValue($details, 'quantity'));
            $sheet->setCellValue('F' . $row, getDetailValue($details, 'purchase_price'));
            $sheet->setCellValue('G' . $row, getDetailValue($details, 'used_price'));
            $sheet->setCellValue('H' . $row, $asset['amount']);
            $sheet->setCellValue('I' . $row, $asset['memo']);
            break;
    }
}

function getDetailValue($details, $key) {
    foreach ($details as $detail) {
        if ($detail['key'] === $key) {
            return $detail['value'];
        }
    }
    return '';
}