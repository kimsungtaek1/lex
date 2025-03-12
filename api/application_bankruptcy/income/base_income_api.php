<?php
class BaseIncomeApi {
    protected $pdo;
    protected $tableName;
    protected $caseNo;

    public function __construct($pdo, $tableName) {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
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

            // 연도 필드 처리
            $year = $data['year'] ?? date('Y');

            // 이미 존재하는 데이터 확인
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND year = :year");
            $stmt->execute([
                'case_no' => $data['case_no'],
                'year' => $year
            ]);
            $existingData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingData) {
                // UPDATE
                $fields = [];
                $params = [];
                foreach ($data as $key => $value) {
                    if ($key == 'case_no' || $key == 'year') continue;
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
                $params['case_no'] = $data['case_no'];
                $params['year'] = $year;

                $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
                       " WHERE case_no = :case_no AND year = :year";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                return ['success' => true, 'data' => $data];
            } else {
                // INSERT
                $columns = array_keys($data);
                $placeholders = array_map(function($col) { return ":$col"; }, $columns);

                $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ")
                        VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);

                return ['success' => true, 'data' => $data];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // 조회
    public function get($case_no, $year = null) {
        try {
            if ($year !== null) {
                $sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no AND year = :year";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['case_no' => $case_no, 'year' => $year]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $sql = "SELECT * FROM {$this->tableName} WHERE case_no = :case_no ORDER BY year DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['case_no' => $case_no]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // 삭제
    public function delete($case_no, $year) {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE case_no = :case_no AND year = :year";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['case_no' => $case_no, 'year' => $year]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => '삭제되었습니다.'];
            } else {
                return ['success' => false, 'message' => '삭제할 데이터가 없습니다.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>