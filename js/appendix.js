$(document).ready(function() {
	// 초기화
	initializeForm();
	loadSavedData();
	
	// 이벤트 리스너 등록
	registerEventListeners();
});

let isSamePropertySelected = false; // 동일 목적물 선택 여부 플래그

// 폼 초기화
function initializeForm() {
	// 숫자 입력 필드 포맷팅
	$('.number-input').each(function() {
		formatNumber($(this));
	});
	
	// 초기 타입에 따라 UI 설정
	const initialType = $('#appendixType').val() || '(근)저당권설정';
	$('#appendixType').val(initialType);
	updateAppendixHeader(initialType);
	setupUIByType(initialType);
}

// 이벤트 리스너 등록
function registerEventListeners() {
	// 목적물 선택 버튼
	$('#propertySelectBtn').on('click', loadAndShowPropertySelector);
	
	// 숫자 입력 필드 이벤트
	$(document).on('input', '.number-input', function() {
		formatNumber($(this));
	});
	
	// 평가비율 입력 이벤트
	$('#evaluation_rate').on('input', handleEvaluationRateInput);
	
	// 부속서류 타입 변경 이벤트
	$('#appendixType').on('change', function() {
		const selectedType = $(this).val();
		updateAppendixHeader(selectedType);
		setupUIByType(selectedType);
	});
	
	// 계산 버튼
	$('#calculateButton').on('click', calculateValues);
	
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

	// 메시지 이벤트 리스너 (팝업에서 데이터 받기)
	window.addEventListener('message', function(event) {
		// console.log("Message received:", event.data); // 디버깅용 로그
		// 출처 확인 (선택 사항이지만 보안상 권장)
		// if (event.origin !== 'expected_origin') return;

		if (event.data && event.data.type === 'propertySelected') { // Corrected message type
			fillSelectedAppendixData(event.data.propertyData); // Use propertyData as sent from property_select.php
			isSamePropertySelected = true; // 동일 목적물 선택 플래그 설정
			// console.log("isSamePropertySelected set to true"); // 디버깅용 로그
		}
	});
}

// 부속서류 헤더 업데이트
function updateAppendixHeader(type) {
	// 선택한 타입 그대로 헤더에 표시
	$('#appendixTypeHeader').text(type);
	$('#appendixTypeDisplay').text(type);
}

// 타입에 따라 UI 조정
function setupUIByType(type) {
	// 모든 타입별 필드 숨김
	$('.type-field').hide();
	
	// 타입에 따라 필요한 필드 표시
	const typeMapping = {
		'(근)저당권설정': '.type-mortgage',
		'질권설정/채권양도(전세보증금)': '.type-pledge',
		'최우선변제임차권': '.type-top-priority',
		'우선변제임차권': '.type-priority'
	};
	
	const selector = typeMapping[type];
	if (selector) {
		$(selector).show();
	}
}

// 평가비율 입력 처리
function handleEvaluationRateInput() {
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
}

// 기존 데이터 로드
function loadSavedData() {
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 current_creditor_count');
		return;
	}

	$.ajax({
		url: 'get_appendix.php', // 경로 수정
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				console.log(data);
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

// 동일 목적물 목록 팝업 열기
function loadAndShowPropertySelector() {
	// appendix.php 기준 상대 경로 수정
	const popupUrl = `property_select.php?case_no=${currentCaseNo}`; 
	const popupWindow = window.open(
		popupUrl,
		'appendixListSelect',
		'width=1200,height=600,scrollbars=yes,resizable=yes'
	);
	if (popupWindow) {
		popupWindow.focus();
	} else {
		alert('팝업 창을 열 수 없습니다. 팝업 차단 설정을 확인해주세요.');
	}
}


// 팝업에서 선택된 부속서류 데이터로 폼 채우기
function fillSelectedAppendixData(data) {
	// console.log("Filling form with selected data:", data); // 디버깅용 로그
	// 기존 폼 데이터 초기화 (선택적)
	// clearForm(); 

	// 타입 설정 및 UI 조정
	const appendixType = data.appendix_type || '(근)저당권설정';
	$('#appendixType').val(appendixType);
	updateAppendixHeader(appendixType);
	setupUIByType(appendixType);

	// 공통 필드 설정 (property_detail은 그대로 가져옴, 저장은 별도 처리)
	$('#property_detail').val(data.property_detail || '');

	// 숫자 필드 포맷팅 함수 재사용
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
	const typeFieldSetters = {
		'(근)저당권설정': () => {
			formatNumberField('#max_claim', data.max_claim);
			$('#registration_date').val(data.registration_date || '');
		},
		'질권설정/채권양도(전세보증금)': () => {
			formatNumberField('#pledge_deposit', data.pledge_deposit);
			formatNumberField('#pledge_amount', data.pledge_amount);
			$('#lease_start_date').val(data.lease_start_date || '');
			$('#lease_end_date').val(data.lease_end_date || '');
		},
		'최우선변제임차권': () => {
			$('#first_mortgage_date').val(data.first_mortgage_date || '');
			$('#region').val(data.region || '서울특별시');
			formatNumberField('#lease_deposit', data.lease_deposit);
			formatNumberField('#top_priority_amount', data.top_priority_amount);
			$('#top_lease_start_date').val(data.top_lease_start_date || '');
			$('#top_lease_end_date').val(data.top_lease_end_date || '');
		},
		'우선변제임차권': () => {
			formatNumberField('#priority_deposit', data.priority_deposit);
			$('#priority_lease_start_date').val(data.priority_lease_start_date || '');
			$('#priority_lease_end_date').val(data.priority_lease_end_date || '');
			$('#fixed_date').val(data.fixed_date || '');
		}
	};

	const setter = typeFieldSetters[appendixType];
	if (setter) setter();

	// 계산 필요 시 계산 함수 호출 (선택적)
	// calculateValues();
}


// 기존 데이터 로드 시 폼 데이터 채우기 (수정 없음)
function fillFormData(data) {
	// 타입 설정 및 UI 조정
	const appendixType = data.appendix_type || $('#appendixType').val() || '(근)저당권설정';
	$('#appendixType').val(appendixType);
	updateAppendixHeader(appendixType);
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
	const typeFieldSetters = {
		'(근)저당권설정': () => {
			formatNumberField('#max_claim', data.max_claim);
			$('#registration_date').val(data.registration_date || '');
		},
		'질권설정/채권양도(전세보증금)': () => {
			formatNumberField('#pledge_deposit', data.pledge_deposit);
			formatNumberField('#pledge_amount', data.pledge_amount);
			$('#lease_start_date').val(data.lease_start_date || '');
			$('#lease_end_date').val(data.lease_end_date || '');
		},
		'최우선변제임차권': () => {
			$('#first_mortgage_date').val(data.first_mortgage_date || '');
			$('#region').val(data.region || '서울특별시');
			formatNumberField('#lease_deposit', data.lease_deposit);
			formatNumberField('#top_priority_amount', data.top_priority_amount);
			$('#top_lease_start_date').val(data.top_lease_start_date || '');
			$('#top_lease_end_date').val(data.top_lease_end_date || '');
		},
		'우선변제임차권': () => {
			formatNumberField('#priority_deposit', data.priority_deposit);
			$('#priority_lease_start_date').val(data.priority_lease_start_date || '');
			$('#priority_lease_end_date').val(data.priority_lease_end_date || '');
			$('#fixed_date').val(data.fixed_date || '');
		}
	};
	
	const setter = typeFieldSetters[appendixType];
	if (setter) setter();
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

	// 타입별 계산 로직
	const typeCalculators = {
		'(근)저당권설정': () => {
			const maxClaim = getIntValue('#max_claim');
			
			if (!expectedValue || !maxClaim) {
				alert('환가예상액과 채권최고액을 먼저 입력해주세요.');
				return false;
			}
			
			const evaluatedValue = Math.floor((expectedValue * evaluationRate) / 100);
			securedExpectedClaim = Math.min(evaluatedValue, maxClaim, totalClaim);
			unsecuredRemainingClaim = Math.max(0, Math.min(totalClaim - securedExpectedClaim, maxClaim - securedExpectedClaim));
			rehabilitationSecuredClaim = securedExpectedClaim;
			return true;
		},
		'질권설정/채권양도(전세보증금)': () => {
			const pledgeAmount = getIntValue('#pledge_amount');
			
			if (!pledgeAmount) {
				alert('질권설정(채권양도)금을 입력해주세요.');
				return false;
			}
			
			securedExpectedClaim = Math.min(pledgeAmount, totalClaim);
			unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
			rehabilitationSecuredClaim = securedExpectedClaim;
			return true;
		},
		'최우선변제임차권': () => {
			const topPriorityAmount = getIntValue('#top_priority_amount');
			
			if (!topPriorityAmount) {
				alert('최우선변제금을 입력해주세요.');
				return false;
			}
			
			securedExpectedClaim = Math.min(topPriorityAmount, totalClaim);
			unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
			rehabilitationSecuredClaim = securedExpectedClaim;
			return true;
		},
		'우선변제임차권': () => {
			const priorityDeposit = getIntValue('#priority_deposit');
			
			if (!priorityDeposit) {
				alert('임대차보증금을 입력해주세요.');
				return false;
			}
			
			securedExpectedClaim = Math.min(priorityDeposit, totalClaim);
			unsecuredRemainingClaim = Math.max(0, totalClaim - securedExpectedClaim);
			rehabilitationSecuredClaim = securedExpectedClaim;
			return true;
		}
	};
	
	const calculator = typeCalculators[appendixType];
	if (calculator && calculator()) {
		// 결과 값 설정
		$('#secured_expected_claim').val(securedExpectedClaim.toLocaleString('ko-KR'));
		$('#unsecured_remaining_claim').val(unsecuredRemainingClaim.toLocaleString('ko-KR'));
		$('#rehabilitation_secured_claim').val(rehabilitationSecuredClaim.toLocaleString('ko-KR'));
	}
}

// 숫자 포맷팅 함수
function formatNumber(input) {
	if (!input || !input.val) return;
	
	let value = input.val().replace(/[^\d.-]/g, '');
	if (value) {
		try {
			value = Number(value).toLocaleString('ko-KR');
			input.val(value);
		} catch (e) {
			console.error('숫자 변환 오류:', e);
		}
	}
}

// 쉼표 제거 함수
function removeCommas(str) {
	return str ? str.replace(/,/g, '') : '';
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

function saveForm() {
    const appendixType = $('#appendixType').val() || '(근)저당권설정';
    
    // 필수 입력값 검증 강화 - 선택적으로 활성화 가능
    if (arguments.length === 0 || arguments[0] !== 'skipValidation') {
        if (!validateForm(appendixType)) {
            return;
        }
    }

    const getIntValue = (selector) => {
        const val = $(selector).val();
        return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : null;
    };

    // 동일 목적물 선택 시 property_detail 처리
    let propertyDetailValue = $('#property_detail').val() || '';
    // console.log("Before save - isSamePropertySelected:", isSamePropertySelected); // 디버깅용 로그
    if (isSamePropertySelected) {
        propertyDetailValue = ''; // 동일 목적물 선택 시 빈 값으로 저장
        // console.log("Property detail cleared due to same property selection."); // 디버깅용 로그
    }

    // 기본 데이터
    const formData = {
        case_no: currentCaseNo,
        creditor_count: current_creditor_count,
        appendix_type: appendixType,
        property_detail: propertyDetailValue, // 수정된 값 사용
        expected_value: getIntValue('#expected_value'),
        evaluation_rate: $('#evaluation_rate').val(),
        secured_expected_claim: getIntValue('#secured_expected_claim'),
        unsecured_remaining_claim: getIntValue('#unsecured_remaining_claim'),
        rehabilitation_secured_claim: getIntValue('#rehabilitation_secured_claim')
    };
    
    // 타입별 추가 필드 설정
    const typeDataCollectors = {
        '(근)저당권설정': () => ({
            max_claim: getIntValue('#max_claim'),
            registration_date: $('#registration_date').val() || null
        }),
        '질권설정/채권양도(전세보증금)': () => ({
            pledge_deposit: getIntValue('#pledge_deposit'),
            pledge_amount: getIntValue('#pledge_amount'),
            lease_start_date: $('#lease_start_date').val() || null,
            lease_end_date: $('#lease_end_date').val() || null
        }),
        '최우선변제임차권': () => ({
            first_mortgage_date: $('#first_mortgage_date').val() || null,
            region: $('#region').val() || null,
            lease_deposit: getIntValue('#lease_deposit'),
            top_priority_amount: getIntValue('#top_priority_amount'),
            top_lease_start_date: $('#top_lease_start_date').val() || null,
            top_lease_end_date: $('#top_lease_end_date').val() || null
        }),
        '우선변제임차권': () => ({
            priority_deposit: getIntValue('#priority_deposit'),
            priority_lease_start_date: $('#priority_lease_start_date').val() || null,
            priority_lease_end_date: $('#priority_lease_end_date').val() || null,
            fixed_date: $('#fixed_date').val() || null
        })
    };
    
    const collector = typeDataCollectors[appendixType];
    if (collector) {
        Object.assign(formData, collector());
    }

    $.ajax({
        url: 'save_appendix.php', // 경로 수정
        method: 'POST',
        data: formData,
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.status === 'success') {
                    alert(result.message || '저장되었습니다.');
                    
                    // 부모 창에 메시지 전송 및 데이터 전달
                    window.opener.postMessage({
                        type: 'appendixSaved', 
                        creditorCount: current_creditor_count,
                        hasData: true,
                    }, '*');

                    isSamePropertySelected = false; // 저장 성공 후 플래그 리셋
                    // console.log("isSamePropertySelected reset to false after successful save."); // 디버깅용 로그
                } else {
                    console.log('저장 실패 응답:', result);
                    alert('저장 중 오류가 발생했습니다.');
                    if (result.message) {
                        console.error('에러 메시지:', result.message);
                    }
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

// 폼 유효성 검사 함수 추가
function validateForm(appendixType) {
	// 기본 필수 필드 검증
	if (!$('#property_detail').val().trim()) {
		alert('목적물을 입력해주세요.');
		$('#property_detail').focus();
		return false;
	}
	
	if (!$('#expected_value').val().trim()) {
		alert('환가예상액을 입력해주세요.');
		$('#expected_value').focus();
		return false;
	}
	
	if (!$('#evaluation_rate').val().trim()) {
		alert('평가비율을 입력해주세요.');
		$('#evaluation_rate').focus();
		return false;
	}
	
	// 타입별 필수 필드 검증
	switch (appendixType) {
		case '(근)저당권설정':
			if (!$('#max_claim').val().trim()) {
				alert('채권최고액(담보액)을 입력해주세요.');
				$('#max_claim').focus();
				return false;
			}
			break;
			
		case '질권설정/채권양도(전세보증금)':
			if (!$('#pledge_amount').val().trim()) {
				alert('질권설정(채권양도)금을 입력해주세요.');
				$('#pledge_amount').focus();
				return false;
			}
			break;
			
		case '최우선변제임차권':
			if (!$('#top_priority_amount').val().trim()) {
				alert('최우선변제금을 입력해주세요.');
				$('#top_priority_amount').focus();
				return false;
			}
			break;
			
		case '우선변제임차권':
			if (!$('#priority_deposit').val().trim()) {
				alert('임대차보증금을 입력해주세요.');
				$('#priority_deposit').focus();
				return false;
			}
			break;
	}
	
	// 계산 결과 필드 검증
	if (!$('#secured_expected_claim').val().trim() || !$('#unsecured_remaining_claim').val().trim() || !$('#rehabilitation_secured_claim').val().trim()) {
		if (confirm('채권액 계산이 완료되지 않았습니다. 계산하시겠습니까?')) {
			calculateValues();
			// 계산 후 다시 검증
			if (!$('#secured_expected_claim').val().trim() || !$('#unsecured_remaining_claim').val().trim() || !$('#rehabilitation_secured_claim').val().trim()) {
				alert('채권액 계산을 완료해주세요.');
				return false;
			}
		} else {
			return false;
		}
	}
	
	return true;
}

// 폼 삭제
function deleteForm() {
	$.ajax({
		url: 'delete_appendix.php', // 경로 수정
		method: 'POST',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
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
