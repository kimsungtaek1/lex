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
        <button type="button" class="btn btn-add2" id="add_cash_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="cash_assets_container"></div>
</div>

<!-- 예금 섹션 -->
<div class="asset-box" data-type="deposit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>예금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_deposit_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="deposit_assets_container"></div>
</div>

<!-- 보험 섹션 -->
<div class="asset-box" data-type="insurance">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>보험</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_insurance_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="insurance_assets_container"></div>
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

<!-- 대여금 등 섹션 -->
<div class="asset-box" data-type="loan_receivables">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>대여금 등</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_loan_receivables_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="loan_receivables_assets_container"></div>
</div>

<!-- 매출금 섹션 -->
<div class="asset-box" data-type="sales_receivables">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>매출금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_sales_receivables_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="sales_receivables_assets_container"></div>
</div>

<!-- 퇴직금 섹션 -->
<div class="asset-box" data-type="severance_pay">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>퇴직금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_severance_pay_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="severance_pay_assets_container"></div>
</div>

<!-- 부동산 섹션 -->
<div class="asset-box" data-type="real_estate">
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

<!-- 자동차(오토바이 포함) 섹션 -->
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

<!-- 기타 재산(주식, 회원권, 특허권, 귀금속, 미술품 등) 섹션 -->
<div class="asset-box" data-type="other_assets">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>기타 재산(주식, 회원권, 특허권, 귀금속, 미술품 등)</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_other_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="other_assets_container"></div>
</div>

<!-- 지급불가능 시점 1년 전부터 현재까지 천만원 이상 재산 처분 섹션 -->
<div class="asset-box" data-type="disposed_assets">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>지급불가능 시점 1년 전부터 현재까지 천만원 이상 재산 처분</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_disposed_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="disposed_assets_container"></div>
</div>

<!-- 2년 내 수령한 임차보증금 섹션 -->
<div class="asset-box" data-type="received_deposit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>2년 내 수령한 임차보증금</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_received_deposit_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="received_deposit_assets_container"></div>
</div>

<!-- 2년 내 이혼시 재산분할 섹션 -->
<div class="asset-box" data-type="divorce_property">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>2년 내 이혼시 재산분할</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_divorce_property_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="divorce_property_assets_container"></div>
</div>

<!-- 상속재산 섹션 -->
<div class="asset-box" data-type="inherited_property">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>상속재산</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_inherited_property_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="inherited_property_assets_container"></div>
</div>