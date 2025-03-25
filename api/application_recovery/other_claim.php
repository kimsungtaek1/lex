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
<link rel="stylesheet" href="../../css/appendix.css">
<div class="content-wrapper">
    <div class="appendix-title">부속서류 2. 다툼있는 채권</div>
    <?php
    $query = "SELECT * FROM application_recovery_creditor_other_claims 
              WHERE case_no = ? 
              AND creditor_count = ?
              AND (? IS NULL OR claim_no = ?)
              ORDER BY claim_no ASC";
              
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        die(json_encode([
            'status' => 'error',
            'message' => '쿼리 준비 실패: ' . $pdo->errorInfo()[2]
        ]));
    }
    
    if (!$stmt->execute([$case_no, $creditor_count, $claim_no, $claim_no])) {
        die(json_encode([
            'status' => 'error',
            'message' => '쿼리 실행 실패: ' . $stmt->errorInfo()[2]
        ]));
    }
    
    $result = $stmt;
    $rowCount = $stmt->rowCount();
    
    if ($rowCount === 0) {
        echo '<div class="no-data">데이터가 없습니다</div>';
    }
    ?>
    
    <div class="appendix-table">
        <div class="table-header">
            <div class="col">|&nbsp;&nbsp;번호</div>
            <div class="col">|&nbsp;&nbsp;다툼 원인</div>
            <div class="col">|&nbsp;&nbsp;채권자 주장</div>
            <div class="col">|&nbsp;&nbsp;다툼없는 부분</div>
            <div class="col">|&nbsp;&nbsp;차이나는 부분</div>
        </div>
        <?php while($row = $result->fetch()): ?>
        <div class="table-row">
            <div class="col"><?= $row['claim_no'] ?></div>
            <div class="col"><?= $row['claim_type'] ?></div>
            <div class="col"><?= number_format($row['amount']) ?>원</div>
            <div class="col"><?= number_format($row['undisputed_amount'] ?? 0) ?>원</div>
            <div class="col"><?= number_format(($row['amount'] ?? 0) - ($row['undisputed_amount'] ?? 0)) ?>원</div>
        </div>
        <?php endwhile; ?>
    </div>
    <div class="form-header">다툼있는 채권 상세</div>
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