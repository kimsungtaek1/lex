<?php
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <header class="top-menu">
        <nav>
            <ul>
                <li><a href="#">렉스솔루션</a></li>
                <li><a href="#">제품소개</a></li>
                <li><a href="#">공지사항</a></li>
                <li><a href="#">고객지원</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div class="login-container">
            <div class="left-section">
				<h1>Your Partner</h1>
                <img src="img/logo_big.png" alt="Your Partner" class="logo">
                <h1 class="subtitle">회생•파산 법무관리 솔루션 </h1>
                <p>사용자 중심에 맞춘 디자인 설계</p>
                <p>신속하고 정확한 법무 업무 진행으로 효율적인 업무 관리</p>
                <div class="button-group">
                    <button class="primary-btn">무료 시작하기</button>
                    <button class="primary-btn">결제 후 바로 사용</button>
                </div>
            </div>
            <div class="right-section">
                <form id="loginForm" class="login-form">
                    <div class="input-group">
                        <input type="text" id="username"  required>
                        <input type="password" id="password"  required>
                    </div>
                    <button type="submit" class="login-btn">LOGIN</button>
                    <div class="form-footer">
                        <a href="page/signup_step1.php" class="find-account">회원가입</a>
                        <a href="#" class="id-inquiry">ID/PW찾기</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/index.js"></script>
</body>
</html>