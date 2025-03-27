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
$claim_no = isset($_GET['claim_no']) ? $_GET['claim_no'] : null;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>기타미확정채권</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
<div class="content-wrapper">
	<div class="appendix-title">부속서류&nbsp;&nbsp;|&nbsp;&nbsp;기타미확정채권(신탁재산 등)</div>
	<div class="appendix-table" style="margin:2vh 0 0;">
        <div class="table-header">
            <div class="col">신탁재산의 반영 (별제권부채권은 아니지만, 미확정채권에 반영해야 할 필요가 있는 경우 등)</div>
        </div>
	</div>
	
	<div class="left-section">
		<input type="hidden" id="claimNo" value="<?php echo $claim_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>목적물</span></div>
			<div class="form-content">
				<input type="text" id="property_detail" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>환가예상액</span></div>
			<div class="form-content form-row">
				<input type="text" id="expected_value" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>평가비율</span></div>
			<div class="form-content form-row">
				<input type="text" id="evaluation_rate" class="form-control">
				<span>%</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>신탁재산의 내용</span></div>
			<div class="form-content">
				<input type="checkbox" id="trust_property_details" checked>
				<label for="trust_property_details">담보신탁채권</label>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>우선수익권증서금액(담보액)</span></div>
			<div class="form-content form-row">
				<input type="text" id="priority_certificate_amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>등기(등록)일자</span></div>
			<div class="form-content">
				<input type="date" id="registration_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>③ 신탁계약에 의한 처분으로<br>변제가 예상되는 채권액</span></div>
			<div class="form-content form-row">
				<input type="text" id="expected_payment" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>④ 신탁계약에 의한 처분으로도<br>변제 받을 수 없는 채권액</span></div>
			<div class="form-content form-row">
				<input type="text" id="unpaid_amount" class="form-control number-input">
				<span>원</span>
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
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	var currentCaseNo = <?php echo $_GET['case_no']; ?>;
	var current_creditor_count = <?php echo isset($_GET['creditor_count']) && $_GET['creditor_count'] !== '' ? $_GET['creditor_count'] : 'null'; ?>;
</script>
<script src="../../js/undetermined_claim.js"></script>
</body>
</html>