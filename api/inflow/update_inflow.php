<<<<<<< HEAD
<?php
require_once '../../config.php';
require_once '../sms/component.php';
header('Content-Type: application/json');

try {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       throw new Exception('잘못된 요청 방식입니다.');
   }

   // 필수 필드 검증
   if (empty($_POST['no'])) {
       throw new Exception('DB 번호가 누락되었습니다.');
   }

   // 기존 데이터 조회
   $stmt = $pdo->prepare("SELECT manager, name, content, phone, category, datetime, region, debt_amount, birth_date, consultation_time FROM inflow WHERE no = ?");
   $stmt->execute([$_POST['no']]);
   $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
   
   // 기존 값 세팅 
   $old_manager = trim($current_data['manager'] ?? '');
   $old_name = $current_data['name'];

   $pdo->beginTransaction();

   try {
       // Inflow 테이블 업데이트
       $sql = "UPDATE inflow SET 
               name = :name,
               phone = :phone,
               category = :category,
               datetime = :datetime,
               region = :region,
               birth_date = :birth_date,
               debt_amount = :debt_amount,
               consultation_time = :consultation_time,
               content = :content,
               inflow_page = :inflow_page,
               inflow = :inflow,
               manager = :manager, 
               updated_at = CURRENT_TIMESTAMP
               WHERE no = :no";
       
       // 바인딩할 데이터 준비
       $params = [
           ':name' => $_POST['name'] ?? null,
           ':phone' => $_POST['phone'] ?? null,
           ':category' => $_POST['category'] ?? null,
           ':datetime' => $_POST['datetime'] ?? null,
           ':region' => $_POST['region'] ?? null,
           ':birth_date' => !empty($_POST['birth_date']) ? date('Y-m-d', strtotime($_POST['birth_date'])) : null,
           ':debt_amount' => $_POST['debt_amount'] ?? null,
           ':consultation_time' => $_POST['consultation_time'] ?? null,
           ':content' => $_POST['content'] ?? null,
           ':inflow_page' => $_POST['inflow_page'] ?? null,
           ':inflow' => $_POST['inflow'] ?? null,
           ':manager' => !empty($_POST['manager']) ? trim($_POST['manager']) : null,
           ':no' => $_POST['no']
       ];

       $stmt = $pdo->prepare($sql);
       $result = $stmt->execute($params);

       // consult_manager 테이블 업데이트 또는 삽입
       // 먼저 해당 inflow_no로 consult_manager 테이블에 데이터가 있는지 확인
       $stmt = $pdo->prepare("SELECT consult_no FROM consult_manager WHERE inflow_no = ?");
       $stmt->execute([$_POST['no']]);
       $existingConsult = $stmt->fetch();

       if ($existingConsult) {
           // 기존 데이터가 있으면 업데이트
           $consultSql = "UPDATE consult_manager SET 
                         datetime = :datetime,
                         category = :category,
                         phone = :phone,
                         name = :name,
                         consultant = :consultant,
                         birth_date = :birth_date,
                         debt_amount = :debt_amount,
                         region = :region,
                         consultation_time = :consultation_time,
                         content = :content
                         WHERE inflow_no = :inflow_no";
       } else {
           // 없으면 새로 삽입
           $consultSql = "INSERT INTO consult_manager (
                         datetime, category, phone, name, consultant, 
                         birth_date, debt_amount, region, consultation_time, 
                         content, inflow_no
                         ) VALUES (
                         :datetime, :category, :phone, :name, :consultant,
                         :birth_date, :debt_amount, :region, :consultation_time,
                         :content, :inflow_no
                         )";
       }

       $consultParams = [
           ':datetime' => $params[':datetime'],
           ':category' => $params[':category'],
           ':phone' => $params[':phone'],
           ':name' => $params[':name'],
           ':consultant' => $params[':manager'],
           ':birth_date' => $params[':birth_date'],
           ':debt_amount' => $params[':debt_amount'],
           ':region' => $params[':region'],
           ':consultation_time' => $params[':consultation_time'],
           ':content' => $params[':content'],
           ':inflow_no' => $_POST['no']
       ];

       $stmt = $pdo->prepare($consultSql);
       $stmt->execute($consultParams);

       // manager 값 정리 및 실제 변경 여부 확인
       $new_manager = trim($params[':manager'] ?? ''); 
       $manager_changed = ($old_manager !== $new_manager) && !empty($new_manager);

       // manager가 실제로 변경되었을 때만 SMS 발송
       if ($result && $manager_changed) {
           // 담당자 정보 조회
           $stmt = $pdo->prepare("SELECT name, phone FROM employee WHERE employee_no = ?");
           $stmt->execute([$new_manager]);
           $manager_info = $stmt->fetch(PDO::FETCH_ASSOC);

           if ($manager_info) {
               // SMS 발송 준비
               $sms = new SMS();
               $sms->SMS_con($socket_host, $socket_port, $icode_key);

               // 실제 사용될 이름 결정 (업데이트된 이름 또는 기존 이름)
               $client_name = !empty($params[':name']) ? $params[':name'] : $old_name;
               $sms_content = "{$client_name}님의 상담이 {$manager_info['name']}님께 배정되었습니다.";
               $phone_number = str_replace('-', '', $manager_info['phone']);

               $result = $sms->Add([$phone_number], $icode_number, $sms_content, '');

               if ($result) {
                   $send_result = $sms->Send();
                   if ($send_result) {
                       // SMS 발송 상태 업데이트
                       $stmt = $pdo->prepare("UPDATE inflow SET sms_sent = 1 WHERE no = ?");
                       $stmt->execute([$params[':no']]);
                   } else {
                       error_log("SMS 발송 실패: " . print_r($sms->Result, true));
                   }
               } else {
                   error_log("SMS 추가 실패: " . print_r($sms->Result, true));
               }
           }
       }

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
   error_log('Error: ' . $e->getMessage());
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
=======
<?php
require_once '../../config.php';
require_once '../sms/component.php';
header('Content-Type: application/json');

try {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       throw new Exception('잘못된 요청 방식입니다.');
   }

   // 필수 필드 검증
   if (empty($_POST['no'])) {
       throw new Exception('DB 번호가 누락되었습니다.');
   }

   // 기존 데이터 조회
   $stmt = $pdo->prepare("SELECT manager, name, content, phone, category, datetime, region, debt_amount, birth_date, consultation_time FROM inflow WHERE no = ?");
   $stmt->execute([$_POST['no']]);
   $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
   
   // 기존 값 세팅 
   $old_manager = trim($current_data['manager'] ?? '');
   $old_name = $current_data['name'];

   $pdo->beginTransaction();

   try {
       // Inflow 테이블 업데이트
       $sql = "UPDATE inflow SET 
               name = :name,
               phone = :phone,
               category = :category,
               datetime = :datetime,
               region = :region,
               birth_date = :birth_date,
               debt_amount = :debt_amount,
               consultation_time = :consultation_time,
               content = :content,
               inflow_page = :inflow_page,
               inflow = :inflow,
               manager = :manager, 
               updated_at = CURRENT_TIMESTAMP
               WHERE no = :no";
       
       // 바인딩할 데이터 준비
       $params = [
           ':name' => $_POST['name'] ?? null,
           ':phone' => $_POST['phone'] ?? null,
           ':category' => $_POST['category'] ?? null,
           ':datetime' => $_POST['datetime'] ?? null,
           ':region' => $_POST['region'] ?? null,
           ':birth_date' => !empty($_POST['birth_date']) ? date('Y-m-d', strtotime($_POST['birth_date'])) : null,
           ':debt_amount' => $_POST['debt_amount'] ?? null,
           ':consultation_time' => $_POST['consultation_time'] ?? null,
           ':content' => $_POST['content'] ?? null,
           ':inflow_page' => $_POST['inflow_page'] ?? null,
           ':inflow' => $_POST['inflow'] ?? null,
           ':manager' => !empty($_POST['manager']) ? trim($_POST['manager']) : null,
           ':no' => $_POST['no']
       ];

       $stmt = $pdo->prepare($sql);
       $result = $stmt->execute($params);

       // consult_manager 테이블 업데이트 또는 삽입
       // 먼저 해당 inflow_no로 consult_manager 테이블에 데이터가 있는지 확인
       $stmt = $pdo->prepare("SELECT consult_no FROM consult_manager WHERE inflow_no = ?");
       $stmt->execute([$_POST['no']]);
       $existingConsult = $stmt->fetch();

       if ($existingConsult) {
           // 기존 데이터가 있으면 업데이트
           $consultSql = "UPDATE consult_manager SET 
                         datetime = :datetime,
                         category = :category,
                         phone = :phone,
                         name = :name,
                         consultant = :consultant,
                         birth_date = :birth_date,
                         debt_amount = :debt_amount,
                         region = :region,
                         consultation_time = :consultation_time,
                         content = :content
                         WHERE inflow_no = :inflow_no";
       } else {
           // 없으면 새로 삽입
           $consultSql = "INSERT INTO consult_manager (
                         datetime, category, phone, name, consultant, 
                         birth_date, debt_amount, region, consultation_time, 
                         content, inflow_no
                         ) VALUES (
                         :datetime, :category, :phone, :name, :consultant,
                         :birth_date, :debt_amount, :region, :consultation_time,
                         :content, :inflow_no
                         )";
       }

       $consultParams = [
           ':datetime' => $params[':datetime'],
           ':category' => $params[':category'],
           ':phone' => $params[':phone'],
           ':name' => $params[':name'],
           ':consultant' => $params[':manager'],
           ':birth_date' => $params[':birth_date'],
           ':debt_amount' => $params[':debt_amount'],
           ':region' => $params[':region'],
           ':consultation_time' => $params[':consultation_time'],
           ':content' => $params[':content'],
           ':inflow_no' => $_POST['no']
       ];

       $stmt = $pdo->prepare($consultSql);
       $stmt->execute($consultParams);

       // manager 값 정리 및 실제 변경 여부 확인
       $new_manager = trim($params[':manager'] ?? ''); 
       $manager_changed = ($old_manager !== $new_manager) && !empty($new_manager);

       // manager가 실제로 변경되었을 때만 SMS 발송
       if ($result && $manager_changed) {
           // 담당자 정보 조회
           $stmt = $pdo->prepare("SELECT name, phone FROM employee WHERE employee_no = ?");
           $stmt->execute([$new_manager]);
           $manager_info = $stmt->fetch(PDO::FETCH_ASSOC);

           if ($manager_info) {
               // SMS 발송 준비
               $sms = new SMS();
               $sms->SMS_con($socket_host, $socket_port, $icode_key);

               // 실제 사용될 이름 결정 (업데이트된 이름 또는 기존 이름)
               $client_name = !empty($params[':name']) ? $params[':name'] : $old_name;
               $sms_content = "{$client_name}님의 상담이 {$manager_info['name']}님께 배정되었습니다.";
               $phone_number = str_replace('-', '', $manager_info['phone']);

               $result = $sms->Add([$phone_number], $icode_number, $sms_content, '');

               if ($result) {
                   $send_result = $sms->Send();
                   if ($send_result) {
                       // SMS 발송 상태 업데이트
                       $stmt = $pdo->prepare("UPDATE inflow SET sms_sent = 1 WHERE no = ?");
                       $stmt->execute([$params[':no']]);
                   } else {
                       error_log("SMS 발송 실패: " . print_r($sms->Result, true));
                   }
               } else {
                   error_log("SMS 추가 실패: " . print_r($sms->Result, true));
               }
           }
       }

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
   error_log('Error: ' . $e->getMessage());
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
>>>>>>> 719d7c8 (Delete all files)
}