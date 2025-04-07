<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? '';
    $dept_name = trim($_POST['dept_name'] ?? '');
    $manager_id = $_POST['manager_id'] ?: null;
    $use_yn = $_POST['use_yn'] ?? 'Y';

    // 필수 필드 검증
    if (empty($dept_name)) {
        throw new Exception('필수 항목이 누락되었습니다.');
    }

    // manager_id 유효성 검사
    if ($manager_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE employee_no = ? AND status = '재직'");
        $stmt->execute([$manager_id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('유효하지 않은 부서장입니다.');
        }
    }

    $pdo->beginTransaction();

    try {
        if ($id === 'new') {
            // 신규 등록
            $sql = "INSERT INTO employee_department (
                dept_name,
                manager_id,
                use_yn
            ) VALUES (?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $dept_name,
                $manager_id,
                $use_yn
            ]);

        } else {
            // 기존 데이터 수정
            $sql = "UPDATE employee_department SET 
                    dept_name = ?,
                    manager_id = ?,
                    use_yn = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE dept_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $dept_name,
                $manager_id,
                $use_yn,
                $id
            ]);
        }

        // 변경된 부서 정보를 employee 테이블에도 반영
        if ($use_yn === 'N') {
            $sql = "UPDATE employee SET 
                    department = '미지정'
                    WHERE department = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$dept_name]);
        }

        $pdo->commit();

        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => '저장되었습니다.'
            ]);
        } else {
            throw new Exception('데이터 저장에 실패했습니다.');
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.'
    ]);
}
?>