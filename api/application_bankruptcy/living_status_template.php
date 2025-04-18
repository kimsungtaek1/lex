<!-- 담보 확정 섹션 -->
<div class="section-header">
  <div class="creditor-title">
    <div class="checkbox-group">
      <span>현재 직업</span>
    </div>
  </div>
</div>
<div class="content-wrapper">
  <div class="left-section">
    <div class="form">
      <div class="form-title"><span>직업 형태</span></div>
      <div class="form-content">
        <input type="checkbox" id="job_type_self" name="job_type" value="자영">
        <label for="job_type_self">자영</label>
        <input type="checkbox" id="job_type_employee" name="job_type" value="고용">
        <label for="job_type_employee">고용</label>
        <input type="checkbox" id="job_type_unemployed" name="job_type" value="무직">
        <label for="job_type_unemployed">무직</label>
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>업종 또는 직업</span></div>
      <div class="form-content">
        <input type="text" id="job_industry" name="job_industry" class="form-control">
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>직장 또는 회사명</span></div>
      <div class="form-content">
        <input type="text" id="company_name" name="company_name" class="form-control">
      </div>
    </div>
  </div>
  <div class="right-section">
	<div class="form">
      <div class="form-title"><span>지위</span></div>
		<div class="form-content">
		  <input type="text" id="job_position" name="job_position" class="form-control">
		</div>
    </div>
    <div class="form">
      <div class="form-title"><span>취직시기</span></div>
      <div class="form-content">
        <input type="text" id="employment_period" name="employment_period" class="form-control">
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span></span></div>
      <div class="form-content btn-right">
        <button type="button" id="save_living_status_basic" class="btn-save-living">저장</button>
      </div>
    </div>
  </div>
</div>

<!-- 수입 상황 섹션 -->
<div class="section-header">
  <div class="creditor-title">
    <div class="checkbox-group">
      <span>수입 상황</span>
    </div>
  </div>
</div>
<div class="content-wrapper">
  <div class="left-section">
    <div class="form">
      <div class="form-title"><span>자영수입</span></div>
      <div class="form-content">
        <input type="text" id="self_income" name="self_income" data-type="money"> 원
        <span>&nbsp;&nbsp;&nbsp;&nbsp;※ 종합소득세신고서를 첨부해 주십시오.</span>
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>월급여</span></div>
      <div class="form-content">
        <input type="text" id="monthly_salary" name="monthly_salary" data-type="money"> 원
        <span>&nbsp;&nbsp;&nbsp;&nbsp;※ 급여명세서 또는 급여입금내역을 첨부해 주십시오.</span>
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>연금</span></div>
      <div class="form-content">
        <input type="text" id="pension" name="pension" data-type="money"> 원
        <span>&nbsp;&nbsp;&nbsp;&nbsp;※ 수급증명서를 첨부해 주십시오.</span>
      </div>
    </div>
  </div>
  <div class="right-section">
    <div class="form">
      <div class="form-title"><span>생활보호</span></div>
      <div class="form-content">
        <input type="text" id="living_support" name="living_support" data-type="money"> 원
        <span>&nbsp;&nbsp;&nbsp;&nbsp;※ 수급증명서를 첨부해 주십시오.</span>
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>기타</span></div>
      <div class="form-content">
        <input type="text" id="other_income" name="other_income" data-type="money"> 원
        <span>&nbsp;&nbsp;&nbsp;&nbsp;※ 수입원을 나타내는 자료를 첨부해 주십시오.</span>
      </div>
    </div>
    <div class="form">
      <div class="form-title"></div>
      <div class="form-content btn-right">
        <button type="button" id="save_living_status_income" class="btn-save-living">저장</button>
      </div>
    </div>
  </div>
</div>

<!-- 가족/동거인 상황 -->
<div class="section-header">
  <div class="creditor-title">
    <div class="checkbox-group">
      <span>가족/동거인 상황</span>
    </div>
    <div class="button-group">
      <button type="button" class="btn btn-add2" id="add_family_member">추가</button>
    </div>
  </div>
</div>
<div id="family_members_container"></div>

<!-- 주거상황 -->
<div class="section-header">
  <div class="creditor-title">
    <div class="checkbox-group">
      <span>주거상황</span>
    </div>
  </div>
</div>
<div class="content-wrapper">
  <div class="left-section">
    <div class="form">
      <div class="form-title"><span>거주 시작점</span></div>
		<div class="form-content">
		  <input type="text" id="living_start_date" name="living_start_date">
		</div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span>거주관계선택</span></div>
      <div class="form-content">
		<div class="checkbox-group">
          <input type="radio" id="family_status_1" name="family_status" value="1">
          <label for="family_status_1">㉠ 임대주택(신청인 이외의 자 임차 포함)</label>
        </div>
        <div class="checkbox-group">
          <input type="radio" id="family_status_2" name="family_status" value="2">
          <label for="family_status_2">㉡ 사택 또는 기숙사</label>
        </div>
      </div>
    </div>
    <div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content">
		<div class="checkbox-group">
          <input type="radio" id="family_status_3" name="family_status" value="3">
          <label for="family_status_3">㉢ 신청인 소유의 주택</label>
        </div>
        <div class="checkbox-group">
          <input type="radio" id="family_status_4" name="family_status" value="4">
          <label for="family_status_4">㉣ 친족 소유 주택에 무상거주</label>
        </div>
      </div>
    </div>
	<div class="form">
		<div class="form-title form-notitle"><span></span></div>
		<div class="form-content">
			<div class="checkbox-group">
			  <input type="radio" id="family_status_5" name="family_status" value="5">
			  <label for="family_status_5">㉤ 친족 외 소유 주택에 무상거주</label>
			</div>
			<div class="checkbox-group">
			  <input type="radio" id="family_status_6" name="family_status" value="6">
			  <label for="family_status_6">㉥ 기타</label>
			</div>
		</div>
    </div>
	<div class="form">
	  <div class="form-title"><span></span></div>
	  <div class="form-content">
		  기타 내용&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="family_status_etc" name="family_status_etc">
		</div>
	</div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content form-nocontent">
      </div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content form-nocontent">
      </div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content form-nocontent">
      </div>
    </div>
  </div>
  <div class="right-section">
    <div class="form">
      <div class="form-title form-notitle"><span>㉠또는 ㉡선택시</span></div>
      <div class="form-content">
          임대료(관리비 포함)&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="monthly_rent" name="monthly_rent" data-type="money"> 원
      </div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content">
		  임대보증금&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="rent_deposit" name="rent_deposit" data-type="money"> 원
		</div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content">
		  연체액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="rent_arrears" name="rent_arrears" data-type="money"> 원
		</div>
    </div>
	<div class="form">
      <div class="form-title"><span></span></div>
      <div class="form-content">
		  신청인 이외의 자가 임차인인 경우 임차인 성명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="tenant_name" name="tenant_name" class="form-content-short15">&nbsp;&nbsp;
		  신청인과의 관계&nbsp;&nbsp;|&nbsp;&nbsp; <input type="text" id="tenant_relation" name="tenant_relation" class="form-content-short15">
		</div>
    </div>
    <div class="form">
      <div class="form-title form-notitle"><span>㉣또는 ㉤선택시</span></div>
      <div class="form-content">
		  소유자 성명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="owner_name" name="owner_name" class="form-content-short15">&nbsp;&nbsp;
		  신청인과의 관계&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="owner_relation" name="owner_relation" class="form-content-short15">&nbsp;&nbsp;
		</div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"><span></span></div>
      <div class="form-content form-nocontent">
        신청인 이외의 자가 소유자이거나 임차인인데 함께 거주하지 않는 경우 그 경위&nbsp;&nbsp;|&nbsp;&nbsp;
      </div>
    </div>
	<div class="form">
      <div class="form-title"><span></span></div>
      <div class="form-content">
		  <input type="text" id="residence_reason" name="residence_reason" placeholder="이곳에 경위를 입력하십시오.">
		</div>
    </div>
    <div class="form">
      <div class="form-title"></div>
      <div class="form-content btn-right">
        <button type="button" id="save_living_status_additional" class="btn-save-living">저장</button>
      </div>
    </div>
  </div>
</div>

<!-- 조세 등 공과금 납부상황 -->
<div class="section-header">
  <div class="creditor-title">
    <div class="checkbox-group">
      <span>조세 등 공과금 납부상황</span>
    </div>
  </div>
</div>
<div class="content-wrapper" style="margin-bottom:2vw">
  <div class="left-section">
    <div class="form">
      <div class="form-title"><span>소득세</span></div>
      <div class="form-content">
        <input type="checkbox" id="income_tax_none" name="income_tax_status" value="미납액 없음">
        <label for="income_tax_none">미납액 없음</label>
        <input type="checkbox" id="income_tax_exist" name="income_tax_status" value="미납액 있음">
        <label for="income_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="income_tax_amount" name="income_tax_amount" data-type="money"> 원
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>주민세</span></div>
      <div class="form-content">
        <input type="checkbox" id="residence_tax_none" name="residence_tax_status" value="미납액 없음">
        <label for="residence_tax_none">미납액 없음</label>
        <input type="checkbox" id="residence_tax_exist" name="residence_tax_status" value="미납액 있음">
        <label for="residence_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="residence_tax_amount" name="residence_tax_amount" data-type="money"> 원
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>재산세</span></div>
      <div class="form-content">
        <input type="checkbox" id="property_tax_none" name="property_tax_status" value="미납액 없음">
        <label for="property_tax_none">미납액 없음</label>
        <input type="checkbox" id="property_tax_exist" name="property_tax_status" value="미납액 있음">
        <label for="property_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="property_tax_amount" name="property_tax_amount" data-type="money"> 원
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>의료보험료</span></div>
		<div class="form-content">
		  <input type="checkbox" id="health_insurance_none" name="health_insurance_status" value="미납액 없음">
		  <label for="health_insurance_none">미납액 없음</label>
		  <input type="checkbox" id="health_insurance_exist" name="health_insurance_status" value="미납액 있음">
		  <label for="health_insurance_exist">미납액 있음</label>
		  &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="health_insurance_amount" name="health_insurance_amount" data-type="money"> 원
		</div>
    </div>
	<div class="form">
      <div class="form-title"></div>
      <div class="form-content">
      </div>
    </div>
  </div>
  <div class="right-section">
    <div class="form">
      <div class="form-title"><span>국민연금</span></div>
      <div class="form-content">
        <input type="checkbox" id="pension_tax_none" name="pension_tax_status" value="미납액 없음">
        <label for="pension_tax_none">미납액 없음</label>
        <input type="checkbox" id="pension_tax_exist" name="pension_tax_status" value="미납액 있음">
        <label for="pension_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="pension_tax_amount" name="pension_tax_amount" data-type="money"> 원
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>자동차세</span></div>
      <div class="form-content">
        <input type="checkbox" id="car_tax_none" name="car_tax_status" value="미납액 없음">
        <label for="car_tax_none">미납액 없음</label>
        <input type="checkbox" id="car_tax_exist" name="car_tax_status" value="미납액 있음">
        <label for="car_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="car_tax_amount" name="car_tax_amount" data-type="money"> 원
      </div>
    </div>
    <div class="form">
      <div class="form-title"><span>기타세금</span></div>
      <div class="form-content">
        <input type="checkbox" id="other_tax_none" name="other_tax_status" value="미납액 없음">
        <label for="other_tax_none">미납액 없음</label>
        <input type="checkbox" id="other_tax_exist" name="other_tax_status" value="미납액 있음">
        <label for="other_tax_exist">미납액 있음</label>
        &nbsp;&nbsp;미납액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="other_tax_amount" name="other_tax_amount" data-type="money"> 원
      </div>
    </div>
	<div class="form">
      <div class="form-title form-notitle"></div>
      <div class="form-content form-nocontent">
		※ 미납분 없음으로 체크된 경우 미납액을 입력하실 수 없습니다.
      </div>
    </div>
    <div class="form">
      <div class="form-title"></div>
      <div class="form-content btn-right">
        <button type="button" id="save_living_status_tax" class="btn-save-living">저장</button>
      </div>
    </div>
  </div>
</div>

<!-- 가족 구성원 템플릿 (JavaScript에서 사용) -->
<div id="family_member_template" style="display:none;">
  <div class="asset-block family-member-block" id="family_member_{id}">
    <input type="hidden" class="family_member_id" value="{id}">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title"><span>성명</span></div>
          <div class="form-content">
            <input type="text" class="family_name" name="family_name_{id}">
          </div>
        </div>
        <div class="form">
          <div class="form-title"><span>관계</span></div>
          <div class="form-content">
            <input type="text" class="family_relation" name="family_relation_{id}">
          </div>
        </div>
        <div class="form">
          <div class="form-title"><span>연령</span></div>
          <div class="form-content">
            <input type="text" class="family_age" name="family_age_{id}">세
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title"><span>직업</span></div>
          <div class="form-content">
            <input type="text" class="family_job" name="family_job_{id}">
          </div>
        </div>
        <div class="form">
          <div class="form-title"><span>월수입</span></div>
          <div class="form-content">
            <input type="text" class="family_income" name="family_income_{id}" data-type="money"> 원
          </div>
        </div>
        <div class="form">
          <div class="form-title"></div>
          <div class="form-content btn-right">
            <button type="button" class="btn-delete family_delete_btn">삭제</button>
            <button type="button" class="btn-save-living family_save_btn">저장</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>