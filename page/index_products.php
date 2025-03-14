<?php include '../index_header.php'; ?>
<div class="products-container">
	<div class="page-header">
		<h2 class="page-title">제품소개</h2>
		<div class="breadcrumb">
			<span>홈</span> &gt; <span>제품소개</span>
		</div>
	</div>
	
	<div class="products-content">
		<div class="product-category">
			<h3>법률 솔루션</h3>
			<div class="product-list">
				<div class="product-item">
					<div class="product-image">
						<img src="img/product_legal1.png" alt="법률 솔루션 1">
					</div>
					<div class="product-info">
						<h4>개인회생 관리 시스템</h4>
						<p class="product-desc">
							개인회생 절차를 체계적으로 관리하고 추적할 수 있는 종합 솔루션입니다. 
							서류 준비, 일정 관리, 진행 상황 모니터링까지 한 번에 해결하세요.
						</p>
						<div class="product-features">
							<ul>
								<li>사용자 친화적 인터페이스</li>
								<li>자동 문서 생성 기능</li>
								<li>진행 상황 실시간 추적</li>
								<li>데이터 보안 강화</li>
							</ul>
						</div>
					</div>
				</div>
				
				<div class="product-item">
					<div class="product-image">
						<img src="img/product_legal2.png" alt="법률 솔루션 2">
					</div>
					<div class="product-info">
						<h4>파산 신청 자동화 도구</h4>
						<p class="product-desc">
							복잡한 파산 신청 절차를 간소화하고 자동화하여 시간과 비용을 절약하세요.
							필요한 모든 서류를 손쉽게 준비하고 제출할 수 있습니다.
						</p>
						<div class="product-features">
							<ul>
								<li>간편한 데이터 입력 시스템</li>
								<li>법적 요건 자동 검증</li>
								<li>서류 템플릿 제공</li>
								<li>진행 상황 알림 서비스</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="product-category">
			<h3>재무 관리 솔루션</h3>
			<div class="product-list">
				<div class="product-item">
					<div class="product-image">
						<img src="img/product_finance1.png" alt="재무 관리 솔루션">
					</div>
					<div class="product-info">
						<h4>채무 관리 시스템</h4>
						<p class="product-desc">
							효율적인 채무 관리와 상환 계획 수립을 도와주는 종합 솔루션입니다.
							복잡한 재정 상황을 명확하게 파악하고 최적의 결정을 내릴 수 있도록 지원합니다.
						</p>
						<div class="product-features">
							<ul>
								<li>채무 현황 종합 대시보드</li>
								<li>맞춤형 상환 일정 생성</li>
								<li>이자율 비교 및 분석</li>
								<li>자동 알림 및 리마인더</li>
							</ul>
						</div>
					</div>
				</div>
				
				<div class="product-item">
					<div class="product-image">
						<img src="img/product_finance2.png" alt="재무 관리 솔루션 2">
					</div>
					<div class="product-info">
						<h4>자산 평가 도구</h4>
						<p class="product-desc">
							정확한 자산 평가와 관리를 위한 전문 도구입니다. 
							부동산, 금융 자산, 기타 재산의 가치를 체계적으로 평가하고 관리할 수 있습니다.
						</p>
						<div class="product-features">
							<ul>
								<li>다양한 자산 유형 지원</li>
								<li>시장가치 자동 조회</li>
								<li>포트폴리오 분석</li>
								<li>시각적 자산 분포도</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="contact-section">
		<h3>더 자세한 정보를 원하시나요?</h3>
		<p>제품에 대한 상세한 정보와 데모 요청은 아래 연락처로 문의해주세요.</p>
		<div class="contact-info">
			<p>이메일: info@lexmarketing.co.kr</p>
			<p>전화: 02-1234-5678</p>
		</div>
		<button class="contact-btn" onclick="location.href='index_contact.php'">문의하기</button>
	</div>
</div>

<style>
.products-container{width:100%;max-width:1200px;margin:0 auto;padding:40px 20px;}
.page-header{margin-bottom:40px;border-bottom:2px solid #ddd;padding-bottom:15px;}
.page-title{font-size:2vw;color:#333;margin-bottom:10px;}
.breadcrumb{font-size:0.9vw;color:#777;}
.breadcrumb span{margin:0 5px;}
.products-content{margin-bottom:60px;}
.product-category{margin-bottom:50px;}
.product-category h3{font-size:1.5vw;color:#333;margin-bottom:20px;padding-left:10px;border-left:4px solid #00e6c3;}
.product-list{display:flex;flex-wrap:wrap;gap:30px;}
.product-item{display:flex;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.05);transition:transform 0.3s ease,box-shadow 0.3s ease;width:100%;}
.product-item:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.product-image{flex:1;max-width:300px;overflow:hidden;}
.product-image img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;}
.product-item:hover .product-image img{transform:scale(1.05);}
.product-info{flex:2;padding:25px;}
.product-info h4{font-size:1.2vw;color:#333;margin-bottom:10px;}
.product-desc{font-size:0.9vw;color:#666;line-height:1.6;margin-bottom:20px;}
.product-features ul{list-style:none;padding-left:0;}
.product-features li{position:relative;padding-left:25px;margin-bottom:8px;font-size:0.9vw;color:#555;}
.product-features li:before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:15px;height:15px;background-image:url('img/check.png');background-size:contain;background-repeat:no-repeat;}
.contact-section{background:#f5f5f5;padding:40px;border-radius:8px;text-align:center;}
.contact-section h3{font-size:1.3vw;color:#333;margin-bottom:15px;}
.contact-section p{font-size:1vw;color:#666;margin-bottom:20px;}
.contact-info{margin-bottom:25px;}
.contact-info p{font-size:0.9vw;margin-bottom:5px;}
.contact-btn{background:#00e6c3;color:#fff;border:none;padding:12px 30px;border-radius:4px;font-size:1vw;cursor:pointer;transition:background 0.3s ease;}
.contact-btn:hover{background:#00cca9;}

@media (max-width:768px){
	.product-item{flex-direction:column;}
	.product-image{max-width:100%;}
	.page-title{font-size:4vw;}
	.product-category h3{font-size:3vw;}
	.product-info h4{font-size:2.5vw;}
	.product-desc,.product-features li{font-size:2vw;}
	.contact-section h3{font-size:3vw;}
	.contact-section p,.contact-info p,.contact-btn{font-size:2vw;}
}
</style>

<?php include '../index_footer.php'; ?>