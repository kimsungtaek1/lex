<?php
/**
 * 향상된 OCR 처리 시스템
 * 네이버 Clova OCR API를 활용하여 인식률을 높이기 위한 다양한 전처리 및 후처리 기법 적용
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';

/**
 * 향상된 OCR 처리 메인 함수
 * @param string $imagePath 이미지 파일 경로
 * @param array $options 처리 옵션 (전처리, 테이블 인식 등)
 * @return array 처리 결과
 */
function enhancedOCRProcess($imagePath, $options = []) {
    global $config;
    
    // 기본 옵션 설정
    $defaultOptions = [
        'preprocess' => true,           // 이미지 전처리 적용
        'enhance_table' => true,        // 테이블 인식 향상
        'apply_custom_dict' => true,    // 사용자 정의 사전 적용
        'document_type' => null,        // 문서 유형 (템플릿)
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // 파일 정보 확인
    $filename = basename($imagePath);
    $outputBasePath = $config['output_path'] . '/' . pathinfo($filename, PATHINFO_FILENAME);
    
    logMessage("파일 처리 시작: $filename");
    
    try {
        // 1. 이미지 전처리
        $enhancedImagePath = $imagePath;
        if ($options['preprocess']) {
            logMessage("이미지 전처리 시작...");
            $tempPath = $config['temp_path'] . '/pre_' . $filename;
            $enhancedImagePath = preprocessImage($imagePath, $tempPath);
            if (!$enhancedImagePath) {
                logMessage("이미지 전처리 실패. 원본 이미지 사용.", 'warning');
                $enhancedImagePath = $imagePath;
            }
        }
        
        // 2. OCR 처리
        logMessage("OCR 처리 시작...");
        $ocrResult = processWithClova($enhancedImagePath, $config['clova_secret_key'], $config['clova_api_url']);
        
        if (!$ocrResult) {
            throw new Exception("OCR 처리 실패");
        }
        
        // 3. 텍스트 추출
        $extractedText = extractTextFromResult($ocrResult);
        
        // 4. 사용자 정의 사전 적용
        if ($options['apply_custom_dict']) {
            logMessage("사용자 정의 사전 적용 중...");
            $correctedText = correctWithCustomDictionary($extractedText, $config['custom_dictionary']);
        } else {
            $correctedText = $extractedText;
        }
        
        // 5. 문서 유형별 템플릿 적용
        $documentType = $options['document_type'];
        if ($documentType && isset($config['document_templates'][$documentType])) {
            logMessage("템플릿 적용 중: $documentType");
            $ocrResult = applyTemplateEnhancement(
                $ocrResult, 
                $config['document_templates'][$documentType]
            );
            
            // 템플릿 기반 텍스트 보정
            $correctedText = applyTemplateCorrection(
                $correctedText, 
                $config['document_templates'][$documentType]
            );
        }
        
        // 6. 테이블 인식 향상
        if ($options['enhance_table'] && hasTableData($ocrResult)) {
            logMessage("테이블 인식 개선 중...");
            $tableTemplate = ($documentType && isset($config['document_templates'][$documentType]['tableStructure'])) ? 
                $config['document_templates'][$documentType]['tableStructure'] : null;
                
            $tableHtml = generateEnhancedTableHtml($ocrResult, $tableTemplate);
        } else {
            $tableHtml = '';
        }
        
        // 7. 결과 저장
        $outputTextFile = $outputBasePath . '_text.txt';
        $outputJsonFile = $outputBasePath . '_data.json';
        $outputTableFile = $outputBasePath . '_table.html';
        
        file_put_contents($outputTextFile, $correctedText);
        file_put_contents($outputJsonFile, json_encode($ocrResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if (!empty($tableHtml)) {
            file_put_contents($outputTableFile, $tableHtml);
        }
        
        // 8. 임시 파일 정리
        if ($enhancedImagePath != $imagePath && file_exists($enhancedImagePath)) {
            @unlink($enhancedImagePath);
        }
        
        // 결과 반환
        $result = [
            'success' => true,
            'text' => $correctedText,
            'has_table' => !empty($tableHtml),
            'output_files' => [
                'text' => $outputTextFile,
                'json' => $outputJsonFile,
                'table' => !empty($tableHtml) ? $outputTableFile : null
            ],
            'original_image' => $imagePath,
            'document_type' => $documentType
        ];
        
        logMessage("처리 완료: $filename");
        return $result;
        
    } catch (Exception $e) {
        logMessage("처리 오류: " . $e->getMessage(), 'error');
        
        // 임시 파일 정리
        if ($enhancedImagePath != $imagePath && file_exists($enhancedImagePath)) {
            @unlink($enhancedImagePath);
        }
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'original_image' => $imagePath
        ];
    }
}

/**
 * 이미지 전처리 함수 (GD 라이브러리 사용)
 * 카페24 웹호스팅 환경에 맞게 최적화됨
 * @param string $imagePath 원본 이미지 경로
 * @param string $outputPath 출력 이미지 경로
 * @return string|bool 처리된 이미지 경로 또는 실패 시 false
 */
function preprocessImage($imagePath, $outputPath) {
    try {
        // 이미지 포맷 확인
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new Exception("이미지 정보를 읽을 수 없습니다.");
        }
        
        // 이미지 로드
        $mimeType = $imageInfo['mime'];
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                throw new Exception("지원하지 않는 이미지 형식: $mimeType");
        }
        
        if (!$image) {
            throw new Exception("이미지 로드 실패");
        }
        
        // 원본 크기 가져오기
        $width = imagesx($image);
        $height = imagesy($image);
        
        // 1. 크기 조정 (업스케일링: 더 선명한 텍스트 인식을 위해)
        $scaleFactor = 1.5;  // 1.5배 확대
        $newWidth = (int)($width * $scaleFactor);
        $newHeight = (int)($height * $scaleFactor);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));  // 흰색 배경
        
        // PNG 투명도 유지
        if ($mimeType == 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
        
        // 2. 대비 향상
        imagefilter($image, IMG_FILTER_CONTRAST, 30);
        
        // 3. 선명도 향상
        imagefilter($image, IMG_FILTER_SMOOTH, -5);
        
        // 4. 노이즈 제거
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($image, IMG_FILTER_CONTRAST, 20);
        
        // 5. 흑백 변환 (텍스트 문서에 효과적)
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        
        // 6. 이진화 (특히 테이블 구조에 효과적)
        // 이진화 임계값 자동 계산을 위한 히스토그램 분석
        $histogram = array_fill(0, 256, 0);
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (int)(($r + $g + $b) / 3);
                $histogram[$gray]++;
            }
        }
        
        // Otsu 알고리즘으로 최적 임계값 찾기
        $total = imagesy($image) * imagesx($image);
        $sum = 0;
        for ($i = 0; $i < 256; $i++) {
            $sum += $i * $histogram[$i];
        }
        
        $sumBackground = 0;
        $weightBackground = 0;
        $weightForeground = 0;
        $maxVariance = 0;
        $threshold = 0;
        
        for ($i = 0; $i < 256; $i++) {
            $weightBackground += $histogram[$i];
            if ($weightBackground == 0) continue;
            
            $weightForeground = $total - $weightBackground;
            if ($weightForeground == 0) break;
            
            $sumBackground += $i * $histogram[$i];
            $meanBackground = $sumBackground / $weightBackground;
            $meanForeground = ($sum - $sumBackground) / $weightForeground;
            
            $variance = $weightBackground * $weightForeground * ($meanBackground - $meanForeground) * ($meanBackground - $meanForeground);
            if ($variance > $maxVariance) {
                $maxVariance = $variance;
                $threshold = $i;
            }
        }
        
        // 임계값 적용하여 이진화
        $thresholdedImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($thresholdedImage, 255, 255, 255);
        $black = imagecolorallocate($thresholdedImage, 0, 0, 0);
        imagefill($thresholdedImage, 0, 0, $white);
        
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (int)(($r + $g + $b) / 3);
                
                if ($gray < $threshold) {
                    imagesetpixel($thresholdedImage, $x, $y, $black);
                }
            }
        }
        
        // 결과 저장
        switch ($mimeType) {
            case 'image/jpeg':
                $success = imagejpeg($thresholdedImage, $outputPath, 95);
                break;
            case 'image/png':
                $success = imagepng($thresholdedImage, $outputPath, 9);
                break;
            case 'image/gif':
                $success = imagegif($thresholdedImage, $outputPath);
                break;
            default:
                $success = false;
        }
        
        // 메모리 정리
        imagedestroy($image);
        imagedestroy($thresholdedImage);
        
        if (!$success) {
            throw new Exception("이미지 저장 실패");
        }
        
        return $outputPath;
        
    } catch (Exception $e) {
        logMessage("이미지 전처리 오류: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 네이버 Clova OCR API 호출 함수
 * @param string $imagePath 이미지 파일 경로
 * @param string $clientSecret API 시크릿 키
 * @param string $url API URL
 * @return array|bool OCR 결과 또는 실패 시 false
 */
function processWithClova($imagePath, $clientSecret, $url) {
    // 요청 파라미터 설정
    $params = new stdClass();
    $params->version = "V2";
    $params->requestId = uniqid();
    $params->timestamp = time();
    
    // 이미지 정보 설정
    $image = new stdClass();
    $image->format = pathinfo($imagePath, PATHINFO_EXTENSION);
    $image->data = base64_encode(file_get_contents($imagePath));
    $image->name = pathinfo(basename($imagePath), PATHINFO_FILENAME);
    
    // 향상된 옵션 설정
    $options = new stdClass();
    $options->language = "ko"; 
    $options->tables = true;
    $options->verbose = true;
    $options->enableTableDetection = true;
    $options->enableLinkedTextDetection = true;
    $options->enableParagraphDetection = true;
    $options->includeVerticalText = true;
    $options->enableHighQualityMode = true;
    $params->options = $options;
    
    // 이미지 배열 설정
    $params->images = array($image);
    
    // JSON 변환
    $json = json_encode($params);
    
    // API 요청을 위한 CURL 초기화
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30초 타임아웃 (웹호스팅 실행 시간 제한 고려)
    
    // 헤더 설정
    $headers = array();
    $headers[] = "X-OCR-SECRET: ".$clientSecret;
    $headers[] = "Content-Type: application/json; charset=utf-8";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // API 요청 실행
    $response = curl_exec($ch);
    
    // 오류 처리
    if (curl_errno($ch)) {
        logMessage('API 요청 오류: ' . curl_error($ch), 'error');
        curl_close($ch);
        return false;
    }
    
    // HTTP 상태 코드 확인
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        logMessage("HTTP 오류 코드: $httpCode", 'error');
        logMessage("응답 내용: $response", 'error');
        return false;
    }
    
    // JSON 응답 분석
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("JSON 파싱 오류: " . json_last_error_msg(), 'error');
        return false;
    }
    
    return $result;
}

/**
 * OCR 결과에서 텍스트 추출
 * @param array $result OCR 결과
 * @return string 추출된 텍스트
 */
function extractTextFromResult($result) {
    $extractedText = '';
    
    if (isset($result['images']) && count($result['images']) > 0) {
        foreach ($result['images'] as $image) {
            if (isset($image['fields']) && is_array($image['fields'])) {
                foreach ($image['fields'] as $field) {
                    if (isset($field['inferText'])) {
                        $extractedText .= $field['inferText'] . ' ';
                    }
                }
                $extractedText .= "\n";
            }
        }
    }
    
    return trim($extractedText);
}

/**
 * OCR 결과에 테이블 데이터가 있는지 확인
 * @param array $result OCR 결과
 * @return bool 테이블 데이터 존재 여부
 */
function hasTableData($result) {
    if (isset($result['images']) && count($result['images']) > 0) {
        foreach ($result['images'] as $image) {
            if (isset($image['tables']) && count($image['tables']) > 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 사용자 정의 사전을 적용한 텍스트 보정
 * @param string $text 원본 텍스트
 * @param array $dictionary 사용자 정의 사전
 * @return string 보정된 텍스트
 */
function correctWithCustomDictionary($text, $dictionary) {
    foreach ($dictionary as $correctWord => $similarWords) {
        // 정확한 유사어 대체
        $text = str_ireplace($similarWords, $correctWord, $text);
        
        // 레벤슈타인 거리 기반 유사어 찾기
        $words = preg_split('/[\s,.]+/', $text);
        $newWords = [];
        
        foreach ($words as $word) {
            $replaced = false;
            
            // 모든 정확한 단어에 대해 유사도 검사
            foreach ($dictionary as $correct => $similars) {
                if (levenshtein(strtolower($word), strtolower($correct)) <= 2) {
                    $newWords[] = $correct;
                    $replaced = true;
                    break;
                }
                
                // 유사 단어 목록 검사
                foreach ($similars as $similar) {
                    if (levenshtein(strtolower($word), strtolower($similar)) <= 2) {
                        $newWords[] = $correct;
                        $replaced = true;
                        break 2;
                    }
                }
            }
            
            if (!$replaced) {
                $newWords[] = $word;
            }
        }
        
        // 새 텍스트 생성
        $text = implode(' ', $newWords);
    }
    
    return $text;
}

/**
 * 템플릿 기반 OCR 결과 개선
 * @param array $ocrResult OCR 결과
 * @param array $template 템플릿 정보
 * @return array 개선된 OCR 결과
 */
function applyTemplateEnhancement($ocrResult, $template) {
    $fields = $template['fields'] ?? [];
    $tableStructure = $template['tableStructure'] ?? [];
    
    if (empty($fields) && empty($tableStructure)) {
        return $ocrResult;
    }
    
    // 이미지 처리
    if (isset($ocrResult['images']) && is_array($ocrResult['images'])) {
        foreach ($ocrResult['images'] as &$image) {
            // 테이블 헤더 개선
            if (isset($image['tables']) && is_array($image['tables']) && 
                isset($tableStructure['headers']) && is_array($tableStructure['headers'])) {
                
                foreach ($image['tables'] as &$table) {
                    if (isset($table['cells']) && is_array($table['cells'])) {
                        $headerCells = [];
                        
                        // 헤더 행 식별
                        foreach ($table['cells'] as $index => $cell) {
                            if (isset($cell['rowIndex']) && $cell['rowIndex'] === 0 && 
                                isset($cell['colIndex']) && isset($cell['inferText'])) {
                                $headerCells[$cell['colIndex']] = $index;
                            }
                        }
                        
                        // 헤더 매칭 및 수정
                        foreach ($tableStructure['headers'] as $colIndex => $expectedHeader) {
                            if (isset($headerCells[$colIndex])) {
                                $cellIndex = $headerCells[$colIndex];
                                $currentText = $table['cells'][$cellIndex]['inferText'];
                                
                                // 유사도 검사
                                similar_text($currentText, $expectedHeader, $percent);
                                
                                if ($percent >= 60) {
                                    $table['cells'][$cellIndex]['inferText'] = $expectedHeader;
                                    $table['cells'][$cellIndex]['inferConfidence'] = max($table['cells'][$cellIndex]['inferConfidence'] ?? 0, 0.95);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $ocrResult;
}

/**
 * 템플릿 기반 텍스트 보정
 * @param string $text 원본 텍스트
 * @param array $template 템플릿 정보
 * @return string 보정된 텍스트
 */
function applyTemplateCorrection($text, $template) {
    $fields = $template['fields'] ?? [];
    
    // 패턴 적용
    // 일반적인 패턴 보정 규칙
    $patterns = [
        // 날짜 형식 교정
        '/(\d{4})[.,](\d{1,2})[.,](\d{1,2})/' => '$1-$2-$3',
        '/(\d{4})년\s*(\d{1,2})월\s*(\d{1,2})일/' => '$1-$2-$3',
        
        // 금액 형식 교정
        '/(\d+)([,.])(\d{3})([^0-9])/' => '$1$3$4', // 천 단위 구분자 오인식 수정
        '/(\d+)원/' => '$1원', // 금액 표기 교정
        
        // 전화번호 형식 교정
        '/(\d{2,3})(\d{3,4})(\d{4})/' => '$1-$2-$3', // 붙어있는 전화번호 분리
        '/(\d{2,3})\s+(\d{3,4})\s+(\d{4})/' => '$1-$2-$3', // 공백으로 구분된 전화번호
        
        // 특수문자 교정
        '/，/' => ',', // 전각 쉼표
        '/．/' => '.', // 전각 마침표
        '/：/' => ':', // 전각 콜론
        '/；/' => ';', // 전각 세미콜론
        
        // 괄호 교정
        '/\(([^)]+)﹚/' => '($1)', // 닫는 괄호 교정
        '/﹙([^)]+)\)/' => '($1)', // 여는 괄호 교정
    ];
    
    // 필드별 패턴 추가
    foreach ($fields as $field) {
        // 필드 이름이 텍스트에 있는지 확인하고 형식 교정
        if (stripos($text, $field) !== false) {
            // 예: '금액' 필드가 있으면 숫자+원 패턴 강화
            if ($field === '금액' || $field === '합계' || $field === '부가세') {
                $patterns['/'. $field .'[\s:]*([\d,]+)([^원])$/m'] = $field . ': $1원$2';
            }
            
            // 날짜 필드 강화
            if ($field === '날짜' || $field === '발행일') {
                $patterns['/'. $field .'[\s:]*(\d{4})[^\d-](\d{1,2})[^\d-](\d{1,2})/'] = $field . ': $1-$2-$3';
            }
        }
    }
    
    // 패턴 적용
    foreach ($patterns as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }
    
    return $text;
}

/**
 * 테이블 HTML 생성
 * @param array $ocrResult OCR 결과
 * @param array|null $tableTemplate 테이블 템플릿 정보
 * @return string HTML 테이블
 */
function generateEnhancedTableHtml($ocrResult, $tableTemplate = null) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>향상된 테이블 추출 결과</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px 15px;
            text-align: left;
        }
        th { 
            background-color: #4CAF50; 
            color: white;
        }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #ddd; }
        h1, h2 { color: #333; }
        .confidence { color: #888; font-size: 0.8em; }
        .high-confidence { color: green; }
        .low-confidence { color: red; }
    </style>
</head>
<body>
    <h1>향상된 테이블 추출 결과</h1>';
    
    if (isset($ocrResult['images']) && is_array($ocrResult['images'])) {
        foreach ($ocrResult['images'] as $imageIndex => $image) {
            if (isset($image['tables']) && is_array($image['tables'])) {
                $html .= '<h2>이미지 ' . ($imageIndex + 1) . ' 테이블</h2>';
                
                foreach ($image['tables'] as $tableIndex => $table) {
                    $html .= '<h3>테이블 ' . ($tableIndex + 1) . '</h3>';
                    
                    // 테이블 구조 분석
                    $tableData = [];
                    $maxRow = 0;
                    $maxCol = 0;
                    
                    if (isset($table['cells']) && is_array($table['cells'])) {
                        foreach ($table['cells'] as $cell) {
                            $rowIndex = isset($cell['rowIndex']) ? $cell['rowIndex'] : 0;
                            $colIndex = isset($cell['colIndex']) ? $cell['colIndex'] : 0;
                            $rowSpan = isset($cell['rowSpan']) ? $cell['rowSpan'] : 1;
                            $colSpan = isset($cell['colSpan']) ? $cell['colSpan'] : 1;
                            $text = isset($cell['inferText']) ? $cell['inferText'] : '';
                            $confidence = isset($cell['inferConfidence']) ? $cell['inferConfidence'] : 0;
                            
                            $tableData[$rowIndex][$colIndex] = [
                                'text' => $text,
                                'rowSpan' => $rowSpan,
                                'colSpan' => $colSpan,
                                'confidence' => $confidence
                            ];
                            
                            $maxRow = max($maxRow, $rowIndex + 1);
                            $maxCol = max($maxCol, $colIndex + $colSpan);
                        }
                    }
                    
                    // 테이블 렌더링
                    $html .= '<table>';
                    
                    // 빈 테이블 확인
                    if (empty($tableData)) {
                        $html .= '<tr><td>테이블 데이터가 없습니다.</td></tr>';
                    } else {
                        // 헤더 행이 있는지 확인
                        $hasHeader = isset($tableData[0]) && count($tableData[0]) > 0;
                        
                        // 테이블 내용 출력
                        for ($row = 0; $row < $maxRow; $row++) {
                            $html .= '<tr>';
                            
                            for ($col = 0; $col < $maxCol; $col++) {
                                if (isset($tableData[$row][$col])) {
                                    $cell = $tableData[$row][$col];
                                    $confidenceClass = $cell['confidence'] > 0.8 ? 'high-confidence' : 
                                                      ($cell['confidence'] < 0.5 ? 'low-confidence' : '');
                                    
                                    // 헤더 행 또는 일반 셀
                                    $cellTag = ($row === 0 && $hasHeader) ? 'th' : 'td';
                                    
                                    $html .= "<{$cellTag}";
                                    if ($cell['rowSpan'] > 1) {
                                        $html .= ' rowspan="' . $cell['rowSpan'] . '"';
                                    }
                                    if ($cell['colSpan'] > 1) {
                                        $html .= ' colspan="' . $cell['colSpan'] . '"';
                                    }
                                    $html .= '>' . htmlspecialchars($cell['text']);
                                    
                                    // 신뢰도 정보 추가
                                    $html .= ' <span class="confidence ' . $confidenceClass . '">';
                                    $html .= '(' . number_format($cell['confidence'] * 100, 1) . '%)</span>';
                                    
                                    $html .= "</{$cellTag}>";
                                }
                            }
                            
                            $html .= '</tr>';
                        }
                    }
                    
                    $html .= '</table>';
                }
            } else {
                $html .= '<p>테이블이 감지되지 않았습니다.</p>';
            }
        }
    } else {
        $html .= '<p>이미지 데이터가 없거나 테이블이 감지되지 않았습니다.</p>';
    }
    
    $html .= '</body></html>';
    return $html;
}
