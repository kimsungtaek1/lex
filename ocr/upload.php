<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * OCR 인식률 향상 시스템 - 파일 업로드 및 OCR 처리 페이지
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'process_monitor.php';
require_once 'document_learning.php';
require_once 'utils.php';

// 안전한 파일 업로드 검증
function validateUploadedFile($file) {
    // 업로드 오류 확인
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '파일이 PHP 설정 upload_max_filesize를 초과합니다.',
            UPLOAD_ERR_FORM_SIZE => '파일이 폼에 지정된 MAX_FILE_SIZE를 초과합니다.',
            UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장에 의해 업로드가 중지되었습니다.'
        ];
        
        $errorMessage = isset($errorMessages[$file['error']]) ? 
            $errorMessages[$file['error']] : '알 수 없는 업로드 오류가 발생했습니다.';
        
        return ['success' => false, 'message' => $errorMessage];
    }
    
    // 파일 크기 검증 (10MB 제한)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '파일 크기가 10MB를 초과합니다.'];
    }
    
    // MIME 타입 검증
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/bmp', 'image/tiff', 'image/webp'
    ];
    
    // finfo로 실제 파일 타입 확인 (MIME 스푸핑 방지)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($realMimeType, $allowedTypes)) {
        return ['success' => false, 'message' => '지원하지 않는 파일 형식입니다. 이미지 파일만 업로드 가능합니다.'];
    }
    
    // 이미지 파일 검증
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => '유효한 이미지 파일이 아닙니다.'];
    }
    
    // 이미지 크기 검증 (최소 크기와 최대 크기)
    $minWidth = 100;
    $minHeight = 100;
    $maxWidth = 8000;
    $maxHeight = 8000;
    
    list($width, $height) = $imageInfo;
    
    if ($width < $minWidth || $height < $minHeight) {
        return ['success' => false, 'message' => "이미지 크기가 너무 작습니다. 최소 {$minWidth}x{$minHeight} 픽셀 이상이어야 합니다."];
    }
    
    if ($width > $maxWidth || $height > $maxHeight) {
        return ['success' => false, 'message' => "이미지 크기가 너무 큽니다. 최대 {$maxWidth}x{$maxHeight} 픽셀 이하여야 합니다."];
    }
    
    // 파일 이름 정리
    $fileName = sanitizeFileName($file['name']);
    
    return [
        'success' => true,
        'mime_type' => $realMimeType,
        'size' => $file['size'],
        'width' => $width,
        'height' => $height,
        'tmp_name' => $file['tmp_name'],
        'name' => $fileName
    ];
}

// 파일 이름 정리 (보안 목적)
function sanitizeFileName($fileName) {
    // 경로 정보 제거
    $fileName = basename($fileName);
    // 특수 문자 제거
    $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fileName);
    // 중복된 공백 제거
    $fileName = preg_replace('/\s+/', ' ', $fileName);
    // 공백 제거
    $fileName = trim($fileName);
    
    if (empty($fileName)) {
        return 'unnamed_file';
    }
    
    return $fileName;
}

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 템플릿 ID (URL 파라미터로 전달된 경우)
$templateId = isset($_GET['template']) ? (int)$_GET['template'] : null;
$selectedTemplate = null;

if ($templateId) {
    $selectedTemplate = $learningSystem->getTemplate($templateId);
}

// 사용 가능한 템플릿 목록 가져오기 (모든 템플릿)
$templates = $learningSystem->getTemplates(true);

// 오류 메시지 초기화
$errorMessage = null;
$successMessage = null;

// 페이지 제목
$pageTitle = '이미지 업로드 및 OCR 처리';
$csrfToken = generateCSRFToken();
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
        #dropArea {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        #dropArea.highlight {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        #filePreview {
            margin-top: 20px;
        }
        .preview-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .preview-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 5px;
        }
        .preview-info {
            flex-grow: 1;
        }
        .preview-remove {
            cursor: pointer;
            color: #dc3545;
            font-size: 1.2rem;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        #processingStatus {
            margin-top: 10px;
        }
        #currentFile {
            font-weight: bold;
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
        <h1 class="mb-4"><?php echo h($pageTitle); ?></h1>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo h($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo h($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-upload me-2"></i>이미지 업로드
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" action="process_upload.php" method="post" enctype="multipart/form-data">
                            <!-- CSRF 토큰 -->
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                            
                            <div class="mb-3">
                                <label for="jobName" class="form-label">작업 이름</label>
                                <input type="text" class="form-control" id="jobName" name="job_name" required
                                       maxlength="100" pattern="[A-Za-z0-9가-힣\s\-_]{1,100}">
                                <div class="form-text">작업 식별을 위한 고유한 이름을 입력하세요.</div>
                                <div class="validation-error" id="jobNameError"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="documentType" class="form-label">문서 유형 (템플릿)</label>
                                <select class="form-select" id="documentType" name="document_type">
                                    <option value="">자동 감지</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo h($template['id']); ?>"
                                            <?php echo ($templateId == $template['id']) ? 'selected' : ''; ?>>
                                            <?php echo h($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    특정 문서 유형을 선택하면 OCR 인식률이 향상됩니다.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">이미지 파일</label>
                                <div id="dropArea" onclick="document.getElementById('fileInput').click();">
                                    <i class="bi bi-cloud-arrow-up-fill fs-1 text-primary"></i>
                                    <p class="mt-3">이미지 파일을 끌어다 놓거나 클릭하여 선택하세요</p>
                                    <p class="text-muted small">지원 형식: JPG, JPEG, PNG, GIF, BMP, TIFF, WEBP</p>
                                    <p class="text-muted small">최대 파일 크기: 10MB</p>
                                </div>
                                <input type="file" id="fileInput" name="files[]" multiple accept="image/*" class="d-none">
                                <div id="filePreview"></div>
                                <div class="validation-error" id="fileError"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">OCR 향상 옵션</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="preprocessImages" name="options[preprocess]" checked>
                                    <label class="form-check-label" for="preprocessImages">
                                        이미지 전처리 적용
                                    </label>
                                    <div class="form-text">흐릿한 이미지를 선명하게 개선하고 텍스트 인식률을 높입니다.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enhanceTable" name="options[enhance_table]" checked>
                                    <label class="form-check-label" for="enhanceTable">
                                        테이블 인식 강화
                                    </label>
                                    <div class="form-text">문서 내 테이블 구조를 더 정확하게 인식합니다.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="applyCustomDict" name="options[apply_custom_dict]" checked>
                                    <label class="form-check-label" for="applyCustomDict">
                                        사용자 정의 사전 적용
                                    </label>
                                    <div class="form-text">자주 오인식되는 단어를 사용자 사전을 통해 자동 보정합니다.</div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" id="uploadButton" class="btn btn-primary" disabled>
                                    <i class="bi bi-play-fill me-2"></i>OCR 처리 시작
                                </button>
                            </div>
                        </form>
                        
                        <!-- 진행 상황 -->
                        <div id="progressContainer" class="progress-container">
                            <h5 class="mt-4">처리 진행 상황</h5>
                            <div class="progress">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    0%
                                </div>
                            </div>
                            <div id="processingStatus" class="alert alert-info">
                                처리 준비 중...
                            </div>
                            <div id="currentFile" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                
                <!-- 템플릿 정보 (선택된 경우) -->
                <?php if ($selectedTemplate): ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-file-text me-2"></i>선택된 템플릿: <?php echo h($selectedTemplate['name']); ?>
                    </div>
                    <div class="card-body">
                        <p><?php echo h($selectedTemplate['description'] ?? '설명 없음'); ?></p>
                        
                        <?php if (!empty($selectedTemplate['fields'])): ?>
                        <h6>인식 필드:</h6>
                        <ul>
                            <?php foreach ($selectedTemplate['fields'] as $field): ?>
                                <li><?php echo h($field['name']); ?> 
                                    <?php if (!empty($field['type'])): ?>
                                        <span class="text-muted small">(<?php echo h($field['type']); ?>)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($selectedTemplate['table_structure']['headers'])): ?>
                        <h6>테이블 구조:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <?php foreach ($selectedTemplate['table_structure']['headers'] as $header): ?>
                                            <th><?php echo h($header); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php foreach ($selectedTemplate['table_structure']['headers'] as $header): ?>
                                            <td>...</td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 팁과 안내 -->
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="bi bi-lightbulb me-2"></i>OCR 처리 팁
                    </div>
                    <div class="card-body">
                        <h6>더 나은 결과를 위한 이미지 준비:</h6>
                        <ul>
                            <li>가능한 한 고해상도 이미지를 사용하세요. (300 DPI 이상 권장)</li>
                            <li>문서가 기울어지지 않도록 평평하게 촬영하세요.</li>
                            <li>적절한 조명에서 촬영하여 그림자나 반사가 생기지 않도록 하세요.</li>
                            <li>문서 전체가 프레임 안에 들어오도록 촬영하세요.</li>
                        </ul>
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
    // 파일 업로드 관련 스크립트
    document.addEventListener('DOMContentLoaded', function() {
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const uploadButton = document.getElementById('uploadButton');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const processingStatus = document.getElementById('processingStatus');
        const currentFile = document.getElementById('currentFile');
        const jobNameInput = document.getElementById('jobName');
        const jobNameError = document.getElementById('jobNameError');
        const fileError = document.getElementById('fileError');
        
        // 클라이언트 측 유효성 검사
        function validateForm() {
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
            
            // 파일 검증
            if (filePreview.children.length === 0) {
                fileError.textContent = '최소 한 개 이상의 이미지 파일을 업로드해야 합니다.';
                isValid = false;
            } else {
                fileError.textContent = '';
            }
            
            return isValid;
        }
        
        // 파일 드래그 앤 드롭 이벤트
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
        }
        
        // 파일 드롭 처리
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            // 데이터 전송 객체에서 파일 목록을 생성
            const fileList = new DataTransfer();
            
            // 모든 파일이 이미지인지 확인
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.match('image.*')) {
                    fileList.items.add(file);
                }
            }
            
            fileInput.files = fileList.files;
            updateFilePreview(fileList.files);
        }
        
        // 파일 선택 처리
        fileInput.addEventListener('change', function() {
            updateFilePreview(this.files);
        });
        
        // 폼 입력 검증
        jobNameInput.addEventListener('input', validateForm);
        
        // 파일 미리보기 업데이트
        function updateFilePreview(files) {
            filePreview.innerHTML = '';
            fileError.textContent = '';
            
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // 이미지 파일 검증
                    if (!file.type.match('image.*')) {
                        fileError.textContent = '이미지 파일만 업로드 가능합니다.';
                        continue;
                    }
                    
                    // 파일 크기 검증
                    if (file.size > 10 * 1024 * 1024) { // 10MB
                        fileError.textContent = '파일 크기는 10MB를 초과할 수 없습니다.';
                        continue;
                    }
                    
                    const reader = new FileReader();
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.dataset.index = i;
                    
                    reader.onload = function(e) {
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" class="preview-thumbnail" alt="${file.name}">
                            <div class="preview-info">
                                <div class="fw-bold">${file.name}</div>
                                <div class="small text-muted">${formatFileSize(file.size)}</div>
                            </div>
                            <div class="preview-remove" data-index="${i}">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        `;
                    };
                    
                    reader.readAsDataURL(file);
                    filePreview.appendChild(previewItem);
                }
                
                // 업로드 버튼 활성화 여부 확인
                validateForm();
                uploadButton.disabled = filePreview.children.length === 0;
                
                // 제거 버튼 이벤트 연결
                setTimeout(() => {
                    document.querySelectorAll('.preview-remove').forEach(button => {
                        button.addEventListener('click', function() {
                            const index = parseInt(this.dataset.index);
                            this.closest('.preview-item').remove();
                            
                            // FileList 재구성
                            const newFileList = new DataTransfer();
                            for (let i = 0; i < fileInput.files.length; i++) {
                                if (i !== index) {
                                    newFileList.items.add(fileInput.files[i]);
                                }
                            }
                            fileInput.files = newFileList.files;
                            
                            // 모든 파일 제거된 경우 버튼 비활성화
                            if (filePreview.children.length === 0) {
                                uploadButton.disabled = true;
                                fileError.textContent = '최소 한 개 이상의 이미지 파일을 업로드해야 합니다.';
                            }
                        });
                    });
                }, 100);
            } else {
                uploadButton.disabled = true;
                fileError.textContent = '최소 한 개 이상의 이미지 파일을 업로드해야 합니다.';
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 폼 제출 처리
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 폼 유효성 검사
            if (!validateForm()) {
                return;
            }
            
            // FormData 객체 생성
            const formData = new FormData(this);
            
            // 선택된 파일이 있는지 확인
            if (fileInput.files.length === 0) {
                fileError.textContent = '최소 한 개 이상의 이미지 파일을 업로드해야 합니다.';
                return;
            }
            
            // 작업 이름 검증
            const jobName = jobNameInput.value.trim();
            if (!jobName) {
                jobNameError.textContent = '작업 이름을 입력해주세요.';
                return;
            }
            
            // 파일 입력란 숨기고 진행 상황 표시
            uploadForm.style.display = 'none';
            progressContainer.style.display = 'block';
            processingStatus.textContent = '파일 업로드 중...';
            processingStatus.className = 'alert alert-info';
            
            // AJAX 요청 - 파일 업로드 및 작업 생성
            $.ajax({
                url: 'ajax_create_job.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        const jobId = response.job_id;
                        processingStatus.textContent = '작업이 생성되었습니다. OCR 처리를 시작합니다...';
                        
                        // 파일 처리 시작
                        processFiles(jobId);
                    } else {
                        showError('작업 생성 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showError('요청 실패: ' + (xhr.responseJSON ? xhr.responseJSON.message : error));
                }
            });
        });
        
        // 파일 처리 함수
        function processFiles(jobId) {
            processNextFile(jobId);
        }
        
        // 다음 파일 처리 함수
        function processNextFile(jobId) {
            $.ajax({
                url: 'ajax_process_file.php',
                type: 'POST',
                data: { 
                    job_id: jobId,
                    csrf_token: '<?php echo h($csrfToken); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.status === 'completed') {
                            // 모든 파일 처리 완료
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            progressBar.setAttribute('aria-valuenow', 100);
                            
                            processingStatus.className = 'alert alert-success';
                            processingStatus.textContent = '모든 파일 처리가 완료되었습니다!';
                            currentFile.textContent = '';
                            
                            // 결과 페이지로 리디렉션
                            setTimeout(function() {
                                window.location.href = 'view_job.php?id=' + jobId;
                            }, 2000);
                        } else {
                            // 파일 처리 진행 중
                            const progress = response.progress.job.progress;
                            progressBar.style.width = progress + '%';
                            progressBar.textContent = progress + '%';
                            progressBar.setAttribute('aria-valuenow', progress);
                            
                            if (response.file_processed) {
                                currentFile.textContent = '처리 중: ' + response.file_processed;
                            }
                            
                            // 다음 파일 처리
                            setTimeout(function() {
                                processNextFile(jobId);
                            }, 1000);
                        }
                    } else {
                        showError('파일 처리 오류: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showError('요청 실패: ' + (xhr.responseJSON ? xhr.responseJSON.message : error));
                }
            });
        }
        
        // 오류 표시
        function showError(message) {
            processingStatus.className = 'alert alert-danger';
            processingStatus.textContent = message;
            
            // 원래 폼으로 돌아갈 수 있는 버튼 추가
            const backButton = document.createElement('button');
            backButton.className = 'btn btn-primary mt-3';
            backButton.textContent = '다시 시도';
            backButton.onclick = function() {
                uploadForm.style.display = 'block';
                progressContainer.style.display = 'none';
            };
            
            processingStatus.appendChild(document.createElement('br'));
            processingStatus.appendChild(backButton);
        }
    });
    </script>
</body>
</html>