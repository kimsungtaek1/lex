<?php 
// application_recovery.php

// CSS 추가 (추가 CSS가 필요한 경우)
$additional_css = '<link rel="stylesheet" href="css/application_recovery.css">';

// 헤더 및 설정파일 포함
include 'header.php';
include 'config.php';

// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
    echo "<script>
            alert('접근 권한이 없습니다.');
            window.location.href = 'main.php';
          </script>";
    exit;
}

// 사건 번호 목록 가져오기
$stmt = $pdo->prepare("
	SELECT case_no, name 
	FROM case_management 
	ORDER BY case_no DESC 
	LIMIT 100
");
$stmt->execute();
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
	<div class="tab">
		<table>
			<thead>
				<tr>
					<th class="active">|&nbsp;&nbsp;OCR 문서 처리</th>
				</tr>
			</thead>
		</table>
	</div>
	
	<div class="ocr-container">
		<div class="upload-form">
			<h2>OCR 이미지 업로드</h2>
			<form action="ocr_process.php" method="post" enctype="multipart/form-data">
				<div class="form-group">
					<label for="case_no">사건 번호:</label>
					<select name="case_no" id="case_no">
						<option value="">선택하세요</option>
						<?php foreach($cases as $case): ?>
							<option value="<?= $case['case_no'] ?>"><?= $case['case_no'] ?> - <?= $case['name'] ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="form-group">
					<label for="document_type">문서 유형:</label>
					<select name="document_type" id="document_type">
						<option value="">선택하세요</option>
						<option value="개인회생">개인회생</option>
						<option value="개인파산">개인파산</option>
						<option value="채권자목록">채권자목록</option>
						<option value="재산목록">재산목록</option>
						<option value="기타">기타</option>
					</select>
				</div>
				
				<div class="form-group">
					<label for="ocr_file">이미지 파일:</label>
					<input type="file" name="ocr_file" id="ocr_file" accept=".jpg,.jpeg,.png,.pdf,.tiff" required>
					<p class="file-info">지원 형식: JPG, PNG, PDF, TIFF (최대 10MB)</p>
				</div>
				
				<div class="form-actions">
					<button type="submit" class="btn-primary">OCR 처리 시작</button>
				</div>
			</form>
		</div>
		
		<div class="recent-documents">
			<h2>최근 처리된 문서</h2>
			<table class="ocr-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>사건번호</th>
						<th>문서유형</th>
						<th>파일명</th>
						<th>처리상태</th>
						<th>처리일시</th>
						<th>관리</th>
					</tr>
				</thead>
				<tbody id="ocr-documents">
					<!-- 자바스크립트로 로드됨 -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
	// 최근 OCR 문서 목록 로드
	loadRecentDocuments();
	
	// 파일 선택 시 파일명 표시
	$('#ocr_file').change(function() {
		var fileName = $(this).val().split('\\').pop();
		if(fileName) {
			$(this).next('.file-info').html('선택된 파일: ' + fileName);
		} else {
			$(this).next('.file-info').html('지원 형식: JPG, PNG, PDF, TIFF (최대 10MB)');
		}
	});
	
	function loadRecentDocuments() {
		$.ajax({
			url: 'api/ocr/get_recent_documents.php',
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				var html = '';
				if(data.length === 0) {
					html = '<tr><td colspan="7" class="no-data">처리된 문서가 없습니다.</td></tr>';
				} else {
					$.each(data, function(i, doc) {
						html += '<tr>';
						html += '<td>' + doc.document_id + '</td>';
						html += '<td>' + (doc.case_no || '-') + '</td>';
						html += '<td>' + (doc.document_type || '-') + '</td>';
						html += '<td>' + doc.file_name + '</td>';
						html += '<td>' + getStatusLabel(doc.ocr_status) + '</td>';
						html += '<td>' + doc.created_at + '</td>';
						html += '<td>';
						html += '<a href="ocr_view.php?id=' + doc.document_id + '" class="btn-view">보기</a> ';
						html += '</td>';
						html += '</tr>';
					});
				}
				$('#ocr-documents').html(html);
			},
			error: function() {
				$('#ocr-documents').html('<tr><td colspan="7" class="error">문서 목록을 불러오는데 실패했습니다.</td></tr>');
			}
		});
	}
	
	function getStatusLabel(status) {
		switch(status) {
			case '대기중': return '<span class="status pending">대기중</span>';
			case '처리중': return '<span class="status processing">처리중</span>';
			case '완료': return '<span class="status completed">완료</span>';
			case '실패': return '<span class="status failed">실패</span>';
			default: return '<span class="status">' + status + '</span>';
		}
	}
});
</script>
</body>
</html>