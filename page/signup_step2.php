<?php
// 약관 동의 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// 필수 약관 동의 확인
	if (!isset($_POST['agree_terms']) || $_POST['agree_terms'] !== 'Y' || 
		!isset($_POST['agree_privacy']) || $_POST['agree_privacy'] !== 'Y') {
		echo "<script>alert('필수 약관에 동의해야 합니다.'); history.back();</script>";
		exit;
	}
	
	// 회원 유형 확인
	$member_type = isset($_POST['member_type']) ? $_POST['member_type'] : 'personal';
	$member_type_kr = $member_type == 'personal' ? '개인' : '사업자';
	$marketing_agree = isset($_POST['agree_marketing']) ? 'Y' : 'N';
} else {
	// POST로 데이터가 전달되지 않았으면 약관 동의 페이지로 리다이렉트
	header("Location: signup_step1.php");
	exit;
}
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
			<div class="step current">
				<div class="step-box highlight">
					STEP 2
					<span class="step-desc">정보입력</span>
				</div>
			</div>
			<div class="step-arrow">&#10095;</div>
			<div class="step">
				<div class="step-box">
					STEP 3
					<span class="step-desc">가입완료</span>
				</div>
			</div>
		</div>
	</div>
	<div class="signup-content">
		<form id="signupForm" class="signup-form" method="post" action="../api/index/signup_process.php">
			<!-- 약관 동의 정보를 hidden 필드로 전달 -->
			<input type="hidden" name="agree_terms" value="<?php echo $_POST['agree_terms']; ?>">
			<input type="hidden" name="agree_privacy" value="<?php echo $_POST['agree_privacy']; ?>">
			<input type="hidden" name="agree_marketing" value="<?php echo $marketing_agree; ?>">
			<input type="hidden" name="member_type" value="<?php echo $member_type; ?>">
			
			<div class="form-group">
				<label for="userid">아이디 <span class="required">*</span></label>
				<div class="input-with-button">
					<input type="text" id="userid" name="userid" required>
					<button type="button" class="check-btn">중복확인</button>
				</div>
			</div>
			<div class="form-group">
				<label for="password">비밀번호 <span class="required">*</span></label>
				<input type="password" id="password" name="password" required>
			</div>
			<div class="form-group">
				<label for="password_confirm">비밀번호 확인 <span class="required">*</span></label>
				<input type="password" id="password_confirm" name="password_confirm" required>
			</div>
			
			<?php if ($member_type === 'personal'): ?>
			<div class="form-group">
				<label for="name">성명 <span class="required">*</span></label>
				<input type="text" id="name" name="name" required>
			</div>
			<?php endif; ?>
			
			<?php if ($member_type === 'business'): ?>
			<div class="form-group">
				<label for="representative">대표자명 <span class="required">*</span></label>
				<input type="text" id="representative" name="representative" required>
			</div>
			<?php endif; ?>
			
			<div class="form-group">
				<label for="phone">연락처 <span class="required">*</span></label>
				<input type="tel" id="phone" name="phone" placeholder="010-0000-0000" required>
			</div>
			<div class="form-group">
				<label for="email">이메일 <span class="required">*</span></label>
				<input type="email" id="email" name="email" required>
			</div>
			
			<!-- 회사 정보 영역 (개인/사업자 모두 표시) -->
			<div class="form-group">
				<label for="company_name">회사명 <span class="required">*</span></label>
				<input type="text" id="company_name" name="company_name" required>
			</div>
			
			<?php if ($member_type === 'business'): ?>
			<div class="form-group">
				<label for="business_number">사업자등록번호 <span class="required">*</span></label>
				<input type="text" id="business_number" name="business_number" placeholder="000-00-00000" required>
			</div>
			<?php endif; ?>
			
			<div class="form-group">
				<label for="company_tel">회사 전화 <span class="required">*</span></label>
				<input type="tel" id="company_tel" name="company_tel" placeholder="02-0000-0000" required>
			</div>
			<div class="form-group">
				<label for="company_fax">회사 팩스</label>
				<input type="tel" id="company_fax" name="company_fax" placeholder="02-0000-0000">
			</div>
			<div class="form-group">
				<label for="company_address">회사 주소 <span class="required">*</span></label>
				<div class="input-with-button">
					<input type="text" id="company_address" name="company_address" required>
					<button type="button" class="address-btn" data-target="company_address">주소찾기</button>
				</div>
			</div>
			
			<div class="form-buttons">
				<button type="button" class="prev-btn" onclick="location.href='signup_step1.php'">◀&nbsp;&nbsp;이전</button>
				<button type="submit" class="next-btn">가입&nbsp;&nbsp;▶</button>
			</div>
		</form>
	</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script src="../js/signup_step2.js"></script>
<?php include '../index_footer.php'; ?>