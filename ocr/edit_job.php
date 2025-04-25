<?php
/**
 * OCR 인식률 향상 시스템 - 작업 편집
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'process_monitor.php';
require_once 'document_learning.php';
require_once 'utils.php';

// 작업 ID
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

// 프로세스 모니터 초기화
$processMonitor = new OCRProcessMonitor();

// 작업 상세 정보 가져오기
$jobDetails = $processMonitor->getJobDetails($jobId);

// 작업이 존재하지 않는 경우
if (!$jobDetails) {
    echo "<script>alert('존재하지 않는 작업입니다.'); history.back();</script>";
    exit;
}

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 사용 가능한 템플릿 목록 가져오기
$templates = $learningSystem->getTemplates(true);

// 작업 정보
$job = $jobDetails['job'];
$jobOptions = json_decode($job['options'], true) ?: [];

// 편집 가능한 상태인지 확인 (완료되거나 취소된 작업은 편집 불가)
$isEditable = !in_array($job['status'], ['completed', 'cancelled']);

// 오류 및 성공 메시지 초기화
$errorMessage = null;
$successMessage = null;

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 편집 불가능한 작업인 경우
        if (!$isEditable) {
            throw new Exception("이 작업은 현재 편집할 수 없습니다.");
        }
        
        // 작업 이름 검증
        if (empty($_POST['job_name'])) {
            throw new Exception("작업 이름은 필수입니다.");
        }
        
        $jobName = trim($_POST['job_name']);
        if (!preg_match('/^[A-Za-z0-9가-힣\s\-_]{1,100}$/', $jobName)) {
            throw new Exception("작업 이름은 한글, 영문, 숫자, 공백, 하이픈, 언더스코어만 포함할 수 있습니다.");
        }
        
        // 문서 유형 (템플릿)
        $documentType = !empty($_POST['document_type']) ? $_POST['document_type'] : null;
        
        // 옵션 설정
        $options = [
            'preprocess' => isset($_POST['options']['preprocess']),
            'enhance_table' => isset($_POST['options']['enhance_table']),
            'apply_custom_dict' => isset($_POST['options']['apply_custom_dict']),
            'document_type' => $documentType
        ];
        
        // DB 연결
        $db = getDB();
        
        // 작업 업데이트
        $stmt = $db->prepare("
            UPDATE ocr_jobs 
            SET name = ?, options = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $optionsJson = json_encode($options);
        $stmt->execute([$jobName, $optionsJson, $jobId]);
        
        // 작업 로그 추가
        $processMonitor->addJobLog($jobId, 'info', "작업 정보가 수정되었습니다.");
        
        $successMessage = "작업 정보가 성공적으로 업데이트되었습니다.";
        
        // 작업 정보 다시 로드
        $jobDetails = $processMonitor->getJobDetails($jobId);
        $job = $jobDetails['job'];
        $jobOptions = json_decode($job['options'], true) ?: [];
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// 작업 상태별 색상 정의
function getStatusColor($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'processing': return 'primary';
        case 'queued': return 'info';
        case 'failed': return 'danger';
        case 'cancelled': return 'warning';
        default: return 'secondary';
    }
}

// 작업 상태 텍스트 정의
function getStatusText($status) {
    switch ($status) {
        case 'completed': return '완료됨';
        case 'processing': return '처리 중';
        case 'queued': return '대기 중';
        case 'failed': return '실패';
        case 'cancelled': return '취소됨';
        default: return $status;
    }
}

// 페이지 제목
$pageTitle = '작업 편집: ' . htmlspecialchars($job['name']);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- 커스텀 스타일 -->
    <style>
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .file-icon {
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .file-info {
            flex-grow: 1;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .validation-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <h1 class="mb-4">
            <?php echo h($pageTitle); ?>
            <span class="badge bg-<?php echo getStatusColor($job['status']); ?> ms-2">
                <?php echo getStatusText($job['status']); ?>
            </span>
        </h1>
        
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <?php if (!$isEditable): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        이 작업은 이미 <?php echo getStatusText($job['status']); ?> 상태이므로 편집할 수 없습니다.
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo h($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?php echo h($successMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- 작업 편집 폼 -->
                <form method="post" id="editJobForm">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-pencil-square me-2"></i>작업 정보 편집
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="job_name" class="form-label">작업 이름 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="job_name" name="job_name" 
                                       value="<?php echo h($job['name']); ?>" 
                                       required <?php echo $isEditable ? '' : 'disabled'; ?>>
                                <div class="form-text">작업 식별을 위한 고유한 이름입니다.</div>
                                <div class="validation-error" id="jobNameError"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_type" class="form-label">문서 유형 (템플릿)</label>
                                <select class="form-select" id="document_type" name="document_type" <?php echo $isEditable ? '' : 'disabled'; ?>>
                                    <option value="">자동 감지</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo h($template['id']); ?>"
                                            <?php echo (isset($jobOptions['document_type']) && $jobOptions['document_type'] == $template['id']) ? 'selected' : ''; ?>>
                                            <?php echo h($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    특정 문서 유형을 선택하면 OCR 인식률이 향상됩니다.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">OCR 향상 옵션</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="preprocessImages" 
                                           name="options[preprocess]" 
                                           <?php echo (isset($jobOptions['preprocess']) && $jobOptions['preprocess']) ? 'checked' : ''; ?>
                                           <?php echo $isEditable ? '' : 'disabled'; ?>>
                                    <label class="form-check-label" for="preprocessImages">
                                        이미지 전처리 적용
                                    </label>
                                    <div class="form-text">흐릿한 이미지를 선명하게 개선하고 텍스트 인식률을 높입니다.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enhanceTable" 
                                           name="options[enhance_table]" 
                                           <?php echo (isset($jobOptions['enhance_table']) && $jobOptions['enhance_table']) ? 'checked' : ''; ?>
                                           <?php echo $isEditable ? '' : 'disabled'; ?>>
                                    <label class="form-check-label" for="enhanceTable">
                                        테이블 인식 강화
                                    </label>
                                    <div class="form-text">문서 내 테이블 구조를 더 정확하게 인식합니다.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="applyCustomDict" 
                                           name="options[apply_custom_dict]" 
                                           <?php echo (isset($jobOptions['apply_custom_dict']) && $jobOptions['apply_custom_dict']) ? 'checked' : ''; ?>
                                           <?php echo $isEditable ? '' : 'disabled'; ?>>
                                    <label class="form-check-label" for="applyCustomDict">
                                        사용자 정의 사전 적용
                                    </label>
                                    <div class="form-text">자주 오인식되는 단어를 사용자 사전을 통해 자동 보정합니다.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 작업 파일 목록 (읽기 전용) -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <i class="bi bi-file-earmark me-2"></i>작업 파일 목록
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">파일 목록은 읽기 전용입니다. 파일을 변경하려면 새 작업을 생성하세요.</p>
                            
                            <?php if (empty($jobDetails['files'])): ?>
                                <div class="alert alert-info">파일 정보가 없습니다.</div>
                            <?php else: ?>
                                <div class="file-list">
                                    <?php foreach ($jobDetails['files'] as $file): ?>
                                        <div class="file-item">
                                            <div class="file-icon">
                                                <?php if (in_array($file['status'], ['completed', 'processing'])): ?>
                                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                                <?php elseif ($file['status'] === 'failed'): ?>
                                                    <i class="bi bi-file-earmark-x text-danger"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-file-earmark text-secondary"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="file-info">
                                                <h6><?php echo h(basename($file['file_path'])); ?></h6>
                                                <div>
                                                    <span class="status-indicator bg-<?php echo getStatusColor($file['status']); ?>"></span>
                                                    <span class="text-<?php echo getStatusColor($file['status']); ?>">
                                                        <?php echo getStatusText($file['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4 mt-4">
                        <a href="jobs.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>작업 목록으로
                        </a>
                        
                        <div>
                            <a href="view_job.php?id=<?php echo h($jobId); ?>" class="btn btn-outline-primary me-2">
                                <i class="bi bi-eye me-1"></i>작업 상세 보기
                            </a>
                            
                            <?php if ($isEditable): ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>변경사항 저장
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 푸터 -->
    <?php include 'footer.php'; ?>

    <!-- JavaScript 추가 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editJobForm = document.getElementById('editJobForm');
            const jobNameInput = document.getElementById('job_name');
            const jobNameError = document.getElementById('jobNameError');
            
            // 폼 유효성 검사
            editJobForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // 작업 이름 검증
                const jobName = jobNameInput.value.trim();
                if (!jobName) {
                    jobNameError.textContent = '작업 이름은 필수입니다.';
                    isValid = false;
                } else if (!/^[A-Za-z0-9가-힣\s\-_]{1,100}$/.test(jobName)) {
                    jobNameError.textContent = '작업 이름은 한글, 영문, 숫자, 공백, 하이픈, 언더스코어만 포함할 수 있습니다.';
                    isValid = false;
                } else {
                    jobNameError.textContent = '';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // 입력 필드 변경 시 유효성 검사
            jobNameInput.addEventListener('input', function() {
                const jobName = this.value.trim();
                if (!jobName) {
                    jobNameError.textContent = '작업 이름은 필수입니다.';
                } else if (!/^[A-Za-z0-9가-힣\s\-_]{1,100}$/.test(jobName)) {
                    jobNameError.textContent = '작업 이름은 한글, 영문, 숫자, 공백, 하이픈, 언더스코어만 포함할 수 있습니다.';
                } else {
                    jobNameError.textContent = '';
                }
            });
        });
    </script>
</body>
</html>