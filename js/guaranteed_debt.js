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
	
	// 대위변제 선택 변경 시 헤더 텍스트와 채권원인 텍스트 업데이트
	$('input[name="subrogation_type"]').off('change').on('change', function() {
		updateSubrogationDisplay();
		toggleSubrogationFields();
	});
	
	// 계산일자 변경 시 산정근거 자동 업데이트
	$('#calculation_date').change(function() {
		updateCalculations();
	});
	
	// 페이지 로드 시 대위변제 텍스트 초기화
	updateSubrogationDisplay();
	toggleSubrogationFields();
}

// 대위변제 표시 및 채권원인 텍스트 업데이트
function updateSubrogationDisplay() {
	const subrogationType = $('input[name="subrogation_type"]:checked').val();
	$('#subrogationDisplay').text(subrogationType);
	
	// 채권원인 텍스트 업데이트
	let claimReasonText = `채무자의 ${current_creditor_count}번 채무를 연대보증`;
	
	// 미발생이 아닌 경우에만 추가 텍스트 적용
	if (subrogationType !== '미발생') {
		claimReasonText += ` (${subrogationType})`;
	}
	
	// 기존 값이 있는 경우 기본 텍스트로 변경하지 않음
	if (!$('#claim_reason').val() || $('#claim_reason').val() === `채무자의 ${current_creditor_count}번 채무를 연대보증` || 
		$('#claim_reason').val() === `채무자의 ${current_creditor_count}번 채무를 연대보증 (일부대위변제)` || 
		$('#claim_reason').val() === `채무자의 ${current_creditor_count}번 채무를 연대보증 (전부대위변제)`) {
		$('#claim_reason').val(claimReasonText);
	}
	
	// placeholder 업데이트
	$('#claim_reason').attr('placeholder', claimReasonText);
}

// 대위변제 옵션에 따라 관련 필드 표시/숨김
function toggleSubrogationFields() {
	const subrogationType = $('input[name="subrogation_type"]:checked').val();
	
	// 일부대위변제 또는 전부대위변제일 때만 관련 필드 표시
	if (subrogationType === '일부대위변제' || subrogationType === '전부대위변제') {
		$('.subrogation-field').show();
		// 자동입력 버튼 표시
		$('.auto-fill').show();
		
		// 원금과 이자 필드를 수정 가능하게 설정
		$('#principal').prop('readonly', false);
		$('#interest').prop('readonly', false);
		
		// 기존에 "장래구상권 미발생" 또는 "미발생"으로 설정된 값을 지운다
		if ($('#principal').val() === '장래구상권 미발생') {
			$('#principal').val('');
		}
		if ($('#interest').val() === '미발생') {
			$('#interest').val('');
		}
	} else {
		$('.subrogation-field').hide();
		// 자동입력 버튼 숨김
		$('.auto-fill').hide();
		
		// 원금과 이자 필드를 수정 불가능하게 설정하고 값 지정
		$('#principal').val('장래구상권 미발생').prop('readonly', true);
		$('#interest').val('미발생').prop('readonly', true);
	}
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
	
	// 주소 검색 버튼
	$('.address-search').on('click', function() {
		searchAddress();
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
	
	// 자동입력 버튼 클릭 이벤트
	$('.auto-fill').on('click', function() {
		autoFillClaimContent();
	});
}

// 자동입력 버튼 기능
function autoFillClaimContent() {
	const principal = parseFloat($('#principal').val().replace(/,/g, '')) || 0;
	const interest = parseFloat($('#interest').val().replace(/,/g, '')) || 0;
	const defaultRate = $('#default_rate').val();
	
	const calculationDate = $('#principal_calculation').val().match(/\d{4}\.\d{2}\.\d{2}/);
	if (!calculationDate) {
		alert('채권현재액 산정근거에 유효한 날짜가 없습니다.');
		return;
	}
	
	const nextDay = addOneDay(calculationDate[0]);
	
	// 대위변제 유형에 따라 다른 내용 설정
	const subrogationType = $('input[name="subrogation_type"]:checked').val();
	
	if (subrogationType === '미발생') {
		const content = `보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자`;
		$('#claim_content').val(content);
	} else {
		const content = `원리금 ${numberWithCommas(principal + interest)}원 및 그 중 원금 ${numberWithCommas(principal)}원에 대한 ${nextDay}부터 완제일까지 연 ${defaultRate}%의 비율에 의한 지연손해금`;
		$('#claim_content').val(content);
	}
}

// 하루 추가
function addOneDay(dateString) {
	const parts = dateString.split('.');
	const date = new Date(parts[0], parts[1] - 1, parts[2]);
	date.setDate(date.getDate() + 1);
	return formatDate(date);
}

// 숫자 콤마 추가
function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 주소 검색 함수
function searchAddress() {
	const width = 500;
	const height = 500;
	const left = (window.screen.width / 2) - (width / 2);
	const top = (window.screen.height / 2) - (height / 2);

	new daum.Postcode({
		width: width,
		height: height,
		oncomplete: function(data) {
			$('#address').val(data.address);
			
			// 팝업 창 닫기
			const frame = document.getElementsByClassName('daum_postcode_layer')[0];
			if (frame) {
				frame.style.display = 'none';
			}
		},
		onclose: function() {
			const frame = document.getElementsByClassName('daum_postcode_layer')[0];
			if (frame) {
				frame.remove();
			}
		}
	}).open({
		left: left,
		top: top
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
			<div class="table-row">
				<div class="col">${current_creditor_count}-${index + 1}</div>
				<div class="col">${debt.subrogation_type || '미발생'}</div>
				<div class="col">${debt.financial_institution || ''}</div>
				<div class="col">${debt.address || ''}</div>
				<div class="col">
					<button type="button" class="edit-debt" data-debt-no="${debt.debt_no}">수정</button>
					<button type="button" class="delete-debt" data-debt-no="${debt.debt_no}">삭제</button>
				</div>
			</div>
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
	
	// 채권원인 초기화
	$('#claim_reason').val(`채무자의 ${current_creditor_count}번 채무를 연대보증`);
	
	// 새로 추가된 필드 초기화
	$('#original_debt_balance').val('');
	$('#original_debt_description').val('');
	$('#default_rate').val('');
	
	$('#principal').val('');
	$('#principal_calculation').val('');
	$('#interest').val('');
	$('#interest_calculation').val('');
	$('#calculation_date').val('');
	$('#claim_content').val('보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자');
	
	// 대위변제 선택 초기화
	$('#subrogation_none').prop('checked', true).trigger('change');
	
	$('#force_payment_plan').prop('checked', false);
	
	// 장래구상권 선택 초기화
	$('input[name="future_right_type"]').prop('checked', false);
	
	// 대위변제 표시 업데이트
	updateSubrogationDisplay();
	toggleSubrogationFields();
}

// 폼 데이터 채우기
function fillFormData(data) {
	$('#entity_type').val(data.entity_type || '법인');
	$('#financial_institution').val(data.financial_institution || '');
	$('#address').val(data.address || '');
	$('#phone').val(formatPhoneNumber(data.phone || ''));
	$('#fax').val(data.fax || '');
	$('#claim_reason').val(data.claim_reason || '');
	
	// 새로 추가된 필드 데이터 채우기
	$('#original_debt_balance').val(formatNumberValue(data.original_debt_balance));
	$('#original_debt_description').val(data.original_debt_description || '');
	$('#default_rate').val(data.default_rate || '');
	
	// 대위변제 선택 (폼 필드에 데이터를 채우기 전에 먼저 처리)
	if (data.subrogation_type) {
		$(`input[name="subrogation_type"][value="${data.subrogation_type}"]`).prop('checked', true).trigger('change');
	} else {
		$('#subrogation_none').prop('checked', true).trigger('change');
	}
	
	// 대위변제 타입이 '미발생'이 아닌 경우에만 실제 데이터로 채운다
	if (data.subrogation_type && data.subrogation_type !== '미발생') {
		$('#principal').val(formatNumberValue(data.principal));
		$('#interest').val(formatNumberValue(data.interest));
	}
	// 미발생의 경우 toggleSubrogationFields에서 설정됨
	
	$('#principal_calculation').val(data.principal_calculation || '');
	$('#interest_calculation').val(data.interest_calculation || '');
	$('#calculation_date').val(data.calculation_date ? data.calculation_date.split(' ')[0] : '');
	$('#claim_content').val(data.claim_content || '보증채무를 대위변제할 경우 대위변제금액 및 이에 대한 대위변제일 이후의 민사 법정이율에 의한 이자');
	
	// 강제 기재 여부
	$('#force_payment_plan').prop('checked', data.force_payment_plan == 1);
	
	// 장래구상권 선택
	if (data.future_right_type) {
		$(`input[name="future_right_type"][value="${data.future_right_type}"]`).prop('checked', true);
	} else {
		$('input[name="future_right_type"]').prop('checked', false);
	}
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
		// 새로 추가된 필드
		original_debt_balance: getNumber('#original_debt_balance'),
		original_debt_description: $('#original_debt_description').val(),
		principal: getNumber('#principal'),
		principal_calculation: $('#principal_calculation').val(),
		interest: getNumber('#interest'),
		interest_calculation: $('#interest_calculation').val(),
		default_rate: $('#default_rate').val(),
		calculation_date: $('#calculation_date').val(),
		claim_content: $('#claim_content').val(),
		future_right_type: $('input[name="future_right_type"]:checked').val() || null
	};
	
	// 필수 필드 체크
	if (!formData.financial_institution) {
		alert('금융기관명은 필수 입력 항목입니다.');
		$('#financial_institution').focus();
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