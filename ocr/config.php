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
        // 디렉토리 생성 시도
        if (!@mkdir($dir, 0755, true)) {
            // 오류 발생 시 오류 로깅
            $error = error_get_last();
            logMessage("디렉토리 생성 실패: {$dir} - {$error['message']}", 'error');
            
            // 상위 디렉토리 쓰기 권한 확인
            $parentDir = dirname($dir);
            if (!is_writable($parentDir)) {
                logMessage("상위 디렉토리 {$parentDir}에 쓰기 권한이 없습니다.", 'error');
            }
        } else {
            logMessage("디렉토리 생성 성공: {$dir}", 'info');
        }
    } else {
        // 기존 디렉토리 쓰기 권한 확인
        if (!is_writable($dir)) {
            logMessage("디렉토리 {$dir}에 쓰기 권한이 없습니다.", 'warning');
        }
    }
}

// 데이터베이스 설정
$config = [
    // 데이터베이스 설정
    'db_host' => getenv('DB_HOST'),
    'db_name' => getenv('DB_NAME'),
    'db_user' => getenv('DB_USER'),
    'db_pass' => getenv('DB_PASS'),
    
    // 네이버 Clova OCR API 설정
    'clova_secret_key' => getenv('CLOVA_SECRET_KEY'),
    'clova_api_url' => 'https://fi843nx2lb.apigw.ntruss.com/custom/v1/41295/ac388ed51e923f188688af742326a2e342ac67854225127e0f917bcf1c08f1c1/general',

    // 파일 및 폴더 경로
    'upload_path' => UPLOAD_PATH,
    'temp_path' => TEMP_PATH,
    'output_path' => OUTPUT_PATH,
    'model_path' => MODEL_PATH,
    
    // 학습 데이터 설정
    'learning_dataset_dir' => MODEL_PATH . '/learning_data',
    
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

/**
 * 안전한 파일 읽기 함수
 * @param string $filePath 파일 경로
 * @param bool $asJson JSON으로 디코딩 여부
 * @return mixed 파일 내용 또는 실패 시 false
 */
function safeReadFile($filePath, $asJson = false) {
    try {
        if (!file_exists($filePath)) {
            throw new Exception("파일이 존재하지 않습니다: $filePath");
        }
        
        if (!is_readable($filePath)) {
            throw new Exception("파일을 읽을 수 없습니다: $filePath");
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new Exception("파일 읽기 실패: $filePath");
        }
        
        if ($asJson) {
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON 파싱 오류: " . json_last_error_msg());
            }
            
            return $decoded;
        }
        
        return $content;
    } catch (Exception $e) {
        logMessage($e->getMessage(), 'error');
        return false;
    }
}

/**
 * 안전한 파일 쓰기 함수
 * @param string $filePath 파일 경로
 * @param mixed $content 쓸 내용
 * @param bool $asJson JSON으로 인코딩 여부
 * @return bool 성공 여부
 */
function safeWriteFile($filePath, $content, $asJson = false) {
    try {
        // 디렉토리 확인 및 생성
        $dir = dirname($filePath);
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("디렉토리를 생성할 수 없습니다: $dir");
            }
        }
        
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new Exception("파일에 쓰기 권한이 없습니다: $filePath");
        }
        
        // 내용 준비
        $dataToWrite = $asJson ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $content;
        
        // 안전한 파일 쓰기 (임시 파일 사용)
        $tempFile = $filePath . '.tmp';
        if (file_put_contents($tempFile, $dataToWrite) === false) {
            throw new Exception("임시 파일 쓰기 실패: $tempFile");
        }
        
        // 원자적 파일 교체
        if (!rename($tempFile, $filePath)) {
            @unlink($tempFile); // 임시 파일 정리
            throw new Exception("파일 교체 실패: $filePath");
        }
        
        return true;
    } catch (Exception $e) {
        logMessage($e->getMessage(), 'error');
        return false;
    }
}
?>