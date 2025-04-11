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
                    
                    // 저장 로직 구현
                    $case_no = $_POST['case_no'];
                    $trustee = $_POST['trustee'] ?? null; // 'yes' or 'no'
                    $fee_rate = $_POST['fee'] ?? 0; // 기본값 0
                    $additional_fee = $_POST['additional_fee'] ?? 'N'; // 'Y' or 'N'
                    $is_external = ($trustee === 'yes') ? 'Y' : 'N';

                    // 기존 데이터 확인
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM application_recovery_income_trustee_fee WHERE case_no = :case_no");
                    $checkStmt->execute([':case_no' => $case_no]);
                    $exists = $checkStmt->fetchColumn() > 0;

                    if ($exists) {
                        // UPDATE
                        $stmt = $pdo->prepare("
                            UPDATE application_recovery_income_trustee_fee
                            SET
                                is_external_trustee = :is_external_trustee,
                                trustee_fee_rate = :trustee_fee_rate,
                                calculate_trustee_fee_after_disposal = :calculate_trustee_fee_after_disposal
                            WHERE case_no = :case_no
                        ");
                    } else {
                        // INSERT
                        $stmt = $pdo->prepare("
                            INSERT INTO application_recovery_income_trustee_fee
                            (case_no, is_external_trustee, trustee_fee_rate, calculate_trustee_fee_after_disposal)
                            VALUES (:case_no, :is_external_trustee, :trustee_fee_rate, :calculate_trustee_fee_after_disposal)
                        ");
                    }
                    
                    $stmt->bindParam(':is_external_trustee', $is_external, PDO::PARAM_STR);
                    $stmt->bindParam(':trustee_fee_rate', $fee_rate, PDO::PARAM_INT);
                    $stmt->bindParam(':calculate_trustee_fee_after_disposal', $additional_fee, PDO::PARAM_STR);
                    $stmt->bindParam(':case_no', $case_no, PDO::PARAM_INT);
                    
                    $stmt->execute();
                    
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
                // 데이터 조회 로직 구현
                $stmt = $pdo->prepare("SELECT is_external_trustee, trustee_fee_rate, calculate_trustee_fee_after_disposal FROM application_recovery_income_trustee_fee WHERE case_no = :case_no");
                $stmt->execute([':case_no' => $case_no]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // is_external_trustee 값을 'yes'/'no'로 변환 (JavaScript 호환성)
                if ($data) {
                    $data['trustee'] = ($data['is_external_trustee'] === 'Y') ? 'yes' : 'no';
                    $data['fee'] = $data['trustee_fee_rate']; // JavaScript에서 fee 이름 사용
                    // calculate_trustee_fee_after_disposal은 'Y'/'N' 그대로 사용
                } else {
                    // 데이터가 없을 경우 기본값 설정 (선택사항)
                    $data = [
                        'trustee' => 'no',
                        'fee' => 0,
                        'calculate_trustee_fee_after_disposal' => 'N'
                    ];
                }

                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'delete':
                // 삭제 로직 구현 (필요 시)
                // $stmt = $pdo->prepare("DELETE FROM application_recovery_income_trustee_fee WHERE case_no = :case_no");
                // $stmt->execute([':case_no' => $case_no]);
                // echo json_encode(['success' => true]);
                echo json_encode(['success' => false, 'message' => '삭제 기능은 지원되지 않습니다.']);
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
<link rel="stylesheet" href="../../../css/trustee_fee_calculator.css">
<div class="content-wrapper">
    <!-- 제목 영역 -->
    <div class="appendix-title">개인회생재단채권 외부회생위원</div>
    
    <!-- 테이블 영역 -->
    <div class="appendix-table">
        <!-- 테이블 헤더 -->
        <div class="table-header">
            <div class="col">외부회생위원</div>
        </div>
    </div>
    
    <div class="left-section">
        <div class="form">
			<div class="form-title"><span>외부회생위원 선임 여부</span></div>
			<div class="form-content">
				<div class="radio">
					<input type="radio" name="trustee" id="trustee_yes" value="yes">
					<label for="trustee_yes">네</label>
					<input type="radio" name="trustee" id="trustee_no" value="no">
					<label for="trustee_no">아니오</label>
				</div>
			</div>
		</div>
		<div class="form">
			<div class="form-title form-notitle"><span>임치한 금액의 비율</span></div>
			<div class="form-content form-nocontent">
				<div class="radio">
					<input type="radio" name="fee" id="fee_0" value="0">
					<label for="fee_0">0%</label>
					<input type="radio" name="fee" id="fee_1" value="1">
					<label for="fee_1">1%</label>
					<input type="radio" name="fee" id="fee_2" value="2">
					<label for="fee_2">2%</label>
					<input type="radio" name="fee" id="fee_3" value="3">
					<label for="fee_3">3%</label>
					<input type="radio" name="fee" id="fee_4" value="4">
					<label for="fee_4">4%</label>
					<input type="radio" name="fee" id="fee_5" value="5">
					<label for="fee_5">5%</label>
				</div>
			</div>
		</div>
		<div class="form">
			<div class="form-title form-notitle"></div>
			<div class="form-content">
				<p>※ 0%를 선택한 경우에는 외부 회생위원의 인가결정이후 업무에 대한 보수(1~5%)가 무시되어 변제계획안이 작성됩니다.</p>
			</div>
		</div>
		<div class="form">
			<div class="form-title"></div>
			<div class="form-content">
				<div class="checkbox-group">
					<input type="checkbox" id="additional_fee">
					<label for="additional_fee">기타 재산권(양육비) 처분 후 보수 산정</label>
				</div>
			</div>
		</div>
		<div class="form">
			<div class="form-title form-notitle"><span>주의사항</span></div>
			<div class="form-content">
				법원사무관 등이 아닌 회생위원을 선임할 사건 [서울회생법원 실무준칙 제401호 ②,③]
			</div>
		</div>
		
		<div class="form">
            <div class="form-title form-notitle"></div>
            <div class="form-content form-content-20">
                <div>
					② 법원은 다음 각 호에서 정한 채무자의 개인회생절차 개시신청 사건은 외부회생위원 전담재판부에 배당하고,<br>
					&nbsp;&nbsp;&nbsp;&nbsp;외부회생위원을 선임한다.<br>
					&nbsp;&nbsp;&nbsp;&nbsp;1. 법 제579조에서 정한 영업소득자인 채무자<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(채무자 명의로 사업자등록이 되어 있는지 여부와 무관하게 채무자가 실질적으로 영업소득을 얻는 경우를<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;의미한다.)<br>
					&nbsp;&nbsp;&nbsp;&nbsp;2. 법 제579조에서 정한 급여소득자인 채무자 중 채무액 총합계(담보부채무액을 포함한다)가 2억원을<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;초과하는 채무자<br>
					&nbsp;&nbsp;&nbsp;&nbsp;3. 법 제579조에서 정한 급여소득자인 채무자 중 다음 각 목에서 정한 직업에 종사하는 채무자<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다만, 채무자가 영업활동에 따른 성과급을 지급받지 않은 경우에는 외부회생위원 전담재판부에<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;배당하지 않을 수 있다.<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;가. 보험설계사<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;나. 영업사원 및 방문판매사원<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다. 법인 대표자<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;라. 지입차주<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;마. 그 밖에 영업활동에 따른 성과급을 지급받는 직업<br>
					&nbsp;&nbsp;③ 법원은 법 제579조에서 정한 급여소득자인 채무자 중 제2항 제2호, 제3호에 해당하지 않는 채무자가<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;신청한 개인회생절차 개시신청 사건의 경우에도 부인권 대상 행위의 존부, 접수 사건수의 추이 등<br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;여러 사정을 참작하여 사건을 외부회생위원 전담재판부에 배당하고, 외부회생위원을 선임할 수 있다.
				</div>
            </div>
        </div>
		<div class="form">
            <div class="form-title form-notitle"></div>
            <div class="form-content form-nocontent">
				<div>법원사무관 등이 아닌 회생위원 보수기준표 [개인회생사건처리지침 제10조 별표1]</div>
            </div>
        </div>
		<div class="form">
            <div class="form-title form-notitle"></div>
            <div class="form-content form-content-6 form-nocontent">
				<table class="fee-table">
				   <tr>
					   <th>|&nbsp;&nbsp;항목</th>
					   <th>|&nbsp;&nbsp;보수기준액</th>
					   <th>|&nbsp;&nbsp;보수상한액</th>
				   </tr>
				   <tr>
					   <td>인가결정 이전<br>업무에 대한 보수</td>
					   <td>15만원</td>
					   <td>30만원</td>
				   </tr>
				   <tr>
					   <td>인가결정 이후<br>업무에 대한 보수</td>
					   <td>인가된 변제계획안에 따라<br>채무자가 실제 임치한 금액의 1%</td>
					   <td>인가된 변제계획안에 따라<br>채무자가 실제 임치한 금액의 5%</td>
				   </tr>
				</table>
            </div>
        </div>
        <div class="form">
            <div class="form-title"><span></span></div>
            <div class="form-content btn-right">
                <button type="button" onclick="window.close()">닫기</button>
				<button type="button" class="btn-save" onclick="saveTrusteeFee()">저장</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    loadTrusteeFee();
});

function saveTrusteeFee() {
    let caseNo = new URLSearchParams(window.location.search).get('case_no');
    let formData = new FormData();
    formData.append('action', 'save');
    formData.append('case_no', caseNo);
    formData.append('trustee', $('input[name="trustee"]:checked').val() || '');
    formData.append('fee', $('input[name="fee"]:checked').val() || '');
    formData.append('additional_fee', $('#additional_fee').is(':checked') ? 'Y' : 'N');

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if(response.success) {
                alert('저장되었습니다.');
                // 부모 창으로 값 전달 (조건부)
                if (window.opener && !window.opener.closed && window.opener.$) {
                    const isExternalTrustee = $('input[name="trustee"]:checked').val();
                    // 'yes'일 경우 선택된 비율, 'no'일 경우 0
                    const feeRateToPass = (isExternalTrustee === 'yes') ? ($('input[name="fee"]:checked').val() || 0) : 0;
                    
                    // 부모 창의 #iex_trustee_fee_rate 요소에 값 설정
                    window.opener.$('#iex_trustee_fee_rate').val(feeRateToPass);
                    
                    // 필요하다면 부모 창의 다른 함수도 호출 (예: 합계 재계산 등)
                    // if (typeof window.opener.recalculateTotals === 'function') {
                    //     window.opener.recalculateTotals();
                    // }
                }
                loadTrusteeFee();
                // window.close(); // 필요하다면 창 닫기
            } else {
                alert(response.message || '저장에 실패했습니다.');
            }
        },
        error: function() {
            alert('저장 중 오류가 발생했습니다.');
        }
    });
}

function loadTrusteeFee() {
    let caseNo = new URLSearchParams(window.location.search).get('case_no');
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: { action: 'get', case_no: caseNo },
        success: function(response) {
            if(response.success && response.data) {
                $(`input[name="trustee"][value="${response.data.trustee}"]`).prop('checked', true);
                $(`input[name="fee"][value="${response.data.fee}"]`).prop('checked', true);
                $('#additional_fee').prop('checked', response.data.additional_fee === 'Y');
            }
        }
    });
}
</script>