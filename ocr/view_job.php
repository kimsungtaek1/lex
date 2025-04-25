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
    <style>
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        .progress {
            height: 20px;
            margin-bottom: 10px;
        }
        .nav-pills .nav-link.active {
            background-color: #007bff;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .file-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        .file-info {
            flex-grow: 1;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .log-entry {
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
            font-family: monospace;
        }
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .result-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .badge-pill {
            padding: 5px 10px;
            border-radius: 15px;
        }
        .result-preview {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        .table-preview {
            width: 100%;
            overflow-x: auto;
            margin-top: 10px;
        }
        .feedback-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        #autoRefresh {
            margin-left: 10px;
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
                    
                    <!-- 결과 탭 -->
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
                                        <div class="list-group">
                                            <?php foreach ($jobResults['files'] as $idx => $result): ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1"><?php echo h(basename($result['original_file'])); ?></h5>
                                                        <small class="text-muted">결과 #<?php echo h($idx + 1); ?></small>
                                                    </div>
                                                    <div class="mt-3">
                                                        <?php if (!empty($result['text_file'])): ?>
                                                            <button class="btn btn-sm btn-outline-primary me-2 view-text-btn" 
                                                                    data-path="<?php echo h($result['text_file']); ?>">
                                                                <i class="bi bi-file-text me-1"></i>텍스트 결과
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($result['table_file'])): ?>
                                                            <button class="btn btn-sm btn-outline-info me-2 view-table-btn" 
                                                                    data-path="<?php echo h($result['table_file']); ?>">
                                                                <i class="bi bi-table me-1"></i>테이블 결과
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($result['json_file'])): ?>
                                                            <button class="btn btn-sm btn-outline-secondary me-2 view-json-btn" 
                                                                    data-path="<?php echo h($result['json_file']); ?>">
                                                                <i class="bi bi-code me-1"></i>JSON 데이터
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <a href="download.php?file=<?php echo urlencode($result['text_file']); ?>" 
                                                           class="btn btn-sm btn-outline-success me-2">
                                                            <i class="bi bi-download me-1"></i>다운로드
                                                        </a>
                                                        
                                                        <button class="btn btn-sm btn-outline-primary provide-feedback-btn"
                                                                data-file-id="<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>"
                                                                data-job-id="<?php echo h($jobId); ?>">
                                                            <i class="bi bi-chat-dots me-1"></i>피드백 제공
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- 피드백 폼 -->
                                                    <div class="feedback-form" id="feedback-form-<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>">
                                                        <h6>피드백 제공</h6>
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
                                                            <button type="button" class="btn btn-sm btn-primary submit-feedback-btn" 
                                                                    data-file-id="<?php echo h($jobDetails['files'][$idx]['id'] ?? ''); ?>"
                                                                    data-job-id="<?php echo h($jobId); ?>">
                                                                피드백 저장
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-secondary ms-2 cancel-feedback-btn">
                                                                취소
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="download_all.php?job_id=<?php echo h($jobId); ?>" class="btn btn-success">
                                            <i class="bi bi-download me-1"></i>모든 결과 다운로드 (ZIP)
                                        </a>
                                    </div>
                                <?php endif; ?>
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
    <script>
    $(document).ready(function() {
        // 자동 새로고침 기능
        const autoRefreshCheckbox = $('#autoRefresh');
        let refreshInterval;
        
        if (autoRefreshCheckbox.is(':checked')) {
            startAutoRefresh();
        }
        
        autoRefreshCheckbox.change(function() {
            if ($(this).is(':checked')) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        function startAutoRefresh() {
            refreshInterval = setInterval(refreshJobStatus, 5000); // 5초마다 갱신
        }
        
        function stopAutoRefresh() {
            clearInterval(refreshInterval);
        }
        
        function refreshJobStatus() {
            const jobId = <?php echo h($jobId); ?>;
            
            $.ajax({
                url: 'ajax_job_status.php',
                type: 'GET',
                data: { job_id: jobId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const job = response.progress.job;
                        const status = job.status;
                        
                        // 진행률 업데이트
                        $('#progressBar').width(job.progress + '%');
                        $('#progressBar').text(job.progress + '%');
                        $('#processedFiles').text(job.processed_files);
                        $('#lastUpdated').text(new Date().toLocaleString());
                        
                        // 작업이 완료되면 새로고침 중지
                        if (status !== 'processing' && status !== 'queued') {
                            stopAutoRefresh();
                            autoRefreshCheckbox.prop('checked', false);
                            location.reload(); // 페이지 새로고침
                        }
                    }
                }
            });
        }
        
        // 결과 보기 버튼 클릭
        $('.view-result-btn').click(function() {
            const fileId = $(this).data('file-id');
            const textPath = $(this).data('text-path');
            const jsonPath = $(this).data('json-path');
            const tablePath = $(this).data('table-path');
            
            // 텍스트 결과 로드
            if (textPath) {
                loadTextResult(textPath);
            }
            
            // 탭 전환
            $('#resultPreview').show();
            $('#results-tab').tab('show');
        });
        
        // 텍스트 결과 보기 버튼
        $('.view-text-btn').click(function() {
            const path = $(this).data('path');
            loadTextResult(path);
        });
        
        // JSON 결과 보기 버튼
        $('.view-json-btn').click(function() {
            const path = $(this).data('path');
            loadJsonResult(path);
        });
        
        // 테이블 결과 보기 버튼
        $('.view-table-btn').click(function() {
            const path = $(this).data('path');
            loadTableResult(path);
        });
        
        // 미리보기 닫기 버튼
        $('#closePreview').click(function() {
            $('#resultPreview').hide();
            $('#textPreview, #jsonPreview, #tablePreview').hide();
        });
        
        // 텍스트 결과 로드
        function loadTextResult(path) {
            $.ajax({
                url: 'ajax_get_file_content.php',
                type: 'GET',
                data: { path: path },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#previewTitle').text('텍스트 결과');
                        $('#textPreview').html(response.content.replace(/\n/g, '<br>')).show();
                        $('#jsonPreview, #tablePreview').hide();
                        $('#resultPreview').show();
                    } else {
                        alert('파일을 로드할 수 없습니다: ' + response.message);
                    }
                },
                error: function() {
                    alert('파일 로드 중 오류가 발생했습니다.');
                }
            });
        }
        
        // JSON 결과 로드
        function loadJsonResult(path) {
            $.ajax({
                url: 'ajax_get_file_content.php',
                type: 'GET',
                data: { path: path },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#previewTitle').text('JSON 데이터');
                        try {
                            const jsonObj = JSON.parse(response.content);
                            const formattedJson = JSON.stringify(jsonObj, null, 4);
                            $('#jsonPreview').text(formattedJson).show();
                        } catch (e) {
                            $('#jsonPreview').text(response.content).show();
                        }
                        $('#textPreview, #tablePreview').hide();
                        $('#resultPreview').show();
                    } else {
                        alert('파일을 로드할 수 없습니다: ' + response.message);
                    }
                },
                error: function() {
                    alert('파일 로드 중 오류가 발생했습니다.');
                }
            });
        }
        
        // 테이블 결과 로드
        function loadTableResult(path) {
            $.ajax({
                url: 'ajax_get_file_content.php',
                type: 'GET',
                data: { path: path, raw: true },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#previewTitle').text('테이블 결과');
                        $('#tablePreview').html(response.content).show();
                        $('#textPreview, #jsonPreview').hide();
                        $('#resultPreview').show();
                    } else {
                        alert('파일을 로드할 수 없습니다: ' + response.message);
                    }
                },
                error: function() {
                    alert('파일 로드 중 오류가 발생했습니다.');
                }
            });
        }
        
        // 작업 취소 버튼
        $('#cancelJobBtn').click(function() {
            if (confirm('정말로 이 작업을 취소하시겠습니까?')) {
                const jobId = $(this).data('job-id');
                
                $.ajax({
                    url: 'ajax_cancel_job.php',
                    type: 'POST',
                    data: { job_id: jobId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('작업이 취소되었습니다.');
                            location.reload();
                        } else {
                            alert('작업 취소 중 오류가 발생했습니다: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('요청 중 오류가 발생했습니다.');
                    }
                });
            }
        });
        
        // 작업 삭제 버튼
        $('#deleteJobBtn').click(function() {
            if (confirm('정말로 이 작업을 삭제하시겠습니까? 모든 결과 파일이 삭제됩니다.')) {
                const jobId = $(this).data('job-id');
                
                $.ajax({
                    url: 'ajax_delete_job.php',
                    type: 'POST',
                    data: { job_id: jobId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('작업이 삭제되었습니다.');
                            window.location.href = 'jobs.php';
                        } else {
                            alert('작업 삭제 중 오류가 발생했습니다: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('요청 중 오류가 발생했습니다.');
                    }
                });
            }
        });
        
        // 피드백 제공 버튼
        $('.provide-feedback-btn').click(function() {
            const fileId = $(this).data('file-id');
            $('#feedback-form-' + fileId).toggle();
        });
        
        // 피드백 취소 버튼
        $('.cancel-feedback-btn').click(function() {
            $(this).closest('.feedback-form').hide();
        });
        
        // 피드백 제출 버튼
        $('.submit-feedback-btn').click(function() {
            const fileId = $(this).data('file-id');
            const jobId = $(this).data('job-id');
            const form = $('#feedback-form-' + fileId);
            
            const fieldName = form.find('.field-name').val();
            const originalText = form.find('.original-text').val();
            const correctedText = form.find('.corrected-text').val();
            
            if (!fieldName || !originalText || !correctedText) {
                alert('모든 필드를 입력해주세요.');
                return;
            }
            
            const feedbackData = {
                job_id: jobId,
                file_id: fileId,
                corrections: [
                    {
                        type: 'field',
                        field: fieldName,
                        original: originalText,
                        corrected: correctedText
                    }
                ]
            };
            
            $.ajax({
                url: 'ajax_save_feedback.php',
                type: 'POST',
                data: JSON.stringify(feedbackData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('피드백이 저장되었습니다. 향후 OCR 인식률 향상에 사용됩니다.');
                        form.hide();
                        form.find('.field-name, .original-text, .corrected-text').val('');
                    } else {
                        alert('피드백 저장 중 오류가 발생했습니다: ' + response.message);
                    }
                },
                error: function() {
                    alert('요청 중 오류가 발생했습니다.');
                }
            });
        });
    });
    </script>
</body>
</html>