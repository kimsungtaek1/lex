<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../config.php';

class ApplicationApi {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getIsCompany($caseNo) {
        try {
            $sql = "SELECT is_company FROM application_recovery WHERE case_no = :case_no";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['case_no' => $caseNo]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return ['success' => true, 'data' => ['is_company' => (int)$result['is_company']]];
            } else {
                return ['success' => false, 'message' => '데이터를 찾을 수 없습니다.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// API 요청 처리
$api = new ApplicationApi($pdo);

$caseNo = $_GET['case_no'] ?? null;
if (!$caseNo) {
    echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
    exit;
}

$result = $api->getIsCompany($caseNo);
echo json_encode($result);
?>