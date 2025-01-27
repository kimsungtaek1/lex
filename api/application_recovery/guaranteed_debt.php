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
        FROM application_recovery_creditor_guaranteed_debts
        WHERE case_no = ? AND creditor_count = ?
        ORDER BY debt_no
    ");
    $stmt->execute([$case_no, $creditor_count]);
    $debts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("보증인채무 조회 오류: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보증인이 있는 채무</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .btn-group { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>보증인이 있는 채무 목록</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="addDebt()">추가</button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>번호</th>
                    <th>보증인명</th>
                    <th>보증인주소</th>
                    <th>보증금액</th>
                    <th>보증일자</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debts as $index => $debt): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($debt['guarantor_name']) ?></td>
                    <td><?= htmlspecialchars($debt['guarantor_address']) ?></td>
                    <td><?= number_format($debt['guarantee_amount']) ?></td>
                    <td><?= date('Y-m-d', strtotime($debt['guarantee_date'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editDebt(<?= $debt['debt_no'] ?>)">수정</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDebt(<?= $debt['debt_no'] ?>)">삭제</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 모달 -->
    <div class="modal fade" id="debtModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">보증인이 있는 채무</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="debtForm">
                        <input type="hidden" name="debt_no" id="debtNo">
                        <div class="mb-3">
                            <label class="form-label">보증인명</label>
                            <input type="text" class="form-control" id="guarantorName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">보증인주소</label>
                            <input type="text" class="form-control" id="guarantorAddress" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">보증금액</label>
                            <input type="text" class="form-control" id="guaranteeAmount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">보증일자</label>
                            <input type="date" class="form-control" id="guaranteeDate" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="saveDebt()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let debtModal;
        
        $(document).ready(function() {
            debtModal = new bootstrap.Modal(document.getElementById('debtModal'));
            
            // 금액 입력 시 자동 콤마
            $('#guaranteeAmount').on('input', function() {
                let value = this.value.replace(/[^\d]/g, '');
                if (value) {
                    this.value = Number(value).toLocaleString();
                }
            });
        });

        function addDebt() {
            $('#debtNo').val('');
            $('#debtForm')[0].reset();
            debtModal.show();
        }

        function editDebt(debtNo) {
            $.get('get_guaranteed_debt.php', { debt_no: debtNo }, function(response) {
                if (response.success) {
                    $('#debtNo').val(response.data.debt_no);
                    $('#guarantorName').val(response.data.guarantor_name);
                    $('#guarantorAddress').val(response.data.guarantor_address);
                    $('#guaranteeAmount').val(Number(response.data.guarantee_amount).toLocaleString());
                    $('#guaranteeDate').val(response.data.guarantee_date);
                    debtModal.show();
                } else {
                    alert(response.message);
                }
            });
        }

        function saveDebt() {
            const formData = {
                debt_no: $('#debtNo').val(),
                guarantor_name: $('#guarantorName').val(),
                guarantor_address: $('#guarantorAddress').val(),
                guarantee_amount: $('#guaranteeAmount').val().replace(/,/g, ''),
                guarantee_date: $('#guaranteeDate').val(),
                case_no: '<?= $case_no ?>',
                creditor_count: '<?= $creditor_count ?>'
            };

            $.ajax({
                url: 'save_guaranteed_debt.php',
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

        function deleteDebt(debtNo) {
            if (!confirm('삭제하시겠습니까?')) return;

            $.ajax({
                url: 'delete_guaranteed_debt.php',
                type: 'POST',
                data: { debt_no: debtNo },
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