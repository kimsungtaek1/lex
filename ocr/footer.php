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
