<?php 
$additional_css = '<link rel="stylesheet" href="css/consult_manager.css">';
include 'header.php';

// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
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
                    <th class="stat-tab active" data-type="consult">|&nbsp;&nbsp;사무장상담일지</th>
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
					<th>|&nbsp;&nbsp;성명</th>
					<th>|&nbsp;&nbsp;연락처</th>
					<th>|&nbsp;&nbsp;상담분야</th>
                    <th>|&nbsp;&nbsp;신청일자</th>
                    <th>|&nbsp;&nbsp;신청내용</th>
                    <th>|&nbsp;&nbsp;진행상황</th>
					<th>|&nbsp;&nbsp;상담자</th>
                    <th>|&nbsp;&nbsp;담당자</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="consultList"></tbody>
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

<div id="loadingIndicator" class="loading" style="display: none;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/consult_manager.js"></script>