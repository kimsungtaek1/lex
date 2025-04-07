<?php
require_once '../adm/api/session_check.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="container">
        <div class="menu-container">
            <div class="menu-grid">
                <div class="menu-item" data-link="stats">
                    <div class="icon stats"></div>
                    <span>통계</span>
                </div>
                <div class="menu-item" data-link="user">
                    <div class="icon user"></div>
                    <span>사원관리</span>
                </div>
                <div class="menu-item" data-link="db">
                    <div class="icon db"></div>
                    <span>DB배정</span>
                </div>
                <div class="menu-item" data-link="manager">
                    <div class="icon manager"></div>
                    <span>상담일지</span>
                </div>
                <div class="menu-item" data-link="paper">
                    <div class="icon paper"></div>
                    <span>담당일지</span>
                </div>
                <div class="menu-item" data-link="cert">
                    <div class="icon cert"></div>
                    <span>신청서</span>
                </div>
                <div class="menu-item" data-link="time">
                    <div class="icon time"></div>
                    <span>사건관리</span>
                </div>
                <div class="menu-item" data-link="schedule">
                    <div class="icon schedule"></div>
                    <span>기일관리</span>
                </div>
                <div class="menu-item" data-link="calendar">
                    <div class="icon calendar"></div>
                    <span>달력</span>
                </div>
                <div class="menu-item" data-link="chat">
                    <div class="icon chat"></div>
                    <span>챗봇</span>
                </div>
				<div class="menu-item" data-link="home">
                    <div class="icon home"></div>
                    <span></span>
                </div>
				<div class="menu-right">
					<div class="menu-item half" data-link="logout">
						<span>로그아웃</span>
					</div>
					<div class="menu-item half" data-link="mypage">
						<span>마이페이지</span>
					</div>
				</div>
            </div>
        </div>
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="month-display">09</div>
            </div>
            <div class="weekdays">
                <div class="weekend">SUN</div>
                <div>MON</div>
                <div>TUE</div>
                <div>WED</div>
                <div>THU</div>
                <div>FRI</div>
                <div class="weekend">SAT</div>
            </div>
            <div id="calendarGrid" class="calendar-grid"></div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>