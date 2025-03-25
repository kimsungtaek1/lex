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
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>기타(보증선 채무등)</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
<div class="content-wrapper">
	<div class="appendix-title">부속서류 4&nbsp;&nbsp;|&nbsp;&nbsp;기타(보증선 채무등)</div>
	
	<div class="left-section">
		<input type="hidden" id="debtNo" value="<?php echo $debt_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>보증인명</span></div>
			<div class="form-content">
				<input type="text" id="guarantor_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채무유형</span></div>
			<div class="form-content">
				<select id="debt_type" class="form-control">
					<option value="보증채무">보증채무</option>
					<option value="연대채무">연대채무</option>
					<option value="기타">기타</option>
				</select>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채무금액</span></div>
			<div class="form-content form-row">
				<input type="text" id="debt_amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>보증일</span></div>
			<div class="form-content">
				<input type="date" id="guarantee_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>채무내용</span></div>
			<div class="form-content">
				<textarea id="debt_content" class="form-control" rows="3"></textarea>
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
<script src="../../js/other_debt.js"></script>
</body>
</html>