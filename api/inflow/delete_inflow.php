<<<<<<< HEAD
<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       throw new Exception('잘못된 요청 방식입니다.');
   }

   // no 파라미터 검증
   if (empty($_POST['no'])) {
       throw new Exception('DB 번호가 누락되었습니다.');
   }

   // 정수형으로 변환하여 검증
   $no = (int)$_POST['no'];
   if ($no <= 0) {
       throw new Exception('유효하지 않은 DB 번호입니다.');
   }

   // SQL 쿼리 준비
   $sql = "DELETE FROM inflow WHERE no = ?";
   
   // 디버깅
   error_log("Deleting inflow with no: " . $no);
   
   $stmt = $pdo->prepare($sql);
   $result = $stmt->execute([$no]);

   if ($result) {
       if ($stmt->rowCount() > 0) {
           echo json_encode([
               'success' => true,
               'message' => '삭제되었습니다.'
           ]);
       } else {
           throw new Exception('해당하는 DB를 찾을 수 없습니다.');
       }
   } else {
       error_log("PDO Error: " . print_r($stmt->errorInfo(), true));
       throw new Exception('DB 삭제에 실패했습니다.');
   }
   
} catch(Exception $e) {
   error_log('Error in delete_inflow.php: ' . $e->getMessage());
   http_response_code(400);
   echo json_encode([
       'success' => false,
       'message' => $e->getMessage()
   ]);
} catch(PDOException $e) {
   error_log('PDO Error: ' . $e->getMessage());
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
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       throw new Exception('잘못된 요청 방식입니다.');
   }

   // no 파라미터 검증
   if (empty($_POST['no'])) {
       throw new Exception('DB 번호가 누락되었습니다.');
   }

   // 정수형으로 변환하여 검증
   $no = (int)$_POST['no'];
   if ($no <= 0) {
       throw new Exception('유효하지 않은 DB 번호입니다.');
   }

   // SQL 쿼리 준비
   $sql = "DELETE FROM inflow WHERE no = ?";
   
   // 디버깅
   error_log("Deleting inflow with no: " . $no);
   
   $stmt = $pdo->prepare($sql);
   $result = $stmt->execute([$no]);

   if ($result) {
       if ($stmt->rowCount() > 0) {
           echo json_encode([
               'success' => true,
               'message' => '삭제되었습니다.'
           ]);
       } else {
           throw new Exception('해당하는 DB를 찾을 수 없습니다.');
       }
   } else {
       error_log("PDO Error: " . print_r($stmt->errorInfo(), true));
       throw new Exception('DB 삭제에 실패했습니다.');
   }
   
} catch(Exception $e) {
   error_log('Error in delete_inflow.php: ' . $e->getMessage());
   http_response_code(400);
   echo json_encode([
       'success' => false,
       'message' => $e->getMessage()
   ]);
} catch(PDOException $e) {
   error_log('PDO Error: ' . $e->getMessage());
   http_response_code(500);
   echo json_encode([
       'success' => false,
       'message' => '데이터베이스 오류가 발생했습니다.'
   ]);
}
>>>>>>> 719d7c8 (Delete all files)
?>