<?php include '../index_header.php'; ?>
<div class="contact-container">
	<div class="page-header">
		<h2 class="page-title">고객지원</h2>
		<div class="breadcrumb">
			<span>홈</span> &gt; <span>고객지원</span>
		</div>
	</div>
	
	<div class="contact-content">
		<div class="contact-tabs">
			<button class="tab-btn active" data-tab="inquiry">1:1 문의</button>
			<button class="tab-btn" data-tab="faq">자주 묻는 질문</button>
			<button class="tab-btn" data-tab="info">이용안내</button>
		</div>
		
		<div class="tab-content" id="inquiry-content">
			<div class="contact-form-container">
				<div class="form-description">
					<h3>1:1 문의하기</h3>
					<p>
						궁금하신 내용이나 요청사항을 남겨주시면 담당자가 확인 후<br>
						최대한 빠르게 답변 드리도록 하겠습니다.
					</p>
					<div class="contact-channels">
						<div class="channel">
							<img src="img/phone_icon.png" alt="전화문의">
							<div class="channel-info">
								<h4>전화문의</h4>
								<p>02-1234-5678</p>
								<p class="small">평일 09:30 ~ 18:00, 주말/공휴일 휴무</p>
							</div>
						</div>
						<div class="channel">
							<img src="img/email_icon.png" alt="이메일문의">
							<div class="channel-info">
								<h4>이메일문의</h4>
								<p>support@lexmarketing.co.kr</p>
								<p class="small">24시간 접수 가능, 영업일 기준 24시간 내 답변</p>
							</div>
						</div>
					</div>
				</div>
				
				<form id="contactForm" class="contact-form">
					<div class="form-group">
						<label for="inquiry_type">문의유형 <span class="required">*</span></label>
						<select id="inquiry_type" name="inquiry_type" required>
							<option value="">문의유형을 선택하세요</option>
							<option value="service">서비스 문의</option>
							<option value="technical">기술 지원</option>
							<option value="proposal">제안/의견</option>
							<option value="partnership">제휴 문의</option>
							<option value="etc">기타</option>
						</select>
					</div>
					
					<div class="form-group">
						<label for="name">이름 <span class="required">*</span></label>
						<input type="text" id="name" name="name" required>
					</div>
					
					<div class="form-group">
						<label for="email">이메일 <span class="required">*</span></label>
						<input type="email" id="email" name="email" required>
					</div>
					
					<div class="form-group">
						<label for="phone">연락처 <span class="required">*</span></label>
						<input type="tel" id="phone" name="phone" placeholder="010-0000-0000" required>
					</div>
					
					<div class="form-group">
						<label for="title">제목 <span class="required">*</span></label>
						<input type="text" id="title" name="title" required>
					</div>
					
					<div class="form-group">
						<label for="content">내용 <span class="required">*</span></label>
						<textarea id="content" name="content" rows="6" required></textarea>
					</div>
					
					<div class="form-group file-group">
						<label for="attachment">첨부파일</label>
						<div class="file-input-wrapper">
							<input type="text" id="file_name" readonly placeholder="파일을 선택해주세요">
							<input type="file" id="attachment" name="attachment" class="hidden-file-input">
							<button type="button" class="file-btn">파일선택</button>
						</div>
						<p class="file-help">※ 최대 5MB, 파일형식: jpg, png, gif, pdf, doc, docx, xls, xlsx, zip</p>
					</div>
					
					<div class="form-group privacy-agree">
						<label>
							<input type="checkbox" id="privacy_agree" name="privacy_agree" required>
							개인정보 수집 및 이용에 동의합니다. (필수)
						</label>
						<a href="#" class="privacy-detail">자세히 보기</a>
					</div>
					
					<div class="form-buttons">
						<button type="submit" class="submit-btn">문의하기</button>
						<button type="reset" class="reset-btn">취소</button>
					</div>
				</form>
			</div>
		</div>
		
		<div class="tab-content" id="faq-content" style="display:none;">
			<div class="faq-container">
				<h3>자주 묻는 질문</h3>
				<div class="faq-category">
					<button class="category-btn active" data-category="all">전체</button>
					<button class="category-btn" data-category="service">서비스</button>
					<button class="category-btn" data-category="account">계정/로그인</button>
					<button class="category-btn" data-category="payment">결제/환불</button>
					<button class="category-btn" data-category="technical">기술지원</button>
				</div>
				
				<div class="faq-list">
					<div class="faq-item" data-category="service">
						<div class="faq-question">
							<span class="q-mark">Q</span>
							<p>렉스마케팅의 주요 서비스는 무엇인가요?</p>
							<span class="toggle-icon">+</span>
						</div>
						<div class="faq-answer">
							<span class="a-mark">A</span>
							<div class="answer-content">
								<p>
									렉스마케팅은 개인회생 및 파산 관련 법률 솔루션과 재무 관리 도구를 제공하고 있습니다. 
									주요 서비스로는 개인회생 관리 시스템, 파산 신청 자동화 도구, 채무 관리 시스템, 자산 평가 도구 등이 있습니다. 
									각 서비스에 대한 자세한 내용은 '제품소개' 페이지에서 확인하실 수 있습니다.
								</p>
							</div>
						</div>
					</div>
					
					<div class="faq-item" data-category="account">
						<div class="faq-question">
							<span class="q-mark">Q</span>
							<p>회원가입은 어떻게 하나요?</p>
							<span class="toggle-icon">+</span>
						</div>
						<div class="faq-answer">
							<span class="a-mark">A</span>
							<div class="answer-content">
								<p>
									회원가입은 웹사이트 우측 상단의 '회원가입' 버튼을 클릭하여 진행할 수 있습니다. 
									개인회원과 사업자회원 중 선택하여 가입할 수 있으며, 필수 정보를 입력한 후 이메일 인증을 완료하면 가입이 완료됩니다.
									사업자회원의 경우 사업자등록증 제출이 필요할 수 있습니다.
								</p>
							</div>
						</div>
					</div>
					
					<div class="faq-item" data-category="payment">
						<div class="faq-question">
							<span class="q-mark">Q</span>
							<p>서비스 요금은 어떻게 되나요?</p>
							<span class="toggle-icon">+</span>
						</div>
						<div class="faq-answer">
							<span class="a-mark">A</span>
							<div class="answer-content">
								<p>
									서비스 요금은 선택하는 솔루션과 사용 기간에 따라 다릅니다. 
									기본 요금제와 프리미엄 요금제를 제공하고 있으며, 월간 구독 또는 연간 구독으로 이용하실 수 있습니다.
									자세한 요금 정보는 로그인 후 마이페이지에서 확인하거나 고객센터로 문의해주시기 바랍니다.
								</p>
							</div>
						</div>
					</div>
					
					<div class="faq-item" data-category="technical">
						<div class="faq-question">
							<span class="q-mark">Q</span>
							<p>시스템 이용 중 오류가 발생했을 때는 어떻게 해야 하나요?</p>
							<span class="toggle-icon">+</span>
						</div>
						<div class="faq-answer">
							<span class="a-mark">A</span>
							<div class="answer-content">
								<p>
									시스템 이용 중 오류가 발생한 경우, 먼저 웹브라우저를 최신 버전으로 업데이트하고 캐시를 삭제한 후 다시 시도해보세요.
									문제가 지속되는 경우 오류 내용을 캡처하여 1:1 문의 또는 이메일(support@lexmarketing.co.kr)로 보내주시면 
									기술지원팀이 신속하게 도움을 드리겠습니다.
								</p>
							</div>
						</div>
					</div>
					
					<div class="faq-item" data-category="service">
	<div class="faq-question">
		<span class="q-mark">Q</span>
		<p>데이터 백업은 어떻게 이루어지나요?</p>
		<span class="toggle-icon">+</span>
	</div>
	<div class="faq-answer">
		<span class="a-mark">A</span>
		<div class="answer-content">
			<p>
				렉스마케팅의 모든 서비스는 자동 백업 시스템을 갖추고 있습니다. 
				사용자 데이터는 일일, 주간, 월간 단위로 자동 백업되며, 최대 6개월까지의 데이터를 복구할 수 있습니다.
				필요한 경우 마이페이지에서 데이터 백업 파일을 직접 다운로드하실 수도 있습니다.
			</p>
		</div>
	</div>
</div>
				</div>
			</div>
		</div>
		
		<div class="tab-content" id="info-content" style="display:none;">
			<div class="info-container">
				<h3>이용안내</h3>
				<div class="info-section">
					<h4>서비스 이용 절차</h4>
					<div class="process-steps">
						<div class="step">
							<div class="step-icon">STEP 1</div>
							<div class="step-desc">
								<h5>회원가입</h5>
								<p>홈페이지에서 회원가입을 진행합니다. 개인회원 또는 사업자회원으로 가입할 수 있습니다.</p>
							</div>
						</div>
						<div class="step">
							<div class="step-icon">STEP 2</div>
							<div class="step-desc">
								<h5>서비스 선택</h5>
								<p>필요한 서비스를 선택하고 이용계약을 체결합니다. 월간, 연간 등 요금제를 선택할 수 있습니다.</p>
							</div>
						</div>
						<div class="step">
							<div class="step-icon">STEP 3</div>
							<div class="step-desc">
								<h5>서비스 이용</h5>
								<p>구독한 서비스를 이용하며, 필요에 따라 고객지원을 받을 수 있습니다.</p>
							</div>
						</div>
					</div>
				</div>
				
				<div class="info-section">
					<h4>이용 시간</h4>
					<div class="service-hours">
						<div class="hours-item">
							<h5>온라인 서비스</h5>
							<p>24시간 365일 이용 가능</p>
							<p class="note">※ 시스템 점검 시간(매월 셋째 주 수요일 02:00~04:00)에는 서비스 이용이 제한될 수 있습니다.</p>
						</div>
						<div class="hours-item">
							<h5>고객센터 운영시간</h5>
							<p>평일 09:30 ~ 18:00 (점심시간 12:00 ~ 13:00)</p>
							<p class="note">※ 토요일, 일요일, 공휴일은 휴무입니다.</p>
						</div>
					</div>
				</div>
				
				<div class="info-section">
					<h4>서비스 이용료</h4>
					<div class="pricing-info">
						<table class="pricing-table">
							<thead>
								<tr>
									<th>서비스 유형</th>
									<th>월간 이용료</th>
									<th>연간 이용료</th>
									<th>비고</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>기본형</td>
									<td>30,000원</td>
									<td>300,000원 <span class="discount">(17% 할인)</span></td>
									<td>모듈 별 개별 구매 가능</td>
								</tr>
								<tr>
									<td>프리미엄형</td>
									<td>50,000원</td>
									<td>500,000원 <span class="discount">(17% 할인)</span></td>
									<td>모든 기능 이용 가능</td>
								</tr>
								<tr>
									<td>엔터프라이즈형</td>
									<td>별도 협의</td>
									<td>별도 협의</td>
									<td>맞춤형 솔루션 제공</td>
								</tr>
							</tbody>
						</table>
						<p class="pricing-note">※ 상기 금액은 부가세 별도 금액입니다.</p>
						<p class="pricing-note">※ 자세한 서비스 이용료는 고객센터로 문의해주세요.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.contact-container{width:100%;max-width:1200px;margin:0 auto;padding:40px 20px;}
.page-header{margin-bottom:40px;border-bottom:2px solid #ddd;padding-bottom:15px;}
.page-title{font-size:2vw;color:#333;margin-bottom:10px;}
.breadcrumb{font-size:0.9vw;color:#777;}
.breadcrumb span{margin:0 5px;}
.contact-content{margin-bottom:60px;}

/* 탭 스타일 */
.contact-tabs{display:flex;margin-bottom:30px;border-bottom:1px solid #ddd;}
.tab-btn{flex:1;padding:15px;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;font-size:1vw;font-weight:600;color:#777;transition:all 0.3s ease;}
.tab-btn:hover{color:#333;}
.tab-btn.active{color:#00e6c3;border-bottom-color:#00e6c3;}

/* 1:1 문의 폼 스타일 */
.contact-form-container{display:flex;flex-wrap:wrap;gap:40px;}
.form-description{flex:1;min-width:300px;}
.form-description h3{font-size:1.3vw;color:#333;margin-bottom:20px;}
.form-description p{font-size:0.9vw;color:#666;line-height:1.6;margin-bottom:30px;}
.contact-channels{display:flex;flex-direction:column;gap:20px;margin-top:40px;}
.channel{display:flex;align-items:flex-start;gap:15px;}
.channel img{width:40px;height:40px;}
.channel-info h4{font-size:1vw;color:#333;margin-bottom:5px;}
.channel-info p{margin:0;font-size:0.9vw;font-weight:600;color:#555;}
.channel-info .small{font-size:0.8vw;color:#888;font-weight:normal;}

.contact-form{flex:1.5;min-width:400px;}
.form-group{margin-bottom:20px;}
.form-group label{display:block;margin-bottom:8px;font-size:0.9vw;font-weight:600;color:#333;}
.form-group input, .form-group select, .form-group textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;font-size:0.9vw;}
.form-group textarea{resize:vertical;}
.required{color:#e74c3c;}

.file-group{position:relative;}
.file-input-wrapper{display:flex;gap:10px;}
#file_name{flex:1;background:#f5f5f5;cursor:default;}
.hidden-file-input{display:none;}
.file-btn{padding:10px 15px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:0.8vw;}
.file-help{font-size:0.8vw;color:#888;margin-top:5px;}

.privacy-agree{display:flex;justify-content:space-between;align-items:center;}
.privacy-agree label{margin:0;cursor:pointer;}
.privacy-agree input[type="checkbox"]{width:auto;margin-right:5px;}
.privacy-detail{font-size:0.8vw;color:#00e6c3;text-decoration:none;}

.form-buttons{display:flex;gap:10px;justify-content:center;margin-top:30px;}
.submit-btn, .reset-btn{padding:12px 30px;border:none;border-radius:4px;cursor:pointer;font-size:0.9vw;transition:background 0.3s ease;}
.submit-btn{background:#00e6c3;color:#fff;}
.submit-btn:hover{background:#00cca9;}
.reset-btn{background:#f5f5f5;color:#555;border:1px solid #ddd;}
.reset-btn:hover{background:#eee;}

/* FAQ 스타일 */
.faq-container{padding:20px 0;}
.faq-container h3{font-size:1.3vw;color:#333;margin-bottom:20px;}
.faq-category{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:30px;}
.category-btn{padding:8px 20px;background:#f5f5f5;border:1px solid #ddd;border-radius:20px;cursor:pointer;font-size:0.8vw;transition:all 0.3s ease;}
.category-btn:hover{background:#eee;}
.category-btn.active{background:#00e6c3;color:#fff;border-color:#00e6c3;}

.faq-list{border-top:1px solid #ddd;}
.faq-item{border-bottom:1px solid #ddd;}
.faq-question{display:flex;align-items:center;padding:20px 15px;cursor:pointer;position:relative;}
.q-mark, .a-mark{display:flex;justify-content:center;align-items:center;width:25px;height:25px;background:#00e6c3;color:#fff;border-radius:50%;font-weight:bold;font-size:0.8vw;margin-right:15px;flex-shrink:0;}
.a-mark{background:#f5f5f5;color:#333;}
.faq-question p{font-size:1vw;font-weight:600;margin:0;flex:1;}
.toggle-icon{font-size:1.5vw;color:#999;}
.faq-answer{display:none;padding:0 15px 20px 55px;position:relative;}
.answer-content{font-size:0.9vw;color:#666;line-height:1.6;}

/* 이용안내 스타일 */
.info-container{padding:20px 0;}
.info-container h3{font-size:1.3vw;color:#333;margin-bottom:30px;}
.info-section{margin-bottom:40px;}
.info-section h4{font-size:1.1vw;color:#333;margin-bottom:20px;padding-left:10px;border-left:3px solid #00e6c3;}

.process-steps{display:flex;flex-wrap:wrap;gap:30px;}
.step{display:flex;align-items:flex-start;gap:15px;flex:1;min-width:250px;}
.step-icon{background:#00e6c3;color:#fff;padding:10px;border-radius:4px;font-size:0.8vw;font-weight:bold;text-align:center;min-width:70px;}
.step-desc h5{font-size:1vw;color:#333;margin-bottom:10px;}
.step-desc p{font-size:0.9vw;color:#666;line-height:1.6;margin:0;}

.service-hours{display:flex;flex-wrap:wrap;gap:40px;}
.hours-item{flex:1;min-width:250px;}
.hours-item h5{font-size:1vw;color:#333;margin-bottom:10px;}
.hours-item p{font-size:0.9vw;color:#555;margin:0 0 5px 0;}
.hours-item .note{font-size:0.8vw;color:#888;}

.pricing-table{width:100%;border-collapse:collapse;margin-bottom:15px;}
.pricing-table th{background:#f5f5f5;padding:12px 15px;text-align:center;border-top:1px solid #ddd;border-bottom:1px solid #ddd;font-size:0.9vw;font-weight:600;}
.pricing-table td{padding:12px 15px;text-align:center;border-bottom:1px solid #eee;font-size:0.9vw;}
.pricing-table td:first-child{font-weight:600;}
.discount{color:#e74c3c;font-size:0.8vw;}
.pricing-note{font-size:0.8vw;color:#888;margin:5px 0;}

@media (max-width:768px){
	.contact-form-container{flex-direction:column;}
	.contact-form{min-width:100%;}
	.page-title{font-size:4vw;}
	.breadcrumb{font-size:2vw;}
	.form-description h3, .faq-container h3, .info-container h3{font-size:3vw;}
	.form-description p{font-size:2vw;}
	.form-group label{font-size:2vw;}
	.form-group input, .form-group select, .form-group textarea{font-size:2vw;}
	.tab-btn{font-size:2vw;}
	.faq-question p{font-size:2vw;}
	.answer-content{font-size:1.8vw;}
	.step-desc h5, .hours-item h5{font-size:2.2vw;}
	.step-desc p, .hours-item p{font-size:2vw;}
	.process-steps, .service-hours{flex-direction:column;}
	.pricing-table th, .pricing-table td{font-size:1.8vw;padding:8px 5px;}
}
</style>

<script>
$(document).ready(function() {
	// 탭 전환 기능
	$(".tab-btn").click(function() {
		$(".tab-btn").removeClass("active");
		$(this).addClass("active");
		
		var tabId = $(this).data("tab");
		$(".tab-content").hide();
		$("#" + tabId + "-content").show();
	});
	
	// FAQ 카테고리 필터링
	$(".category-btn").click(function() {
		$(".category-btn").removeClass("active");
		$(this).addClass("active");
		
		var category = $(this).data("category");
		if(category === 'all') {
			$(".faq-item").show();
		} else {
			$(".faq-item").hide();
			$(".faq-item[data-category='" + category + "']").show();
		}
	});
	
	// FAQ 아코디언 기능
	$(".faq-question").click(function() {
		var $answer = $(this).next(".faq-answer");
		
		if($answer.is(":visible")) {
			$answer.slideUp();
			$(this).find(".toggle-icon").text("+");
		} else {
			$(".faq-answer").slideUp();
			$(".toggle-icon").text("+");
			$answer.slideDown();
			$(this).find(".toggle-icon").text("-");
		}
	});
	
	// 파일 첨부 기능
	$(".file-btn").click(function() {
		$("#attachment").click();
	});
	
	$("#attachment").change(function() {
		var fileName = $(this).val().split('\\').pop();
		$("#file_name").val(fileName || "파일을 선택해주세요");
	});
	
	// 문의 폼 제출
	$("#contactForm").submit(function(e) {
		e.preventDefault();
		
		// 개인정보 수집 동의 확인
		if(!$("#privacy_agree").is(":checked")) {
			alert("개인정보 수집 및 이용에 동의해주세요.");
			return false;
		}
		
		// 폼 데이터 수집
		var formData = new FormData(this);
		
		// 여기에 AJAX로 서버에 데이터 전송하는 코드 추가
		// 예시:
		/*
		$.ajax({
			url: "api/contact_process.php",
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if(response.success) {
					alert("문의가 성공적으로 접수되었습니다. 빠른 시일 내에 답변 드리겠습니다.");
					$("#contactForm")[0].reset();
				} else {
					alert("문의 접수에 실패했습니다. 다시 시도해주세요.");
				}
			},
			error: function() {
				alert("서버와의 통신 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.");
			}
		});
		*/
		
		// 임시 성공 메시지 (실제 구현 시 주석 처리)
		alert("문의가 성공적으로 접수되었습니다. 빠른 시일 내에 답변 드리겠습니다.");
		this.reset();
	});
});
</script>

<?php include '../index_footer.php'; ?>