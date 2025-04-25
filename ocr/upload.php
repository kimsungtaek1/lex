<?php
/**
 * OCR 인식률 향상 시스템 - 파일 업로드 및 OCR 처리 페이지
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'process_monitor.php';
require_once 'document_learning.php';

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

// 페이지 제목
$pageTitle = '이미지 업로드 및 OCR 처리';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
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
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-upload me-2"></i>이미지 업로드
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" action="process_upload.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="jobName" class="form-label">작업 이름</label>
                                <input type="text" class="form-control" id="jobName" name="job_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="documentType" class="form-label">문서 유형 (템플릿)</label>
                                <select class="form-select" id="documentType" name="document_type">
                                    <option value="">자동 감지</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>"
                                            <?php echo ($templateId == $template['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($template['name']); ?>
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
                                    <p class="text-muted small">지원 형식: JPG, JPEG, PNG, GIF, BMP, TIFF</p>
                                </div>
                                <input type="file" id="fileInput" name="files[]" multiple accept="image/*" class="d-none">
                                <div id="filePreview"></div>
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
                        <i class="bi bi-file-text me-2"></i>선택된 템플릿: <?php echo htmlspecialchars($selectedTemplate['name']); ?>
                    </div>
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($selectedTemplate['description'] ?? '설명 없음'); ?></p>
                        
                        <?php if (!empty($selectedTemplate['fields'])): ?>
                        <h6>인식 필드:</h6>
                        <ul>
                            <?php foreach ($selectedTemplate['fields'] as $field): ?>
                                <li><?php echo htmlspecialchars($field['name']); ?></li>
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
                                            <th><?php echo htmlspecialchars($header); ?></th>
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
            fileInput.files = files;
            updateFilePreview(files);
        }
        
        // 파일 선택 처리
        fileInput.addEventListener('change', function() {
            updateFilePreview(this.files);
        });
        
        // 파일 미리보기 업데이트
        function updateFilePreview(files) {
            filePreview.innerHTML = '';
            
            if (files.length > 0) {
                uploadButton.disabled = false;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // 이미지 파일만 허용
                    if (!file.type.match('image.*')) {
                        continue;
                    }
                    
                    const reader = new FileReader();
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
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
                
                // 제거 버튼 이벤트 연결
                setTimeout(() => {
                    document.querySelectorAll('.preview-remove').forEach(button => {
                        button.addEventListener('click', function() {
                            // 실제로는 FileList 객체를 직접 수정할 수 없으므로
                            // 이 버튼이 속한 preview-item을 제거하고 제출 시 파일 목록 재구성
                            this.closest('.preview-item').remove();
                            
                            // 모든 파일 제거된 경우 버튼 비활성화
                            if (filePreview.children.length === 0) {
                                uploadButton.disabled = true;
                            }
                        });
                    });
                }, 100);
            } else {
                uploadButton.disabled = true;
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
            
            // FormData 객체 생성
            const formData = new FormData(this);
            
            // 작업 이름 검증
            const jobName = document.getElementById('jobName').value.trim();
            if (!jobName) {
                alert('작업 이름을 입력해주세요.');
                return;
            }
            
            // 파일 입력란 숨기고 진행 상황 표시
            uploadForm.style.display = 'none';
            progressContainer.style.display = 'block';
            processingStatus.textContent = '파일 업로드 중...';
            
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
                    showError('요청 실패: ' + error);
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
                data: { job_id: jobId },
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
                        showError('파일 처리 오류: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    showError('요청 실패: ' + error);
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
