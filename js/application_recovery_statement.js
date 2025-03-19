class StatementManager {
	constructor() {
		this.careerCounter = 0;
		this.careers = [];
		this.marriageCounter = 0;
		this.marriages = [];
		this.lawsuitCounter = 0;
		this.lawsuits = [];
		this.debtReliefCounter = 0;
		this.debtReliefs = [];
		
		this.educationData = null;
		this.housingData = null;
		this.bankruptcyReasonData = null;
		
		$(document).ready(() => {
			this.initialize();
		});
	}
	
	initialize() {
		try {
			this.initializeEventHandlers();
			this.loadData();
		} catch (error) {
			console.error("초기화 중 오류가 발생했습니다.", error);
			this.initializeEmptyContainers();
		}
	}

	// 모든 데이터 로드 메소드를 하나로 통합
	loadData() {
		this.loadEducation();
		this.loadCareers();
		this.loadMarriages();
		this.loadHousing();
		this.loadLawsuits();
		this.loadBankruptcyReason();
		this.loadDebtRelief();
	}

	// 빈 컨테이너 초기화
	initializeEmptyContainers() {
		if ($("#career_container").length > 0) this.addCareerBlock();
		if ($("#marriage_container").length > 0) this.addMarriageBlock();
		if ($("#lawsuit_container").length > 0) this.addLawsuitBlock();
		if ($("#debt_relief_container").length > 0) this.loadDebtRelief();
	}

	initializeEventHandlers() {
		// 교육 섹션
		$("#education_save_btn").on("click", () => this.saveEducation());
		
		// 경력 섹션
		$("#add_career").on("click", () => this.addCareerBlock());
		
		// 결혼 섹션
		$("#add_marriage").on("click", () => this.addMarriageBlock());
		
		// 주거 섹션
		$("#housing_save_btn").on("click", () => this.saveHousing());
		
		// 소송 섹션
		$("#add_lawsuit").on("click", () => this.addLawsuitBlock());
		
		// 파산 사유 섹션
		$("#bankruptcy_reason_save_btn").on("click", () => this.saveBankruptcyReason());

		// 과거 면책절차 이용상황 저장
		$(".debt_relief_save_btn").on("click", () => this.saveDebtRelief());
	}
	
	// API 호출 함수 - 중복 코드 제거
	apiRequest(method, data = {}, successCallback, errorCallback = null) {
		const url = "/adm/api/application_recovery/statement/statement_api.php";
		
		// GET 요청인 경우 data를 URL 파라미터로 변환
		if (method === "GET") {
			$.ajax({
				url: url,
				type: method,
				data: data,
				dataType: "json",
				success: (response) => {
					if (response.success) {
						successCallback(response);
					} else {
						console.error("API 요청 실패:", response.message);
						if (errorCallback) errorCallback(response);
					}
				},
				error: (xhr, status, error) => {
					console.error("API 요청 중 오류 발생:", error);
					if (errorCallback) errorCallback({success: false, message: error});
				}
			});
		} else if (method === "DELETE") {
			// DELETE 요청의 경우 contentType 및 processData 설정 필요
			$.ajax({
				url: url,
				type: method,
				data: data,
				processData: true,
				contentType: "application/x-www-form-urlencoded; charset=UTF-8",
				dataType: "json",
				success: (response) => {
					if (response.success) {
						successCallback(response);
					} else {
						console.error("API 요청 실패:", response.message);
						if (errorCallback) errorCallback(response);
					}
				},
				error: (xhr, status, error) => {
					console.error("API 요청 중 오류 발생:", error);
					if (errorCallback) errorCallback({success: false, message: error});
				}
			});
		} else {
			// POST 요청
			$.ajax({
				url: url,
				type: method,
				data: data,
				dataType: "json",
				success: (response) => {
					if (response.success) {
						successCallback(response);
					} else {
						console.error("API 요청 실패:", response.message);
						if (errorCallback) errorCallback(response);
					}
				},
				error: (xhr, status, error) => {
					console.error("API 요청 중 오류 발생:", error);
					if (errorCallback) errorCallback({success: false, message: error});
				}
			});
		}
	}
	
	// ==================== 교육 관련 메소드 ====================
	loadEducation() {
		if ($("#school_name").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "education" 
			},
			(response) => {
				let educationData = response.data;
				if (Array.isArray(response.data) && response.data.length > 0) {
					educationData = response.data[0];
				}
				
				if (educationData) {
					this.educationData = educationData;
					this.populateEducationForm(educationData);
				}
			}
		);
	}
  
	populateEducationForm(data) {
		if ($("#school_name").length === 0) return;
		
		if (data.school_name !== undefined) {
			$("#school_name").val(data.school_name);
		}
		
		if (data.graduation_date !== undefined) {
			$("#graduation_date").val(data.graduation_date);
		}
		
		if (data.graduation_status) {
			const $radio = $(`input[name="graduation_status"][value="${data.graduation_status}"]`);
			if ($radio.length > 0) {
				$radio.prop("checked", true);
			}
		}
	}
  
	saveEducation() {
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "education",
			school_name: $("#school_name").val().trim(),
			graduation_date: $("#graduation_date").val(),
			graduation_status: $("input[name='graduation_status']:checked").val() || ""
		};
		
		if (this.educationData && this.educationData.education_id) {
			data.education_id = this.educationData.education_id;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("최종학력 정보가 저장되었습니다.");
				this.educationData = response.data;
			},
			() => {
				alert("최종학력 저장 중 오류가 발생했습니다.");
			}
		);
	}

	// ==================== 경력 관련 메소드 ====================
	loadCareers() {
		if ($("#career_container").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "career" 
			},
			(response) => {
				if (Array.isArray(response.data) && response.data.length > 0) {
					this.careers = response.data;
					this.populateCareerBlocks(response.data);
				} else {
					this.addCareerBlock();
				}
			},
			() => {
				this.addCareerBlock();
			}
		);
	}
	
	populateCareerBlocks(careers) {
		if ($("#career_container").length === 0) return;
		
		$("#career_container").empty();
		
		if (!Array.isArray(careers) || careers.length === 0) {
			this.addCareerBlock();
			return;
		}
		
		careers.sort((a, b) => {
			const dateA = new Date(a.work_start_date || '1900-01-01');
			const dateB = new Date(b.work_start_date || '1900-01-01');
			return dateB - dateA;
		});
		
		careers.forEach((career) => {
			this.addCareerBlock(career);
		});
	}
  
	addCareerBlock(data = {}) {
		this.careerCounter++;
		const blockId = "career_block_" + this.careerCounter;
		const careerId = data.career_id || "";
		
		const html = `
			<div class="asset-block career-block" id="${blockId}">
				<input type="hidden" class="career_id" value="${careerId}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title">
								<span>소속 유형</span>
							</div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_salary" name="company_type_${blockId}" value="급여" ${data.company_type === "급여" ? "checked" : ""}>
									<label for="${blockId}_salary">급여</label>
									<input type="radio" id="${blockId}_self" name="company_type_${blockId}" value="자영" ${data.company_type === "자영" ? "checked" : ""}>
									<label for="${blockId}_self">자영</label>
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
						<div class="form">
							<div class="form-title">
								<span>기간</span>
							</div>
							<div class="form-content">
								<input type="date" class="work_start_date" value="${data.work_start_date || ""}">부터
								<input type="date" class="work_end_date" value="${data.work_end_date || ""}">까지
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title">
								<span>직장명/상호</span>
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
								<div style="width:100%;">※ 건강보험자격득실확인서 또는 사업자사실증명을 참고하여 기재하시기 바랍니다.</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		
		$("#career_container").append(html);
		const $block = $("#" + blockId);
		
		$block.find(".career_save_btn").on("click", () => this.saveCareerBlock($block));
		$block.find(".career_delete_btn").on("click", () => this.deleteCareerBlock($block));
	}
	
	saveCareerBlock($block) {
		const careerId = $block.find(".career_id").val();
		const blockId = $block.attr("id");
		
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "career",
			company_type: $block.find(`input[name="company_type_${blockId}"]:checked`).val() || "",
			business_type: $block.find(".business_type").val().trim(),
			company_name: $block.find(".company_name").val().trim(),
			position: $block.find(".position").val().trim(),
			work_start_date: $block.find(".work_start_date").val(),
			work_end_date: $block.find(".work_end_date").val()
		};
		
		if (careerId) {
			data.career_id = careerId;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("경력 정보가 저장되었습니다.");
				$block.find(".career_id").val(response.data.career_id);
				this.loadCareers();
			},
			() => {
				alert("경력 저장 중 오류가 발생했습니다.");
			}
		);
	}
	
	deleteCareerBlock($block) {
		const careerId = $block.find(".career_id").val();
		
		if (!careerId) {
			$block.remove();
			this.checkEmptyCareerContainer();
			return;
		}
		
		if (!confirm("이 경력 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.apiRequest(
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				career_id: careerId,
				statement_type: "career"
			},
			(response) => {
				alert("경력 정보가 삭제되었습니다.");
				$block.remove();
				this.checkEmptyCareerContainer();
			},
			() => {
				alert("경력 삭제 중 오류가 발생했습니다.");
			}
		);
	}
	
	checkEmptyCareerContainer() {
		if ($("#career_container").children().length === 0) {
			this.addCareerBlock();
		}
	}
	
	// ==================== 결혼 관련 메소드 ====================
	loadMarriages() {
		if ($("#marriage_container").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "marriage" 
			},
			(response) => {
				if (Array.isArray(response.data) && response.data.length > 0) {
					this.marriages = response.data;
					this.populateMarriageBlocks(response.data);
				} else {
					this.addMarriageBlock();
				}
			},
			() => {
				this.addMarriageBlock();
			}
		);
	}

	populateMarriageBlocks(marriages) {
		if ($("#marriage_container").length === 0) return;
		
		$("#marriage_container").empty();
		
		if (!Array.isArray(marriages) || marriages.length === 0) {
			this.addMarriageBlock();
			return;
		}
		
		marriages.sort((a, b) => {
			const dateA = new Date(a.marriage_date || '1900-01-01');
			const dateB = new Date(b.marriage_date || '1900-01-01');
			return dateB - dateA;
		});
		
		marriages.forEach((marriage) => {
			this.addMarriageBlock(marriage);
		});
	}

	addMarriageBlock(data = {}) {
		this.marriageCounter++;
		const blockId = "marriage_block_" + this.marriageCounter;
		const marriageId = data.marriage_id || "";
		
		const html = `
			<div class="asset-block marriage-block" id="${blockId}">
				<input type="hidden" class="marriage_id" value="${marriageId}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title">
								<span>결혼/이혼 여부</span>
							</div>
							<div class="form-content">
								<div class="radio">
									<input type="radio" id="${blockId}_marriage" name="marriage_status_${blockId}" value="결혼" ${data.marriage_status === "결혼" ? "checked" : ""}>
									<label for="${blockId}_marriage">결혼</label>
									<input type="radio" id="${blockId}_divorce" name="marriage_status_${blockId}" value="이혼" ${data.marriage_status === "이혼" ? "checked" : ""}>
									<label for="${blockId}_divorce">이혼</label>
									<input type="radio" id="${blockId}_die" name="marriage_status_${blockId}" value="사망" ${data.marriage_status === "사망" ? "checked" : ""}>
									<label for="${blockId}_die">사망</label>
									<input type="radio" id="${blockId}_separate" name="marriage_status_${blockId}" value="별거" ${data.marriage_status === "별거" ? "checked" : ""}>
									<label for="${blockId}_separate">별거</label>
								</div>
							</div>
						</div>
						<div class="form">
							<div class="form-title">
								<span>결혼/이혼 일자</span>
							</div>
							<div class="form-content">
								<input type="date" class="marriage_date" value="${data.marriage_date || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title">
								<span>배우자</span>
							</div>
							<div class="form-content">
								<input type="text" class="spouse_name" value="${data.spouse_name || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle">
								<span></span>
							</div>
							<div class="form-content form-nocontent btn-right">
								<button type="button" class="btn-delete marriage_delete_btn">삭제</button>
								<button type="button" class="btn-save marriage_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		
		$("#marriage_container").append(html);
		const $block = $("#" + blockId);
		
		$block.find(".marriage_save_btn").on("click", () => this.saveMarriageBlock($block));
		$block.find(".marriage_delete_btn").on("click", () => this.deleteMarriageBlock($block));
	}

	saveMarriageBlock($block) {
		const marriageId = $block.find(".marriage_id").val();
		const blockId = $block.attr("id");
		
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "marriage",
			marriage_status: $block.find(`input[name="marriage_status_${blockId}"]:checked`).val() || "",
			marriage_date: $block.find(".marriage_date").val(),
			spouse_name: $block.find(".spouse_name").val().trim()
		};
		
		if (marriageId) {
			data.marriage_id = marriageId;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("결혼/이혼 정보가 저장되었습니다.");
				$block.find(".marriage_id").val(response.data.marriage_id);
				this.loadMarriages();
			},
			() => {
				alert("결혼/이혼 정보 저장 중 오류가 발생했습니다.");
			}
		);
	}

	deleteMarriageBlock($block) {
		const marriageId = $block.find(".marriage_id").val();
		
		if (!marriageId) {
			$block.remove();
			this.checkEmptyMarriageContainer();
			return;
		}
		
		if (!confirm("이 결혼/이혼 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.apiRequest(
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				marriage_id: marriageId,
				statement_type: "marriage"
			},
			(response) => {
				alert("결혼/이혼 정보가 삭제되었습니다.");
				$block.remove();
				this.checkEmptyMarriageContainer();
			},
			() => {
				alert("결혼/이혼 정보 삭제 중 오류가 발생했습니다.");
			}
		);
	}

	checkEmptyMarriageContainer() {
		if ($("#marriage_container").children().length === 0) {
			this.addMarriageBlock();
		}
	}
	
	// ==================== 주거상황 관련 메소드 ====================
	loadHousing() {
		if ($("#housing_container").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "housing" 
			},
			(response) => {
				let housingData = response.data;
				if (Array.isArray(response.data) && response.data.length > 0) {
					housingData = response.data[0];
				}
				
				if (housingData) {
					this.housingData = housingData;
					this.populateHousingForm(housingData);
				}
			}
		);
	}

	populateHousingForm(data) {
		if ($("#housing_type").length === 0) return;
		
		if (data.housing_type) {
			$("#housing_type").val(data.housing_type);
		}
		
		if (data.deposit_amount !== undefined) {
			$("#deposit_amount").val(data.deposit_amount);
		}
		
		if (data.monthly_rent !== undefined) {
			$("#monthly_rent").val(data.monthly_rent);
		}
		
		if (data.overdue_amount !== undefined) {
			$("#overdue_amount").val(data.overdue_amount);
		}

		// 추가 필드들도 채움
		if (data.owner_name !== undefined) {
			$("#owner_name").val(data.owner_name);
		}

		if (data.relationship !== undefined) {
			$("#relationship").val(data.relationship);
		}

		if (data.etc_description !== undefined) {
			$("#etc_description").val(data.etc_description);
		}

		if (data.residence_start_date !== undefined) {
			$("#residence_start_date").val(data.residence_start_date);
		}

		if (data.tenant_name !== undefined) {
			$("#tenant_name").val(data.tenant_name);
		}

		if (data.additional_info !== undefined) {
			$("#additional_info").val(data.additional_info);
		}
	}

	saveHousing() {
		const housingType = $("#housing_type").val();
		
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "housing",
			housing_type: housingType,
			deposit_amount: $("#deposit_amount").val().trim().replace(/,/g, ""),
			monthly_rent: $("#monthly_rent").val().trim().replace(/,/g, ""),
			overdue_amount: $("#overdue_amount").val().trim().replace(/,/g, ""),
			owner_name: $("#owner_name").val().trim(),
			relationship: $("#relationship").val().trim(),
			etc_description: $("#etc_description").val().trim(),
			residence_start_date: $("#residence_start_date").val(),
			tenant_name: $("#tenant_name").val().trim(),
			additional_info: $("#additional_info").val().trim()
		};
		
		if (this.housingData && this.housingData.housing_id) {
			data.housing_id = this.housingData.housing_id;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("주거상황 정보가 저장되었습니다.");
				this.housingData = response.data;
			},
			() => {
				alert("주거상황 저장 중 오류가 발생했습니다.");
			}
		);
	}

	// ==================== 소송/압류 경험 관련 메소드 ====================
	loadLawsuits() {
		if ($("#lawsuit_container").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "lawsuit" 
			},
			(response) => {
				if (Array.isArray(response.data) && response.data.length > 0) {
					this.lawsuits = response.data;
					this.populateLawsuitBlocks(response.data);
				} else {
					this.addLawsuitBlock();
				}
			},
			() => {
				this.addLawsuitBlock();
			}
		);
	}

	populateLawsuitBlocks(lawsuits) {
		if ($("#lawsuit_container").length === 0) return;
		
		$("#lawsuit_container").empty();
		
		if (!Array.isArray(lawsuits) || lawsuits.length === 0) {
			this.addLawsuitBlock();
			return;
		}
		
		lawsuits.sort((a, b) => {
			const dateA = new Date(a.lawsuit_date || '1900-01-01');
			const dateB = new Date(b.lawsuit_date || '1900-01-01');
			return dateB - dateA;
		});
		
		lawsuits.forEach((lawsuit) => {
			this.addLawsuitBlock(lawsuit);
		});
	}

	addLawsuitBlock(data = {}) {
		this.lawsuitCounter++;
		const blockId = "lawsuit_block_" + this.lawsuitCounter;
		const lawsuitId = data.lawsuit_id || "";
		
		const html = `
			<div class="asset-block lawsuit-block" id="${blockId}">
				<input type="hidden" class="lawsuit_id" value="${lawsuitId}">
				<div class="content-wrapper">
					<div class="left-section">
						<div class="form">
							<div class="form-title">
								<span>내역</span>
							</div>
							<div class="form-content">
								<input type="text" class="lawsuit_type" value="${data.lawsuit_type || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title">
								<span>채권자</span>
							</div>
							<div class="form-content">
								<input type="text" class="creditor" value="${data.creditor || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title">
								<span>일자</span>
							</div>
							<div class="form-content">
								<input type="date" class="lawsuit_date" value="${data.lawsuit_date || ""}">
							</div>
						</div>
					</div>
					<div class="right-section">
						<div class="form">
							<div class="form-title">
								<span>관할법원</span>
							</div>
							<div class="form-content">
								<input type="text" class="court" value="${data.court || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title">
								<span>사건번호</span>
							</div>
							<div class="form-content">
								<input type="text" class="case_number" value="${data.case_number || ""}">
							</div>
						</div>
						<div class="form">
							<div class="form-title form-notitle">
								<span></span>
							</div>
							<div class="form-content form-nocontent btn-right">
								<button type="button" class="btn-delete lawsuit_delete_btn">삭제</button>
								<button type="button" class="btn-save lawsuit_save_btn">저장</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		
		$("#lawsuit_container").append(html);
		const $block = $("#" + blockId);
		
		$block.find(".lawsuit_save_btn").on("click", () => this.saveLawsuitBlock($block));
		$block.find(".lawsuit_delete_btn").on("click", () => this.deleteLawsuitBlock($block));
	}

	saveLawsuitBlock($block) {
		const lawsuitId = $block.find(".lawsuit_id").val();
		
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "lawsuit",
			lawsuit_type: $block.find(".lawsuit_type").val().trim(),
			lawsuit_date: $block.find(".lawsuit_date").val(),
			case_number: $block.find(".case_number").val().trim(),
			creditor: $block.find(".creditor").val().trim(),
			court: $block.find(".court").val().trim()
		};
		
		if (lawsuitId) {
			data.lawsuit_id = lawsuitId;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("소송/압류 정보가 저장되었습니다.");
				$block.find(".lawsuit_id").val(response.data.lawsuit_id);
				this.loadLawsuits();
			},
			() => {
				alert("소송/압류 정보 저장 중 오류가 발생했습니다.");
			}
		);
	}

	deleteLawsuitBlock($block) {
		const lawsuitId = $block.find(".lawsuit_id").val();
		
		if (!lawsuitId) {
			$block.remove();
			this.checkEmptyLawsuitContainer();
			return;
		}
		
		if (!confirm("이 소송/압류 정보를 삭제하시겠습니까?")) {
			return;
		}
		
		this.apiRequest(
			"DELETE",
			{ 
				case_no: window.currentCaseNo,
				lawsuit_id: lawsuitId,
				statement_type: "lawsuit"
			},
			(response) => {
				alert("소송/압류 정보가 삭제되었습니다.");
				$block.remove();
				this.checkEmptyLawsuitContainer();
			},
			() => {
				alert("소송/압류 정보 삭제 중 오류가 발생했습니다.");
			}
		);
	}

	checkEmptyLawsuitContainer() {
		if ($("#lawsuit_container").children().length === 0) {
			this.addLawsuitBlock();
		}
	}

	// ==================== 개인회생절차 사유 관련 메소드 ====================
	loadBankruptcyReason() {
		if ($("input[name='bankruptcy_reason[]']").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "bankruptcyReason" 
			},
			(response) => {
				let bankruptcyReasonData = response.data;
				if (Array.isArray(response.data) && response.data.length > 0) {
					bankruptcyReasonData = response.data[0];
				}
				
				if (bankruptcyReasonData) {
					this.bankruptcyReasonData = bankruptcyReasonData;
					this.populateBankruptcyReasonForm(bankruptcyReasonData);
				}
			}
		);
	}

	populateBankruptcyReasonForm(data) {
		if ($("input[name='bankruptcy_reason[]']").length === 0) return;
		
		// 체크박스 모두 해제
		$("input[name='bankruptcy_reason[]']").prop("checked", false);
		
		if (data.reasons) {
			try {
				const reasons = JSON.parse(data.reasons);
				if (Array.isArray(reasons)) {
					reasons.forEach(reason => {
						$(`input[name='bankruptcy_reason[]'][value='${reason}']`).prop("checked", true);
					});
				}
			} catch (e) {
				console.error("사유 데이터 파싱 실패:", e);
			}
		}
		
		if (data.detail !== undefined) {
			$("#bankruptcy_reason_detail").val(data.detail);
		}
	}

	saveBankruptcyReason() {
		const selectedReasons = [];
		$("input[name='bankruptcy_reason[]']:checked").each(function() {
			selectedReasons.push($(this).val());
		});
		
		const data = {
			case_no: window.currentCaseNo,
			statement_type: "bankruptcyReason",
			reasons: JSON.stringify(selectedReasons),
			detail: $("#bankruptcy_reason_detail").val().trim()
		};
		
		if (this.bankruptcyReasonData && this.bankruptcyReasonData.bankruptcy_reason_id) {
			data.bankruptcy_reason_id = this.bankruptcyReasonData.bankruptcy_reason_id;
		}
		
		this.apiRequest(
			"POST",
			data,
			(response) => {
				alert("개인회생절차 사유가 저장되었습니다.");
				this.bankruptcyReasonData = response.data;
			},
			() => {
				alert("개인회생절차 사유 저장 중 오류가 발생했습니다.");
			}
		);
	}

	// ==================== 과거 면책절차 이용상황 관련 메소드 ====================
	loadDebtRelief() {
		if ($("#debt_relief_container").length === 0) return;
		
		this.apiRequest(
			"GET", 
			{ 
				case_no: window.currentCaseNo,
				statement_type: "debtRelief" 
			},
			(response) => {
				let debtReliefData = response.data;
				if (Array.isArray(response.data) && response.data.length > 0) {
					this.populateDebtReliefForm(response.data);
				}
			}
		);
	}

	populateDebtReliefForm(data) {
		if ($("#debt_relief_container").length === 0) return;
		
		// 파산, 면책절차
		if (data[0]) {
			$("#bankruptcy_court").val(data[0].institution || "");
			$("#bankruptcy_date").val(data[0].application_date || "");
			$("#bankruptcy_status").val(data[0].current_status || "");
		}
		
		// 화의, 회생, 개인회생 절차
		if (data[1]) {
			$("#recovery_court").val(data[1].institution || "");
			$("#recovery_date").val(data[1].application_date || "");
			$("#recovery_status").val(data[1].current_status || "");
		}
		
		// 신용회복위원회 워크아웃
		if (data[2]) {
			$("#workout_institution").val(data[2].institution || "");
			$("#workout_date").val(data[2].application_date || "");
			$("#workout_status").val(data[2].current_status || "");
		}
		
		// 배드뱅크
		if (data[3]) {
			$("#badbank_institution").val(data[3].institution || "");
			$("#badbank_date").val(data[3].application_date || "");
			$("#badbank_status").val(data[3].current_status || "");
		}
	}

	saveDebtRelief() {
		// 파산, 면책절차
		const bankruptcyData = {
			case_no: window.currentCaseNo,
			statement_type: "debtRelief",
			relief_type: "파산_면책",
			institution: $("#bankruptcy_court").val().trim(),
			application_date: $("#bankruptcy_date").val(),
			current_status: $("#bankruptcy_status").val().trim()
		};
		
		// 화의, 회생, 개인회생 절차
		const recoveryData = {
			case_no: window.currentCaseNo,
			statement_type: "debtRelief",
			relief_type: "화의_회생",
			institution: $("#recovery_court").val().trim(),
			application_date: $("#recovery_date").val(),
			current_status: $("#recovery_status").val().trim()
		};
		
		// 신용회복위원회 워크아웃
		const workoutData = {
			case_no: window.currentCaseNo,
			statement_type: "debtRelief",
			relief_type: "워크아웃",
			institution: $("#workout_institution").val().trim(),
			application_date: $("#workout_date").val(),
			current_status: $("#workout_status").val().trim()
		};
		
		// 배드뱅크
		const badbankData = {
			case_no: window.currentCaseNo,
			statement_type: "debtRelief",
			relief_type: "배드뱅크",
			institution: $("#badbank_institution").val().trim(),
			application_date: $("#badbank_date").val(),
			current_status: $("#badbank_status").val().trim()
		};
		
		// 모든 데이터를 저장
		const savePromises = [
			this.saveDebtReliefItem(bankruptcyData),
			this.saveDebtReliefItem(recoveryData),
			this.saveDebtReliefItem(workoutData),
			this.saveDebtReliefItem(badbankData)
		];
		
		Promise.all(savePromises)
			.then(() => {
				alert("과거 면책절차 이용상황이 저장되었습니다.");
				this.loadDebtRelief();
			})
			.catch(() => {
				alert("과거 면책절차 이용상황 저장 중 오류가 발생했습니다.");
			});
	}
	
	saveDebtReliefItem(data) {
		return new Promise((resolve, reject) => {
			if (!data.institution && !data.application_date && !data.current_status) {
				resolve(); // 데이터가 없으면 저장하지 않고 성공으로 처리
				return;
			}
			
			this.apiRequest(
				"POST",
				data,
				(response) => {
					resolve(response);
				},
				(error) => {
					reject(error);
				}
			);
		});
	}
}

// 인스턴스 생성
const statementManager = new StatementManager();