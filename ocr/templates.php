<?php
/**
 * OCR 인식률 향상 시스템 - 템플릿 관리
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'utils.php';
require_once 'document_learning.php';

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 템플릿 목록 가져오기 (모든 템플릿)
$templates = $learningSystem->getTemplates(true); // true로 모든 템플릿

// 템플릿 삭제 처리
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['template_id'])) {
    $templateId = (int)$_POST['template_id'];
    
    try {
        // 템플릿 정보 가져오기
        $template = $learningSystem->getTemplate($templateId);
        
        // 템플릿 비활성화 (소프트 삭제)
        $db = getDB();
        $stmt = $db->prepare("UPDATE ocr_document_templates SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$templateId]);
        
        // 삭제 성공 메시지
        $successMessage = "템플릿이 삭제되었습니다.";
        
        // 템플릿 목록 갱신
        $templates = $learningSystem->getTemplates(true);
    } catch (Exception $e) {
        $errorMessage = "템플릿 삭제 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 페이지 제목
$pageTitle = '템플릿 관리';
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
        .template-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .template-card:hover {
            transform: translateY(-5px);
        }
        .badge-pill {
            padding: 5px 10px;
            border-radius: 15px;
        }
        .template-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <a href="edit_template.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>새 템플릿 생성
            </a>
        </div>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- 템플릿 목록 -->
        <div class="row">
            <?php if (empty($templates)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>아직 생성된 템플릿이 없습니다. 새 템플릿을 생성해보세요.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card template-card h-100">
                            <div class="card-body text-center">
                                <div class="template-icon">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                                <?php if ($template['description']): ?>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($template['description']); ?></p>
                                <?php else: ?>
                                    <p class="card-text text-muted fst-italic">설명 없음</p>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <?php if ($template['is_public']): ?>
                                        <span class="badge bg-info badge-pill">공개</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($template['usage_count'] > 0): ?>
                                        <span class="badge bg-success badge-pill">사용 <?php echo $template['usage_count']; ?>회</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-center">
                                    <a href="edit_template.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-pencil me-1"></i>편집
                                    </a>
                                    
                                    <a href="upload.php?template=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-success me-2">
                                        <i class="bi bi-upload me-1"></i>사용
                                    </a>
                                    
                                    <form method="post" class="d-inline" onsubmit="return confirm('정말로 이 템플릿을 삭제하시겠습니까?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1"></i>삭제
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>최종 수정: <?php echo date('Y-m-d', strtotime($template['updated_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- 템플릿 사용 가이드 -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <i class="bi bi-lightbulb me-2"></i>템플릿 사용 가이드
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>문서 템플릿이란?</h5>
                        <p>문서 템플릿은 OCR 인식률을 높이기 위한 중요한 도구입니다. 반복적으로 처리하는 특정 유형의 문서 구조와 내용을 정의하여 더 정확한 텍스트 인식과 데이터 추출이 가능합니다.</p>
                        
                        <h5 class="mt-3">템플릿의 주요 기능</h5>
                        <ul>
                            <li><strong>필드 인식 강화:</strong> 주요 필드(날짜, 금액, 상품명 등)의 위치와 형식을 미리 정의하여 인식률 향상</li>
                            <li><strong>테이블 구조 인식:</strong> 문서 내 테이블 헤더 및 구조를 정의하여 정확한 데이터 추출</li>
                            <li><strong>문맥 기반 보정:</strong> 특정 문서 유형에서 자주 오인식되는 단어를 자동으로 보정</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>효과적인 템플릿 생성 방법</h5>
                        <ol>
                            <li>처리할 문서 유형(영수증, 송장, 계약서 등)을 명확히 정의합니다.</li>
                            <li>문서에서 추출하고자 하는 주요 필드를 식별합니다.</li>
                            <li>각 필드의 데이터 유형(텍스트, 날짜, 금액 등)을 지정합니다.</li>
                            <li>테이블이 있는 경우 열 이름과 데이터 유형을 정의합니다.</li>
                            <li>동일한 템플릿을 여러 번 사용하면 시스템이 점점 더 정확해집니다.</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>템플릿을 사용한 OCR 처리 후 피드백을 제공하면 인식률이 더욱 향상됩니다.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 푸터 -->
    <?php include 'footer.php'; ?>

    <!-- JavaScript 추가 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
