<?php
/**
 * OCR 인식률 향상 시스템 - 작업 리스트
 * 카페24 웹호스팅 환경에 최적화됨
 */
require_once 'config.php';
require_once 'process_monitor.php';
require_once 'document_learning.php';
require_once 'utils.php';

// 프로세스 모니터 초기화
$processMonitor = new OCRProcessMonitor();

$jobs = $processMonitor->getJobs(100, 0);

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
$pageTitle = '작업 리스트';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);}
        .table thead th { background-color: #f8f9fa;}
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4"><i class="bi bi-list-task me-2"></i><?php echo h($pageTitle); ?></h1>
    <div class="card">
        <div class="card-body">
            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">등록된 작업이 없습니다.</div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>작업 ID</th>
                        <th>작업명</th>
                        <th>상태</th>
                        <th>생성일</th>
                        <th>최종 업데이트</th>
                        <th>진행률</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?php echo h($job['id']); ?></td>
                        <td><?php echo h($job['name']); ?></td>
                        <td><span class="badge bg-<?php echo getStatusColor($job['status']); ?>"><?php echo getStatusText($job['status']); ?></span></td>
                        <td><?php echo h(date('Y-m-d H:i:s', strtotime($job['created_at']))); ?></td>
                        <td><?php echo h(date('Y-m-d H:i:s', strtotime($job['updated_at']))); ?></td>
                        <td>
                            <?php
                                $progress = 0;
                                if (isset($job['processed_count']) && isset($job['total_files']) && $job['total_files'] > 0) {
                                    $progress = round($job['processed_count'] / $job['total_files'] * 100);
                                }
                            ?>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-<?php echo getStatusColor($job['status']); ?>" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $progress; ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="view_job.php?id=<?php echo h($job['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> 상세</a>
                            <a href="edit_job.php?id=<?php echo h($job['id']); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> 수정</a>
                            <button class="btn btn-sm btn-outline-danger delete-job-btn" data-job-id="<?php echo h($job['id']); ?>"><i class="bi bi-trash"></i> 삭제</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('.delete-job-btn').click(function() {
        if (!confirm('정말로 이 작업을 삭제하시겠습니까?')) return;
        var jobId = $(this).data('job-id');
        $.ajax({
            url: 'ajax_delete_job.php',
            type: 'POST',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('작업이 삭제되었습니다.');
                    location.reload();
                } else {
                    alert('작업 삭제 중 오류: ' + response.message);
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
