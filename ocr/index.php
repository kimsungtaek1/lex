<?php
// 500 에러 발생시 로그 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * OCR 인식률 향상 시스템 - 메인 페이지
 * 카페24 웹호스팅 환경에 최적화됨
 */

require_once 'config.php';
require_once 'process_monitor.php';
require_once 'document_learning.php';

// 프로세스 모니터 초기화
$processMonitor = new OCRProcessMonitor();

// 최근 작업 목록 가져오기
$recentJobs = $processMonitor->getUserJobs(null, 5, 0);

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

// 문서 학습 시스템 초기화
$learningSystem = new DocumentLearningSystem();

// 사용 가능한 템플릿 가져오기
$templates = $learningSystem->getUserTemplates(null);

// 작업 통계
$stmt = getDB()->prepare("
    SELECT 
        COUNT(*) AS total_jobs,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_jobs,
        SUM(processed_files) AS processed_files
    FROM ocr_jobs 
    WHERE 1 = 1
");
$stmt->execute([]);
$stats = $stmt->fetch();

// 페이지 제목
$pageTitle = 'OCR 인식률 향상 시스템';
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
        .progress {
            height: 20px;
            margin-bottom: 10px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <?php include 'navbar.php'; ?>

    <!-- 메인 컨텐츠 -->
    <div class="container mt-4">
        <h1 class="mb-4"><?php echo $pageTitle; ?> - 대시보드</h1>
        
        <!-- 통계 카드 -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body stat-card">
                        <h3 class="text-primary"><?php echo number_format($stats['total_jobs'] ?? 0); ?></h3>
                        <p>총 작업 수</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body stat-card">
                        <h3 class="text-success"><?php echo number_format($stats['completed_jobs'] ?? 0); ?></h3>
                        <p>완료된 작업</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body stat-card">
                        <h3 class="text-info"><?php echo number_format($stats['processed_files'] ?? 0); ?></h3>
                        <p>처리된 파일</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- 최근 작업 목록 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-list-check me-2"></i>최근 작업
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentJobs)): ?>
                            <div class="alert alert-info">
                                아직 등록된 작업이 없습니다. 새 OCR 작업을 시작해보세요.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>작업명</th>
                                            <th>상태</th>
                                            <th>진행률</th>
                                            <th>생성일</th>
                                            <th>작업</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentJobs as $job): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($job['status']); ?>">
                                                        <?php echo getStatusText($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-<?php echo getStatusColor($job['status']); ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $job['progress']; ?>%"
                                                             aria-valuenow="<?php echo $job['progress']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo $job['progress']; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        보기
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="jobs.php" class="btn btn-outline-primary">모든 작업 보기</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 빠른 작업 시작 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-lightning-charge me-2"></i>빠른 작업 시작
                    </div>
                    <div class="card-body">
                        <p>OCR 처리를 빠르게 시작하려면 아래 버튼을 클릭하세요:</p>
                        <div class="d-grid gap-2">
                            <a href="upload.php" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>이미지 업로드 및 OCR 처리
                            </a>
                            <a href="templates.php" class="btn btn-outline-info">
                                <i class="bi bi-file-earmark-text me-2"></i>문서 템플릿 관리
                            </a>
                            <a href="dictionary.php" class="btn btn-outline-secondary">
                                <i class="bi bi-book me-2"></i>사용자 정의 사전 관리
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- 템플릿 추천 -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-lightbulb me-2"></i>문서 템플릿 추천
                    </div>
                    <div class="card-body">
                        <?php if (empty($templates)): ?>
                            <div class="alert alert-info">
                                생성된 템플릿이 없습니다. 템플릿을 생성하면 OCR 인식률을 높일 수 있습니다.
                            </div>
                            <a href="templates.php?action=create" class="btn btn-sm btn-info">
                                <i class="bi bi-plus-circle me-2"></i>템플릿 생성하기
                            </a>
                        <?php else: ?>
                            <p class="small">자주 사용하는 템플릿:</p>
                            <ul class="list-group">
                                <?php foreach (array_slice($templates, 0, 3) as $template): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($template['name']); ?></span>
                                        <a href="upload.php?template=<?php echo $template['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            사용
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (count($templates) > 3): ?>
                                <div class="text-end mt-2">
                                    <a href="templates.php" class="small">모든 템플릿 보기</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 시스템 안내 및 팁 -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-info-circle me-2"></i>OCR 인식률 향상 팁
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>더 나은 결과를 위한 팁:</h5>
                        <ul>
                            <li>가능한 한 고해상도 이미지를 사용하세요.</li>
                            <li>반복되는 문서 유형은 템플릿을 생성하여 인식률을 높이세요.</li>
                            <li>자주 오인식되는 단어는 사용자 사전에 추가하세요.</li>
                            <li>테이블이 포함된 문서는 테이블 구조를 템플릿에 정의하면 더 정확한 결과를 얻을 수 있습니다.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>시스템 기능:</h5>
                        <ul>
                            <li><strong>이미지 전처리:</strong> 흐릿한 이미지도 자동으로 선명하게 개선합니다.</li>
                            <li><strong>문서 템플릿:</strong> 특정 문서 유형에 대한 인식률을 높입니다.</li>
                            <li><strong>사용자 사전:</strong> 자주 오인식되는 단어를 자동으로 보정합니다.</li>
                            <li><strong>테이블 인식:</strong> 테이블 구조를 정확하게 추출합니다.</li>
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
</body>
</html>
