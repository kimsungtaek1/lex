$(document).ready(function() {
	// 초기화
	initializeFormEvents();
	
	// URL에서 타입 가져오기
	const urlParams = new URLSearchParams(window.location.search);
	const appendixType = urlParams.get('type') || '(근)저당권설정';
	
	// 타입에 따라 UI 초기화
	setupUIByType(appendixType);
	
	// 타입 정보 히든 필드에 저장
	$('#appendixType').val(appendixType);
	
	loadSavedData();

	// 목적물 선택 버튼 이벤트
	$('#propertySelectBtn').on('click', function() {
		$.ajax({
			url: '../../api/application_recovery/get_assets.php',
			method: 'GET',
			data: { case_no: currentCaseNo },
			success: function(response) {
				let data = response;
				// response가 이미 객체인 경우 JSON.parse 생략
				if (typeof response === 'string') {
					try {
						data = JSON.parse(response);
					} catch (e) {
						console.error('목적물 데이터 파싱 오류:', e);
						return;
					}
				}
				
				if (data.success) {
					showPropertySelector(data.data);
				} else {
					console.error('목적물 데이터 로드 실패:', data.message || 'Unknown error');
				}
			},
			error: function(xhr) {
				console.error('목적물 데이터 요청 오류:', xhr.responseText);
			}
		});
	});

	// 숫자 입력 필드 이벤트
	$(document).on('input', '.number-input', function() {
		formatNumber($(this));
	});

	// 평가비율 입력 이벤트
	$('#evaluation_rate').on('input', function() {
		let value = $(this).val();
		// 숫자와 소수점만 허용
		value = value.replace(/[^\d.]/g, '');
		// 소수점이 2개 이상 입력되는 것 방지
		const decimalParts = value.split('.');
		if (decimalParts.length > 2) {
			value = decimalParts[0] + '.' + decimalParts.slice(1).join('');
		}
		// 소수점 이하 2자리로 제한
		if (decimalParts.length > 1) {
			value = decimalParts[0] + '.' + decimalParts[1].slice(0, 2);
		}
		$(this).val(value);
	});

	// 저장 버튼 이벤트
	$('#saveButton').on('click', function() {
		saveForm();
	});

	// 삭제 버튼 이벤트
	$('#deleteButton').on('click', function() {
		if (confirm('정말 삭제하시겠습니까?')) {
			deleteForm();
		}
	});

	// 닫기 버튼 이벤트
	$('#closeButton').on('click', function() {
		window.close();
	});
	
	// 계산 버튼 이벤트
	$('#calculateButton').on('click', function() {
		calculateValues();
	});
});

// 타입에 따라 UI 조정
function setupUIByType(type) {
	// 모든 타입별 필드 숨김
	$('.type-field').hide();
	
	// 타입에 따라 필요한 필드 표시
	if (type === '최우선변제임차권') {
		$('.type-top-priority').show();
	} else if (type === '우선변제임차권') {
		$('.type-priority').show();
	} else if (type === '질권설정/채권양도(전세보증금)') {
		$('.type-pledge').show();
	} else {
		// (근)저당권설정 (기본값)
		$('.type-mortgage').show();
	}
}

// 값 계산 함수
function calculateValues() {
	const appendixType = $('#appendixType').val() || '(근)저당권설정';
	
	const getIntValue = (selector) => {
		const val = $(selector).val();
		return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : 0;
	};

	// 입력 값 가져오기
	const expectedValue = getIntValue('#expected_value');
	const evaluationRate = parseFloat($('#evaluation_rate').val() || 70); // 기본값 70%
	
	// 타입별 계산 로직
	let securedExpectedClaim = 0;
	let unsecuredRemainingClaim = 0;
	let rehabilitationSecuredClaim = 0;
	
	// 원금과 이자 합계
	const totalClaim = selected_capital + selected_interest;

	if (appendixType === '(근)저당권설정') {
		const maxClaim = getIntValue('#max_claim');
		
		if (!expectedValue || !maxClaim) {
			alert('환가예상액과 채권최고액을 먼저 입력해주세요.');
			return;
		}
		
		const evaluatedValue = Math.floor((expectedValue * evaluationRate) / 100);
		securedExpectedClaim = Math.min(evaluatedValue, maxClaim, totalClaim);
		unsecuredRemainingClaim = Math.max(0, Math.min(totalClaim - securedExpectedClaim, maxClaim - securedExpectedClaim));
		rehabilitationSecuredClaim = securedExpectedClaim;
	} else if (appendixType === '질권설정/채권양도(전세보증금)') {
		const pledgeAmount = getIntValue('#pledge_amount');
		
		if (!pledgeAmount) {
			alert('질권설정(채권양도)금을 입력해주세요.');
			return;
		}
		
		securedExpectedClaim = Math.min(pledgeAmount, totalClaim);
		unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
		rehabilitationSecuredClaim = securedExpectedClaim;
	} else if (appendixType === '최우선변제임차권') {
		const topPriorityAmount = getIntValue('#top_priority_amount');
		
		if (!topPriorityAmount) {
			alert('최우선변제금을 입력해주세요.');
			return;
		}
		
		securedExpectedClaim = Math.min(topPriorityAmount, totalClaim);
		unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
		rehabilitationSecuredClaim = securedExpectedClaim;
	} else if (appendixType === '우선변제임차권') {
		const priorityDeposit = getIntValue('#priority_deposit');
		
		if (!priorityDeposit) {
			alert('임대차보증금을 입력해주세요.');
			return;
		}
		
		securedExpectedClaim = Math.min(priorityDeposit, totalClaim);
		unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
		rehabilitationSecuredClaim = securedExpectedClaim;
	}

	// 결과 값 설정
	$('#secured_expected_claim').val(securedExpectedClaim.toLocaleString('ko-KR'));
	$('#unsecured_remaining_claim').val(unsecuredRemainingClaim.toLocaleString('ko-KR'));
	$('#rehabilitation_secured_claim').val(rehabilitationSecuredClaim.toLocaleString('ko-KR'));
}

// 폼 이벤트 초기화
function initializeFormEvents() {
	// 숫자 입력 필드 초기화
	$('.number-input').each(function() {
		formatNumber($(this));
	});
}

// 숫자 포맷팅 함수
function formatNumber(input) {
	let value = input.val().replace(/[^\d]/g, '');
	if (value) {
		value = Number(value).toLocaleString('ko-KR');
		input.val(value);
	}
}

// 쉼표 제거 함수
function removeCommas(str) {
	return str ? str.replace(/,/g, '') : '';
}

// 기존 데이터 로드
function loadSavedData() {
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}

	$.ajax({
		url: '../../api/application_recovery/get_appendix.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			mortgage_no: current_creditor_count
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success) {
					if (data.data && data.data.length > 0) {
						fillFormData(data.data[0]);
					} else {
						clearForm();
					}
				} else {
					console.error('데이터 로드 실패:', data.message);
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

// 목적물 선택 창 열기
function showPropertySelector(properties) {
	const popupWindow = window.open(
		`../../api/application_recovery/property_select.php?case_no=${currentCaseNo}&count=${current_creditor_count}`,
		'propertySelect',
		'width=1200,height=400'
	);

	// 선택 데이터 수신 이벤트
	window.addEventListener('message', function(event) {
		if (event.data.type === 'propertySelected') {
			const property = properties.find(p => p.id === event.data.propertyId);
			if (property) {
				fillPropertyData(property);
				appendDataToMainForm(property);
			}
		}
	});
}

// 선택한 데이터를 메인 폼에 자동 입력
function appendDataToMainForm(property) {
	const appendixType = $('#appendixType').val() || '(근)저당권설정';
	
	const formData = {
		appendix_type: appendixType,
		bond_number: property.bond_number || '',
		creditor_name: property.creditor_name || '',
		property_detail: property.property_detail || '',
		expected_value: property.expected_value || '',
		evaluation_rate: property.evaluation_rate || '',
		max_claim: property.max_claim || '',
		registration_date: property.registration_date || ''
	};

	// 타입별 추가 필드
	if (appendixType === '최우선변제임차권' && property.priority_amount) {
		formData.priority_amount = property.priority_amount;
	}
	
	if (appendixType === '우선변제임차권' && property.resident_registration_date) {
		formData.resident_registration_date = property.resident_registration_date;
	}

	// 데이터를 메인 폼에 채우기
	for (const [key, value] of Object.entries(formData)) {
		const $input = $(`#${key}`);
		if ($input.length) {
			if ($input.hasClass('number-input') && value) {
				$input.val(Number(value).toLocaleString('ko-KR'));
			} else {
				$input.val(value);
			}
		}
	}

	// 데이터 저장 요청
	$.ajax({
		url: '../../api/application_recovery/save_appendix.php',
		method: 'POST',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			...formData
		},
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.status === 'success') {
					alert('목적물 정보가 저장되었습니다.');
				} else {
					console.error('저장 실패:', result);
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

// 폼 데이터 채우기
function fillFormData(data) {
	// 타입 설정 및 UI 조정
	const appendixType = data.appendix_type || $('#appendixType').val() || '(근)저당권설정';
	$('#appendixType').val(appendixType);
	setupUIByType(appendixType);
	
	// 공통 필드 설정
	$('#property_detail').val(data.property_detail || '');
	
	// 숫자 필드 포맷팅
	const formatNumberField = (selector, value) => {
		if (value !== null && value !== undefined && value !== '') {
			$(selector).val(Number(value).toLocaleString('ko-KR'));
		} else {
			$(selector).val('');
		}
	};
	
	formatNumberField('#expected_value', data.expected_value);
	$('#evaluation_rate').val(data.evaluation_rate || '');
	formatNumberField('#secured_expected_claim', data.secured_expected_claim);
	formatNumberField('#unsecured_remaining_claim', data.unsecured_remaining_claim);
	formatNumberField('#rehabilitation_secured_claim', data.rehabilitation_secured_claim);
	
	// 타입별 필드 설정
	if (appendixType === '(근)저당권설정') {
		formatNumberField('#max_claim', data.max_claim);
		$('#registration_date').val(data.registration_date || '');
	} else if (appendixType === '질권설정/채권양도(전세보증금)') {
		formatNumberField('#pledge_deposit', data.pledge_deposit);
		formatNumberField('#pledge_amount', data.pledge_amount);
		$('#lease_start_date').val(data.lease_start_date || '');
		$('#lease_end_date').val(data.lease_end_date || '');
	} else if (appendixType === '최우선변제임차권') {
		$('#first_mortgage_date').val(data.first_mortgage_date || '');
		$('#region').val(data.region || '서울특별시');
		formatNumberField('#lease_deposit', data.lease_deposit);
		formatNumberField('#top_priority_amount', data.top_priority_amount);
		$('#top_lease_start_date').val(data.top_lease_start_date || '');
		$('#top_lease_end_date').val(data.top_lease_end_date || '');
	} else if (appendixType === '우선변제임차권') {
		formatNumberField('#priority_deposit', data.priority_deposit);
		$('#priority_lease_start_date').val(data.priority_lease_start_date || '');
		$('#priority_lease_end_date').val(data.priority_lease_end_date || '');
		$('#fixed_date').val(data.fixed_date || '');
	}
}

// 목적물 데이터 채우기
function fillPropertyData(property) {
	$('#property_detail').val(property.detail || '');
	if (property.expected_value) {
		$('#expected_value').val(Number(property.expected_value).toLocaleString('ko-KR'));
	}
	if (property.evaluation_rate) {
		$('#evaluation_rate').val(property.evaluation_rate);
	}
}

// 폼 저장
function saveForm() {
	const appendixType = $('#appendixType').val() || '(근)저당권설정';
	
	const getIntValue = (selector) => {
		const val = $(selector).val();
		return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : null;
	};

	// 필수 값 검증
	if (!$('#property_detail').val()) {
		alert('목적물을 입력해주세요.');
		$('#property_detail').focus();
		return;
	}

	// 기본 데이터
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		mortgage_no: $('#mortgageNo').val() || current_creditor_count,
		appendix_type: appendixType,
		property_detail: $('#property_detail').val() || '',
		expected_value: getIntValue('#expected_value'),
		evaluation_rate: $('#evaluation_rate').val(),
		secured_expected_claim: getIntValue('#secured_expected_claim'),
		unsecured_remaining_claim: getIntValue('#unsecured_remaining_claim'),
		rehabilitation_secured_claim: getIntValue('#rehabilitation_secured_claim')
	};
	
	// 타입별 추가 필드
	if (appendixType === '(근)저당권설정') {
		formData.max_claim = getIntValue('#max_claim');
		formData.registration_date = $('#registration_date').val() || null;
	} else if (appendixType === '질권설정/채권양도(전세보증금)') {
		formData.pledge_deposit = getIntValue('#pledge_deposit');
		formData.pledge_amount = getIntValue('#pledge_amount');
		formData.lease_start_date = $('#lease_start_date').val() || null;
		formData.lease_end_date = $('#lease_end_date').val() || null;
	} else if (appendixType === '최우선변제임차권') {
		formData.first_mortgage_date = $('#first_mortgage_date').val() || null;
		formData.region = $('#region').val() || null;
		formData.lease_deposit = getIntValue('#lease_deposit');
		formData.top_priority_amount = getIntValue('#top_priority_amount');
		formData.top_lease_start_date = $('#top_lease_start_date').val() || null;
		formData.top_lease_end_date = $('#top_lease_end_date').val() || null;
	} else if (appendixType === '우선변제임차권') {
		formData.priority_deposit = getIntValue('#priority_deposit');
		formData.priority_lease_start_date = $('#priority_lease_start_date').val() || null;
		formData.priority_lease_end_date = $('#priority_lease_end_date').val() || null;
		formData.fixed_date = $('#fixed_date').val() || null;
	}

	$.ajax({
		url: '../../api/application_recovery/save_appendix.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.status === 'success') {
					alert(result.message || '저장되었습니다.');
					window.opener.postMessage({
						type: 'appendixSaved', 
						creditorCount: current_creditor_count
					}, '*');
					location.reload();
				} else {
					console.log('저장 실패 응답:', result);
					alert('저장 중 오류가 발생했습니다.');
				}
			} catch (e) {
				console.error('저장 오류:', e);
				alert('저장 처리 중 오류가 발생했습니다.');
			}
		},
		error: function(xhr) {
			console.error('서버 통신 오류:', xhr.responseText);
			alert('서버와의 통신 중 오류가 발생했습니다.');
		}
	});
}

// 폼 초기화
function clearForm() {
	$('input[type="text"]').val('');
	$('input[type="number"]').val('');
	$('.number-input').val('');
	$('select').each(function() {
		$(this).val($(this).find('option:first').val());
	});
	$('input[type="date"]').val('');
}

// 폼 삭제
function deleteForm() {
	$.ajax({
		url: '../../api/application_recovery/delete_appendix.php',
		method: 'POST',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			mortgage_no: $('#mortgageNo').val() || current_creditor_count
		},
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert('삭제되었습니다.');
					window.opener.postMessage({
						type: 'appendixDeleted',
						creditorCount: current_creditor_count
					}, '*');
					window.close();
				} else {
					alert(result.message || '삭제 중 오류가 발생했습니다.');
				}
			} catch (e) {
				console.error('삭제 오류:', e);
				alert('삭제 처리 중 오류가 발생했습니다.');
			}
		},
		error: function(xhr) {
			console.error('서버 통신 오류:', xhr.responseText);
			alert('서버와의 통신 중 오류가 발생했습니다.');
		}
	});
}