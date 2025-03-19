<?php
// BaseStatementApi.php

class BaseStatementApi {
    protected $pdo;
    protected $tableName;
    protected $idField;
    protected $caseNo;

    public function __construct($pdo, $tableName, $idField) {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->idField = $idField;
    }

    public function setCaseNo($caseNo) {
        $this->caseNo = $caseNo;
    }
    
    public function save($data) {
        try {
            if (!isset($data['case_no']) || empty($data['case_no'])) {
                return ['success' => false, 'message' => 'case_no가 필요합니다.'];
            }
            $this->setCaseNo($data['case_no']);

            // 날짜 필드의 빈 문자열을 NULL로 변환
            $dateFields = ['graduation_date', 'work_start_date', 'work_end_date'];
            foreach ($dateFields as $field) {
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }

            // ID 필드 값이 있는지 확인
            $idValue = isset($data[$this->idField]) ? $data[$this->idField] : null;

            if ($idValue) {
                // UPDATE
                $fields = [];
                $params = [];
                foreach ($data as $key => $value) {
                    if ($key == 'case_no' || $key == $this->idField) continue;
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
                $params['case_no'] = $data['case_no'];
                $params[$this->idField] = $idValue;
                
                $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
                       " WHERE case_no = :case_no AND {$this->idField} = :{$this->idField}";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                
                return ['success' => true, 'data' => $data];
            } else {
                // INSERT
                // ID 필드 제외
                if (isset($data[$this->idField])) {
                    unset($data[$this->idField]);
                }
                
                $columns = array_keys($data);
                $placeholders = array_map(function($col) { return ":$col"; }, $columns);
                
                $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ")
                        VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);
                
                $data[$this->idField] = $this->pdo->lastInsertId();
                return ['success' => true, 'data' => $data];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // 삭제
    public function delete($case_no, $id) {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['case_no' => $case_no, 'id' => $id]);
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => '삭제되었습니다.'];
            } else {
                return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

	// 조회
	public function get($case_no, $id = null) {
		try {
			if ($id !== null) {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND {$this->idField} = :id";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $case_no, 'id' => $id]);
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['success' => true, 'data' => $data];
			} else {
				$sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute(['case_no' => $case_no]);
				$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				return ['success' => true, 'data' => $data];
			}
		} catch (Exception $e) {
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}
}
?>