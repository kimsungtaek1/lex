<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    if (!isset($_POST['consult_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $pdo->beginTransaction();

    try {
        $consult_no = (int)$_POST['consult_no'];

        // 1. 존재하는지 확인
        $stmt = $pdo->prepare("SELECT consult_no FROM consult_manager WHERE consult_no = ?");
        $stmt->execute([$consult_no]);
        if (!$stmt->fetch()) {
            throw new Exception('존재하지 않는 상담입니다.');
        }

        // 2. consult_paper 관련 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_paper_content WHERE paper_no IN (SELECT paper_no FROM consult_paper WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM consult_paper WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 3. case_management 관련 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE case_no IN (SELECT case_no FROM case_management WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM application_recovery WHERE case_no IN (SELECT case_no FROM case_management WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM case_management WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 4. consult_manager_content 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_manager_content WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 5. consult_manager 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_manager WHERE consult_no = ?");
        $result = $stmt->execute([$consult_no]);

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
    error_log('Error in delete_consult_manager.php: ' . $e->getMessage());
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

    if (!isset($_POST['consult_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $pdo->beginTransaction();

    try {
        $consult_no = (int)$_POST['consult_no'];

        // 1. 존재하는지 확인
        $stmt = $pdo->prepare("SELECT consult_no FROM consult_manager WHERE consult_no = ?");
        $stmt->execute([$consult_no]);
        if (!$stmt->fetch()) {
            throw new Exception('존재하지 않는 상담입니다.');
        }

        // 2. consult_paper 관련 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_paper_content WHERE paper_no IN (SELECT paper_no FROM consult_paper WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM consult_paper WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 3. case_management 관련 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM case_management_content WHERE case_no IN (SELECT case_no FROM case_management WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM application_recovery WHERE case_no IN (SELECT case_no FROM case_management WHERE consult_no = ?)");
        $stmt->execute([$consult_no]);

        $stmt = $pdo->prepare("DELETE FROM case_management WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 4. consult_manager_content 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_manager_content WHERE consult_no = ?");
        $stmt->execute([$consult_no]);

        // 5. consult_manager 삭제
        $stmt = $pdo->prepare("DELETE FROM consult_manager WHERE consult_no = ?");
        $result = $stmt->execute([$consult_no]);

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
    error_log('Error in delete_consult_manager.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}