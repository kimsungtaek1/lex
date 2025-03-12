<?php include '../index_header.php'; ?>
<div class="signup-container">
	<div class="signup-top">
		<h2 class="signup-title">회원 가입</h2>
		<div class="signup-steps">
			<div class="step current">
				<div class="step-box highlight">
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
			<div class="step">
				<div class="step-box">
					STEP 3
					<span class="step-desc">가입완료</span>
				</div>
			</div>
		</div>
	</div>
	<div class="signup-content">
		<form id="agreementForm" class="signup-form" method="post" action="signup_step2.php">
			<div class="member-type-area">
				<h3>회원 유형</h3>
				<div class="member-type-selection">
					<label>
						<input type="radio" name="member_type" value="personal" checked> 개인회원
					</label>
					<label>
						<input type="radio" name="member_type" value="business"> 사업자회원
					</label>
				</div>
			</div>
			
			<div class="agreement-area">
				<h3>이용약관</h3>
				<div class="terms-box">
					<div class="terms-content">
						제1조 (목적)
						이 약관은 회사가 운영하는 웹사이트(이하 "사이트")에서 제공하는 서비스(이하 "서비스")를 이용함에 있어 회사와 이용자의 권리, 의무 및 책임사항을 규정함을 목적으로 합니다.
						
						제2조 (정의)
						1. "이용자"란 사이트에 접속하여 이 약관에 따라 회사가 제공하는 서비스를 받는 회원 및 비회원을 말합니다.
						2. "회원"이라 함은 사이트에 개인정보를 제공하여 회원등록을 한 자로서, 사이트의 정보를 지속적으로 제공받으며, 회사가 제공하는 서비스를 계속적으로 이용할 수 있는 자를 말합니다.
						
						제3조 (약관의 효력 및 변경)
						1. 이 약관은 사이트 화면에 게시하거나 기타의 방법으로 회원에게 공지함으로써 효력이 발생합니다.
						2. 회사는 약관의 규제에 관한 법률, 전자거래기본법, 전자서명법, 정보통신망 이용촉진 및 정보보호 등에 관한 법률, 방문판매 등에 관한 법률, 소비자보호법 등 관련법을 위배하지 않는 범위에서 이 약관을 개정할 수 있습니다.
					</div>
					<div class="terms-agree">
						<label>
							<input type="checkbox" name="agree_terms" value="Y" class="required-check"> 이용약관에 동의합니다. (필수)
						</label>
					</div>
				</div>
				
				<h3>개인정보 수집 및 이용동의</h3>
				<div class="terms-box">
					<div class="terms-content">
						1. 수집하는 개인정보 항목
						- 회원가입 시: 이름, 아이디, 비밀번호, 이메일, 연락처, 주소
						- 사업자회원 추가 정보: 회사명, 사업자등록번호, 대표자명
						
						2. 개인정보의 수집 및 이용목적
						- 서비스 제공에 관한 계약 이행 및 서비스 제공에 따른 요금정산
						- 회원 관리: 회원제 서비스 이용에 따른 본인확인, 개인식별, 불량회원의 부정 이용 방지
						
						3. 개인정보의 보유 및 이용기간
						- 회원탈퇴 시까지 (단, 관계법령에 따라 필요한 경우 일정기간 보존)
					</div>
					<div class="terms-agree">
						<label>
							<input type="checkbox" name="agree_privacy" value="Y" class="required-check"> 개인정보 수집 및 이용에 동의합니다. (필수)
						</label>
					</div>
				</div>
				
				<div class="all-agree">
					<label>
						<input type="checkbox" id="agree_all"> 모든 약관에 동의합니다.
					</label>
				</div>
			</div>
			
			<div class="form-buttons">
				<button type="button" id="nextBtn" class="next-btn">다음&nbsp;&nbsp;▶</button>
			</div>
		</form>
	</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../js/signup_step1.js"></script>
<?php include '../index_footer.php'; ?>