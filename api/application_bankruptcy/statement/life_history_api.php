<?php
// api/application_bankruptcy/statement/life_history_api.php
session_start();
if (!isset($_SESSION['employee_no'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

require_once '../../../config.php';
require_once 'BaseStatementApi.php';

class LifeHistoryApi extends BaseStatementApi {
	public function __construct($pdo) {
		parent::__construct($pdo, 'application_bankruptcy_statement_life_history', 'id');
	}
}

// API 요청 처리
$api = new LifeHistoryApi($pdo);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
	case 'GET':
		$caseNo = $_GET['case_no'] ?? null;
		$id = $_GET['id'] ?? null;
		
		if (!$caseNo) {
			echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
			exit;
		}
		
		$result = $api->get($caseNo, $id);
		break;
		
	case 'POST':
		$result = $api->save($_POST);
		break;
		
	case 'DELETE':
		parse_str(file_get_contents("php://input"), $deleteData);
		$caseNo = $deleteData['case_no'] ?? null;
		$id = $deleteData['id'] ?? null;
		
		if (!$caseNo) {
			echo json_encode(['success' => false, 'message' => '사건번호가 필요합니다.']);
			exit;
		}
		
		$result = $api->delete($caseNo, $id);
		break;
		
	default:
		http_response_code(405);
		$result = ['success' => false, 'message' => '지원하지 않는 메소드입니다.'];
		break;
}

echo json_encode($result);
?>