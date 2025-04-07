<?php
session_start();
require_once '../config.php';

// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
	header('HTTP/1.1 403 Forbidden');
	echo '접근 권한이 없습니다.';
	exit;
}

// 파라미터 검증
$region = isset($_GET['region']) ? $_GET['region'] : '';
$filename = isset($_GET['filename']) ? $_GET['filename'] : '';
$case_no = isset($_GET['case_no']) ? $_GET['case_no'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'recovery'; // 기본값은 회생

if (empty($region) || empty($filename)) {
	header('HTTP/1.1 400 Bad Request');
	echo '잘못된 요청입니다.';
	exit;
}

// 허용된 파일 경로 설정 (보안을 위해 중요)
$baseFolder = ($type == 'bankruptcy') ? 'file/bankruptcy_data_submission_list/' : 'file/recovery_data_submission_list/';

$allowedFiles = [
	'seoul_etc' => $baseFolder . 'seoul_etc.hwp',
	'gangneung' => $baseFolder . 'gangneung.hwp',
	'daegu' => $baseFolder . 'daegu.hwp',
	'daejeon' => $baseFolder . 'daejeon.hwp',
	'busan' => $baseFolder . 'busan.hwp',
	'cheongju' => $baseFolder . 'cheongju.hwp'
];

// 요청된 파일이 허용 목록에 있는지 확인
if (!isset($allowedFiles[$region])) {
	header('HTTP/1.1 404 Not Found');
	echo '파일을 찾을 수 없습니다.';
	exit;
}

// 파일 경로
$filePath = '../' . $allowedFiles[$region];

// 파일 존재 여부 확인
if (!file_exists($filePath)) {
	header('HTTP/1.1 404 Not Found');
	echo '파일을 찾을 수 없습니다.';
	exit;
}

// 파일 다운로드 처리
header('Content-Description: File Transfer');
header('Content-Type: application/hwp');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>