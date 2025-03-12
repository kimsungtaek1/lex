<?php 
$additional_css = '<link rel="stylesheet" href="css/calendar.css">';
include 'header.php';
// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 5) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
// 현재 연월 가져오기
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
?>

<div class="container">
    <div class="calendar-header">
        <button type="button" class="month-nav prev-month">&lt;</button>
        <div class="month-title">
            <span class="year-month"><?php echo $year . '. ' . $month; ?></span>
        </div>
        <button type="button" class="month-nav next-month">&gt;</button>
        
        <div class="category-filter">
            <span class="filter-item type-1">기일</span>
            <span class="filter-item type-2">문서송달</span>
            <span class="filter-item type-3">의뢰인방문</span>
            <span class="filter-item type-4">기타</span>
        </div>
    </div>

    <div class="calendar">
        <table>
            <thead>
                <tr>
                    <th>SUN</th>
                    <th>MON</th>
                    <th>TUE</th>
                    <th>WED</th>
                    <th>THU</th>
                    <th>FRI</th>
                    <th>SAT</th>
                </tr>
            </thead>
            <tbody id="calendarBody"></tbody>
        </table>
    </div>
</div>

<script src="js/calendar.js"></script>