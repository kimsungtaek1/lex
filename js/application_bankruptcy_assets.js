class AssetManager {
	constructor() {
		// 각 섹션별 동적 블록들을 배열로 관리
		this.currentAssets = {
			cash: [],
			deposit: [],
			insurance: [],
			vehicle: [],
			rent_deposit: [],
			real_estate: [],
			loan_receivables: [],
			sales_receivables: [],
			severance_pay: [],
			other_assets: [],
			disposed_assets: [],
			received_deposit: [],
			divorce_property: [],
			inherited_property: []
		};

		// 각 섹션마다 동적 블록의 고유 id 생성을 위한 카운터
		this.assetCounters = {
			cash: 0,
			deposit: 0,
			insurance: 0,
			vehicle: 0,
			rent_deposit: 0,
			real_estate: 0,
			loan_receivables: 0,
			sales_receivables: 0,
			severance_pay: 0,
			other_assets: 0,
			disposed_assets: 0,
			received_deposit: 0,
			divorce_property: 0,
			inherited_property: 0
		};

		this.cache = new Map();
		this.initialize();
	}

	initialize() {
		try {
			this.initializeEventHandlers();
			this.loadAllAssets();
			this.loadAssetSummary();
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
		$("#add_loan_receivables_asset").on("click", () => this.addLoanReceivablesBlock());
		$("#add_sales_receivables_asset").on("click", () => this.addSalesReceivablesBlock());
		$("#add_severance_pay_asset").on("click", () => this.addSeverancePayBlock());
		$("#add_other_asset").on("click", () => this.addOtherAssetBlock());
		$("#add_disposed_asset").on("click", () => this.addDisposedAssetBlock());
		$("#add_received_deposit_asset").on("click", () => this.addReceivedDepositBlock());
		$("#add_divorce_property_asset").on("click", () => this.addDivorcePropertyBlock());
		$("#add_inherited_property_asset").on("click", () => this.addInheritedPropertyBlock());
		
		// 재산목록 요약표 저장/삭제 버튼
		$('#save_asset_summary').on('click', () => this.saveAssetSummary());
		$('#delete_asset_summary').on('click', () => this.deleteAssetSummary());

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
			"loan_receivables",
			"sales_receivables",
			"severance_pay",
			"other_assets",
			"disposed_assets",
			"received_deposit",
			"divorce_property",
			"inherited_property"
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
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
					
					// 자산 요약표 업데이트
					this.updateAssetSummary(type, assets.length > 0);
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
			case "cash": containerId = "#cash_assets_container"; break;
			case "deposit": containerId = "#deposit_assets_container"; break;
			case "insurance": containerId = "#insurance_assets_container"; break;
			case "vehicle": containerId = "#vehicle_assets_container"; break;
			case "rent_deposit": containerId = "#rent_deposit_assets_container"; break;
			case "real_estate": containerId = "#real_estate_assets_container"; break;
			case "loan_receivables": containerId = "#loan_receivables_assets_container"; break;
			case "sales_receivables": containerId = "#sales_receivables_assets_container"; break;
			case "severance_pay": containerId = "#severance_pay_assets_container"; break;
			case "other_assets": containerId = "#other_assets_container"; break;
			case "disposed_assets": containerId = "#disposed_assets_container"; break;
			case "received_deposit": containerId = "#received_deposit_assets_container"; break;
			case "divorce_property": containerId = "#divorce_property_assets_container"; break;
			case "inherited_property": containerId = "#inherited_property_assets_container"; break;
			default: return;
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
			case "disposed_assets":
				this.addDisposedAssetBlock(data);
				break;
			case "received_deposit":
				this.addReceivedDepositBlock(data);
				break;
			case "divorce_property":
				this.addDivorcePropertyBlock(data);
				break;
			case "inherited_property":
				this.addInheritedPropertyBlock(data);
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
									<input type="radio" id="${blockId}_cash_seizure_no" name="cash_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("현금 자산이 저장되었습니다.");
					block.find(".cash_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('cash', true);
					this.invalidateCache('cash');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "cash", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("현금 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('cash');
					this.invalidateCache('cash');
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
									<input type="radio" id="${blockId}_deposit_seizure_no" name="deposit_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("예금 자산이 저장되었습니다.");
					block.find(".deposit_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('deposit', true);
					this.invalidateCache('deposit');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "deposit", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("예금 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('deposit');
					this.invalidateCache('deposit');
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
									<input type="radio" id="${blockId}_insurance_coverage_no" name="insurance_coverage_${blockId}" value="N" ${data.hasOwnProperty("is_coverage") && data.is_coverage==="N" ? "checked" : "checked"}>
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
									<input type="radio" id="${blockId}_insurance_seizure_no" name="insurance_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("보험 자산이 저장되었습니다.");
					block.find(".insurance_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('insurance', true);
					this.invalidateCache('insurance');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "insurance", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("보험 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('insurance');
					this.invalidateCache('insurance');
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
								<input type="text" class="vehicle_info" value="${data.vehicle_info || ""}" placeholder="차량번호, 연식, 모델(예:123가4567, 2020년형, 아반떼)">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>등록번호</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_registration_number" value="${data.registration_number || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>담보권설정 채무 잔액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_security_debt_balance" value="${data.security_debt_balance ? this.formatMoney(data.security_debt_balance) : ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>시가</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_market_value" value="${data.market_value ? this.formatMoney(data.market_value) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="vehicle_liquidation_explanation" value="${data.liquidation_explanation || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_vehicle_seizure_yes" name="vehicle_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_vehicle_seizure_yes">유</label>
									<input type="radio" id="${blockId}_vehicle_seizure_no" name="vehicle_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_vehicle_seizure_no">무</label>
								</div>
							</div>
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
		block.find(".vehicle_security_debt_balance, .vehicle_market_value, .vehicle_liquidation_value").on("input", (e) => {
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
			registration_number: block.find(".vehicle_registration_number").val().trim(),
			security_debt_balance: this.unformatMoney(block.find(".vehicle_security_debt_balance").val()),
			market_value: this.unformatMoney(block.find(".vehicle_market_value").val()),
			liquidation_value: this.unformatMoney(block.find(".vehicle_liquidation_value").val()),
			liquidation_explanation: block.find(".vehicle_liquidation_explanation").val().trim(),
			is_seized: block.find(`input[name="vehicle_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".vehicle_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("자동차 자산이 저장되었습니다.");
					block.find(".vehicle_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('vehicle', true);
					this.invalidateCache('vehicle');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "vehicle", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("자동차 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('vehicle');
					this.invalidateCache('vehicle');
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
							<div class="form-title"><span>임차물건</span></div>
							<div class="form-content">
								<input type="text" class="rent_location" value="${data.rent_location || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>임차보증금</span></div>
							<div class="form-content">
								<input type="text" class="rent_deposit" value="${data.rent_deposit ? this.formatMoney(data.rent_deposit) : ""}" class="form-control form-content-justify">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>권리금</span></div>
							<div class="form-content">
								<input type="text" class="key_money" value="${data.key_money ? this.formatMoney(data.key_money) : ""}" class="form-control form-content-justify">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>반환예상금</span></div>
							<div class="form-content">
								<input type="text" class="expected_refund" value="${data.expected_refund ? this.formatMoney(data.expected_refund) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>부연설명</span></div>
							<div class="form-content">
								<input type="text" class="rent_explanation" value="${data.explanation || ""}" class="form-control form-content-long">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_rent_seizure_yes" name="rent_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_rent_seizure_yes">유</label>
									<input type="radio" id="${blockId}_rent_seizure_no" name="rent_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_rent_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title form-title-3 form-notitle"><span>주의사항</span></div>
							<div class="form-content form-content-3 form-nocontent">
								- 반환예상금란에는 채무자가 파산신청일을 기준으로 임대인에게 임차물건을 명도할 경우<br>
								&nbsp;&nbsp;&nbsp;임대인으로부터 반환 받을 수 있는 임차보증금의 예상액을 기재하여 주십시오.<br>
								- 임대차계약서의 사본 등 임차보증금 중 반환예상액을 알 수 있는 자료를 첨부하여 주십시오.<br>
								- 상가 임대차의 경우에는 권리금이 있으면 반드시 권리금 액수를 기재해 주시기 바랍니다.
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content"></div>
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
		block.find(".rent_deposit, .key_money, .expected_refund").on("input", (e) => {
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
			rent_deposit: this.unformatMoney(block.find(".rent_deposit").val()),
			key_money: this.unformatMoney(block.find(".key_money").val()),
			expected_refund: this.unformatMoney(block.find(".expected_refund").val()),
			explanation: block.find(".rent_explanation").val().trim(),
			is_seized: block.find(`input[name="rent_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".rent_deposit_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("임차보증금이 저장되었습니다.");
					block.find(".rent_deposit_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('rent_deposit', true);
					this.invalidateCache('rent_deposit');
				} else {
					alert(response.message || "임차보증금 저장 실패");
				}
			},
			error: () => {
				alert("임차보증금 저장 중 오류가 발생했습니다.");
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
		if (!confirm("임차보증금을 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".rent_deposit_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "rent_deposit", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("임차보증금이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('rent_deposit');
					this.invalidateCache('rent_deposit');
				} else {
					alert(response.message || "임차보증금 삭제 실패");
				}
			},
			error: () => {
				alert("임차보증금 삭제 중 오류가 발생했습니다.");
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
									<select class="property_type" name="propertyType">
										<option value="토지" ${(data.property_type==="토지") ? "selected" : ""}>토지</option>
										<option value="건물" ${(data.property_type==="건물") ? "selected" : ""}>건물</option>
										<option value="토지, 건물" ${(data.property_type==="토지, 건물") ? "selected" : ""}>토지, 건물</option>
									</select>
									<div class="form-content">
										<input type="text" class="property_area" value="${data.property_area || ""}" class="form-control">
									</div>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>소재지</span></div>
							<div class="form-content">
								소재지&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="property_location" value="${data.property_location || ""}" class="form-control">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>등기된 담보권의<br>피담보채권 잔액</span></div>
							<div class="form-content">
								<input type="text" class="secured_debt_balance" value="${data.secured_debt_balance ? this.formatMoney(data.secured_debt_balance) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span>(가)압류 등</span></div>
							<div class="form-content">
								내용&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="seizure_details" value="${data.seizure_details || ""}" class="form-control">
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span></span></div>
							<div class="form-content">
								채권자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="seizure_creditor" value="${data.seizure_creditor || ""}" class="form-control">
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span></span></div>
							<div class="form-content">
									가액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="seizure_amount" value="${data.seizure_amount ? this.formatMoney(data.seizure_amount) : ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								시가&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="market_value" value="${data.market_value ? this.formatMoney(data.market_value) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="liquidation_explanation" value="${data.liquidation_explanation || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_property_seizure_yes" name="property_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_property_seizure_yes">유</label>
									<input type="radio" id="${blockId}_property_seizure_no" name="property_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_property_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								- 부동산을 소유하고 있는 경우 부동산등기사항전부증명서를 첨부하여 주십시오.<br>
								- 저당권 등 등기된 담보권에 대하여는 은행 등 담보권자가 작성한 피담보채권의 잔액증명서 등의<br>
								&nbsp;&nbsp;&nbsp;증명자료를 첨부하여 주십시오.(가압류나 압류는 등기된 담보권이 아니므로 그 가액을 표시할 때는<br>
								&nbsp;&nbsp;&nbsp;가압류나 압류임을 명시하여 주시기 바랍니다.)<br>
								- 경매진행 중일 경우에는 경매절차의 진행상태를 알 수 있는 자료를 제출하여 주십시오.
							</div>
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
		block.find(".secured_debt_balance, .seizure_amount, .market_value").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		
		block.find(".property_save_btn").on("click", () => this.saveRealEstateBlock(block));
		block.find(".property_delete_btn").on("click", () => this.deleteRealEstateBlock(block));
	}

	saveRealEstateBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".real_estate_asset_no").val();
		const data = {
			asset_type: "real_estate",
			case_no: caseNo,
			property_type: block.find(".property_type").val().trim(),
			property_area: block.find(".property_area").val().trim(),
			property_location: block.find(".property_location").val().trim(),
			secured_debt_balance: this.unformatMoney(block.find(".secured_debt_balance").val()),
			seizure_details: block.find(".seizure_details").val().trim(),
			seizure_creditor: block.find(".seizure_creditor").val().trim(),
			seizure_amount: this.unformatMoney(block.find(".seizure_amount").val()),
			market_value: this.unformatMoney(block.find(".market_value").val()),
			liquidation_explanation: block.find(".liquidation_explanation").val().trim(),
			is_seized: block.find(`input[name="property_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".real_estate_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("부동산 자산이 저장되었습니다.");
					block.find(".real_estate_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('real_estate', true);
					this.invalidateCache('real_estate');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "real_estate", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("부동산 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('real_estate');
					this.invalidateCache('real_estate');
				} else {
					alert(response.message || "부동산 자산 삭제 실패");
				}
			},
			error: () => {
				alert("부동산 자산 삭제 중 오류가 발생했습니다.");
			}
		});
	}
	
	// 7. 대여금채권 섹션
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
							<div class="form-title"><span>채무자명</span></div>
							<div class="form-content">
								<input type="text" class="loan_debtor_name" value="${data.debtor_name || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>채권금액</span></div>
							<div class="form-content">
								<input type="text" class="loan_claim_amount" value="${data.claim_amount ? this.formatMoney(data.claim_amount) : ""}" class="form-control">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>회수가능금액</span></div>
							<div class="form-content">
								<input type="text" class="loan_collectible_amount" value="${data.collectible_amount ? this.formatMoney(data.collectible_amount) : ""}" class="form-control">원
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
									<input type="radio" id="${blockId}_loan_seizure_no" name="loan_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_loan_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>주의사항</span></div>
							<div class="form-content">
								회수가 어렵다고 하더라도 반드시 기재하시고, 대여금뿐만 아니라 구상금, 손해배상금, 계금 등 <br>
								어떠한 명목으로라도 제3자로부터 받아야 할 돈이 있으면 기재하시기 바랍니다.
							</div>
						</div>
						<div class="form">
							<div class="form-title form-nocontent"><span></span></div>
							<div class="form-content form-nocontent"></div>
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
		block.find(".loan_claim_amount, .loan_collectible_amount").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
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
			claim_amount: this.unformatMoney(block.find(".loan_claim_amount").val()),
			collectible_amount: this.unformatMoney(block.find(".loan_collectible_amount").val()),
			is_seized: block.find(`input[name="loan_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".loan_receivables_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("대여금채권이 저장되었습니다.");
					block.find(".loan_receivables_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('loan_receivables', true);
					this.invalidateCache('loan_receivables');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "loan_receivables", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("대여금채권이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('loan_receivables');
					this.invalidateCache('loan_receivables');
				} else {
					alert(response.message || "대여금채권 삭제 실패");
				}
			},
			error: () => {
				alert("대여금채권 삭제 중 오류가 발생했습니다.");
			}
		});
	}
	
	// 8. 매출금채권 섹션
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
							<div class="form-title"><span>채무자명</span></div>
							<div class="form-content">
								<input type="text" class="sales_debtor_name" value="${data.debtor_name || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>채권금액</span></div>
							<div class="form-content">
								<input type="text" class="sales_claim_amount" value="${data.claim_amount ? this.formatMoney(data.claim_amount) : ""}" class="form-control">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>회수가능금액</span></div>
							<div class="form-content">
								<input type="text" class="sales_collectible_amount" value="${data.collectible_amount ? this.formatMoney(data.collectible_amount) : ""}" class="form-control">원
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
									<input type="radio" id="${blockId}_sales_seizure_no" name="sales_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
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
		block.find(".sales_claim_amount, .sales_collectible_amount").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
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
			claim_amount: this.unformatMoney(block.find(".sales_claim_amount").val()),
			collectible_amount: this.unformatMoney(block.find(".sales_collectible_amount").val()),
			is_seized: block.find(`input[name="sales_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".sales_receivables_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("매출금채권이 저장되었습니다.");
					block.find(".sales_receivables_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('sales_receivables', true);
					this.invalidateCache('sales_receivables');
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
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "sales_receivables", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("매출금채권이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('sales_receivables');
					this.invalidateCache('sales_receivables');
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
				<input type="hidden" class="severance_pay_asset_no" value="${data.asset_no || ''}">
				<input type="hidden" class="severance_pay_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>근무처</span></div>
							<div class="form-content">
								<input type="text" class="severance_workplace" value="${data.workplace || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>퇴직금예상액</span></div>
							<div class="form-content">
								<input type="text" class="severance_expected_amount" value="${data.expected_amount ? this.formatMoney(data.expected_amount) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>공제액</span></div>
							<div class="form-content">
								<input type="text" class="severance_deduction_amount" value="${data.deduction_amount ? this.formatMoney(data.deduction_amount) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								<input type="text" class="severance_liquidation_value" value="${data.liquidation_value ? this.formatMoney(data.liquidation_value) : ""}">원
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
									<input type="radio" id="${blockId}_severance_seizure_no" name="severance_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_severance_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								파산신청시에 퇴직하는 경우에 지급 받을 수 있는 퇴직금예상액(퇴직금이 없는 경우에는 그 취지)을<br>
								기재하여 주십시오. 만일 퇴직금채권을 담보로 하여 돈을 차용하였기 때문에 취업규칙상의 퇴직금보다<br> 
								적은 액수를 지급 받게 되는 경우에는 그러한 취지를 기재하여 주시기 바랍니다.
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
		block.find(".severance_expected_amount, .severance_deduction_amount, .severance_liquidation_value").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		
		// 자동 계산 로직
		block.find(".severance_expected_amount, .severance_deduction_amount").on("change", () => {
			const expectedAmount = this.unformatMoney(block.find(".severance_expected_amount").val()) || 0;
			const deductionAmount = this.unformatMoney(block.find(".severance_deduction_amount").val()) || 0;
			const liquidationValue = Math.max(0, expectedAmount - deductionAmount);
			block.find(".severance_liquidation_value").val(this.formatMoney(liquidationValue));
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
			workplace: block.find(".severance_workplace").val().trim(),
			expected_amount: this.unformatMoney(block.find(".severance_expected_amount").val()),
			deduction_amount: this.unformatMoney(block.find(".severance_deduction_amount").val()),
			liquidation_value: this.unformatMoney(block.find(".severance_liquidation_value").val()),
			is_seized: block.find(`input[name="severance_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".severance_pay_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("예상퇴직금이 저장되었습니다.");
					block.find(".severance_pay_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('severance_pay', true);
					this.invalidateCache('severance_pay');
				} else {
					alert(response.message || "예상퇴직금 저장 실패");
				}
			},
			error: () => {
				alert("예상퇴직금 저장 중 오류가 발생했습니다.");
			}
		});
	}

	deleteSeverancePayBlock(block) {
		if (!block.find(".severance_pay_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("severance_pay");
			return;
		}
		if (!confirm("예상퇴직금을 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".severance_pay_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "severance_pay", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("예상퇴직금이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('severance_pay');
					this.invalidateCache('severance_pay');
				} else {
					alert(response.message || "예상퇴직금 삭제 실패");
				}
			},
			error: () => {
				alert("예상퇴직금 삭제 중 오류가 발생했습니다.");
			}
		});
	}

	// 10. 기타 자산 섹션
	addOtherAssetBlock(data = {}) {
		this.assetCounters.other_assets++;
		const blockId = "other_asset_block_" + this.assetCounters.other_assets;
		const propertyNo = data.property_no || this.assetCounters.other_assets;
		const html = `
			<div class="asset-block other-asset-block" id="${blockId}">
				<input type="hidden" class="other_assets_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="other_assets_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>자산 내용</span></div>
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
									<input type="radio" id="${blockId}_other_seizure_no" name="other_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
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
		block.find(".other_liquidation_value").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		block.find(".other_asset_save_btn").on("click", () => this.saveOtherAssetBlock(block));
		block.find(".other_asset_delete_btn").on("click", () => this.deleteOtherAssetBlock(block));
	}

	saveOtherAssetBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".other_assets_asset_no").val();
		const data = {
			asset_type: "other_assets",
			case_no: caseNo,
			asset_content: block.find(".other_asset_content").val().trim(),
			liquidation_value: this.unformatMoney(block.find(".other_liquidation_value").val()),
			is_seized: block.find(`input[name="other_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".other_assets_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("기타 자산이 저장되었습니다.");
					block.find(".other_assets_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('other_assets', true);
					this.invalidateCache('other_assets');
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
		if (!block.find(".other_assets_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("other_assets");
			return;
		}
		if (!confirm("기타 자산을 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".other_assets_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "other_assets", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("기타 자산이 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('other_assets');
					this.invalidateCache('other_assets');
				} else {
					alert(response.message || "기타 자산 삭제 실패");
				}
			},
			error: () => {
				alert("기타 자산 삭제 중 오류가 발생했습니다.");
			}
		});
	}

	// 11. 재산 처분 정보
	addDisposedAssetBlock(data = {}) {
		this.assetCounters.disposed_assets++;
		const blockId = "disposed_assets_block_" + this.assetCounters.disposed_assets;
		const propertyNo = data.property_no || this.assetCounters.disposed_assets;
		const html = `
			<div class="asset-block disposed-assets-block" id="${blockId}">
				<input type="hidden" class="disposed_assets_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="disposed_assets_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>처분시기</span></div>
							<div class="form-content">
								<input type="date" class="disposal_date" value="${data.disposal_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>처분금액</span></div>
							<div class="form-content">
								<input type="text" class="disposal_amount" value="${data.disposal_amount ? this.formatMoney(data.disposal_amount) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>사용처</span></div>
							<div class="form-content">
								<input type="text" class="disposal_usage" value="${data.disposal_usage || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_disposed_seizure_yes" name="disposed_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_disposed_seizure_yes">유</label>
									<input type="radio" id="${blockId}_disposed_seizure_no" name="disposed_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_disposed_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								- 처분의 시기, 대가 및 대가의 사용처를 상세히 기재하여 주시기 바랍니다. 그리고 여기서 말하는<br>
								&nbsp;&nbsp;&nbsp;재산의 처분에는 보험의 해약, 정기예금 등의 해약, 퇴직에 따른 퇴직금수령 등도 포함합니다.<br>
								&nbsp;&nbsp;&nbsp;주거이전에 따른 임차보증금의 수령에 관하여는 다음의 12항에 기재하여 주시기 바랍니다.<br>
								- 특히 부동산이나 하나의 재산의 가액이 1,000만원 이상의 재산을 처분한 경우에는 처분시기와 대가를<br>
								&nbsp;&nbsp;&nbsp;증명할 수 있는 부동산등기사항전부증명서, 계약서사본, 영수증사본 등을 첨부하시기 바랍니다.<br>
								&nbsp;&nbsp;&nbsp;(경매로 처분된 경우에는 배당표 및 사건별수불내역서를 제출하여 주십시오.)
							</div>
						</div>
						<div class="form">
							<div class="form-title"></div>
							<div class="form-content btn-right">
								<button type="button" class="btn-delete disposed_asset_delete_btn">삭제</button>
								<button type="button" class="btn-save disposed_asset_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		$("#disposed_assets_container").append(html);
		const block = $("#" + blockId);
		block.find(".disposal_amount").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		block.find(".disposed_asset_save_btn").on("click", () => this.saveDisposedAssetBlock(block));
		block.find(".disposed_asset_delete_btn").on("click", () => this.deleteDisposedAssetBlock(block));
	}

	saveDisposedAssetBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".disposed_assets_asset_no").val();
		const data = {
			asset_type: "disposed_assets",
			case_no: caseNo,
			disposal_date: block.find(".disposal_date").val(),
			disposal_amount: this.unformatMoney(block.find(".disposal_amount").val()),
			disposal_usage: block.find(".disposal_usage").val().trim(),
			is_seized: block.find(`input[name="disposed_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".disposed_assets_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("처분 자산 정보가 저장되었습니다.");
					block.find(".disposed_assets_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('disposed_assets', true);
					this.invalidateCache('disposed_assets');
				} else {
					alert(response.message || "처분 자산 정보 저장 실패");
				}
			},
			error: () => {
				alert("처분 자산 정보 저장 중 오류가 발생했습니다.");
			}
		});
	}

	deleteDisposedAssetBlock(block) {
		if (!block.find(".disposed_assets_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("disposed_assets");
			return;
		}
		if (!confirm("처분 자산 정보를 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".disposed_assets_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "disposed_assets", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("처분 자산 정보가 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('disposed_assets');
					this.invalidateCache('disposed_assets');
				} else {
					alert(response.message || "처분 자산 정보 삭제 실패");
				}
			},
			error: () => {
				alert("처분 자산 정보 삭제 중 오류가 발생했습니다.");
			}
		});
	}

	// 12. 수령한 임차보증금 섹션
	addReceivedDepositBlock(data = {}) {
		this.assetCounters.received_deposit++;
		const blockId = "received_deposit_block_" + this.assetCounters.received_deposit;
		const propertyNo = data.property_no || this.assetCounters.received_deposit;
		const html = `
			<div class="asset-block received-deposit-block" id="${blockId}">
				<input type="hidden" class="received_deposit_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="received_deposit_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>수령일자</span></div>
							<div class="form-content">
								<input type="date" class="receipt_date" value="${data.receipt_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>임차물건</span></div>
							<div class="form-content">
								<input type="text" class="rental_property" value="${data.rental_property || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>임대차계약상<br>임차보증금액</span></div>
							<div class="form-content">
								<input type="text" class="contract_deposit" value="${data.contract_deposit ? this.formatMoney(data.contract_deposit) : ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>실제수령<br>임차보증금액</span></div>
							<div class="form-content">
								<input type="text" class="received_deposit" value="${data.received_deposit ? this.formatMoney(data.received_deposit) : ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>임차보증금 사용처</span></div>
							<div class="form-content">
								<input type="text" class="deposit_usage" value="${data.deposit_usage || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_received_seizure_yes" name="received_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_received_seizure_yes">유</label>
									<input type="radio" id="${blockId}_received_seizure_no" name="received_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_received_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title"></div>
							<div class="form-content btn-right">
								<button type="button" class="btn-delete received_deposit_delete_btn">삭제</button>
								<button type="button" class="btn-save received_deposit_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		$("#received_deposit_assets_container").append(html);
		const block = $("#" + blockId);
		block.find(".contract_deposit, .received_deposit").on("input", (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		block.find(".received_deposit_save_btn").on("click", () => this.saveReceivedDepositBlock(block));
		block.find(".received_deposit_delete_btn").on("click", () => this.deleteReceivedDepositBlock(block));
	}

	saveReceivedDepositBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".received_deposit_asset_no").val();
		const data = {
			asset_type: "received_deposit",
			case_no: caseNo,
			receipt_date: block.find(".receipt_date").val(),
			rental_property: block.find(".rental_property").val().trim(),
			contract_deposit: this.unformatMoney(block.find(".contract_deposit").val()),
			received_deposit: this.unformatMoney(block.find(".received_deposit").val()),
			deposit_usage: block.find(".deposit_usage").val().trim(),
			is_seized: block.find(`input[name="received_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".received_deposit_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("수령한 임차보증금 정보가 저장되었습니다.");
					block.find(".received_deposit_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('received_deposit', true);
					this.invalidateCache('received_deposit');
				} else {
					alert(response.message || "수령한 임차보증금 정보 저장 실패");
				}
			},
			error: () => {
				alert("수령한 임차보증금 정보 저장 중 오류가 발생했습니다.");
			}
		});
	}

	deleteReceivedDepositBlock(block) {
		if (!block.find(".received_deposit_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("received_deposit");
			return;
		}
		if (!confirm("수령한 임차보증금 정보를 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".received_deposit_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "received_deposit", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("수령한 임차보증금 정보가 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('received_deposit');
					this.invalidateCache('received_deposit');
				} else {
					alert(response.message || "수령한 임차보증금 정보 삭제 실패");
				}
			},
			error: () => {
				alert("수령한 임차보증금 정보 삭제 중 오류가 발생했습니다.");
			}
		});
	}

	// 13. 이혼 재산분할 섹션
	addDivorcePropertyBlock(data = {}) {
		this.assetCounters.divorce_property++;
		const blockId = "divorce_property_block_" + this.assetCounters.divorce_property;
		const propertyNo = data.property_no || this.assetCounters.divorce_property;
		const html = `
			<div class="asset-block divorce-property-block" id="${blockId}">
				<input type="hidden" class="divorce_property_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="divorce_property_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>분여 재산</span></div>
							<div class="form-content">
								<input type="text" class="settlement_property" value="${data.settlement_property || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>시기</span></div>
							<div class="form-content">
								<input type="date" class="divorce_date" value="${data.divorce_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>이혼시점</span></div>
							<div class="form-content">
								<input type="text" class="divorce_timing" value="${data.divorce_timing || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_divorce_seizure_yes" name="divorce_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_divorce_seizure_yes">유</label>
									<input type="radio" id="${blockId}_divorce_seizure_no" name="divorce_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_divorce_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								- 제출 자료<br>
								&nbsp;&nbsp;&nbsp;① 이혼에 따라 배우자에게 분여(할)한 모든 재산의 내역<br>
								&nbsp;&nbsp;&nbsp;② 협의이혼 시 미성년 자녀가 있는 경우, 양육비부담조서 제출<br>
								&nbsp;&nbsp;&nbsp;③ 재판상이혼의 경우, 판결서, 조정조서 등 재판서 및 확정증명 제출<br>
								- 이혼을 증명할 소명자료 제출<br>
								&nbsp;&nbsp;&nbsp;① 시, 구, 읍(면) 등 가족관계등록관서에 이혼신고를 하여 가족관계등록부에 기록된 경우 ㅣ<br>
								&nbsp;&nbsp;&nbsp;혼인관계증명서(상세)<br>
								&nbsp;&nbsp;&nbsp;② 최근 2년 이내에 재판상 이혼을 한 경우로 아직 시, 구, 읍(면)등 가족관계등록관서에 이혼신고를 하지 않은 경우 ㅣ<br>
								&nbsp;&nbsp;&nbsp;재판상 이혼과 관련한 재판서의 등본(조정ㆍ화해가 성립된 경우에는 그에 대한 조서 등본) 및 확정증명을 제출
							</div>
						</div>
						<div class="form">
							<div class="form-title"></div>
							<div class="form-content btn-right">
								<button type="button" class="btn-delete divorce_property_delete_btn">삭제</button>
								<button type="button" class="btn-save divorce_property_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		$("#divorce_property_assets_container").append(html);
		const block = $("#" + blockId);
		block.find(".divorce_property_save_btn").on("click", () => this.saveDivorcePropertyBlock(block));
		block.find(".divorce_property_delete_btn").on("click", () => this.deleteDivorcePropertyBlock(block));
	}

	saveDivorcePropertyBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".divorce_property_asset_no").val();
		const data = {
			asset_type: "divorce_property",
			case_no: caseNo,
			divorce_date: block.find(".divorce_date").val(),
			settlement_property: block.find(".settlement_property").val().trim(),
			divorce_timing: block.find(".divorce_timing").val().trim(),
			is_seized: block.find(`input[name="divorce_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".divorce_property_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("이혼 재산분할 정보가 저장되었습니다.");
					block.find(".divorce_property_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('divorce_property', true);
					this.invalidateCache('divorce_property');
				} else {
					alert(response.message || "이혼 재산분할 정보 저장 실패");
				}
			},
			error: () => {
				alert("이혼 재산분할 정보 저장 중 오류가 발생했습니다.");
			}
		});
	}
	deleteDivorcePropertyBlock(block) {
		if (!block.find(".divorce_property_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("divorce_property");
			return;
		}
		if (!confirm("이혼 재산분할 정보를 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".divorce_property_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "divorce_property", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("이혼 재산분할 정보가 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('divorce_property');
					this.invalidateCache('divorce_property');
				} else {
					alert(response.message || "이혼 재산분할 정보 삭제 실패");
				}
			},
			error: () => {
				alert("이혼 재산분할 정보 삭제 중 오류가 발생했습니다.");
			}
		});
	}

	// 14. 상속재산 섹션
	addInheritedPropertyBlock(data = {}) {
		this.assetCounters.inherited_property++;
		const blockId = "inherited_property_block_" + this.assetCounters.inherited_property;
		const propertyNo = data.property_no || this.assetCounters.inherited_property;
		const html = `
			<div class="asset-block inherited-property-block" id="${blockId}">
				<input type="hidden" class="inherited_property_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="inherited_property_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>상속일자</span></div>
							<div class="form-content">
								<input type="date" class="inheritance_date" value="${data.inheritance_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>피상속인 구분</span></div>
							<div class="form-content">
								<select class="deceased_type" name="deceased_type">
									<option value="부" ${(data.deceased_type==="부") ? "selected" : ""}>부</option>
									<option value="모" ${(data.deceased_type==="모") ? "selected" : ""}>모</option>
									<option value="기타" ${(data.deceased_type==="기타") ? "selected" : ""}>기타</option>
								</select>
								&nbsp;의 사망에 의한 상속
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>상속상황</span></div>
							<div class="form-content">
								<select class="inheritance_status form-control-long" name="inheritance_status">
									<option value="㉠ 상속재산이 전혀 없음" ${(data.inheritance_status==="㉠ 상속재산이 전혀 없음") ? "selected" : ""}>㉠ 상속재산이 전혀 없음</option>
									<option value="㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음" ${(data.inheritance_status==="㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음") ? "selected" : ""}>㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음</option>
									<option value="㉢ 신청인이 전부 또는 일부를 상속하였음" ${(data.inheritance_status==="㉢ 신청인이 전부 또는 일부를 상속하였음") ? "selected" : ""}>㉢ 신청인이 전부 또는 일부를 상속하였음</option>
								</select>
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>주된 상속재산</span></div>
							<div class="form-content">
								<input type="text" class="main_inheritance_property" value="${data.main_inheritance_property || ""}" placeholder="㉡ 또는 ㉢항 선택시 기재하여 주십시오.">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>취득경위</span></div>
							<div class="form-content">
								<input type="text" class="acquisition_process" value="${data.acquisition_process || ""}" placeholder="㉡항 선택시 다른 상속인이 주된 상속재산을 취득하게 된 경위를 기재하여 주십시오.">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>압류 유무</span></div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_inherited_seizure_yes" name="inherited_seizure_${blockId}" value="Y" ${data.hasOwnProperty("is_seized") && data.is_seized==="Y" ? "checked" : ""}>
									<label for="${blockId}_inherited_seizure_yes">유</label>
									<input type="radio" id="${blockId}_inherited_seizure_no" name="inherited_seizure_${blockId}" value="N" ${data.hasOwnProperty("is_seized") && data.is_seized==="N" ? "checked" : "checked"}>
									<label for="${blockId}_inherited_seizure_no">무</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title"></div>
							<div class="form-content btn-right">
								<button type="button" class="btn-delete inherited_property_delete_btn">삭제</button>
								<button type="button" class="btn-save inherited_property_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		$("#inherited_property_assets_container").append(html);
		const block = $("#" + blockId);
		
		// 상속상황 선택에 따라 필드 활성화/비활성화
		block.find(".inheritance_status").on("change", function() {
			const value = $(this).val();
			if (value.startsWith("㉠")) {
				block.find(".main_inheritance_property").prop("disabled", true).val("");
				block.find(".acquisition_process").prop("disabled", true).val("");
			} else if (value.startsWith("㉡")) {
				block.find(".main_inheritance_property").prop("disabled", false);
				block.find(".acquisition_process").prop("disabled", false);
			} else if (value.startsWith("㉢")) {
				block.find(".main_inheritance_property").prop("disabled", false);
				block.find(".acquisition_process").prop("disabled", true).val("");
			}
		});
		
		// 초기 상태 설정
		block.find(".inheritance_status").trigger("change");
		
		block.find(".inherited_property_save_btn").on("click", () => this.saveInheritedPropertyBlock(block));
		block.find(".inherited_property_delete_btn").on("click", () => this.deleteInheritedPropertyBlock(block));
	}

	saveInheritedPropertyBlock(block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(".inherited_property_asset_no").val();
		const data = {
			asset_type: "inherited_property",
			case_no: caseNo,
			inheritance_date: block.find(".inheritance_date").val(),
			deceased_type: block.find(".deceased_type").val(),
			inheritance_status: block.find(".inheritance_status").val(),
			main_inheritance_property: block.find(".main_inheritance_property").val().trim(),
			acquisition_process: block.find(".acquisition_process").val().trim(),
			is_seized: block.find(`input[name="inherited_seizure_${block.attr("id")}"]:checked`).val() || "N",
			property_no: block.find(".inherited_property_property_no").val()
		};
		if (assetNo) data.asset_no = assetNo;
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("상속재산 정보가 저장되었습니다.");
					block.find(".inherited_property_asset_no").val(response.data.asset_no);
					this.updateAssetSummary('inherited_property', true);
					this.invalidateCache('inherited_property');
				} else {
					alert(response.message || "상속재산 정보 저장 실패");
				}
			},
			error: () => {
				alert("상속재산 정보 저장 중 오류가 발생했습니다.");
			}
		});
	}

	deleteInheritedPropertyBlock(block) {
		if (!block.find(".inherited_property_asset_no").val()) {
			block.remove();
			this.checkEmptyBlock("inherited_property");
			return;
		}
		if (!confirm("상속재산 정보를 삭제하시겠습니까?")) return;
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(".inherited_property_property_no").val();
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: "inherited_property", case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert("상속재산 정보가 삭제되었습니다.");
					block.remove();
					this.checkEmptyContainer('inherited_property');
					this.invalidateCache('inherited_property');
				} else {
					alert(response.message || "상속재산 정보 삭제 실패");
				}
			},
			error: () => {
				alert("상속재산 정보 삭제 중 오류가 발생했습니다.");
			}
		});
	}
	
	// 재산목록 요약표 저장
	saveAssetSummary() {
		const data = {
			case_no: window.currentCaseNo,
			cash_exists: $('input[name="sum_cash_exists"]:checked').val(),
			deposit_exists: $('input[name="sum_deposit_exists"]:checked').val(),
			insurance_exists: $('input[name="sum_insurance_exists"]:checked').val(),
			rent_deposit_exists: $('input[name="sum_rent_deposit_exists"]:checked').val(),
			loan_receivables_exists: $('input[name="sum_loan_receivables_exists"]:checked').val(),
			sales_receivables_exists: $('input[name="sum_sales_receivables_exists"]:checked').val(),
			severance_pay_exists: $('input[name="sum_severance_pay_exists"]:checked').val(),
			real_estate_exists: $('input[name="sum_real_estate_exists"]:checked').val(),
			vehicle_exists: $('input[name="sum_vehicle_exists"]:checked').val(),
			other_assets_exists: $('input[name="sum_other_assets_exists"]:checked').val(),
			disposed_assets_exists: $('input[name="sum_disposed_assets_exists"]:checked').val(),
			received_deposit_exists: $('input[name="sum_received_deposit_exists"]:checked').val(),
			divorce_property_exists: $('input[name="sum_divorce_property_exists"]:checked').val(),
			inherited_property_exists: $('input[name="sum_inherited_property_exists"]:checked').val()
		};
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/assets/asset_summary_api.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('재산목록 요약표가 저장되었습니다.');
				} else {
					alert(response.message || '재산목록 요약표 저장 실패');
				}
			},
			error: () => {
				alert('재산목록 요약표 저장 중 오류가 발생했습니다.');
			}
		});
	}

	// 자산 요약표 업데이트
	updateAssetSummary(type, exists) {
		const value = exists ? 'Y' : 'N';
		$(`input[name="sum_${type}_exists"][value="${value}"]`).prop('checked', true);
	}

	// 재산목록 요약표 데이터 로드
	loadAssetSummary() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/assets/asset_summary_api.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					const data = response.data;
					
					// 각 자산 유형의 라디오 버튼 설정
					$(`input[name="sum_cash_exists"][value="${data.cash_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_deposit_exists"][value="${data.deposit_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_insurance_exists"][value="${data.insurance_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_rent_deposit_exists"][value="${data.rent_deposit_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_loan_receivables_exists"][value="${data.loan_receivables_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_sales_receivables_exists"][value="${data.sales_receivables_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_severance_pay_exists"][value="${data.severance_pay_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_real_estate_exists"][value="${data.real_estate_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_vehicle_exists"][value="${data.vehicle_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_other_assets_exists"][value="${data.other_assets_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_disposed_assets_exists"][value="${data.disposed_assets_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_received_deposit_exists"][value="${data.received_deposit_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_divorce_property_exists"][value="${data.divorce_property_exists || 'N'}"]`).prop('checked', true);
					$(`input[name="sum_inherited_property_exists"][value="${data.inherited_property_exists || 'N'}"]`).prop('checked', true);
				} else {
					// 기본값으로 모두 'N'(없음)으로 설정
					$('input[name^="sum_"][value="N"]').prop('checked', true);
				}
			},
			error: (xhr, status, error) => {
				console.error('요약표 데이터 로드 오류:', error);
				// 오류 시 기본값으로 모두 'N'(없음)으로 설정
				$('input[name^="sum_"][value="N"]').prop('checked', true);
			}
		});
	}

	deleteAssetSummary() {
		if (!confirm("재산목록 요약표를 초기화하시겠습니까?")) {
			return;
		}
		
		// 모든 라디오 버튼을 '없음'(N)으로 설정
		$('input[name^="sum_"][value="N"]').prop('checked', true);
		
		// 서버에 변경사항 저장
		const data = {
			case_no: window.currentCaseNo,
			cash_exists: 'N',
			deposit_exists: 'N',
			insurance_exists: 'N',
			rent_deposit_exists: 'N',
			loan_receivables_exists: 'N',
			sales_receivables_exists: 'N',
			severance_pay_exists: 'N',
			real_estate_exists: 'N',
			vehicle_exists: 'N',
			other_assets_exists: 'N',
			disposed_assets_exists: 'N',
			received_deposit_exists: 'N',
			divorce_property_exists: 'N',
			inherited_property_exists: 'N'
		};
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/assets/asset_summary_api.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('재산목록 요약표가 초기화되었습니다.');
				} else {
					alert(response.message || '재산목록 요약표 초기화 실패');
				}
			},
			error: () => {
				alert('재산목록 요약표 초기화 중 오류가 발생했습니다.');
			}
		});
	}

	// 빈 컨테이너 체크 및 처리
	checkEmptyContainer(type) {
		// 컨테이너가 비어있고 요약표가 'Y'로 되어 있으면 'N'으로 업데이트
		const container = this.getContainerId(type);
		if ($(container).children().length === 0) {
			$(`input[name="sum_${type}_exists"][value="N"]`).prop('checked', true);
			this.checkEmptyBlock(type);
		}
	}

	// 컨테이너 ID 가져오기
	getContainerId(type) {
		const containerMap = {
			cash: "#cash_assets_container",
			deposit: "#deposit_assets_container",
			insurance: "#insurance_assets_container",
			vehicle: "#vehicle_assets_container",
			rent_deposit: "#rent_deposit_assets_container",
			real_estate: "#real_estate_assets_container",
			loan_receivables: "#loan_receivables_assets_container",
			sales_receivables: "#sales_receivables_assets_container",
			severance_pay: "#severance_pay_assets_container",
			other_assets: "#other_assets_container",
			disposed_assets: "#disposed_assets_container",
			received_deposit: "#received_deposit_assets_container",
			divorce_property: "#divorce_property_assets_container",
			inherited_property: "#inherited_property_assets_container"
		};
		return containerMap[type] || "";
	}

	// 빈 블록 체크 및 추가
	checkEmptyBlock(type) {
		const containerId = this.getContainerId(type);
		if ($(containerId).children().length === 0) {
			this.addAssetBlock(type);
		}
	}

	invalidateCache(type) {
		const cacheKey = `${type}_${window.currentCaseNo}`;
		this.cache.delete(cacheKey);
	}

	// 금액 형식 변환 유틸리티 함수
	formatMoney(amount) {
		if (!amount) return "0";
		return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

	unformatMoney(str) {
		if (!str) return 0;
		return parseInt(str.replace(/,/g, "")) || 0;
	}

	hasUnsavedChanges() {
		// 변경사항 확인 로직 (현재는 항상 false 반환)
		return false;
	}
}

// 페이지 로드 시 AssetManager 인스턴스 생성
$(document).ready(() => {
	if (typeof window.currentCaseNo !== 'undefined' && window.currentCaseNo) {
		window.assetManager = new AssetManager();
	}
});