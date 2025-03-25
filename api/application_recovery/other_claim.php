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
	<title>다툼있는 채권</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
<div class="content-wrapper">
	<div class="appendix-title">부속서류 2&nbsp;&nbsp;|&nbsp;&nbsp;다툼있는 채권</div>
	
	<div class="left-section">
		<input type="hidden" id="claimNo" value="<?php echo $claim_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>채권자 주장(원금)</span></div>
			<div class="form-content form-row">
				<input type="text" id="creditor_principal" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채권자 주장(이자)</span></div>
			<div class="form-content form-row">
				<input type="text" id="creditor_interest" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>다툼없는 부분(원금)</span></div>
			<div class="form-content form-row">
				<input type="text" id="undisputed_principal" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>다툼없는 부분(이자)</span></div>
			<div class="form-content form-row">
				<input type="text" id="undisputed_interest" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>차이나는 부분(원금)</span></div>
			<div class="form-content form-row">
				<input type="text" id="difference_principal" class="form-control number-input" readonly>
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>차이나는 부분(이자)</span></div>
			<div class="form-content form-row">
				<input type="text" id="difference_interest" class="form-control number-input" readonly>
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>다툼의 원인</span></div>
			<div class="form-content">
				<input type="text" id="dispute_reason" class="form-control form-control-long">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>소송제기 여부 및<br>진행경과</span></div>
			<div class="form-content">
				<input type="text" id="litigation_status" class="form-control form-control-long">
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
<script src="../../js/other_claim.js"></script>
</body>
</html>