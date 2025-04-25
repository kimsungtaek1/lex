<?php
/**
 * OCR 인식률 향상 시스템 - 작업 상세 보기
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

// 작업 결과 가져오기
$jobResults = $processMonitor->getJobResults($jobId);

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

// 로그 레벨 색상 정의
function getLogLevelColor($level) {
    switch ($level) {
        case 'info': return 'info';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        default: return 'secondary';
    }
}

// 페이지 제목
$pageTitle = '작업 상세 정보: ' . htmlspecialchars($jobDetails['job']['name']);
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
    <link rel="stylesheet" href="css/view_job.css">
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <h1 class="mb-4">
            <?php echo h($pageTitle); ?>
            <span class="badge bg-<?php echo getStatusColor($jobDetails['job']['status']); ?> ms-2">
                <?php echo getStatusText($jobDetails['job']['status']); ?>
            </span>
        </h1>
        
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- 작업 진행 정보 -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-info-circle me-2"></i>작업 정보</span>
                        
                        <?php if ($jobDetails['job']['status'] === 'processing' || $jobDetails['job']['status'] === 'queued'): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                            <label class="form-check-label text-white" for="autoRefresh">자동 새로고침</label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>작업 ID:</strong> <?php echo h($jobDetails['job']['id']); ?></p>
                                <p><strong>생성 일시:</strong> <?php echo h(date('Y-m-d H:i:s', strtotime($jobDetails['job']['created_at']))); ?></p>
                                <p><strong>최종 업데이트:</strong> 
                                    <span id="lastUpdated">
                                        <?php echo $jobDetails['job']['updated_at'] ? h(date('Y-m-d H:i:s', strtotime($jobDetails['job']['updated_at']))) : '-'; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>처리 파일:</strong> <span id="processedFiles"><?php echo h($jobDetails['job']['processed_files']); ?></span> / <?php echo h($jobDetails['job']['total_files']); ?></p>
                                <p><strong>문서 유형:</strong> 
                                    <?php
                                    $options = json_decode($jobDetails['job']['options'], true);
                                    echo !empty($options['document_type']) ? h($options['document_type']) : '자동 감지';
                                    ?>
                                </p>
                                <div class="progress">
                                    <div id="progressBar" class="progress-bar bg-<?php echo getStatusColor($jobDetails['job']['status']); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo h($jobDetails['job']['progress']); ?>%" 
                                         aria-valuenow="<?php echo h($jobDetails['job']['progress']); ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo h($jobDetails['job']['progress']); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 탭 네비게이션 -->
                <ul class="nav nav-pills mb-3" id="jobTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab" aria-controls="files" aria-selected="true">
                            <i class="bi bi-file-earmark me-1"></i>파일 목록
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab" aria-controls="results" aria-selected="false">
                            <i class="bi bi-card-text me-1"></i>결과
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="false">
                            <i class="bi bi-terminal me-1"></i>로그
                        </button>
                    </li>
                </ul>
                
                <!-- 탭 컨텐츠 -->
                <div class="tab-content">
                    <!-- 파일 목록 탭 -->
                    <div class="tab-pane fade show active" id="files" role="tabpanel" aria-labelledby="files-tab">
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="bi bi-file-earmark me-2"></i>처리 파일 목록
                            </div>
                            <div class="card-body">
                                <div id="filesList">
                                    <?php if (empty($jobDetails['files'])): ?>
                                        <div class="alert alert-info">파일 정보가 없습니다.</div>
                                    <?php else: ?>
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
                                                    <h5><?php echo h(basename($file['file_path'])); ?></h5>
                                                    <div>
                                                        <span class="status-indicator bg-<?php echo getStatusColor($file['status']); ?>"></span>
                                                        <span class="text-<?php echo getStatusColor($file['status']); ?>">
                                                            <?php echo getStatusText($file['status']); ?>
                                                        </span>
                                                        <span class="text-muted ms-2">
                                                            마지막 업데이트: 
                                                            <?php echo $file['updated_at'] ? h(date('Y-m-d H:i:s', strtotime($file['updated_at']))) : h(date('Y-m-d H:i:s', strtotime($file['created_at']))); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <?php if ($file['status'] === 'completed' && !empty($file['result_path'])): ?>
                                                    <?php
                                                    $resultPaths = json_decode($file['result_path'], true);
                                                    $fileId = $file['id'];
                                                    ?>
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary view-result-btn" 
                                                                data-file-id="<?php echo h($fileId); ?>"
                                                                data-text-path="<?php echo h($resultPaths['text'] ?? ''); ?>"
                                                                data-json-path="<?php echo h($resultPaths['json'] ?? ''); ?>"
                                                                data-table-path="<?php echo h($resultPaths['table'] ?? ''); ?>">
                                                            <i class="bi bi-eye me-1"></i>결과 보기
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- OCR 결과 탭 개선 코드 -->
<div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
    <div class="card">
        <div class="card-header bg-success text-white">
            <i class="bi bi-card-text me-2"></i>OCR 처리 결과
        </div>
        <div class="card-body">
            <?php if ($jobDetails['job']['status'] !== 'completed'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>작업이 완료되면 결과를 확인할 수 있습니다.
                </div>
            <?php elseif (empty($jobResults['files'])): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>처리된 결과가 없습니다.
                </div>
            <?php else: ?>
                <div class="result-container">
                    <?php foreach ($jobResults['files'] as $idx => $result): ?>
                        <div class="card mb-4 result-card" id="result-card-<?php echo h($idx); ?>">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo h(basename($result['original_file'])); ?></h5>
                                    <div>
                                        <span class="badge bg-primary">결과 #<?php echo h($idx + 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- 결과 뷰어 컨테이너 -->
                                <div class="row">
                                    <!-- 왼쪽: 제어 버튼 패널 -->
                                    <div class="col-md-3 mb-3">
                                        <div class="list-group">
                                            <button class="list-group-item list-group-item-action active text-view-btn" 
                                                    data-idx="<?php echo h($idx); ?>" 
                                                    data-path="<?php echo h($result['text_file']); ?>">
                                                <i class="bi bi-file-text me-2"></i>텍스트 결과
                                            </button>
                                            
                                            <?php if (!empty($result['table_file'])): ?>
                                            <button class="list-group-item list-group-item-action table-view-btn" 
                                                    data-idx="<?php echo h($idx); ?>" 
                                                    data-path="<?php echo h($result['table_file']); ?>">
                                                <i class="bi bi-table me-2"></i>테이블 결과
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($result['json_file'])): ?>
                                            <button class="list-group-item list-group-item-action json-view-btn" 
                                                    data-idx="<?php echo h($idx); ?>" 
                                                    data-path="<?php echo h($result['json_file']); ?>">
                                                <i class="bi bi-code me-2"></i>JSON 데이터
                                            </button>
                                            <?php endif; ?>
                                            
                                            <div class="list-group-item">
                                                <a href="download.php?file=<?php echo urlencode($result['text_file']); ?>" 
                                                   class="btn btn-sm btn-success w-100">
                                                    <i class="bi bi-download me-1"></i>다운로드
                                                </a>
                                            </div>
                                            
                                            <div class="list-group-item">
                                                <button class="btn btn-sm btn-primary w-100 provide-feedback-btn"
                                                        data-file-id="<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>"
                                                        data-job-id="<?php echo h($jobId); ?>">
                                                    <i class="bi bi-chat-dots me-1"></i>피드백 제공
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 오른쪽: 결과 표시 영역 -->
                                    <div class="col-md-9">
                                        <!-- 텍스트 결과 영역 -->
                                        <div class="content-viewer text-viewer-<?php echo h($idx); ?>" style="display: block;">
                                            <div class="p-3 border rounded bg-light">
                                                <div class="text-content" style="white-space: pre-wrap; font-family: monospace; max-height: 500px; overflow-y: auto;">
                                                    <!-- 텍스트 내용은 JavaScript로 로드됨 -->
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">로딩중...</span>
                                                    </div>
                                                    <span class="ms-2">텍스트 결과 로딩 중...</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 테이블 결과 영역 -->
                                        <div class="content-viewer table-viewer-<?php echo h($idx); ?>" style="display: none;">
                                            <div class="p-3 border rounded bg-light">
                                                <div class="table-content" style="max-height: 500px; overflow-y: auto;">
                                                    <!-- 테이블 내용은 JavaScript로 로드됨 -->
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">로딩중...</span>
                                                    </div>
                                                    <span class="ms-2">테이블 결과 로딩 중...</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- JSON 결과 영역 -->
                                        <div class="content-viewer json-viewer-<?php echo h($idx); ?>" style="display: none;">
                                            <div class="p-3 border rounded bg-light">
                                                <div class="json-content" style="white-space: pre-wrap; font-family: monospace; max-height: 500px; overflow-y: auto;">
                                                    <!-- JSON 내용은 JavaScript로 로드됨 -->
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">로딩중...</span>
                                                    </div>
                                                    <span class="ms-2">JSON 데이터 로딩 중...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 피드백 폼 -->
                                <div class="feedback-form mt-3" id="feedback-form-<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>" style="display: none;">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">피드백 제공</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="small text-muted">오인식된 내용과 올바른 내용을 입력하세요. 이 피드백은 향후 OCR 인식률 향상에 사용됩니다.</p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">필드 이름</label>
                                                <input type="text" class="form-control field-name" placeholder="예: 금액, 날짜, 상품명">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">오인식된 텍스트</label>
                                                    <input type="text" class="form-control original-text" placeholder="잘못 인식된 텍스트">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">올바른 텍스트</label>
                                                    <input type="text" class="form-control corrected-text" placeholder="실제 올바른 텍스트">
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-primary submit-feedback-btn" 
                                                        data-file-id="<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>"
                                                        data-job-id="<?php echo h($jobId); ?>">
                                                    <i class="bi bi-check-circle me-1"></i>피드백 저장
                                                </button>
                                                <button type="button" class="btn btn-secondary ms-2 cancel-feedback-btn">
                                                    <i class="bi bi-x-circle me-1"></i>취소
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3">
                    <a href="download_all.php?job_id=<?php echo h($jobId); ?>" class="btn btn-success">
                        <i class="bi bi-download me-1"></i>모든 결과 다운로드 (ZIP)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
                        
                        <!-- 결과 미리보기 모달 -->
                        <div class="mt-3">
                            <div id="resultPreview" style="display: none;">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span id="previewTitle">결과 미리보기</span>
                                            <button type="button" class="btn-close" id="closePreview" aria-label="Close"></button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- 텍스트 미리보기 -->
                                        <div id="textPreview" class="result-preview" style="display: none;"></div>
                                        
                                        <!-- JSON 미리보기 -->
                                        <div id="jsonPreview" class="result-preview" style="display: none;"></div>
                                        
                                        <!-- 테이블 미리보기 -->
                                        <div id="tablePreview" class="table-preview" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 로그 탭 -->
                    <div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="logs-tab">
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="bi bi-terminal me-2"></i>작업 로그
                            </div>
                            <div class="card-body">
                                <div class="log-container">
                                    <?php if (empty($jobDetails['logs'])): ?>
                                        <div class="alert alert-info">로그 정보가 없습니다.</div>
                                    <?php else: ?>
                                        <?php foreach ($jobDetails['logs'] as $log): ?>
                                            <div class="log-entry">
                                                <span class="text-muted"><?php echo h(date('Y-m-d H:i:s', strtotime($log['created_at']))); ?></span>
                                                <span class="badge bg-<?php echo getLogLevelColor($log['level']); ?> ms-1"><?php echo h($log['level']); ?></span>
                                                <span><?php echo h($log['message']); ?></span>
                                                <?php if (!empty($log['context'])): ?>
                                                    <pre class="mt-1 ms-4 small"><?php echo h(json_encode(json_decode($log['context']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 작업 제어 버튼 -->
                <div class="mt-4 d-flex justify-content-between">
                    <a href="jobs.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>목록으로 돌아가기
                    </a>
                    
                    <div>
                        <?php if ($jobDetails['job']['status'] === 'processing' || $jobDetails['job']['status'] === 'queued'): ?>
                            <button type="button" id="cancelJobBtn" class="btn btn-warning me-2" data-job-id="<?php echo h($jobId); ?>">
                                <i class="bi bi-x-circle me-1"></i>작업 취소
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" id="deleteJobBtn" class="btn btn-danger" data-job-id="<?php echo h($jobId); ?>">
                            <i class="bi bi-trash me-1"></i>작업 삭제
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 푸터 -->
    <?php include 'footer.php'; ?>

    <!-- JavaScript 추가 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/view_job.js"></script>
</body>
</html>