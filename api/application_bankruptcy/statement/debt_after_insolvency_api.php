<?php
// api/application_bankruptcy/statement/debt_after_insolvency_api.php
session_start();
if (!isset($_SESSION['employee_no'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

require_once '../../../config.php';
require_once 'BaseStatementApi.php';

class DebtAfterInsolvencyApi {
	private $pdo;
	private $tableName = 'application_bankruptcy_statement_debt_after_insolvency';
	private $idField = 'id';
	
	public function __construct($pdo) {
		$this->pdo = $pdo;
	}
	
	public function get($caseNo, $id = null) {
		try {
			if ($id !== null) {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo, 'id' => $id]);
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
			} else {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no ORDER BY {$this->idField} DESC";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo]);
				$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			
			return ['success' => true, 'data' => $data];
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	public function save($data) {
		try {
			if (!isset($data['case_no']) || empty($data['case_no'])) {
				return ['success' => false, 'message' => '사건번호가 필요합니다.'];
			}
			
			$this->processDateFields($data);
			
			if (isset($data['id']) && !empty($data['id'])) {
				$stmt = $this->pdo->prepare("SELECT {$this->idField} FROM {$this->tableName} 
                    WHERE case_no = :case_no AND {$this->idField} = :id");
				$stmt->execute(['case_no' => $data['case_no'], 'id' => $data['id']]);
				$exists = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if ($exists) {
					$result = $this->update($data);
					$message = '지급불능 이후 채무 정보가 수정되었습니다.';
				} else {
					unset($data['id']);
					$result = $this->insert($data);
					$message = '지급불능 이후 채무 정보가 새로 추가되었습니다.';
				}
			} else {
				$result = $this->insert($data);
				$message = '지급불능 이후 채무 정보가 추가되었습니다.';
			}
			
			return [
				'success' => true,
				'message' => $message,
				'data' => $result
			];
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	public function delete($caseNo, $id) {
		try {
			if (!$id) {
				return ['success' => false, 'message' => '삭제할 ID가 필요합니다.'];
			}
			
			$sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute(['case_no' => $caseNo, 'id' => $id]);
			
			if ($stmt->rowCount() > 0) {
				return ['success' => true, 'message' => '지급불능 이후 채무 정보가 삭제되었습니다.'];
			} else {
				return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
			}
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	private function processDateFields(&$data) {
		$dateFields = ['date'];
		
		foreach ($dateFields as $field) {
			if (isset($data[$field]) && empty($data[$field])) {
				$data[$field] = null;
			}
		}
	}
	
	private function update($data) {
		$id = $data['id'];
		unset($data['id']);
		
		$fields = [];
		foreach ($data as $key => $value) {
			if ($key == 'case_no') continue;
			$fields[] = "$key = :$key";
		}
		
		$data['id'] = $id;
		
		$sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
		       ", updated_at = CURRENT_TIMESTAMP WHERE case_no = :case_no AND {$this->idField} = :id";
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($data);
		
		return $data;
	}
	
	private function insert($data) {
		if (isset($data['id'])) {
			unset($data['id']);
		}
		
		$columns = array_keys($data);
		$placeholders = array_map(function($col) { return ":$col"; }, $columns);
		
		$sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ") 
				VALUES (" . implode(', ', $placeholders) . ")";
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($data);
		
		$data['id'] = $this->pdo->lastInsertId();
		return $data;
	}
}

// API 요청 처리
$api = new DebtAfterInsolvencyApi($pdo);
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