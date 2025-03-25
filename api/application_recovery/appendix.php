<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}
include '../../config.php';

// case_no 파라미터 필수 체크
if (!isset($_GET['case_no']) || empty($_GET['case_no'])) {
    die(json_encode([
        'status' => 'error',
        'message' => 'case_no 파라미터가 필요합니다'
    ]));
}

// DB 연결 확인
if (!$pdo) {
    die(json_encode([
        'status' => 'error', 
        'message' => '데이터베이스 연결 실패'
    ]));
}

$case_no = (int)$_GET['case_no'];
?>
<link rel="stylesheet" href="../../css/appendix.css">
<div class="content-wrapper">
    <div class="appendix-title">부속서류 1. 별제권부채권</div>
    <?php
    $creditor_count = isset($_GET['count']) ? $_GET['count'] : null;
    $appendix_no = isset($_GET['appendix_no']) ? $_GET['appendix_no'] : null;

    $query = "SELECT * FROM application_recovery_creditor_appendix 
              WHERE case_no = ? 
              AND (? IS NULL OR creditor_count = ?)
              AND (? IS NULL OR appendix_no = ?)
              ORDER BY creditor_count ASC";
              
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        die(json_encode([
            'status' => 'error',
            'message' => '쿼리 준비 실패: ' . $pdo->errorInfo()[2]
        ]));
    }
    
    if (!$stmt->execute([$case_no, $creditor_count, $creditor_count, $appendix_no, $appendix_no])) {
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
            <div class="col">|&nbsp;&nbsp;채권번호</div>
            <div class="col">|&nbsp;&nbsp;(동일)목적물</div>
            <div class="col">|&nbsp;&nbsp;환가예상액 </div>
            <div class="col">|&nbsp;&nbsp;환가비율</div>
            <div class="col">|&nbsp;&nbsp;➂예상채권액</div>
            <div class="col">|&nbsp;&nbsp;➃없을채권액</div>
            <div class="col">|&nbsp;&nbsp;➄회생채권액</div>
        </div>
        <?php while($row = $result->fetch()): ?>
        <div class="table-row">
            <div class="col"><?= $row['creditor_count'] ?></div>
            <div class="col"><?= $row['property_detail'] ?></div>
            <div class="col"><?= number_format($row['expected_value']) ?></div>
            <div class="col"><?= $row['evaluation_rate'] ?>%</div>
            <div class="col"><?= number_format($row['secured_expected_claim']) ?></div>
            <div class="col"><?= number_format($row['unsecured_remaining_claim']) ?></div>
            <div class="col"><?= number_format($row['rehabilitation_secured_claim']) ?></div>
        </div>
        <?php endwhile; ?>
    </div>
    <div class="form-header" id="appendixTypeHeader"></div>
	<div class="left-section">
		<div class="form">
			<div class="form-title"><span>부속서류 타입</span></div>
			<div class="form-content">
				<select id="appendixType" class="form-control">
					<option value="(근)저당권설정">(근)저당권설정</option>
					<option value="질권설정/채권양도(전세보증금)">질권설정/채권양도(전세보증금)</option>
					<option value="최우선변제임차권">최우선변제임차권</option>
					<option value="우선변제임차권">우선변제임차권</option>
				</select>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>동일목적물</span></div>
			<div class="form-content">
				<button type="button" class="btn-nomargin" id="propertySelectBtn">목적물 선택</button>
				<span>* 기존 입력된 목적물이 있는 경우</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>목적물</span></div>
			<div class="form-content">
				<input type="text" id="property_detail" class="form-control form-control-long" placeholder="부동산 : 주소입력 / 차량 : 차량번호, 연식, 모델(예:123가4567, 2020년형, 현대쏘나타)">
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
			<div class="form-content">
				<input type="text" id="evaluation_rate" class="form-control"><span>%</span>
				<button type="button" id="calculateButton">계산하기</button>
			</div>
		</div>
			
		<!-- 동적으로 생성 - (근)저당권설정 필드 -->
		<div class="type-field type-mortgage">
			<div class="form">
				<div class="form-title"><span>채권최고액(담보액)</span></div>
				<div class="form-content form-row">
					<input type="text" id="max_claim" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>등기(등록)일자</span></div>
				<div class="form-content">
					<input type="date" id="registration_date" class="form-control">
				</div>
			</div>
		</div>
		
		<!-- 질권설정/채권양도(전세보증금) 필드 -->
		<div class="type-field type-pledge" style="display:none;">
			<div class="form">
				<div class="form-title"><span>보증금(전세/임대차)</span></div>
				<div class="form-content form-row">
					<input type="text" id="pledge_deposit" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>질권설정(채권양도)금</span></div>
				<div class="form-content form-row">
					<input type="text" id="pledge_amount" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>전세(임대차)기간</span></div>
				<div class="form-content">
					<input type="date" id="lease_start_date" class="form-control"> ~ 
					<input type="date" id="lease_end_date" class="form-control">
				</div>
			</div>
		</div>
		
		<!-- 최우선변제임차권 필드 -->
		<div class="type-field type-top-priority" style="display:none;">
			<div class="form">
				<div class="form-title"><span>최초근저당권설정일</span></div>
				<div class="form-content">
					<input type="date" id="first_mortgage_date" class="form-control">
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>지역</span></div>
				<div class="form-content">
					<select id="region" class="form-control">
						<option value="서울특별시">서울특별시</option>
						<option value="경기도">경기도</option>
						<option value="인천광역시">인천광역시</option>
						<option value="기타">기타</option>
					</select>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>임대차보증금</span></div>
				<div class="form-content form-row">
					<input type="text" id="lease_deposit" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>최우선변제금</span></div>
				<div class="form-content form-row">
					<input type="text" id="top_priority_amount" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>임대차기간</span></div>
				<div class="form-content">
					<input type="date" id="top_lease_start_date" class="form-control"> ~ 
					<input type="date" id="top_lease_end_date" class="form-control">
				</div>
			</div>
		</div>
		
		<!-- 우선변제임차권 필드 -->
		<div class="type-field type-priority" style="display:none;">
			<div class="form">
				<div class="form-title"><span>임대차보증금</span></div>
				<div class="form-content form-row">
					<input type="text" id="priority_deposit" class="form-control number-input">
					<span>원</span>
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>임대차기간</span></div>
				<div class="form-content">
					<input type="date" id="priority_lease_start_date" class="form-control"> ~ 
					<input type="date" id="priority_lease_end_date" class="form-control">
				</div>
			</div>
			<div class="form">
				<div class="form-title"><span>확정일자</span></div>
				<div class="form-content">
					<input type="date" id="fixed_date" class="form-control">
				</div>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>③ 별제권 행사 등으로<br>변제가 예상되는 채권액</span></div>
			<div class="form-content form-row">
				<input type="text" id="secured_expected_claim" class="form-control number-input">
				<span>원</span>
			</div>
		</div>
		<div class="form">
			<div class="form-title"><span>④ 별제권 행사 등으로도<br>변제 받을 수 없는 채권액</span></div>
			<div class="form-content form-row">
				<input type="text" id="unsecured_remaining_claim" class="form-control number-input">
				<span>원</span>
			</div>
		</div>
		<div class="form">
			<div class="form-title"><span>➄ 담보부 회생채권액</span></div>
			<div class="form-content form-row">
				<input type="text" id="rehabilitation_secured_claim" class="form-control number-input">
				<span>원</span>
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
<input type="hidden" id="mortgageNo" value="<?php echo isset($_GET['appendix_no']) ? $_GET['appendix_no'] : ''; ?>">
<script>
    var currentCaseNo = <?php echo $_GET['case_no']; ?>;
    var current_creditor_count = <?php echo isset($_GET['count']) && $_GET['count'] !== '' ? $_GET['count'] : 'null'; ?>;
    var selected_capital = <?php echo isset($_GET['capital']) && $_GET['capital'] !== '' ? $_GET['capital'] : 0; ?>;
    var selected_interest = <?php echo isset($_GET['interest']) && $_GET['interest'] !== '' ? $_GET['interest'] : 0; ?>;
</script>
<script src="../../js/appendix.js"></script>
</body>
</html>
