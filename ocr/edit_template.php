<?php
/**
 * OCR 인식률 향상 시스템 - 템플릿 생성/편집
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'document_learning.php';

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 템플릿 ID (URL 파라미터로 전달된 경우 - 편집 모드)
$templateId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$template = null;
$isEditMode = false;

if ($templateId) {
    // 기존 템플릿 정보 가져오기
    $template = $learningSystem->getTemplate($templateId);
    
    // 템플릿이 존재하는지만 확인
    if (!$template) {
        header('Location: templates.php');
        exit;
    }
    
    $isEditMode = true;
}

// 템플릿 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 필수 필드 확인
        if (empty($_POST['name'])) {
            throw new Exception("템플릿 이름은 필수입니다.");
        }
        
        // 필드 데이터 처리
        $fields = [];
        
        if (isset($_POST['field_name']) && is_array($_POST['field_name'])) {
            foreach ($_POST['field_name'] as $index => $fieldName) {
                if (!empty($fieldName)) {
                    $fieldType = $_POST['field_type'][$index] ?? 'text';
                    $fieldPattern = $_POST['field_pattern'][$index] ?? '';
                    
                    $fields[] = [
                        'name' => $fieldName,
                        'type' => $fieldType,
                        'pattern' => $fieldPattern
                    ];
                }
            }
        }
        
        // 테이블 구조 처리
        $tableStructure = [];
        
        if (isset($_POST['table_headers']) && !empty($_POST['table_headers'])) {
            $headers = array_map('trim', explode(',', $_POST['table_headers']));
            $tableStructure['headers'] = array_filter($headers, function($header) {
                return !empty($header);
            });
        }
        
        // 템플릿 데이터 구성
        $templateData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'fields' => $fields,
            'tableStructure' => $tableStructure,
            'is_public' => isset($_POST['is_public']) ? 1 : 0
        ];
        
        // 편집 모드인 경우 ID 추가
        if ($isEditMode) {
            $templateData['id'] = $templateId;
        }
        
        // 템플릿 저장
        $savedId = $learningSystem->saveTemplate($templateData);
        
        // 성공 메시지 및 리디렉션
        $_SESSION['success_message'] = $isEditMode ? "템플릿이 업데이트되었습니다." : "새 템플릿이 생성되었습니다.";
        header('Location: templates.php');
        exit;
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// 필드 타입 옵션
$fieldTypes = [
    'text' => '텍스트',
    'number' => '숫자',
    'date' => '날짜',
    'amount' => '금액',
    'phone' => '전화번호',
    'email' => '이메일',
    'address' => '주소'
];

// 페이지 제목
$pageTitle = $isEditMode ? '템플릿 편집: ' . htmlspecialchars($template['name']) : '새 템플릿 생성';
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
        .field-card {
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            position: relative;
        }
        .field-card .btn-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .pattern-help {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
                
                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $errorMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="templateForm">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-file-earmark-text me-2"></i>기본 정보
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">템플릿 이름 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo $isEditMode ? htmlspecialchars($template['name']) : ''; ?>">
                                <div class="form-text">이 템플릿의 용도를 명확히 알 수 있는 이름을 입력하세요 (예: 음식점 영수증, 택배 송장)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">설명</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $isEditMode ? htmlspecialchars($template['description']) : ''; ?></textarea>
                                <div class="form-text">이 템플릿의 용도나 적용 가능한 문서 유형에 대한 설명을 입력하세요.</div>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public"
                                       <?php echo ($isEditMode && $template['is_public']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_public">
                                    공개 템플릿으로 설정
                                </label>
                                <div class="form-text">공개 템플릿은 다른 사용자도 사용할 수 있습니다.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-list-check me-2"></i>필드 정의
                        </div>
                        <div class="card-body">
                            <p>문서에서 인식할 주요 필드를 정의하세요. 필드는 OCR 인식 정확도를 높이는데 중요합니다.</p>
                            
                            <div id="fieldsContainer">
                                <?php if ($isEditMode && !empty($template['fields'])): ?>
                                    <?php foreach ($template['fields'] as $index => $field): ?>
                                        <div class="field-card p-3">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">필드 이름 <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="field_name[]" required
                                                           value="<?php echo htmlspecialchars($field['name']); ?>">
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">필드 타입</label>
                                                    <select class="form-select field-type-select" name="field_type[]">
                                                        <?php foreach ($fieldTypes as $value => $label): ?>
                                                            <option value="<?php echo $value; ?>" <?php echo ($field['type'] == $value) ? 'selected' : ''; ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-5 mb-3">
                                                    <label class="form-label">인식 패턴 (정규식)</label>
                                                    <input type="text" class="form-control" name="field_pattern[]" 
                                                           value="<?php echo htmlspecialchars($field['pattern'] ?? ''); ?>">
                                                    <div class="pattern-help mt-1"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" id="addFieldBtn" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-plus-circle me-2"></i>필드 추가
                            </button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <i class="bi bi-table me-2"></i>테이블 구조
                        </div>
                        <div class="card-body">
                            <p>문서에 테이블이 포함된 경우, 테이블 구조를 정의하면 더 정확한 인식이 가능합니다.</p>
                            
                            <div class="mb-3">
                                <label for="table_headers" class="form-label">테이블 헤더 (쉼표로 구분)</label>
                                <input type="text" class="form-control" id="table_headers" name="table_headers"
                                       value="<?php 
                                           echo $isEditMode && !empty($template['table_structure']['headers']) ? 
                                                htmlspecialchars(implode(', ', $template['table_structure']['headers'])) : ''; 
                                       ?>">
                                <div class="form-text">예: 상품명, 단가, 수량, 금액</div>
                            </div>
                            
                            <div class="table-preview mt-3" style="display: none;">
                                <h6>테이블 미리보기:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead id="tablePreviewHeader">
                                            <tr></tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-muted">
                                                <!-- 동적으로 생성됨 -->
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <a href="templates.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>템플릿 목록으로
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i><?php echo $isEditMode ? '템플릿 업데이트' : '템플릿 생성'; ?>
                        </button>
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
        // 필드 추가 버튼
        const addFieldBtn = document.getElementById('addFieldBtn');
        const fieldsContainer = document.getElementById('fieldsContainer');
        
        // 필드 타입별 패턴 도움말
        const patternHelp = {
            'text': '원하는 텍스트 패턴 (예: [가-힣]+ 또는 [A-Za-z]+)',
            'number': '숫자 패턴 (예: \\d+ 또는 \\d{3,5})',
            'date': '날짜 패턴 (예: \\d{4}-\\d{2}-\\d{2} 또는 \\d{4}\\.\\d{1,2}\\.\\d{1,2})',
            'amount': '금액 패턴 (예: [\\d,]+원 또는 \\d+\\.\\d{2})',
            'phone': '전화번호 패턴 (예: \\d{2,3}-\\d{3,4}-\\d{4})',
            'email': '이메일 패턴 (예: [a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,})',
            'address': '주소 패턴 (자유 형식)'
        };
        
        // 필드 타입별 기본 패턴
        const defaultPatterns = {
            'text': '.+',
            'number': '\\d+',
            'date': '\\d{4}[-.\/]\\d{1,2}[-.\/]\\d{1,2}',
            'amount': '[\\d,]+원?',
            'phone': '\\d{2,3}[-\\s]?\\d{3,4}[-\\s]?\\d{4}',
            'email': '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}',
            'address': '.+'
        };
        
        // 필드 추가 함수
        function addField(name = '', type = 'text', pattern = '') {
            const fieldCard = document.createElement('div');
            fieldCard.className = 'field-card p-3';
            
            fieldCard.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove">
                    <i class="bi bi-x"></i>
                </button>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">필드 이름 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="field_name[]" required value="${name}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">필드 타입</label>
                        <select class="form-select field-type-select" name="field_type[]">
                            ${Object.entries(fieldTypes).map(([value, label]) => 
                                `<option value="${value}" ${type === value ? 'selected' : ''}>${label}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">인식 패턴 (정규식)</label>
                        <input type="text" class="form-control" name="field_pattern[]" value="${pattern}">
                        <div class="pattern-help mt-1">${patternHelp[type] || ''}</div>
                    </div>
                </div>
            `;
            
            fieldsContainer.appendChild(fieldCard);
            
            // 삭제 버튼 이벤트 연결
            const removeBtn = fieldCard.querySelector('.btn-remove');
            removeBtn.addEventListener('click', function() {
                fieldCard.remove();
            });
            
            // 필드 타입 변경 이벤트 연결
            const typeSelect = fieldCard.querySelector('.field-type-select');
            const patternInput = fieldCard.querySelector('input[name="field_pattern[]"]');
            const patternHelpDiv = fieldCard.querySelector('.pattern-help');
            
            typeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                patternHelpDiv.textContent = patternHelp[selectedType] || '';
                
                // 패턴이 비어있는 경우 기본 패턴 제안
                if (!patternInput.value.trim()) {
                    patternInput.value = defaultPatterns[selectedType] || '';
                }
            });
        }
        
        // 필드 추가 버튼 클릭 이벤트
        addFieldBtn.addEventListener('click', function() {
            addField();
        });
        
        // 기존 필드가 없으면 기본 필드 추가
        if (fieldsContainer.children.length === 0) {
            // 기본 필드 추가 (빈 템플릿 생성 시)
            addField('날짜', 'date', '\\d{4}[-.\/]\\d{1,2}[-.\/]\\d{1,2}');
            addField('금액', 'amount', '[\\d,]+원');
        } else {
            // 기존 필드의 이벤트 연결
            document.querySelectorAll('.field-card').forEach(fieldCard => {
                // 삭제 버튼 이벤트 연결
                const removeBtn = fieldCard.querySelector('.btn-remove');
                removeBtn.addEventListener('click', function() {
                    fieldCard.remove();
                });
                
                // 필드 타입 변경 이벤트 연결
                const typeSelect = fieldCard.querySelector('.field-type-select');
                const patternHelpDiv = fieldCard.querySelector('.pattern-help');
                
                // 초기 도움말 표시
                patternHelpDiv.textContent = patternHelp[typeSelect.value] || '';
                
                typeSelect.addEventListener('change', function() {
                    const selectedType = this.value;
                    patternHelpDiv.textContent = patternHelp[selectedType] || '';
                });
            });
        }
        
        // 테이블 헤더 미리보기
        const tableHeaders = document.getElementById('table_headers');
        const tablePreview = document.querySelector('.table-preview');
        const tablePreviewHeader = document.getElementById('tablePreviewHeader').querySelector('tr');
        
        tableHeaders.addEventListener('input', function() {
            const headers = this.value.split(',').map(h => h.trim()).filter(h => h);
            
            if (headers.length > 0) {
                tablePreview.style.display = 'block';
                
                // 헤더 셀 생성
                tablePreviewHeader.innerHTML = '';
                headers.forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    tablePreviewHeader.appendChild(th);
                });
                
                // 본문 행 셀 생성
                const tbody = tablePreview.querySelector('tbody tr');
                tbody.innerHTML = '';
                headers.forEach(() => {
                    const td = document.createElement('td');
                    td.textContent = '...';
                    tbody.appendChild(td);
                });
            } else {
                tablePreview.style.display = 'none';
            }
        });
        
        // 초기 테이블 미리보기 표시
        if (tableHeaders.value.trim()) {
            tableHeaders.dispatchEvent(new Event('input'));
        }
    });
    
    // JavaScript에서 사용할 필드 타입 객체
    const fieldTypes = <?php echo json_encode($fieldTypes); ?>;
    </script>
</body>
</html>
