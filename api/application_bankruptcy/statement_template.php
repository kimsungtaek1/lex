<div class="asset-box" data-type="highest_education">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>최종학력</span>
			</div>
		</div>
	</div>
	
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form">
				<div class="form-title">
					<span>일자</span>
				</div>
				<div class="form-content">
					<input type="date" class="form-control form-content-short" id="graduation_date" name="graduation_date">
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>학교명</span>
				</div>
				<div class="form-content">
					<input type="text" class="form-control" id="school_name" name="school_name">
				</div>
			</div>
		</div>
		<div class="right-section">
			<div class="form">
				<div class="form-title">
					<span>졸업여부</span>
				</div>
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="graduation_status_graduate" name="graduation_status" value="졸업">
						<label for="graduation_status_graduate">졸업</label>
						<input type="radio" id="graduation_status_dropout" name="graduation_status" value="중퇴">
						<label for="graduation_status_dropout">중퇴</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent btn-right">
					<button type="button" class="btn-save" id="save_education">저장</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="asset-box" data-type="career_history">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>과거경력&nbsp;&nbsp;|&nbsp;&nbsp;최근 경력부터 기재</span>
			</div>
			<div class="button-group">
				<button type="button" class="btn-add2" id="add_career">추가</button>
			</div>
		</div>
	</div>
	
	<div id="career_container">
		
	</div>
</div>

<div class="asset-box" data-type="domestic_court_history">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>동시에 개인파산을 신청한 가족이 있는지 여부</span>
			</div>
		</div>
	</div>
	
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form">
				<div class="form-title">
					<span>신청 가족 여부</span>
				</div>
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="family_bankruptcy_yes" name="family_application" value="네">
						<label for="family_bankruptcy_yes">네</label>
						<input type="radio" id="family_bankruptcy_no" name="family_application" value="아니오">
						<label for="family_bankruptcy_no">아니오</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>배우자와 파산신청</span>
				</div>
				<div class="form-content">
					성명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="spouse_bankruptcy_name" name="spouse_bankruptcy_name">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent">
				</div>
			</div>
		</div>
		<div class="right-section">
			<div class="form">
				<div class="form-title">
					<span>배우자 외 다른 가족과<br>파산신청</span>
				</div>
				<div class="form-content form-content-2">
					<textarea class="form-control form-content-long" id="other_family_bankruptcy_members" name="other_family_bankruptcy_members" placeholder="성명과 관계를 작성하여 주십시오." rows="2"></textarea>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent btn-right">
					<button type="button" class="btn-save" id="save_family_bankruptcy_info">저장</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="asset-box" data-type="life_history">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>현재까지의 생활상황</span>
			</div>
		</div>
	</div>
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;사기죄, 사기파산죄, 과태파산죄, 도박죄로 고소되거나 형사재판을 받은 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="fraud_experience_yes" name="fraud_experience" value="있음">
						<label for="fraud_experience_yes">있음</label>
						<input type="radio" id="fraud_experience_no" name="fraud_experience" value="없음">
						<label for="fraud_experience_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					사유&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="fraud_reason" name="fraud_reason">
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;과거에 파산신청을 하였다가 취하하거나 기각당한 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="past_bankruptcy_yes" name="past_bankruptcy" value="있음">
						<label for="past_bankruptcy_yes">있음</label>
						<input type="radio" id="past_bankruptcy_no" name="past_bankruptcy" value="없음">
						<label for="past_bankruptcy_no">없음</label>
					</div>
					<button type="button" id="add_bankruptcy_history_record">추가</button>
				</div>
			</div>
			
			<div id="bankruptcy_history_container"></div>
			
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;과거에 파산선고를 받은 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="past_bankruptcy_declared_yes" name="past_bankruptcy_declared" value="있음">
						<label for="past_bankruptcy_declared_yes">있음</label>
						<input type="radio" id="past_bankruptcy_declared_no" name="past_bankruptcy_declared" value="없음">
						<label for="past_bankruptcy_declared_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="bankruptcy_declared_date" name="bankruptcy_declared_date">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="bankruptcy_declared_court" name="bankruptcy_declared_court">
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;그 파산신청에 이어서 면책을 받은 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="past_discharge_yes" name="past_discharge" value="있음">
						<label for="past_discharge_yes">있음</label>
						<input type="radio" id="past_discharge_no" name="past_discharge" value="없음">
						<label for="past_discharge_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="discharge_date" name="discharge_date">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="discharge_court" name="discharge_court">
					확정일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="discharge_confirmed_date" name="discharge_confirmed_date">
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;개인회생절차를 이용한 경험 (개인회생절차 중이면 기각될 수 있음)</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="personal_rehabilitation_yes" name="personal_rehabilitation" value="있음">
						<label for="personal_rehabilitation_yes">있음</label>
						<input type="radio" id="personal_rehabilitation_no" name="personal_rehabilitation" value="없음">
						<label for="personal_rehabilitation_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					인가결정일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="approval_date" name="approval_date">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="approval_court" name="approval_court">
					사건번호&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="approval_case_number" name="approval_case_number">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					폐지결정일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="cancellation_date" name="cancellation_date">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="cancellation_court" name="cancellation_court">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					폐지사유&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="cancellation_reason" name="cancellation_reason">
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;그 개인회생신청에서 면책을 받은 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="rehabilitation_discharge_yes" name="rehabilitation_discharge" value="있음">
						<label for="rehabilitation_discharge_yes">있음</label>
						<input type="radio" id="rehabilitation_discharge_no" name="rehabilitation_discharge" value="없음">
						<label for="rehabilitation_discharge_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="rehabilitation_discharge_date" name="rehabilitation_discharge_date">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="rehabilitation_discharge_court" name="rehabilitation_discharge_court">
					사건번호&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="rehabilitation_discharge_case_number" name="rehabilitation_discharge_case_number">
				</div>
			</div>
		</div>

		
		<div class="right-section">
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;과거 1년간 물건을 원가나 일부로 구입하고 대금을 전부 지급하지 않은 상태에서 처분(매각, 담보 등)을 한 경험</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="unpaid_sales_yes" name="unpaid_sales" value="있음">
						<label for="unpaid_sales_yes">있음</label>
						<input type="radio" id="unpaid_sales_no" name="unpaid_sales" value="없음">
						<label for="unpaid_sales_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					품명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="unpaid_goods_name" name="unpaid_goods_name">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					구입시기&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control" id="unpaid_purchase_date" name="unpaid_purchase_date">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					가격&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="unpaid_price" name="unpaid_price">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					처분시기&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control" id="unpaid_disposal_date" name="unpaid_disposal_date">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					처분방법&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="unpaid_disposal_method" name="unpaid_disposal_method">
				</div>
			</div>

			<div class="form-title-top-sub">
				<span>개인 영업 정황이 있는 분만 아래 항목을 기재하여 주십시오.</span>
			</div>

			<div class="form-title-top">
				<span>|&nbsp;&nbsp;개인 영업 중 사업 정부의 기재</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="checkbox-group">
						<input type="checkbox" id="business_record_normal" name="business_record_type[]" value="정상하게 기재">
						<label for="business_record_normal">정상하게 기재</label>
						<input type="checkbox" id="business_record_inaccurate" name="business_record_type[]" value="부정확하게 기재">
						<label for="business_record_inaccurate">부정확하게 기재</label>
						<input type="checkbox" id="business_record_none" name="business_record_type[]" value="기장하지 않음">
						<label for="business_record_none">기장하지 않음</label>
					</div>
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;영업 중에 도산을 염려한 침하여 상품을 부당하게 염가로 매각한 사실</span>
			</div>
			<div class="form">
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="unfair_sale_yes" name="unfair_sale" value="있음">
						<label for="unfair_sale_yes">있음</label>
						<input type="radio" id="unfair_sale_no" name="unfair_sale" value="없음">
						<label for="unfair_sale_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					매각상품&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="unfair_sale_product" name="unfair_sale_product">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					매각시기&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control" id="unfair_sale_date" name="unfair_sale_date">
				</div>
			</div>
			<div class="form">
				<div class="form-content">
					할인율&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="unfair_discount_rate" name="unfair_discount_rate">
				</div>
			</div>
			<div class="form-title-top">
				<span>|&nbsp;&nbsp;주의사항</span>
			</div>
			<div class="form-content form-content-6" style="flex-direction:column;align-items:flex-start;justify-content:center;">
				<p>- 개인 영업을 경영한 경험이 있는 분은 아래 8종류의 사실증명(현재부터 과거 3년까지의 기간)에 대하여 발급신청을 하고, 그에 따라 세무 공무원이 교부하여 주는 서류를 제출하여 주시기 바랍니다.<br><br>
				- 8종류의 사실증명<br>
				&nbsp;&nbsp;&nbsp;①사업자등록증명 ②휴업사실증명 ③폐업사업증명  ④납세 및 체납사실증명 ⑤소득금액증명  ⑥부가가치세과세표준증명  ⑦부가가치세면세사업자수입금액증명  ⑧표준재무제표증명(개인, 법인)</p>
			</div>
			<div class="form-content form-nocontent btn-right">
				<button type="button" class="btn-delete" id="cancel_life_history">취소</button>
				<button type="button" class="btn-save" id="save_life_history">저장</button>
			</div>
		</div>
	</div>
</div>

<div class="asset-box" data-type="creditor_status">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>채권자의 상황</span>
			</div>
		</div>
	</div>
	
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form">
				<div class="form-title">
					<span>채권자와 채무지급방법에 관하여 교섭한 경험</span>
				</div>
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="negotiation_yes" name="negotiation_experience" value="있음">
						<label for="negotiation_yes">있음</label>
						<input type="radio" id="negotiation_no" name="negotiation_experience" value="없음">
						<label for="negotiation_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>협의가 성립된 채권자수</span>
				</div>
				<div class="form-content">
					<input type="text" class="form-control form-content-short20" id="agreed_creditors_count" name="agreed_creditors_count">명
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>협의에 거쳐어 지급된<br>기간</span>
				</div>
				<div class="form-content">
					<input type="date" class="form-control form-content-short" id="payment_period_start" name="payment_period_start">부터&nbsp;&nbsp;
					<input type="date" class="form-control form-content-short" id="payment_period_end" name="payment_period_end">까지
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>매월 지급한 총액</span>
				</div>
				<div class="form-content">
					1개월 평균&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="monthly_payment_amount" name="monthly_payment_amount">원 정도
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span>지급내역</span>
				</div>
				<div class="form-content form-nocontent">
					<input type="text" class="form-control form-content-long" id="creditor_payment_details" name="creditor_payment_details" placeholder="누구에게 얼마를 지급하였는지를 작성하여 주십시오."/>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span></span>
				</div>
				<div class="form-content">
				</div>
			</div>
		</div>
		<div class="right-section">
			<div class="form">
				<div class="form-title form-notitle">
					<span>소송, 지급명령, 압류,<br>가압류 등을 받은 경험</span>
				</div>
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="legal_action_yes" name="legal_action" value="있음">
						<label for="legal_action_yes">있음</label>
						<input type="radio" id="legal_action_no" name="legal_action" value="없음">
						<label for="legal_action_no">없음</label>
					</div>
					<button type="button" id="add_legal_action">추가</button>
				</div>
			</div>
			
			<div id="legal_action_container">
				<!-- 동적으로 추가될 블록들이 여기에 생성됩니다 -->
			</div>
			
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent btn-right">
					<button type="button" class="btn-save" id="save_creditor_status">저장</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="asset-box" data-type="bankruptcy_reason">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>파산신청에 이르게 된 사정</span>
			</div>
		</div>
	</div>
	
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form">
				<div class="form-title form-notitle">
					<span>많은 채무를 지게 된 이유<br>(두 가지 이상 선택 가능)</span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_living_expense" name="debt_reason[]" value="생활비 부족">
					<label for="debt_reason_living_expense">생활비 부족</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;부양가족수&nbsp;&nbsp;|&nbsp;&nbsp;<input type="number" class="form-control form-content-short" id="dependents_count" name="dependents_count">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;부족한 생활비 항목&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="living_expense_shortage_items" name="living_expense_shortage_items">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_housing" name="debt_reason[]" value="주택구입자금 차용">
					<label for="debt_reason_housing">주택구입자금 차용</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;주택구입시기&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="house_purchase_date" name="house_purchase_date">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;주택처분시기&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short" id="house_disposal_date" name="house_disposal_date">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;구입한 주택의 명세&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short" id="house_details" name="house_details">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_extravagance" name="debt_reason[]" value="낭비 등(음식, 음주, 투자, 투기, 상품구입, 도박 등)">
					<label for="debt_reason_extravagance">낭비 등(음식, 음주, 투자, 투기, 상품구입, 도박 등)</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_business_failure" name="debt_reason[]" value="사업의 경영부진(다단계 사업포함)">
					<label for="debt_reason_business_failure">사업의 경영부진(다단계 사업포함)</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;사업시기&nbsp;&nbsp;|&nbsp;&nbsp;</span><input type="date" class="form-control form-content-short" id="business_start_date" name="business_start_date">
					부터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="date" class="form-control form-content-short" id="business_end_date" name="business_end_date">
					까지
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;사업종류&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="business_type_detail" name="business_type_detail">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_guarantee" name="debt_reason[]" value="타인(친족, 지인, 회사 등)의 채무 보증">
					<label for="debt_reason_guarantee">타인(친족, 지인, 회사 등)의 채무 보증</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_fraud" name="debt_reason[]" value="사기 피해">
					<label for="debt_reason_fraud">사기 피해</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;가해자 이름&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short20" id="fraud_perpetrator_name" name="fraud_perpetrator_name">
					관계&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short20" id="fraud_perpetrator_relationship" name="fraud_perpetrator_relationship">
					피해액수&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short20" id="fraud_damage_amount" name="fraud_damage_amount">
					원
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="debt_reason_other" name="debt_reason[]" value="그 밖의 사유">
					<label for="debt_reason_other">그 밖의 사유</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;사유&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="debt_reason_other_detail" name="debt_reason_other_detail">
				</div>
			</div>
		</div>
		<div class="right-section">
			<div class="form">
				<div class="form-title form-notitle">
					<span>지급 불가능하게 된 계기<br>(두가지 이상 선택가능)</span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_excess" name="inability_reason[]" value="변제해야 할 원리금이 불어나 수입 초과">
					<label for="inability_reason_excess">변제해야 할 원리금이 불어나 수입 초과</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_unemployment" name="inability_reason[]" value="실직">
					<label for="inability_reason_unemployment">실직</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_business_closure" name="inability_reason[]" value="경영 사정 악화로 사업 폐업">
					<label for="inability_reason_business_closure">경영 사정 악화로 사업 폐업</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_income_decrease" name="inability_reason[]" value="급여 또는 사업 소득의 감소">
					<label for="inability_reason_income_decrease">급여 또는 사업 소득의 감소</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_medical" name="inability_reason[]" value="병에 걸려 입원">
					<label for="inability_reason_medical">병에 걸려 입원</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content">
					<input type="checkbox" id="inability_reason_other" name="inability_reason[]" value="그 밖의 사유">
					<label for="inability_reason_other">그 밖의 사유</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span></span>
				</div>
				<div class="form-content">
					사유&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control" id="inability_reason_other_detail" name="inability_reason_other_detail">
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>지급 불가능하게 된 시기</span>
				</div>
				<div class="form-content">
					<input type="date" class="form-control form-content-short" id="inability_reason_other_date" name="inability_reason_other_date">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span>구체적 사정</span>
				</div>
				<div class="form-content">
					<span>오래된 사실관계부터 기재</span>
					<div style="width:15vw"></div>
					<input type="checkbox" id="exact_date_unknown" name="exact_date_unknown" value="별지 사용 (하단 기재 생략)">
					<label for="exact_date_unknown">별지 사용 (하단 기재 생략)</label>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span></span>
				</div>
				<div class="form-content form-content-2">
					<textarea class="form-control form-content-long" id="inability_timeline" name="inability_timeline" placeholder="시기와 사유를 작성하여 주십시오." rows="3"></textarea>
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent">
					※ 언제, 어떠한 사정 하에 누구로부터 얼마를 차용하여 어디에 사용하였는지, 무엇을 구입하였는지, 
					어떠한 사정하에 지급이 불가능하게 되었는지를 오래된 사실부터 시간순서에 따라 기재하여 주십시오. 
					별지를 사용하여도 됩니다.
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent btn-right">
					<button type="button" class="btn-delete" id="delete_bankruptcy_reason">삭제</button>
					<button type="button" class="btn-save" id="save_bankruptcy_reason">저장</button>
					<button type="button" class="btn-file btn-long" id="file_upload_btn">별지다운로드</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="asset-box" data-type="bankruptcy_history_records">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>지급이 불가능하게 된 시기 이후에 차용하거나 채무가 발생한 사실</span>
			</div>
			<div class="button-group">
				<button type="button" class="btn-add2" id="add_debt_after_insolvency">추가</button>
			</div>
		</div>
	</div>
	
	<div id="debt_after_insolvency_container">
		<!-- 동적으로 추가될 블록들이 여기에 생성됩니다 -->
	</div>
</div>

<div class="asset-box" data-type="bankruptcy_repayment_records">
	<div class="section-header">
		<div class="creditor-title">
			<div class="checkbox-group">
				<span>채무의 지급이 불가능하게 된 시점 이후에 일부 채권자에게만 변제한 경험</span>
			</div>
		</div>
	</div>
	
	<div class="content-wrapper">
		<div class="left-section">
			<div class="form">
				<div class="form-title">
					<span>경험 여부</span>
				</div>
				<div class="form-content">
					<div class="radio">
						<input type="radio" id="partial_repayment_yes" name="partial_repayment" value="있음">
						<label for="partial_repayment_yes">있음</label>
						<input type="radio" id="partial_repayment_no" name="partial_repayment" value="없음">
						<label for="partial_repayment_no">없음</label>
					</div>
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>변제 시기</span>
				</div>
				<div class="form-content">
					<input type="date" class="form-control form-content-short" id="partial_repayment_date" name="partial_repayment_date">
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span></span>
				</div>
				<div class="form-content">
				</div>
			</div>
		</div>
		<div class="right-section">
			<div class="form">
				<div class="form-title">
					<span>변제한 채권자 성명</span>
				</div>
				<div class="form-content">
					<input type="text" class="form-control form-content-long" id="partial_repaid_creditor_name" name="partial_repaid_creditor_name">
				</div>
			</div>
			<div class="form">
				<div class="form-title">
					<span>금액</span>
				</div>
				<div class="form-content">
					<input type="text" class="form-control form-content-long" id="partial_repaid_amount" name="partial_repaid_amount">
				</div>
			</div>
			<div class="form">
				<div class="form-title form-notitle">
					<span></span>
				</div>
				<div class="form-content form-nocontent btn-right">
					<button type="button" class="btn-save" id="save_partial_repayment">저장</button>
				</div>
			</div>
		</div>
	</div>
</div>