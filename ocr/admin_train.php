<?php
/**
 * OCR 인식률 향상 시스템 - 관리자 모델 훈련 페이지
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'utils.php';
require_once 'document_learning.php';

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 모델 훈련 처리
$trainResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'train_model') {
        // 강제 훈련 여부
        $forceTrain = isset($_POST['force_train']) && $_POST['force_train'] === 'on';
        // 모델 훈련 실행
        $trainResult = $learningSystem->trainModel($forceTrain);
    }
}

// 모델 평가 및 통계 가져오기
$modelStats = $learningSystem->evaluateModel();

// 사용자 사전 통계
$dictionary = $learningSystem->getCustomDictionary();
$dictionaryStats = [
    'total' => count($dictionary),
    'frequently_used' => count(array_filter($dictionary, function($item) {
        return $item['frequency'] > 10;
    }))
];

// 템플릿 통계
$db = getDB();
$stmt = $db->prepare("
    SELECT COUNT(*) as count, 
           SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public_count
    FROM ocr_document_templates 
    WHERE is_active = 1
");
$stmt->execute();
$templateStats = $stmt->fetch();

// 피드백 통계
$stmt = $db->prepare("SELECT COUNT(*) as count FROM ocr_feedback");
$stmt->execute();
$feedbackStats = $stmt->fetch();

// 페이지 제목
$pageTitle = '모델 훈련 및 관리';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 관리자</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-card p {
            font-size: 1.1rem;
            color: #666;
        }
        .progress-card .progress {
            height: 25px;
            margin-bottom: 10px;
        }
        .progress-card .progress-bar {
            line-height: 25px;
            font-weight: bold;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .training-log {
            max-height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?> <span class="badge bg-danger">관리자</span></h1>
        </div>
        
        <?php if ($trainResult): ?>
            <?php if ($trainResult['success']): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?php echo $trainResult['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $trainResult['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- 모델 정확도 및 상태 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card progress-card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-graph-up me-2"></i>모델 정확도
                    </div>
                    <div class="card-body">
                        <div class="progress-label">
                            <span>기본 정확도</span>
                            <span><?php echo $modelStats['accuracy_estimate']['base_accuracy']; ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-secondary" style="width: <?php echo $modelStats['accuracy_estimate']['base_accuracy']; ?>%">
                                <?php echo $modelStats['accuracy_estimate']['base_accuracy']; ?>%
                            </div>
                        </div>
                        
                        <div class="progress-label mt-3">
                            <span>현재 정확도</span>
                            <span><?php echo $modelStats['accuracy_estimate']['estimated_accuracy']; ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo $modelStats['accuracy_estimate']['base_accuracy']; ?>%">
                                <?php echo $modelStats['accuracy_estimate']['base_accuracy']; ?>%
                            </div>
                            <div class="progress-bar bg-info" style="width: <?php echo $modelStats['accuracy_estimate']['dictionary_improvement']; ?>%">
                                +<?php echo $modelStats['accuracy_estimate']['dictionary_improvement']; ?>%
                            </div>
                            <div class="progress-bar bg-warning" style="width: <?php echo $modelStats['accuracy_estimate']['template_improvement']; ?>%">
                                +<?php echo $modelStats['accuracy_estimate']['template_improvement']; ?>%
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <span class="badge bg-info me-2">사전 개선: +<?php echo $modelStats['accuracy_estimate']['dictionary_improvement']; ?>%</span>
                            <span class="badge bg-warning me-2">템플릿 개선: +<?php echo $modelStats['accuracy_estimate']['template_improvement']; ?>%</span>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>마지막 모델 훈련: <strong><?php echo $modelStats['last_training']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-cpu me-2"></i>모델 훈련
                    </div>
                    <div class="card-body">
                        <p>모델 훈련은 사용자 사전, 문서 템플릿, 피드백 데이터를 기반으로 OCR 인식률을 향상시킵니다.</p>
                        
                        <form method="post" onsubmit="return confirm('모델 훈련을 시작하시겠습니까? 서버 자원을 많이 사용할 수 있습니다.');">
                            <input type="hidden" name="action" value="train_model">
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="force_train" name="force_train">
                                <label class="form-check-label" for="force_train">
                                    강제 훈련 (마지막 훈련 후 24시간이 지나지 않았어도 훈련)
                                </label>
                            </div>
                            
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>모델 훈련은 서버 자원을 많이 사용할 수 있으므로 트래픽이 적은 시간에 실행하는 것이 좋습니다.
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-fill me-2"></i>모델 훈련 시작
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- 시스템 통계 요약 -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-bar-chart me-2"></i>시스템 통계
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="stat-item mb-3">
                                    <h5>사용자 사전</h5>
                                    <p class="mb-1">총 단어 수: <strong><?php echo $dictionaryStats['total']; ?></strong></p>
                                    <p>자주 사용되는 단어: <strong><?php echo $dictionaryStats['frequently_used']; ?></strong></p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item mb-3">
                                    <h5>문서 템플릿</h5>
                                    <p class="mb-1">총 템플릿 수: <strong><?php echo $templateStats['count']; ?></strong></p>
                                    <p>공개 템플릿: <strong><?php echo $templateStats['public_count']; ?></strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="stat-item">
                                    <h5>사용자 피드백</h5>
                                    <p>총 피드백 수: <strong><?php echo $feedbackStats['count']; ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- 자주 수정되는 필드 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="bi bi-card-checklist me-2"></i>자주 수정되는 필드
                    </div>
                    <div class="card-body">
                        <?php if (empty($modelStats['frequently_corrected_fields'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>자주 수정되는 필드 정보가 없습니다.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>필드 이름</th>
                                            <th>수정 횟수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modelStats['frequently_corrected_fields'] as $field => $count): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($field); ?></td>
                                                <td><?php echo $count; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- 필드 수정 차트 -->
                            <div class="mt-3">
                                <canvas id="fieldCorrectionsChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 훈련 로그 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="bi bi-terminal me-2"></i>마지막 훈련 로그
                    </div>
                    <div class="card-body">
                        <?php
                        $logFile = $config['model_path'] . '/training_log.txt';
                        if (file_exists($logFile)):
                            $logContent = file_get_contents($logFile);
                            if (!empty($logContent)):
                        ?>
                            <div class="training-log">
                                <?php echo htmlspecialchars($logContent); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>훈련 로그가 비어 있습니다.
                            </div>
                        <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>훈련 로그 파일이 아직 생성되지 않았습니다.
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $dictionaryFile = $config['model_path'] . '/custom_dictionary.json';
                        if (file_exists($dictionaryFile)):
                        ?>
                            <div class="mt-3">
                                <a href="download.php?file=<?php echo urlencode($dictionaryFile); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>사전 파일 다운로드
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 고급 관리 옵션 -->
        <div class="card mt-3">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-gear me-2"></i>고급 관리 옵션
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">사전 내보내기/가져오기</h5>
                                <p class="card-text">사용자 정의 사전을 JSON 형식으로 내보내거나 가져옵니다.</p>
                                <div class="d-flex justify-content-between">
                                    <a href="export_dictionary.php" class="btn btn-sm btn-outline-primary">내보내기</a>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importDictionaryModal">
                                        가져오기
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">템플릿 내보내기/가져오기</h5>
                                <p class="card-text">문서 템플릿을 JSON 형식으로 내보내거나 가져옵니다.</p>
                                <div class="d-flex justify-content-between">
                                    <a href="export_templates.php" class="btn btn-sm btn-outline-primary">내보내기</a>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importTemplateModal">
                                        가져오기
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">시스템 설정 초기화</h5>
                                <p class="card-text">경고: 이 작업은 모든 학습 데이터를 초기화합니다.</p>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetSystemModal">
                                    시스템 초기화
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 사전 가져오기 모달 -->
    <div class="modal fade" id="importDictionaryModal" tabindex="-1" aria-labelledby="importDictionaryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importDictionaryModalLabel">사전 가져오기</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import_dictionary.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="dictionaryFile" class="form-label">사전 파일 (JSON)</label>
                            <input class="form-control" type="file" id="dictionaryFile" name="dictionary_file" accept=".json">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="mergeDictionary" name="merge_dictionary" checked>
                            <label class="form-check-label" for="mergeDictionary">
                                기존 사전과 병합 (체크 해제 시 기존 사전을 대체)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">가져오기</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 템플릿 가져오기 모달 -->
    <div class="modal fade" id="importTemplateModal" tabindex="-1" aria-labelledby="importTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTemplateModalLabel">템플릿 가져오기</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import_templates.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="templateFile" class="form-label">템플릿 파일 (JSON)</label>
                            <input class="form-control" type="file" id="templateFile" name="template_file" accept=".json">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="publicTemplates" name="public_templates" checked>
                            <label class="form-check-label" for="publicTemplates">
                                공개 템플릿으로 설정
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">가져오기</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 시스템 초기화 모달 -->
    <div class="modal fade" id="resetSystemModal" tabindex="-1" aria-labelledby="resetSystemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetSystemModalLabel">시스템 초기화</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>경고: 이 작업은 되돌릴 수 없습니다!</strong>
                    </div>
                    <p>시스템 초기화는 다음 데이터를 삭제합니다:</p>
                    <ul>
                        <li>모든 사용자 정의 사전</li>
                        <li>모든 학습 통계 및 피드백 데이터</li>
                        <li>모델 훈련 파일</li>
                    </ul>
                    <p>문서 템플릿과 사용자 계정은 유지됩니다.</p>
                    
                    <div class="mb-3">
                        <label for="confirmReset" class="form-label">확인을 위해 "RESET"을 입력하세요</label>
                        <input type="text" class="form-control" id="confirmReset" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" id="resetButton" class="btn btn-danger" disabled>초기화</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 푸터 -->
    <?php include 'footer.php'; ?>

    <!-- JavaScript 추가 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 필드 수정 차트
        const fieldCorrectionsData = <?php echo json_encode($modelStats['frequently_corrected_fields'] ?? []); ?>;
        
        if (Object.keys(fieldCorrectionsData).length > 0) {
            const ctx = document.getElementById('fieldCorrectionsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(fieldCorrectionsData),
                    datasets: [{
                        label: '수정 횟수',
                        data: Object.values(fieldCorrectionsData),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: '자주 수정되는 필드 분포'
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        // 시스템 초기화 확인
        const confirmResetInput = document.getElementById('confirmReset');
        const resetButton = document.getElementById('resetButton');
        
        confirmResetInput.addEventListener('input', function() {
            resetButton.disabled = this.value.trim() !== 'RESET';
        });
        
        resetButton.addEventListener('click', function() {
            if (confirmResetInput.value.trim() === 'RESET') {
                if (confirm('정말로 시스템을 초기화하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
                    window.location.href = 'reset_system.php';
                }
            }
        });
    });
    </script>
</body>
</html>
