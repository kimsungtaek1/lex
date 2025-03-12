<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['paper_no']) || !isset($_POST['field_name']) || !isset($_POST['value'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $paper_no = (int)$_POST['paper_no'];
    $field_name = $_POST['field_name'];
    $value = $_POST['value'];

    $pdo->beginTransaction();

	try {
		$pdo->beginTransaction();

		// consult_paper 업데이트
		$sql1 = "UPDATE consult_paper 
				SET {$field_name} = :value, 
					updated_at = CURRENT_TIMESTAMP 
				WHERE paper_no = :paper_no";
		
		$stmt1 = $pdo->prepare($sql1);
		$stmt1->execute([
			':value' => $value,
			':paper_no' => $paper_no
		]);

		// case_management 업데이트
		$sql2 = "UPDATE case_management 
				SET {$field_name} = :value,
					status = CASE 
						WHEN :field_name = 'accept_date' THEN '접수'
						WHEN :field_name = 'start_date' THEN '개시'
						WHEN :field_name = 'approval_date' THEN '인가'
						ELSE status 
					END,
					updated_at = CURRENT_TIMESTAMP 
				WHERE paper_no = :paper_no";

		$stmt2 = $pdo->prepare($sql2);
		$stmt2->execute([
			':value' => $value,
			':paper_no' => $paper_no,
			':field_name' => $field_name
		]);

		$pdo->commit();
		
	} catch (Exception $e) {
		$pdo->rollBack();
		throw $e;
	}

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['paper_no']) || !isset($_POST['field_name']) || !isset($_POST['value'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $paper_no = (int)$_POST['paper_no'];
    $field_name = $_POST['field_name'];
    $value = $_POST['value'];

    $pdo->beginTransaction();

	try {
		$pdo->beginTransaction();

		// consult_paper 업데이트
		$sql1 = "UPDATE consult_paper 
				SET {$field_name} = :value, 
					updated_at = CURRENT_TIMESTAMP 
				WHERE paper_no = :paper_no";
		
		$stmt1 = $pdo->prepare($sql1);
		$stmt1->execute([
			':value' => $value,
			':paper_no' => $paper_no
		]);

		// case_management 업데이트
		$sql2 = "UPDATE case_management 
				SET {$field_name} = :value,
					status = CASE 
						WHEN :field_name = 'accept_date' THEN '접수'
						WHEN :field_name = 'start_date' THEN '개시'
						WHEN :field_name = 'approval_date' THEN '인가'
						ELSE status 
					END,
					updated_at = CURRENT_TIMESTAMP 
				WHERE paper_no = :paper_no";

		$stmt2 = $pdo->prepare($sql2);
		$stmt2->execute([
			':value' => $value,
			':paper_no' => $paper_no,
			':field_name' => $field_name
		]);

		$pdo->commit();
		
	} catch (Exception $e) {
		$pdo->rollBack();
		throw $e;
	}

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}