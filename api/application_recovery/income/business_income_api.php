<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
   http_response_code(401);
   echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
   exit;
}

require_once '../../../config.php';

class BusinessIncomeApi {
   private $pdo;

   public function __construct($pdo) {
       $this->pdo = $pdo;
   }

   public function save($data) {
       try {
           // 필수 필드 검증
           if (!isset($data['case_no']) || empty($data['case_no'])) {
               return ['success' => false, 'message' => '사건번호가 필요합니다.'];
           }

           // 기타 유형일 경우 상세 내용 필수
           if ($data['type'] === '기타(임의입력)' && empty($data['type_etc'])) {
               return ['success' => false, 'message' => '기타 수입의 상세 내용을 입력해주세요.'];
           }

           // 이미 존재하는 데이터 확인
           $stmt = $this->pdo->prepare("SELECT business_no FROM application_recovery_income_business WHERE case_no = :case_no");
           $stmt->execute(['case_no' => $data['case_no']]);
           $existingData = $stmt->fetch(PDO::FETCH_ASSOC);

           // 데이터 준비
           $params = [
               'case_no' => $data['case_no'],
               'type' => $data['type'] ?? null,
               'type_etc' => $data['type'] === '기타(임의입력)' ? $data['type_etc'] : null,
               'monthly_income' => $data['monthly_income'] ?? 0,
               'yearly_income' => $data['yearly_income'] ?? 0,
               'business_name' => $data['business_name'] ?? null,
               'sector' => $data['sector'] ?? null,
               'career' => $data['career'] ?? null
           ];

           if ($existingData) {
               // UPDATE
               $sql = "UPDATE application_recovery_income_business SET 
                       type = :type,
                       type_etc = :type_etc,
                       monthly_income = :monthly_income,
                       yearly_income = :yearly_income,
                       business_name = :business_name,
                       sector = :sector,
                       career = :career,
                       updated_at = CURRENT_TIMESTAMP
                       WHERE case_no = :case_no";
               
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute($params);

               return [
                   'success' => true, 
                   'message' => '사업소득 정보가 수정되었습니다.',
                   'data' => $params
               ];
           } else {
               // INSERT
               $sql = "INSERT INTO application_recovery_income_business 
                       (case_no, type, type_etc, monthly_income, yearly_income, business_name, sector, career)
                       VALUES 
                       (:case_no, :type, :type_etc, :monthly_income, :yearly_income, :business_name, :sector, :career)";
               
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute($params);

               $params['business_no'] = $this->pdo->lastInsertId();

               return [
                   'success' => true, 
                   'message' => '사업소득 정보가 저장되었습니다.',
                   'data' => $params
               ];
           }

       } catch (PDOException $e) {
           error_log("SQL Error: " . $e->getMessage());
           return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
       } catch (Exception $e) {
           error_log("General Error: " . $e->getMessage());
           return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
       }
   }

   public function get($caseNo) {
       try {
           $sql = "SELECT * FROM application_recovery_income_business WHERE case_no = :case_no";
           $stmt = $this->pdo->prepare($sql);
           $stmt->execute(['case_no' => $caseNo]);
           
           $data = $stmt->fetch(PDO::FETCH_ASSOC);
           
           return ['success' => true, 'data' => $data];
       } catch (PDOException $e) {
           error_log("SQL Error: " . $e->getMessage());
           return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
       }
   }

   public function delete($caseNo) {
       try {
           $sql = "DELETE FROM application_recovery_income_business WHERE case_no = :case_no";
           $stmt = $this->pdo->prepare($sql);
           $stmt->execute(['case_no' => $caseNo]);
           
           if ($stmt->rowCount() > 0) {
               return ['success' => true, 'message' => '사업소득 정보가 삭제되었습니다.'];
           } else {
               return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
           }
       } catch (PDOException $e) {
           error_log("SQL Error: " . $e->getMessage());
           return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
       }
   }
}

// API 요청 처리
$api = new BusinessIncomeApi($pdo);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
   case 'GET':
       $caseNo = $_GET['case_no'] ?? null;
       if (!$caseNo) {
           echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
           exit;
       }
       $result = $api->get($caseNo);
       break;

   case 'POST':
       $result = $api->save($_POST);
       break;

   case 'DELETE':
       parse_str(file_get_contents("php://input"), $deleteData);
       $caseNo = $deleteData['case_no'] ?? null;
       if (!$caseNo) {
           echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
           exit;
       }
       $result = $api->delete($caseNo);
       break;

   default:
       http_response_code(405);
       $result = ['success' => false, 'message' => '지원하지 않는 메소드입니다.'];
       break;
}

echo json_encode($result);
?>