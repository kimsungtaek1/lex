$(document).ready(function() {
	// 초기화
	initializeForm();
	
	// 이벤트 리스너 등록
	registerEventListeners();
	
	// 저장된 데이터 불러오기
	loadDebtData();
	
	// 보증인 채무 목록 불러오기
	loadGuarantorDebtList();
});

// 폼 초기화
function initializeForm() {
	// 숫자 입력 필드 포맷팅
	$('.number-input').each(function() {
		formatNumber($(this));
	});
	
	// 대위변제 선택 변경 시 헤더 텍스트 업데이트
	$('input[name="subrogation_type"]').change(function() {
		$('#subrogationDisplay').text($(this).val());
	});
	
	// 계산일자 변경 시 산정근거 자동 업데이트
	$('#calculation_date').change(function() {
		updateCalculations();
	});
}

// 이벤트 리스너 등록
function registerEventListeners() {
	// 숫자 입력 필드 이벤트
	$(document).on('input', '.number-input', function() {
		formatNumber($(this));
	});
	
	// 금융기관 검색 버튼
	$('.btn-financial-institution').on('click', function() {
		openFinancialInstitutionSearch();
	});
	
	// 저장 버튼
	$('#saveButton').on('click', saveForm);
	
	// 삭제 버튼
	$('#deleteButton').on('click', function() {
		if (confirm('정말 삭제하시겠습니까?')) {
			deleteForm();
		}
	});
	
	// 닫기 버튼
	$('#closeButton').on('click', function() {
		window.close();
	});
	
	// 테이블의 수정 버튼 클릭 이벤트
	$(document).on('click', '.edit-debt', function() {
		const debtNo = $(this).data('debt-no');
		loadDebtForEdit(debtNo);
	});
	
	// 테이블의 삭제 버튼 클릭 이벤트
	$(document).on('click', '.delete-debt', function() {
		const debtNo = $(this).data('debt-no');
		if (confirm('정말 삭제하시겠습니까?')) {
			deleteDebt(debtNo);
		}
	});
}

// 금융기관 검색 창 열기
function openFinancialInstitutionSearch() {
	const width = 1200;
	const height = 750;
	const left = (screen.width - width) / 2;
	const top = (screen.height - height) / 2;

	const searchWindow = window.open(
		'../application_recovery/search_financial_institution.php',
		'SearchFinancialInstitution',
		`width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
	);

	if (searchWindow === null) {
		alert('팝업이 차단되어 있습니다. 팝업 차단을 해제해주세요.');
		return;
	}

	// 금융기관 선택 이벤트 리스너
	window.addEventListener('message', function(event) {
		if (event.data.type === 'financialInstitutionSelected') {
			fillFinancialInstitution(event.data.institution);
			searchWindow.close();
		}
	});
}

// 금융기관 정보 채우기
function fillFinancialInstitution(institution) {
	$('#financial_institution').val(institution.name);
	$('#address').val(institution.address);
	$('#phone').val(formatPhoneNumber(institution.phone));
	$('#fax').val(institution.fax);
}

// 전화번호 포맷팅
function formatPhoneNumber(value) {
	if (!value) return '';
	
	value = value.replace(/[^\d]/g, '');
	
	if (value.length <= 3) {
		return value;
	} else if (value.length <= 7) {
		return value.slice(0, 3) + '-' + value.slice(3);
	} else {
		return value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
	}
}

// 저장된 채무 데이터 불러오기
function loadDebtData() {
	// 사건 및 채권자 번호가 없으면 데이터를 조회할 수 없음
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}
	
	const debtNo = $('#debtNo').val();
	if (!debtNo) return; // 신규 입력 모드
	
	$.ajax({
		url: '../../api/application_recovery/get_guaranteed_debts.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			debt_no: debtNo
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					// 데이터로 폼 채우기
					fillFormData(data.data[0]);
				} else {
					clearForm(); // 데이터가 없으면 폼 초기화
				}
			} catch (e) {
				console.error('데이터 파싱 오류:', e);
			}
		},
		error: function(xhr) {
			console.error('서버 오류:', xhr.responseText);
		}
	});
}

// 특정 보증인 채무 데이터 불러오기 (수정용)
function loadDebtForEdit(debtNo) {
	$.ajax({
		url: '../../api/application_recovery/get_guaranteed_debts.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			debt_no: debtNo
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					// 데이터로 폼 채우기
					fillFormData(data.data[0]);
					// debt_no 필드에 값 설정
					$('#debtNo').val(data.data[0].debt_no);
				}
			} catch (e) {
				console.error('데이터 파싱 오류:', e);
			}
		},
		error: function(xhr) {
			console.error('서버 오류:', xhr.responseText);
		}
	});
}

// 보증인 채무 목록 불러오기
function loadGuarantorDebtList() {
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}
	
	$.ajax({
		url: '../../api/application_recovery/get_guaranteed_debts.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success) {
					renderGuarantorTable(data.data);
				}
			} catch (e) {
				console.error('데이터 파싱 오류:', e);
			}
		},
		error: function(xhr) {
			console.error('서버 오류:', xhr.responseText);
		}
	});
}

// 보증인 채무 테이블 렌더링
function renderGuarantorTable(debts) {
	const tbody = $('#guarantorTableBody');
	tbody.empty();
	
	if (!debts || debts.length === 0) {
		tbody.append('<div class="no-data">보증인이 있는 채무 정보가 존재하지 않습니다. (회생신청자가 주채무자인 경우임)</div>');
		return;
	}
	
	debts.forEach(function(debt, index) {
		const row = `
			<tr>
				<td>${index + 1}</td>
				<td>${debt.subrogation_type || '미발생'}</td>
				<td>${debt.financial_institution || ''}</td>
				<td>${debt.address || ''}</td>
				<td class="edit-buttons">
					<button type="button" class="edit-debt" data-debt-no="${debt.debt_no}">수정</button>
					<button type="button" class="delete-debt" data-debt-no="${debt.debt_no}">삭제</button>
				</td>
			</tr>
		`;
		tbody.append(row);
	});
}

// 폼 초기화
function clearForm() {
	$('#entity_type').val('법인');
	$('#financial_institution').val('');
	$('#address').val('');
	$('#phone').val('');
	$('#fax').val('');
	$('#claim_reason').val('');
	$('#principal').val('');
	$('#principal_calculation').val('');
	$('#interest').val('');
	$('#interest_calculation').val('');
	$('#calculation_date').val('');
	$('#claim_content').val('보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자');
	$('#subrogation_none').prop('checked', true);
	$('#force_payment_plan').prop('checked', false);
	$('input[name="future_right_type"]').prop('checked', false);
	$('#guarantor_name').val('');
	$('#guarantor_address').val('');
	$('#guarantee_amount').val('');
	$('#guarantee_date').val('');
	$('#subrogationDisplay').text('미발생');
}

// 폼 데이터 채우기
function fillFormData(data) {
	$('#entity_type').val(data.entity_type || '법인');
	$('#financial_institution').val(data.financial_institution || '');
	$('#address').val(data.address || '');
	$('#phone').val(formatPhoneNumber(data.phone || ''));
	$('#fax').val(data.fax || '');
	$('#claim_reason').val(data.claim_reason || '');
	$('#principal').val(formatNumberValue(data.principal));
	$('#principal_calculation').val(data.principal_calculation || '');
	$('#interest').val(formatNumberValue(data.interest));
	$('#interest_calculation').val(data.interest_calculation || '');
	$('#calculation_date').val(data.calculation_date ? data.calculation_date.split(' ')[0] : '');
	$('#claim_content').val(data.claim_content || '보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자');
	
	// 대위변제 선택
	$(`input[name="subrogation_type"][value="${data.subrogation_type || '미발생'}"]`).prop('checked', true);
	$('#subrogationDisplay').text(data.subrogation_type || '미발생');
	
	// 강제 기재 여부
	$('#force_payment_plan').prop('checked', data.force_payment_plan == 1);
	
	// 장래구상권 선택
	if (data.future_right_type) {
		$(`input[name="future_right_type"][value="${data.future_right_type}"]`).prop('checked', true);
	} else {
		$('input[name="future_right_type"]').prop('checked', false);
	}
	
	$('#guarantor_name').val(data.guarantor_name || '');
	$('#guarantor_address').val(data.guarantor_address || '');
	$('#guarantee_amount').val(formatNumberValue(data.guarantee_amount));
	$('#guarantee_date').val(data.guarantee_date ? data.guarantee_date.split(' ')[0] : '');
}

// 숫자 포맷팅
function formatNumber(input) {
	if (!input || !input.val) return;
	
	let value = input.val();
	if (!value) return;
	
	// 숫자와 소수점, 마이너스 기호만 남기고 모두 제거
	value = value.replace(/[^\d.-]/g, '');
	
	if (value) {
		try {
			// 숫자로 변환 후 천단위 콤마 추가
			value = Number(value).toLocaleString('ko-KR');
			input.val(value);
		} catch (e) {
			console.error('숫자 변환 중 오류:', e);
			input.val(''); // 오류 발생시 입력값 초기화
		}
	}
}

// 숫자 값 포맷팅
function formatNumberValue(value) {
	if (value === null || value === undefined || value === '') return '';
	return Number(value).toLocaleString('ko-KR');
}

// 계산일자 변경 시 산정근거 자동 업데이트
function updateCalculations() {
	const date = $('#calculation_date').val();
	if (!date) return;
	
	const formattedDate = formatDate(new Date(date));
	$('#principal_calculation').val(`부채증명서 참조(산정기준일: ${formattedDate})`);
	$('#interest_calculation').val(`부채증명서 참조(산정기준일: ${formattedDate})`);
}

// 날짜 포맷팅
function formatDate(date) {
	return date.getFullYear() + '.' + 
		   String(date.getMonth() + 1).padStart(2, '0') + '.' + 
		   String(date.getDate()).padStart(2, '0');
}

// 폼 저장
function saveForm() {
	const getNumber = function(selector) {
		return parseFloat($(selector).val().replace(/,/g, '')) || 0;
	};
	
	// 데이터 수집
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		subrogation_type: $('input[name="subrogation_type"]:checked').val() || '미발생',
		force_payment_plan: $('#force_payment_plan').is(':checked') ? 1 : 0,
		entity_type: $('#entity_type').val(),
		financial_institution: $('#financial_institution').val(),
		address: $('#address').val(),
		phone: $('#phone').val().replace(/-/g, ''),
		fax: $('#fax').val(),
		claim_reason: $('#claim_reason').val(),
		principal: getNumber('#principal'),
		principal_calculation: $('#principal_calculation').val(),
		interest: getNumber('#interest'),
		interest_calculation: $('#interest_calculation').val(),
		calculation_date: $('#calculation_date').val(),
		claim_content: $('#claim_content').val(),
		future_right_type: $('input[name="future_right_type"]:checked').val() || null,
		guarantor_name: $('#guarantor_name').val(),
		guarantor_address: $('#guarantor_address').val(),
		guarantee_amount: getNumber('#guarantee_amount'),
		guarantee_date: $('#guarantee_date').val()
	};
	
	// 필수 필드 체크
	if (!formData.guarantor_name) {
		alert('보증인명은 필수 입력 항목입니다.');
		$('#guarantor_name').focus();
		return;
	}
	
	// debt_no가 있으면 추가
	const debtNo = $('#debtNo').val();
	if (debtNo) {
		formData.debt_no = debtNo;
	}
	
	$.ajax({
		url: '../../api/application_recovery/save_guaranteed_debt.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'guaranteedDebtSaved', 
						creditorCount: current_creditor_count,
						hasData: true
					}, '*');
					
					// debt_no 업데이트
					if (result.debt_no) {
						$('#debtNo').val(result.debt_no);
					}
					
					// 테이블 새로고침
					loadGuarantorDebtList();
				} else {
					console.error('저장 실패 응답:', result);
					alert('저장 중 오류가 발생했습니다.');
					if (result.message) {
						console.error('에러 메시지:', result.message);
					}
				}
			} catch (e) {
				console.error('저장 오류:', e);
			}
		},
		error: function(xhr) {
			console.error('서버 오류:', xhr.responseText);
		}
	});
}

// 보증인 채무 삭제
function deleteDebt(debtNo) {
	$.ajax({
		url: '../../api/application_recovery/delete_guaranteed_debt.php',
		method: 'POST',
		data: { debt_no: debtNo },
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '삭제되었습니다.');
					
					// 테이블에서 해당 행 삭제
					loadGuarantorDebtList();
					
					// 현재 편집 중인 항목이 삭제된 경우 폼 초기화
					if ($('#debtNo').val() == debtNo) {
						clearForm();
						$('#debtNo').val('');
					}
					
					// 남은 항목이 없을 경우 부모 창에 알림
					$.ajax({
						url: '../../api/application_recovery/get_guaranteed_debt_count.php',
						method: 'GET',
						data: {
							case_no: currentCaseNo,
							creditor_count: current_creditor_count
						},
						success: function(countResponse) {
							if (countResponse.success && countResponse.count == 0) {
								// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
								window.opener.postMessage({
									type: 'guaranteedDebtDeleted',
									creditorCount: current_creditor_count,
									hasData: false
								}, '*');
							}
						}
					});
				} else {
					alert(result.message || '삭제 중 오류가 발생했습니다.');
				}
			} catch (e) {
				console.error('삭제 오류:', e);
			}
		},
		error: function(xhr) {
			console.error('서버 오류:', xhr.responseText);
		}
	});
}

// 폼 삭제
function deleteForm() {
	const debtNo = $('#debtNo').val();
	if (!debtNo) {
		alert('삭제할 항목이 없습니다.');
		return;
	}
	
	deleteDebt(debtNo);
}