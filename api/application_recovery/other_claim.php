<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

$case_no = $_GET['case_no'] ?? '';
$creditor_count = $_GET['creditor_count'] ?? '';

if (!$case_no || !$creditor_count) {
    exit("필수 파라미터가 누락되었습니다.");
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM application_recovery_creditor_other_claims
        WHERE case_no = ? AND creditor_count = ?
        ORDER BY claim_no
    ");
    $stmt->execute([$case_no, $creditor_count]);
    $claims = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("기타미확정채권 조회 오류: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>기타미확정채권</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .btn-group { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>기타미확정채권 목록</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="addClaim()">추가</button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>번호</th>
                    <th>채권종류</th>
                    <th>금액</th>
                    <th>설명</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $index => $claim): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($claim['claim_type']) ?></td>
                    <td><?= number_format($claim['amount']) ?></td>
                    <td><?= htmlspecialchars($claim['description']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editClaim(<?= $claim['claim_no'] ?>)">수정</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClaim(<?= $claim['claim_no'] ?>)">삭제</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 모달 -->
    <div class="modal fade" id="claimModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">기타미확정채권</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="claimForm">
                        <input type="hidden" name="claim_no" id="claimNo">
                        <div class="mb-3">
                            <label class="form-label">채권종류</label>
                            <input type="text" class="form-control" id="claimType" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">금액</label>
                            <input type="text" class="form-control" id="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="saveClaim()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let claimModal;
        
        $(document).ready(function() {
            claimModal = new bootstrap.Modal(document.getElementById('claimModal'));
            
            // 금액 입력 시 자동 콤마
            $('#amount').on('input', function() {
                let value = this.value.replace(/[^\d]/g, '');
                if (value) {
                    this.value = Number(value).toLocaleString();
                }
            });
        });

        function addClaim() {
            $('#claimNo').val('');
            $('#claimForm')[0].reset();
            claimModal.show();
        }

        function editClaim(claimNo) {
            $.get('get_other_claim.php', { claim_no: claimNo }, function(response) {
                if (response.success) {
                    $('#claimNo').val(response.data.claim_no);
                    $('#claimType').val(response.data.claim_type);
                    $('#amount').val(Number(response.data.amount).toLocaleString());
                    $('#description').val(response.data.description);
                    claimModal.show();
                } else {
                    alert(response.message);
                }
            });
        }

        function saveClaim() {
            const formData = {
                claim_no: $('#claimNo').val(),
                claim_type: $('#claimType').val(),
                amount: $('#amount').val().replace(/,/g, ''),
                description: $('#description').val(),
                case_no: '<?= $case_no ?>',
                creditor_count: '<?= $creditor_count ?>'
            };

            $.ajax({
                url: 'save_other_claim.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert('저장되었습니다.');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function deleteClaim(claimNo) {
            if (!confirm('삭제하시겠습니까?')) return;

            $.ajax({
                url: 'delete_other_claim.php',
                type: 'POST',
                data: { claim_no: claimNo },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }
    </script>
</body>
</html>