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
					registration_number: block.find(".vehicle_registration_number").val().trim(),
					security_debt_balance: this.unformatMoney(block.find(".vehicle_security_debt_balance").val()),
					market_value: this.unformatMoney(block.find(".vehicle_market_value").val()),
					liquidation_value: this.unformatMoney(block.find(".vehicle_liquidation_value").val()),
					liquidation_explanation: block.find(".vehicle_liquidation_explanation").val().trim(),
					is_seized: block.find(`input[name^="vehicle_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'rent_deposit':
				Object.assign(data, {
					rent_location: block.find(".rent_location").val().trim(),
					rent_deposit: this.unformatMoney(block.find(".rent_deposit").val()),
					key_money: this.unformatMoney(block.find(".key_money").val()),
					expected_refund: this.unformatMoney(block.find(".expected_refund").val()),
					explanation: block.find(".rent_explanation").val().trim(),
					is_seized: block.find(`input[name^="rent_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'real_estate':
				Object.assign(data, {
					property_type: block.find(".property_type").val().trim(),
					property_area: block.find(".property_area").val().trim(),
					property_location: block.find(".property_location").val().trim(),
					secured_debt_balance: this.unformatMoney(block.find(".secured_debt_balance").val()),
					seizure_details: block.find(".seizure_details").val().trim(),
					seizure_creditor: block.find(".seizure_creditor").val().trim(),
					seizure_amount: this.unformatMoney(block.find(".seizure_amount").val()),
					market_value: this.unformatMoney(block.find(".market_value").val()),
					liquidation_explanation: block.find(".liquidation_explanation").val().trim(),
					is_seized: block.find(`input[name^="property_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'loan_receivables':
				Object.assign(data, {
					debtor_name: block.find(".loan_debtor_name").val().trim(),
					claim_amount: this.unformatMoney(block.find(".loan_claim_amount").val()),
					collectible_amount: this.unformatMoney(block.find(".loan_collectible_amount").val()),
					is_seized: block.find(`input[name^="loan_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'sales_receivables':
				Object.assign(data, {
					debtor_name: block.find(".sales_debtor_name").val().trim(),
					claim_amount: this.unformatMoney(block.find(".sales_claim_amount").val()),
					collectible_amount: this.unformatMoney(block.find(".sales_collectible_amount").val()),
					is_seized: block.find(`input[name^="sales_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'severance_pay':
				Object.assign(data, {
					workplace: block.find(".severance_workplace").val().trim(),
					expected_amount: this.unformatMoney(block.find(".severance_expected_amount").val()),
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
					disposal_date: block.find(".disposal_date").val(),
					disposal_amount: this.unformatMoney(block.find(".disposal_amount").val()),
					disposal_usage: block.find(".disposal_usage").val().trim(),
					is_seized: block.find(`input[name^="disposed_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'received_deposit':
				Object.assign(data, {
					receipt_date: block.find(".receipt_date").val(),
					rental_property: block.find(".rental_property").val().trim(),
					contract_deposit: this.unformatMoney(block.find(".contract_deposit").val()),
					received_deposit: this.unformatMoney(block.find(".received_deposit").val()),
					deposit_usage: block.find(".deposit_usage").val().trim(),
					is_seized: block.find(`input[name^="received_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'divorce_property':
				Object.assign(data, {
					divorce_date: block.find(".divorce_date").val(),
					settlement_property: block.find(".settlement_property").val().trim(),
					divorce_timing: block.find(".divorce_timing").val(),
					is_seized: block.find(`input[name^="divorce_seizure_"]:checked`).val() || "N"
				});
				break;
			case 'inherited_property':
				Object.assign(data, {
					inheritance_date: block.find(".inheritance_date").val(),
					deceased_type: block.find(".deceased_type").val().trim(),
					inheritance_status: block.find(".inheritance_status").val().trim(),
					main_inheritance_property: block.find(".main_inheritance_property").val().trim(),
					acquisition_process: block.find(".acquisition_process").val().trim(),
					is_seized: block.find(`input[name^="inherited_seizure_"]:checked`).val() || "N"
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

	rentDepositTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="rent_deposit" data-type="money" value="${data.rent_deposit || ""}" class="form-control form-content-justify">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>권리금</span></div>
							<div class="form-content">
								<input type="text" class="key_money" data-type="money" value="${data.key_money || ""}" class="form-control form-content-justify">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>반환예상금</span></div>
							<div class="form-content">
								<input type="text" class="expected_refund" data-type="money" value="${data.expected_refund || ""}">원
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
							<div class="form-title form-title-3 form-notitle"><span>주의사항</span></div>
							<div class="form-content form-content-3 form-nocontent">
								- 반환예상금란에는 채무자가 파산신청일을 기준으로 임대인에게 임차물건을 명도할 경우<br>
								&nbsp;&nbsp;&nbsp;임대인으로부터 반환 받을 수 있는 임차보증금의 예상액을 기재하여 주십시오.<br>
								- 임대차계약서의 사본 등 임차보증금 중 반환예상액을 알 수 있는 자료를 첨부하여 주십시오.<br>
								- 상가 임대차의 경우에는 권리금이 있으면 반드시 권리금 액수를 기재해 주시기 바랍니다.
							</div>
						</div>
						<div class="form">
							<div class="form-title form-nocontent"><span></span></div>
							<div class="form-content form-nocontent"></div>
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
							<div class="form-title"><span>채무자명</span></div>
							<div class="form-content">
								<input type="text" class="loan_debtor_name" value="${data.debtor_name || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>채권금액</span></div>
							<div class="form-content">
								<input type="text" class="loan_claim_amount" data-type="money" value="${data.claim_amount || ""}" class="form-control">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>회수가능금액</span></div>
							<div class="form-content">
								<input type="text" class="loan_collectible_amount" data-type="money" value="${data.collectible_amount || ""}" class="form-control">원
							</div>
						</div>
					</div>
					<div class="right-section">
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
	}
	
	salesReceivablesTemplate(blockId, propertyNo, data = {}) {
		return `
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
								<input type="text" class="sales_claim_amount" data-type="money" value="${data.claim_amount || ""}" class="form-control">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>회수가능금액</span></div>
							<div class="form-content">
								<input type="text" class="sales_collectible_amount" data-type="money" value="${data.collectible_amount || ""}" class="form-control">원
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
							<div class="form-title"><span>근무처</span></div>
							<div class="form-content">
								<input type="text" class="severance_workplace" value="${data.workplace || ""}" class="form-control form-content-long">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>퇴직금예상액</span></div>
							<div class="form-content">
								<input type="text" class="severance_expected_amount" data-type="money" value="${data.expected_amount || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle"><span>취지</span></div>
							<div class="form-content">
								<input type="text" class="severance_liquidation_value" data-type="money" value="${data.liquidation_value || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-title-2"><span>주의사항</span></div>
							<div class="form-content form-content-2">
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
								<input type="text" class="secured_debt_balance" data-type="money" value="${data.secured_debt_balance || ""}">원
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
									가액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="seizure_amount" data-type="money" value="${data.seizure_amount || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								시가&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="market_value" data-type="money" value="${data.market_value || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="liquidation_explanation" value="${data.liquidation_explanation || ""}">
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
	}
	
	vehicleTemplate(blockId, propertyNo, data = {}) {
		return `
			<div class="asset-block vehicle-block" id="${blockId}">
				<input type="hidden" class="vehicle_asset_no" value="${data.asset_no || ""}">
				<input type="hidden" class="vehicle_property_no" value="${propertyNo}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title"><span>차종/연식</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_info form-content-long" value="${data.vehicle_info || ""}" placeholder="차량번호, 연식, 모델(예:123가4567, 2020년형, 아반떼)">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>등록번호</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_registration_number" value="${data.registration_number || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>등록된 담보권의<br>피담보채권 잔액</span></div>
							<div class="form-content">
								<input type="text" class="vehicle_security_debt_balance" data-type="money" value="${data.security_debt_balance || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-notitle"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								시가&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="vehicle_market_value" data-type="money" value="${data.market_value || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
								부연설명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="vehicle_liquidation_explanation" value="${data.liquidation_explanation || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>주의사항</span></div>
							<div class="form-content">자동차등록원부와 시가 증명자료를 첨부하여 주십시오.</div>
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
							<div class="form-title"><span>품목명</span></div>
							<div class="form-content">
								<input type="text" class="other_asset_content" value="${data.asset_content || ""}" class="form-control">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>청산가치 판단금액</span></div>
							<div class="form-content">
								시가&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="other_liquidation_value" data-type="money" value="${data.liquidation_value || ""}">원
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
							<div class="form-title"><span>처분시기</span></div>
							<div class="form-content">
								<input type="date" class="disposal_date" value="${data.disposal_date || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>처분금액</span></div>
							<div class="form-content">
								<input type="text" class="disposal_amount" data-type="money" value="${data.disposal_amount || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>사용처</span></div>
							<div class="form-content">
								<input type="text" class="disposal_usage" value="${data.disposal_usage || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
							</div>
						</div>
					</div>
					<div class="right-section">
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
								<input type="text" class="contract_deposit" data-type="money" value="${data.contract_deposit || ""}">원
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title"><span>실제수령<br>임차보증금액</span></div>
							<div class="form-content">
								<input type="text" class="received_deposit" data-type="money" value="${data.received_deposit || ""}">원
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span>임차보증금 사용처</span></div>
							<div class="form-content">
								<input type="text" class="deposit_usage" value="${data.deposit_usage || ""}">
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
							<div class="form-title"><span>이혼시기</span></div>
							<div class="form-content">
								<input type="date" class="divorce_timing" value="${data.divorce_timing || ""}">
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
						<div class="form">
							<div class="form-title form-notitle"><span></span></div>
							<div class="form-content form-nocontent">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title form-title-6"><span>주의사항</span></div>
							<div class="form-content form-content-6">
								- 제출 자료<br>
								&nbsp;&nbsp;&nbsp;① 이혼에 따라 배우자에게 분여(할)한 모든 재산의 내역<br>
								&nbsp;&nbsp;&nbsp;② 협의이혼 시 미성년 자녀가 있는 경우, 양육비부담조서 제출<br>
								&nbsp;&nbsp;&nbsp;③ 재판상이혼의 경우, 판결서, 조정조서 등 재판서 및 확정증명 제출<br>
								- 이혼을 증명할 소명자료 제출<br>
								&nbsp;&nbsp;&nbsp;① 시, 구, 읍(면) 등 가족관계등록관서에 이혼신고를 하여 가족관계등록부에 기록된 경우 ㅣ<br>
								&nbsp;&nbsp;&nbsp;혼인관계증명서(상세)<br>
								&nbsp;&nbsp;&nbsp;② 최근 2년 이내에 재판상 이혼을 한 경우로 아직 시, 구, 읍(면)등 가족관계등록관서에 이혼신고를 하지 않은 경우 ㅣ<br>
								&nbsp;&nbsp;&nbsp;재판상 이혼과 관련한 재판서의 등본(조정ㆍ화해가 성립된 경우에는 그에 대한 조서 등본) 및 확정증명을 제출<br>
								&nbsp;&nbsp;&nbsp;③ 외국에서 유효한 신분행위를 하여 해당 국가의 증서 등본이 발행되었으나 아직 한국 시, 구, 읍(면) 등<br>
								&nbsp;&nbsp;&nbsp;가족관계등록관서에 신고하지 않은 경우 :<br>
								&nbsp;&nbsp;&nbsp;신분관계에 관한 외국의 증서 등본
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
								<select class="inheritance_status form-content-long" name="inheritance_status">
									<option value="㉠ 상속재산이 전혀 없음" ${(data.inheritance_status==="㉠ 상속재산이 전혀 없음") ? "selected" : ""}>㉠ 상속재산이 전혀 없음</option>
									<option value="㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음" ${(data.inheritance_status==="㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음") ? "selected" : ""}>㉡ 신청인의 상속포기 또는 상속재산 분할에 의하여 다른 상속인이 모두 취득하였음</option>
									<option value="㉢ 신청인이 전부 또는 일부를 상속하였음" ${(data.inheritance_status==="㉢ 신청인이 전부 또는 일부를 상속하였음") ? "selected" : ""}>㉢ 신청인이 전부 또는 일부를 상속하였음</option>
								</select>
							</div>
						</div>
						<div class="form">
							<div class="form-title"><span></span></div>
							<div class="form-content">
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
							<div class="form-title"><span>주의사항</span></div>
							<div class="form-content">
								-  ㉡ 또는 ㉢항을 선택한 분은 주된 상속재산을 기재하여 주십시오.<br>
								-  ㉡항을 선택한 분은 다른 상속인이 주된 상속재산을 취득하게 된 경위를 기재하여 주십시오.
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