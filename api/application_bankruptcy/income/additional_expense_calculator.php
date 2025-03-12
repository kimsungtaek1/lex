<?php
session_start();
if (!isset($_SESSION['employee_no'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../../../config.php';

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $case_no = $_POST['case_no'] ?? $_GET['case_no'] ?? null;

    try {
        switch ($action) {
            case 'save':
				try {
					$pdo->beginTransaction();
					
					// 기존 데이터 삭제
					$stmt = $pdo->prepare("DELETE FROM application_recovery_living_expenses WHERE case_no = ?");
					$stmt->execute([$_POST['case_no']]);
					
					// 새 데이터 저장
					$stmt = $pdo->prepare("INSERT INTO application_recovery_living_expenses 
						(case_no, type, amount, reason, additional_note) VALUES (?, ?, ?, ?, ?)");
					
					$expenses = json_decode($_POST['expenses'], true);
					$additional_note = $_POST['additional_note'];
					
					foreach ($expenses as $expense) {
						$stmt->execute([
							$_POST['case_no'],
							$expense['type'],
							$expense['amount'],
							$expense['reason'],
							$additional_note
						]);
					}
					
					$pdo->commit();
					echo json_encode(['success' => true]);
				} catch (Exception $e) {
					$pdo->rollBack();
					echo json_encode([
						'success' => false, 
						'message' => $e->getMessage(),
						'post_data' => $_POST
					]);
				}
				break;

            case 'get':
                $stmt = $pdo->prepare("SELECT * FROM application_recovery_living_expenses WHERE case_no = ?");
                $stmt->execute([$case_no]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'additional_note' => $data[0]['additional_note'] ?? null
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM application_recovery_living_expenses WHERE case_no = ?");
                $stmt->execute([$case_no]);
                echo json_encode(['success' => true]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// HTML 출력
?>
<link rel="stylesheet" href="../../../css/additional_expense_calculator.css">
<div class="content-wrapper">
    <!-- 제목 영역 -->
    <div class="appendix-title">추가생계비계산기</div>
    
    <!-- 테이블 영역 -->
    <div class="appendix-table">
        <!-- 테이블 헤더 -->
        <div class="table-header">
            <div class="col">| 비목</div>
            <div class="col">| 생계비 추가금액</div>
            <div class="col">| 추가지출사유</div>
        </div>
    </div>
    
    <div class="left-section">
        <div class="form">
            <div class="form-title"><span>생계비</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="amount_생계비">원
                <input type="text" class="form-control" id="reason_생계비">
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>주거비</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="amount_주거비">원
                <input type="text" class="form-control" id="reason_주거비">
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>의료비</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="amount_의료비">원
                <input type="text" class="form-control" id="reason_의료비">
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>교육비</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="amount_교육비">원
                <input type="text" class="form-control" id="reason_교육비">
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>기타</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="amount_기타">원
                <input type="text" class="form-control" id="reason_기타">
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>합계</span></div>
            <div class="form-content">
                <input type="text" class="form-control" id="totalAmount" readonly>원
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span>추가지출사유<br>보충기재사항</span></div>
            <div class="form-content form-content-3">
                <textarea class="textarea" id="additional_note" rows="6"></textarea>
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span></span></div>
            <div class="form-content btn-right">
				<button type="button" onclick="window.close()">닫기</button>
                <button type="button" class="btn-delete" onclick="deleteExpense()">삭제</button>
                <button type="button" class="btn-save" onclick="saveAllExpenses()">저장</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // 금액 입력 필드에 숫자 포맷팅과 합계 계산 이벤트 추가
    $('[id^=amount_]').on('input', function() {
        // 숫자 외 문자 제거
        let value = $(this).val().replace(/[^\d]/g, '');
        // 숫자 포맷팅
        if(value) {
            value = parseInt(value).toLocaleString();
        }
        $(this).val(value);
        
        // 합계 계산
        let total = 0;
        $('[id^=amount_]').each(function() {
            let amount = $(this).val().replace(/[^\d]/g, '') || 0;
            total += parseInt(amount);
        });
        $('#totalAmount').val(total.toLocaleString());
    });

    // 초기 데이터 로드
    loadExpenses();
});

function saveAllExpenses() {
    let caseNo = new URLSearchParams(window.location.search).get('case_no');
    let formData = new FormData();
    formData.append('action', 'save');
    formData.append('case_no', caseNo);
    
    let expenses = [];
    ['생계비', '주거비', '의료비', '교육비', '기타'].forEach(type => {
        let amount = $(`#amount_${type}`).val().replace(/[^\d]/g, '') || 0;
        let reason = $(`#reason_${type}`).val();
        if(amount > 0 || reason) {
            expenses.push({
                type: type,
                amount: amount,
                reason: reason
            });
        }
    });
    
    formData.append('expenses', JSON.stringify(expenses));
    formData.append('additional_note', $('#additional_note').val());

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if(response.success) {
                alert('저장되었습니다.');
                loadExpenses();
                if(window.opener && !window.opener.closed) {
                    window.opener.$('#iex_additional_expense').val($('#totalAmount').val());
                    window.opener.incomeExpenditure.calculateTotalExpense();
                }
            } else {
                alert(response.message || '저장에 실패했습니다.');
                console.log(response);
            }
        },
        error: function(xhr, status, error) {
            alert('저장 중 오류가 발생했습니다.');
            console.log(xhr.responseText);
        }
    });
}

function loadExpenses() {
    let caseNo = new URLSearchParams(window.location.search).get('case_no');
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: { action: 'get', case_no: caseNo },
        success: function(response) {
            if(response.success) {
                // 폼 초기화
                $('[id^=amount_]').val('');
                $('[id^=reason_]').val('');
                $('#additional_note').val('');
                
                // 데이터 채우기
                response.data.forEach(item => {
                    $(`#amount_${item.type}`).val(parseInt(item.amount).toLocaleString());
                    $(`#reason_${item.type}`).val(item.reason);
                });
                
                if(response.additional_note) {
                    $('#additional_note').val(response.additional_note);
                }
                
                // 합계 계산
                let total = response.data.reduce((sum, item) => sum + parseInt(item.amount), 0);
                $('#totalAmount').val(total.toLocaleString());
            }
        }
    });
}

function deleteExpense() {
    if(!confirm('모든 데이터를 삭제하시겠습니까?')) return;
    
    let caseNo = new URLSearchParams(window.location.search).get('case_no');
    
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: { action: 'delete', case_no: caseNo },
        success: function(response) {
            if(response.success) {
                alert('삭제되었습니다.');
                $('[id^=amount_]').val('');
                $('[id^=reason_]').val('');
                $('#additional_note').val('');
                $('#totalAmount').val('0');
                
                if(window.opener && !window.opener.closed) {
                    window.opener.$('#iex_additional_expense').val('0');
                    window.opener.incomeExpenditure.calculateTotalExpense();
                }
            } else {
                alert('삭제에 실패했습니다.');
            }
        },
        error: function() {
            alert('삭제 중 오류가 발생했습니다.');
        }
    });
}
</script>