<?php
/**
 * OCR 인식률 향상 시스템 - 문서 학습 시스템
 */

require_once 'config.php';

class DocumentLearningSystem {
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
     * 문서 템플릿 생성/수정
     * @param array $templateData 템플릿 데이터
     * @return int 템플릿 ID
     */
    public function saveTemplate($templateData) {
        // 필수 필드 검증
        if (empty($templateData['name'])) {
            throw new Exception("템플릿 이름은 필수입니다.");
        }
        
        try {
            // 필드 정보 처리
            $fields = !empty($templateData['fields']) ? $templateData['fields'] : [];
            $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
            
            // 테이블 구조 처리
            $tableStructure = !empty($templateData['tableStructure']) ? $templateData['tableStructure'] : [];
            $tableJson = json_encode($tableStructure, JSON_UNESCAPED_UNICODE);
            
            // 공개 여부
            $isPublic = !empty($templateData['isPublic']) ? 1 : 0;
            
            // 기존 템플릿 확인
            $templateId = !empty($templateData['id']) ? $templateData['id'] : null;
            
            if ($templateId) {
                // 기존 템플릿 업데이트
                $stmt = $this->db->prepare("
                    UPDATE ocr_document_templates
                    SET name = ?, description = ?, fields = ?, table_structure = ?, 
                        is_public = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $templateData['name'],
                    $templateData['description'] ?? '',
                    $fieldsJson,
                    $tableJson,
                    $isPublic,
                    $templateId
                ]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("템플릿을 업데이트할 권한이 없거나 템플릿이 존재하지 않습니다.");
                }
                
                return $templateId;
            } else {
                // 새 템플릿 생성
                $stmt = $this->db->prepare("
                    INSERT INTO ocr_document_templates
                    (name, description, fields, table_structure, is_public, created_at, updated_at, is_active)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 1)
                ");
                
                $stmt->execute([
                    $templateData['name'],
                    $templateData['description'] ?? '',
                    $fieldsJson,
                    $tableJson,
                    $isPublic
                ]);
                
                return $this->db->lastInsertId();
            }
            
        } catch (Exception $e) {
            logMessage("템플릿 저장 오류: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 템플릿 정보 가져오기
     * @param int $templateId 템플릿 ID
     * @return array 템플릿 정보
     */
    public function getTemplate($templateId) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.username
            FROM ocr_document_templates t
            LEFT JOIN ocr_users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        
        if (!$template) {
            return null;
        }
        
        // JSON 디코딩
        $template['fields'] = json_decode($template['fields'], true) ?: [];
        $template['table_structure'] = json_decode($template['table_structure'], true) ?: [];
        $template['learning_data'] = json_decode($template['learning_data'], true) ?: [];
        $template['learning_statistics'] = json_decode($template['learning_statistics'], true) ?: [];
        
        return $template;
    }
    
    /**
     * 템플릿 목록 가져오기
     * @param bool $includePublic 공개 템플릿 포함 여부
     * @return array 템플릿 목록
     */
    public function getTemplates($includePublic = true) {
        $sql = "
            SELECT t.id, t.name, t.description, t.is_public, t.created_at, t.updated_at,
                   u.username,
                   (SELECT COUNT(*) FROM ocr_jobs WHERE document_type = t.id) as usage_count
            FROM ocr_document_templates t
            LEFT JOIN ocr_users u ON t.user_id = u.id
            WHERE t.is_active = 1
        ";
        
        if ($includePublic) {
            $sql .= " AND (t.is_public = 1)";
        } else {
            $sql .= " AND (t.is_public = 0)";
        }
        
        $sql .= " ORDER BY t.updated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 사용자 피드백 저장
     * @param int $jobId 작업 ID
     * @param int $fileId 파일 ID
     * @param array $corrections 수정 내용
     * @return int 피드백 ID
     */
    public function saveFeedback($jobId, $fileId, $corrections) {
        try {
            // 피드백 저장
            $stmt = $this->db->prepare("
                INSERT INTO ocr_feedback
                (job_id, file_id, corrections, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $correctionsJson = json_encode($corrections, JSON_UNESCAPED_UNICODE);
            $stmt->execute([$jobId, $fileId, $correctionsJson]);
            $feedbackId = $this->db->lastInsertId();
            
            // 문서 유형 확인
            $stmt = $this->db->prepare("
                SELECT document_type FROM ocr_jobs WHERE id = ?
            ");
            
            $stmt->execute([$jobId]);
            $job = $stmt->fetch();
            
            if ($job && !empty($job['document_type'])) {
                // 템플릿에 피드백 적용
                $this->applyFeedbackToTemplate($job['document_type'], $corrections);
            }
            
            // 사용자 사전에 피드백 적용
            $this->applyFeedbackToDictionary($corrections);
            
            return $feedbackId;
            
        } catch (Exception $e) {
            logMessage("피드백 저장 오류: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 템플릿에 피드백 적용
     * @param int $templateId 템플릿 ID
     * @param array $corrections 수정 내용
     */
    private function applyFeedbackToTemplate($templateId, $corrections) {
        // 기존 템플릿 정보 가져오기
        $template = $this->getTemplate($templateId);
        
        if (!$template) {
            return;
        }
        
        $fields = $template['fields'];
        $tableStructure = $template['table_structure'];
        $learningData = $template['learning_data'];
        
        // 수정 정보 처리
        $updated = false;
        
        foreach ($corrections as $correction) {
            // 필드 수정
            if (isset($correction['type']) && $correction['type'] === 'field') {
                $fieldName = $correction['field'];
                $originalText = $correction['original'];
                $correctedText = $correction['corrected'];
                
                // 기존 필드 찾기
                $fieldIndex = $this->findFieldIndex($fields, $fieldName);
                
                if ($fieldIndex !== false) {
                    // 학습 데이터 업데이트
                    if (!isset($fields[$fieldIndex]['learning_examples'])) {
                        $fields[$fieldIndex]['learning_examples'] = [];
                    }
                    
                    // 유사한 예제가 있는지 확인
                    $exampleFound = false;
                    
                    foreach ($fields[$fieldIndex]['learning_examples'] as &$example) {
                        if ($example['original'] === $originalText) {
                            // 기존 예제 가중치 증가
                            $example['weight'] = ($example['weight'] ?? 1) + 1;
                            $exampleFound = true;
                            break;
                        }
                    }
                    
                    // 새 예제 추가
                    if (!$exampleFound) {
                        $fields[$fieldIndex]['learning_examples'][] = [
                            'original' => $originalText,
                            'corrected' => $correctedText,
                            'weight' => 1
                        ];
                    }
                    
                    $updated = true;
                } else {
                    // 새 필드 추가
                    $fields[] = [
                        'name' => $fieldName,
                        'type' => 'text',
                        'learning_examples' => [
                            [
                                'original' => $originalText,
                                'corrected' => $correctedText,
                                'weight' => 1
                            ]
                        ]
                    ];
                    
                    $updated = true;
                }
            }
            // 테이블 헤더 수정
            else if (isset($correction['type']) && $correction['type'] === 'table_header') {
                $columnIndex = $correction['column_index'];
                $originalText = $correction['original'];
                $correctedText = $correction['corrected'];
                
                // 헤더 정보가 없으면 초기화
                if (!isset($tableStructure['headers'])) {
                    $tableStructure['headers'] = [];
                }
                
                // 헤더 학습 데이터가 없으면 초기화
                if (!isset($tableStructure['header_learning'])) {
                    $tableStructure['header_learning'] = [];
                }
                
                // 특정 열의 학습 데이터가 없으면 초기화
                if (!isset($tableStructure['header_learning'][$columnIndex])) {
                    $tableStructure['header_learning'][$columnIndex] = [];
                }
                
                // 헤더 값 설정
                $tableStructure['headers'][$columnIndex] = $correctedText;
                
                // 유사한 예제가 있는지 확인
                $exampleFound = false;
                
                foreach ($tableStructure['header_learning'][$columnIndex] as &$example) {
                    if ($example['original'] === $originalText) {
                        $example['weight'] = ($example['weight'] ?? 1) + 1;
                        $exampleFound = true;
                        break;
                    }
                }
                
                // 새 예제 추가
                if (!$exampleFound) {
                    $tableStructure['header_learning'][$columnIndex][] = [
                        'original' => $originalText,
                        'corrected' => $correctedText,
                        'weight' => 1
                    ];
                }
                
                $updated = true;
            }
        }
        
        // 전체 학습 데이터 업데이트
        $learningData[] = [
            'corrections' => $corrections,
            'timestamp' => time()
        ];
        
        // 최대 100개의 학습 데이터만 유지
        if (count($learningData) > 100) {
            $learningData = array_slice($learningData, -100);
        }
        
        if ($updated) {
            // 템플릿 업데이트
            $stmt = $this->db->prepare("
                UPDATE ocr_document_templates
                SET fields = ?, table_structure = ?, learning_data = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
            $tableJson = json_encode($tableStructure, JSON_UNESCAPED_UNICODE);
            $learningJson = json_encode($learningData, JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([$fieldsJson, $tableJson, $learningJson, $templateId]);
        }
    }
    
    /**
     * 필드 인덱스 찾기
     * @param array $fields 필드 배열
     * @param string $fieldName 찾을 필드 이름
     * @return int|bool 필드 인덱스 또는 찾지 못한 경우 false
     */
    private function findFieldIndex($fields, $fieldName) {
        foreach ($fields as $index => $field) {
            if ($field['name'] === $fieldName) {
                return $index;
            }
        }
        return false;
    }
    
    /**
     * 사용자 사전에 피드백 적용
     * @param array $corrections 수정 내용
     */
    private function applyFeedbackToDictionary($corrections) {
        // 각 수정에 대해 단어 사전 업데이트
        foreach ($corrections as $correction) {
            if (isset($correction['original']) && isset($correction['corrected'])) {
                $originalText = trim($correction['original']);
                $correctedText = trim($correction['corrected']);
                
                // 단일 단어만 처리 (공백 없는 경우)
                if (!empty($originalText) && !empty($correctedText) && 
                    strpos($correctedText, ' ') === false && mb_strlen($correctedText, 'UTF-8') >= 2) {
                    
                    // 기존 단어 확인
                    $stmt = $this->db->prepare("
                        SELECT id, similar_words FROM ocr_dictionary
                        WHERE word = ?
                    ");
                    
                    $stmt->execute([$correctedText]);
                    $dictEntry = $stmt->fetch();
                    
                    if ($dictEntry) {
                        // 기존 단어 업데이트
                        $similarWords = json_decode($dictEntry['similar_words'], true) ?: [];
                        
                        // 오인식된 단어가 유사 단어 목록에 없으면 추가
                        if (!in_array($originalText, $similarWords)) {
                            $similarWords[] = $originalText;
                            
                            // 중복 제거 및 정렬
                            $similarWords = array_unique($similarWords);
                            sort($similarWords);
                            
                            // 업데이트
                            $stmt = $this->db->prepare("
                                UPDATE ocr_dictionary
                                SET similar_words = ?, frequency = frequency + 1, updated_at = NOW()
                                WHERE id = ?
                            ");
                            
                            $similarWordsJson = json_encode($similarWords, JSON_UNESCAPED_UNICODE);
                            $stmt->execute([$similarWordsJson, $dictEntry['id']]);
                        } else {
                            // 빈도수만 증가
                            $stmt = $this->db->prepare("
                                UPDATE ocr_dictionary
                                SET frequency = frequency + 1, updated_at = NOW()
                                WHERE id = ?
                            ");
                            
                            $stmt->execute([$dictEntry['id']]);
                        }
                    } else {
                        // 새로운 단어 추가
                        $stmt = $this->db->prepare("
                            INSERT INTO ocr_dictionary
                            (word, similar_words, frequency, is_active, created_at, updated_at)
                            VALUES (?, ?, 1, 1, NOW(), NOW())
                        ");
                        
                        $similarWords = [$originalText];
                        $similarWordsJson = json_encode($similarWords, JSON_UNESCAPED_UNICODE);
                        
                        $stmt->execute([$correctedText, $similarWordsJson]);
                    }
                }
            }
        }
    }
    
    /**
     * 사용자 사전 가져오기
     * @param bool $activeOnly 활성 단어만 가져오기
     * @return array 사용자 사전 데이터
     */
    public function getCustomDictionary($activeOnly = true) {
        $sql = "
            SELECT id, word, similar_words, frequency, is_active, created_at, updated_at 
            FROM ocr_dictionary
        ";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY frequency DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $dictionary = $stmt->fetchAll();
        
        // JSON 디코딩
        foreach ($dictionary as &$entry) {
            $entry['similar_words'] = json_decode($entry['similar_words'], true) ?: [];
        }
        
        return $dictionary;
    }
    
    /**
     * 사용자 사전 단어 추가/수정
     * @param string $word 단어
     * @param array $similarWords 유사 단어 목록
     * @param bool $isActive 활성 여부
     * @return int 단어 ID
     */
    public function upsertDictionaryWord($word, $similarWords, $isActive = true) {
        try {
            // 이미 존재하는지 확인
            $stmt = $this->db->prepare("
                SELECT id FROM ocr_dictionary
                WHERE word = ?
            ");
            
            $stmt->execute([$word]);
            $existing = $stmt->fetch();
            
            $similarWordsJson = json_encode($similarWords, JSON_UNESCAPED_UNICODE);
            
            if ($existing) {
                // 업데이트
                $stmt = $this->db->prepare("
                    UPDATE ocr_dictionary
                    SET similar_words = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([$similarWordsJson, $isActive ? 1 : 0, $existing['id']]);
                return $existing['id'];
            } else {
                // 새로 추가
                $stmt = $this->db->prepare("
                    INSERT INTO ocr_dictionary
                    (word, similar_words, frequency, is_active, created_at, updated_at)
                    VALUES (?, ?, 1, ?, NOW(), NOW())
                ");
                
                $stmt->execute([$word, $similarWordsJson, $isActive ? 1 : 0]);
                return $this->db->lastInsertId();
            }
            
        } catch (Exception $e) {
            logMessage("사전 단어 저장 오류: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 템플릿 추천
     * @param string $text OCR 추출 텍스트
     * @return array 추천 템플릿 목록
     */
    public function recommendTemplates($text) {
        // 모든 템플릿 가져오기
        $stmt = $this->db->prepare("
            SELECT id, name, fields, table_structure
            FROM ocr_document_templates
            WHERE is_active = 1
        ");
        
        $stmt->execute();
        $templates = $stmt->fetchAll();
        
        $recommendations = [];
        
        foreach ($templates as $template) {
            $templateId = $template['id'];
            $templateName = $template['name'];
            $fields = json_decode($template['fields'], true) ?: [];
            
            // 점수 계산
            $score = 0;
            $matchedFields = [];
            
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                
                // 필드 이름이 텍스트에 있는지 확인
                if (stripos($text, $fieldName) !== false) {
                    $score += 2;
                    $matchedFields[] = $fieldName;
                }
                
                // 패턴이 있는 경우 패턴 매칭 시도
                if (!empty($field['pattern'])) {
                    $pattern = '/' . $field['pattern'] . '/i';
                    if (preg_match($pattern, $text)) {
                        $score += 3;
                        if (!in_array($fieldName, $matchedFields)) {
                            $matchedFields[] = $fieldName;
                        }
                    }
                }
                
                // 유사 단어 확인
                if (isset($field['learning_examples']) && is_array($field['learning_examples'])) {
                    foreach ($field['learning_examples'] as $example) {
                        if (stripos($text, $example['original']) !== false) {
                            $score += 1;
                            if (!in_array($fieldName, $matchedFields)) {
                                $matchedFields[] = $fieldName;
                            }
                            break;
                        }
                    }
                }
            }
            
            // 테이블 구조 확인
            $tableStructure = json_decode($template['table_structure'], true) ?: [];
            if (isset($tableStructure['headers']) && is_array($tableStructure['headers'])) {
                $tableHeaders = $tableStructure['headers'];
                $matchedHeaders = 0;
                
                foreach ($tableHeaders as $header) {
                    if (stripos($text, $header) !== false) {
                        $matchedHeaders++;
                    }
                }
                
                // 절반 이상의 헤더가 매칭되면 높은 점수 부여
                if ($matchedHeaders > 0) {
                    $headerRatio = $matchedHeaders / count($tableHeaders);
                    if ($headerRatio >= 0.5) {
                        $score += 5;
                    } else {
                        $score += $matchedHeaders;
                    }
                }
            }
            
            // 최소 점수 이상인 경우 추천에 추가
            if ($score >= 3) {
                $recommendations[] = [
                    'id' => $templateId,
                    'name' => $templateName,
                    'score' => $score,
                    'matched_fields' => $matchedFields
                ];
            }
        }
        
        // 점수 기준 정렬
        usort($recommendations, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // 상위 3개만 반환
        return array_slice($recommendations, 0, 3);
    }
    
    /**
     * 모델 훈련 (관리자용)
     * 카페24 웹호스팅 환경에 최적화: 리소스 소모가 많은 작업 최소화
     * @param bool $forceTrain 강제 훈련 여부
     * @return array 훈련 결과
     */
    public function trainModel($forceTrain = false) {
        // 마지막 훈련 시간 확인
        $stmt = $this->db->prepare("
            SELECT value FROM ocr_system_settings
            WHERE name = 'last_training_time'
        ");
        
        $stmt->execute();
        $lastTraining = $stmt->fetch();
        
        $currentTime = time();
        $minInterval = 60 * 60 * 24; // 1일
        
        // 마지막 훈련 후 충분한 시간이 지났거나 강제 훈련인 경우
        if ($forceTrain || !$lastTraining || ($currentTime - intval($lastTraining['value']) > $minInterval)) {
            try {
                // 1. 사용자 사전 최적화
                $this->trainCustomDictionary();
                
                // 2. 문서 템플릿 최적화
                $this->trainDocumentTemplates();
                
                // 3. 학습 통계 생성
                $stats = $this->generateLearningStatistics();
                
                // 마지막 훈련 시간 업데이트
                $stmt = $this->db->prepare("
                    INSERT INTO ocr_system_settings (name, value) 
                    VALUES ('last_training_time', ?)
                    ON DUPLICATE KEY UPDATE value = ?
                ");
                
                $stmt->execute([$currentTime, $currentTime]);
                
                return [
                    'success' => true,
                    'message' => '모델 훈련이 완료되었습니다.',
                    'trained_at' => date('Y-m-d H:i:s', $currentTime),
                    'statistics' => $stats
                ];
                
            } catch (Exception $e) {
                logMessage("모델 훈련 오류: " . $e->getMessage(), 'error');
                
                return [
                    'success' => false,
                    'message' => '모델 훈련 중 오류가 발생했습니다: ' . $e->getMessage()
                ];
            }
        } else {
            // 훈련 간격이 충분하지 않음
            $nextTrainingTime = intval($lastTraining['value']) + $minInterval;
            $hoursToWait = round(($nextTrainingTime - $currentTime) / 3600, 1);
            
            return [
                'success' => false,
                'message' => "최근에 모델 훈련이 수행되었습니다. 다음 훈련까지 약 {$hoursToWait}시간 기다려주세요.",
                'last_trained_at' => date('Y-m-d H:i:s', intval($lastTraining['value'])),
                'next_training_at' => date('Y-m-d H:i:s', $nextTrainingTime)
            ];
        }
    }
    
    /**
     * 사용자 사전 최적화
     */
    private function trainCustomDictionary() {
        // 사용자 사전 가져오기
        $dictionary = $this->getCustomDictionary(true);
        
        // 최적화된 사전 데이터 구성
        $optimizedDict = [];
        
        foreach ($dictionary as $entry) {
            $word = $entry['word'];
            $similarWords = $entry['similar_words'];
            
            // 중복 제거 및 정렬
            $similarWords = array_unique($similarWords);
            sort($similarWords);
            
            $optimizedDict[$word] = $similarWords;
        }
        
        // 모델 디렉토리 확인
        if (!file_exists($this->config['model_path'])) {
            mkdir($this->config['model_path'], 0755, true);
        }
        
        // 최적화된 사전 파일 저장
        $dictFile = $this->config['model_path'] . '/custom_dictionary.json';
        file_put_contents($dictFile, json_encode($optimizedDict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return count($dictionary);
    }
    
    /**
     * 문서 템플릿 최적화
     */
    private function trainDocumentTemplates() {
        // 모든 템플릿 가져오기
        $stmt = $this->db->prepare("
            SELECT id, fields, table_structure, learning_data 
            FROM ocr_document_templates
            WHERE is_active = 1
        ");
        
        $stmt->execute();
        $templates = $stmt->fetchAll();
        
        $optimizedCount = 0;
        
        foreach ($templates as $template) {
            $templateId = $template['id'];
            $fields = json_decode($template['fields'], true) ?: [];
            $tableStructure = json_decode($template['table_structure'], true) ?: [];
            $learningData = json_decode($template['learning_data'], true) ?: [];
            
            // 템플릿 최적화
            $optimizedFields = $this->optimizeTemplateFields($fields);
            $optimizedTable = $this->optimizeTableStructure($tableStructure);
            
            // 학습 통계 생성
            $learningStats = $this->generateTemplateStatistics($learningData);
            
            // 최적화된 템플릿 저장
            $stmt = $this->db->prepare("
                UPDATE ocr_document_templates
                SET fields = ?, table_structure = ?, learning_statistics = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $fieldsJson = json_encode($optimizedFields, JSON_UNESCAPED_UNICODE);
            $tableJson = json_encode($optimizedTable, JSON_UNESCAPED_UNICODE);
            $statsJson = json_encode($learningStats, JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([$fieldsJson, $tableJson, $statsJson, $templateId]);
            $optimizedCount++;
        }
        
        return $optimizedCount;
    }
    
    /**
     * 템플릿 필드 최적화
     * @param array $fields 필드 정보
     * @return array 최적화된 필드 정보
     */
    private function optimizeTemplateFields($fields) {
        $optimizedFields = [];
        
        foreach ($fields as $field) {
            $optimizedField = $field;
            
            // 학습 예제가 있는 경우
            if (isset($field['learning_examples']) && is_array($field['learning_examples'])) {
                $examples = $field['learning_examples'];
                
                // 패턴 생성 (정규식)
                if (count($examples) >= 3) {
                    $pattern = $this->generateFieldPattern($examples, $field['type'] ?? 'text');
                    $optimizedField['pattern'] = $pattern;
                }
                
                // 유사어 그룹화 및 정규화
                $optimizedField['similar_words'] = $this->groupSimilarWords($examples);
            }
            
            $optimizedFields[] = $optimizedField;
        }
        
        return $optimizedFields;
    }
    
    /**
     * 테이블 구조 최적화
     * @param array $tableStructure 테이블 구조 정보
     * @return array 최적화된 테이블 구조 정보
     */
    private function optimizeTableStructure($tableStructure) {
        $optimizedTable = $tableStructure;
        
        // 헤더 학습 데이터가 있는 경우
        if (isset($tableStructure['header_learning']) && is_array($tableStructure['header_learning'])) {
            $headerLearning = $tableStructure['header_learning'];
            
            // 열 별 유사어 그룹화
            $optimizedTable['header_similar_words'] = [];
            
            foreach ($headerLearning as $columnIndex => $examples) {
                if (count($examples) >= 2) {
                    $optimizedTable['header_similar_words'][$columnIndex] = $this->groupSimilarWords($examples);
                }
            }
        }
        
        return $optimizedTable;
    }
    
    /**
     * 필드 패턴 생성
     * @param array $examples 예제 목록
     * @param string $fieldType 필드 타입
     * @return string 정규식 패턴
     */
    private function generateFieldPattern($examples, $fieldType) {
        // 필드 타입 별 기본 패턴
        $defaultPatterns = [
            'text' => '.+',
            'number' => '\\d+',
            'date' => '\\d{4}[-.\/]\\d{1,2}[-.\/]\\d{1,2}',
            'amount' => '[\\d,]+원?',
            'phone' => '\\d{2,3}[-\\s]?\\d{3,4}[-\\s]?\\d{4}'
        ];
        
        // 예제가 많지 않으면 기본 패턴 반환
        if (count($examples) < 5) {
            return $defaultPatterns[$fieldType] ?? $defaultPatterns['text'];
        }
        
        // 예제로부터 패턴 추출 (간단한 구현)
        $correctedValues = array_column($examples, 'corrected');
        
        switch ($fieldType) {
            case 'date':
                // 날짜 형식 분석
                return $this->analyzeDateFormat($correctedValues);
                
            case 'number':
                // 숫자 패턴 분석
                return $this->analyzeNumberFormat($correctedValues);
                
            case 'amount':
                // 금액 패턴 분석
                return $this->analyzeAmountFormat($correctedValues);
                
            case 'phone':
                // 전화번호 패턴 분석
                return $this->analyzePhoneFormat($correctedValues);
                
            default:
                // 텍스트 필드는 기본 패턴 사용
                return $defaultPatterns['text'];
        }
    }
    
    /**
     * 날짜 형식 분석
     * @param array $dates 날짜 문자열 목록
     * @return string 정규식 패턴
     */
    private function analyzeDateFormat($dates) {
        $formatCounts = [
            'Y-m-d' => 0,
            'Y/m/d' => 0,
            'Y.m.d' => 0,
            'other' => 0
        ];
        
        foreach ($dates as $date) {
            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $date)) {
                $formatCounts['Y-m-d']++;
            } elseif (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
                $formatCounts['Y/m/d']++;
            } elseif (preg_match('/^\d{4}\.\d{1,2}\.\d{1,2}$/', $date)) {
                $formatCounts['Y.m.d']++;
            } else {
                $formatCounts['other']++;
            }
        }
        
        // 가장 많은 형식 찾기
        arsort($formatCounts);
        $dominantFormat = key($formatCounts);
        
        $patterns = [
            'Y-m-d' => '\\d{4}-\\d{1,2}-\\d{1,2}',
            'Y/m/d' => '\\d{4}/\\d{1,2}/\\d{1,2}',
            'Y.m.d' => '\\d{4}\\.\\d{1,2}\\.\\d{1,2}',
            'other' => '\\d{4}[-.\/]\\d{1,2}[-.\/]\\d{1,2}'
        ];
        
        return $patterns[$dominantFormat];
    }
    
    /**
     * 숫자 형식 분석
     * @param array $numbers 숫자 문자열 목록
     * @return string 정규식 패턴
     */
    private function analyzeNumberFormat($numbers) {
        $min = PHP_INT_MAX;
        $max = 0;
        
        foreach ($numbers as $number) {
            // 숫자만 추출
            $digits = preg_replace('/[^\d]/', '', $number);
            $count = strlen($digits);
            
            $min = min($min, $count);
            $max = max($max, $count);
        }
        
        // 최소값이 설정되지 않은 경우 (숫자가 없는 경우)
        if ($min === PHP_INT_MAX) {
            $min = 1;
            $max = 10;
        }
        
        return "\\d{" . $min . "," . $max . "}";
    }
    
    /**
     * 금액 형식 분석
     * @param array $amounts 금액 문자열 목록
     * @return string 정규식 패턴
     */
    private function analyzeAmountFormat($amounts) {
        $hasCurrency = false;
        
        foreach ($amounts as $amount) {
            if (preg_match('/원|₩|\\\\/', $amount)) {
                $hasCurrency = true;
                break;
            }
        }
        
        return $hasCurrency ? "[\\d,]+(원|₩)?" : "[\\d,]+";
    }
    
    /**
     * 전화번호 형식 분석
     * @param array $phones 전화번호 문자열 목록
     * @return string 정규식 패턴
     */
    private function analyzePhoneFormat($phones) {
        $hasHyphen = false;
        
        foreach ($phones as $phone) {
            if (strpos($phone, '-') !== false) {
                $hasHyphen = true;
                break;
            }
        }
        
        return $hasHyphen ? 
            "\\d{2,3}-\\d{3,4}-\\d{4}" : 
            "\\d{2,3}\\s*\\d{3,4}\\s*\\d{4}";
    }
    
    /**
     * 유사 단어 그룹화
     * @param array $examples 예제 목록
     * @return array 그룹화된 유사 단어
     */
    private function groupSimilarWords($examples) {
        $groups = [];
        
        // 각 예제의 가중치 기반 정렬
        usort($examples, function($a, $b) {
            return ($b['weight'] ?? 1) - ($a['weight'] ?? 1);
        });
        
        // 가장 빈도가 높은 단어를 기준으로 그룹화
        foreach ($examples as $example) {
            $original = $example['original'];
            $corrected = $example['corrected'];
            
            if (!isset($groups[$corrected])) {
                $groups[$corrected] = [];
            }
            
            if (!in_array($original, $groups[$corrected])) {
                $groups[$corrected][] = $original;
            }
        }
        
        return $groups;
    }
    
    /**
     * 템플릿 학습 통계 생성
     * @param array $learningData 학습 데이터
     * @return array 통계 정보
     */
    private function generateTemplateStatistics($learningData) {
        $stats = [
            'total_corrections' => 0,
            'field_corrections' => [],
            'table_corrections' => 0,
            'common_errors' => [],
            'last_update' => time()
        ];
        
        // 통계 계산
        foreach ($learningData as $data) {
            if (isset($data['corrections']) && is_array($data['corrections'])) {
                $corrections = $data['corrections'];
                $stats['total_corrections'] += count($corrections);
                
                foreach ($corrections as $correction) {
                    if (isset($correction['type']) && $correction['type'] === 'field') {
                        $fieldName = $correction['field'];
                        
                        if (!isset($stats['field_corrections'][$fieldName])) {
                            $stats['field_corrections'][$fieldName] = 0;
                        }
                        
                        $stats['field_corrections'][$fieldName]++;
                        
                        // 자주 발생하는 오류 추적
                        if (isset($correction['original']) && isset($correction['corrected'])) {
                            $errorKey = $correction['original'] . ' -> ' . $correction['corrected'];
                            if (!isset($stats['common_errors'][$errorKey])) {
                                $stats['common_errors'][$errorKey] = 0;
                            }
                            
                            $stats['common_errors'][$errorKey]++;
                        }
                    } elseif (isset($correction['type']) && $correction['type'] === 'table_header') {
                        $stats['table_corrections']++;
                    }
                }
            }
        }
        
        // 자주 발생하는 오류 정렬 및 상위 10개만 유지
        arsort($stats['common_errors']);
        $stats['common_errors'] = array_slice($stats['common_errors'], 0, 10, true);
        
        return $stats;
    }
    
    /**
     * 전체 학습 통계 생성
     * @return array 통계 정보
     */
    private function generateLearningStatistics() {
        // 1. 템플릿 통계
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as template_count,
                   SUM(JSON_LENGTH(fields)) as total_fields
            FROM ocr_document_templates
            WHERE is_active = 1
        ");
        
        $stmt->execute();
        $templateStats = $stmt->fetch();
        
        // 2. 사전 통계
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as dict_count,
                   SUM(frequency) as total_frequency
            FROM ocr_dictionary
            WHERE is_active = 1
        ");
        
        $stmt->execute();
        $dictStats = $stmt->fetch();
        
        // 3. 피드백 통계
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as feedback_count
            FROM ocr_feedback
        ");
        
        $stmt->execute();
        $feedbackStats = $stmt->fetch();
        
        // 전체 통계 구성
        return [
            'templates' => [
                'count' => (int)$templateStats['template_count'],
                'fields' => (int)$templateStats['total_fields']
            ],
            'dictionary' => [
                'count' => (int)$dictStats['dict_count'],
                'frequency' => (int)$dictStats['total_frequency']
            ],
            'feedback' => [
                'count' => (int)$feedbackStats['feedback_count']
            ],
            'last_update' => time()
        ];
    }
    
    /**
     * 모델 평가
     * @return array 평가 결과
     */
    public function evaluateModel() {
        // 1. 전체 통계 가져오기
        $stmt = $this->db->prepare("
            SELECT value FROM ocr_system_settings
            WHERE name = 'learning_statistics'
        ");
        
        $stmt->execute();
        $statsRow = $stmt->fetch();
        
        $stats = $statsRow ? json_decode($statsRow['value'], true) : [];
        
        if (empty($stats)) {
            $stats = $this->generateLearningStatistics();
            
            // 통계 저장
            $stmt = $this->db->prepare("
                INSERT INTO ocr_system_settings (name, value) 
                VALUES ('learning_statistics', ?)
                ON DUPLICATE KEY UPDATE value = ?
            ");
            
            $statsJson = json_encode($stats, JSON_UNESCAPED_UNICODE);
            $stmt->execute([$statsJson, $statsJson]);
        }
        
        // 2. 마지막 학습 시간 가져오기
        $stmt = $this->db->prepare("
            SELECT value FROM ocr_system_settings
            WHERE name = 'last_training_time'
        ");
        
        $stmt->execute();
        $lastTrainingRow = $stmt->fetch();
        $lastTraining = $lastTrainingRow ? date('Y-m-d H:i:s', (int)$lastTrainingRow['value']) : '없음';
        
        // 3. 자주 수정되는 필드 가져오기
        $frequentlyCorrectFields = $this->getFrequentlyCorrectedFields();
        
        // 4. 정확도 추정
        $accuracy = $this->estimateAccuracy($stats);
        
        // 평가 결과 구성
        return [
            'template_count' => $stats['templates']['count'] ?? 0,
            'dictionary_size' => $stats['dictionary']['count'] ?? 0,
            'correction_counts' => $stats['feedback']['count'] ?? 0,
            'frequently_corrected_fields' => $frequentlyCorrectFields,
            'accuracy_estimate' => $accuracy,
            'last_training' => $lastTraining
        ];
    }
    
    /**
     * 자주 수정되는 필드 가져오기
     * @return array 필드 목록
     */
    private function getFrequentlyCorrectedFields() {
        // 모든 템플릿의 통계 분석
        $stmt = $this->db->prepare("
            SELECT learning_statistics FROM ocr_document_templates
            WHERE is_active = 1 AND learning_statistics IS NOT NULL
        ");
        
        $stmt->execute();
        $templates = $stmt->fetchAll();
        
        $fieldCounts = [];
        
        foreach ($templates as $template) {
            $stats = json_decode($template['learning_statistics'], true) ?: [];
            
            if (isset($stats['field_corrections']) && is_array($stats['field_corrections'])) {
                foreach ($stats['field_corrections'] as $field => $count) {
                    if (!isset($fieldCounts[$field])) {
                        $fieldCounts[$field] = 0;
                    }
                    $fieldCounts[$field] += $count;
                }
            }
        }
        
        // 정렬 및 상위 5개 선택
        arsort($fieldCounts);
        return array_slice($fieldCounts, 0, 5, true);
    }
    
    /**
     * 정확도 추정
     * @param array $stats 학습 통계
     * @return array 정확도 추정치
     */
    private function estimateAccuracy($stats) {
        // 초기 정확도 기준 (Clova OCR 자체 정확도를 약 85%로 가정)
        $baseAccuracy = 85.0;
        
        // 사전 크기 및 활용도에 따른 보정
        $dictionaryFactor = 0;
        if (isset($stats['dictionary']) && $stats['dictionary']['count'] > 0) {
            // 사전 크기에 따른 보정 (최대 3%)
            $dictionaryFactor += min($stats['dictionary']['count'] / 100, 3.0);
            
            // 사용 빈도에 따른 보정 (최대 2%)
            $freqFactor = $stats['dictionary']['frequency'] / $stats['dictionary']['count'];
            $dictionaryFactor += min($freqFactor / 50, 2.0);
        }
        
        // 템플릿 활용도에 따른 보정
        $templateFactor = 0;
        if (isset($stats['templates']) && $stats['templates']['count'] > 0) {
            // 템플릿 수에 따른 보정 (최대 5%)
            $templateFactor += min($stats['templates']['count'] * 0.5, 5.0);
            
            // 필드 수에 따른 보정 (최대 3%)
            $fieldFactor = $stats['templates']['fields'] / $stats['templates']['count'];
            $templateFactor += min($fieldFactor * 0.3, 3.0);
        }
        
        // 피드백 활용도에 따른 보정
        $feedbackFactor = 0;
        if (isset($stats['feedback']) && $stats['feedback']['count'] > 0) {
            // 피드백 수에 따른 보정 (최대 4%)
            $feedbackFactor += min($stats['feedback']['count'] / 50, 4.0);
        }
        
        // 최종 정확도 계산
        $estimatedAccuracy = $baseAccuracy + $dictionaryFactor + $templateFactor + $feedbackFactor;
        
        // 최대 99%로 제한
        $estimatedAccuracy = min($estimatedAccuracy, 99.0);
        
        return [
            'estimated_accuracy' => round($estimatedAccuracy, 2),
            'base_accuracy' => $baseAccuracy,
            'dictionary_improvement' => round($dictionaryFactor, 2),
            'template_improvement' => round($templateFactor, 2),
            'feedback_improvement' => round($feedbackFactor, 2)
        ];
    }
}
