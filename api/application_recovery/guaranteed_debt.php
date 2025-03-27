<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['employee_no'])) {
	exit("권한이 없습니다.");
}
include '../../config.php';

$case_no = (int)$_GET['case_no'];
$creditor_count = isset($_GET['creditor_count']) ? $_GET['creditor_count'] : null;
$debt_no = isset($_GET['debt_no']) ? $_GET['debt_no'] : null;

// 추가: 채권 번호 자동 계산 (동일한 가지번호의 개수 + 1)
$nextNumber = 1;
if (!$debt_no && $case_no && $creditor_count) {
	$stmt = $pdo->prepare("
		SELECT COUNT(*) as count 
		FROM application_recovery_creditor_guaranteed_debts 
		WHERE case_no = ? AND creditor_count = ?
	");
	$stmt->execute([$case_no, $creditor_count]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	$nextNumber = $result['count'] + 1;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>보증인이 있는 채무</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
<div class="content-wrapper">
	<div class="appendix-title">보증인이 있는 채무(가지번호)&nbsp;&nbsp;|&nbsp;&nbsp;<span id="subrogationDisplay">미발생</span></div>
	
	<div class="left-section">
		<input type="hidden" id="debtNo" value="<?php echo $debt_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>채권번호</span></div>
			<div class="form-content">
				<input type="text" id="debtNumber" value="<?php echo $nextNumber; ?>" class="form-control" readonly>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>대위변제선택</span></div>
			<div class="form-content">
				<div class="subrogation-group">
					<input type="radio" id="subrogation_none" name="subrogation_type" value="미발생" checked>
					<label for="subrogation_none">미발생</label>
					
					<input type="radio" id="subrogation_partial" name="subrogation_type" value="일부대위변제">
					<label for="subrogation_partial">일부대위변제</label>
					
					<input type="radio" id="subrogation_full" name="subrogation_type" value="전부대위변제">
					<label for="subrogation_full">전부대위변제</label>
				</div>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>선택</span></div>
			<div class="form-content">
				<input type="checkbox" id="force_payment_plan">
				<label for="force_payment_plan">장래구상권 미발생인 경우에도 변제계획안(변제예정액표)에 가지번호를 강제 기재함</label>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채권자 정보</span></div>
			<div class="form-content">
				<select class="form-select" id="entity_type" name="entity_type">
					<option value="">선택하세요</option>
					<option value="자연인">자연인</option>
					<option value="법인" selected>법인</option>
					<option value="권리능력없는법인">권리능력없는법인(비법인)</option>
					<option value="국가">국가</option>
					<option value="지방자치단체">지방자치단체</option>
				</select>
				<button type="button" class="btn btn-long btn-financial-institution" data-count="<?php echo $creditor_count; ?>">금융기관 검색</button>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>금융기관명</span></div>
			<div class="form-content">
				<input type="text" id="financial_institution" class="form-control">
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>주소</span></div>
			<div class="form-content">
				<input type="text" id="address" class="form-control form-control-long">
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>전화</span></div>
			<div class="form-content">
				<input type="text" id="phone" class="form-control">
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>팩스</span></div>
			<div class="form-content">
				<input type="text" id="fax" class="form-control">
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>채권원인</span></div>
			<div class="form-content">
				<input type="text" id="claim_reason" class="form-control form-control-long">
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>원금</span></div>
			<div class="form-content form-row">
				<input type="text" id="principal" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채권현재액(원금)<br>산정근거</span></div>
			<div class="form-content">
				<input type="text" id="principal_calculation" class="form-control" placeholder="부채증명서 참고(산정기준일 : 2000.00.00)"/>
				<input type="date" id="calculation_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>이자</span></div>
			<div class="form-content form-row">
				<input type="text" id="interest" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채권현재액(이자)<br>산정근거</span></div>
			<div class="form-content">
				<input type="text" id="interest_calculation" class="form-control" placeholder="부채증명서 참고(산정기준일 : 2000.00.00)" readonly/>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>채권내용</span></div>
			<div class="form-content">
				<textarea id="claim_content" class="form-control" rows="3">보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자</textarea>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>장래구상권</span></div>
			<div class="form-content">
				<div class="future-right-group">
					<input type="radio" id="future_right_abandon" name="future_right_type" value="포기">
					<label for="future_right_abandon">포기</label>
					
					<input type="radio" id="future_right_claim" name="future_right_type" value="청구">
					<label for="future_right_claim">청구</label>
				</div>
			</div>
		</div>
		
		<div class="form">
			<div class="form-title"><span>보증인명</span></div>
			<div class="form-content">
				<input type="text" id="guarantor_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>보증인 주소</span></div>
			<div class="form-content">
				<input type="text" id="guarantor_address" class="form-control form-control-long">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>보증금액</span></div>
			<div class="form-content form-row">
				<input type="text" id="guarantee_amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>보증일자</span></div>
			<div class="form-content">
				<input type="date" id="guarantee_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title form-notitle"><span></span></div>
			<div class="form-content form-nocontent">
				<span>※  주채권자 정보를 저장한 후 부속서류를 선택하실 수 있습니다.</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span></span></div>
			<div class="form-content btn-right">
				<button type="button" id="closeButton">닫기</button>
				<button type="button" id="deleteButton">삭제</button>
				<button type="button" id="saveButton">저장</button>
			</div>
		</div>
	</div>

	<div class="appendix-table">
        <div class="table-header">
            <div class="col">|&nbsp;&nbsp;채권번호</div>
            <div class="col">|&nbsp;&nbsp;대위변제</div>
            <div class="col">|&nbsp;&nbsp;채권자정보 </div>
            <div class="col">|&nbsp;&nbsp;주소</div>
            <div class="col">|&nbsp;&nbsp;편집</div>
        </div>
		<div id="guarantorTableBody">
        </div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	var currentCaseNo = <?php echo $_GET['case_no']; ?>;
	var current_creditor_count = <?php echo isset($_GET['creditor_count']) && $_GET['creditor_count'] !== '' ? $_GET['creditor_count'] : 'null'; ?>;
</script>
<script src="../../js/guaranteed_debt.js"></script>
</body>
</html>