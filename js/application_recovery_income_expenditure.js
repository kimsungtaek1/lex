class ApplicationRecoveryIncomeExpenditure {
  constructor() {
    this.currentYear = new Date().getFullYear();
    this.isCompany = false;
    this.isSaving = false;
    this.initialize();
  }

  initialize() {
    try {
      this.loadIncomeType();
      this.loadBusinessIncome();
      this.initializeEventHandlers();
      this.initializeSalarySection();
      this.initializeBusinessSection();
      this.initializeFamilySection();
      this.loadYearOptions();
      this.initializeLivingExpenseSection();
      // 데이터 직접 로드 함수 호출 추가
      this.loadOtherFeeData();
      this.loadTrusteeFeeData();
      this.loadAdditionalExpenseData();
      this.initializePlan10Section();
      $('#iex_year').on('change', () => {
        const selectedYear = $('#iex_year').val();
        this.updateLivingExpenseStandards(selectedYear);
      });
      $('#living_expense_table').on('click', () => this.openLivingExpenseTable());

      $('#iex_plan10_save_btn').off('click').on('click', () => {
          console.log('iex_plan10_save_btn clicked (direct)');
        this.savePlan10Data();
      });

    } catch (error) {
      console.error("초기화 실패:", error);
      alert("초기화 중 오류가 발생했습니다.");
    }
  }

  initializeEventHandlers() {
    // 급여수입 섹션 이벤트
    $('#iex_salary_calc_btn').off('click').on('click', () => this.openSalaryCalculator());
    $('#iex_salary_save_btn').off('click').on('click', () => this.saveSalaryIncome());
    
    // 자영수입 섹션 이벤트 
    $('#iex_business_type').off('change').on('change', () => this.handleBusinessTypeChange());
    $('#iex_monthly_income').off('input').on('input', (e) => this.calculateYearlyIncome(e.target.value));
    $('#iex_business_save_btn').off('click').on('click', () => this.saveBusinessIncome());

    // 가족관계 섹션 이벤트
    $('#iex_family_add_btn').off('click').on('click', () => this.addFamilyRow());
    $('.iex_family_save_btn').off('click').on('click', (e) => this.saveFamilyMember(e));
    $('.iex_family_delete_btn').off('click').on('click', (e) => this.deleteFamilyMember(e));

    // 생계비 섹션 이벤트
    $('input[name="iex_expense_range"]').off('change').on('change', (e) => this.handleExpenseRangeChange(e));
    $('#iex_direct_input').off('change').on('change', (e) => this.handleDirectInputChange(e));
    // 팝업 버튼 이벤트 핸들러 복원
    $('#iex_expense_calc_btn').off('click').on('click', () => this.openAdditionalExpenseCalculator());
    $('#iex_trustee_fee_btn').off('click').on('click', () => this.openTrusteeFeeCalculator());
    $('#iex_other_fee_btn').off('click').on('click', () => this.openOtherFeeCalculator());

    // 변제계획 이벤트
    $('#iex-monthCountValue').off('input').on('input', () => this.calculateMonthlyPayment());
    $('#iex-calcPlanBtn').off('click').on('click', () => this.calculateRepaymentPlan());
    
    $('#iex_plan10_save_btn').off('click').on('click', () => {
      console.log('iex_plan10_save_btn clicked (in event handlers)');
      this.savePlan10Data();
    });

    // 공통 이벤트
    this.initializeMoneyInputs();

  }

  loadIncomeType() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/application_api.php',
      type: 'GET',
      data: { case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          this.isCompany = response.data.is_company === 1;
          this.toggleIncomeSections();
          this.loadSalaryData();
        }
      },
      error: () => {
        console.error('소득자 구분 로드 중 오류 발생');
      }
    });
  }

  loadSalaryData() {
    if (this.isCompany) return;
    
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/salary_income_api.php',
      type: 'GET',
      data: { case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          this.populateSalaryData(response.data);
        }
      },
      error: () => {
        console.error('급여 소득 데이터 로드 중 오류 발생');
      }
    });
  }
  
  loadBusinessIncome() {
  const caseNo = window.currentCaseNo;
  if (!caseNo) return;

  $.ajax({
    url: '/adm/api/application_recovery/income/business_income_api.php',
    type: 'GET',
    data: { case_no: caseNo },
    dataType: 'json',
    success: (response) => {
      if (response.success && response.data) {
        // 자영수입 데이터 채우기
        $('#iex_business_type').val(response.data.type || '사업소득');
        $('#iex_business_type_etc').val(response.data.type_etc || '');
        $('#iex_monthly_income').val(this.formatMoney(response.data.monthly_income || 0));
        $('#iex_yearly_income').val(this.formatMoney(response.data.yearly_income || 0));
        $('#iex_business_name').val(response.data.business_name || '');
        $('#iex_business_sector').val(response.data.sector || '');
        $('#iex_business_career').val(response.data.career || '');

        // 기타 타입일 경우 기타 입력란 활성화
        const etcInput = $('#iex_business_type_etc');
        etcInput.prop('disabled', response.data.type !== '기타(임의입력)');
      }
    },
    error: (xhr, status, error) => {
      console.error('자영수입 데이터 로드 중 오류 발생:', error);
    }
  });
}

  populateSalaryData(data) {
    $('#iex_salary_avg_income').val(this.formatMoney(data.monthly_income || 0));
    $('#iex_salary_yearly_income').val(this.formatMoney(data.yearly_income || 0));
    $(`input[name="iex_salary_seizure"][value="${data.is_seized || 'N'}"]`).prop('checked', true);
    $('#iex_company_name').val(data.company_name || '');
    $('#iex_position').val(data.position || '');
    $('#iex_work_period').val(data.work_period || '');
  }

  toggleIncomeSections() {
    if (this.isCompany) {
      $('#salaryIncomeSection').hide();
      $('#businessIncomeSection').show();
    } else {
      $('#salaryIncomeSection').show();
      $('#businessIncomeSection').hide();
    }
  }

  initializeMoneyInputs() {
    const moneyInputs = [
      '#iex_salary_avg_income',
      '#iex_salary_yearly_income',
      '#iex_monthly_income',
      '#iex_yearly_income',
      '#iex_family_income',
      '#iex_family_assets',
      '#iex_living_expense',
      '#iex_additional_expense',
      '#iex_other_fee',
      '#iex_trustee_fee_rate' // 외부회생위원 보수율 필드 추가
    ];

    moneyInputs.forEach(selector => {
      $(selector).off('input').on('input', (e) => {
        const val = e.target.value.replace(/[^\d]/g, '');
        e.target.value = this.formatMoney(val);
      });
    });
  }

  saveSalaryIncome() {
    if (this.isCompany) {
      alert('급여소득자만 저장할 수 있습니다.');
      return;
    }

    if (this.isSaving) return;
    this.isSaving = true;

    const caseNo = window.currentCaseNo;
    if (!caseNo) {
      alert('사건 번호가 필요합니다.');
      this.isSaving = false;
      return;
    }

    const data = {
      case_no: caseNo,
      monthly_income: this.unformatMoney($('#iex_salary_avg_income').val()),
      yearly_income: this.unformatMoney($('#iex_salary_yearly_income').val()),
      is_seized: $('input[name="iex_salary_seizure"]:checked').val() || 'N',
      company_name: $('#iex_company_name').val().trim(),
      position: $('#iex_position').val().trim(),
      work_period: $('#iex_work_period').val().trim()
    };

    $.ajax({
      url: '/adm/api/application_recovery/income/salary_income_api.php',
      type: 'POST',
      data: data,
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          alert('급여 수입이 저장되었습니다.');
        } else {
          alert(response.message || '급여 수입 저장 실패');
        }
      },
      error: (xhr, status, error) => {
        console.error('급여 수입 저장 중 오류:', error);
        alert('급여 수입 저장 중 오류가 발생했습니다.');
      },
      complete: () => {
        this.isSaving = false;
      }
    });
  }

  initializeSalarySection() {
    $('#iex_salary_avg_income').off('input').on('input', (e) => {
      const monthly = this.unformatMoney(e.target.value);
      const yearly = monthly * 12;
      $('#iex_salary_yearly_income').val(this.formatMoney(yearly));
    });
  }

  openSalaryCalculator() {
	const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    window.open('/adm/api/application_recovery/income/salary_calculator.php?case_no=' + caseNo,
      '월평균소득계산기',
      'width=1600,height=800,scrollbars=yes');
  }

  formatMoney(amount) {
    if (!amount) return '0';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  unformatMoney(str) {
    if (!str) return 0;
    return parseInt(str.replace(/,/g, '')) || 0;
  }
  
  handleBusinessTypeChange() {
    const type = $('#iex_business_type').val();
    const etcInput = $('#iex_business_type_etc');
    etcInput.prop('disabled', type !== '기타(임의입력)');
    if (type !== '기타(임의입력)') {
      etcInput.val('');
    }
  }

  calculateYearlyIncome(monthlyIncome) {
    const monthly = this.unformatMoney(monthlyIncome);
    const yearly = monthly * 12;
    $('#iex_yearly_income').val(this.formatMoney(yearly));
  }

  saveBusinessIncome() {
    if (!this.isCompany) {
      alert('영업소득자만 저장할 수 있습니다.');
      return;
    }

    if (this.isSaving) return;
    this.isSaving = true;

    const data = {
      case_no: window.currentCaseNo,
      type: $('#iex_business_type').val(),
      type_etc: $('#iex_business_type_etc').val().trim(),
      monthly_income: this.unformatMoney($('#iex_monthly_income').val()),
      yearly_income: this.unformatMoney($('#iex_yearly_income').val()),
      business_name: $('#iex_business_name').val().trim(),
      sector: $('#iex_business_sector').val().trim(),
      career: $('#iex_business_career').val().trim()
    };

    $.ajax({
      url: '/adm/api/application_recovery/income/business_income_api.php',
      type: 'POST',
      data: data,
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          alert('자영 수입이 저장되었습니다.');
        } else {
          alert(response.message || '자영 수입 저장 실패');
        }
      },
      error: () => {
        alert('자영 수입 저장 중 오류가 발생했습니다.');
      },
      complete: () => {
        this.isSaving = false;
      }
    });
  }

	addFamilyRow() {
		const newRowId = Date.now();
		const html = `
			<tr>
				<td><input type="text" class="form-control iex_family_relation"></td>
				<td><input type="text" class="form-control iex_family_name"></td>
				<td><input type="text" class="form-control iex_family_age">세</td>
				<td>
					<input type="radio" id="iex_family_live_y_${newRowId}" name="iex_family_live_together_${newRowId}" value="Y">
					<label for="iex_family_live_y_${newRowId}">동거</label>
					<input type="radio" id="iex_family_live_n_${newRowId}" name="iex_family_live_together_${newRowId}" value="N">
					<label for="iex_family_live_n_${newRowId}">별거</label>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;기간 | 
					<input type="text" class="form-control iex_family_live_period">
				</td>
				<td><input type="text" class="form-control iex_family_job"></td>
				<td><input type="text" class="form-control iex_family_income money">원</td>
				<td><input type="text" class="form-control iex_family_assets money">원</td>
				<td>
					<input type="radio" id="iex_family_support_y_${newRowId}" name="iex_family_support_${newRowId}" value="Y">
					<label for="iex_family_support_y_${newRowId}">유</label>
					<input type="radio" id="iex_family_support_n_${newRowId}" name="iex_family_support_${newRowId}" value="N">
					<label for="iex_family_support_n_${newRowId}">무</label>
				</td>
				<td>
					<div class="button-group">
						<button class="btn-save iex_family_save_btn">저장</button>
						<button class="btn-delete iex_family_delete_btn">삭제</button>
					</div>
				</td>
			</tr>
		`;
		$('#familyRelationshipSection .long-table tbody').append(html);
		
		// 새로 추가된 행의 이벤트 핸들러 등록
		const newRow = $('#familyRelationshipSection .long-table tbody tr:last');
		newRow.find('.money').on('input', (e) => {
			const val = e.target.value.replace(/[^\d]/g, '');
			e.target.value = this.formatMoney(val);
		});
		newRow.find('.iex_family_save_btn').on('click', (e) => this.saveFamilyMember(e));
		newRow.find('.iex_family_delete_btn').on('click', (e) => this.deleteFamilyMember(e));
	}

	populateFamilyMembers(members) {
		if (!Array.isArray(members)) return;
		
		members.forEach(member => {
			const newRowId = member.member_no;
			const row = `
				<tr data-member-no="${member.member_no}">
					<td><input type="text" class="form-control iex_family_relation" value="${member.relation || ''}"></td>
					<td><input type="text" class="form-control iex_family_name" value="${member.name || ''}"></td>
					<td><input type="text" class="form-control iex_family_age" value="${member.age || ''}">세</td>
					<td>
						<input type="radio" id="iex_family_live_y_${newRowId}" name="iex_family_live_together_${newRowId}" value="Y" ${member.live_together === 'Y' ? 'checked' : ''}>
						<label for="iex_family_live_y_${newRowId}">동거</label>
						<input type="radio" id="iex_family_live_n_${newRowId}" name="iex_family_live_together_${newRowId}" value="N" ${member.live_together === 'N' ? 'checked' : ''}>
						<label for="iex_family_live_n_${newRowId}">별거</label>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;기간 | 
						<input type="text" class="form-control iex_family_live_period" value="${member.live_period || ''}">
					</td>
					<td><input type="text" class="form-control iex_family_job" value="${member.job || ''}"></td>
					<td><input type="text" class="form-control iex_family_income money" value="${this.formatMoney(member.income || 0)}">원</td>
					<td><input type="text" class="form-control iex_family_assets money" value="${this.formatMoney(member.assets || 0)}">원</td>
					<td>
						<input type="radio" id="iex_family_support_y_${newRowId}" name="iex_family_support_${newRowId}" value="Y" ${member.support === 'Y' ? 'checked' : ''}>
						<label for="iex_family_support_y_${newRowId}">유</label>
						<input type="radio" id="iex_family_support_n_${newRowId}" name="iex_family_support_${newRowId}" value="N" ${member.support === 'N' ? 'checked' : ''}>
						<label for="iex_family_support_n_${newRowId}">무</label>
					</td>
					<td>
						<div class="button-group">
							<button class="btn-save iex_family_save_btn">저장</button>
							<button class="btn-delete iex_family_delete_btn">삭제</button>
						</div>
					</td>
				</tr>
			`;
			$('#familyRelationshipSection .long-table tbody').append(row);
			
			// 새로 추가된 행의 이벤트 바인딩
			const newRow = $('#familyRelationshipSection .long-table tbody tr:last');
			newRow.find('.money').on('input', (e) => {
				const val = e.target.value.replace(/[^\d]/g, '');
				e.target.value = this.formatMoney(val);
			});
			newRow.find('.iex_family_save_btn').on('click', (e) => this.saveFamilyMember(e));
			newRow.find('.iex_family_delete_btn').on('click', (e) => this.deleteFamilyMember(e));
		});
	}

	saveFamilyMember(e) {
		if (this.isSaving) return;
		this.isSaving = true;

		const row = $(e.target).closest('tr');
		const memberNo = row.attr('data-member-no');
		const data = {
			case_no: window.currentCaseNo,
			member_no: memberNo || '', // 기존에 저장된 member_no 포함
			relation: row.find('.iex_family_relation').val()?.trim() || '',
			name: row.find('.iex_family_name').val()?.trim() || '',
			age: row.find('.iex_family_age').val() || 0,
			live_together: row.find('input[name^="iex_family_live_together"]:checked').val() || 'N',
			live_period: row.find('.iex_family_live_period').val()?.trim() || '',
			job: row.find('.iex_family_job').val()?.trim() || '',
			income: this.unformatMoney(row.find('.iex_family_income').val()),
			assets: this.unformatMoney(row.find('.iex_family_assets').val()),
			support: row.find('input[name^="iex_family_support"]:checked').val() || 'N'
		};

		if (!data.relation || !data.name) {
			alert('관계와 성명은 필수입력 항목입니다.');
			this.isSaving = false;
			return;
		}

		$.ajax({
			url: '/adm/api/application_recovery/income/family_member_api.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert(memberNo ? '가족관계 정보가 수정되었습니다.' : '가족관계 정보가 저장되었습니다.');
					if (response.data?.member_no) {
						row.attr('data-member-no', response.data.member_no);
					}
					// 가족 수 변경 후 생계비 기준 업데이트
					const selectedYear = $('#iex_year').val();
					this.updateLivingExpenseStandards(selectedYear);
				} else {
					alert(response.message || '가족관계 정보 저장 실패');
				}
			},
			error: () => {
				alert('가족관계 정보 저장 중 오류가 발생했습니다.');
			},
			complete: () => {
				this.isSaving = false;
			}
		});
	}

  deleteFamilyMember(e) {
    const row = $(e.target).closest('tr');
    const memberNo = row.attr('data-member-no');

    if (!memberNo) {
      row.remove();
      return;
    }

    if (!confirm('가족관계 정보를 삭제하시겠습니까?')) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/family_member_api.php',
      type: 'DELETE',
      data: {
        case_no: window.currentCaseNo,
        member_no: memberNo
      },
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          alert('가족관계 정보가 삭제되었습니다.');
          row.remove();
          
          // 삭제 후 가족 수 다시 계산하여 생계비 기준 업데이트
          const selectedYear = $('#iex_year').val();
          this.updateLivingExpenseStandards(selectedYear);
        } else {
          alert(response.message || '가족관계 정보 삭제 실패');
        }
      },
      error: () => {
        alert('가족관계 정보 삭제 중 오류가 발생했습니다.');
      }
    });
  }

  handleExpenseRangeChange(e) {
    const isStandard = e.target.value === 'Y';
    const livingExpenseInput = $('#iex_living_expense');
    livingExpenseInput.prop('readonly', isStandard);

    if (isStandard) {
      // 기준 범위 내 선택 시: 표준 금액 설정 및 추가 생계비 데이터 삭제
      const familyCount = parseInt($('#iex_family_count').val()) || 1;
      const standardAmount = this.getStandardLivingExpense(familyCount);
      livingExpenseInput.val(this.formatMoney(standardAmount));
      this.deleteAdditionalExpenseData(); // 추가 생계비 데이터 삭제 함수 호출
    } else {
      // 기준 범위 초과 선택 시: 중위소득 비율 재계산 (기존 로직 유지)
      this.getFamilyCountFromDB((familyCount) => {
        const standardAmount = this.getStandardLivingExpense(familyCount);
        const midIncome = standardAmount * (100/60);
        const currentExpense = this.unformatMoney(livingExpenseInput.val());
        const percentageOfMidIncome = midIncome > 0 ? (currentExpense / midIncome * 100).toFixed(2) : 0;
        $('#iex_income_ratio').val(percentageOfMidIncome);
      });
    }

    this.calculateTotalExpense();
  }

  handleDirectInputChange(e) {
    $('#iex_living_expense').prop('readonly', !e.target.checked);
  }

	getStandardLivingExpense(familyCount) {
		// form-content에서 familyCount번째 input의 값을 가져옴 
		const standardAmount = this.unformatMoney($('#standard_amount_container input').eq(familyCount - 1).val());
		return standardAmount || 0; // 값이 없으면 0 반환
	}

  calculateTotalExpense() {
    const livingExpense = this.unformatMoney($('#iex_living_expense').val());
    const additionalExpense = this.unformatMoney($('#iex_additional_expense').val());
    // 참고: 외부회생위원 보수와 기타 개인회생재단 채권은 현재 총 지출 계산에 포함되지 않음. 필요시 로직 수정.
    const totalExpense = livingExpense + additionalExpense;

    $('#iex-livingExpenseValue').val(this.formatMoney(totalExpense));
    this.calculateMonthlyPayment();
  }

  calculateMonthlyPayment() {
    const monthCount = parseInt($('#iex-monthCountValue').val()) || 36;
    const livingExpense = this.unformatMoney($('#iex-livingExpenseValue').val());
    const monthlyPayment = Math.round(livingExpense / monthCount);
    
    $('#iex-monthPaymentValue').val(this.formatMoney(monthlyPayment));
    this.calculateRepaymentRate();
  }

  calculateRepaymentRate() {
    const totalDebt = this.unformatMoney($('#totalDebtAmount').val());
    const monthlyPayment = this.unformatMoney($('#iex-monthPaymentValue').val());
    const monthCount = parseInt($('#iex-monthCountValue').val()) || 36;
    
    if (totalDebt > 0) {
      const totalPayment = monthlyPayment * monthCount;
      const rate = (totalPayment / totalDebt) * 100;
      $('#iex-repaymentRateValue').val(rate.toFixed(2));
    }
  }

  // --- 팝업 창 여는 함수들 (닫힐 때 데이터 리로드 추가) ---

  openAdditionalExpenseCalculator() {
    // 기준 범위 내 생계비 선택 시 추가 생계비 입력 불가
    const isStandard = $('input[name="iex_expense_range"]:checked').val() === 'Y';
    if (isStandard) {
      alert('추가 생계비는 기준 범위 초과 생계비를 선택한 경우에만 입력 가능합니다.');
      return;
    }

    const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    const popup = window.open('/adm/api/application_recovery/income/additional_expense_calculator.php?case_no=' + caseNo, '추가생계비계산기', 'width=1000,height=600,scrollbars=yes');
    this.monitorPopupClose(popup, () => this.loadAdditionalExpenseData());
  }

  openTrusteeFeeCalculator() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    const popup = window.open('/adm/api/application_recovery/income/trustee_fee_calculator.php?case_no=' + caseNo, '외부회생위원보수계산기', 'width=1000,height=1200,scrollbars=yes');
    this.monitorPopupClose(popup, () => this.loadTrusteeFeeData());
  }

  openOtherFeeCalculator() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    const popup = window.open('/adm/api/application_recovery/income/other_fee_calculator.php?case_no=' + caseNo, '기타재단채권계산기', 'width=1000,height=1200,scrollbars=yes');
    this.monitorPopupClose(popup, () => this.loadOtherFeeData());
  }

  // 팝업 창 닫힘 감지 및 콜백 실행 함수
  monitorPopupClose(popup, callback) {
    if (!popup) return;
    const timer = setInterval(() => {
      if (popup.closed) {
        clearInterval(timer);
        if (typeof callback === 'function') {
          callback();
        }
      }
    }, 500); // 0.5초마다 확인
  }


  // --- 데이터 직접 로딩 함수들 ---

  loadOtherFeeData() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/other_fee_calculator.php',
      type: 'GET',
      data: { action: 'get', case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          let totalOtherFee = 0;
          response.data.forEach(item => {
            totalOtherFee += parseInt(item.amount) || 0;
          });
          $('#iex_other_fee').val(this.formatMoney(totalOtherFee));
          // 필요시 총 지출 계산 업데이트
          // this.calculateTotalExpense();
        } else {
           $('#iex_other_fee').val(this.formatMoney(0)); // 데이터 없으면 0으로 초기화
        }
      },
      error: (xhr, status, error) => {
        console.error('기타 개인회생재단 채권 데이터 로드 중 오류 발생:', error);
         $('#iex_other_fee').val(this.formatMoney(0)); // 오류 시 0으로 초기화
      }
    });
  }

  loadTrusteeFeeData() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/trustee_fee_calculator.php',
      type: 'GET',
      data: { action: 'get', case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          // 외부회생위원 선임 여부에 따라 보수율 설정
          const isExternalTrustee = response.data.trustee === 'yes';
          const feeRate = isExternalTrustee ? (response.data.fee || 0) : 0;
          $('#iex_trustee_fee_rate').val(feeRate);
          // 필요시 총 지출 계산 업데이트
          // this.calculateTotalExpense();
        } else {
           $('#iex_trustee_fee_rate').val(0); // 데이터 없으면 0으로 초기화
        }
      },
      error: (xhr, status, error) => {
        console.error('외부회생위원 보수 데이터 로드 중 오류 발생:', error);
         $('#iex_trustee_fee_rate').val(0); // 오류 시 0으로 초기화
      }
    });
  }

  loadAdditionalExpenseData() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/additional_expense_calculator.php',
      type: 'GET',
      data: { action: 'get', case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          let totalAdditionalExpense = 0;
          response.data.forEach(item => {
            totalAdditionalExpense += parseInt(item.amount) || 0;
          });
          $('#iex_additional_expense').val(this.formatMoney(totalAdditionalExpense));
          // 추가 생계비 로드 후 총 지출 계산 업데이트
          this.calculateTotalExpense();
        } else {
           $('#iex_additional_expense').val(this.formatMoney(0)); // 데이터 없으면 0으로 초기화
           this.calculateTotalExpense(); // 총 지출 업데이트
        }
      },
      error: (xhr, status, error) => {
        console.error('추가 생계비 데이터 로드 중 오류 발생:', error);
         $('#iex_additional_expense').val(this.formatMoney(0)); // 오류 시 0으로 초기화
         this.calculateTotalExpense(); // 총 지출 업데이트
      }
    });
  }

  // 추가 생계비 데이터 삭제 함수
  deleteAdditionalExpenseData() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    // 사용자에게 삭제 여부 확인 (선택 사항)
    // if (!confirm('기준 범위 내 생계비를 선택하면 입력된 추가 생계비 정보가 삭제됩니다. 계속하시겠습니까?')) {
    //   // 이전 선택 상태로 되돌리기 (필요 시 구현)
    //   $('input[name="iex_expense_range"][value="N"]').prop('checked', true);
    //   return;
    // }

    $.ajax({
      url: '/adm/api/application_recovery/income/additional_expense_calculator.php',
      type: 'POST', // DELETE 메서드 대신 POST 사용 (서버 API가 POST로 delete 액션을 처리)
      data: {
        action: 'delete',
        case_no: caseNo
      },
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          console.log('추가 생계비 데이터가 삭제되었습니다.');
          // 화면 업데이트: 추가 생계비 0으로 설정 및 총 지출 재계산
          $('#iex_additional_expense').val(this.formatMoney(0));
          this.calculateTotalExpense();
        } else {
          alert(response.message || '추가 생계비 데이터 삭제 실패');
          // 실패 시 이전 선택 상태로 되돌릴 수 있음 (필요 시 구현)
        }
      },
      error: (xhr, status, error) => {
        console.error('추가 생계비 삭제 중 오류 발생:', error);
        alert('추가 생계비 삭제 중 오류가 발생했습니다.');
        // 오류 시 이전 선택 상태로 되돌릴 수 있음 (필요 시 구현)
      }
    });
  }

  initializeBusinessSection() {
	  // 기존 이벤트 핸들러
	  $('#iex_business_type').on('change', () => this.handleBusinessTypeChange());
	  $('#iex_business_save_btn').on('click', () => this.saveBusinessIncome());
	  
	  // 월소득 입력 시 연소득 자동 계산
	  $('#iex_monthly_income').on('input', (e) => {
		const monthly = this.unformatMoney(e.target.value);
		$('#iex_yearly_income').val(this.formatMoney(monthly * 12));
	  });

	  // 숫자 포맷팅 적용
	  $('#iex_monthly_income, #iex_yearly_income').on('input', (e) => {
		const val = e.target.value.replace(/[^\d]/g, '');
		e.target.value = this.formatMoney(val);
	  });
	}

	initializeFamilySection() {
		const caseNo = window.currentCaseNo;
		if (!caseNo) return;

		$.ajax({
			url: '/adm/api/application_recovery/income/family_member_api.php',
			type: 'GET',
			data: { case_no: caseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data && response.data.length > 0) {
					this.populateFamilyMembers(response.data);
				} else {
					// 가족 데이터가 없으면 빈 행 추가
					this.addFamilyRow();
				}
				
				// 첫 번째 행의 이벤트 핸들러 등록
				this.initializeFirstRowEvents();
			},
			error: () => {
				console.error('가족구성원 데이터 로드 실패');
				// 데이터 로드 실패 시에도 빈 행 추가
				this.addFamilyRow();
				this.initializeFirstRowEvents();
			}
		});
	}
	
	initializeFirstRowEvents() {
		const firstRow = $('#familyRelationshipSection .long-table tbody tr:first');
		
		// 첫 번째 행의 금액 입력 필드에 이벤트 바인딩
		firstRow.find('.money').on('input', (e) => {
			const val = e.target.value.replace(/[^\d]/g, '');
			e.target.value = this.formatMoney(val);
		});

		// 첫 번째 행의 저장 버튼 이벤트
		firstRow.find('.iex_family_save_btn').on('click', (e) => this.saveFamilyMember(e));
	}
	
	loadYearOptions() {
		const currentYear = new Date().getFullYear();
		
		const $yearSelect = $('#iex_year');
		$yearSelect.empty(); // 기존 옵션 제거

		// 현재 연도만 옵션으로 추가
		const $option = $('<option>', {
			value: currentYear,
			text: currentYear + '년',
			selected: true
		});
		$yearSelect.append($option);

		// 현재 연도로 생계비 기준 업데이트
		this.updateLivingExpenseStandards(currentYear);
	}

	// 데이터베이스에서 부양가족 수 계산하는 함수
	getFamilyCountFromDB(callback) {
		const caseNo = window.currentCaseNo;
		if (!caseNo) {
			callback(1); // 기본값 1 (본인만)
			return;
		}

		$.ajax({
			url: '/adm/api/application_recovery/income/family_count_api.php',
			type: 'GET',
			data: { case_no: caseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					callback(response.family_count);
				} else {
					console.error('가족 수 조회 실패:', response.message);
					callback(1); // 오류 시 기본값 1 (본인만)
				}
			},
			error: (xhr, status, error) => {
				console.error('가족 수 조회 중 오류 발생:', error);
				callback(1); // 오류 시 기본값 1 (본인만)
			}
		});
	}

updateLivingExpenseStandards(year) {
	// 데이터베이스에서 가족 수를 가져옴
	this.getFamilyCountFromDB((familyCount) => {
		// 받아온 가족 수로 생계비 기준 업데이트
		$.ajax({
			url: '/adm/api/application_recovery/income/living_expense_standard_api.php',
			type: 'GET',
			data: { year: year },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					// 가족 수를 화면에 표시
					$('#iex_family_count').val(familyCount);

					$('#standard_amount_container input').each((index, element) => {
						const members = $(element).data('family-members');
						const amount = response.data[members] || 0;
						
						// 해당 가족 구성원 수의 금액
						$(element).val(this.formatNumber(amount));
						
						// 총 가족 구성원 수와 일치하는 경우
						if (parseInt(members) === familyCount) {
							// 수정: 최소값은 기준중위소득 60%의 2/3, 최대값은 기준중위소득 60% 그대로
							const minValue = Math.round(amount * 2 / 3);
							const maxValue = amount;
							
							$('#iex_range_min').val(this.formatNumber(minValue));
							$('#iex_range_max').val(this.formatNumber(maxValue));
							
							// 생계비 범위 체크에 따라 생계비 금액 설정
							const isStandard = $('input[name="iex_expense_range"]:checked').val() === 'Y';
							if (isStandard) {
								$('#iex_living_expense').val(this.formatMoney(amount));
								// 중위소득 비율 설정 (60%)
								$('#iex_income_ratio').val('60.00');
								this.calculateTotalExpense();
							} else {
								// 기준 범위 초과 시 중위소득 비율 재계산
								const currentExpense = this.unformatMoney($('#iex_living_expense').val());
								const midIncome = amount * (100/60);
								const percentageOfMidIncome = midIncome > 0 ? (currentExpense / midIncome * 100).toFixed(2) : 0;
								
								$('#iex_income_ratio').val(percentageOfMidIncome);
							}
						}
					});
				} else {
					alert('해당 연도의 생계비 기준 데이터를 찾을 수 없습니다.');
					$('#standard_amount_container input, #iex_range_min, #iex_range_max').val(0);
					$('#iex_income_ratio').val('0.00');
				}
			},
			error: () => {
				alert('생계비 기준 데이터를 불러오는 데 실패했습니다.');
				$('#standard_amount_container input, #iex_range_min, #iex_range_max').val(0);
				$('#iex_income_ratio').val('0.00');
			}
		});
	});
}

	formatNumber(number) {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

initializeLivingExpenseSection() {
	// 기본적으로 기준 범위 내 생계비 선택
	$('input[name="iex_expense_range"][value="Y"]').prop('checked', true);

	// 초기 로드 시 생계비 자동 설정
	const calculateInitialStandardExpense = () => {
		// DB에서 가족 수 가져오기
		this.getFamilyCountFromDB((familyCount) => {
			$('#iex_family_count').val(familyCount);
			
			const standardAmount = this.getStandardLivingExpense(familyCount);
			$('#iex_living_expense').val(this.formatMoney(standardAmount));
			
			// 기준 중위소득 60%의 값이 최대값, 그 2/3가 최소값
			$('#iex_range_min').val(this.formatMoney(Math.round(standardAmount * 2/3)));
			$('#iex_range_max').val(this.formatMoney(standardAmount));
			
			// 기준 범위 내 생계비일 경우 60% 설정
			$('#iex_income_ratio').val('60.00');
			
			this.calculateTotalExpense();
		});
	};

	// 페이지 로드 직후와 가족 구성원 정보 로드 후 호출
	const deferredInitialCalculation = () => {
		// 약간의 지연을 두어 DOM 렌더링 후 계산
		setTimeout(calculateInitialStandardExpense, 300);
	};

	// 초기 로드 시 호출
	deferredInitialCalculation();

	// 생계비 범위 변경 이벤트 핸들러 개선
	$('input[name="iex_expense_range"]').on('change', (e) => {
		const isStandard = e.target.value === 'Y';
		const livingExpenseInput = $('#iex_living_expense');
		const directInputCheckbox = $('#iex_direct_input');
		
		// 표준 범위일 경우 직접 입력 불가능 처리
		if (isStandard) {
			livingExpenseInput.prop('readonly', true);
			directInputCheckbox.prop('checked', false);
			
			// DB에서 가족 수 조회
			this.getFamilyCountFromDB((familyCount) => {
				$('#iex_family_count').val(familyCount);
				const standardAmount = this.getStandardLivingExpense(familyCount);
				livingExpenseInput.val(this.formatMoney(standardAmount));
				// 기준 범위 내인 경우 60% 설정
				$('#iex_income_ratio').val('60.00');
				this.calculateTotalExpense();
			});
		} else {
			// 기준 범위 초과 선택 시에도 직접 입력 체크박스 상태는 변경하지 않음
			// 다만 읽기 전용 상태는 직접 입력 체크박스 상태에 따름
			livingExpenseInput.prop('readonly', !directInputCheckbox.prop('checked'));
			
			// DB에서 가족 수 조회하여 중위소득 비율 계산
			this.getFamilyCountFromDB((familyCount) => {
				$('#iex_family_count').val(familyCount);
				const standardAmount = this.getStandardLivingExpense(familyCount);
				const midIncome = standardAmount * (100/60);
				
				// 현재 생계비 금액 계산
				const currentExpense = this.unformatMoney(livingExpenseInput.val());
				const percentageOfMidIncome = midIncome > 0 ? (currentExpense / midIncome * 100).toFixed(2) : 0;
				
				$('#iex_income_ratio').val(percentageOfMidIncome);
				this.calculateTotalExpense();
			});
		}
	});
	
	// 직접 입력 체크박스 이벤트 수정
	$('#iex_direct_input').on('change', (e) => {
		// 읽기 전용 속성만 변경하고, 라디오 버튼 상태는 변경하지 않음
		$('#iex_living_expense').prop('readonly', !e.target.checked);
		
		// 중위소득 비율 계산은 현재 선택된 라디오 버튼에 따라 결정
		const isExceedRange = $('input[name="iex_expense_range"]:checked').val() === 'N';
		if (isExceedRange) {
			this.getFamilyCountFromDB((familyCount) => {
				const standardAmount = this.getStandardLivingExpense(familyCount);
				const midIncome = standardAmount * (100/60);
				const currentExpense = this.unformatMoney($('#iex_living_expense').val());
				const percentageOfMidIncome = midIncome > 0 ? (currentExpense / midIncome * 100).toFixed(2) : 0;
				
				$('#iex_income_ratio').val(percentageOfMidIncome);
			});
		}
		
		// 총액 계산 업데이트
		this.calculateTotalExpense();
	});
	
	// 생계비 금액 변경 시 중위소득 비율 계산
	$('#iex_living_expense').on('input', (e) => {
		// 금액 포맷팅
		const val = e.target.value.replace(/[^\d]/g, '');
		e.target.value = this.formatMoney(val);
		
		// 중위소득 비율 계산 (기준 범위 초과 선택 시에만)
		const isExceedRange = $('input[name="iex_expense_range"]:checked').val() === 'N';
		if (isExceedRange) {
			this.getFamilyCountFromDB((familyCount) => {
				const standardAmount = this.getStandardLivingExpense(familyCount);
				const midIncome = standardAmount * (100/60);
				const currentExpense = this.unformatMoney(e.target.value);
				const percentageOfMidIncome = midIncome > 0 ? (currentExpense / midIncome * 100).toFixed(2) : 0;
				
				$('#iex_income_ratio').val(percentageOfMidIncome);
			});
		}
		
		this.calculateTotalExpense();
	});
	
	// 추가 생계비 변경 시 총액 계산
	$('#iex_additional_expense').on('input', () => {
		this.calculateTotalExpense();
	});
	
	this.calculateTotalExpense();
}

  initializePlan10Section() {
    this.loadPlan10Data(); // 데이터 로딩 함수 호출
    // 기존 변제계획안 10항 초기화 로직 (있다면 유지)
  }

  loadPlan10Data() {
    const caseNo = window.currentCaseNo;
    if (!caseNo) return;

    $.ajax({
      url: '/adm/api/application_recovery/income/plan10_api.php', // API 경로 확인 필요
      type: 'GET',
      data: { case_no: caseNo },
      dataType: 'json',
      success: (response) => {
        if (response.success && response.data) {
          $('#iex_plan10_title').val(response.data.title || '');
          $('#iex_plan10_content').val(response.data.content || '');
        } else {
        }
      },
      error: (xhr, status, error) => {
        console.error('변제계획안 10항 데이터 로드 중 오류:', error);
      }
    });
  }

  savePlan10Data() {
    console.log('savePlan10Data function called'); // 함수 호출 확인 로그
    if (this.isSaving) {
       console.log('Saving already in progress, returning.'); // 저장 중복 방지 확인 로그
       return;
    }
    this.isSaving = true;

    const caseNo = window.currentCaseNo;
    if (!caseNo) {
      alert('사건 번호가 필요합니다.');
      this.isSaving = false;
      return;
    }

    const title = $('#iex_plan10_title').val()?.trim() || '';
    const content = $('#iex_plan10_content').val()?.trim() || '';

    if (!title || !content) {
		alert('제목과 내용을 모두 입력해주세요.');
		his.isSaving = false;
		return;
    }

    const data = {
      case_no: caseNo,
      title: title,
      content: content
    };

    $.ajax({
      url: '/adm/api/application_recovery/income/plan10_api.php', // API 경로 확인 필요
      type: 'POST',
      data: data,
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          alert('변제계획안 10항 정보가 저장되었습니다.');
        } else {
          alert(response.message || '변제계획안 10항 정보 저장 실패');
        }
      },
      error: (xhr, status, error) => {
        console.error('변제계획안 10항 저장 중 오류:', status, error);
        // 서버 응답 내용 확인 (JSON 형태일 수 있음)
        let errorMessage = '변제계획안 10항 저장 중 오류가 발생했습니다.';
        if (xhr.responseText) {
          try {
            const response = JSON.parse(xhr.responseText);
            // PHP에서 반환하는 상세 오류 메시지 사용
            if (response && response.message) {
              errorMessage += `\n서버 메시지: ${response.message}`;
              if (response.error_message) { // PHP에서 추가한 상세 오류
                 console.error('서버 오류 상세:', response.error_message);
                 console.error('서버 오류 추적:', response.error_trace);
              }
            } else {
               console.error('서버 응답:', xhr.responseText); // JSON 파싱 실패 시 원본 응답 출력
            }
          } catch (e) {
            console.error('서버 응답 파싱 오류:', e);
            console.error('원본 서버 응답:', xhr.responseText);
          }
        }
        alert(errorMessage);
      },
      complete: () => {
        this.isSaving = false;
      }
    });
  }
  
  openLivingExpenseTable() {
		const caseNo = window.currentCaseNo;
		if (!caseNo) return;
		
		const popupWidth = 1200;
		const popupHeight = 800;
		const left = (window.screen.width - popupWidth) / 2;
		const top = (window.screen.height - popupHeight) / 2;
		
		window.open('../adm/api/application_recovery/income/living_expense_standard_table.php', 
			'년도별 기준중위소득 60% 기준', 
			`width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes`
		);
	}
}

// 페이지 로드 시 인스턴스 생성
$(document).ready(() => {
  window.incomeExpenditure = new ApplicationRecoveryIncomeExpenditure();
});