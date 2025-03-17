// 수입지출 관리 클래스
class IncomeExpenditureManager {
	constructor() {
		this.dependentCounter = 0;
		this.initialize();
	}

	initialize() {
		// 현재 사건 번호 확인 및 설정
		if (!window.currentCaseNo && window.currentCaseNo !== 0) {
			if (typeof currentCaseNo !== 'undefined') {
				window.currentCaseNo = currentCaseNo;
			}
		}

		// 생계비 데이터를 저장할 객체 생성
		if (!$('#living_expense_values').length) {
			$('body').append('<div id="living_expense_values" style="display:none;"></div>');
		}

		this.bindEvents();
		this.loadYearOptions();
		
		if (window.currentCaseNo) {
			this.loadIncomeData();
		}
		
		// 부양가족 컨테이너가 비어있으면 빈 블록 추가
		if ($('#dependents_container').children().length === 0) {
			this.addDependent();
		}
		
		// 초기 합계 계산
		this.calculateTotals();
	}

	bindEvents() {
		// 부양가족 추가 버튼 이벤트 (이벤트 위임 사용)
		$(document).on('click', '#add_dependent', () => this.addDependent());

		// 저장 버튼 이벤트
		$('#save_income_expense').on('click', () => this.saveIncomeExpense());
		$('#save_disposable_income').on('click', () => this.saveDisposableIncome());

		// 이벤트 위임: 부양가족 삭제
		$(document).on('click', '.dependent_delete_btn', (e) => {
			const block = $(e.target).closest('.dependent-row');
			this.deleteDependent(block);
		});

		// 금액 입력 필드 이벤트
		$(document).on('input', 'input[data-type="money"]', (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
			this.calculateTotals();
		});
		
		// 수입/지출 필드 변경 시 합계 계산
		$('input[id^="income_"], input[id^="expense_"]').on('input', () => {
			this.calculateTotals();
		});
		
		// 가구 인원수 변경 이벤트
		$('input[name="household_size"]').on('change', () => {
			this.calculateDisposableIncome();
		});
		
		// 채무자 월 평균소득 변경 이벤트
		$('#debtor_monthly_income').on('input', () => {
			this.calculateDisposableIncome();
		});
	}

	// 수입/지출 합계 계산
	calculateTotals() {
		// 수입 합계 계산
		let incomeTotal = 0;
		$('input[id^="income_"]:not(#income_total)').each(function() {
			incomeTotal += parseInt($(this).val().replace(/,/g, '') || 0);
		});
		$('#income_total').val(this.formatMoney(incomeTotal));
		
		// 지출 합계 계산
		let expenseTotal = 0;
		$('input[id^="expense_"]:not(#expense_total)').each(function() {
			expenseTotal += parseInt($(this).val().replace(/,/g, '') || 0);
		});
		$('#expense_total').val(this.formatMoney(expenseTotal));
		
		// 가용소득 계산도 함께 실행
		this.calculateDisposableIncome();
	}
	
	// 가용소득 계산
	calculateDisposableIncome() {
		const monthlyIncome = this.unformatMoney($('#debtor_monthly_income').val());
		const householdSize = $('input[name="household_size"]:checked').val() || '1';
		const householdExpense = $('#living_expense_values').data('expense' + householdSize) || 0;
		
		const disposableIncome = Math.max(0, monthlyIncome - householdExpense);
		$('#disposable_income').val(this.formatMoney(disposableIncome));
	}

	// 데이터 로드
	loadIncomeData() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/income/income_expenditure_api.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					if (response.data) {
						this.populateIncomeData(response.data);
						this.populateDependents(response.data.dependents || []);
					}
				} else {
					console.error('데이터 로드 실패:', response.message);
				}
			},
			error: (xhr, status, error) => {
				console.error('Ajax 에러:', error);
			}
		});
	}

	populateIncomeData(data) {
		// 가계수지표 데이터 설정
		$('#statement_month').val(data.statement_month || '');
		
		// 수입 데이터 설정
		$('#income_salary_applicant').val(this.formatMoney(data.income_salary_applicant || 0));
		$('#income_salary_spouse').val(this.formatMoney(data.income_salary_spouse || 0));
		$('#income_salary_others').val(this.formatMoney(data.income_salary_others || 0));
		$('#income_pension_applicant').val(this.formatMoney(data.income_pension_applicant || 0));
		$('#income_pension_spouse').val(this.formatMoney(data.income_pension_spouse || 0));
		$('#income_pension_others').val(this.formatMoney(data.income_pension_others || 0));
		$('#income_support').val(this.formatMoney(data.income_support || 0));
		$('#income_others').val(this.formatMoney(data.income_others || 0));
		
		// 지출 데이터 설정
		$('#expense_housing').val(this.formatMoney(data.expense_housing || 0));
		$('#expense_food').val(this.formatMoney(data.expense_food || 0));
		$('#expense_education').val(this.formatMoney(data.expense_education || 0));
		$('#expense_utilities').val(this.formatMoney(data.expense_utilities || 0));
		$('#expense_transportation').val(this.formatMoney(data.expense_transportation || 0));
		$('#expense_communication').val(this.formatMoney(data.expense_communication || 0));
		$('#expense_medical').val(this.formatMoney(data.expense_medical || 0));
		$('#expense_insurance').val(this.formatMoney(data.expense_insurance || 0));
		$('#expense_others').val(this.formatMoney(data.expense_others || 0));
		
		// 가용소득 관련 데이터 설정
		$('#debtor_monthly_income').val(this.formatMoney(data.debtor_monthly_income || 0));
		
		// 가구 규모 선택
		const householdSize = data.household_size || '1';
		$(`#household_size_${householdSize}`).prop('checked', true);
		
		// 합계 계산 및 가용소득 계산
		this.calculateTotals();
	}

	populateDependents(dependentsData) {
		// 컨테이너 초기화
		$('#dependents_container').empty();
		
		if (dependentsData && dependentsData.length > 0) {
			dependentsData.forEach(dependent => {
				this.addDependent(dependent);
			});
		} else {
			// 데이터가 없으면 빈 블록 추가
			this.addDependent();
		}
	}

	// 부양가족 추가
	addDependent(data = {}) {
		this.dependentCounter++;
		const id = data.dependent_id || this.dependentCounter;
		
		// 템플릿에서 HTML 생성
		let html = $('#dependent_template').html()
			.replace(/{id}/g, id);
		
		// 컨테이너에 추가
		$('#dependents_container').append(
			`<div class="dependent-row">${html}</div>`
		);
		
		// 데이터가 있으면 채우기
		if (data.dependent_id) {
			const row = $('#dependents_container .dependent-row:last-child');
			row.find('.dependent_id').val(data.dependent_id);
			row.find('.dependent_name').val(data.name || '');
			row.find('.dependent_age').val(data.age || '');
			row.find('.dependent_relation').val(data.relation || '');
		}
		
		// 이미 입력된 부양가족은 추가 버튼 숨기고 마지막 부양가족만 추가 버튼 표시
		$('#dependents_container .dependent-row:not(:last-child) #add_dependent').hide();
	}

	deleteDependent(block) {
		const dependentId = block.find('.dependent_id').val();
		
		// 저장되지 않은 블록인 경우 바로 삭제
		if (!dependentId || dependentId === this.dependentCounter.toString()) {
			block.remove();
			
			// 부양가족이 없으면 빈 블록 추가
			if ($('#dependents_container').children().length === 0) {
				this.addDependent();
			}
			
			return;
		}
		
		if (!confirm('이 부양가족을 삭제하시겠습니까?')) {
			return;
		}

		$.ajax({
			url: '/adm/api/application_bankruptcy/income/income_expenditure_api.php',
			type: 'POST',
			data: {
				case_no: window.currentCaseNo,
				dependent_id: dependentId,
				action: 'delete_dependent'
			},
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('부양가족이 삭제되었습니다.');
					block.remove();
					
					// 부양가족이 없으면 빈 블록 추가
					if ($('#dependents_container').children().length === 0) {
						this.addDependent();
					}
					
					// 마지막 부양가족의 추가 버튼 표시
					$('#dependents_container .dependent-row:last-child #add_dependent').show();
					
					// 가구 크기를 다시 계산하고 생계비 업데이트
					this.updateHouseholdSize();
				} else {
					alert(response.message || '삭제 중 오류가 발생했습니다.');
				}
			},
			error: (xhr, status, error) => {
				console.error('삭제 실패:', error);
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 가구 크기 업데이트
	updateHouseholdSize() {
		// 부양가족 수에 따라 가구 인원수 업데이트
		const dependentCount = $('#dependents_container').children().length;
		const householdSize = Math.min(6, dependentCount + 1); // 본인 + 부양가족, 최대 6인
		
		$(`#household_size_${householdSize}`).prop('checked', true);
		this.calculateDisposableIncome();
	}

	// 수입지출 정보 저장
	saveIncomeExpense() {
		const data = {
			case_no: window.currentCaseNo,
			statement_month: $('#statement_month').val(),
			income_salary_applicant: this.unformatMoney($('#income_salary_applicant').val()),
			income_salary_spouse: this.unformatMoney($('#income_salary_spouse').val()),
			income_salary_others: this.unformatMoney($('#income_salary_others').val()),
			income_pension_applicant: this.unformatMoney($('#income_pension_applicant').val()),
			income_pension_spouse: this.unformatMoney($('#income_pension_spouse').val()),
			income_pension_others: this.unformatMoney($('#income_pension_others').val()),
			income_support: this.unformatMoney($('#income_support').val()),
			income_others: this.unformatMoney($('#income_others').val()),
			income_total: this.unformatMoney($('#income_total').val()),
			expense_housing: this.unformatMoney($('#expense_housing').val()),
			expense_food: this.unformatMoney($('#expense_food').val()),
			expense_education: this.unformatMoney($('#expense_education').val()),
			expense_utilities: this.unformatMoney($('#expense_utilities').val()),
			expense_transportation: this.unformatMoney($('#expense_transportation').val()),
			expense_communication: this.unformatMoney($('#expense_communication').val()),
			expense_medical: this.unformatMoney($('#expense_medical').val()),
			expense_insurance: this.unformatMoney($('#expense_insurance').val()),
			expense_others: this.unformatMoney($('#expense_others').val()),
			expense_total: this.unformatMoney($('#expense_total').val())
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/income/income_expenditure_api.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('수입지출 정보가 저장되었습니다.');
				} else {
					alert(response.message || '저장 중 오류가 발생했습니다.');
				}
			},
			error: (xhr, status, error) => {
				console.error('저장 실패:', error);
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}

	loadYearOptions() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/income/living_expense_standard_api.php',
			type: 'GET',
			data: { action: 'get_years' },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.years && response.years.length > 0) {
					const $yearSelect = $('#standard_year');
					$yearSelect.empty(); // 기존 옵션 제거

					// 연도 옵션 동적 생성
					response.years.forEach((year, index) => {
						const $option = $('<option>', {
							value: year,
							text: year + '년',
							// 첫 번째 요소(가장 최근 연도)를 기본 선택
							selected: index === 0
						});
						$yearSelect.append($option);
					});

					// 선택된 연도의 생계비 기준 업데이트
					const selectedYear = $yearSelect.val();
					if (selectedYear) {
						this.updateLivingExpenseStandards(selectedYear);
					}
				}
			},
			error: () => {
				console.error('연도 목록을 불러오는 중 오류가 발생했습니다.');
			}
		});
	}

	updateLivingExpenseStandards(year) {
		if (!year) return;
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/income/living_expense_standard_api.php',
			type: 'GET',
			data: { year: year },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					// 각 가구별 생계비 업데이트
					for (let i = 1; i <= 6; i++) {
						const expense = response.data[i.toString()] || 0;
						$('#household_expense' + i).text(this.formatMoney(expense) + '원');
						$('#living_expense_values').data('expense' + i, expense);
					}
					
					// 가용소득 계산
					this.calculateDisposableIncome();
				}
			},
			error: () => {
				console.error('생계비 기준을 불러오는 중 오류가 발생했습니다.');
			}
		});
	}

	// 가용소득 정보 저장
	saveDisposableIncome() {
		// 부양가족 정보 수집
		const dependents = [];
		$('#dependents_container .dependent-row').each(function() {
			const id = $(this).find('.dependent_id').val();
			const name = $(this).find('.dependent_name').val();
			const age = $(this).find('.dependent_age').val();
			const relation = $(this).find('.dependent_relation').val();
			
			if (name || age || relation) {
				dependents.push({
					dependent_id: id,
					name: name,
					age: age,
					relation: relation
				});
			}
		});
		
		const data = {
			case_no: window.currentCaseNo,
			debtor_monthly_income: this.unformatMoney($('#debtor_monthly_income').val()),
			household_size: $('input[name="household_size"]:checked').val() || '1',
			disposable_income: this.unformatMoney($('#disposable_income').val()),
			dependents: JSON.stringify(dependents)
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/income/income_expenditure_api.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('가용소득 정보가 저장되었습니다.');
				} else {
					alert(response.message || '저장 중 오류가 발생했습니다.');
				}
			},
			error: (xhr, status, error) => {
				console.error('저장 실패:', error);
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
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
}

// 수입지출목록 탭이 활성화될 때 초기화
$(document).ready(function() {
	if (typeof currentCaseNo !== 'undefined' && currentCaseNo !== null) {
		window.currentCaseNo = currentCaseNo;
		console.log('수입지출목록 매니저 초기화 시 currentCaseNo 설정:', window.currentCaseNo);
	}
	window.incomeExpenditureManager = new IncomeExpenditureManager();
});