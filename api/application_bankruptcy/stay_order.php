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
$order_no = $_GET['order_no'] ?? null;

// 삭제 처리
if (isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();
        
        $deleteStmt = $pdo->prepare("DELETE FROM application_recovery_stay_orders WHERE order_no = ?");
        $result = $deleteStmt->execute([$order_no]);
        
        if ($result) {
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        } else {
            throw new Exception("삭제 실패");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT 
        cm.case_no, 
        cm.case_number,
        cm.category, 
        cm.status,
        ar.name,
        ar.recovery_no,
        ar.resident_number,
        ar.now_address,
        ar.workplace,
        ar.is_company,
        ar.phone,
        (SELECT name FROM employee WHERE position = '대표' LIMIT 1) as representative_name,
        (SELECT position FROM employee WHERE position = '대표' LIMIT 1) as representative_position
    FROM case_management cm
    LEFT JOIN application_recovery ar ON cm.case_no = ar.case_no 
    WHERE cm.case_no = ?
");
$stmt->execute([$case_no]);
$case_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case_info) {
    exit("사건 정보를 찾을 수 없습니다.");
}

// 기존 중지명령 조회
$stmt = $pdo->prepare("SELECT * FROM application_recovery_stay_orders WHERE case_no = ?");
$stmt->execute([$case_no]);
$order_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();

        $application = $_POST['application'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $method = $_POST['method'] ?? '';
        
        if ($order_info) {
            $stmt = $pdo->prepare("
                UPDATE application_recovery_stay_orders 
                SET 
                    application = ?,
                    purpose = ?,
                    reason = ?,
                    method = ?,
                    updated_at = NOW()
                WHERE case_no = ?
            ");
            
            $result = $stmt->execute([
                $application,
                $purpose,
                $reason,
                $method,
                $case_no
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO application_recovery_stay_orders 
                (case_no, application, purpose, reason, method, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $case_no,
                $application,
                $purpose,
                $reason,
                $method
            ]);
        }

        if (!$result) {
            throw new Exception("데이터베이스 쿼리 실행 실패");
        }

        $pdo->commit();

        echo "<script>
            alert('저장되었습니다.');
            if (window.opener && typeof window.opener.updateStayOrderCount === 'function') {
                window.opener.updateStayOrderCount();
            }
            window.location.href = '?case_no=" . $case_no . "';
        </script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("중지명령서 저장 오류: " . $e->getMessage());
        echo "<script>alert('저장 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// 기본값 설정
$defaultApplication = "
사    건   {$case_info['case_number']} 개인회생

신 청 인   {$case_info['name']}
(채무자)   주소: {$case_info['now_address']}";

$defaultPurpose = "다음 각호의 내용에 대하여 중지를 구합니다.

1. 채권자들의 강제집행, 가압류, 가처분 중지
2. 채권자들의 변제 독촉행위 중지";

$defaultReason = "1. 신청인은 상당한 채무초과 상태에 있어 회생절차를 신청하게 되었습니다.

2. 채권자들의 개별적인 강제집행 등으로 인하여 신청인의 회생절차 진행에 중대한 지장이 있을 것으로 예상됩니다.

3. 이에 신청인은 채무자 회생 및 파산에 관한 법률 제45조에 의하여 이 신청에 이르게 되었습니다.

									" . date('Y. m. d.') . "
									신청인 | {$case_info['name']}
									연락처 | {$case_info['phone']}
									위 대리인 | 변호사 {$case_info['representative_name']} (인)

																			관할법원 귀중";

$defaultMethod = "1. 사업자등록증
2. 급여명세서
3. 재직증명서";

$application = $order_info['application'] ?? $defaultApplication;
$purpose = $order_info['purpose'] ?? $defaultPurpose;
$reason = $order_info['reason'] ?? $defaultReason;
$method = $order_info['method'] ?? $defaultMethod;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>중지명령신청서</title>
    <link rel="stylesheet" href="../../css/stay_order.css">
</head>
<body>
    <div class="section-header">중지명령신청서</div>
    <div class="container">
        <div class="document-info">
            귀하의 재산에 현재 강제집행이 진행 중이거나 강제집행 될 우려가 있는 경우 이미 집행된 강제집행의 중지나 앞으로 예상되는 강제집행의 중지를 신청할 수 있습니다.
        </div>
        
        <div class="document-title">중지명령 신청서</div>
        <form method="post" id="stayOrderForm">
            <input type="hidden" name="case_no" value="<?= $case_no ?>">
            <div class="form-group-label">중지명령 신청서</div>
            <table class="table">
                <tr>
                    <td>사&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;건&nbsp;&nbsp;|</td>
                    <td><?= htmlspecialchars($case_info['case_number']) ?> 개인회생</td>
                </tr>
                <tr>
                    <td>신&nbsp;&nbsp;&nbsp;청&nbsp;&nbsp;&nbsp;인&nbsp;&nbsp;|</td>
                    <td><?= htmlspecialchars($case_info['name']) ?></td>
                </tr>
                <tr>
                    <td>주소(채무자)&nbsp;&nbsp;|</td>
                    <td><?= htmlspecialchars($case_info['now_address']) ?></td>
                </tr>
            </table>
            
            <div class="form-group">
                <div class="form-group-label">신청취지</div>
                <textarea name="purpose" required><?= htmlspecialchars($purpose) ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-group-label">신청원인</div>
                <textarea name="reason" required><?= htmlspecialchars($reason) ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-group-label">소명방법</div>
                <textarea name="method" required><?= htmlspecialchars($method) ?></textarea>
            </div>
            
            <div class="btn-group no-print">
                <button type="submit" class="btn">저장</button>
                <button type="button" class="btn" onclick="printOrder()">인쇄</button>
                <button type="button" class="btn" onclick="deleteOrder()">삭제</button>
                <button type="button" class="btn" onclick="goList()">목록</button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#stayOrderForm').on('submit', function(e) {
            if (!confirm('저장하시겠습니까?')) {
                e.preventDefault();
            }
        });
    });

    function printOrder() {
        if (!<?= $order_info ? 'true' : 'false' ?>) {
            alert('먼저 중지명령신청서를 저장해주세요.');
            return;
        }
        window.print();
    }

    function deleteOrder() {
        if (!confirm('정말로 이 중지명령신청서를 삭제하시겠습니까?')) {
            return;
        }

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                delete: true
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('삭제되었습니다.');
                    if (window.opener && typeof window.opener.updateStayOrderCount === 'function') {
                        window.opener.updateStayOrderCount();
                    }
                    window.close();
                } else {
                    alert('삭제 중 오류가 발생했습니다: ' + (response.message || '알 수 없는 오류'));
                }
            },
            error: function() {
                alert('서버와 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // Textarea 자동 크기 조절
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight + 2) + 'px';
        });
        
        // 초기 높이 설정
        textarea.dispatchEvent(new Event('input'));
    });

    // 이전에 작성한 내용 자동 저장
    let autoSaveTimeout;
    const autosave = function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            const formData = $('#stayOrderForm').serialize();
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                success: function() {
                    console.log('자동 저장 완료');
                },
                error: function() {
                    console.error('자동 저장 실패');
                }
            });
        }, 3000);
    };

    // textarea 변경 시 자동 저장 실행
    $('textarea').on('input', autosave);

    // 페이지 벗어날 때 경고
    let formChanged = false;
    $('textarea').on('input', function() {
        formChanged = true;
    });

    window.onbeforeunload = function() {
        if (formChanged) {
            return '저장하지 않은 변경사항이 있습니다. 정말로 페이지를 벗어나시겠습니까?';
        }
    };

    // 폼 제출 시 경고 메시지 제거
    $('#stayOrderForm').on('submit', function() {
        formChanged = false;
    });
	
	function goList() {
		const urlParams = new URLSearchParams(window.location.search);
		const caseNo = urlParams.get('case_no');
		window.location.href = 'stay_order_list.php?case_no=' + caseNo;
	}
    </script>
</body>
</html>