<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['employee_no'])) {
	exit("권한이 없습니다.");
}
include '../../config.php';

$case_no = (int)$_GET['case_no'];
$creditor_count = isset($_GET['creditor_count']) ? (int)$_GET['creditor_count'] : null;
$principal = isset($_GET['principal']) ? (float)$_GET['principal'] : 0; // 원금 파라미터 가져오기
$debt_no = isset($_GET['debt_no']) ? $_GET['debt_no'] : null;

// 원금 포맷팅 (천 단위 콤마)
$formatted_principal = number_format($principal);

// textarea에 들어갈 기본 문자열 생성
$debt_description = "";
if ($creditor_count !== null && $principal > 0) { // creditor_count와 principal 값이 유효할 때만 생성
    $debt_description = "채권번호 ({$creditor_count}) : 해당 채권사에 대한 원금 ({$formatted_principal})원의 채무는 연대보증 채무이며 채권원인(으)로 발생한 채무입니다.";
}

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
			<div class="form-title form-notitle"><span>기타내역</span></div>
			<div class="form-content">
				<textarea id="debtDescription" rows="2"></textarea>
			</div>
		</div>

		<div class="form">
			<div class="form-title form-notitle"><span></span></div>
			<div class="form-content">
				<input type="checkbox" id="hasMortgage">
				<label for="hasMortgage">주채무자 소유 부동산에 근저당권이 설정되어 있는 경우</label>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span></span></div>
			<div class="form-content">
				※  채무자가 보증인인 경우, 주채무의 내용(주채무자,금액, 관계 등) 채무자 이외의 제3자가 물상보증을 제공한 경우 등 1~3의 부속서류에 기재하기 어려운 유형의 채권이 있는 경우에도 본 란에 기재할 수 있습니다.      
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
	var principalAmount = <?php echo isset($_GET['principal']) ? $_GET['principal'] : 'null'; ?>;
</script>
<script src="../../js/other_debt.js"></script>
</body>
</html>
