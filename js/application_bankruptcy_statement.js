/**
 * 파산 신청 진술서 관리 클래스
 * 최종학력, 과거경력, 법원 제출 정보 등 다양한 진술 항목을 관리합니다.
 */
class BankruptcyStatementManager {
	constructor() {
		// 데이터 저장소 초기화
		this.initializeDataStores();
		
		// 전역 접근을 위한 인스턴스 연결
		window.bankruptcyStatementManager = this;
		
		// 이벤트 핸들러 초기화
		this.initializeEventHandlers();
	}
	
	/**
	 * 데이터 저장소 초기화
	 */
	initializeDataStores() {
		// 카운터 초기화
		this.careerCounter = 0;
		this.bankruptcyHistoryCounter = 0;
		this.legalActionCounter = 0;
		this.debtAfterInsolvencyCounter = 0;
		
		// 데이터 배열 초기화
		this.careers = [];
		this.bankruptcyHistories = [];
		this.legalActions = [];
		this.debtAfterInsolvencies = [];
		
		// 단일 객체 데이터 초기화
		this.educationData = null;
		this.domesticCourtData = null;
		this.lifeHistoryData = null;
		this.creditorStatusData = null;
		this.bankruptcyReasonData = null;
		this.partialRepaymentData = null;
	}
	
	/**
	 * 모든 진술서 데이터 로드
	 */
	loadStatements() {
		if (!window.currentCaseNo) {
			console.warn('사건번호가 없어 데이터를 로드할 수 없습니다.');
			return;
		}
		
		// 모든 데이터 로드 메서드 호출
		this.loadEducation();
		this.loadCareers();
		this.loadDomesticCourtData();
		this.loadLifeHistory();
		this.loadCreditorStatus();
		this.loadBankruptcyReason();
		this.loadDebtAfterInsolvencies();
		this.loadPartialRepayment();
	}
	
	/**
	 * 이벤트 핸들러 초기화 메서드
	 */
	initializeEventHandlers() {
		// 최종학력 섹션
		$("#save_education").on("click", () => this.saveEducation());
		
		// 과거경력 섹션
		$("#add_career").on("click", () => this.addCareerBlock());
		
		// 동시 개인파산 신청 가족 섹션
		$("#save_family_bankruptcy_info").on("click", () => this.saveDomesticCourtData());
		
		// 현재까지의 생활상황 섹션
		$("#save_life_history").on("click", () => this.saveLifeHistory());
		$("#cancel_life_history").on("click", () => this.resetLifeHistoryForm());
		$("#add_bankruptcy_history_record").on("click", () => this.addBankruptcyHistoryRecord());
		
		// 채권자의 상황 섹션
		$("#save_creditor_status").on("click", () => this.saveCreditorStatus());
		$("#add_legal_action").on("click", () => this.addLegalActionRecord());
		
		// 파산신청 사유 섹션
		$("#save_bankruptcy_reason").on("click", () => this.saveBankruptcyReason());
		$("#delete_bankruptcy_reason").on("click", () => this.deleteBankruptcyReason());
		
		// 지급불능 이후 채무 발생 섹션
		$("#add_debt_after_insolvency").on("click", () => this.addDebtAfterInsolvencyBlock());
		
		// 일부 채권자에게만 변제한 경험 섹션
		$("#save_partial_repayment").on("click", () => this.savePartialRepayment());
		$("#delete_partial_repayment").on("click", () => this.deletePartialRepayment());
		
		// 금액 입력 필드의 포맷팅
		this.initializeMoneyInputs();
	}
	
	/**
	 * 금액 입력 필드 초기화 - 자동 콤마 포맷팅
	 */
	initializeMoneyInputs() {
		const moneyInputs = [
			'#unpaid_price',
			'#monthly_payment_amount',
			'#fraud_damage_amount',
			'#debt_after_insolvency_amount',
			'#partial_repaid_amount'
		];
		
		moneyInputs.forEach(selector => {
			$(document).on('input', selector, (e) => {
				const val = e.target.value.replace(/[^\d]/g, '');
				e.target.value = this.formatMoney(val);
			});
		});
	}
	
	/**
	 * 금액 형식으로 변환 (천 단위 콤마)
	 */
	formatMoney(amount) {
		if (!amount) return '0';
		return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}
	
	/**
	 * 금액 문자열에서 콤마 제거하고 숫자로 변환
	 */
	unformatMoney(str) {
		if (!str) return 0;
		return parseInt(str.replace(/,/g, '')) || 0;
	}
	
	/**
	 * 공통 API 요청 함수
	 * @param {string} url - API 엔드포인트
	 * @param {string} method - HTTP 메서드 (GET, POST, DELETE)
	 * @param {Object} data - 요청 데이터
	 * @param {function} callback - 성공 시 콜백 함수
	 * @param {string} successMessage - 성공 메시지
	 * @param {string} errorPrefix - 오류 메시지 접두사
	 */
	sendApiRequest(url, method, data, callback, successMessage, errorPrefix) {
		$.ajax({
			url: url,
			type: method,
			data: data,
			dataType: "json",
			success: (response) => {
				if (response.success) {
					if (successMessage) {
						alert(successMessage);
					}
					if (callback && typeof callback === 'function') {
						callback(response.data);
					}
				} else {
					alert(response.message || `${errorPrefix} 실패`);
				}
			},
			error: (xhr, status, error) => {
				console.error(`${errorPrefix} 오류:`, error);
				console.error(xhr.responseText);
				alert(`${errorPrefix} 중 오류가 발생했습니다.`);
			}
		});
	}
	
	/**
	 * 최종학력 데이터 로드
	 */
	loadEducation() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/education_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.educationData = Array.isArray(data) ? data[0] : data;
				this.populateEducationForm(this.educationData);
			},
			null,
			"최종학력 데이터 로드"
		);
	}
	
	/**
	 * 최종학력 폼에 데이터 표시
	 */
	populateEducationForm(data) {
		if (!data) return;
		
		$("#graduation_date").val(data.graduation_date || "");
		$("#school_name").val(data.school_name || "");
		
		if (data.graduation_status) {
			const $radio = $(`input[name="graduation_status"][value="${data.graduation_status}"]`);
			if ($radio.length > 0) {
				$radio.prop("checked", true);
			}
		}
	}
	
	/**
	 * 최종학력 데이터 저장
	 */
	saveEducation() {
		const data = {
			case_no: window.currentCaseNo,
			graduation_date: $("#graduation_date").val().trim(),
			school_name: $("#school_name").val().trim(),
			graduation_status: $("input[name='graduation_status']:checked").val() || ""
		};
		
		if (this.educationData && this.educationData.education_id) {
			data.education_id = this.educationData.education_id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/education_api.php",
			"POST",
			data,
			(responseData) => {
				this.educationData = responseData;
			},
			"최종학력 정보가 저장되었습니다.",
			"최종학력 저장"
		);
	}
	
	/**
	 * 과거경력 블록 추가
	 */
	addCareerBlock(data = {}) {
		this.careerCounter++;
		const blockId = "career_block_" + this.careerCounter;
		const careerId = data.career_id || "";
		
		const html = `
		<div class="asset-block career-block" id="${blockId}" data-career-id="${careerId}">
			<input type="hidden" class="career_id" value="${careerId}">
			<div class="content-wrapper">
				<div class="left-section">
					<div class="form">
						<div class="form-title">
							<span>기간</span>
						</div>
						<div class="form-content">
							<input type="date" class="work_start_date form-content-short" value="${data.work_start_date || ""}">부터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="date" class="work_end_date form-content-short" value="${data.work_end_date || ""}">까지
						</div>
					</div>
					<div class="form">
						<div class="form-title">
							<span>자영/근무</span>
						</div>
						<div class="form-content">
							<div class="radio">
								<input type="radio" id="${blockId}_self" name="company_type_${blockId}" value="자영" ${data.company_type === "자영" ? "checked" : ""}>
								<label for="${blockId}_self">자영</label>
								<input type="radio" id="${blockId}_employed" name="company_type_${blockId}" value="근무" ${data.company_type === "근무" ? "checked" : ""}>
								<label for="${blockId}_employed">근무</label>
							</div>
						</div>
					</div>
					<div class="form">
						<div class="form-title">
							<span>업종</span>
						</div>
						<div class="form-content">
							<input type="text" class="business_type" value="${data.business_type || ""}">
						</div>
					</div>
				</div>
				<div class="right-section">
					<div class="form">
						<div class="form-title">
							<span>직장명</span>
						</div>
						<div class="form-content">
							<input type="text" class="company_name" value="${data.company_name || ""}">
						</div>
					</div>
					<div class="form">
						<div class="form-title">
							<span>직위</span>
						</div>
						<div class="form-content">
							<input type="text" class="position" value="${data.position || ""}">
						</div>
					</div>
					<div class="form">
						<div class="form-title form-notitle">
							<span></span>
						</div>
						<div class="form-content form-nocontent btn-right">
							<button type="button" class="btn-delete career_delete_btn">삭제</button>
							<button type="button" class="btn-save career_save_btn">저장</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
		
		$("#career_container").append(html);
		const block = $("#" + blockId);
		
		// 이벤트 핸들러 등록
		block.find(".career_save_btn").on("click", () => {
			this.saveCareerBlock(block);
		});
		
		block.find(".career_delete_btn").on("click", () => {
			this.deleteCareerBlock(block);
		});
	}

	/**
	 * 과거경력 블록 저장
	 */
	saveCareerBlock(block) {
		const blockId = block.attr('id');
		const careerId = block.find(".career_id").val();
		
		const data = {
			case_no: window.currentCaseNo,
			company_type: block.find(`input[name="company_type_${blockId}"]:checked`).val() || "",
			business_type: block.find(`.business_type`).val().trim(),
			company_name: block.find(`.company_name`).val().trim(),
			position: block.find(`.position`).val().trim(),
			work_start_date: block.find(`.work_start_date`).val(),
			work_end_date: block.find(`.work_end_date`).val()
		};
		
		// 기존 레코드 ID가 있으면 전달
		if (careerId) {
			data.career_id = careerId;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/career_api.php",
			"POST",
			data,
			(responseData) => {
				// 새 career_id 업데이트
				if (responseData && responseData.career_id) {
					block.find(".career_id").val(responseData.career_id);
					block.attr("data-career-id", responseData.career_id);
				}
			},
			"경력 정보가 저장되었습니다.",
			"경력 저장"
		);
	}

	/**
	 * 과거경력 폼 초기화 및 데이터 표시
	 */
	populateCareerBlocks(careers) {
		if ($("#career_container").length === 0) return;
		
		// 컨테이너 비우고 카운터 초기화
		$("#career_container").empty();
		this.careerCounter = 0;
		
		// 데이터가 없으면 빈 블록 하나 추가
		if (!Array.isArray(careers) || careers.length === 0) {
			this.addCareerBlock();
			return;
		}
		
		// 최근 경력부터 표시를 위한 정렬
		careers.sort((a, b) => {
			const dateA = new Date(a.work_start_date || '1900-01-01');
			const dateB = new Date(b.work_start_date || '1900-01-01');
			return dateB - dateA;
		});
		
		// 모든 경력 데이터를 각각 블록으로 추가
		careers.forEach((career) => {
			this.addCareerBlock(career);
		});
	}

	/**
	 * 과거경력 블록 삭제
	 */
	deleteCareerBlock(block) {
		const careerId = block.data("career-id") || block.find(".career_id").val();
		
		// DB에 저장되지 않은 블록은 바로 제거
		if (!careerId) {
			block.remove();
			this.checkEmptyCareerContainer();
			return;
		}
		
		if (!confirm("이 경력 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/career_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				career_id: careerId
			},
			() => {
				block.remove();
				this.checkEmptyCareerContainer();
			},
			"경력 정보가 삭제되었습니다.",
			"경력 삭제"
		);
	}

	/**
	 * 과거경력 데이터 로드
	 */
	loadCareers() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/career_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				if (Array.isArray(data) && data.length > 0) {
					this.careers = data;
					this.populateCareerBlocks(data);
				} else {
					// 데이터가 없을 경우 빈 블록 추가
					$("#career_container").empty();
					this.careerCounter = 0;
					this.addCareerBlock();
				}
			},
			null,
			"과거경력 데이터 로드"
		);
	}

	/**
	 * 과거경력 컨테이너가 비었는지 확인하고 빈 블럭 추가
	 */
	checkEmptyCareerContainer() {
		if ($("#career_container").children().length === 0) {
			this.addCareerBlock();
		}
	}
	
	/**
	 * 동시 개인파산 신청 가족 데이터 로드
	 */
	loadDomesticCourtData() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/domestic_court_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.domesticCourtData = Array.isArray(data) ? data[0] : data;
				this.populateDomesticCourtForm(this.domesticCourtData);
			},
			null,
			"가족 파산 신청 데이터 로드"
		);
	}
	
	/**
	 * 동시 개인파산 신청 가족 폼에 데이터 표시
	 */
	populateDomesticCourtForm(data) {
		if (!data) return;
		
		if (data.family_application) {
			$(`input[name="family_application"][value="${data.family_application}"]`).prop("checked", true);
		}
		
		$("#spouse_bankruptcy_name").val(data.spouse_name || "");
		$("#other_family_bankruptcy_members").val(data.other_family_members || "");
	}
	
	/**
	 * 동시 개인파산 신청 가족 데이터 저장
	 */
	saveDomesticCourtData() {
		const data = {
			case_no: window.currentCaseNo,
			family_application: $("input[name='family_application']:checked").val() || "",
			spouse_name: $("#spouse_bankruptcy_name").val().trim(),
			other_family_members: $("#other_family_bankruptcy_members").val().trim()
		};
		
		if (this.domesticCourtData && this.domesticCourtData.id) {
			data.id = this.domesticCourtData.id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/domestic_court_api.php",
			"POST",
			data,
			(responseData) => {
				this.domesticCourtData = responseData;
			},
			"가족 파산신청 정보가 저장되었습니다.",
			"가족 파산신청 정보 저장"
		);
	}
	
	/**
	 * 생활 상황 데이터 로드
	 */
	loadLifeHistory() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/life_history_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.lifeHistoryData = Array.isArray(data) ? data[0] : data;
				this.populateLifeHistoryForm(this.lifeHistoryData);
				this.loadBankruptcyHistories();
			},
			null,
			"생활상황 데이터 로드"
		);
	}
	
	/**
	 * 생활 상황 폼에 데이터 표시
	 */
	populateLifeHistoryForm(data) {
		if (!data) return;
		
		// 라디오 버튼 필드 설정 함수
		const setRadioValue = (name, value) => {
			if (value) {
				$(`input[name="${name}"][value="${value}"]`).prop("checked", true);
			}
		};
		
		// 체크박스 필드 설정 함수
		const setCheckboxArray = (name, values) => {
			if (values) {
				const valueArray = Array.isArray(values) ? values : values.split(',');
				valueArray.forEach(value => {
					$(`input[name="${name}[]"][value="${value}"]`).prop("checked", true);
				});
			}
		};
		
		// 라디오 버튼 설정
		setRadioValue("fraud_experience", data.fraud_experience);
		setRadioValue("past_bankruptcy", data.past_bankruptcy);
		setRadioValue("past_bankruptcy_declared", data.past_bankruptcy_declared);
		setRadioValue("past_discharge", data.past_discharge);
		setRadioValue("personal_rehabilitation", data.personal_rehabilitation);
		setRadioValue("rehabilitation_discharge", data.rehabilitation_discharge);
		setRadioValue("unpaid_sales", data.unpaid_sales);
		setRadioValue("unfair_sale", data.unfair_sale);
		
		// 체크박스 설정
		setCheckboxArray("business_record_type", data.business_record_type);
		
		// 텍스트 필드 설정
		$("#fraud_reason").val(data.fraud_reason || "");
		$("#bankruptcy_declared_date").val(data.bankruptcy_declared_date || "");
		$("#bankruptcy_declared_court").val(data.bankruptcy_declared_court || "");
		$("#discharge_date").val(data.discharge_date || "");
		$("#discharge_court").val(data.discharge_court || "");
		$("#discharge_confirmed_date").val(data.discharge_confirmed_date || "");
		$("#approval_date").val(data.approval_date || "");
		$("#approval_court").val(data.approval_court || "");
		$("#approval_case_number").val(data.approval_case_number || "");
		$("#cancellation_date").val(data.cancellation_date || "");
		$("#cancellation_court").val(data.cancellation_court || "");
		$("#cancellation_reason").val(data.cancellation_reason || "");
		$("#rehabilitation_discharge_date").val(data.rehabilitation_discharge_date || "");
		$("#rehabilitation_discharge_court").val(data.rehabilitation_discharge_court || "");
		$("#rehabilitation_discharge_case_number").val(data.rehabilitation_discharge_case_number || "");
		$("#unpaid_goods_name").val(data.unpaid_goods_name || "");
		$("#unpaid_purchase_date").val(data.unpaid_purchase_date || "");
		$("#unpaid_price").val(data.unpaid_price ? this.formatMoney(data.unpaid_price) : "");
		$("#unpaid_disposal_date").val(data.unpaid_disposal_date || "");
		$("#unpaid_disposal_method").val(data.unpaid_disposal_method || "");
		$("#unfair_sale_product").val(data.unfair_sale_product || "");
		$("#unfair_sale_date").val(data.unfair_sale_date || "");
		$("#unfair_discount_rate").val(data.unfair_discount_rate || "");
	}
	
	/**
	 * 생활 상황 데이터 저장
	 */
	saveLifeHistory() {
		// 체크박스 데이터 처리
		const getCheckedValues = (name) => {
			const values = [];
			$(`input[name='${name}[]']:checked`).each(function() {
				values.push($(this).val());
			});
			return values.length > 0 ? values : null;
		};
		
		const data = {
			case_no: window.currentCaseNo,
			fraud_experience: $("input[name='fraud_experience']:checked").val() || "",
			fraud_reason: $("#fraud_reason").val().trim(),
			past_bankruptcy: $("input[name='past_bankruptcy']:checked").val() || "",
			past_bankruptcy_declared: $("input[name='past_bankruptcy_declared']:checked").val() || "",
			bankruptcy_declared_date: $("#bankruptcy_declared_date").val(),
			bankruptcy_declared_court: $("#bankruptcy_declared_court").val().trim(),
			past_discharge: $("input[name='past_discharge']:checked").val() || "",
			discharge_date: $("#discharge_date").val(),
			discharge_court: $("#discharge_court").val().trim(),
			discharge_confirmed_date: $("#discharge_confirmed_date").val(),
			personal_rehabilitation: $("input[name='personal_rehabilitation']:checked").val() || "",
			approval_date: $("#approval_date").val(),
			approval_court: $("#approval_court").val().trim(),
			approval_case_number: $("#approval_case_number").val().trim(),
			cancellation_date: $("#cancellation_date").val(),
			cancellation_court: $("#cancellation_court").val().trim(),
			cancellation_reason: $("#cancellation_reason").val().trim(),
			rehabilitation_discharge: $("input[name='rehabilitation_discharge']:checked").val() || "",
			rehabilitation_discharge_date: $("#rehabilitation_discharge_date").val(),
			rehabilitation_discharge_court: $("#rehabilitation_discharge_court").val().trim(),
			rehabilitation_discharge_case_number: $("#rehabilitation_discharge_case_number").val().trim(),
			unpaid_sales: $("input[name='unpaid_sales']:checked").val() || "",
			unpaid_goods_name: $("#unpaid_goods_name").val().trim(),
			unpaid_purchase_date: $("#unpaid_purchase_date").val(),
			unpaid_price: this.unformatMoney($("#unpaid_price").val()),
			unpaid_disposal_date: $("#unpaid_disposal_date").val(),
			unpaid_disposal_method: $("#unpaid_disposal_method").val().trim(),
			business_record_type: getCheckedValues("business_record_type"),
			unfair_sale: $("input[name='unfair_sale']:checked").val() || "",
			unfair_sale_product: $("#unfair_sale_product").val().trim(),
			unfair_sale_date: $("#unfair_sale_date").val(),
			unfair_discount_rate: $("#unfair_discount_rate").val().trim()
		};
		
		if (this.lifeHistoryData && this.lifeHistoryData.id) {
			data.id = this.lifeHistoryData.id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/life_history_api.php",
			"POST",
			data,
			(responseData) => {
				this.lifeHistoryData = responseData;
			},
			"생활상황 정보가 저장되었습니다.",
			"생활상황 정보 저장"
		);
	}
	
	/**
	 * 생활 상황 폼 초기화
	 */
	resetLifeHistoryForm() {
		// 라디오 버튼 초기화
		$("input[type='radio'][name^='fraud_experience'], input[type='radio'][name^='past_'], input[type='radio'][name^='rehabilitation_'], input[type='radio'][name^='unpaid_'], input[type='radio'][name^='unfair_']").prop("checked", false);
		
		// 체크박스 초기화
		$("input[type='checkbox'][name^='business_record_type']").prop("checked", false);
		
		// 텍스트/날짜 필드 초기화
		$(".asset-box[data-type='life_history'] input[type='text'], .asset-box[data-type='life_history'] input[type='date']").val("");
		
		this.lifeHistoryData = null;
	}
	
	/**
	 * 파산 내역 레코드 추가
	 */
	addBankruptcyHistoryRecord(data = {}) {
		this.bankruptcyHistoryCounter++;
		const blockId = "bankruptcy_history_block_" + this.bankruptcyHistoryCounter;
		
		const html = `
		<div class="form bankruptcy-history-record" id="${blockId}" data-id="${data.id || ''}">
			<div class="form-content">
				일자&nbsp;&nbsp;|&nbsp;&nbsp;<input type="date" class="form-control form-content-short20 bankruptcy_history_date" value="${data.date || ''}">
				법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short20 bankruptcy_history_court" value="${data.court || ''}">
				<div style="width:4vw;"></div>
				<input type="checkbox" id="${blockId}_withdrawal" class="bankruptcy_history_status" value="취하" ${Array.isArray(data.status) && data.status.includes('취하') ? 'checked' : ''}>
				<label for="${blockId}_withdrawal">취하</label>
				<input type="checkbox" id="${blockId}_rejection" class="bankruptcy_history_status" value="기각" ${Array.isArray(data.status) && data.status.includes('기각') ? 'checked' : ''}>
				<label for="${blockId}_rejection">기각</label>
				<button type="button" class="btn-save save_bankruptcy_history_btn">저장</button>
				<button type="button" class="btn-delete delete_bankruptcy_history_btn">삭제</button>
			</div>
		</div>
		`;
		
		$("#bankruptcy_history_container").append(html);
		const block = $("#" + blockId);
		
		// 저장 버튼 클릭 이벤트 처리
		block.find(".save_bankruptcy_history_btn").on("click", () => {
			this.saveBankruptcyHistoryRecord(block);
		});
		
		// 삭제 버튼 클릭 이벤트 처리
		block.find(".delete_bankruptcy_history_btn").on("click", () => {
			this.deleteBankruptcyHistoryRecord(block);
		});
	}

	/**
	 * 파산 내역 레코드 삭제
	 */
	deleteBankruptcyHistoryRecord(block) {
		const id = block.data("id");
		
		// DB에 저장되지 않은 블록은 바로 제거
		if (!id) {
			block.remove();
			return;
		}
		
		if (!confirm("이 파산 내역을 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_history_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				id: id
			},
			() => {
				block.remove();
			},
			"파산 내역이 삭제되었습니다.",
			"파산 내역 삭제"
		);
	}

	/**
	 * 파산 내역 레코드 저장
	 */
	saveBankruptcyHistoryRecord(block) {
		// 상태 체크박스 값 수집
		const statusValues = [];
		block.find(".bankruptcy_history_status:checked").each(function() {
			statusValues.push($(this).val());
		});
		
		const data = {
			case_no: window.currentCaseNo,
			date: block.find(".bankruptcy_history_date").val(),
			court: block.find(".bankruptcy_history_court").val().trim(),
			status: statusValues
		};
		
		// 기존 ID가 있으면 전달
		const existingId = block.data("id");
		if (existingId) {
			data.id = existingId;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_history_api.php",
			"POST",
			data,
			(responseData) => {
				// 새로 생성된 ID로 블록 업데이트
				if (responseData && responseData.id) {
					block.data("id", responseData.id);
					block.attr("data-id", responseData.id);
				}
			},
			"파산 내역이 저장되었습니다.",
			"파산 내역 저장"
		);
	}

	/**
	 * 파산 내역 데이터 로드
	 */
	loadBankruptcyHistories() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_history_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				$("#bankruptcy_history_container").empty();
				this.bankruptcyHistoryCounter = 0;
				
				if (Array.isArray(data) && data.length > 0) {
					// 기존 데이터가 있으면 각각의 데이터에 대해 블록 생성
					data.forEach(history => {
						this.addBankruptcyHistoryRecord(history);
					});
				} else {
					// 데이터가 없으면 빈 블록 하나 추가
					this.addBankruptcyHistoryRecord();
				}
			},
			null,
			"파산 내역 데이터 로드"
		);
	}
	
	/**
	 * 채권자 상황 데이터 로드
	 */
	loadCreditorStatus() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/creditor_status_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.creditorStatusData = Array.isArray(data) ? data[0] : data;
				this.populateCreditorStatusForm(this.creditorStatusData);
				this.loadLegalActions();
			},
			null,
			"채권자 상황 데이터 로드"
		);
	}
	
	/**
	 * 채권자 상황 폼에 데이터 표시
	 */
	populateCreditorStatusForm(data) {
		if (!data) return;
		
		if (data.negotiation_experience) {
			$(`input[name="negotiation_experience"][value="${data.negotiation_experience}"]`).prop("checked", true);
		}
		
		$("#agreed_creditors_count").val(data.agreed_creditors_count || "");
		$("#payment_period_start").val(data.payment_period_start || "");
		$("#payment_period_end").val(data.payment_period_end || "");
		$("#monthly_payment_amount").val(data.monthly_payment_amount ? this.formatMoney(data.monthly_payment_amount) : "");
		$("#creditor_payment_details").val(data.creditor_payment_details || "");
		
		if (data.legal_action) {
			$(`input[name="legal_action"][value="${data.legal_action}"]`).prop("checked", true);
		}
	}
	
	/**
	 * 채권자 상황 데이터 저장
	 */
	saveCreditorStatus() {
		const data = {
			case_no: window.currentCaseNo,
			negotiation_experience: $("input[name='negotiation_experience']:checked").val() || "",
			agreed_creditors_count: $("#agreed_creditors_count").val().trim(),
			payment_period_start: $("#payment_period_start").val(),
			payment_period_end: $("#payment_period_end").val(),
			monthly_payment_amount: this.unformatMoney($("#monthly_payment_amount").val()),
			creditor_payment_details: $("#creditor_payment_details").val().trim(),
			legal_action: $("input[name='legal_action']:checked").val() || ""
		};
		
		if (this.creditorStatusData && this.creditorStatusData.id) {
			data.id = this.creditorStatusData.id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/creditor_status_api.php",
			"POST",
			data,
			(responseData) => {
				this.creditorStatusData = responseData;
			},
			"채권자 상황 정보가 저장되었습니다.",
			"채권자 상황 정보 저장"
		);
	}

	/**
	 * 소송/압류 내역 데이터 로드
	 */
	loadLegalActions() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/legal_action_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				$("#legal_action_container").empty();
				this.legalActionCounter = 0;
				
				if (Array.isArray(data) && data.length > 0) {
					this.legalActions = data;
					// 모든 소송/압류 데이터에 대해 블록 생성
					data.forEach(item => {
						this.addLegalActionBlock(item);
					});
				} else {
					// 데이터가 없으면 빈 블록 추가
					this.addLegalActionBlock();
				}
			},
			null,
			"소송/압류 내역 데이터 로드"
		);
	}
	
	/**
	 * 소송/압류 내역 블록 추가
	 */
	addLegalActionBlock(data = {}) {
		this.legalActionCounter++;
		const blockId = "legal_action_block_" + this.legalActionCounter;
		const actionId = data.id || "";
		
		const html = `
		<div class="legal-action-block" id="${blockId}" data-id="${actionId}">
			<input type="hidden" class="legal_action_id" value="${actionId}">
			<div class="form">
				<div class="form-title form-notitle" style="border-bottom-left-radius: 0;">
					<span></span>
				</div>
				<div class="form-content">
					법원&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short15 legal_action_court" value="${data.court || ""}">
					사건번호&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short15 legal_action_case_number" value="${data.case_number || ""}">
					채권자명&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" class="form-control form-content-short15 legal_action_creditor" value="${data.creditor || ""}">
					<button type="button" class="btn-save legal_action_save_btn">저장</button>
					<button type="button" class="btn-delete legal_action_delete_btn">삭제</button>
				</div>
			</div>
		</div>
		`;
		
		$("#legal_action_container").append(html);
		const block = $("#" + blockId);
		
		// 이벤트 핸들러 등록
		block.find(".legal_action_save_btn").on("click", () => {
			this.saveLegalActionBlock(block);
		});
		
		block.find(".legal_action_delete_btn").on("click", () => {
			this.deleteLegalActionBlock(block);
		});
	}
	
	/**
	 * 소송/압류 내역 블록 저장
	 */
	saveLegalActionBlock(block) {
		const data = {
			case_no: window.currentCaseNo,
			id: block.find(".legal_action_id").val(),
			court: block.find(".legal_action_court").val().trim(),
			case_number: block.find(".legal_action_case_number").val().trim(),
			creditor: block.find(".legal_action_creditor").val().trim()
		};
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/legal_action_api.php",
			"POST",
			data,
			(responseData) => {
				block.find(".legal_action_id").val(responseData.id);
				block.attr("data-id", responseData.id);
			},
			"소송/압류 내역이 저장되었습니다.",
			"소송/압류 내역 저장"
		);
	}

	/**
	 * 소송/압류 내역 블록 삭제
	 */
	deleteLegalActionBlock(block) {
		const id = block.find(".legal_action_id").val() || block.data("id");
		
		// DB에 저장되지 않은 블록은 바로 제거
		if (!id) {
			block.remove();
			this.checkEmptyLegalActionContainer();
			return;
		}
		
		if (!confirm("이 소송/압류 내역을 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/legal_action_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				id: id
			},
			() => {
				block.remove();
				this.checkEmptyLegalActionContainer();
			},
			"소송/압류 내역이 삭제되었습니다.",
			"소송/압류 내역 삭제"
		);
	}

	/**
	 * 소송/압류 내역 컨테이너가 비었는지 확인하고 빈 블록 추가
	 */
	checkEmptyLegalActionContainer() {
		if ($("#legal_action_container").children().length === 0) {
			this.addLegalActionBlock();
		}
	}

	/**
	 * 소송/압류 내역 레코드 추가
	 */
	addLegalActionRecord() {
		this.addLegalActionBlock();
	}
	
	/**
	 * 파산 신청 사유 데이터 로드
	 */
	loadBankruptcyReason() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_reason_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.bankruptcyReasonData = Array.isArray(data) ? data[0] : data;
				this.populateBankruptcyReasonForm(this.bankruptcyReasonData);
			},
			null,
			"파산 신청 사유 데이터 로드"
		);
	}
	
	/**
	 * 파산 신청 사유 폼에 데이터 표시
	 */
	populateBankruptcyReasonForm(data) {
		if (!data) return;
		
		// 체크박스 값 설정 함수
		const setCheckboxValues = (name, values) => {
			if (values) {
				const valuesArray = Array.isArray(values) ? values : values.split(',');
				valuesArray.forEach(value => {
					$(`input[name="${name}[]"][value="${value}"]`).prop("checked", true);
				});
			}
		};
		
		// 채무 원인 체크박스 처리
		setCheckboxValues("debt_reason", data.debt_reason);
		
		// 채무 원인 관련 필드 채우기
		$("#dependents_count").val(data.dependents_count || "");
		$("#living_expense_shortage_items").val(data.living_expense_shortage_items || "");
		$("#house_purchase_date").val(data.house_purchase_date || "");
		$("#house_disposal_date").val(data.house_disposal_date || "");
		$("#house_details").val(data.house_details || "");
		$("#business_start_date").val(data.business_start_date || "");
		$("#business_end_date").val(data.business_end_date || "");
		$("#business_type_detail").val(data.business_type_detail || "");
		$("#fraud_perpetrator_name").val(data.fraud_perpetrator_name || "");
		$("#fraud_perpetrator_relationship").val(data.fraud_perpetrator_relationship || "");
		$("#fraud_damage_amount").val(data.fraud_damage_amount ? this.formatMoney(data.fraud_damage_amount) : "");
		$("#debt_reason_other_detail").val(data.debt_reason_other_detail || "");
		
		// 지급 불능 원인 체크박스 처리
		setCheckboxValues("inability_reason", data.inability_reason);
		
		// 지급 불능 관련 필드 채우기
		$("#inability_reason_other_detail").val(data.inability_reason_other_detail || "");
		$("#inability_reason_other_date").val(data.inability_reason_other_date || "");
		
		// 별지 사용 체크박스
		if (data.exact_date_unknown) {
			$("#exact_date_unknown").prop("checked", true);
		}
		
		// 구체적 사정
		$("#inability_timeline").val(data.inability_timeline || "");
	}
	
	/**
	 * 파산 신청 사유 데이터 저장
	 */
	saveBankruptcyReason() {
		// 체크박스 데이터 수집 함수
		const getCheckedValues = (name) => {
			const values = [];
			$(`input[name='${name}[]']:checked`).each(function() {
				values.push($(this).val());
			});
			return values.length > 0 ? values : null;
		};
		
		const data = {
			case_no: window.currentCaseNo,
			debt_reason: getCheckedValues("debt_reason"),
			dependents_count: $("#dependents_count").val().trim(),
			living_expense_shortage_items: $("#living_expense_shortage_items").val().trim(),
			house_purchase_date: $("#house_purchase_date").val(),
			house_disposal_date: $("#house_disposal_date").val(),
			house_details: $("#house_details").val().trim(),
			business_start_date: $("#business_start_date").val(),
			business_end_date: $("#business_end_date").val(),
			business_type_detail: $("#business_type_detail").val().trim(),
			fraud_perpetrator_name: $("#fraud_perpetrator_name").val().trim(),
			fraud_perpetrator_relationship: $("#fraud_perpetrator_relationship").val().trim(),
			fraud_damage_amount: this.unformatMoney($("#fraud_damage_amount").val()),
			debt_reason_other_detail: $("#debt_reason_other_detail").val().trim(),
			inability_reason: getCheckedValues("inability_reason"),
			inability_reason_other_detail: $("#inability_reason_other_detail").val().trim(),
			inability_reason_other_date: $("#inability_reason_other_date").val(),
			exact_date_unknown: $("#exact_date_unknown").is(":checked") ? $("#exact_date_unknown").val() : "",
			inability_timeline: $("#inability_timeline").val().trim()
		};
		
		if (this.bankruptcyReasonData && this.bankruptcyReasonData.id) {
			data.id = this.bankruptcyReasonData.id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_reason_api.php",
			"POST",
			data,
			(responseData) => {
				this.bankruptcyReasonData = responseData;
			},
			"파산 신청 사유 정보가 저장되었습니다.",
			"파산 신청 사유 정보 저장"
		);
	}
	
	/**
	 * 파산 신청 사유 데이터 삭제
	 */
	deleteBankruptcyReason() {
		if (!this.bankruptcyReasonData || !this.bankruptcyReasonData.id) {
			alert("삭제할 데이터가 없습니다.");
			return;
		}
		
		if (!confirm("파산 신청 사유 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/bankruptcy_reason_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				id: this.bankruptcyReasonData.id
			},
			() => {
				// 폼 초기화
				$("input[name='debt_reason[]']").prop("checked", false);
				$("input[name='inability_reason[]']").prop("checked", false);
				$("#dependents_count").val("");
				$("#living_expense_shortage_items").val("");
				$("#house_purchase_date").val("");
				$("#house_disposal_date").val("");
				$("#house_details").val("");
				$("#business_start_date").val("");
				$("#business_end_date").val("");
				$("#business_type_detail").val("");
				$("#fraud_perpetrator_name").val("");
				$("#fraud_perpetrator_relationship").val("");
				$("#fraud_damage_amount").val("");
				$("#debt_reason_other_detail").val("");
				$("#inability_reason_other_detail").val("");
				$("#inability_reason_other_date").val("");
				$("#exact_date_unknown").prop("checked", false);
				$("#inability_timeline").val("");
				
				this.bankruptcyReasonData = null;
			},
			"파산 신청 사유 정보가 삭제되었습니다.",
			"파산 신청 사유 정보 삭제"
		);
	}
	
	/**
	 * 지급불능 이후 채무 발생 데이터 로드
	 */
	loadDebtAfterInsolvencies() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/debt_after_insolvency_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 컨테이너 초기화
				$("#debt_after_insolvency_container").empty();
				this.debtAfterInsolvencyCounter = 0;
				
				if (Array.isArray(data) && data.length > 0) {
					this.debtAfterInsolvencies = data;
					
					// 모든 데이터에 대해 블록 생성
					data.forEach(item => {
						this.addDebtAfterInsolvencyBlock(item);
					});
				} else {
					// 데이터가 없으면 빈 블록 추가
					this.addDebtAfterInsolvencyBlock();
				}
			},
			null,
			"지급불능 이후 채무 발생 데이터 로드"
		);
	}
	
	/**
	 * 지급불능 이후 채무 발생 블록 추가
	 */
	addDebtAfterInsolvencyBlock(data = {}) {
		// 신규 블록에 대한 ID 카운터 생성
		this.debtAfterInsolvencyCounter++;
		const blockId = "debt_after_insolvency_block_" + this.debtAfterInsolvencyCounter;
		const id = data.id || "";
		
		const html = `
		<div class="asset-block debt-after-insolvency-block" id="${blockId}" data-id="${id}">
			<input type="hidden" class="debt_after_insolvency_id" value="${id}">
			<div class="content-wrapper">
				<div class="left-section">
					<div class="form">
						<div class="form-title">
							<span>시기</span>
						</div>
						<div class="form-content">
							<input type="date" class="debt_after_insolvency_date form-content-short" value="${data.date || ""}">
						</div>
					</div>
					<div class="form">
						<div class="form-title">
							<span>원인</span>
						</div>
						<div class="form-content">
							<input type="text" class="debt_after_insolvency_reason form-content-long" value="${data.reason || ""}">
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
							<span>금액</span>
						</div>
						<div class="form-content">
							<input type="text" class="debt_after_insolvency_amount form-content-long" value="${data.amount ? this.formatMoney(data.amount) : ""}">
						</div>
					</div>
					<div class="form">
						<div class="form-title">
							<span>조건</span>
						</div>
						<div class="form-content">
							<input type="text" class="debt_after_insolvency_condition form-content-long" value="${data.debt_condition || ""}">
						</div>
					</div>
					<div class="form">
						<div class="form-title form-notitle">
							<span></span>
						</div>
						<div class="form-content form-nocontent btn-right">
							<button type="button" class="btn-delete debt_after_insolvency_delete_btn">삭제</button>
							<button type="button" class="btn-save debt_after_insolvency_save_btn">저장</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
		
		$("#debt_after_insolvency_container").append(html);
		const block = $("#" + blockId);
		
		// 금액 입력 필드에 이벤트 리스너 추가
		block.find(".debt_after_insolvency_amount").on('input', (e) => {
			const val = e.target.value.replace(/[^\d]/g, '');
			e.target.value = this.formatMoney(val);
		});
		
		// 이벤트 핸들러 등록
		block.find(".debt_after_insolvency_save_btn").on("click", () => {
			this.saveDebtAfterInsolvencyBlock(block);
		});
		
		block.find(".debt_after_insolvency_delete_btn").on("click", () => {
			this.deleteDebtAfterInsolvencyBlock(block);
		});
	}

	/**
	 * 지급불능 이후 채무 발생 블록 저장
	 */
	saveDebtAfterInsolvencyBlock(block) {
		const data = {
			case_no: window.currentCaseNo,
			id: block.find(".debt_after_insolvency_id").val(),
			date: block.find(".debt_after_insolvency_date").val(),
			reason: block.find(".debt_after_insolvency_reason").val().trim(),
			amount: this.unformatMoney(block.find(".debt_after_insolvency_amount").val()),
			debt_condition: block.find(".debt_after_insolvency_condition").val().trim()
		};
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/debt_after_insolvency_api.php",
			"POST",
			data,
			(responseData) => {
				block.find(".debt_after_insolvency_id").val(responseData.id);
				block.attr("data-id", responseData.id);
			},
			"지급불능 이후 채무 정보가 저장되었습니다.",
			"지급불능 이후 채무 정보 저장"
		);
	}

	/**
	 * 지급불능 이후 채무 발생 블록 삭제
	 */
	deleteDebtAfterInsolvencyBlock(block) {
		const id = block.find(".debt_after_insolvency_id").val();
		
		if (!id) {
			block.remove();
			this.checkEmptyDebtAfterInsolvencyContainer();
			return;
		}
		
		if (!confirm("이 채무 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/debt_after_insolvency_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				id: id
			},
			() => {
				block.remove();
				this.checkEmptyDebtAfterInsolvencyContainer();
			},
			"채무 정보가 삭제되었습니다.",
			"채무 정보 삭제"
		);
	}

	/**
	 * 지급불능 이후 채무 발생 컨테이너가 비었는지 확인하고 빈 블록 추가
	 */
	checkEmptyDebtAfterInsolvencyContainer() {
		if ($("#debt_after_insolvency_container").children().length === 0) {
			this.addDebtAfterInsolvencyBlock();
		}
	}
	
	/**
	 * 일부 채권자에게만 변제한 경험 데이터 로드
	 */
	loadPartialRepayment() {
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/partial_repayment_api.php",
			"GET",
			{ case_no: window.currentCaseNo },
			(data) => {
				// 단일 레코드 또는 첫 번째 레코드 사용
				this.partialRepaymentData = Array.isArray(data) ? data[0] : data;
				this.populatePartialRepaymentForm(this.partialRepaymentData);
			},
			null,
			"일부 채권자 변제 데이터 로드"
		);
	}
	
	/**
	 * 일부 채권자에게만 변제한 경험 폼에 데이터 표시
	 */
	populatePartialRepaymentForm(data) {
		if (!data) return;
		
		if (data.partial_repayment) {
			$(`input[name="partial_repayment"][value="${data.partial_repayment}"]`).prop("checked", true);
		}
		
		$("#partial_repayment_date").val(data.date || "");
		$("#partial_repaid_creditor_name").val(data.creditor_name || "");
		$("#partial_repaid_amount").val(data.amount ? this.formatMoney(data.amount) : "");
	}
	
	/**
	 * 일부 채권자에게만 변제한 경험 데이터 저장
	 */
	savePartialRepayment() {
		const data = {
			case_no: window.currentCaseNo,
			partial_repayment: $("input[name='partial_repayment']:checked").val() || "",
			date: $("#partial_repayment_date").val(),
			creditor_name: $("#partial_repaid_creditor_name").val().trim(),
			amount: this.unformatMoney($("#partial_repaid_amount").val())
		};
		
		if (this.partialRepaymentData && this.partialRepaymentData.id) {
			data.id = this.partialRepaymentData.id;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/partial_repayment_api.php",
			"POST",
			data,
			(responseData) => {
				this.partialRepaymentData = responseData;
			},
			"일부 채권자 변제 정보가 저장되었습니다.",
			"일부 채권자 변제 정보 저장"
		);
	}
	
	/**
	 * 일부 채권자에게만 변제한 경험 데이터 삭제
	 */
	deletePartialRepayment() {
		if (!this.partialRepaymentData || !this.partialRepaymentData.id) {
			alert("삭제할 데이터가 없습니다.");
			return;
		}
		
		if (!confirm("일부 채권자 변제 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.sendApiRequest(
			"/adm/api/application_bankruptcy/statement/partial_repayment_api.php",
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				id: this.partialRepaymentData.id
			},
			() => {
				// 폼 초기화
				$("input[name='partial_repayment']").prop("checked", false);
				$("#partial_repayment_date").val("");
				$("#partial_repaid_creditor_name").val("");
				$("#partial_repaid_amount").val("");
				
				this.partialRepaymentData = null;
			},
			"일부 채권자 변제 정보가 삭제되었습니다.",
			"일부 채권자 변제 정보 삭제"
		);
	}
}

// 클래스 인스턴스 생성 및 전역 변수에 할당
$(document).ready(() => {
	new BankruptcyStatementManager();
});