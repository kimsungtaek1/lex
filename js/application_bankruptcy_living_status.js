// 생활상황 관리 클래스
class LivingStatusManager {
	constructor() {
		this.familyCounter = 0;
		this.initialize();
	}

	initialize() {
		this.bindEvents();
		this.loadData();
		
		// 가족 구성원 컨테이너가 비어있으면 빈 블록 추가
		if ($('#family_members_container').children().length === 0) {
			this.addFamilyMember();
		}
	}

	bindEvents() {
		// 가족 구성원 추가 버튼 이벤트
		$('#add_family_member').on('click', () => this.addFamilyMember());

		// 저장 버튼 이벤트
		$('#save_living_status_basic').on('click', () => this.saveBasicInfo());
		$('#save_living_status_income').on('click', () => this.saveIncomeInfo());
		$('#save_living_status_additional').on('click', () => this.saveAdditionalInfo());
		$('#save_living_status_tax').on('click', () => this.saveTaxInfo());

		// 이벤트 위임: 가족 구성원 저장 및 삭제
		$(document).on('click', '.family_save_btn', (e) => {
			const block = $(e.target).closest('.family-member-block');
			this.saveFamilyMember(block);
		});

		$(document).on('click', '.family_delete_btn', (e) => {
			const block = $(e.target).closest('.family-member-block');
			this.deleteFamilyMember(block);
		});

		// 금액 입력 필드 이벤트
		$(document).on('input', 'input[data-type="money"]', (e) => {
			const val = e.target.value.replace(/[^\d]/g, "");
			e.target.value = this.formatMoney(val);
		});

		// 체크박스 이벤트 (같은 그룹에서 하나만 선택 가능)
		$('input[name="job_type"]').on('change', function() {
			$('input[name="job_type"]').not(this).prop('checked', false);
		});

		// 세금 미납 체크박스 이벤트
		$('input[name$="_tax_status"]').on('change', function() {
			const name = $(this).attr('name');
			$(`input[name="${name}"]`).not(this).prop('checked', false);
		});
	}

	loadData() {
		if (!window.currentCaseNo) return;

		// 기본 정보 로드
		this.loadBasicInfo();
		
		// 수입 정보 로드
		this.loadIncomeInfo();
		
		// 가족 구성원 정보 로드
		this.loadFamilyMembers();
		
		// 추가 정보 로드
		this.loadAdditionalInfo();
		
		// 세금 정보 로드
		this.loadTaxInfo();
	}

	// 기본 정보 로드
	loadBasicInfo() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/get_basic_info.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					const data = response.data;
					
					// 체크박스 설정
					if (data.job_type) {
						$(`input[name="job_type"][value="${data.job_type}"]`).prop('checked', true);
					}
					
					// 텍스트 필드 설정
					$('#job_industry').val(data.job_industry || '');
					$('#company_name').val(data.company_name || '');
					$('#employment_period').val(data.employment_period || '');
				}
			},
			error: (xhr, status, error) => {
				console.error('기본 정보 로드 실패:', error);
			}
		});
	}

	// 수입 정보 로드
	loadIncomeInfo() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/get_income_info.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					const data = response.data;
					
					$('#self_income').val(this.formatMoney(data.self_income || 0));
					$('#monthly_salary').val(this.formatMoney(data.monthly_salary || 0));
					$('#pension').val(this.formatMoney(data.pension || 0));
					$('#living_support').val(this.formatMoney(data.living_support || 0));
					$('#other_income').val(this.formatMoney(data.other_income || 0));
				}
			},
			error: (xhr, status, error) => {
				console.error('수입 정보 로드 실패:', error);
			}
		});
	}

	// 가족 구성원 정보 로드
	loadFamilyMembers() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/get_family_members.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				// 컨테이너 초기화
				$('#family_members_container').empty();
				
				if (response.success) {
					// 데이터가 있으면 표시
					if (response.data && response.data.length > 0) {
						response.data.forEach(member => {
							this.addFamilyMember(member);
						});
					} else {
						// 데이터가 없으면 빈 블록 추가
						this.addFamilyMember();
					}
				} else {
					// 오류 발생 시에도 빈 블록 추가
					this.addFamilyMember();
				}
			},
			error: (xhr, status, error) => {
				console.error('가족 구성원 정보 로드 실패:', error);
				// 오류 시에도 빈 블록 추가
				$('#family_members_container').empty();
				this.addFamilyMember();
			}
		});
	}

	// 추가 정보 로드
	loadAdditionalInfo() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/get_additional_info.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					const data = response.data;
					
					$('#basic_facts').val(data.basic_facts || '');
					
					// 가족관계사항 라디오 버튼 설정
					if (data.family_status) {
						$(`input[name="family_status"][value="${data.family_status}"]`).prop('checked', true);
					}
					
					$('#monthly_rent').val(this.formatMoney(data.monthly_rent || 0));
					$('#rent_deposit').val(this.formatMoney(data.rent_deposit || 0));
					$('#applicant_relation').val(data.applicant_relation || '');
				}
			},
			error: (xhr, status, error) => {
				console.error('추가 정보 로드 실패:', error);
			}
		});
	}

	// 세금 정보 로드
	loadTaxInfo() {
		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/get_tax_info.php',
			type: 'GET',
			data: { case_no: window.currentCaseNo },
			dataType: 'json',
			success: (response) => {
				if (response.success && response.data) {
					const data = response.data;
					
					// 각 세금 상태 설정
					const taxTypes = ['income_tax', 'residence_tax', 'property_tax', 'alimony', 'pension_tax', 'car_tax', 'other_tax'];
					
					taxTypes.forEach(type => {
						if (data[`${type}_status`]) {
							$(`input[name="${type}_status"][value="${data[`${type}_status`]}"]`).prop('checked', true);
						}
						
						if (data[`${type}_amount`]) {
							$(`#${type}_amount`).val(this.formatMoney(data[`${type}_amount`]));
						}
					});
				}
			},
			error: (xhr, status, error) => {
				console.error('세금 정보 로드 실패:', error);
			}
		});
	}

	// 기본 정보 저장
	saveBasicInfo() {
		const data = {
			case_no: window.currentCaseNo,
			job_type: $('input[name="job_type"]:checked').val() || '',
			job_industry: $('#job_industry').val(),
			company_name: $('#company_name').val(),
			employment_period: $('#employment_period').val()
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/save_basic_info.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('기본 정보가 저장되었습니다.');
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

	// 수입 정보 저장
	saveIncomeInfo() {
		const data = {
			case_no: window.currentCaseNo,
			self_income: this.unformatMoney($('#self_income').val()),
			monthly_salary: this.unformatMoney($('#monthly_salary').val()),
			pension: this.unformatMoney($('#pension').val()),
			living_support: this.unformatMoney($('#living_support').val()),
			other_income: this.unformatMoney($('#other_income').val())
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/save_income_info.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('수입 정보가 저장되었습니다.');
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

	// 추가 정보 저장
	saveAdditionalInfo() {
		const selectedStatus = $('input[name="family_status"]:checked').val() || '';

		const data = {
			case_no: window.currentCaseNo,
			basic_facts: $('#basic_facts').val(),
			family_status: selectedStatus,
			monthly_rent: this.unformatMoney($('#monthly_rent').val()),
			rent_deposit: this.unformatMoney($('#rent_deposit').val()),
			applicant_relation: $('#applicant_relation').val()
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/save_additional_info.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('추가 정보가 저장되었습니다.');
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

	// 세금 정보 저장
	saveTaxInfo() {
		const data = {
			case_no: window.currentCaseNo
		};

		// 각 세금 유형별 데이터 수집
		const taxTypes = ['income_tax', 'residence_tax', 'property_tax', 'alimony', 'pension_tax', 'car_tax', 'other_tax'];
		
		taxTypes.forEach(type => {
			data[`${type}_status`] = $(`input[name="${type}_status"]:checked`).val() || '';
			data[`${type}_amount`] = this.unformatMoney($(`#${type}_amount`).val());
		});

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/save_tax_info.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('세금 정보가 저장되었습니다.');
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

	// 가족 구성원 추가
	addFamilyMember(data = {}) {
		this.familyCounter++;
		const id = data.member_id || this.familyCounter;
		
		// 템플릿에서 HTML 생성
		let html = $('#family_member_template').html()
			.replace(/{id}/g, id);
		
		// 컨테이너에 추가
		$('#family_members_container').append(html);
		
		// 데이터가 있으면 채우기
		if (data.member_id) {
			const block = $(`#family_member_${id}`);
			block.find('.family_name').val(data.name || '');
			block.find('.family_relation').val(data.relation || '');
			block.find('.family_age').val(data.age || '');
			block.find('.family_job').val(data.job || '');
			block.find('.family_income').val(this.formatMoney(data.income || 0));
		}
	}

	// 가족 구성원 저장
	saveFamilyMember(block) {
		const memberId = block.find('.family_member_id').val();
		
		const data = {
			case_no: window.currentCaseNo,
			member_id: memberId,
			name: block.find('.family_name').val(),
			relation: block.find('.family_relation').val(),
			age: block.find('.family_age').val(),
			job: block.find('.family_job').val(),
			income: this.unformatMoney(block.find('.family_income').val())
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/save_family_member.php',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('가족 구성원 정보가 저장되었습니다.');
					if (response.data.member_id) {
						block.find('.family_member_id').val(response.data.member_id);
					}
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

	// 가족 구성원 삭제
	deleteFamilyMember(block) {
		const memberId = block.find('.family_member_id').val();
		
		// 저장되지 않은 블록인 경우 바로 삭제
		if (!memberId) {
			block.remove();
			return;
		}
		
		if (!confirm('이 가족 구성원을 삭제하시겠습니까?')) {
			return;
		}

		$.ajax({
			url: '/adm/api/application_bankruptcy/living_status/delete_family_member.php',
			type: 'POST',
			data: {
				case_no: window.currentCaseNo,
				member_id: memberId
			},
			dataType: 'json',
			success: (response) => {
				if (response.success) {
					alert('가족 구성원이 삭제되었습니다.');
					block.remove();
					
					// 가족 구성원이 없으면 빈 블록 추가
					if ($('#family_members_container').children().length === 0) {
						this.addFamilyMember();
					}
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

// 생활상황 탭이 활성화될 때 초기화
$(document).ready(function() {
	window.livingStatusManager = new LivingStatusManager();
});