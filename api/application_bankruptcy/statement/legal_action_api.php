<?php
// api/application_bankruptcy/statement/legal_action_api.php
session_start();
if (!isset($_SESSION['employee_no'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

require_once '../../../config.php';
require_once 'BaseStatementApi.php';

class LegalActionApi {
	private $pdo;
	private $tableName = 'application_bankruptcy_statement_legal_action';
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
			// 필수 필드 검증
			if (!isset($data['case_no']) || empty($data['case_no'])) {
				return ['success' => false, 'message' => '사건번호가 필요합니다.'];
			}
			
			// ID가 있으면 업데이트, 없으면 삽입
			if (isset($data['id']) && !empty($data['id'])) {
				$stmt = $this->pdo->prepare("SELECT {$this->idField} FROM {$this->tableName} 
					WHERE case_no = :case_no AND {$this->idField} = :id");
				$stmt->execute(['case_no' => $data['case_no'], 'id' => $data['id']]);
				$exists = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if ($exists) {
					// UPDATE
					$result = $this->update($data);
					$message = '소송/압류 내역이 수정되었습니다.';
				} else {
					// 존재하지 않는 ID면 INSERT로 처리
					unset($data['id']);
					$result = $this->insert($data);
					$message = '소송/압류 내역이 추가되었습니다.';
				}
			} else {
				// INSERT
				$result = $this->insert($data);
				$message = '소송/압류 내역이 추가되었습니다.';
			}
			
			return [
				'success' => true,
				'message' => $message,
				'data' => $result
			];
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		} catch (Exception $e) {
			error_log("General Error: " . $e->getMessage());
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}
	
	public function delete($caseNo, $id) {
		try {
			if (!$id) {
				return ['success' => false, 'message' => '삭제할 소송/압류 내역 ID가 필요합니다.'];
			}
			
			$sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute(['case_no' => $caseNo, 'id' => $id]);
			
			if ($stmt->rowCount() > 0) {
				return ['success' => true, 'message' => '소송/압류 내역이 삭제되었습니다.'];
			} else {
				return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
			}
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	private function update($data) {
		$id = $data['id'];
		unset($data['id']); // ID 필드는 SET 구문에서 제외
		
		$fields = [];
		foreach ($data as $key => $value) {
			if ($key == 'case_no') continue; // case_no는 WHERE 절에서 사용
			$fields[] = "$key = :$key";
		}
		
		$data['id'] = $id; // WHERE 절을 위해 다시 추가
		
		$sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
			   ", updated_at = CURRENT_TIMESTAMP WHERE case_no = :case_no AND {$this->idField} = :id";
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($data);
		
		return $data;
	}
	
	private function insert($data) {
		// ID 필드 제거 (자동 생성)
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
$api = new LegalActionApi($pdo);
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