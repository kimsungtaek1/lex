$(document).ready(function() {
	// 초기화
	initializeForm();
	
	// 이벤트 리스너 등록
	registerEventListeners();
	
	// 저장된 데이터 불러오기
	loadClaimData();
});

// 폼 초기화
function initializeForm() {
	// 숫자 입력 필드 포맷팅
	$('.number-input').each(function() {
		formatNumber($(this));
	});
}

// 이벤트 리스너 등록
function registerEventListeners() {
	// 숫자 입력 필드 이벤트
	$(document).on('input', '.number-input', function() {
		formatNumber($(this));
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
}

// 저장된 클레임 데이터 불러오기
function loadClaimData() {
	// 사건 및 채권자 번호가 없으면 데이터를 조회할 수 없음
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}
	
	$.ajax({
		url: '../../api/application_recovery/get_undetermined_claims.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					// 첫 번째 데이터로 폼 채우기
					fillFormData(data.data[0]);
					// claim_no 필드에 값 설정
					$('#claimNo').val(data.data[0].claim_no);
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

// 폼 초기화
function clearForm() {
	$('#property_detail').val('');
	$('#expected_value').val('');
	$('#evaluation_rate').val('');
	$('#trust_property_details').val('');
	$('#priority_certificate_amount').val('');
	$('#registration_date').val('');
	$('#expected_payment').val('');
	$('#unpaid_amount').val('');
}

// 폼 데이터 채우기
function fillFormData(data) {
	$('#property_detail').val(data.property_detail || '');
	$('#expected_value').val(formatNumberValue(data.expected_value));
	$('#evaluation_rate').val(data.evaluation_rate || '');
	$('#trust_property_details').val(data.trust_property_details || '');
	$('#priority_certificate_amount').val(formatNumberValue(data.priority_certificate_amount));
	$('#registration_date').val(data.registration_date || '');
	$('#expected_payment').val(formatNumberValue(data.expected_payment));
	$('#unpaid_amount').val(formatNumberValue(data.unpaid_amount));
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

// 폼 저장
function saveForm() {
	const getNumber = function(selector) {
		return parseFloat($(selector).val().replace(/,/g, '')) || 0;
	};
	
	// 데이터 수집
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		property_detail: $('#property_detail').val(),
		expected_value: getNumber('#expected_value'),
		evaluation_rate: $('#evaluation_rate').val(),
		trust_property_details: $('#trust_property_details').val(),
		priority_certificate_amount: getNumber('#priority_certificate_amount'),
		registration_date: $('#registration_date').val(),
		expected_payment: getNumber('#expected_payment'),
		unpaid_amount: getNumber('#unpaid_amount')
	};
	
	// claim_no가 있으면 추가
	const claimNo = $('#claimNo').val();
	if (claimNo) {
		formData.claim_no = claimNo;
	}
	
	$.ajax({
		url: '../../api/application_recovery/save_undetermined_claim.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'undeterminedClaimSaved', 
						creditorCount: current_creditor_count,
						hasData: true
					}, '*');
					
					// claim_no 업데이트
					if (result.claim_no) {
						$('#claimNo').val(result.claim_no);
					}
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

// 폼 삭제
function deleteForm() {
	const claimNo = $('#claimNo').val();
	if (!claimNo) {
		alert('삭제할 항목이 없습니다.');
		return;
	}
	
	$.ajax({
		url: '../../api/application_recovery/delete_undetermined_claim.php',
		method: 'POST',
		data: { claim_no: claimNo },
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '삭제되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'undeterminedClaimDeleted',
						creditorCount: current_creditor_count,
						hasData: false
					}, '*');
					
					// 창 닫기
					window.close();
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