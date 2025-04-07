<?php include '../index_header.php'; ?>
<div class="notice-container">
	<div class="page-header">
		<h2 class="page-title">공지사항</h2>
		<div class="breadcrumb">
			<span>홈</span> &gt; <span>공지사항</span>
		</div>
	</div>
	
	<div class="notice-content">
		<div class="notice-search">
			<select id="searchType">
				<option value="title">제목</option>
				<option value="content">내용</option>
				<option value="both">제목+내용</option>
			</select>
			<div class="search-input">
				<input type="text" id="searchKeyword" placeholder="검색어를 입력하세요">
				<button id="searchBtn"><img src="img/search.png" alt="검색"></button>
			</div>
		</div>
		
		<div class="notice-list">
			<table class="notice-table">
				<thead>
					<tr>
						<th class="number">번호</th>
						<th class="title">제목</th>
						<th class="date">등록일</th>
						<th class="views">조회수</th>
					</tr>
				</thead>
				<tbody>
					<tr class="notice-important">
						<td class="number"><span class="badge">공지</span></td>
						<td class="title"><a href="#">[필독] 서비스 이용 안내 및 개인정보 처리방침 개정 안내</a></td>
						<td class="date">2025-03-10</td>
						<td class="views">1,547</td>
					</tr>
					<tr class="notice-important">
						<td class="number"><span class="badge">공지</span></td>
						<td class="title"><a href="#">렉스마케팅 사이트 개편 안내</a></td>
						<td class="date">2025-03-05</td>
						<td class="views">1,254</td>
					</tr>
					<tr>
						<td class="number">15</td>
						<td class="title"><a href="#">2025년 3월 시스템 정기점검 일정 안내</a></td>
						<td class="date">2025-03-12</td>
						<td class="views">328</td>
					</tr>
					<tr>
						<td class="number">14</td>
						<td class="title"><a href="#">개인회생 신청 프로세스 개선 안내</a></td>
						<td class="date">2025-03-08</td>
						<td class="views">452</td>
					</tr>
					<tr>
						<td class="number">13</td>
						<td class="title"><a href="#">파산 관련 법률 서비스 지원 확대 안내</a></td>
						<td class="date">2025-03-01</td>
						<td class="views">567</td>
					</tr>
					<tr>
						<td class="number">12</td>
						<td class="title"><a href="#">신규 솔루션 출시 안내 - 채무 관리 시스템</a></td>
						<td class="date">2025-02-25</td>
						<td class="views">621</td>
					</tr>
					<tr>
						<td class="number">11</td>
						<td class="title"><a href="#">모바일 서비스 기능 개선 완료 안내</a></td>
						<td class="date">2025-02-20</td>
						<td class="views">473</td>
					</tr>
					<tr>
						<td class="number">10</td>
						<td class="title"><a href="#">2025년 2월 법률 세미나 개최 안내</a></td>
						<td class="date">2025-02-15</td>
						<td class="views">389</td>
					</tr>
					<tr>
						<td class="number">9</td>
						<td class="title"><a href="#">신규 고객 지원 프로그램 안내</a></td>
						<td class="date">2025-02-10</td>
						<td class="views">412</td>
					</tr>
					<tr>
						<td class="number">8</td>
						<td class="title"><a href="#">온라인 상담 시스템 업데이트 안내</a></td>
						<td class="date">2025-02-05</td>
						<td class="views">385</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div class="pagination">
			<a href="#" class="page-arrow">&lt;</a>
			<a href="#" class="page-number active">1</a>
			<a href="#" class="page-number">2</a>
			<a href="#" class="page-number">3</a>
			<a href="#" class="page-number">4</a>
			<a href="#" class="page-number">5</a>
			<a href="#" class="page-arrow">&gt;</a>
		</div>
	</div>
</div>

<style>
.notice-container{width:100%;max-width:1200px;margin:0 auto;padding:40px 20px;}
.page-header{margin-bottom:40px;border-bottom:2px solid #ddd;padding-bottom:15px;}
.page-title{font-size:2vw;color:#333;margin-bottom:10px;}
.breadcrumb{font-size:0.9vw;color:#777;}
.breadcrumb span{margin:0 5px;}
.notice-content{margin-bottom:60px;}
.notice-search{display:flex;justify-content:flex-end;margin-bottom:20px;gap:10px;}
#searchType{padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9vw;}
.search-input{display:flex;border:1px solid #ddd;border-radius:4px;overflow:hidden;}
#searchKeyword{padding:8px 15px;border:none;width:250px;font-size:0.9vw;}
#searchBtn{background:#f5f5f5;border:none;padding:0 15px;cursor:pointer;}
#searchBtn img{width:18px;height:18px;}
.notice-table{width:100%;border-collapse:collapse;margin-bottom:30px;}
.notice-table th{background:#f5f5f5;padding:14px 10px;text-align:center;border-top:2px solid #333;border-bottom:1px solid #ddd;font-size:1vw;font-weight:600;}
.notice-table td{padding:14px 10px;text-align:center;border-bottom:1px solid #eee;font-size:0.9vw;}
.notice-table .title{text-align:left;}
.notice-table .title a{color:#333;text-decoration:none;transition:color 0.2s ease;}
.notice-table .title a:hover{color:#00e6c3;}
.notice-important{background-color:#f9f9f9;}
.badge{background:#00e6c3;color:#fff;padding:3px 8px;border-radius:3px;font-size:0.8vw;}
.number{width:10%;}
.title{width:60%;}
.date{width:15%;}
.views{width:15%;}
.pagination{display:flex;justify-content:center;gap:5px;}
.page-number, .page-arrow{display:inline-block;width:35px;height:35px;line-height:35px;text-align:center;border:1px solid #ddd;border-radius:3px;text-decoration:none;color:#555;font-size:0.9vw;transition:all 0.2s ease;}
.page-number:hover, .page-arrow:hover{background:#f5f5f5;}
.page-number.active{background:#00e6c3;color:#fff;border-color:#00e6c3;}

@media (max-width:768px){
	.notice-search{flex-direction:column;}
	.search-input{width:100%;}
	#searchKeyword{width:100%;}
	.notice-table th{font-size:2vw;}
	.notice-table td{font-size:1.8vw;}
	.badge{font-size:1.5vw;}
	.page-number, .page-arrow{font-size:1.8vw;}
	.page-title{font-size:4vw;}
	.breadcrumb{font-size:2vw;}
	.notice-table .number, .notice-table .date, .notice-table .views{display:none;}
	.notice-table .title{width:100%;}
}
</style>

<script>
$(document).ready(function() {
	// 검색 버튼 클릭 이벤트
	$("#searchBtn").click(function() {
		var searchType = $("#searchType").val();
		var keyword = $("#searchKeyword").val();
		
		if(keyword.trim() === "") {
			alert("검색어를 입력해주세요.");
			return false;
		}
		
		// 검색 로직 구현 (예시)
		console.log("검색 유형: " + searchType + ", 키워드: " + keyword);
		// 실제 검색 처리는 서버에서 처리해야 함
	});
	
	// 엔터키 처리
	$("#searchKeyword").keypress(function(e) {
		if(e.which === 13) {
			$("#searchBtn").click();
			return false;
		}
	});
});
</script>

<?php include '../index_footer.php'; ?>