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

// 삭제 처리
if (isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();
        
        $deleteStmt = $pdo->prepare("DELETE FROM application_recovery_prohibition_orders WHERE case_no = ?");
        $result = $deleteStmt->execute([$case_no]);
        
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

// 기존 금지명령 조회
$stmt = $pdo->prepare("SELECT * FROM application_recovery_prohibition_orders WHERE case_no = ?");
$stmt->execute([$case_no]);
$order_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $application = $_POST['application'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if ($order_info) {
            $stmt = $pdo->prepare("
                UPDATE application_recovery_prohibition_orders 
                SET 
                    application = ?,
                    purpose = ?,
                    reason = ?,
                    updated_at = NOW()
                WHERE case_no = ?
            ");
            
            $result = $stmt->execute([
                $application,
                $purpose,
                $reason,
                $case_no
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO application_recovery_prohibition_orders 
                (case_no, application, purpose, reason) 
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $case_no,
                $application,
                $purpose,
                $reason
            ]);
        }

        if (!$result) {
            throw new Exception("데이터베이스 쿼리 실행 실패");
        }

        $pdo->commit();

        echo "<script>
            alert('저장되었습니다.');
            if (window.opener && typeof window.opener.updateProhibitionCount === 'function') {
                window.opener.updateProhibitionCount();
            }
            window.location.href = '?case_no=" . $case_no . "';
        </script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("금지명령서 저장 오류: " . $e->getMessage());
        echo "<script>alert('저장 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "');</script>";
    }
} else {
    $income_type = $_GET['income_type'] ?? 0;
    $income_type_changed = $_GET['income_type_changed'] ?? 0;
    
    if ($income_type_changed == 1) {
        $deleteStmt = $pdo->prepare("DELETE FROM application_recovery_prohibition_orders WHERE case_no = ?");
        $deleteStmt->execute([$case_no]);
    }
}

// 기본값 설정
$defaultApplication = "
사    건   {$case_info['case_number']} 개인회생

신 청 인   {$case_info['name']}
(채무자)   주소: {$case_info['now_address']}";

$defaultPurpose = '';
$defaultReason = '';
if ($income_type == 1) {
    $defaultPurpose = "신청인에 대한 이 법원 {$case_info['case_number']} 개인회생사건에 관하여 개인회생절차의 개시신청에 대한 결정이 있을 때까지
 신청인에 대하여 그 결정 전의 원인으로 생긴 재산상의 청구권에 기하여 신청인의 소득 및 신청인이 영업을 함에 필요한 설비, 기구 및 기타 기자재에 대하여 하는 가압류 또는 가처분 등 강제집행 절차 또는 행위를 금지한다. 다만, 소송행위를 제외한다.
 라는 결정을 구합니다.";
    
    $defaultReason = "1. 신청인은 귀원 {$case_info['case_number']} 개인회생 사건의 신청 채무자입니다.
2. 신청인은 위 개인회생사건에서 신청인이 영업활동을 통해 얻는 소득에서 최저생계비를 제외한 나머지 가용소득으로 채무를 변제하는 계획안을 제출하였습니다.
3. 현재 신청인이 영업을 영위하기 위하여 필요한 설비, 기구, 기타 기자재에 대하여 아직 가압류, 가처분 또는 압류의 집행이 없는바, 채권자들이 신청인 소유의 설비, 기구, 기타 기자재 및 신청인의 소득 등에 대하여 강제집행, 가압류 또는
   가처분을 하게 되면 신청인의 개인회생절차에 따른 변제계획의 수행에 큰 어려움이 생길 것입니다.
4. 또한, 채권자들이 신청인으로부터 개인회생채권을 변제받거나 변제를 요구하는 행위를 할 경우 채권자간의 형평을 해하게 되며, 신청인의 정상적인 생활에도 지장을 초래하게 될 것입니다.
5. 따라서 신청인은 신청인 소유의 설비, 기구, 기타 기자재 및 신청인 소유의 소득에 대한 강제집행, 가압류 또는 가처분과 개인회생채권의 변제요구행위를 금지시켜야 할 필요가 있으므로, 채무자 회생 및 파산에 관한 법률 제593조 제1항에 의하여 이 신청에 이르게 되었습니다.

									" . date('Y. m. d.') . "
									신청인 | {$case_info['name']}
									연락처 | {$case_info['phone']}
									위 대리인 | 변호사 {$case_info['representative_name']} (인)

                                                                                                                                       관할법원 귀중";
} else {
    $defaultPurpose = "신청인에 대한 이 법원 {$case_info['case_number']} 개인회생사건에 관하여 개인회생절차의 개시신청에 대한 결정이 있을 때까지
다음의 각 절차 또는 행위를 금지한다.
1. 개인회생채권에 기하여 신청인 소유의 유체동산과 신청인이 사용자로부터 매월 지급받을 급료, 제수당, 상여금
   기타 명목의 급여 및 퇴직금에 대하여 하는 강제집행, 가압류 또는 가처분.
2. 개인회생채권을 변제받거나 변제를 요구하는 일체의 행위. 다만, 소송행위를 제외한다.
라는 결정을 구합니다.";
    
    $defaultReason = "1. 신청인은 귀원 {$case_info['case_number']} 개인회생 사건의 신청 채무자입니다.
2. 신청인은 위 개인회생 사건에서 신청인이 매월 {$case_info['workplace']}에서 지급받는 급여에서 생계비를 제외한
   나머지 가용소득으로 채무를 변제하는 계획안을 제출하였습니다.
3. 현재 신청인 소유의 유체동산과 신청인이 사용자로부터 매월 지급받을 급여 및 퇴직금에 대하여는 아직 가압류 또는
   압류의 집행이 없는 바, 채권자들이 신청인 소유의 유체동산이나 신청인의 급여 등에 대하여 강제집행, 가압류 또는
   가처분을 하게 되면 신청인의 개인회생절차에 따른 변제계획의 수행에 큰 어려움이 생길 것입니다.
4. 또한, 채권자들이 신청인으로부터 개인회생채권을 변제받거나 변제를 요구하는 행위를 할 경우 채권자간의 형평을 해하게 되며,
   신청인의 정상적인 생활에도 지장을 초래하게 될 것입니다.
5. 따라서 신청인은 신청인 소유의 유체동산과 급여 및 퇴직금에 대한 강제집행, 가압류 또는 가처분과 개인회생채권의 변제요구행위를
   금지시켜야 할 필요가 있으므로, 채무자 회생 및 파산에 관한 법률 제593조 제1항에 의하여 이 신청에 이르게 되었습니다.

									" . date('Y. m. d.') . "
									신청인 | {$case_info['name']}
									연락처 | {$case_info['phone']}
									위 대리인 | 변호사 {$case_info['representative_name']} (인)

                                                                                                                                       관할법원 귀중";
}	

$application = $order_info['application'] ?? $defaultApplication;
$purpose = $order_info['purpose'] ?? $defaultPurpose;
$reason = $order_info['reason'] ?? $defaultReason;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>금지명령신청서</title>
    <link rel="stylesheet" href="../../css/prohibition_order.css">
</head>
<body>
	<div class="section-header">금지명령신청서</div>
    <div class="container">
        <div class="document-info">
			귀하의 재산에 현재 강제집행이 진행 중이거나 강제집행 될 우려가 있는 경우 이미 집행된 강제집행의 중지나 앞으로 예상되는 강제집행의 금지를 신청할 수 있습니다.
		</div>
		
		<? if ($income_type == 1) { ?>
		<div class="document-title"><span>영업소득자</span> 금지명령 신청서</div>
		<? } else { ?>
		<div class="document-title"><span>급여소득자</span> 금지명령 신청서</div>
		<? } ?>
        <form method="post" id="prohibitionForm">
            <input type="hidden" name="case_no" value="<?= $case_no ?>">
            <div class="form-group-label">금지명령 신청서</div>
            <div class="form-group-grid">
                <div class="grid-row">
                    <div class="grid-label">사 건</div>
                    <div class="grid-value">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($case_info['case_number']) ?> 개인회생</div>
                </div>
                <div class="grid-row">
                    <div class="grid-label">신 청 인</div>
                    <div class="grid-value">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($case_info['name']) ?></div>
                </div>
                <div class="grid-row">
                    <div class="grid-label">주 소 (채무자)</div>
                    <div class="grid-value">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?= $case_info['now_address'] ? htmlspecialchars($case_info['now_address']) : '' ?></div>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-group-label">신청취지</div>
                <textarea name="purpose" required><?= htmlspecialchars($purpose) ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-group-label">신청원인</div>
                <textarea name="reason" required><?= htmlspecialchars($reason) ?></textarea>
            </div>
            
            <div class="btn-group no-print">
                <button type="submit" class="btn">저장</button>
                <button type="button" class="btn" onclick="printOrder()">인쇄</button>
                <button type="button" class="btn" onclick="deleteOrder()">삭제</button>
                <button type="button" class="btn" onclick="window.close()">닫기</button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#prohibitionForm').on('submit', function(e) {
            if (!confirm('저장하시겠습니까?')) {
                e.preventDefault();
            }
        });
    });

    function printOrder() {
        if (!<?= $order_info ? 'true' : 'false' ?>) {
            alert('먼저 금지명령신청서를 저장해주세요.');
            return;
        }
        window.print();
    }

    function deleteOrder() {
        if (!confirm('정말로 이 금지명령신청서를 삭제하시겠습니까?')) {
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
                    if (window.opener && typeof window.opener.updateProhibitionCount === 'function') {
                        window.opener.updateProhibitionCount();
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
            const formData = $('#prohibitionForm').serialize();
            
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
    $('#prohibitionForm').on('submit', function() {
        formChanged = false;
    });
    </script>
</body>
</html>
