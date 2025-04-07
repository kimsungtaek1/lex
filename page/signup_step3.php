<?php
session_start();
/*
// 회원가입 정보가 세션에 없으면 첫 페이지로 리다이렉트
if (!isset($_SESSION['signup_userid']) || !isset($_SESSION['signup_name'])) {
	header("Location: signup_step1.php");
	exit;
}

// 회원 정보 가져오기
$name = $_SESSION['signup_name'];
$userid = $_SESSION['signup_userid'];
$company = $_SESSION['signup_company'] ?? '';
$member_type = $_SESSION['signup_member_type'] ?? 'personal';
$member_type_kr = $member_type == 'personal' ? '개인' : '사업자';

// 회원가입 세션 정보 삭제 (사용 후 제거)
unset($_SESSION['signup_name']);
unset($_SESSION['signup_userid']);
unset($_SESSION['signup_company']);
unset($_SESSION['signup_member_type']);
*/
?>

<?php include '../index_header.php'; ?>
<div class="signup-container">
	<div class="signup-top">
		<h2 class="signup-title"><?=$member_type_kr?> 회원 가입</h2>
		<div class="signup-steps">
			<div class="step">
				<div class="step-box">
					STEP 1
					<span class="step-desc">약관동의</span>
				</div>
			</div>
			<div class="step-arrow">&#10095;</div>
			<div class="step">
				<div class="step-box">
					STEP 2
					<span class="step-desc">정보입력</span>
				</div>
			</div>
			<div class="step-arrow">&#10095;</div>
			<div class="step current">
				<div class="step-box highlight">
					STEP 3
					<span class="step-desc">가입완료</span>
				</div>
			</div>
		</div>
	</div>
	<div class="signup-content">
		<div class="complete-box">
			<div class="complete-message">
				<h1>회원 가입 완료</h1>
				<img src="../img/logo_gray.png"></img>
				<p>회원가입해주셔서 감사합니다.</p>
				<button class="home-btn" onclick="location.href='../index.php'"><img src="../img/home.png"></img></button>
			</div>
		</div>
	</div>
</div>

<?php include '../index_footer.php'; ?>