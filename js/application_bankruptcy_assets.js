class AssetManager {
	constructor() {
		// 각 자산 유형별 카운터
		this.assetCounters = {};
		// 자산 유형과 컨테이너 ID 매핑
		this.containerMap = {
			cash: "#cash_assets_container",
			deposit: "#deposit_assets_container",
			insurance: "#insurance_assets_container",
			rent_deposit: "#rent_deposit_assets_container",
			loan_receivables: "#loan_receivables_assets_container",
			sales_receivables: "#sales_receivables_assets_container",
			severance_pay: "#severance_pay_assets_container",
			real_estate: "#real_estate_assets_container",
			vehicle: "#vehicle_assets_container",
			other_assets: "#other_assets_container",
			disposed_assets: "#disposed_assets_container",
			received_deposit: "#received_deposit_assets_container",
			divorce_property: "#divorce_property_assets_container",
			inherited_property: "#inherited_property_assets_container"
		};
		// 캐시 시스템
		this.cache = new Map();
		// 각 자산 유형별 템플릿 함수 매핑
		this.templateMap = {
			cash: this.cashTemplate,
			deposit: this.depositTemplate,
			insurance: this.insuranceTemplate,
			rent_deposit: this.rentDepositTemplate,
			loan_receivables: this.loanReceivablesTemplate,
			sales_receivables: this.salesReceivablesTemplate,
			severance_pay: this.severancePayTemplate,
			real_estate: this.realEstateTemplate,
			vehicle: this.vehicleTemplate,
			other_assets: this.otherAssetTemplate,
			disposed_assets: this.disposedAssetTemplate,
			received_deposit: this.receivedDepositTemplate,
			divorce_property: this.divorcePropertyTemplate,
			inherited_property: this.inheritedPropertyTemplate
		};
		
		this.initialize();
	}

	initialize() {
		try {
			this.initializeAssetCounters();
			this.initializeEventHandlers();
			this.loadAllAssets();
		} catch (error) {
			console.error("초기화 실패:", error);
			alert("초기화 중 오류가 발생했습니다.");
		}
	}

	initializeAssetCounters() {
		// 모든 자산 유형에 대해 카운터 초기화
		Object.keys(this.containerMap).forEach(type => {
			this.assetCounters[type] = 0;
		});
	}

	initializeEventHandlers() {
		// 각 자산 유형별 추가 버튼 이벤트 바인딩
		Object.keys(this.containerMap).forEach(type => {
			$(`#add_${type}_asset`).on("click", () => this.addAssetBlock(type));
		});
		
		// 소액임차인 최우선 변제금 기준 팝업 이벤트
		$('#exempt_rent_criteria').on('click', () => {
			window.open('/adm/api/application_bankruptcy/assets/exempt_rent_criteria.php', 
				'소액임차인 최우선 변제금의 범위와 기준', 
				'width=1000,height=500,scrollbars=yes');
		});
		
		// 이벤트 위임을 사용하여 저장 및 삭제 버튼 이벤트 처리
		$(document).on('click', '.btn-save', (e) => {
			const block = $(e.target).closest('.asset-block');
			const type = this.getAssetTypeFromBlock(block);
			if (type) this.saveAssetBlock(type, block);
		});
		
		$(document).on('click', '.btn-delete', (e) => {
			const block = $(e.target).closest('.asset-block');
			const type = this.getAssetTypeFromBlock(block);
			if (type) this.deleteAssetBlock(type, block);
		});
		
		// 금액 입력 필드에 대한 이벤트 위임
		$(document).on('input', 'input[data-type="money"]', (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});
		
		// 창 닫기 전 저장되지 않은 변경사항 확인
		window.addEventListener("beforeunload", (e) => {
			if (this.hasUnsavedChanges()) {
				e.preventDefault();
				e.returnValue = "저장되지 않은 변경사항이 있습니다. 정말 나가시겠습니까?";
			}
		});
	}

	getAssetTypeFromBlock(block) {
		// CSS 클래스에서 자산 유형 추출
		const classes = block.attr('class').split(' ');
		for (let cls of classes) {
			if (cls.endsWith('-block')) {
				const type = cls.replace('-block', '');
				return Object.keys(this.containerMap).find(key => key.replace('_', '-') === type);
			}
		}
		return null;
	}

	loadAllAssets() {
		// 모든 자산 유형에 대해 데이터 로드
		Object.keys(this.containerMap).forEach(type => {
			this.loadAsset(type);
		});
	}

	loadAsset(type) {
		const cacheKey = `${type}_${window.currentCaseNo}`;
		// 캐시 확인 (5초 이내의 데이터만 유효)
		if (this.cache.has(cacheKey)) {
			const cached = this.cache.get(cacheKey);
			if (Date.now() - cached.timestamp < 5000) {
				this.populateAssetBlocks(type, cached.data);
				return;
			}
		}
		
		// 캐시가 없거나 만료된 경우 서버에서 데이터 로드
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
					// 데이터 캐싱
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
		const containerId = this.containerMap[type];
		$(containerId).empty();

		if (assets.length > 0) {
			assets.forEach(asset => {
				this.addAssetBlock(type, asset);
			});
		} else {
			// 데이터가 없으면 빈 블록 생성
			this.addAssetBlock(type);
		}
	}

	addAssetBlock(type, data = {}) {
		this.assetCounters[type]++;
		const blockId = `${type.replace('_', '-')}_block_${this.assetCounters[type]}`;
		const propertyNo = data.property_no || this.assetCounters[type];
		
		// 템플릿 함수 호출하여 HTML 생성
		const html = this.templateMap[type].call(this, blockId, propertyNo, data);
		
		// 컨테이너에 HTML 추가
		$(this.containerMap[type]).append(html);
		
		// 금액 입력 필드 이벤트 설정
		const block = $(`#${blockId}`);
		
		// 금액 입력 필드 초기화
		block.find('input[data-type="money"]').each((i, el) => {
			if ($(el).val()) {
				$(el).val(this.formatMoney($(el).val()));
			}
		});
	}

	saveAssetBlock(type, block) {
		const caseNo = window.currentCaseNo;
		const assetNo = block.find(`.${type}_asset_no`).val();
		
		// 기본 데이터 세팅
		const data = {
			asset_type: type,
			case_no: caseNo,
			property_no: block.find(`.${type}_property_no`).val()
		};
		
		// 유형별 데이터 추가
		switch (type) {
			case 'cash':
				Object.assign(data, {
					property_detail: block.find(".cash_property_detail").val().trim(),
					liquidation_value: this.unformatMoney(block.find(".cash_liquidation_value").val()),
					is_seized: block.find(`input[name^="cash_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'deposit':
				Object.assign(data, {
					bank_name: block.find(".deposit_bank_name").val().trim(),
					account_number: block.find(".deposit_account_number").val().trim(),
					deposit_amount: this.unformatMoney(block.find(".deposit_amount").val()),
					deduction_amount: this.unformatMoney(block.find(".deposit_deduction_amount").val()),
					is_seized: block.find(`input[name^="deposit_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'insurance':
				Object.assign(data, {
					company_name: block.find(".insurance_company_name").val().trim(),
					securities_number: block.find(".insurance_securities_number").val().trim(),
					refund_amount: this.unformatMoney(block.find(".insurance_refund_amount").val()),
					is_coverage: block.find(`input[name^="insurance_coverage_"]:checked`).val() || "N",
					explanation: block.find(".insurance_explanation").val().trim(),
					is_seized: block.find(`input[name^="insurance_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'vehicle':
				Object.assign(data, {
					vehicle_info: block.find(".vehicle_info").val().trim(),
					is_spouse: block.find(".vehicle_spouse_owned").is(":checked") ? 1 : 0,
					security_type: block.find(".vehicle_security_type").val().trim(),
					max_bond: this.unformatMoney(block.find(".vehicle_max_bond").val()),
					expected_value: this.unformatMoney(block.find(".vehicle_expected_value").val()),
					financial_balance: this.unformatMoney(block.find(".vehicle_financial_balance").val()),
					liquidation_value: this.unformatMoney(block.find(".vehicle_liquidation_value").val()),
					explanation: block.find(".vehicle_liquidation_explain").val().trim(),
					is_manual_calc: block.find(".vehicle_manual_calc").is(":checked") ? "Y" : "N",
					is_seized: block.find(`input[name^="vehicle_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'rent_deposit':
				Object.assign(data, {
					rent_location: block.find(".rent_location").val().trim(),
					is_business_place: block.find(".rent_business_place").is(":checked") ? "Y" : "N",
					contract_deposit: this.unformatMoney(block.find(".rent_contract_deposit").val()),
					is_deposit_spouse: block.find(".rent_deposit_spouse").is(":checked") ? 1 : 0,
					monthly_rent: this.unformatMoney(block.find(".rent_monthly_rent").val()),
					is_monthly_spouse: block.find(".rent_monthly_spouse").is(":checked") ? 1 : 0,
					refund_deposit: this.unformatMoney(block.find(".rent_refund_deposit").val()),
					difference_reason: block.find(".rent_difference_reason").val().trim(),
					priority_deposit: this.unformatMoney(block.find(".rent_priority_deposit").val()),
					liquidation_value: this.unformatMoney(block.find(".rent_liquidation_value").val()),
					explanation: block.find(".rent_liquidation_explain").val().trim(),
					is_seized: block.find(`input[name^="rent_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'real_estate':
				Object.assign(data, {
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
					is_seized: block.find(`input[name^="property_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'loan_receivables':
				Object.assign(data, {
					debtor_name: block.find(".loan_debtor_name").val().trim(),
					has_evidence: block.find(".loan_evidence_attached").is(":checked") ? "Y" : "N",
					liquidation_value: this.unformatMoney(block.find(".loan_liquidation_value").val()),
					is_seized: block.find(`input[name^="loan_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'sales_receivables':
				Object.assign(data, {
					debtor_name: block.find(".sales_debtor_name").val().trim(),
					has_evidence: block.find(".sales_evidence_attached").is(":checked") ? "Y" : "N",
					liquidation_value: this.unformatMoney(block.find(".sales_liquidation_value").val()),
					is_seized: block.find(`input[name^="sales_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'severance_pay':
				Object.assign(data, {
					is_public: block.find(`input[name^="severance_is_public_"]:checked`).val() || "N",
					has_pension: block.find(".severance_pension").is(":checked") ? "Y" : "N",
					workplace: block.find(".severance_workplace").val().trim(),
					expected_severance: this.unformatMoney(block.find(".severance_expected_amount").val()),
					deduction_amount: this.unformatMoney(block.find(".severance_deduction_amount").val()),
					liquidation_value: this.unformatMoney(block.find(".severance_liquidation_value").val()),
					is_seized: block.find(`input[name^="severance_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'other_assets':
				Object.assign(data, {
					asset_content: block.find(".other_asset_content").val().trim(),
					liquidation_value: this.unformatMoney(block.find(".other_liquidation_value").val()),
					is_seized: block.find(`input[name^="other_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'disposed_assets':
				Object.assign(data, {
					disposal_date: block.find(".disposed_date").val(),
					property_type: block.find(".disposed_property_type").val().trim(),
					disposal_amount: this.unformatMoney(block.find(".disposed_amount").val()),
					disposal_reason: block.find(".disposed_reason").val().trim(),
					recipient: block.find(".disposed_recipient").val().trim()
				});
				break;
			case 'received_deposit':
				Object.assign(data, {
					receipt_date: block.find(".received_date").val(),
					lessor: block.find(".received_lessor").val().trim(),
					location: block.find(".received_location").val().trim(),
					deposit_amount: this.unformatMoney(block.find(".received_deposit_amount").val()),
					note: block.find(".received_note").val().trim()
				});
				break;
			case 'divorce_property':
				Object.assign(data, {
					divorce_date: block.find(".divorce_date").val(),
					spouse_name: block.find(".divorce_spouse").val().trim(),
					settlement_date: block.find(".divorce_settlement_date").val(),
					property_type: block.find(".divorce_property_type").val().trim(),
					property_amount: this.unformatMoney(block.find(".divorce_property_amount").val())
				});
				break;
			case 'inherited_property':
				Object.assign(data, {
					heir_name: block.find(".inherited_heir").val().trim(),
					deceased_name: block.find(".inherited_deceased").val().trim(),
					inheritance_date: block.find(".inherited_start_date").val(),
					property_type: block.find(".inherited_property_type").val().trim(),
					property_amount: this.unformatMoney(block.find(".inherited_property_amount").val())
				});
				break;
		}
		
		// asset_no가 있으면 추가
		if (assetNo) data.asset_no = assetNo;
		
		// Ajax 요청 보내기
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "POST",
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert(`${this.getAssetTypeName(type)} 저장되었습니다.`);
					block.find(`.${type}_asset_no`).val(response.data.asset_no);
					// 캐시 무효화
					this.invalidateCache(type);
				} else {
					alert(response.message || `${this.getAssetTypeName(type)} 저장 실패`);
				}
			},
			error: () => {
				alert(`${this.getAssetTypeName(type)} 저장 중 오류가 발생했습니다.`);
			}
		});
	}

	deleteAssetBlock(type, block) {
		// 저장되지 않은 블록인 경우 바로 삭제
		if (!block.find(`.${type}_asset_no`).val()) {
			block.remove();
			this.checkEmptyBlock(type);
			return;
		}
		
		// 삭제 확인
		if (!confirm(`${this.getAssetTypeName(type)}을(를) 삭제하시겠습니까?`)) return;
		
		const caseNo = window.currentCaseNo;
		const propertyNo = block.find(`.${type}_property_no`).val();
		
		// Ajax 요청 보내기
		$.ajax({
			url: "/adm/api/application_bankruptcy/assets/asset_api.php",
			type: "DELETE",
			data: { asset_type: type, case_no: caseNo, property_no: propertyNo },
			processData: true,
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			dataType: "json",
			success: (response) => {
				if (response.success) {
					alert(`${this.getAssetTypeName(type)} 삭제되었습니다.`);
					block.remove();
					this.checkEmptyBlock(type);
					// 캐시 무효화
					this.invalidateCache(type);
				} else {
					alert(response.message || `${this.getAssetTypeName(type)} 삭제 실패`);
				}
			},
			error: () => {
				alert(`${this.getAssetTypeName(type)} 삭제 중 오류가 발생했습니다.`);
			}
		});
	}

	checkEmptyBlock(type) {
		// 컨테이너가 비어있으면 빈 블록 추가
		const containerId = this.containerMap[type];
		if ($(containerId).children().length === 0) {
			this.addAssetBlock(type);
		}
	}

	invalidateCache(type) {
		const cacheKey = `${type}_${window.currentCaseNo}`;
		this.cache.delete(cacheKey);
	}

	// 자산 유형별 이름 반환
	getAssetTypeName(type) {
		const nameMap = {
			cash: '현금 자산이',
			deposit: '예금 자산이',
			insurance: '보험 자산이',
			rent_deposit: '임차보증금 자산이',
			loan_receivables: '대여금채권이',
			sales_receivables: '매출금채권이',
			severance_pay: '예상퇴직금 자산이',
			real_estate: '부동산 자산이',
			vehicle: '자동차 자산이',
			other_assets: '기타 자산이',
			disposed_assets: '재산 처분 정보가',
			received_deposit: '수령한 임차보증금 정보가',
			divorce_property: '이혼 재산분할 정보가',
			inherited_property: '상속재산 정보가'
		};
		return nameMap[type] || '자산이';
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

	// 아래부터는 각 자산 유형별 HTML 템플릿 함수들

	cashTemplate(blockId, propertyNo, data = {}) {
		return `
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
							<div class="form-title"></div>
							<div class="form-content">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								<input type="text" class="cash_liquidation_value" data-type="money" value="${data.liquidation_value || ''}">원
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
	}

	depositTemplate(blockId, propertyNo, data = {}) {
		return `
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
							<div class="form-title"><span>잔고</span></div>
							<div class="form-content">
								<input type="text" class="deposit_amount" data-type="money" value="${data.deposit_amount || ""}" placeholder="총 합계액">원
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span></span></div>
							<div class="form-content form-nocontent"></div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								- 은행 이외의 금융기관에 대한 것도 포함합니다.<br>
								- 예금잔고가 소액이라도 반드시 기재하고 파산신청시의 잔고(정기예금분을 포함)와 최종 금융거래일로부터<br>
								&nbsp;&nbsp;&nbsp;과거 1년간의 입출금이 기재된 통장 사본 또는 예금거래내역서를 첨부하여 주십시오.<br>
								&nbsp;&nbsp;&nbsp;(공과금, 통신료, 카드사용, 급여이체 등이 기재된 통장 사본 또는 예금거래내역서를 제출,<br>
								&nbsp;&nbsp;&nbsp;가족명의의 계좌로 거래하였다면 그 계좌에 관한 통장 사본 또는 예금거래내역서를 제출)
							</div>
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
	}

	insuranceTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block insurance-block" id="${blockId}">
				<input type="hidden" class="insurance_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="insurance_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
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
							<div class="form-title"><span>해약반환금</span></div>
							<div class="form-content">
								<input type="text" class="insurance_refund_amount" data-type="money" value="${data.refund_amount || ""}" placeholder="총 합계액">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content"></div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-title-3"><span>주의사항</span></div>
							<div class="form-content form-content-3">
								- 파산신청 당시에 가입하고 있는 보험은 해약환급금 없는 경우에도 반드시 전부 기재하여 주십시오.<br>
								- 생명보험협회에서 발급받는 채무자에 대한 생존자 보험가입내역조회를 첨부하여 주시고,<br>
								&nbsp;&nbsp;&nbsp;그러한 보험가입내역조회에 기재된 생명보험(손해보험, 자동차보험, 운전자보험, 여행자ㆍ단체보험,<br> 
								&nbsp;&nbsp;&nbsp;주말휴일상해보험은 제외)의 해지ㆍ실효ㆍ유지 여부 및 예상해약환급금 내역을 기재한<br>
								&nbsp;&nbsp;&nbsp;각 보험회사 작성의 증명서도 첨부하여 주십시오.
							</div>
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
	}

	// 나머지 템플릿 함수들을 동일한 방식으로 구현...
	rentDepositTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="rent_contract_deposit input86" data-type="money" value="${data.contract_deposit || ""}" class="form-control form-content-justify">원
							</div>
							<div class="form-content checkbox-right">
								<input type="checkbox" id="${blockId}_rent_deposit_spouse" class="rent_deposit_spouse" ${data.is_deposit_spouse==1 ? "checked" : ""}>
								<label for="${blockId}_rent_deposit_spouse">배우자명의</label>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>월세</span></div>
							<div class="form-content">
								<input type="text" class="rent_monthly_rent input86" data-type="money" value="${data.monthly_rent || ""}" class="form-control form-content-justify">원
							</div>
							<div class="form-content checkbox-right">
								<input type="checkbox" id="${blockId}_rent_monthly_spouse" class="rent_monthly_spouse" ${data.is_monthly_spouse==1 ? "checked" : ""}>
								<label for="${blockId}_rent_monthly_spouse">배우자명의</label>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>반환받을 보증금</span></div>
							<div class="form-content">
								<input type="text" class="rent_refund_deposit" data-type="money" value="${data.refund_deposit || ""}">원
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
								<input type="text" class="rent_priority_deposit" data-type="money" value="${data.priority_deposit || ""}">원 제외
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								<input type="text" class="rent_liquidation_value" data-type="money" value="${data.liquidation_value || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="rent_liquidation_explain" value="${data.explanation || ""}">
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
	}
	
	loanReceivablesTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="loan_liquidation_value" data-type="money" placeholder="회수가능금액" value="${data.liquidation_value || ""}" class="form-control">원
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
	}
	
	salesReceivablesTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="sales_liquidation_value" data-type="money" placeholder="회수가능금액" value="${data.liquidation_value || ""}" class="form-control">원
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
	}
	
	severancePayTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="severance_expected_amount" data-type="money" value="${data.expected_severance || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content form-nocontent">
								<input type="text" class="severance_deduction_amount" data-type="money" value="${data.deduction_amount || ""}" placeholder="">원 제외 (압류할 수 없는 퇴직금)
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"></div>
							<div class="form-content">
								<input type="text" class="severance_liquidation_value" data-type="money" value="${data.liquidation_value || ""}" placeholder="">원 (청산가치)
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
	}
	
	realEstateTemplate(blockId, propertyNo, data = {}) {
		return `
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
										면적&nbsp;&nbsp;<input type="text" class="property_area" data-type="money" value="${data.property_area || ""}" class="form-control form-content-short">㎡
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
								<input type="text" class="property_expected_value" data-type="money" value="${data.property_expected_value || ""}">원
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
								<input type="text" class="property_secured_debt" data-type="money" value="${data.property_secured_debt || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>보증금 채무액</span></div>
							<div class="form-content">
								<input type="text" class="property_deposit_debt" data-type="money" value="${data.property_deposit_debt || ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content form-nocontent">
								<input type="text" class="property_liquidation_value" data-type="money" value="${data.property_liquidation_value || ""}">원
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
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="property_liquidation_explain" value="${data.property_liquidation_explain || ""}">
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
	}
	
	vehicleTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="vehicle_max_bond" data-type="money" value="${data.max_bond || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>환가 예상액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_expected_value" data-type="money" value="${data.expected_value || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>재무 잔액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_financial_balance" data-type="money" value="${data.financial_balance || ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_liquidation_value input86" data-type="money" value="${data.liquidation_value || ""}">원
							</div>
							<div class="form-content checkbox-right">
								<input type="checkbox" id="${blockId}_vehicle_manual_calc" class="vehicle_manual_calc" ${data.is_manual_calc==="Y" ? "checked" : ""}>
								<label for="${blockId}_vehicle_manual_calc">수동계산</label>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="vehicle_liquidation_explain" value="${data.explanation || ""}">
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
	}
	
	otherAssetTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block other-asset-block" id="${blockId}">
				<input type="hidden" class="other_assets_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="other_assets_property_no" value="${propertyNo}">
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
								<input type="text" class="other_liquidation_value" data-type="money" value="${data.liquidation_value || ""}">원
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
	}
	
	disposedAssetTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block disposed-assets-block" id="${blockId}">
				<input type="hidden" class="disposed_assets_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="disposed_assets_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>처분일자</span></div>
							<div class="form-content">
								<input type="date" class="disposed_date" value="${data.disposal_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>재산의 종류</span></div>
							<div class="form-content">
								<input type="text" class="disposed_property_type" value="${data.property_type || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>처분 가액</span></div>
							<div class="form-content">
								<input type="text" class="disposed_amount" data-type="money" value="${data.disposal_amount || ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>처분 사유</span></div>
							<div class="form-content">
								<input type="text" class="disposed_reason" value="${data.disposal_reason || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>양수인</span></div>
							<div class="form-content">
								<input type="text" class="disposed_recipient" value="${data.recipient || ""}">
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
	}
	
	receivedDepositTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block received-deposit-block" id="${blockId}">
				<input type="hidden" class="received_deposit_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="received_deposit_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>수령일자</span></div>
							<div class="form-content">
								<input type="date" class="received_date" value="${data.receipt_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>임대인</span></div>
							<div class="form-content">
								<input type="text" class="received_lessor" value="${data.lessor || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>소재지</span></div>
							<div class="form-content">
								<input type="text" class="received_location" value="${data.location || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>임차보증금</span></div>
							<div class="form-content">
								<input type="text" class="received_deposit_amount" data-type="money" value="${data.deposit_amount || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>비고</span></div>
							<div class="form-content">
								<input type="text" class="received_note" value="${data.note || ""}">
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
	}
	
	divorcePropertyTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block divorce-property-block" id="${blockId}">
				<input type="hidden" class="divorce_property_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="divorce_property_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>이혼일자</span></div>
							<div class="form-content">
								<input type="date" class="divorce_date" value="${data.divorce_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>배우자</span></div>
							<div class="form-content">
								<input type="text" class="divorce_spouse" value="${data.spouse_name || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>재산분할 합의일</span></div>
							<div class="form-content">
								<input type="date" class="divorce_settlement_date" value="${data.settlement_date || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>분할받은 재산종류</span></div>
							<div class="form-content">
								<input type="text" class="divorce_property_type" value="${data.property_type || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>분할받은 재산가액</span></div>
							<div class="form-content">
								<input type="text" class="divorce_property_amount" data-type="money" value="${data.property_amount || ""}">원
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
	}
	
	inheritedPropertyTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block inherited-property-block" id="${blockId}">
				<input type="hidden" class="inherited_property_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="inherited_property_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>상속인</span></div>
							<div class="form-content">
								<input type="text" class="inherited_heir" value="${data.heir_name || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>피상속인</span></div>
							<div class="form-content">
								<input type="text" class="inherited_deceased" value="${data.deceased_name || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>상속개시일</span></div>
							<div class="form-content">
								<input type="date" class="inherited_start_date" value="${data.inheritance_date || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>상속재산 종류</span></div>
							<div class="form-content">
								<input type="text" class="inherited_property_type" value="${data.property_type || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>상속재산 가액</span></div>
							<div class="form-content">
								<input type="text" class="inherited_property_amount" data-type="money" value="${data.property_amount || ""}">원
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
	}
}