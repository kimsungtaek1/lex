<?php 
$additional_css = '<link rel="stylesheet" href="css/schedule.css">';
include 'header.php';
// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 5) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
?>

<div class="container">
	<div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="stat-tab active" data-type="employee">|&nbsp;&nbsp;DB배정</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="data-table">
        <table>
            <thead>
                <tr>
					<th data-field="category">구분</th>
					<th data-field="name">이름</th>
					<th data-field="date">일자</th>
					<th data-field="time">시간</th>
					<th data-field="content">내용</th>
					<th data-field="location">기관 / 장소</th>
					<th data-field="memo">비고</th>
                    <th>
                        <button type="button" class="btn-add">추가</button>
                    </th>
                </tr>
            </thead>
            <tbody id="scheduleList"></tbody>
        </table>
        <div class="search-box">
            <input type="text" placeholder="검색" id="searchInput">
            <button type="button" class="search-btn">Q</button>
        </div>
        <div class="pagination">
            <button type="button" class="page-btn prev-btn" disabled>&lt;</button>
            <div class="page-numbers"></div>
            <button type="button" class="page-btn next-btn">&gt;</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/schedule.js"></script>