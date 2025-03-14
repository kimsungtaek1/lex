<?php
// base_asset_api.php

class BaseAssetApi {
    protected $pdo;
    protected $tableName;
    protected $idField;
    protected $caseNo;

    public function __construct($pdo, $tableName, $idField = 'property_no') {
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

			// asset_type 필드는 사용하지 않으므로 제거
			if(isset($data['asset_type'])) {
				unset($data['asset_type']);
			}
			
			// 날짜 필드의 빈 문자열을 NULL로 변환
            $dateFields = [
                'contract_date', 'lease_start_date', 'lease_end_date', 'fixed_date', 'registration_date',
                'disposal_date', 'receipt_date', 'divorce_date', 'inheritance_date'
            ];
            foreach ($dateFields as $field) {
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }

			// 기존 데이터 확인 (property_no로 확인)
			$stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND property_no = :property_no");
			$stmt->execute([
				'case_no' => $data['case_no'],
				'property_no' => $data['property_no']
			]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($existingData) {
				// UPDATE
				$fields = [];
				$params = [];
				foreach ($data as $key => $value) {
					if ($key == 'case_no' || $key == 'property_no') continue;
					$fields[] = "$key = :$key";
					$params[$key] = $value;
				}
				$params['case_no'] = $data['case_no'];
				$params['property_no'] = $data['property_no'];
				
				$sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
					   " WHERE case_no = :case_no AND property_no = :property_no";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute($params);
				
				// asset_no를 결과에 포함
				$data['asset_no'] = $existingData['asset_no'];
				return ['success' => true, 'data' => $data];
			} else {
				// INSERT
				$columns = array_keys($data);
				$placeholders = array_map(function($col) { return ":$col"; }, $columns);
				
				$sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ")
						VALUES (" . implode(', ', $placeholders) . ")";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute($data);
				
				$data['asset_no'] = $this->pdo->lastInsertId();
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