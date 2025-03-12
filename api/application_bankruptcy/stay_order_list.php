<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

$case_no = $_GET['case_no'] ?? '';
if (empty($case_no)) {
    exit("사건 번호가 필요합니다.");
}

try {
    // 사건 정보 조회
    $stmt = $pdo->prepare("
        SELECT cm.*, ar.name
        FROM case_management cm
        LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no
        WHERE cm.case_no = ?
    ");
    $stmt->execute([$case_no]);
    $case_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case_info) {
        exit("사건 정보를 찾을 수 없습니다.");
    }

    // 중지명령 목록 조회
    $stmt = $pdo->prepare("
		SELECT so.*, cm.case_number, 
			   DATE_FORMAT(so.created_at, '%Y. %m. %d.') as formatted_date,
			   e.name as assigned_name,
			   e.position as assigned_position
		FROM application_recovery_stay_orders so
		JOIN case_management cm ON so.case_no = cm.case_no
		LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no
		LEFT JOIN employee e ON ar.assigned_employee = e.employee_no
		WHERE so.case_no = ? 
		ORDER BY so.created_at DESC
	");
    $stmt->execute([$case_no]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit("데이터베이스 오류: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>중지명령신청서 목록</title>
    <link rel="stylesheet" href="../../css/stay_order_list.css">
</head>
<body>
    <div class="container">
        <div class="section-header">
            중지명령신청서 목록
        </div>
        
        <div class="content-wrapper">
            <?php if (count($orders) > 0): ?>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>작성자</th>
                            <th>작성일자</th>
							<th>기능</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $index => $order): ?>
                        <tr onclick="openOrder(<?= $order['order_no'] ?>)">
                            <td><?= count($orders) - $index ?></td>
                            <td><?= htmlspecialchars($order['assigned_name']) ?></td>
                            <td><?= $order['formatted_date'] ?></td>
							<td>
								<button class="btn">인쇄</button>
							</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-message">
                <p>작성된 중지명령신청서가 없습니다.</p>
            </div>
            <?php endif; ?>
            
            <div class="button-container">'
                <button type="button" class="btn" onclick="openNewOrder()">작성</button>
				<button type="button" class="btn" onclick="window.close()">닫기</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function openOrder(orderNo) {
        const width = 1000;
        const height = 800;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        
        const url = `stay_order.php?case_no=<?= $case_no ?>&order_no=${orderNo}`;
        
        window.open(
            url,
            'stay_order_window',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
        );
    }
    
    function openNewOrder() {
        const width = 1000;
        const height = 800;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        
        const stayOrderWindow = window.open(
            'stay_order.php?case_no=<?= $case_no ?>&parent_reload=true',
            'stayOrderWindow',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
        );
    }

    function printOrder(orderNo) {
        const width = 800;
        const height = 600;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;

        window.open(
            `print_stay_order.php?order_no=${orderNo}`,
            'print_window',
            `width=${width},height=${height},left=${left},top=${top}`
        );
    }

    function deleteOrder(orderNo) {
        if (!confirm('이 중지명령신청서를 삭제하시겠습니까?')) {
            return;
        }

        $.ajax({
            url: 'delete_stay_order.php',
            method: 'POST',
            data: { order_no: orderNo },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('삭제되었습니다.');
                    if (window.opener) {
                        window.opener.location.reload();
                    }
                    window.location.reload();
                } else {
                    alert('삭제 실패: ' + (response.message || '알 수 없는 오류'));
                }
            },
            error: function() {
                alert('서버와 통신 중 오류가 발생했습니다.');
            }
        });
    }

    function updateParentCount() {
        if (window.opener && window.opener.updateStayOrderCount) {
            window.opener.updateStayOrderCount();
        }
    }

    window.onunload = function() {
        updateParentCount();
    };
    </script>
</body>
</html>