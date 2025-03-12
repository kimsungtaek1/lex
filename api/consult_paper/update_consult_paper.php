<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 파라미터 확인
    $required_fields = ['paper_no', 'name', 'phone', 'category'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("필수 입력값이 누락되었습니다: {$field}");
        }
    }

    $paper_no = (int)$_POST['paper_no'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $case_number = trim($_POST['case_number'] ?? '');
    $category = trim($_POST['category']);
    $assign_date = !empty($_POST['assign_date']) ? trim($_POST['assign_date']) : null;
    $start_date = !empty($_POST['start_date']) ? trim($_POST['start_date']) : null;
    $accept_date = !empty($_POST['accept_date']) ? trim($_POST['accept_date']) : null;
    $approval_date = !empty($_POST['approval_date']) ? trim($_POST['approval_date']) : null;
    $status = trim($_POST['status'] ?? '접수');
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

    // 전화번호 형식 검증
    if (!preg_match('/^01[016789]-\d{3,4}-\d{4}$/', $phone)) {
        throw new Exception('올바른 전화번호 형식이 아닙니다.');
    }

    // 날짜 형식 검증
    $date_fields = [
        'assign_date' => $assign_date,
        'start_date' => $start_date,
        'accept_date' => $accept_date,
        'approval_date' => $approval_date
    ];

    foreach ($date_fields as $field_name => $date) {
        if ($date !== null) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception("올바른 날짜 형식이 아닙니다: {$field_name}");
            }
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                throw new Exception("유효하지 않은 날짜입니다: {$field_name}");
            }
        }
    }

    // manager_id가 유효한 직원번호인지 확인
    if ($manager_id !== null) {
        $stmt = $pdo->prepare("SELECT employee_no FROM employee WHERE employee_no = ? AND status = '재직'");
        $stmt->execute([$manager_id]);
        if (!$stmt->fetch()) {
            throw new Exception('유효하지 않은 직원번호입니다.');
        }
    }

    $pdo->beginTransaction();

    try {
        // consult_paper 업데이트
        $sql = "UPDATE consult_paper SET
            name = :name,
            phone = :phone,
            case_number = :case_number,
            category = :category,
            assign_date = :assign_date,
            start_date = :start_date,
            accept_date = :accept_date,
            approval_date = :approval_date,
            status = :status,
            manager_id = :manager_id,
            updated_at = CURRENT_TIMESTAMP
            WHERE paper_no = :paper_no";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':paper_no' => $paper_no,
            ':name' => $name,
            ':phone' => $phone,
            ':case_number' => $case_number,
            ':category' => $category,
            ':assign_date' => $assign_date,
            ':start_date' => $start_date,
            ':accept_date' => $accept_date,
            ':approval_date' => $approval_date,
            ':status' => $status,
            ':manager_id' => $manager_id
        ]);

        if (!$result) {
            throw new Exception('데이터 저장에 실패했습니다.');
        }

        // case_management 업데이트 (paper_no로 연결된 케이스가 있는 경우)
        $sql2 = "UPDATE case_management SET
            name = :name,
            phone = :phone,
            case_number = :case_number,
            category = :category,
            assign_date = :assign_date,
            start_date = :start_date,
            accept_date = :accept_date,
            approval_date = :approval_date,
            status = :status,
            paper = :manager_id,
            updated_at = CURRENT_TIMESTAMP
            WHERE paper_no = :paper_no";

        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':paper_no' => $paper_no,
            ':name' => $name,
            ':phone' => $phone,
            ':case_number' => $case_number,
            ':category' => $category,
            ':assign_date' => $assign_date,
            ':start_date' => $start_date,
            ':accept_date' => $accept_date,
            ':approval_date' => $approval_date,
            ':status' => $status,
            ':manager_id' => $manager_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '저장되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error in update_consult_paper.php: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}