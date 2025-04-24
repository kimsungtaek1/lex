<?php
// Mistral OCR API를 사용하여 PDF에서 텍스트 추출하기

// API 키 설정
$apiKey = 'ObF5TSsg5RGcxjIbo3PBAaETt3Jpy4jP'; // 실제 API 키로 변경하세요

// PDF 파일이 들어 있는 폴더 경로 설정
$folderPath = './pdf/1';

// 처리 결과를 저장할 디렉토리
$outputPath = './ocr_results';
if (!file_exists($outputPath)) {
    mkdir($outputPath, 0777, true);
}

// 폴더 내 모든 PDF 파일 목록 가져오기
$pdfs = glob($folderPath . '/*.pdf', GLOB_BRACE);

// 파일이 하나도 없을 경우
if (empty($pdfs)) {
    die("폴더에 PDF 파일이 없습니다.");
}

echo "총 " . count($pdfs) . "개의 PDF 파일을 처리합니다.\n";

// 각 PDF 파일에 대해 OCR 처리
foreach ($pdfs as $pdfPath) {
    $filename = basename($pdfPath);
    echo "처리 중: $filename\n";
    
    // 파일의 웹 접근 가능한 URL 생성
    // 주의: 해당 URL은 인터넷에서 접근 가능해야 합니다
    $fileUrl = 'https://lez062811.mycafe24.com/ocr' . substr($pdfPath, 1);
    
    // JSON 요청 바디 설정
    $jsonData = [
        "model" => "mistral-ocr-latest",
        "document" => [
            "type" => "document_url",
            "document_url" => $fileUrl,
            "document_name" => $filename
        ]
    ];
    
    // API 요청을 위한 CURL 초기화
    $ch = curl_init('https://api.mistral.ai/v1/ocr');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 타임아웃 설정 (2분)
    
    // 요청 헤더 설정
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    // 요청 바디 설정
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
    
    // API 요청 실행
    $response = curl_exec($ch);
    
    // 오류 처리
    if (curl_errno($ch)) {
        echo '요청 오류: ' . curl_error($ch) . "\n";
        curl_close($ch);
        continue;
    }
    
    // HTTP 상태 코드 확인
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        echo "HTTP 오류 코드: $httpCode\n";
        echo "응답 내용: $response\n";
        continue;
    }
    
    // JSON 응답 분석
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON 파싱 오류: " . json_last_error_msg() . "\n";
        continue;
    }
    
    // 추출된 텍스트 확인
    if (isset($result['pages']) && !empty($result['pages'][0]['markdown'])) {
        $extractedText = $result['pages'][0]['markdown'];
        
        // 결과를 파일로 저장
        $outputFile = $outputPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
        file_put_contents($outputFile, $extractedText);
        
        echo "텍스트 추출 성공: $outputFile\n";
        
        // 추출된 텍스트 일부 표시 (처음 200자)
        $previewText = substr($extractedText, 0, 200) . (strlen($extractedText) > 200 ? '...' : '');
        echo "추출된 텍스트 미리보기: \n$previewText\n\n";
    } else {
        echo "경고: 텍스트 추출 결과가 없거나 형식이 잘못되었습니다.\n";
        if (isset($result['error'])) {
            echo "API 오류: " . $result['error']['message'] . "\n";
        }
    }
}

echo "모든 PDF 처리가 완료되었습니다.\n";
?>
