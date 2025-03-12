<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['case_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $pdo->beginTransaction();

    try {
        $case_no = (int)$_POST['case_no'];

        // 1. 존재하는지 확인
        $stmt = $pdo->prepare("SELECT case_no FROM case_management WHERE case_no = ?");
        $stmt->execute([$case_no]);
        if (!$stmt->fetch()) {
            throw new Exception('존재하지 않는 사건입니다.');
        }

        // 2. application_recovery 삭제
        $stmt = $pdo->prepare("DELETE FROM application_recovery WHERE case_no = ?");
        $stmt->execute([$case_no]);

        // 3. case_management_content 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE case_no = ?");
        $stmt->execute([$case_no]);

        // 4. case_management 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management WHERE case_no = ?");
        $result = $stmt->execute([$case_no]);

        if (!$result) {
            throw new Exception('삭제 실패');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error in delete_case_management.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['case_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $pdo->beginTransaction();

    try {
        $case_no = (int)$_POST['case_no'];

        // 1. 존재하는지 확인
        $stmt = $pdo->prepare("SELECT case_no FROM case_management WHERE case_no = ?");
        $stmt->execute([$case_no]);
        if (!$stmt->fetch()) {
            throw new Exception('존재하지 않는 사건입니다.');
        }

        // 2. application_recovery 삭제
        $stmt = $pdo->prepare("DELETE FROM application_recovery WHERE case_no = ?");
        $stmt->execute([$case_no]);

        // 3. case_management_content 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE case_no = ?");
        $stmt->execute([$case_no]);

        // 4. case_management 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management WHERE case_no = ?");
        $result = $stmt->execute([$case_no]);

        if (!$result) {
            throw new Exception('삭제 실패');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '삭제되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error in delete_case_management.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}