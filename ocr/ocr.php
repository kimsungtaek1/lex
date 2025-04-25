<?php

include_once 'config.php'; // 설정 파일 포함

// API 설정
$client_secret = "RmFaRmVTRlJ2b2F3REVNYXFRYkl4cmR3eklteGdQeGE="; // 네이버 클로바 OCR API 시크릿 키로 변경하세요
$url = "https://fi843nx2lb.apigw.ntruss.com/custom/v1/41295/ac388ed51e923f188688af742326a2e342ac67854225127e0f917bcf1c08f1c1/general"; // 네이버 클로바 OCR API URL

// 처리할 이미지 폴더 경로 설정
$folderPath = './images';

// 결과를 저장할 디렉토리
$outputPath = './ocr_results';
if (!file_exists($outputPath)) {
	mkdir($outputPath, 0777, true);
}

// 로그 함수
function logMessage($message) {
	global $outputPath;
	$logFile = $outputPath . '/ocr_log.txt';
	$timestamp = date('Y-m-d H:i:s');
	$formattedMessage = "[{$timestamp}] {$message}\n";
	file_put_contents($logFile, $formattedMessage, FILE_APPEND);
	echo $formattedMessage;
}

// 폴더 내 모든 이미지 파일 목록 가져오기
$images = glob($folderPath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

// 파일이 하나도 없을 경우
if (empty($images)) {
	logMessage("폴더에 이미지 파일이 없습니다.");
	die();
}

logMessage("총 " . count($images) . "개의 이미지 파일을 처리합니다.");

// 각 이미지 파일에 대해 OCR 처리
foreach ($images as $imagePath) {
	$filename = basename($imagePath);
	logMessage("처리 중: $filename");
	
	// OCR 처리 함수 호출
	$result = processImageWithClova($imagePath, $filename, $client_secret, $url);
	
	if ($result !== false) {
		// 결과를 파일로 저장
		$outputTextFile = $outputPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_text.txt';
		$outputJsonFile = $outputPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_data.json';
		
		// 텍스트만 추출하여 저장
		$extractedText = extractTextFromResult($result);
		file_put_contents($outputTextFile, $extractedText);
		
		// 원본 JSON 응답 저장
		file_put_contents($outputJsonFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		
		logMessage("텍스트 추출 성공: $outputTextFile");
		
		// 테이블 정보가 있는지 확인
		if (hasTableData($result)) {
			$outputTableFile = $outputPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_table.html';
			$tableHtml = convertToHtmlTable($result);
			file_put_contents($outputTableFile, $tableHtml);
			logMessage("테이블 추출 성공: $outputTableFile");
		}
		
		// 추출된 텍스트 일부 표시 (처음 200자)
		$previewText = substr($extractedText, 0, 200) . (strlen($extractedText) > 200 ? '...' : '');
		logMessage("추출된 텍스트 미리보기: \n$previewText\n");
	}
}

logMessage("모든 이미지 처리가 완료되었습니다.");

function processImageWithClova($imagePath, $filename, $client_secret, $url) {
	// 요청 파라미터 설정
	$params = new stdClass();
	$params->version = "V2";
	$params->requestId = uniqid(); // UUID 대신 uniqid() 사용
	$params->timestamp = time();
	
	// 이미지 정보 설정
	$image = new stdClass();
	$image->format = pathinfo($imagePath, PATHINFO_EXTENSION);
	$image->data = base64_encode(file_get_contents($imagePath));
	$image->name = pathinfo($filename, PATHINFO_FILENAME);
	
	// 테이블 인식 옵션 추가
	$options = new stdClass();
	$options->language = "ko"; // 한국어 설정
	$options->tables = true; // 테이블 인식 활성화
	$options->verbose = true; // 상세 결과 요청
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
	curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 타임아웃 설정 (2분)
	
	// 헤더 설정
	$headers = array();
	$headers[] = "X-OCR-SECRET: ".$client_secret;
	$headers[] = "Content-Type: application/json; charset=utf-8";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	// API 요청 실행
	$response = curl_exec($ch);
	
	// 오류 처리
	if (curl_errno($ch)) {
		logMessage('요청 오류: ' . curl_error($ch));
		curl_close($ch);
		return false;
	}
	
	// HTTP 상태 코드 확인
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpCode != 200) {
		logMessage("HTTP 오류 코드: $httpCode");
		logMessage("응답 내용: $response");
		return false;
	}
	
	// JSON 응답 분석
	$result = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		logMessage("JSON 파싱 오류: " . json_last_error_msg());
		return false;
	}
	
	return $result;
}


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


function convertToHtmlTable($result) {
	$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>추출된 테이블</title>
	<style>
		table {
			border-collapse: collapse;
			margin-bottom: 20px;
			width: 100%;
		}
		table, th, td {
			border: 1px solid black;
		}
		th, td {
			padding: 8px;
			text-align: left;
		}
		th {
			background-color: #f2f2f2;
		}
	</style>
</head>
<body>
	<h1>추출된 테이블</h1>';
	
	if (isset($result['images']) && count($result['images']) > 0) {
		foreach ($result['images'] as $imageIndex => $image) {
			if (isset($image['tables']) && count($image['tables']) > 0) {
				$html .= '<h2>이미지 ' . ($imageIndex + 1) . '</h2>';
				
				foreach ($image['tables'] as $tableIndex => $table) {
					$html .= '<h3>테이블 ' . ($tableIndex + 1) . '</h3>';
					$html .= '<table>';
					
					// 테이블 구조 분석
					$rows = [];
					$maxCol = 0;
					
					if (isset($table['cells']) && is_array($table['cells'])) {
						foreach ($table['cells'] as $cell) {
							$rowIndex = isset($cell['rowIndex']) ? $cell['rowIndex'] : 0;
							$colIndex = isset($cell['colIndex']) ? $cell['colIndex'] : 0;
							$rowSpan = isset($cell['rowSpan']) ? $cell['rowSpan'] : 1;
							$colSpan = isset($cell['colSpan']) ? $cell['colSpan'] : 1;
							$text = isset($cell['inferText']) ? $cell['inferText'] : '';
							
							if (!isset($rows[$rowIndex])) {
								$rows[$rowIndex] = [];
							}
							
							$rows[$rowIndex][$colIndex] = [
								'text' => $text,
								'rowSpan' => $rowSpan,
								'colSpan' => $colSpan
							];
							
							$currentMaxCol = $colIndex + $colSpan;
							if ($currentMaxCol > $maxCol) {
								$maxCol = $currentMaxCol;
							}
						}
					}
					
					// 테이블 렌더링
					ksort($rows); // 행 인덱스로 정렬
					foreach ($rows as $rowIndex => $cols) {
						$html .= '<tr>';
						ksort($cols); // 열 인덱스로 정렬
						
						for ($i = 0; $i < $maxCol; $i++) {
							if (isset($cols[$i])) {
								$cell = $cols[$i];
								$html .= '<td';
								if ($cell['rowSpan'] > 1) {
									$html .= ' rowspan="' . $cell['rowSpan'] . '"';
								}
								if ($cell['colSpan'] > 1) {
									$html .= ' colspan="' . $cell['colSpan'] . '"';
								}
								$html .= '>' . htmlspecialchars($cell['text']) . '</td>';
							}
						}
						
						$html .= '</tr>';
					}
					
					$html .= '</table>';
				}
			}
		}
	} else {
		$html .= '<p>추출된 테이블이 없습니다.</p>';
	}
	
	$html .= '</body></html>';
	return $html;
}
?>