<?php
/**
 * OCR 인식률 향상 시스템 - 사용자 사전 관리
 */

require_once 'config.php';
require_once 'document_learning.php';
require_once 'utils.php';

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 사전 목록 가져오기 (모든 사전)
$dictionary = $learningSystem->getCustomDictionary(null);

// 단어 추가/수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        try {
            // 필수 필드 검증
            if (empty($_POST['word'])) {
                throw new Exception("단어는 필수입니다.");
            }
            
            $word = trim($_POST['word']);
            $similarWords = [];
            
            // 유사 단어 처리
            if (!empty($_POST['similar_words'])) {
                $similarWords = array_map('trim', explode(',', $_POST['similar_words']));
                $similarWords = array_filter($similarWords, function($w) {
                    return !empty($w);
                });
            }
            
            // 활성 상태
            $isActive = isset($_POST['is_active']) ? true : false;
            
            // 사전에 단어 추가/수정
            $wordId = $learningSystem->upsertDictionaryWord($word, $similarWords, $isActive);
            
            // 성공 메시지
            $successMessage = $_POST['action'] === 'add' ? 
                              "단어가 사전에 추가되었습니다." : 
                              "단어가 업데이트되었습니다.";
            
            // 사전 목록 갱신
            $dictionary = $learningSystem->getCustomDictionary(null);
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['word_id'])) {
        try {
            $wordId = (int)$_POST['word_id'];
            
            // 단어 비활성화 (소프트 삭제)
            $db = getDB();
            $stmt = $db->prepare("UPDATE ocr_custom_dictionary SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$wordId]);
            
            $successMessage = "단어가 사전에서 삭제되었습니다.";
            
            // 사전 목록 갱신
            $dictionary = $learningSystem->getCustomDictionary(null);
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

// 단어 검색
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($searchTerm)) {
    $dictionary = array_filter($dictionary, function($item) use ($searchTerm) {
        return (stripos($item['word'], $searchTerm) !== false) || 
               (stripos(implode(' ', $item['similar_words']), $searchTerm) !== false);
    });
}

// 페이지 제목
$pageTitle = '사전 관리';
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
        .table th {
            background-color: #f8f9fa;
        }
        .badge-count {
            font-size: 0.8rem;
            background-color: #e9ecef;
            color: #495057;
            padding: 3px 6px;
            border-radius: 10px;
        }
        .similar-word {
            display: inline-block;
            background-color: #e9ecef;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .search-container {
            max-width: 500px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWordModal">
                <i class="bi bi-plus-circle me-2"></i>새 단어 추가
            </button>
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
        
        <!-- 검색 및 필터 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <form method="get" class="search-container">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="단어 검색..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                                <?php if (!empty($searchTerm)): ?>
                                    <a href="dictionary.php" class="btn btn-outline-danger">
                                        <i class="bi bi-x"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="me-3">총 단어 수: <strong><?php echo count($dictionary); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 사전 목록 -->
        <div class="card">
            <div class="card-header bg-light">
                <i class="bi bi-book me-2"></i>사전
            </div>
            <div class="card-body">
                <?php if (empty($dictionary)): ?>
                    <div class="alert alert-info">
                        <?php if (empty($searchTerm)): ?>
                            <i class="bi bi-info-circle me-2"></i>사전에 등록된 단어가 없습니다. 새 단어를 추가해보세요.
                        <?php else: ?>
                            <i class="bi bi-search me-2"></i>검색 결과가 없습니다. 다른 검색어를 시도해보세요.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>정확한 단어</th>
                                    <th>유사 단어 (오인식되는 단어)</th>
                                    <th>사용 빈도</th>
                                    <th>마지막 업데이트</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dictionary as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['word']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (empty($item['similar_words'])): ?>
                                                <span class="text-muted">-</span>
                                            <?php else: ?>
                                                <?php foreach ($item['similar_words'] as $similarWord): ?>
                                                    <span class="similar-word"><?php echo htmlspecialchars($similarWord); ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $item['frequency']; ?>
                                            <?php if ($item['frequency'] > 10): ?>
                                                <span class="badge bg-success ms-1">자주 사용됨</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($item['updated_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-word-btn" 
                                                    data-word="<?php echo htmlspecialchars($item['word']); ?>"
                                                    data-similar="<?php echo htmlspecialchars(implode(', ', $item['similar_words'])); ?>"
                                                    data-active="<?php echo $item['is_active']; ?>"
                                                    data-id="<?php echo $item['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('정말로 이 단어를 삭제하시겠습니까?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="word_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 사전 사용 가이드 -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <i class="bi bi-lightbulb me-2"></i>사전 활용 가이드
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>사전이란?</h5>
                        <p>사전은 OCR 처리 중 자주 오인식되는 단어를 올바른 단어로 자동 변환해주는 기능입니다. 이를 통해 OCR 인식률을 크게 높일 수 있습니다.</p>
                        
                        <h5 class="mt-3">사전의 주요 기능</h5>
                        <ul>
                            <li><strong>오인식 자동 보정:</strong> OCR에서 자주 오인식되는 단어를 올바른 단어로 자동 변환</li>
                            <li><strong>유사 단어 처리:</strong> 유사하게 인식되는 여러 단어를 하나의 정확한 단어로 통일</li>
                            <li><strong>사용 빈도 기반 학습:</strong> 자주 사용되는 단어일수록 인식 우선순위가 높아짐</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>효과적인 사전 구축 방법</h5>
                        <ol>
                            <li>자주 처리하는 문서에서 오인식되는 단어를 파악합니다.</li>
                            <li>정확한 단어와 오인식되는 단어들을 쌍으로 등록합니다.</li>
                            <li>특히 고유명사, 전문용어, 약어 등을 중점적으로 등록하면 효과적입니다.</li>
                            <li>OCR 결과에 피드백을 제공하면 사전이 자동으로 확장됩니다.</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>오인식된 단어와 올바른 단어의 편집 거리(레벤슈타인 거리)가 가까울수록 시스템이 더 정확하게 교정합니다.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 단어 추가 모달 -->
    <div class="modal fade" id="addWordModal" tabindex="-1" aria-labelledby="addWordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWordModalLabel">새 단어 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="word" class="form-label">정확한 단어 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="word" name="word" required>
                            <div class="form-text">OCR 결과에서 보이길 원하는 정확한 단어를 입력하세요.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="similar_words" class="form-label">유사 단어 (쉼표로 구분)</label>
                            <textarea class="form-control" id="similar_words" name="similar_words" rows="3"></textarea>
                            <div class="form-text">OCR에서 자주 오인식되는 단어들을 쉼표로 구분하여 입력하세요.</div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                활성화
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">추가</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 단어 편집 모달 -->
    <div class="modal fade" id="editWordModal" tabindex="-1" aria-labelledby="editWordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWordModalLabel">단어 편집</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_word_id" name="word_id">
                        
                        <div class="mb-3">
                            <label for="edit_word" class="form-label">정확한 단어 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_word" name="word" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_similar_words" class="form-label">유사 단어 (쉼표로 구분)</label>
                            <textarea class="form-control" id="edit_similar_words" name="similar_words" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                활성화
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">저장</button>
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
        // 단어 편집 버튼 클릭 이벤트
        const editButtons = document.querySelectorAll('.edit-word-btn');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const word = this.getAttribute('data-word');
                const similarWords = this.getAttribute('data-similar');
                const isActive = this.getAttribute('data-active') === '1';
                
                // 모달 필드에 값 설정
                document.getElementById('edit_word_id').value = id;
                document.getElementById('edit_word').value = word;
                document.getElementById('edit_similar_words').value = similarWords;
                document.getElementById('edit_is_active').checked = isActive;
                
                // 모달 표시
                const editModal = new bootstrap.Modal(document.getElementById('editWordModal'));
                editModal.show();
            });
        });
    });
    </script>
</body>
</html>
