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
            <div class="col">신탁재산의 반영 (별제권부채권은 아니지만, 미확정채권에 반영해야 할 필요가 있는 경우 등)</div>
        </div>
	</div>
	
	<div class="left-section">
		<input type="hidden" id="claimNo" value="<?php echo $claim_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>채권종류</span></div>
			<div class="form-content">
				<select id="claim_type" class="form-control">
					<option value="신탁재산">신탁재산</option>
					<option value="조건부채권">조건부채권</option>
					<option value="기한미확정채권">기한미확정채권</option>
					<option value="기타">기타</option>
				</select>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>금액</span></div>
			<div class="form-content form-row">
				<input type="text" id="amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채권설명</span></div>
			<div class="form-content">
				<textarea id="claim_description" class="form-control" rows="5"></textarea>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>변제기</span></div>
			<div class="form-content">
				<input type="text" id="payment_term" class="form-control">
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