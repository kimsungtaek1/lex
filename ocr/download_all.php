<?php
// 오류 표시 끄기
error_reporting(0);
ini_set('display_errors', 0);

// download_all.php - 작업의 모든 결과 파일을 ZIP으로 다운로드
require_once 'config.php';
require_once 'process_monitor.php';

// 임시 ZIP 파일 생성 경로
$tmpZipPath = $config['temp_path'] . '/ocr_results_' . time() . '.zip';

// 작업 ID 확인
if (empty($_GET['job_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo '작업 ID가 필요합니다.';
    exit;
}

$jobId = (int)$_GET['job_id'];

// 프로세스 모니터 초기화
$processMonitor = new OCRProcessMonitor();

// 작업 결과 가져오기
$jobResults = $processMonitor->getJobResults($jobId);

// 작업 정보 가져오기
$jobDetails = $processMonitor->getJobDetails($jobId);

if (!$jobDetails || !$jobResults) {
    header('HTTP/1.1 404 Not Found');
    echo '작업을 찾을 수 없습니다.';
    exit;
}

// 결과 파일 목록 확인
if (empty($jobResults['files'])) {
    header('HTTP/1.1 404 Not Found');
    echo '다운로드할 결과 파일이 없습니다.';
    exit;
}

// ZIP 파일 생성
$zip = new ZipArchive();

if ($zip->open($tmpZipPath, ZipArchive::CREATE) !== true) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'ZIP 파일 생성에 실패했습니다.';
    exit;
}

// 파일을 ZIP에 추가
$addedFiles = 0;
$errorFiles = [];

foreach ($jobResults['files'] as $result) {
    // 결과 파일들 추가
    foreach (['text_file', 'json_file', 'table_file'] as $fileType) {
        if (!empty($result[$fileType]) && file_exists($result[$fileType])) {
            $fileName = basename($result[$fileType]);
            $originalFileName = basename($result['original_file']);
            $fileInfo = pathinfo($fileName);
            
            // 원본 파일명 + 결과 유형으로 파일명 변경
            $fileNameInZip = pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . 
                             ($fileType === 'text_file' ? 'text' : 
                              ($fileType === 'json_file' ? 'data' : 'table')) . 
                             '.' . $fileInfo['extension'];
            
            if ($zip->addFile($result[$fileType], $fileNameInZip)) {
                $addedFiles++;
            } else {
                $errorFiles[] = $fileName;
            }
        }
    }
}

// ZIP 파일 닫기
$zip->close();

// 파일 추가 실패 확인
if ($addedFiles === 0) {
    unlink($tmpZipPath);
    header('HTTP/1.1 500 Internal Server Error');
    echo '결과 파일을 ZIP에 추가하는데 실패했습니다.';
    exit;
}

// 작업 이름을 ZIP 파일명에 포함
$jobName = preg_replace('/[^a-zA-Z0-9가-힣_\-]/', '_', $jobDetails['job']['name']);
$downloadFileName = 'OCR_결과_' . $jobName . '_' . date('Ymd_His') . '.zip';

// 다운로드 헤더 설정
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
header('Content-Length: ' . filesize($tmpZipPath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// 출력 버퍼 비우기
ob_clean();
flush();

// 파일 전송
readfile($tmpZipPath);

// 임시 파일 정리
unlink($tmpZipPath);
exit;
?>