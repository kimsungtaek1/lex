<?php
/**
 * navbar.php - 네비게이션 바 컴포넌트
 */
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-file-earmark-text me-2"></i>OCR 인식률 향상 시스템
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house me-1"></i>대시보드
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>" href="upload.php">
                        <i class="bi bi-upload me-1"></i>이미지 업로드
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>" href="jobs.php">
                        <i class="bi bi-list-check me-1"></i>작업 관리
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'templates.php' ? 'active' : ''; ?>" href="templates.php">
                        <i class="bi bi-file-earmark-text me-1"></i>템플릿
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dictionary.php' ? 'active' : ''; ?>" href="dictionary.php">
                        <i class="bi bi-book me-1"></i>사전
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_train.php' ? 'active' : ''; ?>" href="admin_train.php">
                        <i class="bi bi-gear me-1"></i>관리자
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>로그인</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
/**
 * footer.php - 푸터 컴포넌트
 */
?>
<footer class="bg-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>OCR 인식률 향상 시스템</h5>
                <p class="text-muted">네이버 Clova OCR API를 활용한 인식률 향상 솔루션</p>
            </div>
            <div class="col-md-3">
                <h6>바로가기</h6>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-decoration-none">대시보드</a></li>
                    <li><a href="upload.php" class="text-decoration-none">이미지 업로드</a></li>
                    <li><a href="templates.php" class="text-decoration-none">템플릿 관리</a></li>
                    <li><a href="dictionary.php" class="text-decoration-none">사전 관리</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>도움말</h6>
                <ul class="list-unstyled">
                    <li><a href="help.php" class="text-decoration-none">사용 가이드</a></li>
                    <li><a href="faq.php" class="text-decoration-none">자주 묻는 질문</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="text-muted small mb-0">
                &copy; <?php echo date('Y'); ?> OCR 인식률 향상 시스템. 모든 권리 보유.
            </p>
        </div>
    </div>
</footer>
