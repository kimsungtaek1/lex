<?php
include '../../config.php';

$case_no = $_POST['case_no'];
$index = $_POST['index'];

try {
    $pdo->beginTransaction();
    
    // 부속서류 삭제
    $sql = "DELETE FROM application_recovery_creditor_appendix 
            WHERE case_no = ? AND creditor_count = ? AND appendix_no = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$case_no, $_POST['creditor_count'], $index]);
    
    $pdo->commit();
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
