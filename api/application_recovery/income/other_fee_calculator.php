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
					
					// 금액에서 콤마 제거 후 정수로 변환
					$amount = (int)str_replace(',', '', $_POST['amount']);
					
					// 이미 존재하는 레코드인지 확인 (case_no와 creditor_count 기준)
					$checkSql = "SELECT other_fee_id FROM application_recovery_income_other_fee
								 WHERE case_no = :case_no AND creditor_count = :creditor_count";
					$checkStmt = $pdo->prepare($checkSql);
					$checkStmt->execute([
						'case_no' => $case_no,
						'creditor_count' => $_POST['creditor_count']
					]);
					$existing_id = $checkStmt->fetchColumn();

					if ($existing_id) {
						// UPDATE 쿼리
						$sql = "UPDATE application_recovery_income_other_fee
								SET claim_type = :claim_type,
									amount = :amount,
									description = :description,
									payment_term = :payment_term
								WHERE other_fee_id = :other_fee_id"; // PK로 업데이트
					                   $params = [
					                       'claim_type' => $_POST['claim_type'],
					                       'amount' => $amount,
					                       'description' => $_POST['description'],
					                       'payment_term' => $_POST['payment_term'],
					                       'other_fee_id' => $existing_id
					                   ];
					} else {
						// INSERT 쿼리
						$sql = "INSERT INTO application_recovery_income_other_fee
								(case_no, creditor_count, claim_type, amount, description, payment_term)
								VALUES (:case_no, :creditor_count, :claim_type, :amount, :description, :payment_term)";
					                   $params = [
					                       'case_no' => $case_no,
					                       'creditor_count' => $_POST['creditor_count'],
					                       'claim_type' => $_POST['claim_type'],
					                       'amount' => $amount,
					                       'description' => $_POST['description'],
					                       'payment_term' => $_POST['payment_term']
					                   ];
					}
					
					$stmt = $pdo->prepare($sql);
					$stmt->execute($params);
					
					$pdo->commit();
					
					// 새로 삽입된 경우 lastInsertId 반환
					$other_fee_id = $existing_id ? $existing_id : $pdo->lastInsertId();
					
					echo json_encode([
						'success' => true,
						'data' => ['other_fee_id' => $other_fee_id] // other_fee_id 반환
					]);
				} catch (Exception $e) {
					$pdo->rollBack();
					echo json_encode([
						'success' => false, 
						'message' => $e->getMessage()
					]);
				}
				break;

            case 'get':
                $sql = "SELECT * FROM application_recovery_income_other_fee
                       WHERE case_no = :case_no ORDER BY creditor_count";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['case_no' => $case_no]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'delete':
                $sql = "DELETE FROM application_recovery_income_other_fee
                       WHERE case_no = :case_no AND creditor_count = :creditor_count";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'case_no' => $case_no,
                    'creditor_count' => $_POST['creditor_count']
                ]);
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
?>
<link rel="stylesheet" href="../../../css/other_fee_calculator.css">
<div class="content-wrapper">
    <!-- 제목 영역 -->
    <div class="appendix-title">기타 개인회생재단 채권</div>
    
    <!-- 테이블 영역 -->
    <div class="appendix-table">
        <!-- 테이블 헤더 -->
        <div class="table-header">
            <div class="col">기타 개인회생재단채권 (양육비 등)</div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="btnAddOtherFee">추가</button>
            </div>
        </div>
    </div>
    
    <!-- 동적으로 추가될 컨테이너 -->
    <div id="otherFeeContainer"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
class OtherFeeManager {
    constructor() {
        this.counter = 0;
        this.initialize();
    }

    initialize() {
        this.loadData();
        this.bindEvents();
    }

    bindEvents() {
        $('#btnAddOtherFee').on('click', () => this.addBlock());
    }

    loadData() {
        const caseNo = new URLSearchParams(window.location.search).get('case_no');
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: { action: 'get', case_no: caseNo },
            success: (response) => {
                if(response.success && response.data) {
                    if(response.data.length > 0) {
                        response.data.forEach(data => this.addBlock(data));
                    } else {
                        this.addBlock();
                    }
                }
            }
        });
    }

    addBlock(data = {}) {
        this.counter++;
        const blockId = `other_fee_block_${this.counter}`;
        const creditorCount = data.creditor_count || this.counter;
        
        const html = `
            <div class="fee-block" id="${blockId}">
                <input type="hidden" class="other_fee_id" value="${data.other_fee_id || ''}">
                <input type="hidden" class="creditor_count" value="${creditorCount}">
                <div class="left-section">
                    <div class="form">
                        <div class="form-title"><span>채권자/사건본인</span></div>
                        <div class="form-content">
                            <input type="text" class="claim_type" value="${data.claim_type || ''}">
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>채권현재액</span></div>
                        <div class="form-content">
                            <input type="text" class="amount" value="${data.amount ? this.formatMoney(data.amount) : ''}">원
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>채권발생원인</span></div>
                        <div class="form-content">
                            <input type="text" class="description" value="${data.description ? data.description : ''}">
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>변제기</span></div>
                        <div class="form-content">
                            <input type="text" class="payment_term" value="${data.payment_term || ''}">
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"></div>
                        <div class="form-content btn-right">
                            <button type="button" class="btn-delete">삭제</button>
                            <button type="button" class="btn-save">저장</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#otherFeeContainer').append(html);
        const block = $(`#${blockId}`);

        // 이벤트 바인딩
        block.find('.amount').on('input', (e) => {
            const val = e.target.value.replace(/[^\d]/g, '');
            e.target.value = this.formatMoney(val);
        });

        block.find('.btn-save').on('click', () => this.saveBlock(block));
        block.find('.btn-delete').on('click', () => this.deleteBlock(block));
    }

    saveBlock(block) {
        const caseNo = new URLSearchParams(window.location.search).get('case_no');
        const amountVal = block.find('.amount').val().replace(/,/g, ''); // 콤마 제거
        
        const formData = {
            action: 'save',
            case_no: caseNo,
            creditor_count: block.find('.creditor_count').val(),
            claim_type: block.find('.claim_type').val().trim(),
            amount: amountVal,
            description: block.find('.description').val().trim(),
            payment_term: block.find('.payment_term').val().trim()
        };

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            success: (response) => {
                if(response.success) {
                    alert('저장되었습니다.');
                    // other_fee_id 업데이트
                    if(response.data && response.data.other_fee_id) {
                        block.find('.other_fee_id').val(response.data.other_fee_id);
                    }
                    this.updateParentTotal(); // 합계 업데이트 및 부모 창 전달
                } else {
                    alert(response.message || '저장에 실패했습니다.');
                }
            },
            error: () => {
                alert('저장 중 오류가 발생했습니다.');
            }
        });
    }

    deleteBlock(block) {
        // 저장되지 않은 항목(other_fee_id가 없는 항목)은 바로 삭제
        if(!block.find('.other_fee_id').val()) {
            block.remove();
            this.updateParentTotal(); // 삭제 후 합계 업데이트
            return;
        }

        if(!confirm('이 항목을 삭제하시겠습니까?')) return;

        const caseNo = new URLSearchParams(window.location.search).get('case_no');
        const creditorCount = block.find('.creditor_count').val();

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'delete',
                case_no: caseNo,
                creditor_count: creditorCount
            },
            success: (response) => {
                if(response.success) {
                    alert('삭제되었습니다.');
                    block.remove();
                    this.updateParentTotal(); // 합계 업데이트 및 부모 창 전달
                } else {
                    alert(response.message || '삭제에 실패했습니다.');
                }
            },
            error: () => {
                alert('삭제 중 오류가 발생했습니다.');
            }
        });
    }

    formatMoney(amount) {
        if (!amount) return '';
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    unformatMoney(str) {
        if (!str) return 0;
        // 숫자가 아닌 문자 제거 후 정수로 변환
        const numStr = String(str).replace(/[^\d]/g, '');
        return parseInt(numStr) || 0;
    }

    calculateTotal() {
        let total = 0;
        $('.fee-block').each((index, element) => {
            const amountStr = $(element).find('.amount').val();
            total += this.unformatMoney(amountStr);
        });
        return total;
    }

    updateParentTotal() {
        const totalAmount = this.calculateTotal();
        if (window.opener && typeof window.opener.updateOtherFeeTotal === 'function') {
            window.opener.updateOtherFeeTotal(totalAmount); // 부모 창 함수 호출 (함수명은 예시)
        }
        // 또는 부모 창의 특정 요소에 직접 값 설정
        // if (window.opener && window.opener.$) {
        //     window.opener.$('#parent_other_fee_total').val(this.formatMoney(totalAmount)); // 부모 창 요소 ID는 예시
        // }
    }
}

$(document).ready(function() {
    new OtherFeeManager();
});
</script>