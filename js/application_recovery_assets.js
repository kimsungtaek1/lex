class AssetManager {
  constructor() {
    // 각 섹션별 동적 블록들을 배열로 관리 (필요시 사용)
    this.currentAssets = {
      cash: [],
      deposit: [],
      insurance: [],
      vehicle: [],
      rent_deposit: [],
      real_estate: [],
      business_equipment: [],
      loan_receivables: [],
	  sales_receivables: [],
      severance_pay: [],
      other_assets: [],
      exempt_property: [],
      exempt_property_special: []
    };

    // 각 섹션마다 동적 블록의 고유 id 생성을 위한 카운터
    this.assetCounters = {
      cash: 0,
      deposit: 0,
      insurance: 0,
      vehicle: 0,
      rent_deposit: 0,
      real_estate: 0,
      business_equipment: 0,
      loan_receivables: 0,
	  sales_receivables: 0,
      severance_pay: 0,
      other_assets: 0,
      exempt_property: 0,
      exempt_property_special: 0
    };

    this.cache = new Map();
    this.initialize();
  }

  initialize() {
    try {
      this.initializeEventHandlers();
      this.loadAllAssets();
    } catch (error) {
      console.error("초기화 실패:", error);
      alert("초기화 중 오류가 발생했습니다.");
    }
  }

  initializeEventHandlers() {
    $("#add_cash_asset").on("click", () => this.addCashBlock());
    $("#add_deposit_asset").on("click", () => this.addDepositBlock());
    $("#add_insurance_asset").on("click", () => this.addInsuranceBlock());
    $("#add_vehicle_asset").on("click", () => this.addVehicleBlock());
    $("#add_rent_deposit_asset").on("click", () => this.addRentDepositBlock());
    $("#add_real_estate_asset").on("click", () => this.addRealEstateBlock());
    $("#add_business_equipment_asset").on("click", () => this.addBusinessEquipmentBlock());
    $("#add_loan_receivables_asset").on("click", () => this.addLoanReceivablesBlock());
	$("#add_sales_receivables_asset").on("click", () => this.addSalesReceivablesBlock());
    $("#add_severance_pay_asset").on("click", () => this.addSeverancePayBlock());
    $("#add_other_asset").on("click", () => this.addOtherAssetBlock());
    $("#add_exempt_property_asset").on("click", () => this.addExemptPropertyBlock());
    $("#add_exempt_property_special_asset").on("click", () => this.addExemptPropertySpecialBlock());
	
    // 소액임차인 최우선 변제금 기준 팝업 이벤트 추가
    $('#exempt_rent_criteria').on('click', function() {
        window.open('/adm/api/application_recovery/assets/exempt_rent_criteria.php', 
            '소액임차인 최우선 변제금의 범위와 기준', 
            'width=1000,height=500,scrollbars=yes');
    });
	
	$('#table_btn_business_equipment').on('click', function() {
		var caseNo = window.currentCaseNo; // 전역 변수에서 사건번호 가져오기
		var url = '/adm/api/application_recovery/assets/business_equipment_list.php?case_no=' + encodeURIComponent(caseNo);
		
		window.open(url, '시설비품목록표', 'width=1000,height=700,scrollbars=yes');
	});

	
    // 창 닫기 전 저장되지 않은 변경사항 확인
    window.addEventListener("beforeunload", (e) => {
      if (this.hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = "저장되지 않은 변경사항이 있습니다. 정말 나가시겠습니까?";
      }
    });
  }

  loadAllAssets() {
	const types = [
	  "cash",
	  "deposit",
	  "insurance",
	  "vehicle",
	  "rent_deposit",
	  "real_estate",
	  "business_equipment",
	  "loan_receivables",
	  "sales_receivables",
	  "severance_pay",
	  "other_assets",
	  "exempt_property",
	  "exempt_property_special"
	];

    types.forEach((type) => {
      this.loadAsset(type);
    });
  }

  loadAsset(type) {
    const cacheKey = `${type}_${window.currentCaseNo}`;
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey);
      if (Date.now() - cached.timestamp < 5000) {
        this.populateAssetBlocks(type, cached.data);
        return;
      }
    }
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "GET",
      data: { case_no: window.currentCaseNo, asset_type: type },
      dataType: "json",
      success: (response) => {
        if (response.success) {
          let assets = [];
          if (Array.isArray(response.data)) {
            assets = response.data;
          } else if (response.data) {
            assets = [response.data];
          }
          this.cache.set(cacheKey, { data: assets, timestamp: Date.now() });
          this.populateAssetBlocks(type, assets);
        } else {
          console.error(`${type} 자산 로드 실패:`, response.message);
        }
      },
      error: (xhr, status, error) => {
        console.error(`${type} AJAX 로드 오류:`, error);
      }
    });
  }

  populateAssetBlocks(type, assets) {
    let containerId = "";
    switch (type) {
      case "cash":
        containerId = "#cash_assets_container";
        break;
      case "deposit":
        containerId = "#deposit_assets_container";
        break;
      case "insurance":
        containerId = "#insurance_assets_container";
        break;
      case "vehicle":
        containerId = "#vehicle_assets_container";
        break;
      case "rent_deposit":
        containerId = "#rent_deposit_assets_container";
        break;
      case "real_estate":
        containerId = "#real_estate_assets_container";
        break;
      case "business_equipment":
        containerId = "#business_equipment_assets_container";
        break;
      case "loan_receivables":
        containerId = "#loan_receivables_assets_container";
        break;
	  case "sales_receivables":
		containerId = "#sales_receivables_assets_container";
		break;
      case "severance_pay":
        containerId = "#severance_pay_assets_container";
        break;
      case "other_assets":
        containerId = "#other_assets_container";
        break;
      case "exempt_property":
        containerId = "#exempt_property_assets_container";
        break;
      case "exempt_property_special":
        containerId = "#exempt_property_special_assets_container";
        break;
      default:
        console.warn("알 수 없는 자산 유형:", type);
        return;
    }
    $(containerId).empty();

    if (assets.length > 0) {
      assets.forEach((asset) => {
        this.addAssetBlock(type, asset);
      });
    } else {
      // 데이터가 없으면 빈 블록을 하나 생성합니다.
      this.addAssetBlock(type);
    }
  }

  // 각 섹션별 addAssetBlock() 함수
  addAssetBlock(type, data = {}) {
    switch (type) {
      case "cash":
        this.addCashBlock(data);
        break;
      case "deposit":
        this.addDepositBlock(data);
        break;
      case "insurance":
        this.addInsuranceBlock(data);
        break;
      case "vehicle":
        this.addVehicleBlock(data);
        break;
      case "rent_deposit":
        this.addRentDepositBlock(data);
        break;
      case "real_estate":
        this.addRealEstateBlock(data);
        break;
      case "business_equipment":
        this.addBusinessEquipmentBlock(data);
        break;
      case "loan_receivables":
        this.addLoanReceivablesBlock(data);
        break;
      case "sales_receivables":
        this.addSalesReceivablesBlock(data);
        break;
      case "severance_pay":
        this.addSeverancePayBlock(data);
        break;
      case "other_assets":
        this.addOtherAssetBlock(data);
        break;
      case "exempt_property":
        this.addExemptPropertyBlock(data);
        break;
      case "exempt_property_special":
        this.addExemptPropertySpecialBlock(data);
        break;
      default:
        console.warn("알 수 없는 자산 유형:", type);
        break;
    }
  }

  /* =========================================
     섹션별 동적 블록 추가/저장/삭제 함수들
     ========================================= */

  // 1. 현금 섹션
  addCashBlock(data = {}) {
    this.assetCounters.cash++;
    const blockId = "cash_block_" + this.assetCounters.cash;
    const propertyNo = data.property_no || this.assetCounters.cash;
    const html = `
      <div class="asset-block cash-block" id="${blockId}">
        <input type="hidden" class="cash_asset_no" value="${data.asset_no || ''}">
        <input type="hidden" class="cash_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>재산 세부 상황</span></div>
              <div class="form-content">
                <input type="text" class="cash_property_detail" value="${data.property_detail || ''}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="cash_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ''}">원
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_cash_seizure_yes" name="cash_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_cash_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_cash_seizure_no" name="cash_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_cash_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete cash_delete_btn">삭제</button>
                <button type="button" class="btn-save cash_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#cash_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".cash_liquidation_value").on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".cash_save_btn").on("click", () => this.saveCashBlock(block));
    block.find(".cash_delete_btn").on("click", () => this.deleteCashBlock(block));
  }

  saveCashBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".cash_asset_no").val();
    const data = {
      asset_type: "cash",
      case_no: caseNo,
      property_detail: block.find(".cash_property_detail").val().trim(),
      liquidation_value: this.unformatMoney(block.find(".cash_liquidation_value").val()),
      is_seized: block.find(`input[name="cash_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".cash_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("현금 자산이 저장되었습니다.");
          block.find(".cash_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "현금 자산 저장 실패");
        }
      },
      error: () => {
        alert("현금 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteCashBlock(block) {
    // 저장되지 않은 블록인 경우 바로 삭제하고 빈 블록 생성
    if (!block.find(".cash_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("cash");
      return;
    }
    if (!confirm("현금 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".cash_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "cash", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("현금 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("cash");
        } else {
          alert(response.message || "현금 자산 삭제 실패");
        }
      },
      error: () => {
        alert("현금 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 2. 예금 섹션
  addDepositBlock(data = {}) {
    this.assetCounters.deposit++;
    const blockId = "deposit_block_" + this.assetCounters.deposit;
    const propertyNo = data.property_no || this.assetCounters.deposit;
    const html = `
      <div class="asset-block deposit-block" id="${blockId}">
        <input type="hidden" class="deposit_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="deposit_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>은행명</span></div>
              <div class="form-content">
                <input type="text" class="deposit_bank_name" value="${data.bank_name || ""}" placeholder="신한은행 외 n개">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>계좌번호</span></div>
              <div class="form-content">
                <input type="text" class="deposit_account_number" value="${data.account_number || ""}" placeholder="별지 참조">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>예치 금액</span></div>
              <div class="form-content">
                <input type="text" class="deposit_amount" value="${data.deposit_amount ? this.formatMoney(data.deposit_amount) : ""}" placeholder="총 합계액">원
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span>공제 금액</span></div>
              <div class="form-content">
                <input type="text" class="deposit_deduction_amount" value="${data.deduction_amount ? this.formatMoney(data.deduction_amount) : ""}">원
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
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_deposit_seizure_yes" name="deposit_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_deposit_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_deposit_seizure_no" name="deposit_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_deposit_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title form-title-2"><span>주의사항</span></div>
              <div class="form-content form-content-2">
                • 계산값이 음수인 경우 예금의 청산가치는 "0"원입니다.<br>
                • 별지다운로드 후 계좌목록을 먼저 작성해 주십시오.<br>
                • 작성한 별지 파일은 보험조회내역 파일 다음 순서로 제출해 주십시오.
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span></span></div>
              <div class="form-content form-nocontent"></div>
            </div>
            <div class="form">
              <div class="form-title"><span></span></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete deposit_delete_btn">삭제</button>
                <button type="button" class="btn-save deposit_save_btn">저장</button>
                <button type="button" class="btn btn-long btn-download" id="deposit_btn_download">별지다운로드</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#deposit_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".deposit_amount, .deposit_deduction_amount").on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".deposit_save_btn").on("click", () => this.saveDepositBlock(block));
    block.find(".deposit_delete_btn").on("click", () => this.deleteDepositBlock(block));
  }

  saveDepositBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".deposit_asset_no").val();
    const data = {
      asset_type: "deposit",
      case_no: caseNo,
      bank_name: block.find(".deposit_bank_name").val().trim(),
      account_number: block.find(".deposit_account_number").val().trim(),
      deposit_amount: this.unformatMoney(block.find(".deposit_amount").val()),
      deduction_amount: this.unformatMoney(block.find(".deposit_deduction_amount").val()),
      is_seized: block.find(`input[name="deposit_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".deposit_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("예금 자산이 저장되었습니다.");
          block.find(".deposit_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "예금 자산 저장 실패");
        }
      },
      error: () => {
        alert("예금 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteDepositBlock(block) {
    // 저장되지 않은 블록은 바로 삭제 후 빈 블록 생성
    if (!block.find(".deposit_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("deposit");
      return;
    }
    if (!confirm("예금 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".deposit_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "deposit", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("예금 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("deposit");
        } else {
          alert(response.message || "예금 자산 삭제 실패");
        }
      },
      error: () => {
        alert("예금 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 3. 보험 섹션
  addInsuranceBlock(data = {}) {
    this.assetCounters.insurance++;
    const blockId = "insurance_block_" + this.assetCounters.insurance;
    const propertyNo = data.property_no || this.assetCounters.insurance;
    const html = `
      <div class="asset-block insurance-block" id="${blockId}">
        <input type="hidden" class="insurance_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="insurance_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>보장성 보험여부</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_insurance_coverage_yes" name="insurance_coverage_${blockId}" value="Y" ${data.hasOwnProperty("is_coverage") && data.is_coverage==="Y" ? "checked" : ""}>
                  <label for="${blockId}_insurance_coverage_yes">네</label>
                  <input type="radio" id="${blockId}_insurance_coverage_no" name="insurance_coverage_${blockId}" value="N" ${data.hasOwnProperty("is_coverage") && data.is_coverage==="N" ? "checked" : ""}>
                  <label for="${blockId}_insurance_coverage_no">아니요</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>보험사</span></div>
              <div class="form-content">
                <input type="text" class="insurance_company_name" value="${data.company_name || ""}" placeholder="삼성화재 외 n개">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>증권번호</span></div>
              <div class="form-content">
                <input type="text" class="insurance_securities_number" value="${data.securities_number || ""}" placeholder="별지 참조">
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span>예상 환급 금액</span></div>
              <div class="form-content">
                <input type="text" class="insurance_refund_amount" value="${data.refund_amount ? this.formatMoney(data.refund_amount) : ""}" placeholder="총 합계액">원
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
                부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="insurance_explanation" value="${data.explanation || ""}">
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_insurance_seizure_yes" name="insurance_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_insurance_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_insurance_seizure_no" name="insurance_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_insurance_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title form-title-3"><span>주의사항</span></div>
              <div class="form-content form-content-3">
                보험의 청산가치는 체크된 보장성보험 각각의 예상환급금을 합산한 금액에서<br>
                압류금지보장성보험금 150만원을 공제한 값을 청산가치 합계란에 자동반영합니다.<br>
                • 계산값이 음수인 경우 청산가치는 "0"원입니다. 보장성 보험이 아닌 경우에는 공제항목에서 제외됩니다.<br>
                • 별지다운로드 후 계좌목록을 먼저 작성해 주십시오.<br>
                • 작성한 별지 파일은 보험조회내역 파일 다음 순서로 제출해 주십시오.
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span></span></div>
              <div class="form-content form-nocontent"></div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span></span></div>
              <div class="form-content form-nocontent btn-right">
                <button type="button" class="btn-delete insurance_delete_btn">삭제</button>
                <button type="button" class="btn-save insurance_save_btn">저장</button>
                <button type="button" class="btn btn-long btn-download" id="insurance_btn_download">별지다운로드</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#insurance_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".insurance_refund_amount").on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".insurance_save_btn").on("click", () => this.saveInsuranceBlock(block));
    block.find(".insurance_delete_btn").on("click", () => this.deleteInsuranceBlock(block));
  }

  saveInsuranceBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".insurance_asset_no").val();
    const data = {
      asset_type: "insurance",
      case_no: caseNo,
      company_name: block.find(".insurance_company_name").val().trim(),
      securities_number: block.find(".insurance_securities_number").val().trim(),
      refund_amount: this.unformatMoney(block.find(".insurance_refund_amount").val()),
      is_coverage: block.find(`input[name="insurance_coverage_${block.attr("id")}"]:checked`).val() || "N",
      explanation: block.find(".insurance_explanation").val().trim(),
      is_seized: block.find(`input[name="insurance_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".insurance_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("보험 자산이 저장되었습니다.");
          block.find(".insurance_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "보험 자산 저장 실패");
        }
      },
      error: () => {
        alert("보험 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteInsuranceBlock(block) {
    // 저장되지 않은 블록은 바로 삭제하고 빈 블록 추가
    if (!block.find(".insurance_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("insurance");
      return;
    }
    if (!confirm("보험 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".insurance_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "insurance", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("보험 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("insurance");
        } else {
          alert(response.message || "보험 자산 삭제 실패");
        }
      },
      error: () => {
        alert("보험 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 4. 자동차 섹션
  addVehicleBlock(data = {}) {
    this.assetCounters.vehicle++;
    const blockId = "vehicle_block_" + this.assetCounters.vehicle;
    const propertyNo = data.property_no || this.assetCounters.vehicle;
    const html = `
      <div class="asset-block vehicle-block" id="${blockId}">
        <input type="hidden" class="vehicle_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="vehicle_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>차량 정보</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_info input100" value="${data.vehicle_info || ""}" placeholder="차량번호, 연식, 모델(예:123가4567, 2020년형, 아반떼)">
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_vehicle_spouse_owned" class="vehicle_spouse_owned" ${data.is_spouse == 1 ? "checked" : ""}>
                <label for="${blockId}_vehicle_spouse_owned">배우자명의</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>담보권 종류</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_security_type" value="${data.security_type || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>채권(최고)액</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_max_bond" value="${data.max_bond ? this.formatMoney(data.max_bond) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>환가 예상액</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_expected_value" value="${data.expected_value ? this.formatMoney(data.expected_value) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>채무 잔액</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_financial_balance" value="${data.financial_balance ? this.formatMoney(data.financial_balance) : ""}">원
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="vehicle_liquidation_value input86" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}">원
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_vehicle_manual_calc" class="vehicle_manual_calc" ${data.is_manual_calc==="Y" ? "checked" : ""}>
                <label for="${blockId}_vehicle_manual_calc">수동계산</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span></span></div>
              <div class="form-content">
                부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="vehicle_liquidation_explain  form-content-justify" value="${data.explanation || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_vehicle_seizure_yes" name="vehicle_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_vehicle_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_vehicle_seizure_no" name="vehicle_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_vehicle_seizure_no">무</label>
                </div>
              </div>
            </div>
			<div class="form">
              <div class="form-title form-notitle"><span></span></div>
              <div class="form-content form-nocontent"></div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete vehicle_delete_btn">삭제</button>
                <button type="button" class="btn-save vehicle_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#vehicle_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".vehicle_max_bond, .vehicle_expected_value, .vehicle_financial_balance, .vehicle_liquidation_value")
         .on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".vehicle_save_btn").on("click", () => this.saveVehicleBlock(block));
    block.find(".vehicle_delete_btn").on("click", () => this.deleteVehicleBlock(block));
  }

  saveVehicleBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".vehicle_asset_no").val();
    const data = {
      asset_type: "vehicle",
      case_no: caseNo,
      vehicle_info: block.find(".vehicle_info").val().trim(),
      is_spouse: block.find(".vehicle_spouse_owned").is(":checked") ? 1 : 0,
      security_type: block.find(".vehicle_security_type").val().trim(),
      max_bond: this.unformatMoney(block.find(".vehicle_max_bond").val()),
      expected_value: this.unformatMoney(block.find(".vehicle_expected_value").val()),
      financial_balance: this.unformatMoney(block.find(".vehicle_financial_balance").val()),
      liquidation_value: this.unformatMoney(block.find(".vehicle_liquidation_value").val()),
      explanation: block.find(".vehicle_liquidation_explain").val().trim(),
      is_manual_calc: block.find(".vehicle_manual_calc").is(":checked") ? "Y" : "N",
      is_seized: block.find(`input[name="vehicle_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".vehicle_property_no").val() || this.assetCounters.vehicle
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("자동차 자산이 저장되었습니다.");
          block.find(".vehicle_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "자동차 자산 저장 실패");
        }
      },
      error: () => {
        alert("자동차 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteVehicleBlock(block) {
    if (!block.find(".vehicle_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("vehicle");
      return;
    }
    if (!confirm("자동차 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".vehicle_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "vehicle", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("자동차 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("vehicle");
        } else {
          alert(response.message || "자동차 자산 삭제 실패");
        }
      },
      error: () => {
        alert("자동차 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 5. 임차보증금 섹션
  addRentDepositBlock(data = {}) {
    this.assetCounters.rent_deposit++;
    const blockId = "rent_deposit_block_" + this.assetCounters.rent_deposit;
    const propertyNo = data.property_no || this.assetCounters.rent_deposit;
    const html = `
      <div class="asset-block rent-deposit-block" id="${blockId}">
        <input type="hidden" class="rent_deposit_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="rent_deposit_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>임차지</span></div>
              <div class="form-content">
                <input type="text" class="rent_location" value="${data.rent_location || ""}" placeholder="" class="form-control form-content-long">
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_rent_business_place" class="rent_business_place" ${data.is_business_place==="Y" ? "checked" : ""}>
                <label for="${blockId}_rent_business_place">영업장</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>계약상 보증금</span></div>
              <div class="form-content">
                <input type="text" class="rent_contract_deposit input86" value="${data.contract_deposit ? this.formatMoney(data.contract_deposit) : ""}" class="form-control form-content-justify">원
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_rent_deposit_spouse" class="rent_deposit_spouse" ${data.is_deposit_spouse==1 ? "checked" : ""}>
                <label for="${blockId}_rent_deposit_spouse">배우자명의</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>월세</span></div>
              <div class="form-content">
                <input type="text" class="rent_monthly_rent" value="${data.monthly_rent ? this.formatMoney(data.monthly_rent) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>반환받을 보증금</span></div>
              <div class="form-content">
                <input type="text" class="rent_refund_deposit" value="${data.refund_deposit ? this.formatMoney(data.refund_deposit) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>차이나는 이유</span></div>
              <div class="form-content">
                <input type="text" class="rent_difference_reason input100" value="${data.difference_reason || ""}" placeholder="계약상보증금과 반환받을 금액이 차이나는 경우 작성해 주십시오." class="form-control form-content-long">
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류할 수 없는<br>최우선 변제 보증금</span></div>
              <div class="form-content">
                <input type="text" class="rent_priority_deposit" value="${data.priority_deposit ? this.formatMoney(data.priority_deposit) : ""}">원 제외
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="rent_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span></span></div>
              <div class="form-content">
                부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="rent_liquidation_explain  form-content-justify" value="${data.explanation || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_rent_seizure_yes" name="rent_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_rent_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_rent_seizure_no" name="rent_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_rent_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete rent_deposit_delete_btn">삭제</button>
                <button type="button" class="btn-save rent_deposit_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#rent_deposit_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".rent_contract_deposit, .rent_monthly_rent, .rent_refund_deposit, .rent_priority_deposit, .rent_liquidation_value")
         .on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".rent_deposit_save_btn").on("click", () => this.saveRentDepositBlock(block));
    block.find(".rent_deposit_delete_btn").on("click", () => this.deleteRentDepositBlock(block));
  }

  saveRentDepositBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".rent_deposit_asset_no").val();
    const data = {
      asset_type: "rent_deposit",
      case_no: caseNo,
      rent_location: block.find(".rent_location").val().trim(),
      is_business_place: block.find(".rent_business_place").is(":checked") ? "Y" : "N",
      contract_deposit: this.unformatMoney(block.find(".rent_contract_deposit").val()),
      is_deposit_spouse: block.find(".rent_deposit_spouse").is(":checked") ? 1 : 0,
      monthly_rent: this.unformatMoney(block.find(".rent_monthly_rent").val()),
      refund_deposit: this.unformatMoney(block.find(".rent_refund_deposit").val()),
      difference_reason: block.find(".rent_difference_reason").val().trim(),
      priority_deposit: this.unformatMoney(block.find(".rent_priority_deposit").val()),
      liquidation_value: this.unformatMoney(block.find(".rent_liquidation_value").val()),
      explanation: block.find(".rent_liquidation_explain").val().trim(),
      is_seized: block.find(`input[name="rent_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".rent_deposit_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("임차보증금 자산이 저장되었습니다.");
          block.find(".rent_deposit_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "임차보증금 자산 저장 실패");
        }
      },
      error: () => {
        alert("임차보증금 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteRentDepositBlock(block) {
    // 저장되지 않은 블록은 바로 삭제하고 빈 블록 추가
    if (!block.find(".rent_deposit_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("rent_deposit");
      return;
    }
    if (!confirm("임차보증금 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".rent_deposit_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "rent_deposit", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("임차보증금 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("rent_deposit");
        } else {
          alert(response.message || "임차보증금 자산 삭제 실패");
        }
      },
      error: () => {
        alert("임차보증금 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

	// 6. 부동산 섹션
	addRealEstateBlock(data = {}) {
		this.assetCounters.real_estate++;
		const blockId = "real_estate_block_" + this.assetCounters.real_estate;
		const propertyNo = data.property_no || this.assetCounters.real_estate;
		const html = `
		  <div class="asset-block real-estate-block" id="${blockId}">
			<input type="hidden" class="real_estate_asset_no" value="${data.asset_no || ""}">
			<input type="hidden" class="real_estate_property_no" value="${propertyNo}">
			<div class="content-wrapper">
			  <div class="left-section">
				<div class="form">
				  <div class="form-title"><span>권리 및 부동산 종류</span></div>
				  <div class="form-content">
					<div class="form-group">
					  <select class="property_right_type" name="propertyRightType">
						<option value="소유권" ${(data.property_right_type==="소유권") ? "selected" : ""}>소유권</option>
						<option value="지분권" ${(data.property_right_type==="지분권") ? "selected" : ""}>지분권</option>
					  </select>
					  <select class="property_type" name="propertyType">
						<option value="토지" ${(data.property_type==="토지") ? "selected" : ""}>토지</option>
						<option value="건물" ${(data.property_type==="건물") ? "selected" : ""}>건물</option>
						<option value="집합건물" ${(data.property_type==="집합건물") ? "selected" : ""}>집합건물</option>
						<option value="토지, 건물" ${(data.property_type==="토지, 건물") ? "selected" : ""}>토지, 건물</option>
					  </select>
					  <div class="form-content checkbox-right" style="border-bottom:none;">
						면적&nbsp;&nbsp;<input type="text" class="property_area" value="${data.property_area ? this.formatMoney(data.property_area) : ""}" class="form-control form-content-short">㎡
					  </div>
					</div>
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>소재지</span></div>
				  <div class="form-content">
					<input type="text" class="property_location" value="${data.property_location || ""}">
				  </div>
				  <div class="form-content checkbox-right">
					<input type="checkbox" id="${blockId}_property_spouse_owned" class="property_spouse_owned" ${data.is_spouse==1 ? "checked" : ""}>
					<label for="${blockId}_property_spouse_owned">배우자명의</label>
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>환가예상액</span></div>
				  <div class="form-content">
					<input type="text" class="property_expected_value" value="${data.property_expected_value ? this.formatMoney(data.property_expected_value) : ""}">원
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>담보권 종류</span></div>
				  <div class="form-content">
					<div class="form-group">
					  <select class="property_security_type" name="securityRightType">
						<option value="근저당권" ${(data.property_security_type==="근저당권") ? "selected" : ""}>근저당권</option>
						<option value="전세(임차)권" ${(data.property_security_type==="전세(임차)권") ? "selected" : ""}>전세(임차)권</option>
						<option value="근저당권, 전세(임차)권" ${(data.property_security_type==="근저당권, 전세(임차)권") ? "selected" : ""}>근저당권, 전세(임차)권</option>
					  </select>
					</div>
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>담보권 내용</span></div>
				  <div class="form-content">
					<input type="text" class="property_security_details" value="${data.property_security_details || ""}">
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>피담보 채무액</span></div>
				  <div class="form-content">
					<input type="text" class="property_secured_debt" value="${data.property_secured_debt ? this.formatMoney(data.property_secured_debt) : ""}">원
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>보증금 채무액</span></div>
				  <div class="form-content">
					<input type="text" class="property_deposit_debt" value="${data.property_deposit_debt ? this.formatMoney(data.property_deposit_debt) : ""}">원
				  </div>
				</div>
			  </div>
			  <div class="right-section">
				<div class="form">
				  <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
				  <div class="form-content form-nocontent">
					<input type="text" class="property_liquidation_value" value="${data.property_liquidation_value ? this.formatMoney(data.property_liquidation_value) : ""}">원
				  </div>
				</div>
				<div class="form">
				  <div class="form-title form-notitle"><span></span></div>
				  <div class="form-content">
					※ 별제권부채권의 목적물인 경우 채권자 목록을 반드시 먼저 작성해야 합니다.
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span></span></div>
				  <div class="form-content">
					부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="property_liquidation_explain form-content-justify" value="${data.property_liquidation_explain || ""}">
				  </div>
				</div>
				<div class="form">
				  <div class="form-title"><span>압류 유무</span></div>
				  <div class="form-content">
					<div class="radio">
					  <input type="radio" id="${blockId}_property_seizure_yes" name="property_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
					  <label for="${blockId}_property_seizure_yes">유</label>
					  <input type="radio" id="${blockId}_property_seizure_no" name="property_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
					  <label for="${blockId}_property_seizure_no">무</label>
					</div>
				  </div>
				</div>
				<div class="form">
				  <div class="form-title form-notitle"><span></span></div>
				  <div class="form-content form-nocontent"></div>
				</div>
				<div class="form">
				  <div class="form-title form-notitle"><span></span></div>
				  <div class="form-content form-nocontent"></div>
				</div>
				<div class="form">
				  <div class="form-title"></div>
				  <div class="form-content btn-right">
					<button type="button" class="btn-delete property_delete_btn">삭제</button>
					<button type="button" class="btn-save property_save_btn">저장</button>
				  </div>
				</div>
			  </div>
			</div>
		  </div>
		`;
		$("#real_estate_assets_container").append(html);
		const block = $("#" + blockId);

		// 기존 이벤트 핸들러 설정
		block.find(".property_area, .property_expected_value, .property_secured_debt, .property_deposit_debt, .property_liquidation_value")
		   .on("input", (e) => {
		const val = e.target.value.replace(/[^\d]/g, "");
		e.target.value = this.formatMoney(val);
		});

		// 배우자명의 체크박스 이벤트 추가
		block.find(".property_spouse_owned").on("change", () => {
		this.calculateRealEstateLiquidationValue(block);
		});

		// 환가예상액, 담보채무액, 보증금 채무액 입력 시 자동 계산
		block.find(".property_expected_value, .property_secured_debt, .property_deposit_debt").on("input", () => {
		this.calculateRealEstateLiquidationValue(block);
		});

		block.find(".property_save_btn").on("click", () => this.saveRealEstateBlock(block));
		block.find(".property_delete_btn").on("click", () => this.deleteRealEstateBlock(block));

		// 초기 로드 시 한 번 계산
		this.calculateRealEstateLiquidationValue(block);
	}
	
	// 부동산 청산가치 계산 함수 추가
	calculateRealEstateLiquidationValue(block) {
	  const isSpouseOwned = block.find(".property_spouse_owned").is(":checked");
	  const expectedValue = this.unformatMoney(block.find(".property_expected_value").val());
	  const securedDebt = this.unformatMoney(block.find(".property_secured_debt").val());
	  const depositDebt = this.unformatMoney(block.find(".property_deposit_debt").val());
	  
	  const totalDebt = securedDebt + depositDebt;
	  let liquidationValue = expectedValue - totalDebt;
	  
	  // 청산가치가 0보다 작으면 0으로 설정
	  liquidationValue = Math.max(0, liquidationValue);
	  
	  // 배우자명의인 경우 청산가치의 1/2 적용
	  if (isSpouseOwned) {
		liquidationValue = Math.floor(liquidationValue / 2);
		block.find(".property_liquidation_explain").val("배우자명의 재산으로서 채무액을 공제한 환가예상액의 1/2 반영함");
	  } else {
		// 배우자명의가 아닌 경우 기존 설명 유지 또는 비움
		if (block.find(".property_liquidation_explain").val().includes("배우자명의 재산으로서")) {
		  block.find(".property_liquidation_explain").val("");
		}
	  }
	  
	  // 청산가치 업데이트
	  block.find(".property_liquidation_value").val(this.formatMoney(liquidationValue));
	}

  saveRealEstateBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".real_estate_asset_no").val();
    const data = {
      asset_type: "real_estate",
      case_no: caseNo,
      property_right_type: block.find(".property_right_type").val().trim(),
      property_type: block.find(".property_type").val().trim(),
      property_area: this.unformatMoney(block.find(".property_area").val()),
      property_location: block.find(".property_location").val().trim(),
      is_spouse: block.find(".property_spouse_owned").is(":checked") ? 1 : 0,
      property_expected_value: this.unformatMoney(block.find(".property_expected_value").val()),
      property_security_type: block.find(".property_security_type").val().trim(),
      property_security_details: block.find(".property_security_details").val().trim(),
      property_secured_debt: this.unformatMoney(block.find(".property_secured_debt").val()),
      property_deposit_debt: this.unformatMoney(block.find(".property_deposit_debt").val()),
      property_liquidation_value: this.unformatMoney(block.find(".property_liquidation_value").val()),
      property_liquidation_explain: block.find(".property_liquidation_explain").val().trim(),
      is_seized: block.find(`input[name="property_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".real_estate_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("부동산 자산이 저장되었습니다.");
          block.find(".real_estate_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "부동산 자산 저장 실패");
        }
      },
      error: () => {
        alert("부동산 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteRealEstateBlock(block) {
    // 저장되지 않은 블록인 경우 바로 삭제 후 빈 블록 추가
    if (!block.find(".real_estate_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("real_estate");
      return;
    }
    if (!confirm("부동산 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".real_estate_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "real_estate", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("부동산 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("real_estate");
        } else {
          alert(response.message || "부동산 자산 삭제 실패");
        }
      },
      error: () => {
        alert("부동산 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 7. 사업용설비, 재고, 비품 등 섹션
  addBusinessEquipmentBlock(data = {}) {
    this.assetCounters.business_equipment++;
    const blockId = "business_equipment_block_" + this.assetCounters.business_equipment;
    const propertyNo = data.property_no || this.assetCounters.business_equipment;
    const html = `
      <div class="asset-block business-equipment-block" id="${blockId}">
        <input type="hidden" class="business_equipment_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="business_equipment_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>품목</span></div>
              <div class="form-content">
                <input type="text" class="equipment_item_name" value="${data.item_name || ""}" placeholder="시설비품목록표 참조">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>수량</span></div>
              <div class="form-content">
                <input type="number" class="equipment_quantity" value="${data.quantity || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>구입시기</span></div>
              <div class="form-content">
                <input type="text" class="equipment_purchase_date" value="${data.purchase_date || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>중고시세</span></div>
              <div class="form-content">
                <input type="text" class="equipment_used_price" value="${data.used_price ? this.formatMoney(data.used_price) : ""}">원
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="equipment_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}" placeholder="평가 총액">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_equipment_seizure_yes" name="equipment_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_equipment_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_equipment_seizure_no" name="equipment_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_equipment_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span></span></div>
              <div class="form-content form-nocontent"></div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-save equipment_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#business_equipment_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".equipment_used_price, .equipment_liquidation_value")
         .on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".equipment_save_btn").on("click", () => this.saveBusinessEquipmentBlock(block));
    block.find(".equipment_delete_btn").on("click", () => this.deleteBusinessEquipmentBlock(block));
  }

  saveBusinessEquipmentBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".business_equipment_asset_no").val();
    const data = {
      asset_type: "business_equipment",
      case_no: caseNo,
      item_name: block.find(".equipment_item_name").val().trim(),
      quantity: block.find(".equipment_quantity").val(),
      purchase_date: block.find(".equipment_purchase_date").val().trim(),
      used_price: this.unformatMoney(block.find(".equipment_used_price").val()),
      liquidation_value: this.unformatMoney(block.find(".equipment_liquidation_value").val()),
      property_no: block.find(".business_equipment_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("사업용설비 자산이 저장되었습니다.");
          block.find(".business_equipment_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "사업용설비 자산 저장 실패");
        }
      },
      error: () => {
        alert("사업용설비 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteBusinessEquipmentBlock(block) {
    if (!block.find(".business_equipment_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("business_equipment");
      return;
    }
    if (!confirm("사업용설비 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".business_equipment_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "business_equipment", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("사업용설비 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("business_equipment");
        } else {
          alert(response.message || "사업용설비 자산 삭제 실패");
        }
      },
      error: () => {
        alert("사업용설비 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 8. 대여금채권 섹션
  addLoanReceivablesBlock(data = {}) {
    this.assetCounters.loan_receivables++;
    const blockId = "loan_receivables_block_" + this.assetCounters.loan_receivables;
    const propertyNo = data.property_no || this.assetCounters.loan_receivables;
    const html = `
      <div class="asset-block loan-receivables-block" id="${blockId}">
        <input type="hidden" class="loan_receivables_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="loan_receivables_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>상대방(채무자)</span></div>
              <div class="form-content">
                <input type="text" class="loan_debtor_name" value="${data.debtor_name || ""}" class="form-control form-content-long">
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_loan_evidence_attached" class="loan_evidence_attached" ${data.hasOwnProperty("has_evidence") && data.has_evidence==="Y" ? "checked" : ""}>
                <label for="${blockId}_loan_evidence_attached">소명자료별첨</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="loan_liquidation_value" placeholder="회수가능금액" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}" class="form-control">원
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_loan_seizure_yes" name="loan_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_loan_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_loan_seizure_no" name="loan_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_loan_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete loan_receivables_delete_btn">삭제</button>
                <button type="button" class="btn-save loan_receivables_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#loan_receivables_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".loan_receivables_save_btn").on("click", () => this.saveLoanReceivablesBlock(block));
    block.find(".loan_receivables_delete_btn").on("click", () => this.deleteLoanReceivablesBlock(block));
  }

  saveLoanReceivablesBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".loan_receivables_asset_no").val();
    const data = {
      asset_type: "loan_receivables",
      case_no: caseNo,
      debtor_name: block.find(".loan_debtor_name").val().trim(),
      has_evidence: block.find(".loan_evidence_attached").is(":checked") ? "Y" : "N",
      liquidation_value: this.unformatMoney(block.find(".loan_liquidation_value").val()),
      is_seized: block.find(`input[name="loan_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".loan_receivables_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("대여금채권이 저장되었습니다.");
          block.find(".loan_receivables_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "대여금채권 저장 실패");
        }
      },
      error: () => {
        alert("대여금채권 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteLoanReceivablesBlock(block) {
    if (!block.find(".loan_receivables_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("loan_receivables");
      return;
    }
    if (!confirm("대여금채권을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".loan_receivables_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "loan_receivables", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("대여금채권이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("loan_receivables");
        } else {
          alert(response.message || "대여금채권 삭제 실패");
        }
      },
      error: () => {
        alert("대여금채권 삭제 중 오류가 발생했습니다.");
      }
    });
  }
  
	// 8-2. 매출금채권 섹션 (대여금채권과 동일한 내용, 단 제목 및 asset_type만 "sales_receivables"로 변경)
	addSalesReceivablesBlock(data = {}) {
	  this.assetCounters.sales_receivables++;
	  const blockId = "sales_receivables_block_" + this.assetCounters.sales_receivables;
	  const propertyNo = data.property_no || this.assetCounters.sales_receivables;
	  const html = `
		<div class="asset-block sales-receivables-block" id="${blockId}">
		  <input type="hidden" class="sales_receivables_asset_no" value="${data.asset_no || ""}">
		  <input type="hidden" class="sales_receivables_property_no" value="${propertyNo}">
		  <div class="content-wrapper">
			<div class="left-section">
			  <div class="form">
				<div class="form-title"><span>상대방(채무자)</span></div>
				<div class="form-content">
				  <input type="text" class="sales_debtor_name" value="${data.debtor_name || ""}" class="form-control form-content-long">
				</div>
				<div class="form-content checkbox-right">
				  <input type="checkbox" id="${blockId}_sales_evidence_attached" class="sales_evidence_attached" ${data.hasOwnProperty("has_evidence") && data.has_evidence==="Y" ? "checked" : ""}>
				  <label for="${blockId}_sales_evidence_attached">소명자료별첨</label>
				</div>
			  </div>
			  <div class="form">
				<div class="form-title"><span>청산가치 판단금액</span></div>
				<div class="form-content">
				  <input type="text" class="sales_liquidation_value" placeholder="회수가능금액" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}" class="form-control">원
				</div>
			  </div>
			</div>
			<div class="right-section">
			  <div class="form">
				<div class="form-title"><span>압류 유무</span></div>
				<div class="form-content">
				  <div class="radio">
					<input type="radio" id="${blockId}_sales_seizure_yes" name="sales_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
					<label for="${blockId}_sales_seizure_yes">유</label>
					<input type="radio" id="${blockId}_sales_seizure_no" name="sales_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
					<label for="${blockId}_sales_seizure_no">무</label>
				  </div>
				</div>
			  </div>
			  <div class="form">
				<div class="form-title"></div>
				<div class="form-content btn-right">
				  <button type="button" class="btn-delete sales_receivables_delete_btn">삭제</button>
				  <button type="button" class="btn-save sales_receivables_save_btn">저장</button>
				</div>
			  </div>
			</div>
		  </div>
		</div>
	  `;
	  $("#sales_receivables_assets_container").append(html);
	  const block = $("#" + blockId);
	  block.find(".sales_receivables_save_btn").on("click", () => this.saveSalesReceivablesBlock(block));
	  block.find(".sales_receivables_delete_btn").on("click", () => this.deleteSalesReceivablesBlock(block));
	}

	saveSalesReceivablesBlock(block) {
	  const caseNo = window.currentCaseNo;
	  const assetNo = block.find(".sales_receivables_asset_no").val();
	  const data = {
		asset_type: "sales_receivables",
		case_no: caseNo,
		debtor_name: block.find(".sales_debtor_name").val().trim(),
		has_evidence: block.find(".sales_evidence_attached").is(":checked") ? "Y" : "N",
		liquidation_value: this.unformatMoney(block.find(".sales_liquidation_value").val()),
		is_seized: block.find(`input[name="sales_seizure_${block.attr("id")}"]:checked`).val() || "N",
		property_no: block.find(".sales_receivables_property_no").val()
	  };
	  if (assetNo) data.asset_no = assetNo;
	  $.ajax({
		url: "/adm/api/application_recovery/assets/asset_api.php",
		type: "POST",
		data: data,
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("매출금채권이 저장되었습니다.");
			block.find(".sales_receivables_asset_no").val(response.data.asset_no);
		  } else {
			alert(response.message || "매출금채권 저장 실패");
		  }
		},
		error: () => {
		  alert("매출금채권 저장 중 오류가 발생했습니다.");
		}
	  });
	}

	deleteSalesReceivablesBlock(block) {
	  if (!block.find(".sales_receivables_asset_no").val()) {
		block.remove();
		this.checkEmptyBlock("sales_receivables");
		return;
	  }
	  if (!confirm("매출금채권을 삭제하시겠습니까?")) return;
	  const caseNo = window.currentCaseNo;
	  const propertyNo = block.find(".sales_receivables_property_no").val();
	  $.ajax({
		url: "/adm/api/application_recovery/assets/asset_api.php",
		type: "DELETE",
		data: { asset_type: "sales_receivables", case_no: caseNo, property_no: propertyNo },
		processData: true,
		contentType: "application/x-www-form-urlencoded; charset=UTF-8",
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("매출금채권이 삭제되었습니다.");
			block.remove();
			this.checkEmptyBlock("sales_receivables");
		  } else {
			alert(response.message || "매출금채권 삭제 실패");
		  }
		},
		error: () => {
		  alert("매출금채권 삭제 중 오류가 발생했습니다.");
		}
	  });
	}

  // 9. 예상퇴직금 섹션
  addSeverancePayBlock(data = {}) {
    this.assetCounters.severance_pay++;
    const blockId = "severance_pay_block_" + this.assetCounters.severance_pay;
    const propertyNo = data.property_no || this.assetCounters.severance_pay;
    const html = `
      <div class="asset-block severance-pay-block" id="${blockId}">
        <input type="hidden" class="severance_pay_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="severance_pay_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>공무원</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_severance_public_yes" name="severance_is_public_${blockId}" value="Y" ${data.hasOwnProperty("is_public") && data.is_public==="Y" ? "checked" : ""}>
                  <label for="${blockId}_severance_public_yes">네</label>
                  <input type="radio" id="${blockId}_severance_public_no" name="severance_is_public_${blockId}" value="N" ${data.hasOwnProperty("is_public") && data.is_public==="N" ? "checked" : ""}>
                  <label for="${blockId}_severance_public_no">아니오</label>
                </div>
              </div>
              <div class="form-content checkbox-right">
                <input type="checkbox" id="${blockId}_severance_pension" class="severance_pension" ${data.hasOwnProperty("has_pension") && data.has_pension==="Y" ? "checked" : ""}>
                <label for="${blockId}_severance_pension">퇴직연금가입사업장</label>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>근무지</span></div>
              <div class="form-content">
                <input type="text" class="severance_workplace" value="${data.workplace || ""}" class="form-control form-content-long">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>예상퇴직금</span></div>
              <div class="form-content">
                <input type="text" class="severance_expected_amount" value="${data.expected_severance ? this.formatMoney(data.expected_severance) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
              <div class="form-content form-nocontent">
                <input type="text" class="severance_deduction_amount" value="${data.deduction_amount ? this.formatMoney(data.deduction_amount) : ""}" placeholder="">원 제외 (압류할 수 없는 퇴직금)
              </div>
            </div>
            <div class="form">
              <div class="form-title form-notitle"></div>
              <div class="form-content">
                <input type="text" class="severance_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}" placeholder="">원 (청산가치)
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_severance_seizure_yes" name="severance_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_severance_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_severance_seizure_no" name="severance_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_severance_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title form-title-3"><span>주의사항</span></div>
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
                <button type="button" class="btn-delete severance_pay_delete_btn">삭제</button>
                <button type="button" class="btn-save severance_pay_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#severance_pay_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".severance_expected_amount, .severance_deduction_amount, .severance_liquidation_value")
         .on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".severance_pay_save_btn").on("click", () => this.saveSeverancePayBlock(block));
    block.find(".severance_pay_delete_btn").on("click", () => this.deleteSeverancePayBlock(block));
  }

  saveSeverancePayBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".severance_pay_asset_no").val();
    const data = {
      asset_type: "severance_pay",
      case_no: caseNo,
      is_public: block.find(`input[name="severance_is_public_${block.attr("id")}"]:checked`).val() || "N",
      has_pension: block.find(".severance_pension").is(":checked") ? "Y" : "N",
      workplace: block.find(".severance_workplace").val().trim(),
      expected_severance: this.unformatMoney(block.find(".severance_expected_amount").val()),
      deduction_amount: this.unformatMoney(block.find(".severance_deduction_amount").val()),
      liquidation_value: this.unformatMoney(block.find(".severance_liquidation_value").val()),
      is_seized: block.find(`input[name="severance_seizure_${block.attr("id")}"]:checked`).val() || "N",
      property_no: block.find(".severance_pay_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("예상퇴직금 자산이 저장되었습니다.");
          block.find(".severance_pay_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "예상퇴직금 자산 저장 실패");
        }
      },
      error: () => {
        alert("예상퇴직금 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteSeverancePayBlock(block) {
    if (!block.find(".severance_pay_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("severance_pay");
      return;
    }
    if (!confirm("예상퇴직금 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".severance_pay_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "severance_pay", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("예상퇴직금 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("severance_pay");
        } else {
          alert(response.message || "예상퇴직금 자산 삭제 실패");
        }
      },
      error: () => {
        alert("예상퇴직금 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 10. 기타 섹션
  addOtherAssetBlock(data = {}) {
    this.assetCounters.other_assets++;
    const blockId = "other_asset_block_" + this.assetCounters.other_assets;
    const propertyNo = data.property_no || this.assetCounters.other_assets;
    const html = `
      <div class="asset-block other-asset-block" id="${blockId}">
        <input type="hidden" class="other_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="other_asset_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>재산 내용</span></div>
              <div class="form-content">
                <input type="text" class="other_asset_content" value="${data.asset_content || ""}" class="form-control">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>청산가치 판단금액</span></div>
              <div class="form-content">
                <input type="text" class="other_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}">원
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>압류 유무</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_other_seizure_yes" name="other_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
                  <label for="${blockId}_other_seizure_yes">유</label>
                  <input type="radio" id="${blockId}_other_seizure_no" name="other_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : ""}>
                  <label for="${blockId}_other_seizure_no">무</label>
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete other_asset_delete_btn">삭제</button>
                <button type="button" class="btn-save other_asset_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#other_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".other_asset_save_btn").on("click", () => this.saveOtherAssetBlock(block));
    block.find(".other_asset_delete_btn").on("click", () => this.deleteOtherAssetBlock(block));
  }

  saveOtherAssetBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".other_asset_no").val();
    const data = {
      asset_type: "other_assets",
      case_no: caseNo,
      asset_content: block.find(".other_asset_content").val().trim(),
      liquidation_value: this.unformatMoney(block.find(".other_liquidation_value").val()),
      property_no: block.find(".other_asset_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("기타 자산이 저장되었습니다.");
          block.find(".other_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "기타 자산 저장 실패");
        }
      },
      error: () => {
        alert("기타 자산 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteOtherAssetBlock(block) {
    // 저장되지 않은 블록은 바로 삭제 후 빈 블록 생성
    if (!block.find(".other_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("other_assets");
      return;
    }
    if (!confirm("기타 자산을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".other_asset_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "other_assets", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("기타 자산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("other_assets");
        } else {
          alert(response.message || "기타 자산 삭제 실패");
        }
      },
      error: () => {
        alert("기타 자산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 11. 면제재산 결정신청 - 주거용 임차보증금반환청구권 섹션
  addExemptPropertyBlock(data = {}) {
    this.assetCounters.exempt_property++;
    const blockId = "exempt_property_block_" + this.assetCounters.exempt_property;
    const propertyNo = data.property_no || this.assetCounters.exempt_property;
    const html = `
      <div class="asset-block exempt-property-block" id="${blockId}">
        <input type="hidden" class="exempt_property_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="exempt_property_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>첨부할 소명자료</span></div>
              <div class="form-content">
                <div class="checkbox-group input100">
                  <input type="checkbox" id="${blockId}_exempt_rent_contract" class="exempt_rent_contract">
                  <label for="${blockId}_exempt_rent_contract">임대차계약서</label>
                  <input type="checkbox" id="${blockId}_exempt_resident_registration" class="exempt_resident_registration">
                  <label for="${blockId}_exempt_resident_registration">주민등록등본</label>
                  <input type="checkbox" id="${blockId}_exempt_other_evidence" class="exempt_other_evidence">
                  <label for="${blockId}_exempt_other_evidence">기타</label>&nbsp;&nbsp;|&nbsp;&nbsp;
                  <input type="text" class="exempt_other_evidence_detail"  value="${data.other_evidence_detail || ""}" style="width:30%;">통
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>임차한 소재지</span></div>
              <div class="form-content">
                <input type="text" class="exempt_rent_location" value="${data.lease_location || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>계약일자</span></div>
              <div class="form-content">
                <input type="date" class="exempt_contract_date" value="${data.contract_date || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>임대차 기간</span></div>
              <div class="form-content">
                <input type="date" class="exempt_rent_start_date" value="${data.lease_start_date || ""}">&nbsp;&nbsp;부터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="date" class="exempt_rent_end_date" value="${data.lease_end_date || ""}">&nbsp;&nbsp;까지
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>확정일자</span></div>
              <div class="form-content">
                <div class="radio">
                  <input type="radio" id="${blockId}_exempt_fixed_date_yes" name="exempt_fixed_date_${blockId}" value="Y" ${data.hasOwnProperty("has_fixed_date") && data.has_fixed_date==="Y" ? "checked" : ""}>
                  <label for="${blockId}_exempt_fixed_date_yes">유</label>
                  <input type="radio" id="${blockId}_exempt_fixed_date_no" name="exempt_fixed_date_${blockId}" value="N" ${data.hasOwnProperty("has_fixed_date") && data.has_fixed_date==="N" ? "checked" : ""}>
                  <label for="${blockId}_exempt_fixed_date_no">무</label>
                </div>
                <input type="date" class="exempt_fixed_date" value="${data.fixed_date || ""}">까지
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>주민등록일자</span></div>
              <div class="form-content">
                <input type="date" class="exempt_registration_date" value="${data.registration_date || ""}">
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>임차보증금</span></div>
              <div class="form-content">
                <input type="text" class="exempt_rent_deposit" value="${data.lease_deposit ? this.formatMoney(data.lease_deposit) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>임료</span></div>
              <div class="form-content">
                <input type="text" class="exempt_rent_fee" value="${data.rent_fee ? this.formatMoney(data.rent_fee) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>연체기간</span></div>
              <div class="form-content">
                <input type="text" class="exempt_overdue_months" value="${data.overdue_months || ""}">개월
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>임대인 성명</span></div>
              <div class="form-content">
                <input type="text" class="exempt_lessor_name" value="${data.lessor_name || ""}">
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>면제신청 금액</span></div>
              <div class="form-content">
                <input type="text" class="exempt_amount" value="${data.exemption_amount ? this.formatMoney(data.exemption_amount) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete exempt_property_delete_btn">삭제</button>
                <button type="button" class="btn-save exempt_property_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#exempt_property_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".exempt_amount").on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
	block.find(".exempt_rent_contract").prop("checked", data.lease_contract === "Y");
	block.find(".exempt_resident_registration").prop("checked", data.resident_registration === "Y"); 
	block.find(".exempt_other_evidence").prop("checked", data.other_evidence === "Y");
    block.find(".exempt_property_save_btn").on("click", () => this.saveExemptPropertyBlock(block));
    block.find(".exempt_property_delete_btn").on("click", () => this.deleteExemptPropertyBlock(block));
	
  }

	saveExemptPropertyBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".exempt_property_asset_no").val();
		
		// 날짜 값을 처리하는 헬퍼 함수
		const formatDate = (dateStr) => {
			return dateStr ? dateStr : null;
		};
		
		const data = {
			asset_type: "exempt_property",
			case_no: caseNo,
			lease_contract: block.find(".exempt_rent_contract").is(":checked") ? "Y" : "N",
			resident_registration: block.find(".exempt_resident_registration").is(":checked") ? "Y" : "N",
			other_evidence: block.find(".exempt_other_evidence").is(":checked") ? "Y" : "N",
			other_evidence_detail: block.find(".exempt_other_evidence_detail").val().trim(),
			lease_location: block.find(".exempt_rent_location").val().trim(),
			contract_date: formatDate(block.find(".exempt_contract_date").val()),
			lease_start_date: formatDate(block.find(".exempt_rent_start_date").val()),
			lease_end_date: formatDate(block.find(".exempt_rent_end_date").val()),
			has_fixed_date: block.find(`input[name="exempt_fixed_date_${block.attr("id")}"]:checked`).val() || "N",
			fixed_date: formatDate(block.find(".exempt_fixed_date").val()),
			registration_date: formatDate(block.find(".exempt_registration_date").val()),
			lease_deposit: this.unformatMoney(block.find(".exempt_rent_deposit").val()),
			rent_fee: this.unformatMoney(block.find(".exempt_rent_fee").val()),
			overdue_months: block.find(".exempt_overdue_months").val() || 0,
			lessor_name: block.find(".exempt_lessor_name").val().trim(),
			exemption_amount: this.unformatMoney(block.find(".exempt_amount").val()),
			property_no: block.find(".exempt_property_property_no").val()
		};
		
		if (assetNo) data.asset_no = assetNo;
		
		$.ajax({
			url: "/adm/api/application_recovery/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("면제재산(임차보증금반환청구권)이 저장되었습니다.");
					block.find(".exempt_property_asset_no").val(response.data.asset_no);
				} else {
					alert(response.message || "면제재산 저장 실패");
				}
			},
			error: () => {
				alert("면제재산 저장 중 오류가 발생했습니다.");
			}
		});
	}

  deleteExemptPropertyBlock(block) {
    // 저장되지 않은 블록인 경우 바로 삭제 후 빈 블록 추가
    if (!block.find(".exempt_property_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("exempt_property");
      return;
    }
    if (!confirm("면제재산(임차보증금반환청구권)을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".exempt_property_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "exempt_property", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("면제재산이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("exempt_property");
        } else {
          alert(response.message || "면제재산 삭제 실패");
        }
      },
      error: () => {
        alert("면제재산 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  // 12. 면제재산 결정신청 - 6개월간 생계비 섹션
  addExemptPropertySpecialBlock(data = {}) {
    this.assetCounters.exempt_property_special++;
    const blockId = "exempt_property_special_block_" + this.assetCounters.exempt_property_special;
    const propertyNo = data.property_no || this.assetCounters.exempt_property_special;
    const html = `
      <div class="asset-block exempt-property-special-block" id="${blockId}">
        <input type="hidden" class="exempt_property_special_asset_no" value="${data.asset_no || ""}">
        <input type="hidden" class="exempt_property_special_property_no" value="${propertyNo}">
        <div class="content-wrapper">
          <div class="left-section">
            <div class="form">
              <div class="form-title"><span>첨부할 소명자료</span></div>
              <div class="form-content">
                <div class="evidence-group">
                  ① <input type="text" class="exempt_special_evidence1" style="width:24%" value="${data.evidence1 || ""}">&nbsp;&nbsp;통&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  ② <input type="text" class="exempt_special_evidence2" style="width:24%" value="${data.evidence2 || ""}">&nbsp;&nbsp;통&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  ③ <input type="text" class="exempt_special_evidence3" style="width:24%" value="${data.evidence3 || ""}">&nbsp;&nbsp;통
                </div>
              </div>
            </div>
            <div class="form">
              <div class="form-title"><span>특정재산의 내용</span></div>
              <div class="form-content">
                <input type="text" class="exempt_special_content" value="${data.special_property_content || ""}">
              </div>
            </div>
          </div>
          <div class="right-section">
            <div class="form">
              <div class="form-title"><span>면제신청 금액</span></div>
              <div class="form-content">
                <input type="text" class="exempt_special_amount" value="${data.exemption_amount ? this.formatMoney(data.exemption_amount) : ""}">원
              </div>
            </div>
            <div class="form">
              <div class="form-title"></div>
              <div class="form-content btn-right">
                <button type="button" class="btn-delete exempt_property_special_delete_btn">삭제</button>
                <button type="button" class="btn-save exempt_property_special_save_btn">저장</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    $("#exempt_property_special_assets_container").append(html);
    const block = $("#" + blockId);
    block.find(".exempt_special_amount").on("input", (e) => {
      const val = e.target.value.replace(/[^\d]/g, "");
      e.target.value = this.formatMoney(val);
    });
    block.find(".exempt_property_special_save_btn").on("click", () => this.saveExemptPropertySpecialBlock(block));
    block.find(".exempt_property_special_delete_btn").on("click", () => this.deleteExemptPropertySpecialBlock(block));
  }

  saveExemptPropertySpecialBlock(block) {
    const caseNo = window.currentCaseNo;
    const assetNo = block.find(".exempt_property_special_asset_no").val();
    const data = {
      asset_type: "exempt_property_special",
      case_no: caseNo,
      evidence1: block.find(".exempt_special_evidence1").val().trim(),
      evidence2: block.find(".exempt_special_evidence2").val().trim(),
      evidence3: block.find(".exempt_special_evidence3").val().trim(),
      special_property_content: block.find(".exempt_special_content").val().trim(),
      exemption_amount: this.unformatMoney(block.find(".exempt_special_amount").val()),
      property_no: block.find(".exempt_property_special_property_no").val()
    };
    if (assetNo) data.asset_no = assetNo;
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("면제재산(6개월간 생계비)이 저장되었습니다.");
          block.find(".exempt_property_special_asset_no").val(response.data.asset_no);
        } else {
          alert(response.message || "면제재산(6개월간 생계비) 저장 실패");
        }
      },
      error: () => {
        alert("면제재산(6개월간 생계비) 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteExemptPropertySpecialBlock(block) {
    // 저장되지 않은 블록은 바로 삭제 후 빈 블록 추가
    if (!block.find(".exempt_property_special_asset_no").val()) {
      block.remove();
      this.checkEmptyBlock("exempt_property_special");
      return;
    }
    if (!confirm("면제재산(6개월간 생계비)을 삭제하시겠습니까?")) return;
    const caseNo = window.currentCaseNo;
    const propertyNo = block.find(".exempt_property_special_property_no").val();
    $.ajax({
      url: "/adm/api/application_recovery/assets/asset_api.php",
      type: "DELETE",
      data: { asset_type: "exempt_property_special", case_no: caseNo, property_no: propertyNo },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("면제재산(6개월간 생계비)이 삭제되었습니다.");
          block.remove();
          this.checkEmptyBlock("exempt_property_special");
        } else {
          alert(response.message || "면제재산(6개월간 생계비) 삭제 실패");
        }
      },
      error: () => {
        alert("면제재산(6개월간 생계비) 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  /* =========================================
     공통 헬퍼 함수
     ========================================= */
  formatMoney(amount) {
    if (!amount) return "0";
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  unformatMoney(str) {
    if (!str) return 0;
    return parseInt(str.replace(/,/g, "")) || 0;
  }

  hasUnsavedChanges() {
    // 필요에 따라 변경사항이 있는지 체크하는 로직 구현
    return false;
  }

  // 삭제 후 해당 섹션 컨테이너에 블록이 하나도 없으면 빈 블록을 추가하는 함수
  checkEmptyBlock(assetType) {
    let containerId = "";
    switch (assetType) {
      case "cash":
        containerId = "#cash_assets_container";
        break;
      case "deposit":
        containerId = "#deposit_assets_container";
        break;
      case "insurance":
        containerId = "#insurance_assets_container";
        break;
      case "vehicle":
        containerId = "#vehicle_assets_container";
        break;
      case "rent_deposit":
        containerId = "#rent_deposit_assets_container";
        break;
      case "real_estate":
        containerId = "#real_estate_assets_container";
        break;
      case "business_equipment":
        containerId = "#business_equipment_assets_container";
        break;
      case "loan_receivables":
        containerId = "#loan_receivables_assets_container";
        break;
	  case "sales_receivables":
		containerId = "#sales_receivables_assets_container";
		break;
      case "severance_pay":
        containerId = "#severance_pay_assets_container";
        break;
      case "other_assets":
        containerId = "#other_assets_container";
        break;
      case "exempt_property":
        containerId = "#exempt_property_assets_container";
        break;
      case "exempt_property_special":
        containerId = "#exempt_property_special_assets_container";
        break;
      default:
        console.warn("알 수 없는 assetType:", assetType);
        return;
    }
    if ($(containerId).children().length === 0) {
      this.addAssetBlock(assetType);
    }
  }
}
