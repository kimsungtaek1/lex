<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 한국 시간대 설정
    date_default_timezone_set('Asia/Seoul');

    // datetime 형식 변환 (한국 시간)
    $datetime = !empty($_POST['datetime']) 
        ? date('Y-m-d H:i:s', strtotime($_POST['datetime']))
        : date('Y-m-d H:i:s');

    // birth_date 처리
    $birth_date = !empty($_POST['birth_date']) ? date('Y-m-d', strtotime($_POST['birth_date'])) : null;
    
    $sql = "INSERT INTO inflow (
        datetime, inflow_page, inflow, category, phone, name, 
        manager, birth_date, debt_amount, region, 
        consultation_time, content, status, ip, user_agent, device_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $datetime,
        '관리자',
        '관리자' ?? '',
        $_POST['category'],
        $_POST['phone'],
        $_POST['name'],
        $_POST['manager'] ?: null,
        $birth_date,
        $_POST['debt_amount'] ?? '',
        $_POST['region'] ?? '',
        $_POST['consultation_time'] ?? '',
        $_POST['content'] ?? '',
        '신규',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        'desktop'
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'DB가 추가되었습니다.'
        ]);
    } else {
        error_log("PDO Error: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to add inflow');
    }
    
} catch(Exception $e) {
    error_log('Error in add_inflow.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 한국 시간대 설정
    date_default_timezone_set('Asia/Seoul');

    // datetime 형식 변환 (한국 시간)
    $datetime = !empty($_POST['datetime']) 
        ? date('Y-m-d H:i:s', strtotime($_POST['datetime']))
        : date('Y-m-d H:i:s');

    // birth_date 처리
    $birth_date = !empty($_POST['birth_date']) ? date('Y-m-d', strtotime($_POST['birth_date'])) : null;
    
    $sql = "INSERT INTO inflow (
        datetime, inflow_page, inflow, category, phone, name, 
        manager, birth_date, debt_amount, region, 
        consultation_time, content, status, ip, user_agent, device_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $datetime,
        '관리자',
        '관리자' ?? '',
        $_POST['category'],
        $_POST['phone'],
        $_POST['name'],
        $_POST['manager'] ?: null,
        $birth_date,
        $_POST['debt_amount'] ?? '',
        $_POST['region'] ?? '',
        $_POST['consultation_time'] ?? '',
        $_POST['content'] ?? '',
        '신규',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        'desktop'
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'DB가 추가되었습니다.'
        ]);
    } else {
        error_log("PDO Error: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to add inflow');
    }
    
} catch(Exception $e) {
    error_log('Error in add_inflow.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}