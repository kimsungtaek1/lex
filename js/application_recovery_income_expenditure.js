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
		this.initializePlan10Section();
		$('#iex_year').on('change', () => {
			const selectedYear = $('#iex_year').val();
			this.updateLivingExpenseStandards(selectedYear);
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
    $('#iex_expense_calc_btn').off('click').on('click', () => this.openAdditionalExpenseCalculator());
    $('#iex_trustee_fee_btn').off('click').on('click', () => this.openTrusteeFeeCalculator());
    $('#iex_other_fee_btn').off('click').on('click', () => this.openOtherFeeCalculator());

    // 변제계획 이벤트
    $('#iex-monthCountValue').off('input').on('input', () => this.calculateMonthlyPayment());
    $('#iex-calcPlanBtn').off('click').on('click', () => this.calculateRepaymentPlan());

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
      '#iex_other_fee'
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
      'width=1600,height=600,scrollbars=yes');
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
    const data = {
      case_no: window.currentCaseNo,
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
          alert('가족관계 정보가 저장되었습니다.');
          if (response.data?.member_no) {
            row.attr('data-member-no', response.data.member_no);
          }
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
      const familyCount = parseInt($('#iex_family_count').val()) || 1;
      const standardAmount = this.getStandardLivingExpense(familyCount);
      livingExpenseInput.val(this.formatMoney(standardAmount));
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

  openAdditionalExpenseCalculator() {
	const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    window.open('/adm/api/application_recovery/income/additional_expense_calculator.php?case_no=' + caseNo, '추가생계비계산기', 'width=1000,height=600,scrollbars=yes');
  }

  openTrusteeFeeCalculator() {
	const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    window.open('/adm/api/application_recovery/income/trustee_fee_calculator.php?case_no=' + caseNo,
      '외부회생위원보수계산기',
      'width=1000,height=1200,scrollbars=yes');
  }

  openOtherFeeCalculator() {
	const caseNo = window.currentCaseNo;
    if (!caseNo) return;
    window.open('/adm/api/application_recovery/income/other_fee_calculator.php?case_no=' + caseNo,
      '기타재단채권계산기',
      'width=1000,height=1200,scrollbars=yes');
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
		$.ajax({
			url: '/adm/api/application_recovery/income/living_expense_standard_api.php',
			type: 'GET',
			data: { action: 'get_years' },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.years) {
					const $yearSelect = $('#iex_year');
					$yearSelect.empty(); // 기존 옵션 제거

					// 연도 옵션 동적 생성
					response.years.forEach((year, index) => {
						const $option = $('<option>', {
							value: year,
							text: year + '년',
							selected: index === 0 // 첫 번째 연도를 기본 선택
						});
						$yearSelect.append($option);
					});

					// 첫 번째 연도로 생계비 기준 업데이트
					this.updateLivingExpenseStandards($yearSelect.val());
				} else {
					alert('연도 목록을 불러오는 데 실패했습니다.');
				}
			},
			error: () => {
				alert('연도 목록을 불러오는 중 오류가 발생했습니다.');
			}
		});
	}

	updateLivingExpenseStandards(year) {
		$.ajax({
			url: '/adm/api/application_recovery/income/living_expense_standard_api.php',
			type: 'GET',
			data: { year: year },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					$('#standard_amount_container input').each((index, element) => {
						const familyMembers = $(element).data('family-members');
						const amount = response.data[familyMembers] || 0;
						$(element).val(this.formatNumber(amount));
					});
				} else {
					alert('해당 연도의 생계비 기준 데이터를 찾을 수 없습니다.');
					$('#standard_amount_container input').val(0);
				}
			},
			error: () => {
				alert('생계비 기준 데이터를 불러오는 데 실패했습니다.');
				$('#standard_amount_container input').val(0);
			}
		});
	}

	formatNumber(number) {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

  initializeLivingExpenseSection() {
    $('input[name="iex_expense_range"]:checked').trigger('change');
    this.calculateTotalExpense();
  }

  initializePlan10Section() {
    // 변제계획안 10항 초기화 로직
  }
}

// 페이지 로드 시 인스턴스 생성
$(document).ready(() => {
  window.incomeExpenditure = new ApplicationRecoveryIncomeExpenditure();
});