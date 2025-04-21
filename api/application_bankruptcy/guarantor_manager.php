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

// creditor_count 파라미터 필수 체크
if (!isset($_GET['creditor_count']) || empty($_GET['creditor_count'])) {
	die(json_encode([
		'status' => 'error',
		'message' => 'creditor_count 파라미터가 필요합니다'
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
$creditor_count = (int)$_GET['creditor_count'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>보증인 관리</title>
	<link rel="stylesheet" href="../../css/appendix.css">
</head>
<body>
	<div class="content-wrapper">
		
		<div class="appendix-title">채권자목록 보증인관리</div>
		
		<div class="left-section">
			<div class="form">
				<div class="form-title"><span>채권번호</span></div>
				<div class="form-content">
					<input type="text" id="" class="form-control" >
				</div>
			</div>

			<div class="form">
				<div class="form-title"><span>채권자명</span></div>
				<div class="form-content">
					<input type="text" id="guarantor_name<?= $creditor_count ?>" class="form-control form-control-long" >
					<button type="button" class="btn-long btn-financial-institution">금융기관검색</button>
				</div>
			</div>

			<div class="form">
				<div class="form-title"><span>연락처</span></div>
				<div class="form-content form-row">
					<input type="text" id="guarantor_phone<?= $creditor_count ?>" class="form-control">
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span>팩스</span></div>
				<div class="form-content form-row">
					<input type="text" id="guarantor_fax<?= $creditor_count ?>" class="form-control">
				</div>
			</div>

			<div class="form">
				<div class="form-title"><span>주소</span></div>
				<div class="form-content">
					<input type="text" id="guarantor_address<?= $creditor_count ?>" class="form-control form-control-long" >
					<button type="button" class="btn-long" id="addressSearchBtn">주소찾기</button>
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span>차용 또는 구입일자</span></div>
				<div class="form-content">
					<input type="date" id="" class="form-control" >
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span>발생원인/피보증인</span></div>
				<div class="form-content">
					<select class="form-select" id="">
						<option value="금원차용" selected>금원차용</option>
						<option value="물품구입">물품구입</option>
						<option value="보증(피보증인 기재)">보증(피보증인 기재)</option>
						<option value="대위변제">대위변제</option>
						<option value="기타">기타</option>
					</select>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="text" id="" class="form-control" >
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span>잔존원금<br>(대위변제금액)</span></div>
				<div class="form-content">
					<input type="text" id="" class="form-control" >원
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span>잔존원금<br>(대위변제금액)</span></div>
				<div class="form-content">
					<input type="text" id="" class="form-control" >원
				</div>
			</div>
			
			<div class="form">
				<div class="form-title"><span></span></div>
				<div class="form-content btn-right">
					<button type="button" id="closeButton">닫기</button>
					<button type="button" id="saveButton">저장</button>
					<button type="button" id="deleteButton">초기화</button>
				</div>
			</div>
		</div>
		
		<div class="guarantor-table">
			<div class="table-header">
				<div class="col">|&nbsp;&nbsp;채권번호</div>
				<div class="col">|&nbsp;&nbsp;채권자명</div>
				<div class="col">|&nbsp;&nbsp;주소</div>
				<div class="col">|&nbsp;&nbsp;발생원인</div>
				<div class="col">|&nbsp;&nbsp;편집</div>
			</div>
			<div id="guarantorTableBody">
				<!-- 보증인 데이터가 여기에 동적으로 로드됩니다 -->
			</div>
		</div>
		
		<div>※ 리스트의 편집 버튼을 이용하시면 보증인 정보를 수정 또는 삭제하실 수 있습니다.</div>
	</div>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
	<input type="hidden" id="guarantor_no" value="">
	<script>
		var currentCaseNo = <?php echo $case_no; ?>;
		var currentCreditorCount = <?php echo $creditor_count; ?>;
	</script>
	<script src="../../js/guarantor_manager.js"></script>
</body>
</html>