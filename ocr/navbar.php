<?php
/**
 * navbar.php - 네비게이션 바 컴포넌트
 */
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-file-earmark-text me-2"></i><?php echo h('OCR 인식률 향상 시스템'); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house me-1"></i><?php echo h('대시보드'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>" href="upload.php">
                        <i class="bi bi-upload me-1"></i><?php echo h('이미지 업로드'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>" href="jobs.php">
                        <i class="bi bi-list-check me-1"></i><?php echo h('작업 관리'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'templates.php' ? 'active' : ''; ?>" href="templates.php">
                        <i class="bi bi-file-earmark-text me-1"></i><?php echo h('템플릿'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dictionary.php' ? 'active' : ''; ?>" href="dictionary.php">
                        <i class="bi bi-book me-1"></i><?php echo h('사전'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_train.php' ? 'active' : ''; ?>" href="admin_train.php">
                        <i class="bi bi-gear me-1"></i><?php echo h('관리자'); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>