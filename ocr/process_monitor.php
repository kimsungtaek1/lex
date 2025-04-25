<?php
/**
 * OCR 프로세스 모니터링 시스템
 * 카페24 웹호스팅 환경에 최적화된 버전
 */

require_once 'config.php';
require_once 'enhanced_ocr.php';

class OCRProcessMonitor {
    // 데이터베이스 연결
    private $db;
    
    // 설정 옵션
    private $config;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $config;
        $this->config = $config;
        $this->db = getDB();
        
        if (!$this->db) {
            throw new Exception("데이터베이스 연결 실패");
        }
    }
    
    /**
     * 새 작업 생성
     * @param string $name 작업 이름
     * @param array $files 파일 목록
     * @param array $options 처리 옵션
     * @return int 생성된 작업 ID
     */
    public function createJob($name, $files, $options = []) {
        try {
            $this->db->beginTransaction();
            
            // 작업 생성
            $stmt = $this->db->prepare("
                INSERT INTO ocr_jobs 
                (name, total_files, options, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$name, count($files), json_encode($options)]);
            
            $jobId = $this->db->lastInsertId();
            
            // 파일 등록
            $this->registerFiles($jobId, $files);
            
            // 작업 로그 추가
            $this->addJobLog($jobId, 'info', "작업 생성: {$name}, 파일 수: " . count($files));
            
            $this->db->commit();
            return $jobId;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logMessage("작업 생성 오류: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 작업에 파일 등록
     * @param int $jobId 작업 ID
     * @param array $files 파일 목록
     */
    private function registerFiles($jobId, $files) {
        $stmt = $this->db->prepare("
            INSERT INTO ocr_job_files 
            (job_id, file_path, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
        ");
        
        foreach ($files as $file) {
            $stmt->execute([$jobId, $file]);
        }
    }
    
    /**
     * 다음 처리할 파일 가져오기
     * @param int $jobId 작업 ID
     * @return array|null 파일 정보 또는 처리할 파일이 없는 경우 null
     */
    public function getNextPendingFile($jobId) {
        $stmt = $this->db->prepare("
            SELECT id, file_path 
            FROM ocr_job_files 
            WHERE job_id = ? AND status = 'pending' 
            ORDER BY id ASC 
            LIMIT 1
        ");
        
        $stmt->execute([$jobId]);
        return $stmt->fetch();
    }
    
    /**
     * 작업 상태 업데이트
     * @param int $jobId 작업 ID
     * @param string $status 상태
     */
    public function updateJobStatus($jobId, $status) {
        $stmt = $this->db->prepare("
            UPDATE ocr_jobs 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $jobId]);
        
        $this->addJobLog($jobId, 'info', "작업 상태 변경: {$status}");
    }
    
    /**
     * 파일 상태 업데이트
     * @param int $fileId 파일 ID
     * @param string $status 상태
     * @param string $resultPath 결과 경로 (선택)
     */
    public function updateFileStatus($fileId, $status, $resultPath = null) {
        $sql = "
            UPDATE ocr_job_files 
            SET status = ?, updated_at = NOW()
        ";
        
        $params = [$status];
        
        if ($resultPath !== null) {
            $sql .= ", result_path = ?";
            $params[] = $resultPath;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $fileId;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // 작업 파일 ID로 작업 ID 가져오기
        $stmt = $this->db->prepare("
            SELECT job_id FROM ocr_job_files WHERE id = ?
        ");
        $stmt->execute([$fileId]);
        $result = $stmt->fetch();
        
        if ($result) {
            $jobId = $result['job_id'];
            $this->updateProcessedFilesCount($jobId);
        }
    }
    
    /**
     * 처리된 파일 수 업데이트
     * @param int $jobId 작업 ID
     */
    private function updateProcessedFilesCount($jobId) {
        // 처리 완료/실패된 파일 수 조회
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS processed_count 
            FROM ocr_job_files 
            WHERE job_id = ? AND status IN ('completed', 'failed')
        ");
        
        $stmt->execute([$jobId]);
        $result = $stmt->fetch();
        $processedCount = $result['processed_count'];
        
        // 전체 파일 수 조회
        $stmt = $this->db->prepare("
            SELECT total_files 
            FROM ocr_jobs 
            WHERE id = ?
        ");
        
        $stmt->execute([$jobId]);
        $result = $stmt->fetch();
        $totalFiles = $result['total_files'];
        
        // 진행률 계산
        $progress = ($totalFiles > 0) ? round(($processedCount / $totalFiles) * 100, 2) : 0;
        
        // 업데이트
        $stmt = $this->db->prepare("
            UPDATE ocr_jobs 
            SET processed_files = ?, progress = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$processedCount, $progress, $jobId]);
        
        // 모든 파일이 처리되었는지 확인
        if ($processedCount >= $totalFiles) {
            $this->updateJobStatus($jobId, 'completed');
        }
    }
    
    /**
     * 작업 로그 추가
     * @param int $jobId 작업 ID
     * @param string $level 로그 레벨 (info, warning, error)
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 (선택)
     */
    public function addJobLog($jobId, $level, $message, $context = []) {
        $stmt = $this->db->prepare("
            INSERT INTO ocr_job_logs
            (job_id, level, message, context, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $contextJson = !empty($context) ? json_encode($context) : null;
        $stmt->execute([$jobId, $level, $message, $contextJson]);
    }
    
    /**
     * 파일 처리 
     * - 웹호스팅 환경을 고려하여 단일 파일만 처리
     * @param int $jobId 작업 ID
     * @param int $fileId 파일 ID
     * @param string $filePath 파일 경로
     * @return array 처리 결과
     */
    public function processFile($jobId, $fileId, $filePath) {
        try {
            // 작업 옵션 가져오기
            $stmt = $this->db->prepare("
                SELECT options FROM ocr_jobs WHERE id = ?
            ");
            
            $stmt->execute([$jobId]);
            $jobData = $stmt->fetch();
            $options = !empty($jobData['options']) ? json_decode($jobData['options'], true) : [];
            
            // 파일 상태 업데이트
            $this->updateFileStatus($fileId, 'processing');
            
            // OCR 처리
            $this->addJobLog($jobId, 'info', "파일 처리 시작: {$filePath}");
            $result = enhancedOCRProcess($filePath, $options);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? "처리 실패");
            }
            
            // 결과 파일 경로 JSON으로 저장
            $resultPathJson = json_encode($result['output_files']);
            
            // 파일 상태 업데이트
            $this->updateFileStatus($fileId, 'completed', $resultPathJson);
            
            $this->addJobLog(
                $jobId, 
                'info', 
                "파일 처리 완료: {$filePath}", 
                ['output_files' => $result['output_files']]
            );
            
            return $result;
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $this->addJobLog($jobId, 'error', "파일 처리 오류: {$errorMsg}", ['file' => $filePath]);
            $this->updateFileStatus($fileId, 'failed');
            
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }
    
    /**
     * AJAX 기반 처리를 위한 파일 처리 진행
     * - 웹호스팅 환경에 최적화된 작업 처리 방식
     * @param int $jobId 작업 ID
     * @return array 처리 결과
     */
    public function processNextFile($jobId) {
        try {
            // 작업 상태 확인
            $stmt = $this->db->prepare("
                SELECT status FROM ocr_jobs WHERE id = ?
            ");
            
            $stmt->execute([$jobId]);
            $job = $stmt->fetch();
            
            if (!$job) {
                throw new Exception("작업을 찾을 수 없습니다.");
            }
            
            if ($job['status'] === 'completed') {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'message' => '모든 파일 처리가 완료되었습니다.'
                ];
            }
            
            if ($job['status'] === 'failed' || $job['status'] === 'cancelled') {
                return [
                    'success' => false,
                    'status' => $job['status'],
                    'message' => '작업이 ' . ($job['status'] === 'failed' ? '실패' : '취소') . '되었습니다.'
                ];
            }
            
            // 처리할 파일 가져오기
            $nextFile = $this->getNextPendingFile($jobId);
            
            if (!$nextFile) {
                // 처리할 파일이 없으면 진행 상태 확인
                $stmt = $this->db->prepare("
                    SELECT total_files, processed_files FROM ocr_jobs WHERE id = ?
                ");
                
                $stmt->execute([$jobId]);
                $jobStatus = $stmt->fetch();
                
                if ($jobStatus['total_files'] <= $jobStatus['processed_files']) {
                    $this->updateJobStatus($jobId, 'completed');
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'message' => '모든 파일 처리가 완료되었습니다.'
                    ];
                } else {
                    // 처리 중인 파일이 있는 경우
                    return [
                        'success' => true,
                        'status' => 'processing',
                        'message' => '다른 파일이 처리 중입니다.',
                        'progress' => $this->getJobProgress($jobId)
                    ];
                }
            }
            
            // 파일 처리
            $result = $this->processFile($jobId, $nextFile['id'], $nextFile['file_path']);
            
            // 최신 진행 상태 조회
            $progress = $this->getJobProgress($jobId);
            
            return [
                'success' => $result['success'],
                'status' => 'processing',
                'file_processed' => $nextFile['file_path'],
                'result' => $result,
                'progress' => $progress
            ];
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $this->addJobLog($jobId, 'error', "처리 오류: {$errorMsg}");
            
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }
    
    /**
     * 작업 진행 상황 가져오기
     * @param int $jobId 작업 ID
     * @return array 작업 진행 정보
     */
    public function getJobProgress($jobId) {
        $stmt = $this->db->prepare("
            SELECT id, name, total_files, processed_files, progress, status, created_at, updated_at
            FROM ocr_jobs
            WHERE id = ?
        ");
        
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            return null;
        }
        
        // 최근 처리된 파일들 가져오기 (최대 10개)
        $stmt = $this->db->prepare("
            SELECT id, file_path, status, result_path, created_at, updated_at
            FROM ocr_job_files
            WHERE job_id = ?
            ORDER BY updated_at DESC
            LIMIT 10
        ");
        
        $stmt->execute([$jobId]);
        $recentFiles = $stmt->fetchAll();
        
        // 각 상태별 파일 수 계산
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count
            FROM ocr_job_files
            WHERE job_id = ?
            GROUP BY status
        ");
        
        $stmt->execute([$jobId]);
        $statusCounts = [];
        
        while ($row = $stmt->fetch()) {
            $statusCounts[$row['status']] = $row['count'];
        }
        
        return [
            'job' => $job,
            'recent_files' => $recentFiles,
            'status_counts' => $statusCounts
        ];
    }
    
    /**
     * 모든 작업 가져오기
     * @param int $limit 최대 개수
     * @param int $offset 시작 위치
     * @return array 작업 목록
     */
    public function getUserJobs($limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT id, name, total_files, processed_files, progress, status, created_at, updated_at
            FROM ocr_jobs
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * 작업 상세 정보 가져오기
     * @param int $jobId 작업 ID
     * @return array 작업 상세 정보
     */
    public function getJobDetails($jobId) {
        // 작업 기본 정보
        $stmt = $this->db->prepare("
            SELECT * FROM ocr_jobs
            WHERE id = ?
        ");
        
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            return null;
        }
        
        // 파일 목록
        $stmt = $this->db->prepare("
            SELECT id, file_path, status, result_path, created_at, updated_at
            FROM ocr_job_files
            WHERE job_id = ?
            ORDER BY id ASC
        ");
        
        $stmt->execute([$jobId]);
        $files = $stmt->fetchAll();
        
        // 로그 목록
        $stmt = $this->db->prepare("
            SELECT id, level, message, context, created_at
            FROM ocr_job_logs
            WHERE job_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ");
        
        $stmt->execute([$jobId]);
        $logs = $stmt->fetchAll();
        
        // 결과 구성
        return [
            'job' => $job,
            'files' => $files,
            'logs' => $logs
        ];
    }
    
    /**
     * 작업 취소
     * @param int $jobId 작업 ID
     * @return bool 성공 여부
     */
    public function cancelJob($jobId) {
        try {
            $this->db->beginTransaction();
            
            // 작업 상태 업데이트
            $stmt = $this->db->prepare("
                UPDATE ocr_jobs 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE id = ? AND status IN ('queued', 'processing')
            ");
            
            $stmt->execute([$jobId]);
            $jobUpdated = ($stmt->rowCount() > 0);
            
            if ($jobUpdated) {
                // 대기 중인 파일 상태 업데이트
                $stmt = $this->db->prepare("
                    UPDATE ocr_job_files 
                    SET status = 'cancelled', updated_at = NOW() 
                    WHERE job_id = ? AND status = 'pending'
                ");
                
                $stmt->execute([$jobId]);
                
                $this->addJobLog($jobId, 'info', "작업이 취소되었습니다.");
            }
            
            $this->db->commit();
            return $jobUpdated;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->addJobLog($jobId, 'error', "작업 취소 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 작업 삭제
     * @param int $jobId 작업 ID
     * @return bool 성공 여부
     */
    public function deleteJob($jobId) {
        try {
            $this->db->beginTransaction();
            
            // 결과 파일 경로 가져오기
            $stmt = $this->db->prepare("
                SELECT result_path FROM ocr_job_files
                WHERE job_id = ? AND result_path IS NOT NULL
            ");
            
            $stmt->execute([$jobId]);
            $files = $stmt->fetchAll();
            
            // 결과 파일 삭제
            foreach ($files as $file) {
                if (!empty($file['result_path'])) {
                    $resultPaths = json_decode($file['result_path'], true);
                    if (is_array($resultPaths)) {
                        foreach ($resultPaths as $path) {
                            if (file_exists($path)) {
                                @unlink($path);
                            }
                        }
                    }
                }
            }
            
            // 로그 삭제
            $stmt = $this->db->prepare("DELETE FROM ocr_job_logs WHERE job_id = ?");
            $stmt->execute([$jobId]);
            
            // 파일 정보 삭제
            $stmt = $this->db->prepare("DELETE FROM ocr_job_files WHERE job_id = ?");
            $stmt->execute([$jobId]);
            
            // 작업 삭제
            $stmt = $this->db->prepare("DELETE FROM ocr_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            logMessage("작업 삭제 오류: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 작업 결과 가져오기
     * @param int $jobId 작업 ID
     * @return array 작업 결과 파일 정보
     */
    public function getJobResults($jobId) {
        // 완료된 파일 결과 가져오기
        $stmt = $this->db->prepare("
            SELECT file_path, result_path
            FROM ocr_job_files
            WHERE job_id = ? AND status = 'completed' AND result_path IS NOT NULL
            ORDER BY id ASC
        ");
        
        $stmt->execute([$jobId]);
        $files = $stmt->fetchAll();
        
        $results = [];
        
        foreach ($files as $file) {
            $resultPaths = json_decode($file['result_path'], true) ?: [];
            
            $results[] = [
                'original_file' => $file['file_path'],
                'text_file' => $resultPaths['text'] ?? null,
                'json_file' => $resultPaths['json'] ?? null,
                'table_file' => $resultPaths['table'] ?? null
            ];
        }
        
        return [
            'job_id' => $jobId,
            'files' => $results
        ];
    }
}
