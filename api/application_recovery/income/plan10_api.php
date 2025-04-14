<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php'; // 데이터베이스 설정 파일 경로 확인 필요

header('Content-Type: application/json');

// GET 또는 POST 요청에서 case_no 가져오기
$case_no = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $case_no = $_GET['case_no'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_no = $_POST['case_no'] ?? null;
}


if (!$case_no) {
    echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 데이터 조회 (GET 요청)
        $stmt = $pdo->prepare("SELECT title, content FROM application_recovery_plan10 WHERE case_no = :case_no");
        $stmt->execute([':case_no' => $case_no]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            // 데이터가 없을 경우 null 또는 빈 객체 반환 가능
            echo json_encode(['success' => true, 'data' => null]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 데이터 저장/수정 (POST 요청)
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        // 기존 데이터 확인
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM application_recovery_plan10 WHERE case_no = :case_no");
        $checkStmt->execute([':case_no' => $case_no]);
        $exists = $checkStmt->fetchColumn() > 0;

        $pdo->beginTransaction();

        if ($exists) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE application_recovery_plan10
                SET title = :title, content = :content
                WHERE case_no = :case_no
            ");
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO application_recovery_plan10 (case_no, title, content)
                VALUES (:case_no, :title, :content)
            ");
        }

        $stmt->bindParam(':case_no', $case_no, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);

        $stmt->execute();
        $pdo->commit();

        echo json_encode(['success' => true]);

    } else {
        // 지원하지 않는 메소드
        echo json_encode(['success' => false, 'message' => '지원하지 않는 요청 메소드입니다.']);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 오류 발생 시 더 자세한 정보 반환 (개발 중에만 사용 권장)
    http_response_code(500); // 서버 오류 상태 코드 설정
    echo json_encode([
        'success' => false,
        'message' => '처리 중 오류가 발생했습니다.',
        'error_message' => $e->getMessage(), // 실제 오류 메시지
        'error_trace' => $e->getTraceAsString() // 오류 추적 정보 (디버깅용)
    ]);
}

?>