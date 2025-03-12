<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php';

class FamilyMemberApi {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function get($caseNo) {
        try {
            $sql = "SELECT * FROM application_recovery_family_members WHERE case_no = :case_no ORDER BY member_no ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['case_no' => $caseNo]);
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $data];
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            return ['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.'];
        }
    }

    public function save($data) {
        try {
            // 필수 필드 검증
            if (!isset($data['case_no']) || empty($data['case_no'])) {
                return ['success' => false, 'message' => '사건번호가 필요합니다.'];
            }
            if (!isset($data['relation']) || empty($data['relation'])) {
                return ['success' => false, 'message' => '관계를 입력해주세요.'];
            }
            if (!isset($data['name']) || empty($data['name'])) {
                return ['success' => false, 'message' => '성명을 입력해주세요.'];
            }

            // 데이터 준비
            $params = [
                'case_no' => $data['case_no'],
                'relation' => $data['relation'],
                'name' => $data['name'],
                'age' => intval($data['age'] ?? 0),
                'live_together' => $data['live_together'] ?? 'N',
                'live_period' => $data['live_period'] ?? null,
                'job' => $data['job'] ?? null,
                'income' => intval($data['income'] ?? 0),
                'assets' => intval($data['assets'] ?? 0),
                'support' => $data['support'] ?? 'N'
            ];

            // member_no가 있으면 UPDATE, 없으면 INSERT
            if (isset($data['member_no']) && !empty($data['member_no'])) {
                $params['member_no'] = $data['member_no'];
                $sql = "UPDATE application_recovery_family_members SET 
                        relation = :relation,
                        name = :name,
                        age = :age,
                        live_together = :live_together,
                        live_period = :live_period,
                        job = :job,
                        income = :income,
                        assets = :assets,
                        support = :support,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE member_no = :member_no AND case_no = :case_no";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                return [
                    'success' => true,
                    'message' => '가족구성원 정보가 수정되었습니다.',
                    'data' => $params
                ];
            } else {
                $sql = "INSERT INTO application_recovery_family_members 
                        (case_no, relation, name, age, live_together, live_period, job, income, assets, support)
                        VALUES 
                        (:case_no, :relation, :name, :age, :live_together, :live_period, :job, :income, :assets, :support)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                $params['member_no'] = $this->pdo->lastInsertId();

                return [
                    'success' => true,
                    'message' => '가족구성원 정보가 저장되었습니다.',
                    'data' => $params
                ];
            }
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            return ['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.'];
        }
    }

    public function delete($caseNo, $memberNo) {
        try {
            $sql = "DELETE FROM application_recovery_family_members WHERE case_no = :case_no AND member_no = :member_no";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'case_no' => $caseNo,
                'member_no' => $memberNo
            ]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => '가족구성원 정보가 삭제되었습니다.'];
            } else {
                return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
            }
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            return ['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.'];
        }
    }
}

// API 요청 처리
$api = new FamilyMemberApi($pdo);
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
        $memberNo = $deleteData['member_no'] ?? null;
        
        if (!$caseNo || !$memberNo) {
            echo json_encode(['success' => false, 'message' => '사건번호와 구성원번호가 필요합니다.']);
            exit;
        }
        $result = $api->delete($caseNo, $memberNo);
        break;

    default:
        http_response_code(405);
        $result = ['success' => false, 'message' => '지원하지 않는 메소드입니다.'];
        break;
}

echo json_encode($result);
?>