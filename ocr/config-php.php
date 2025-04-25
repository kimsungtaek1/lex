<?php
/**
 * OCR 인식률 향상 시스템 기본 설정 파일
 * 카페24 웹호스팅 환경에 최적화됨
 */

// 에러 표시 설정 (개발시에만 활성화)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// 기본 경로 설정
define('ROOT_PATH', dirname(__FILE__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('TEMP_PATH', ROOT_PATH . '/tmp');
define('OUTPUT_PATH', ROOT_PATH . '/results');
define('MODEL_PATH', ROOT_PATH . '/models');

// 필요한 디렉토리 생성
$required_dirs = [UPLOAD_PATH, TEMP_PATH, OUTPUT_PATH, MODEL_PATH];
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 데이터베이스 설정
$config = [
    // 데이터베이스 설정
    'db_host' => 'localhost',
    'db_name' => 'ocr_system',
    'db_user' => 'username',  // 변경 필요
    'db_pass' => 'password',  // 변경 필요
    
    // 네이버 Clova OCR API 설정
    'clova_secret_key' => 'RmFaRmVTRlJ2b2F3REVNYXFRYkl4cmR3eklteGdQeGE=',  // 변경 필요
    'clova_api_url' => 'https://fi843nx2lb.apigw.ntruss.com/custom/v1/41295/ac388ed51e923f188688af742326a2e342ac67854225127e0f917bcf1c08f1c1/general',
    
    // 파일 및 폴더 경로
    'upload_path' => UPLOAD_PATH,
    'temp_path' => TEMP_PATH,
    'output_path' => OUTPUT_PATH,
    'model_path' => MODEL_PATH,
    
    // 학습 데이터 설정
    'learning_dataset_dir' => MODEL_PATH . '/learning_data',
    
    // 웹호스팅 최적화 설정
    'max_execution_time' => 30,  // 카페24 기본 실행 시간 제한
    'memory_limit' => '256M',    // 메모리 제한
    
    // 사용자 정의 사전
    'custom_dictionary' => [
        '금액' => ['긍액', '금맥', '급액', '금엑', '귬액'],
        '부가세' => ['부가서', '부까세', '부가셰', '부가새'],
        '합계' => ['합게', '합깨', '합개', '함계'],
        // 추가할 사전 단어...
    ],
    
    // 문서 템플릿
    'document_templates' => [
        '영수증' => [
            'fields' => ['날짜', '금액', '상품명', '부가세', '합계'],
            'tableStructure' => [
                'headers' => ['상품명', '단가', '수량', '금액']
            ]
        ],
        '송장' => [
            'fields' => ['송장번호', '발송인', '수취인', '주소', '연락처'],
            'tableStructure' => [
                'headers' => ['품목', '중량', '수량', '비고']
            ]
        ],
        // 추가 템플릿...
    ]
];

// 데이터베이스 연결 함수
function getDB() {
    global $config;
    
    try {
        $db = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
            $config['db_user'],
            $config['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $db;
    } catch (PDOException $e) {
        // 오류 로깅 (실제 환경에서는 오류 내용을 숨김)
        error_log("데이터베이스 연결 오류: " . $e->getMessage());
        return null;
    }
}

// 로그 함수
function logMessage($message, $level = 'info') {
    global $config;
    $logFile = $config['output_path'] . '/ocr_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    
    // 개발 모드에서만 콘솔에 출력
    if (defined('DEV_MODE') && DEV_MODE === true) {
        echo $formattedMessage;
    }
}

// 메모리 및 실행 시간 최적화 설정
ini_set('memory_limit', $config['memory_limit']);
ini_set('max_execution_time', $config['max_execution_time']);

// 세션 시작 (웹 인터페이스에서 사용)
if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
    session_start();
}
