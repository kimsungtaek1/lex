<<<<<<< HEAD
<?php
//DB
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');
$charset = 'utf8mb4';
define('CLAUDE_API_KEY', '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');
define('OCR_SPACE_API_KEY', 'K89247739088957');
try {
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

function writeLog($message) {
    $logFile = __DIR__ . '/logs/' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);

    // 로그 디렉토리가 없으면 생성
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // 로그 메시지 포맷팅
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] {$message}\n";
    
    // 파일에 로그 작성
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

//문자
$socket_host	= "211.172.232.124"; //아이코드의 고정서버 IP. 변경하면 안됨.
$socket_port	= 9201; //상황에 따라 달라질 수 있다.


// 매우중요 !! https://www.icodekorea.com/?ctl=user_token를 고객사 서버IP를 최근접속정보의 IP로 반드시 바꿔야 문자발송이 된다.
// config 테이블에서 icode_key 값을 가져옴
$stmt = $pdo->prepare("SELECT * FROM config WHERE no = 1");
$stmt->execute();
$result = $stmt->fetch();
$icode_key = $result['icode_key'];
$icode_id = $result['customer_id'];
$icode_number = $result['customer_number'];
=======
<?php
//DB
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');
$charset = 'utf8mb4';
define('CLAUDE_API_KEY', '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');
define('OCR_SPACE_API_KEY', 'K89247739088957');
try {
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

function writeLog($message) {
    $logFile = __DIR__ . '/logs/' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);

    // 로그 디렉토리가 없으면 생성
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // 로그 메시지 포맷팅
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] {$message}\n";
    
    // 파일에 로그 작성
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

//문자
$socket_host	= "211.172.232.124"; //아이코드의 고정서버 IP. 변경하면 안됨.
$socket_port	= 9201; //상황에 따라 달라질 수 있다.


// 매우중요 !! https://www.icodekorea.com/?ctl=user_token를 고객사 서버IP를 최근접속정보의 IP로 반드시 바꿔야 문자발송이 된다.
// config 테이블에서 icode_key 값을 가져옴
$stmt = $pdo->prepare("SELECT * FROM config WHERE no = 1");
$stmt->execute();
$result = $stmt->fetch();
$icode_key = $result['icode_key'];
$icode_id = $result['customer_id'];
$icode_number = $result['customer_number'];
>>>>>>> 719d7c8 (Delete all files)
?>