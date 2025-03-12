<<<<<<< HEAD
<div style="margin: 2vh 0;">※ 재산 정보의 금액이 변경된 경우 ㅣ수입지출목록 탭에서 예상생계비를 다시 계산 후 저장하셔야 합니다.</div>

<!-- 현금 섹션 -->
<div class="asset-box" data-type="cash">
    <div class="section-header">현금</div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>재산 세부 상황</span></div>
                <div class="form-content">
                    <input type="text" name="property_detail" id="cash_property_detail">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="cash_liquidation_value" id="cash_liquidation_value" class="">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="cash_seizure_yes" name="cash_seizure" value="Y">
                        <label for="cash_seizure_yes">유</label>
                        <input type="radio" id="cash_seizure_no" name="cash_seizure" value="N">
                        <label for="cash_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span></span></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-add" id="cash_btn_add">추가</button>
                    <button type="button" class="btn-delete" id="cash_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="cash_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 예금목록 섹션 -->
<div class="asset-box" data-type="deposit">
    <div class="section-header">예금목록</div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>은행명</span></div>
                <div class="form-content">
                    <input type="text" name="bank_name" id="deposit_bank_name" placeholder="신한은행 외 n개">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>계좌번호</span></div>
                <div class="form-content">
                    <input type="text" name="account_number" id="deposit_account_number" placeholder="별지 참조">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>예치 금액</span></div>
                <div class="form-content">
                    <input type="text" name="deposit_amount" id="deposit_amount" placeholder="총 합계액">원
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"><span>공제 금액</span></div>
                <div class="form-content">
                    <input type="text" name="deduction_amount" id="deposit_deduction_amount">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content">
                    단, 공제 금액란에는 185만원을 초과한 금액을 기재할 수 없습니다.<br>
                    민사집행법 시행령 제7조(압류금지 예금동의 범위)참조
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title">
                    <span>압류 유무</span>
                </div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="deposit_seizure_yes" name="deposit_seizure" value="Y">
                        <label for="deposit_seizure_yes">유</label>
                        <input type="radio" id="deposit_seizure_no" name="deposit_seizure" value="N">
                        <label for="deposit_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-title-2">
                    <span>주의사항</span>
                </div>
                <div class="form-content form-content-2">
                    • 계산값이 음수인 경우 예금의 청산가치는 "0"원입니다.<br>
                    • 별지다운로드 후 계좌목록을 먼저 작성해 주십시오.<br>
                    • 작성한 별지 파일은 보험조회내역 파일 다음 순서로 제출해 주십시오.
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
                <div class="form-title">
                    <span></span>
                </div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="deposit_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="deposit_btn_save">저장</button>
                    <button type="button" class="btn btn-long btn-download" id="deposit_btn_download">별지다운로드</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 보험목록 섹션 -->
<div class="asset-box" data-type="insurance">
    <div class="section-header">보험목록</div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>보장성 보험여부</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="insurance_coverage_yes" name="insurance_coverage" value="Y">
                        <label for="insurance_coverage_yes">네</label>
                        <input type="radio" id="insurance_coverage_no" name="insurance_coverage" value="N">
                        <label for="insurance_coverage_no">아니요</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>보험사</span></div>
                <div class="form-content">
                    <input type="text" name="insurance_name" id="insurance_company_name" placeholder="삼성화재 외 n개">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>증권번호</span></div>
                <div class="form-content">
                    <input type="text" name="securities_number" id="insurance_securities_number" placeholder="별지 참조">
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"><span>예상 환급 금액</span></div>
                <div class="form-content">
                    <input type="text" name="expected_refund_amount" id="insurance_refund_amount" placeholder="총 합계액">원
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"><span></span></div>
                <div class="form-content">
                    민사집행법 시행령 제6조(압류금지 보장성 보험금등의 범위) 참조
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span></span></div>
                <div class="form-content">
                    부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="insurance_explain" id="insurance_explanation"> 
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title">
                    <span>압류 유무</span>
                </div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="insurance_seizure_yes" name="insurance_seizure" value="Y">
                        <label for="insurance_seizure_yes">유</label>
                        <input type="radio" id="insurance_seizure_no" name="insurance_seizure" value="N">
                        <label for="insurance_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-title-3">
                    <span>주의사항</span>
                </div>
                <div class="form-content form-content-3">
                    보험의 청산가치는 체크된 보장성보험 각각의 예상환급금을 합산한 금액에서<br>
                    압류금지보장성보험금 150만원을 공제한 값을 청산가치 합계란에 자동반영합니다.<br>
                    • 계산값이 음수인 경우 청산가치는 "0"원입니다. 보장성 보험이 아닌 경우에는 공제항목에서 제외됩니다.<br>
                    • 별지다운로드 후 계좌목록을 먼저 작성해 주십시오.<br>
                    • 작성한 별지 파일은 보험조회내역 파일 다음 순서로 제출해 주십시오.
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
                    <button type="button" class="btn-delete" id="insurance_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="insurance_btn_save">저장</button>
                    <button type="button" class="btn btn-long btn-download" id="insurance_btn_download">별지다운로드</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 자동차 섹션 -->
<div class="asset-box" data-type="vehicle">
    <div class="section-header">자동차(오토바이 포함)</div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>차량 정보</span></div>
                <div class="form-content">
                    <input type="text" name="vehicle_info" id="vehicle_info" class="form-control form-content-long" placeholder="차량번호, 연식, 모델(예:123가4567, 2020년형, 아반떼)">
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="vehicle_spouse_owned" name="vehicle_spouse_owned">
                    <label for="vehicle_spouse_owned">배우자명의</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>담보권 종류</span></div>
                <div class="form-content">
                    <input type="text" name="security_type" id="vehicle_security_type">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>채권(최고)액</span></div>
                <div class="form-content">
                    <input type="text" name="max_bond_amount" id="vehicle_max_bond">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>환가 예상액</span></div>
                <div class="form-content">
                    <input type="text" name="expected_value" id="vehicle_expected_value">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>재무 잔액</span></div>
                <div class="form-content">
                    <input type="text" name="financial_balance" id="vehicle_financial_balance">
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="vehicle_liquidation_value" id="vehicle_liquidation_value" class="form-control form-content-justify">원
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="vehicle_manual_calc" name="vehicle_manual_calc">
                    <label for="vehicle_manual_calc">수동계산</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span></span></div>
                <div class="form-content">
                    부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="liquidation_explain" id="vehicle_liquidation_explain">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="vehicle_seizure_yes" name="vehicle_seizure" value="Y">
                        <label for="vehicle_seizure_yes">유</label>
                        <input type="radio" id="vehicle_seizure_no" name="vehicle_seizure" value="N">
                        <label for="vehicle_seizure_no">무</label>
                    </div>
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
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="vehicle_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="vehicle_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 전세/월세 섹션 -->
<div class="asset-box" data-type="rent_deposit">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>전세/월세</span>
                <button type="button" class="btn btn-add2" id="rent_btn_view_priority">소액임차인 최우선 변제금의 범위와 기준보기</button>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="rent_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>임차지</span></div>
                <div class="form-content">
                    <input type="text" name="lease_location" id="rent_lease_location" class="form-control form-content-long">
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="rent_business_place" name="rent_business_place">
                    <label for="rent_business_place">영업장</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>계약상 보증금</span></div>
                <div class="form-content">
                    <input type="text" name="contract_deposit" id="rent_contract_deposit" class="form-control form-content-justify">원
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="rent_deposit_spouse" name="rent_deposit_spouse">
                    <label for="rent_deposit_spouse">배우자명의</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>월세</span></div>
                <div class="form-content">
                    <input type="text" name="monthly_rent" id="rent_monthly_amount" class="form-control form-content-justify">원
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="rent_monthly_spouse" name="rent_monthly_spouse">
                    <label for="rent_monthly_spouse">배우자명의</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>받환받을 보증금</span></div>
                <div class="form-content">
                    <input type="text" name="refund_deposit" id="rent_refund_deposit">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>차이나는 이유</span></div>
                <div class="form-content">
                    <input type="text" name="difference_reason" id="rent_difference_reason" class="form-control form-content-long" placeholder="계약상보증금과 반환받을 금액이 차이 나는 경우 작성해 주십시오.">
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>압류할 수 없는<br>최우선 변제 보증금</span></div>
                <div class="form-content">
                    <input type="text" name="priority_deposit" id="rent_priority_deposit">원 제외
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="rent_liquidation_value">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span></span></div>
                <div class="form-content">
                    부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="liquidation_explain" id="rent_liquidation_explain">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="rent_seizure_yes" name="rent_seizure" value="Y">
                        <label for="rent_seizure_yes">유</label>
                        <input type="radio" id="rent_seizure_no" name="rent_seizure" value="N">
                        <label for="rent_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="rent_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="rent_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 부동산 섹션 -->
<div class="asset-box" data-type="property">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>부동산</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="property_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>권리 및 부동산 종류</span></div>
                <div class="form-content">
                    <div class="form-group">
                        <select class="form-select" id="property_right_type" name="propertyRightType">
                            <option value="소유권" selected>소유권</option>
                            <option value="지분권">지분권</option>
                        </select>
                        <select class="form-select" id="property_type" name="propertyType">
                            <option value="토지" selected>토지</option>
                            <option value="건물">건물</option>
                            <option value="집합건물">집합건물</option>
                            <option value="토지, 건물">토지, 건물</option>
                        </select>
                        <div class="form-content checkbox-right">
                            면적&nbsp;&nbsp;<input type="text" name="property_area" id="property_area" class="form-control form-content-short">㎡
                        </div>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>소재지</span></div>
                <div class="form-content">
                    <input type="text" name="property_location" id="property_location" class="">
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="property_spouse_owned" name="property_spouse_owned">
                    <label for="property_spouse_owned">배우자명의</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>환가예상액</span></div>
                <div class="form-content">
                    <input type="text" name="expected_value" id="property_expected_value" class="">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>담보권 종류</span></div>
                <div class="form-content">
                    <div class="form-group">
                        <select class="form-select" id="property_security_type" name="securityRightType">
                            <option value="근저당권" selected>근저당권</option>
                            <option value="전세(임차)권">전세(임차)권</option>
                            <option value="근저당권, 전세(임차)권">근저당권, 전세(임차)권</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>담보권 내용</span></div>
                <div class="form-content">
                    <input type="text" name="security_details" id="property_security_details" class="">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>피담보 채무액</span></div>
                <div class="form-content">
                    <input type="text" name="secured_debt" id="property_secured_debt" class="">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>보증금 채무액</span></div>
                <div class="form-content">
                    <input type="text" name="deposit_debt" id="property_deposit_debt" class="">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="property_liquidation_value">원
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle">
                    <span></span>
                </div>
                <div class="form-content">
                    ※ 별제권부채권의 목적물인 경우 채권자 목록을 반드시 먼저 작성해야 합니다.
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span></span>
                </div>
                <div class="form-content">
                    부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="liquidation_explain" id="property_liquidation_explain">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="property_seizure_yes" name="property_seizure" value="Y">
                        <label for="property_seizure_yes">유</label>
                        <input type="radio" id="property_seizure_no" name="property_seizure" value="N">
                        <label for="property_seizure_no">무</label>
                    </div>
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
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="property_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="property_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 사업용설비, 재고, 비품 등 섹션 -->
<div class="asset-box" data-type="business_equipment">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>사업용설비, 재고, 비품 등</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="equipment_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>품목</span></div>
                <div class="form-content">
                    <input type="text" name="item_name" id="equipment_item_name" class="form-control form-content-long" placeholder="별지 참조">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>수량</span></div>
                <div class="form-content">
                    <input type="number" name="quantity" id="equipment_quantity" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>구입시기</span></div>
                <div class="form-content">
                    <input type="text" name="purchase_date" id="equipment_purchase_date" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>중고시세</span></div>
                <div class="form-content">
                    <input type="text" name="used_price" id="equipment_used_price" class="form-control">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="equipment_liquidation_value" placeholder="평가 총액">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="equipment_seizure_yes" name="equipment_seizure" value="Y">
                        <label for="equipment_seizure_yes">유</label>
                        <input type="radio" id="equipment_seizure_no" name="equipment_seizure" value="N">
                        <label for="equipment_seizure_no">무</label>
                    </div>
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
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-download" id="equipment_btn_download">별지다운로드</button>
                    <button type="button" class="btn-save" id="equipment_btn_save">저장</button>
                    <button type="button" class="btn-delete" id="equipment_btn_delete">삭제</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 대여금채권 섹션 -->
<div class="asset-box" data-type="loan_receivables">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>대여금채권</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="loan_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>상대방(채무자)</span></div>
                <div class="form-content">
                    <input type="text" name="debtor_name" id="loan_debtor_name" class="form-control form-content-long">
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="loan_evidence_attached" name="loan_evidence_attached">
                    <label for="loan_evidence_attached">소명자료별첨</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="loan_liquidation_value" class="form-control">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="loan_seizure_yes" name="loan_seizure" value="Y">
                        <label for="loan_seizure_yes">유</label>
                        <input type="radio" id="loan_seizure_no" name="loan_seizure" value="N">
                        <label for="loan_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="loan_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="loan_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 예상퇴직금 섹션 -->
<div class="asset-box" data-type="severance_pay">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>예상퇴직금</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="severance_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>공무원</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="severance_public_yes" name="severance_is_public" value="Y">
                        <label for="severance_public_yes">네</label>
                        <input type="radio" id="severance_public_no" name="severance_is_public" value="N">
                        <label for="severance_public_no">아니오</label>
                    </div>
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="severance_pension" name="severance_pension">
                    <label for="severance_pension">퇴직연금가입사업장</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>근무지</span></div>
                <div class="form-content">
                    <input type="text" name="workplace" id="severance_workplace" class="form-control form-content-long">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>예상퇴직금</span></div>
                <div class="form-content">
                    <input type="text" name="expected_severance" id="severance_expected_amount">원
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
                <div class="form-content form-nocontent">
                    <input type="text" name="deduction_amount" id="severance_deduction_amount" placeholder="압류할 수 없는 퇴직금">원 제외
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="severance_liquidation_value" placeholder="청산가치">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="severance_seizure_yes" name="severance_seizure" value="Y">
                        <label for="severance_seizure_yes">유</label>
                        <input type="radio" id="severance_seizure_no" name="severance_seizure" value="N">
                        <label for="severance_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-title-3">
                    <span>주의사항</span>
                </div>
                <div class="form-content form-content-3">
                    • 일반 직장의 경우 퇴직금의 1/2은 청산가치에 반영하여야 합니다.<br>
                    • 퇴직연금에 가입 한 직장의 경우 압류금지채권은 청산가치에 반영하지 않습니다.<br>
                    &nbsp;&nbsp;&nbsp;민사집행법 제246조 제1항 참조<br>
                    • 공무원, 군인, 사립교직원 등의 연금은 압류금지채권으로 청산가치에 반영하지 않습니다.
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="severance_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="severance_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- (가)압류 적립금 섹션 -->
<div class="asset-box" data-type="seizure_deposit">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>(가)압류 적립금</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="seizure_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>(가)압류 내용</span></div>
                <div class="form-content">
                    <input type="text" name="seizure_content" id="seizure_content_desc" class="form-control form-content-long">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>보관자(회사 등)</span></div>
                <div class="form-content">
                    <input type="text" name="custodian" id="seizure_custodian" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="liquidation_value" id="seizure_liquidation_value" class="form-control form-content-justify">원
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="seizure_exclude_liquidation" name="seizure_exclude_liquidation">
                    <label for="seizure_exclude_liquidation">청산가치에서 제외</label>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>변제투입 유무</span></div>
                <div class="form-content">
                    <input type="checkbox" id="seizure_repayment_input" name="seizure_repayment_input">
                    <label for="seizure_repayment_input">가용소득 1회 투입</label>
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
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-save" id="seizure_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 공탁금 섹션 -->
<div class="asset-box" data-type="deposit_money">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group"><span>공탁금</span></div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="deposit_money_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>(가)압류 내용</span></div>
                <div class="form-content">
                    <input type="text" name="seizure_content" id="deposit_money_seizure_content" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>보관자(공탁된 법원)</span></div>
                <div class="form-content">
                    <input type="text" name="court_name" id="deposit_money_court_name" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="deposit_liquidation_value" id="deposit_money_liquidation_value" class="form-control form-content-justify">원
                </div>
                <div class="form-content checkbox-right">
                    <input type="checkbox" id="deposit_money_exclude" name="deposit_money_exclude">
                    <label for="deposit_money_exclude">청산가치에서 제외</label>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>변제투입 유무</span></div>
                <div class="form-content">
                    <input type="checkbox" id="deposit_money_repayment" name="deposit_money_repayment">
                    <label for="deposit_money_repayment">가용소득 1회 투입</label>
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
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-save" id="deposit_money_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 기타 섹션 -->
<div class="asset-box" data-type="other_assets">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group"><span>기타</span></div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="other_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>재산 내용</span></div>
                <div class="form-content">
                    <input type="text" name="asset_content" id="other_asset_content" class="form-control">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>청산가치 판단금액</span></div>
                <div class="form-content">
                    <input type="text" name="other_liquidation_value" id="other_liquidation_value">원
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>압류 유무</span></div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="other_seizure_yes" name="other_seizure" value="Y">
                        <label for="other_seizure_yes">유</label>
                        <input type="radio" id="other_seizure_no" name="other_seizure" value="N">
                        <label for="other_seizure_no">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="other_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="other_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 면제재산 결정신청 - 주거용 임차보증금반환청구권 -->
<div class="asset-box" data-type="exempt_property">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>면제재산 결정신청 | 주거용 임차보증금반환청구권</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="exempt_lease_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>첨부할 소명자료</span></div>
                <div class="form-content">
                    <div class="checkbox-group">
                        <input type="checkbox" id="exempt_lease_contract" name="exempt_lease_contract">
                        <label for="exempt_lease_contract">임대차계약서</label>
                        <input type="checkbox" id="exempt_resident_registration" name="exempt_resident_registration">
                        <label for="exempt_resident_registration">주민등록등본</label>
                        <input type="checkbox" id="exempt_other_evidence" name="exempt_other_evidence">
                        <label for="exempt_other_evidence">기타</label> |
                        <input type="text" name="other_evidence_detail" id="exempt_other_evidence_detail">통
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>임차한 소재지</span></div>
                <div class="form-content">
                    <input type="text" name="lease_location" id="exempt_lease_location">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>계약일자</span></div>
                <div class="form-content">
                    <input type="date" name="contract_date" id="exempt_contract_date">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>임대차 기간</span></div>
                <div class="form-content">
                    <input type="date" name="lease_start_date" id="exempt_lease_start_date"> 부터
                    <input type="date" name="lease_end_date" id="exempt_lease_end_date"> 까지
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>확정일자</span></div>
                <div class="form-content">
                    <input type="checkbox" id="exempt_fixed_date_yes" name="exempt_fixed_date">
                    <label for="exempt_fixed_date_yes">유</label>
                    <input type="checkbox" id="exempt_fixed_date_no" name="exempt_fixed_date">
                    <label for="exempt_fixed_date_no">무</label>
                    <input type="date" name="fixed_date" id="exempt_fixed_date"> 까지
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>주민등록일자</span></div>
                <div class="form-content">
                    <input type="date" name="registration_date" id="exempt_registration_date">
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>임차보증금</span></div>
                <div class="form-content">
                    <input type="text" name="lease_deposit" id="exempt_lease_deposit">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>임료</span></div>
                <div class="form-content">
                    <input type="text" name="rent_fee" id="exempt_rent_fee">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>연체기간</span></div>
                <div class="form-content">
                    <input type="text" name="overdue_months" id="exempt_overdue_months">개월
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>임대인 성명</span></div>
                <div class="form-content">
                    <input type="text" name="lessor_name" id="exempt_lessor_name">
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>면제신청 금액</span></div>
                <div class="form-content">
                    <input type="text" name="exemption_amount" id="exempt_amount">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="exempt_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="exempt_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 면제재산 결정신청 - 6개월간 생계비 -->
<div class="asset-box" data-type="exempt_property_special">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>면제재산 결정신청 | 6개월간 생계비에 사용할 특정재산</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="exempt_special_btn_add">추가</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title"><span>첨부할 소명자료</span></div>
                <div class="form-content">
                    <div class="evidence-group">
                        ① <input type="text" name="evidence1" id="exempt_special_evidence1"> 통
                        ② <input type="text" name="evidence2" id="exempt_special_evidence2"> 통
                        ③ <input type="text" name="evidence3" id="exempt_special_evidence3"> 통
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title"><span>특정재산의 내용</span></div>
                <div class="form-content">
                    <input type="text" name="special_property_content" id="exempt_special_content">
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title"><span>면제신청 금액</span></div>
                <div class="form-content">
                    <input type="text" name="exemption_amount" id="exempt_special_amount">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button type="button" class="btn-delete" id="exempt_special_btn_delete">삭제</button>
                    <button type="button" class="btn-save" id="exempt_special_btn_save">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>
=======
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
    </div>
  </div>
  <div id="seizure_deposit_assets_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title">
            <span>(가)압류 내용</span>
          </div>
          <div class="form-content">
            <input type="text" name="seizure_content" id="seizure_content_desc" class="form-control form-content-long">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>보관자(회사 등)</span>
          </div>
          <div class="form-content">
            <input type="text" name="custodian" id="seizure_custodian" class="form-control">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>청산가치 판단금액</span>
          </div>
          <div class="form-content">
            <input type="text" name="liquidation_value" id="seizure_liquidation_value" class="form-control form-content-justify">원
          </div>
          <div class="form-content checkbox-right">
            <input type="checkbox" id="seizure_exclude_liquidation" name="seizure_exclude_liquidation">
            <label for="seizure_exclude_liquidation">청산가치에서 제외</label>
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title">
            <span>변제투입 유무</span>
          </div>
          <div class="form-content">
            <input type="checkbox" id="seizure_repayment_input" name="seizure_repayment_input">
            <label for="seizure_repayment_input">가용소득 1회 투입</label>
          </div>
        </div>
        <div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent"></div>
        </div>
        <div class="form">
          <div class="form-title"></div>
          <div class="form-content btn-right">
            <button type="button" class="btn-save" id="seizure_btn_save">저장</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 공탁금 섹션 -->
<div class="asset-box" data-type="deposit_money">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>공탁금</span>
      </div>
    </div>
  </div>
  <div id="deposit_money_assets_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title">
            <span>(가)압류 내용</span>
          </div>
          <div class="form-content">
            <input type="text" name="seizure_content" id="deposit_money_seizure_content" class="form-control">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>보관자(공탁된 법원)</span>
          </div>
          <div class="form-content">
            <input type="text" name="court_name" id="deposit_money_court_name" class="form-control">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>청산가치 판단금액</span>
          </div>
          <div class="form-content">
            <input type="text" name="deposit_liquidation_value" id="deposit_money_liquidation_value" class="form-control form-content-justify">원
          </div>
          <div class="form-content checkbox-right">
            <input type="checkbox" id="deposit_money_exclude" name="deposit_money_exclude">
            <label for="deposit_money_exclude">청산가치에서 제외</label>
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title">
            <span>변제투입 유무</span>
          </div>
          <div class="form-content">
            <input type="checkbox" id="deposit_money_repayment" name="deposit_money_repayment">
            <label for="deposit_money_repayment">가용소득 1회 투입</label>
          </div>
        </div>
        <div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent"></div>
        </div>
        <div class="form">
          <div class="form-title"></div>
          <div class="form-content btn-right">
            <button type="button" class="btn-save" id="deposit_money_btn_save">저장</button>
          </div>
        </div>
      </div>
    </div>
  </div>
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
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_exempt_property_asset">추가</button>
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
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_exempt_property_special_asset">추가</button>
      </div>
    </div>
  </div>
  <div id="exempt_property_special_assets_container"></div>
</div>
>>>>>>> 719d7c8 (Delete all files)
