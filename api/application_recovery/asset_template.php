<div style="margin: 2vh 0;">
  ※ 재산 정보의 금액이 변경된 경우 ㅣ수입지출목록 탭에서 예상생계비를 다시 계산 후 저장하셔야 합니다.
</div>

<!-- 현금 섹션 -->
<div class="asset-box" data-type="cash">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>현금</span>
      </div>
      <div class="button-group">
        <!-- 현금 섹션의 추가 버튼 -->
        <button type="button" class="btn btn-add2" id="add_cash_asset">추가</button>
      </div>
    </div>
  </div>
  <!-- 자산 블록들이 추가될 컨테이너 -->
  <div id="cash_assets_container"></div>
</div>

<!-- 예금목록 섹션 -->
<div class="asset-box" data-type="deposit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>예금목록</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_deposit_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="deposit_assets_container"></div>
</div>

<!-- 보험목록 섹션 -->
<div class="asset-box" data-type="insurance">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>보험목록</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_insurance_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="insurance_assets_container"></div>
</div>

<!-- 자동차 섹션 -->
<div class="asset-box" data-type="vehicle">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>자동차(오토바이 포함)</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_vehicle_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="vehicle_assets_container"></div>
</div>

<!-- 임차보증금 섹션 -->
<div class="asset-box" data-type="rent_deposit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>임차보증금</span>
        <button type="button" class="btn btn-add2" id="exempt_rent_criteria">소액임차인 최우선 변제금의 범위와 기준보기</button>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_rent_deposit_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="rent_deposit_assets_container"></div>
</div>

<!-- 부동산 섹션 -->
<div class="asset-box" data-type="property">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>부동산</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_real_estate_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="real_estate_assets_container"></div>
</div>

<!-- 사업용설비, 재고, 비품 등 섹션 -->
<div class="asset-box" data-type="business_equipment">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>사업용설비, 재고, 비품 등</span>
        <button type="button" class="btn btn-add2" id="table_btn_business_equipment">시설비품목록표</button>
      </div>
    </div>
  </div>
  <div id="business_equipment_assets_container"></div>
</div>

<!-- 대여금채권 섹션 -->
<div class="asset-box" data-type="loan_receivables">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>대여금채권</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_loan_receivables_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="loan_receivables_assets_container"></div>
</div>

<!-- 매출금채권 섹션 -->
<div class="asset-box" data-type="sales_receivables">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>매출금채권</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_sales_receivables_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="sales_receivables_assets_container"></div>
</div>

<!-- 예상퇴직금 섹션 -->
<div class="asset-box" data-type="severance_pay">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>예상퇴직금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_severance_pay_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="severance_pay_assets_container"></div>
</div>

<!-- (가)압류 적립금 섹션 -->
<div class="asset-box" data-type="seizure_deposit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>(가)압류 적립금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_seizure_deposit_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="seizure_deposit_assets_container"></div>
</div>

<!-- 공탁금 섹션 -->
<div class="asset-box" data-type="seizure_reserve">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>공탁금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_seizure_reserve_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="seizure_reserve_assets_container"></div>
</div>

<!-- 기타 섹션 -->
<div class="asset-box" data-type="other_assets">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>기타</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_other_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="other_assets_container"></div>
</div>

<!-- 면제재산 결정신청 - 주거용 임차보증금반환청구권 -->
<div class="asset-box" data-type="exempt_property">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>면제재산 결정신청 | 주거용 임차보증금반환청구권</span>
      </div>
    </div>
  </div>
  <div id="exempt_property_assets_container"></div>
</div>

<!-- 면제재산 결정신청 - 6개월간 생계비 -->
<div class="asset-box" data-type="exempt_property_special">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>면제재산 결정신청 | 6개월간 생계비에 사용할 특정재산</span>
      </div>
    </div>
  </div>
  <div id="exempt_property_special_assets_container"></div>
</div>