<<<<<<< HEAD
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
    <link rel="stylesheet" href="css/header.css">
	<?php if(isset($additional_css)) echo $additional_css; ?>
</head>
<body>
    <header>
        <div class="header-top">
            <div class="menu-btns">
                <button onclick="window.open('https://www.scourt.go.kr/portal/information/events/search/search.jsp', 'popup', 'width=800,height=600,scrollbars=yes');">사건검색</button>
                <button onclick="window.open('https://www.scourt.go.kr/region/location/RegionSearchListAction.work', 'popup', 'width=800,height=600,scrollbars=yes');">관할법원찾기</button>
                <button onclick="window.open('https://ecfs.scourt.go.kr/ecf/ecf300/ECF304_2.jsp', 'popup', 'width=800,height=600,scrollbars=yes');">송달료계산</button>
                <button onclick="window.open('about:blank', 'popup', 'width=800,height=600,scrollbars=yes');">예상변제금</button>
            </div>
            <div class="header-right">
                <div class="user-group">
					<div class="user-info">
						<span><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
					</div>
					<button id="logoutBtn" class="logout-btn">로그아웃</button>
				</div>
                <button id="header_alert">
					<span>알림</span>
					<div class="alert-circle">20</div>
				</button>
            </div>
        </div>
        <div class="header-bottom">
            <div class="menu-grid">
                <a href="statistics.php" class="menu-item">
                    <div class="stats"></div>
                    <span>통계</span>
                </a>
                <a href="employee.php" class="menu-item">
                    <div class="user"></div>
                    <span>사원관리</span>
                </a>
                <a href="inflow.php" class="menu-item">
                    <div class="db"></div>
                    <span>DB배정</span>
                </a>
                <a href="consult_manager.php" class="menu-item">
                    <div class="manager"></div>
                    <span>사무장상담일지</span>
                </a>
                <a href="consult_paper.php" class="menu-item">
                    <div class="paper"></div>
                    <span>서류담당일지</span>
                </a>
                <a href="application_recovery.php" class="menu-item">
                    <div class="cert"></div>
                    <span>개인회생신청서</span>
                </a>
				<a href="application_bankruptcy.php" class="menu-item">
                    <div class="cert"></div>
                    <span>개인파산신청서</span>
                </a>
                <a href="case_management.php" class="menu-item">
                    <div class="time"></div>
                    <span>사건관리</span>
                </a>
                <a href="schedule.php" class="menu-item">
                    <div class="schedule"></div>
                    <span>기일관리</span>
                </a>
                <a href="calendar.php" class="menu-item">
                    <div class="calendar"></div>
                    <span>달력</span>
                </a>
                <a href="chatbot.php" class="menu-item">
                    <div class="chat"></div>
                    <span>챗봇</span>
                </a>
            </div>
        </div>
    </header>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
=======
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
    <link rel="stylesheet" href="css/header.css">
	<?php if(isset($additional_css)) echo $additional_css; ?>
</head>
<body>
    <header>
        <div class="header-top">
            <div class="menu-btns">
                <button onclick="window.open('https://www.scourt.go.kr/portal/information/events/search/search.jsp', 'popup', 'width=800,height=600,scrollbars=yes');">사건검색</button>
                <button onclick="window.open('https://www.scourt.go.kr/region/location/RegionSearchListAction.work', 'popup', 'width=800,height=600,scrollbars=yes');">관할법원찾기</button>
                <button onclick="window.open('https://ecfs.scourt.go.kr/ecf/ecf300/ECF304_2.jsp', 'popup', 'width=800,height=600,scrollbars=yes');">송달료계산</button>
                <button onclick="window.open('about:blank', 'popup', 'width=800,height=600,scrollbars=yes');">예상변제금</button>
            </div>
            <div class="header-right">
                <div class="user-group">
					<div class="user-info">
						<span><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
					</div>
					<button id="logoutBtn" class="logout-btn">로그아웃</button>
				</div>
                <button id="header_alert">
					<span>알림</span>
					<div class="alert-circle">20</div>
				</button>
            </div>
        </div>
        <div class="header-bottom">
            <div class="menu-grid">
                <a href="statistics.php" class="menu-item">
                    <div class="stats"></div>
                    <span>통계</span>
                </a>
                <a href="employee.php" class="menu-item">
                    <div class="user"></div>
                    <span>사원관리</span>
                </a>
                <a href="inflow.php" class="menu-item">
                    <div class="db"></div>
                    <span>DB배정</span>
                </a>
                <a href="consult_manager.php" class="menu-item">
                    <div class="manager"></div>
                    <span>사무장상담일지</span>
                </a>
                <a href="consult_paper.php" class="menu-item">
                    <div class="paper"></div>
                    <span>서류담당일지</span>
                </a>
                <a href="application_recovery.php" class="menu-item">
                    <div class="cert"></div>
                    <span>개인회생신청서</span>
                </a>
				<a href="application_bankruptcy.php" class="menu-item">
                    <div class="cert"></div>
                    <span>개인파산신청서</span>
                </a>
                <a href="case_management.php" class="menu-item">
                    <div class="time"></div>
                    <span>사건관리</span>
                </a>
                <a href="schedule.php" class="menu-item">
                    <div class="schedule"></div>
                    <span>기일관리</span>
                </a>
                <a href="calendar.php" class="menu-item">
                    <div class="calendar"></div>
                    <span>달력</span>
                </a>
                <a href="chatbot.php" class="menu-item">
                    <div class="chat"></div>
                    <span>챗봇</span>
                </a>
            </div>
        </div>
    </header>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
>>>>>>> 719d7c8 (Delete all files)
    <script src="js/header.js"></script>