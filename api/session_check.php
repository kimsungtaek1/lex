<?php
function checkLogin() {
    session_start();
    
    if (!isset($_SESSION['employee_no'])) {
        header('Location: /adm/index.php');
        exit;
    }

    // 페이지 접근시마다 최종접속일 업데이트
    if (isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("UPDATE employee SET access_date = NOW() WHERE employee_no = ?");
            $stmt->execute([$_SESSION['employee_no']]);
			$stmt = $pdo->prepare("SELECT * FROM config");
			$stmt->execute([$case_no]);
        } catch (Exception $e) {
            error_log('Failed to update access_date: ' . $e->getMessage());
        }
    }
}

function checkNotLogin() {
    session_start();
    
    if (isset($_SESSION['employee_no'])) {
        header('Location: /adm/main.php');
        exit;
    }
}
?>