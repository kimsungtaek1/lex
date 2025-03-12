<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // 필수 파라미터 체크
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        throw new Exception('아이디와 비밀번호를 입력해주세요.');
    }

    // 회원정보 조회
    $stmt = $pdo->prepare("SELECT employee_no, employee_id, name, password, auth, status FROM employee WHERE employee_id = ?");
    $stmt->execute([$username]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('아이디 또는 비밀번호가 일치하지 않습니다.');
    }

    if ($employee['status'] !== '재직') {
        throw new Exception('퇴사 또는 휴직중인 계정입니다.');
    }

    // 비밀번호 검증
    if (!password_verify($password, $employee['password'])) {
        throw new Exception('아이디 또는 비밀번호가 일치하지 않습니다.');
    }

    // 세션 시작 및 정보 저장
    session_start();
    $_SESSION['employee_no'] = $employee['employee_no'];
    $_SESSION['employee_id'] = $employee['employee_id'];
    $_SESSION['name'] = $employee['name'];
    $_SESSION['auth'] = $employee['auth'];

    // 최종접속일 업데이트
	$updateStmt = $pdo->prepare("
		UPDATE employee 
		SET access_date = DATE_FORMAT(CURDATE(), '%Y-%m-%d') 
		WHERE employee_no = ?
	");
    $updateStmt->execute([$employee['employee_no']]);

    echo json_encode([
        'success' => true, 
        'redirect' => '/adm/main.php'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>