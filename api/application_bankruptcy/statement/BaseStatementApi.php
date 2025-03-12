<?php
// BaseStatementApi.php

class BaseStatementApi {
	private $pdo;
	private $tableName;
	private $idField;
	
	public function __construct($pdo, $tableName, $idField) {
		$this->pdo = $pdo;
		$this->tableName = $tableName;
		$this->idField = $idField;
	}
	
	public function save($data) {
		try {
			// 필수 필드 검증
			if (!isset($data['case_no']) || empty($data['case_no'])) {
				return ['success' => false, 'message' => '사건번호가 필요합니다.'];
			}
			
			// 날짜 필드 처리
			$this->processDateFields($data);
			
			// 배열 필드 처리
			$this->processArrayFields($data);
			
			// 이미 존재하는 데이터 확인
			$stmt = $this->pdo->prepare("SELECT {$this->idField} FROM {$this->tableName} WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $data['case_no']]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// 기본 파라미터 세팅
			$params = $this->prepareParams($data);
			
			if ($existingData) {
				// UPDATE
				$result = $this->update($params, $existingData[$this->idField]);
				$message = '정보가 수정되었습니다.';
			} else {
				// INSERT
				$result = $this->insert($params);
				$message = '정보가 저장되었습니다.';
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
	
	public function get($caseNo, $id = null) {
		try {
			if ($id !== null) {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo, 'id' => $id]);
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
			} else {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo]);
				$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			
			if ($data) {
				// 배열 필드 복원
				$this->restoreArrayFields($data);
			}
			
			return ['success' => true, 'data' => $data];
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	public function delete($caseNo, $id = null) {
		try {
			if ($id !== null) {
				$sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo, 'id' => $id]);
			} else {
				$sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $caseNo]);
			}
			
			if ($stmt->rowCount() > 0) {
				return ['success' => true, 'message' => '정보가 삭제되었습니다.'];
			} else {
				return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
			}
		} catch (PDOException $e) {
			error_log("SQL Error: " . $e->getMessage());
			return ['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()];
		}
	}
	
	protected function processDateFields(&$data) {
		$dateFields = [
			'graduation_date', 'work_start_date', 'work_end_date', 'bankruptcy_history_date',
			'bankruptcy_declared_date', 'discharge_date', 'discharge_confirmed_date', 
			'approval_date', 'cancellation_date', 'rehabilitation_discharge_date',
			'unpaid_purchase_date', 'unpaid_disposal_date', 'unfair_sale_date',
			'payment_period_start', 'payment_period_end', 'house_purchase_date',
			'house_disposal_date', 'business_start_date', 'business_end_date',
			'inability_reason_other_date', 'date'
		];
		
		foreach ($dateFields as $field) {
			if (isset($data[$field]) && empty($data[$field])) {
				$data[$field] = null;
			}
		}
	}
	
	protected function processArrayFields(&$data) {
		$arrayFields = [
			'bankruptcy_history_status', 'business_record_type', 'debt_reason', 
			'inability_reason'
		];
		
		foreach ($arrayFields as $field) {
			if (isset($data[$field]) && is_array($data[$field])) {
				$data[$field] = implode(',', $data[$field]);
			}
		}
	}
	
	protected function restoreArrayFields(&$data) {
		if (!$data) return;
		
		$arrayFields = [
			'bankruptcy_history_status', 'business_record_type', 'debt_reason', 
			'inability_reason'
		];
		
		if (is_array($data) && isset($data[0])) {
			// 여러 레코드인 경우
			foreach ($data as &$record) {
				foreach ($arrayFields as $field) {
					if (isset($record[$field]) && !empty($record[$field])) {
						$record[$field] = explode(',', $record[$field]);
					}
				}
			}
		} else {
			// 단일 레코드인 경우
			foreach ($arrayFields as $field) {
				if (isset($data[$field]) && !empty($data[$field])) {
					$data[$field] = explode(',', $data[$field]);
				}
			}
		}
	}
	
	protected function prepareParams($data) {
		// ID 필드 제외
		if (isset($data[$this->idField])) {
			unset($data[$this->idField]);
		}
		
		return $data;
	}
	
	protected function update($params, $id) {
		$fields = [];
		$updateParams = [];
		
		foreach ($params as $key => $value) {
			if ($key == 'case_no') continue; // case_no는 WHERE 절에서 사용
			$fields[] = "$key = :$key";
			$updateParams[$key] = $value;
		}
		
		$updateParams['case_no'] = $params['case_no'];
		$updateParams[$this->idField] = $id;
		
		$sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
		       ", updated_at = CURRENT_TIMESTAMP WHERE case_no = :case_no AND {$this->idField} = :{$this->idField}";
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($updateParams);
		
		// 업데이트 후 최신 데이터 반환
		$params[$this->idField] = $id;
		return $params;
	}
	
	protected function insert($params) {
		$columns = array_keys($params);
		$placeholders = array_map(function($col) { return ":$col"; }, $columns);
		
		$sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ") 
				VALUES (" . implode(', ', $placeholders) . ")";
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		
		$params[$this->idField] = $this->pdo->lastInsertId();
		return $params;
	}
}
?>