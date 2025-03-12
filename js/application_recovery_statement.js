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
      this.loadEducation();
      this.loadCareers();
      this.loadMarriages();
      this.loadHousing();
      this.loadLawsuits();
      this.loadBankruptcyReason();
    } catch (error) {
      console.error("초기화 중 오류가 발생했습니다.", error);
      this.addCareerBlock();
      this.addMarriageBlock();
      this.addLawsuitBlock();
    }
  }

  initializeEventHandlers() {
    const $educationSaveBtn = $("#education_save_btn");
    if ($educationSaveBtn.length > 0) {
      $educationSaveBtn.on("click", () => this.saveEducation());
    }
    
    const $addCareerBtn = $("#add_career");
    if ($addCareerBtn.length > 0) {
      $addCareerBtn.on("click", () => this.addCareerBlock());
    }
    
    const $addMarriageBtn = $("#add_marriage");
    if ($addMarriageBtn.length > 0) {
      $addMarriageBtn.on("click", () => this.addMarriageBlock());
    }
    
    const $housingSaveBtn = $("#housing_save_btn");
    if ($housingSaveBtn.length > 0) {
      $housingSaveBtn.on("click", () => this.saveHousing());
    }
    
    const $addLawsuitBtn = $("#add_lawsuit");
    if ($addLawsuitBtn.length > 0) {
      $addLawsuitBtn.on("click", () => this.addLawsuitBlock());
    }
    
    const $bankruptcyReasonSaveBtn = $("#bankruptcy_reason_save_btn");
    if ($bankruptcyReasonSaveBtn.length > 0) {
      $bankruptcyReasonSaveBtn.on("click", () => this.saveBankruptcyReason());
    }
  }
  
	loadEducation() {
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "GET",
		data: { 
		  case_no: window.currentCaseNo,
		  statement_type: "education" 
		},
		dataType: "json",
		success: (response) => {
		  let educationData = response.data;
		  if (response.success && Array.isArray(response.data)) {
			educationData = response.data[0];
		  }
		  
		  if (response.success && educationData) {
			this.educationData = educationData;
			
			setTimeout(() => {
			  this.populateEducationForm(educationData);
			}, 100);
		  }
		},
		error: (xhr, status, error) => {
		}
	  });
	}
  
  loadCareers() {
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "GET",
      data: { 
        case_no: window.currentCaseNo,
        statement_type: "career" 
      },
      dataType: "json",
      success: (response) => {
        if (response.success && Array.isArray(response.data) && response.data.length > 0) {
          this.careers = response.data;
          this.populateCareerBlocks(response.data);
        } else {
          this.addCareerBlock();
        }
      },
      error: (xhr, status, error) => {
        this.addCareerBlock();
      }
    });
  }
  
  populateEducationForm(data) {
	  if ($("#school_name").length === 0) {
		return;
	  }
	  
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
  
	populateCareerBlocks(careers) {
	  if ($("#career_container").length === 0) {
		return;
	  }
	  
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
	  
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "POST",
		data: data,
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("최종학력 정보가 저장되었습니다.");
			this.educationData = response.data;
		  } else {
			alert(response.message || "최종학력 저장 실패");
		  }
		},
		error: (xhr) => {
		  alert("최종학력 저장 중 오류가 발생했습니다.");
		}
	  });
	}

	saveCareerBlock(block) {
	  const careerId = block.find(".career_id").val();
	  const blockId = block.attr("id");
	  
	  const data = {
		case_no: window.currentCaseNo,
		statement_type: "career",
		company_type: block.find(`input[name="company_type_${blockId}"]:checked`).val() || "",
		business_type: block.find(".business_type").val().trim(),
		company_name: block.find(".company_name").val().trim(),
		position: block.find(".position").val().trim(),
		work_start_date: block.find(".work_start_date").val(),
		work_end_date: block.find(".work_end_date").val()
	  };
	  
	  if (careerId) {
		data.career_id = careerId;
	  }
	  
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "POST",
		data: data,
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("경력 정보가 저장되었습니다.");
			block.find(".career_id").val(response.data.career_id);
			
			this.loadCareers();
		  } else {
			alert(response.message || "경력 저장 실패");
		  }
		},
		error: (xhr) => {
		  alert("경력 저장 중 오류가 발생했습니다.");
		}
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
    const block = $("#" + blockId);
    
    block.find(".career_save_btn").on("click", () => this.saveCareerBlock(block));
    block.find(".career_delete_btn").on("click", () => this.deleteCareerBlock(block));
  }
  
  deleteCareerBlock(block) {
    const careerId = block.find(".career_id").val();
    
    if (!careerId) {
      block.remove();
      this.checkEmptyCareerContainer();
      return;
    }
    
    if (!confirm("이 경력 정보를 삭제하시겠습니까?")) {
      return;
    }
    
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "DELETE",
      data: { 
        case_no: window.currentCaseNo,
        career_id: careerId,
        statement_type: "career"
      },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("경력 정보가 삭제되었습니다.");
          block.remove();
          this.checkEmptyCareerContainer();
        } else {
          alert(response.message || "경력 삭제 실패");
        }
      },
      error: () => {
        alert("경력 삭제 중 오류가 발생했습니다.");
      }
    });
  }
  
  checkEmptyCareerContainer() {
    if ($("#career_container").children().length === 0) {
      this.addCareerBlock();
    }
  }
  
  loadMarriages() {
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "GET",
		data: { 
		  case_no: window.currentCaseNo,
		  statement_type: "marriage" 
		},
		dataType: "json",
		success: (response) => {
		  if (response.success && Array.isArray(response.data) && response.data.length > 0) {
			this.marriages = response.data;
			this.populateMarriageBlocks(response.data);
		  } else {
			this.addMarriageBlock();
		  }
		},
		error: (xhr, status, error) => {
		  this.addMarriageBlock();
		}
	  });
	}

	populateMarriageBlocks(marriages) {
	  if ($("#marriage_container").length === 0) {
		return;
	  }
	  
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
	  const block = $("#" + blockId);
	  
	  block.find(".marriage_save_btn").on("click", () => this.saveMarriageBlock(block));
	  block.find(".marriage_delete_btn").on("click", () => this.deleteMarriageBlock(block));
	}

	saveMarriageBlock(block) {
	  const marriageId = block.find(".marriage_id").val();
	  const blockId = block.attr("id");
	  
	  const data = {
		case_no: window.currentCaseNo,
		statement_type: "marriage",
		marriage_status: block.find(`input[name="marriage_status_${blockId}"]:checked`).val() || "",
		marriage_date: block.find(".marriage_date").val(),
		spouse_name: block.find(".spouse_name").val().trim()
	  };
	  
	  if (marriageId) {
		data.marriage_id = marriageId;
	  }
	  
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "POST",
		data: data,
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("결혼/이혼 정보가 저장되었습니다.");
			block.find(".marriage_id").val(response.data.marriage_id);
			
			this.loadMarriages();
		  } else {
			alert(response.message || "결혼/이혼 정보 저장 실패");
		  }
		},
		error: (xhr) => {
		  alert("결혼/이혼 정보 저장 중 오류가 발생했습니다.");
		}
	  });
	}

	deleteMarriageBlock(block) {
	  const marriageId = block.find(".marriage_id").val();
	  
	  if (!marriageId) {
		block.remove();
		this.checkEmptyMarriageContainer();
		return;
	  }
	  
	  if (!confirm("이 결혼/이혼 정보를 삭제하시겠습니까?")) {
		return;
	  }
	  
	  $.ajax({
		url: "/adm/api/application_recovery/statement/statement_api.php",
		type: "DELETE",
		data: { 
		  case_no: window.currentCaseNo,
		  marriage_id: marriageId,
		  statement_type: "marriage"
		},
		processData: true,
		contentType: "application/x-www-form-urlencoded; charset=UTF-8",
		dataType: "json",
		success: (response) => {
		  if (response.success) {
			alert("결혼/이혼 정보가 삭제되었습니다.");
			block.remove();
			this.checkEmptyMarriageContainer();
		  } else {
			alert(response.message || "결혼/이혼 정보 삭제 실패");
		  }
		},
		error: () => {
		  alert("결혼/이혼 정보 삭제 중 오류가 발생했습니다.");
		}
	  });
	}

	checkEmptyMarriageContainer() {
	  if ($("#marriage_container").children().length === 0) {
		this.addMarriageBlock();
	  }
	}
	
	// 현재주거상황 관련 함수
  loadHousing() {
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "GET",
      data: { 
        case_no: window.currentCaseNo,
        statement_type: "housing" 
      },
      dataType: "json",
      success: (response) => {
        let housingData = response.data;
        if (response.success && Array.isArray(response.data)) {
          housingData = response.data[0];
        }
        
        if (response.success && housingData) {
          this.housingData = housingData;
          setTimeout(() => {
            this.populateHousingForm(housingData);
          }, 100);
        }
      },
      error: (xhr, status, error) => {
        console.error("주거상황 로드 실패:", error);
      }
    });
  }

  populateHousingForm(data) {
    if ($("#housing_type_own").length === 0) {
      return;
    }
    
    if (data.housing_type) {
      const $radio = $(`input[name="housing_type"][value="${data.housing_type}"]`);
      if ($radio.length > 0) {
        $radio.prop("checked", true);
      }
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
  }

  saveHousing() {
    const data = {
      case_no: window.currentCaseNo,
      statement_type: "housing",
      housing_type: $("input[name='housing_type']:checked").val() || "",
      deposit_amount: $("#deposit_amount").val().trim().replace(/,/g, ""),
      monthly_rent: $("#monthly_rent").val().trim().replace(/,/g, ""),
      overdue_amount: $("#overdue_amount").val().trim().replace(/,/g, "")
    };
    
    if (this.housingData && this.housingData.housing_id) {
      data.housing_id = this.housingData.housing_id;
    }
    
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("주거상황 정보가 저장되었습니다.");
          this.housingData = response.data;
        } else {
          alert(response.message || "주거상황 저장 실패");
        }
      },
      error: (xhr) => {
        alert("주거상황 저장 중 오류가 발생했습니다.");
      }
    });
  }

  // 소송/압류 경험 관련 함수
  loadLawsuits() {
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "GET",
      data: { 
        case_no: window.currentCaseNo,
        statement_type: "lawsuit" 
      },
      dataType: "json",
      success: (response) => {
        if (response.success && Array.isArray(response.data) && response.data.length > 0) {
          this.lawsuits = response.data;
          this.populateLawsuitBlocks(response.data);
        } else {
          this.addLawsuitBlock();
        }
      },
      error: (xhr, status, error) => {
        this.addLawsuitBlock();
      }
    });
  }

  populateLawsuitBlocks(lawsuits) {
    if ($("#lawsuit_container").length === 0) {
      return;
    }
    
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
                <div class="radio">
                  <input type="text" class="" value="">
                </div>
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
              <div class="form-title form-notitle">
                <span></span>
              </div>
              <div class="form-content form-nocontent">
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
    const block = $("#" + blockId);
    
    block.find(".lawsuit_save_btn").on("click", () => this.saveLawsuitBlock(block));
    block.find(".lawsuit_delete_btn").on("click", () => this.deleteLawsuitBlock(block));
  }

  saveLawsuitBlock(block) {
    const lawsuitId = block.find(".lawsuit_id").val();
    const blockId = block.attr("id");
    
    const data = {
      case_no: window.currentCaseNo,
      statement_type: "lawsuit",
      lawsuit_type: block.find(`input[name="lawsuit_type_${blockId}"]:checked`).val() || "",
      lawsuit_date: block.find(".lawsuit_date").val(),
      case_number: block.find(".case_number").val().trim(),
      creditor: block.find(".creditor").val().trim(),
      court: block.find(".court").val().trim()
    };
    
    if (lawsuitId) {
      data.lawsuit_id = lawsuitId;
    }
    
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("소송/압류 정보가 저장되었습니다.");
          block.find(".lawsuit_id").val(response.data.lawsuit_id);
          
          this.loadLawsuits();
        } else {
          alert(response.message || "소송/압류 정보 저장 실패");
        }
      },
      error: (xhr) => {
        alert("소송/압류 정보 저장 중 오류가 발생했습니다.");
      }
    });
  }

  deleteLawsuitBlock(block) {
    const lawsuitId = block.find(".lawsuit_id").val();
    
    if (!lawsuitId) {
      block.remove();
      this.checkEmptyLawsuitContainer();
      return;
    }
    
    if (!confirm("이 소송/압류 정보를 삭제하시겠습니까?")) {
      return;
    }
    
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "DELETE",
      data: { 
        case_no: window.currentCaseNo,
        lawsuit_id: lawsuitId,
        statement_type: "lawsuit"
      },
      processData: true,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("소송/압류 정보가 삭제되었습니다.");
          block.remove();
          this.checkEmptyLawsuitContainer();
        } else {
          alert(response.message || "소송/압류 정보 삭제 실패");
        }
      },
      error: () => {
        alert("소송/압류 정보 삭제 중 오류가 발생했습니다.");
      }
    });
  }

  checkEmptyLawsuitContainer() {
    if ($("#lawsuit_container").children().length === 0) {
      this.addLawsuitBlock();
    }
  }

  // 개인회생절차 사유 관련 함수
  loadBankruptcyReason() {
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "GET",
      data: { 
        case_no: window.currentCaseNo,
        statement_type: "bankruptcyReason" 
      },
      dataType: "json",
      success: (response) => {
        let bankruptcyReasonData = response.data;
        if (response.success && Array.isArray(response.data)) {
          bankruptcyReasonData = response.data[0];
        }
        
        if (response.success && bankruptcyReasonData) {
          this.bankruptcyReasonData = bankruptcyReasonData;
          setTimeout(() => {
            this.populateBankruptcyReasonForm(bankruptcyReasonData);
          }, 100);
        }
      },
      error: (xhr, status, error) => {
        console.error("개인회생절차 사유 로드 실패:", error);
      }
    });
  }

  populateBankruptcyReasonForm(data) {
    if ($("input[name='bankruptcy_reason[]']").length === 0) {
      return;
    }
    
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
    
    $.ajax({
      url: "/adm/api/application_recovery/statement/statement_api.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: (response) => {
        if (response.success) {
          alert("개인회생절차 사유가 저장되었습니다.");
          this.bankruptcyReasonData = response.data;
        } else {
          alert(response.message || "개인회생절차 사유 저장 실패");
        }
      },
      error: (xhr) => {
        alert("개인회생절차 사유 저장 중 오류가 발생했습니다.");
      }
    });
  }
}