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
	<title>전부명령된 채권</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
<div class="content-wrapper">
	<div class="appendix-title">부속서류 3&nbsp;&nbsp;|&nbsp;&nbsp;전부명령된 채권</div>
	
	<div class="left-section">
		<input type="hidden" id="claimNo" value="<?php echo $claim_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>채권자</span></div>
			<div class="form-content">
				<input type="text" id="original_creditor" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>전부명령 채무자</span></div>
			<div class="form-content">
				<input type="text" id="debtor_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>명령법원</span></div>
			<div class="form-content">
				<input type="text" id="court_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>사건번호</span></div>
			<div class="form-content">
				<input type="text" id="case_number" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>명령금액</span></div>
			<div class="form-content form-row">
				<input type="text" id="order_amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>명령일자</span></div>
			<div class="form-content">
				<input type="date" id="order_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>비고</span></div>
			<div class="form-content">
				<input type="text" id="remark" class="form-control form-control-long">
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
<script src="../../js/assigned_claim.js"></script>
</body>
</html>