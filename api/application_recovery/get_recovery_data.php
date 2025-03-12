<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config.php';
session_start();

// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isset($_SESSION['employee_no'])) {
    writeLog("Unauthorized access attempt - No employee_no in session");
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// case_no 파라미터 체크
if (!isset($_GET['case_no'])) {
    writeLog("Missing case_no parameter in request");
    echo json_encode([
        'success' => false,
        'message' => '사건번호가 누락되었습니다.'
    ]);
    exit;
}

$case_no = intval($_GET['case_no']);
<<<<<<< HEAD
=======
$employee_no = $_SESSION['employee_no'];
>>>>>>> 719d7c8 (Delete all files)

try {
    // PDO 연결 확인
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established");
    }
<<<<<<< HEAD

    // 쿼리 준비
=======
    
    // 직원의 권한 확인
    $authQuery = "SELECT auth FROM employee WHERE employee_no = ?";
    $authStmt = $pdo->prepare($authQuery);
    $authStmt->execute([$employee_no]);
    $authData = $authStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$authData) {
        throw new Exception("직원 정보를 찾을 수 없습니다.");
    }
    
    $isAdmin = ($authData['auth'] == 10);
    
    // 쿼리 준비 - 관리자(auth=10)는 모든 사건을 볼 수 있고, 일반 직원은 자신의 사건만 볼 수 있도록 함
>>>>>>> 719d7c8 (Delete all files)
    $query = "
        SELECT 
            cm.case_no,
            cm.case_number,
            cm.court_name,
            cm.name,
            cm.phone,
            cm.category,
            cm.status as case_status,
            cm.created_at as case_created_at,
            ar.recovery_no,
            ar.resident_number,
            ar.registered_address,
            ar.now_address,
            ar.work_address,
            ar.workplace,
            ar.position,
            ar.phone as recovery_phone,
            ar.is_company,
            ar.debt_total,
            ar.income_monthly,
            ar.expense_monthly,
            ar.repayment_monthly,
            ar.assets_total,
            ar.memo,
            ar.application_date,
            ar.court_name as recovery_court,
            ar.case_year,
            ar.bank_name,
            ar.account_number,
            ar.status as recovery_status,
            ar.assigned_employee,
            ar.created_at as recovery_created_at,
<<<<<<< HEAD
			ar.work_period,
			ar.other_income,
			ar.other_income_name,
			ar.income_source,
			ar.unspecified_date,
			ar.repayment_start_date
        FROM case_management cm
        LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no
        WHERE cm.case_no = ? AND cm.paper = ?
    ";
=======
            ar.work_period,
            ar.other_income,
            ar.other_income_name,
            ar.income_source,
            ar.unspecified_date,
            ar.repayment_start_date
        FROM case_management cm
        LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no
        WHERE cm.case_no = ? ";
        
    if (!$isAdmin) {
        $query .= "AND cm.paper = ?";
    }
>>>>>>> 719d7c8 (Delete all files)

    $stmt = $pdo->prepare($query);

    // 쿼리 실행
<<<<<<< HEAD
    $result = $stmt->execute([$case_no, $_SESSION['employee_no']]);
=======
    if ($isAdmin) {
        $result = $stmt->execute([$case_no]);
    } else {
        $result = $stmt->execute([$case_no, $employee_no]);
    }
>>>>>>> 719d7c8 (Delete all files)
    
    if (!$result) {
        writeLog("Query execution failed: " . print_r($stmt->errorInfo(), true));
        throw new PDOException("Query execution failed");
    }

    // 데이터 가져오기
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        writeLog("No data found or access denied for case_no: {$case_no}");
        echo json_encode([
            'success' => false,
            'message' => '해당 사건을 찾을 수 없거나 접근 권한이 없습니다.'
        ]);
        exit;
    }

    // 데이터 후처리
    $data['phone'] = $data['recovery_phone'] ?: $data['phone'];
    $data['court_name'] = $data['recovery_court'] ?: $data['court_name'];

    // 날짜 형식 변환
    if ($data['application_date']) {
        $data['application_date'] = date('Y-m-d', strtotime($data['application_date']));
    }
    if ($data['case_created_at']) {
        $data['case_created_at'] = date('Y-m-d H:i:s', strtotime($data['case_created_at']));
    }
    if ($data['recovery_created_at']) {
        $data['recovery_created_at'] = date('Y-m-d H:i:s', strtotime($data['recovery_created_at']));
    }

    // 숫자 데이터 형식 변환
    $numberFields = ['debt_total', 'income_monthly', 'expense_monthly', 'repayment_monthly', 'assets_total'];
    foreach ($numberFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = (int)$data[$field];
        }
    }

    // 불필요한 필드 제거
    unset(
        $data['recovery_phone'],
        $data['recovery_court']
    );

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (PDOException $e) {
    writeLog("Database Error: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    writeLog("General Error: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
?>