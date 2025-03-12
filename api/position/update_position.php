<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? '';
    $position_name = trim($_POST['position_name'] ?? '');
    $position_order = $_POST['position_order'] ?? '';
    $use_yn = $_POST['use_yn'] ?? 'Y';

    // 필수 필드 체크
    if (empty($position_name)) {
        throw new Exception('필수 항목이 누락되었습니다.');
    }

    // position_order 값이 숫자인지 확인
    if (!is_numeric($position_order)) {
        throw new Exception('순서는 숫자만 입력 가능합니다.');
    }

    if ($id === 'new') {
        // 신규 등록
        $sql = "INSERT INTO employee_position (
            position_name, 
            position_order, 
            use_yn
        ) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $position_name,
            $position_order,
            $use_yn
        ]);
    } else {
        // 기존 데이터 수정
        $sql = "UPDATE employee_position SET 
                position_name = ?,
                position_order = ?,
                use_yn = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE position_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $position_name,
            $position_order,
            $use_yn,
            $id
        ]);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
    } else {
        throw new Exception('데이터 저장에 실패했습니다.');
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
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? '';
    $position_name = trim($_POST['position_name'] ?? '');
    $position_order = $_POST['position_order'] ?? '';
    $use_yn = $_POST['use_yn'] ?? 'Y';

    // 필수 필드 체크
    if (empty($position_name)) {
        throw new Exception('필수 항목이 누락되었습니다.');
    }

    // position_order 값이 숫자인지 확인
    if (!is_numeric($position_order)) {
        throw new Exception('순서는 숫자만 입력 가능합니다.');
    }

    if ($id === 'new') {
        // 신규 등록
        $sql = "INSERT INTO employee_position (
            position_name, 
            position_order, 
            use_yn
        ) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $position_name,
            $position_order,
            $use_yn
        ]);
    } else {
        // 기존 데이터 수정
        $sql = "UPDATE employee_position SET 
                position_name = ?,
                position_order = ?,
                use_yn = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE position_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $position_name,
            $position_order,
            $use_yn,
            $id
        ]);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
    } else {
        throw new Exception('데이터 저장에 실패했습니다.');
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
>>>>>>> 719d7c8 (Delete all files)
?>