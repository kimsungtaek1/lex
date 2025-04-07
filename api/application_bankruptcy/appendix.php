<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    <div class="form-header"><?=$_GET['type']?></div>
    <div class="left-section">
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
    var selected_capital = <?php echo isset($_GET['capital']) ? $_GET['capital'] : 'null'; ?>;
    var selected_interest = <?php echo isset($_GET['interest']) ? $_GET['interest'] : 'null'; ?>;
    var current_creditor_count = <?php echo isset($_GET['count']) ? $_GET['count'] : 'null'; ?>;
    var currentCaseNo = <?php echo $_GET['case_no']; ?>;
</script>
<script src="../../js/appendix.js"></script>
</body>
</html>
