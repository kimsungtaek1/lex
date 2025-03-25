$(document).ready(function() {
	// 초기화
	initializeForm();
	loadSavedData();
	
	// 이벤트 리스너 등록
	registerEventListeners();
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
		if ($(this).attr('id').includes('creditor_') || $(this).attr('id').includes('undisputed_')) {
			calculateDifferences();
		}
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

// 기존 데이터 로드
function loadSavedData() {
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}
	
	const claim_no = $('#claimNo').val();
	if (!claim_no) {
		return; // 신규 생성 모드
	}

	$.ajax({
		url: '../../api/application_recovery/get_other_claims.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			claim_no: claim_no
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

// 폼 데이터 채우기
function fillFormData(data) {
	// 공통 필드 설정
	$('#creditor_principal').val(numberWithCommas(data.creditor_principal || 0));
	$('#creditor_interest').val(numberWithCommas(data.creditor_interest || 0));
	$('#undisputed_principal').val(numberWithCommas(data.undisputed_principal || 0));
	$('#undisputed_interest').val(numberWithCommas(data.undisputed_interest || 0));
	$('#dispute_reason').val(data.dispute_reason || '');
	$('#litigation_status').val(data.litigation_status || '');
	
	// 차이 계산
	calculateDifferences();
}

// 차이 계산 함수
function calculateDifferences() {
	const getIntValue = (selector) => {
		const val = $(selector).val();
		return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : 0;
	};
	
	const creditorPrincipal = getIntValue('#creditor_principal');
	const creditorInterest = getIntValue('#creditor_interest');
	const undisputedPrincipal = getIntValue('#undisputed_principal');
	const undisputedInterest = getIntValue('#undisputed_interest');
	
	const diffPrincipal = creditorPrincipal - undisputedPrincipal;
	const diffInterest = creditorInterest - undisputedInterest;
	
	$('#difference_principal').val(numberWithCommas(diffPrincipal));
	$('#difference_interest').val(numberWithCommas(diffInterest));
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

// 숫자 콤마 추가
function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
}

// 폼 저장
function saveForm() {
	// 필수 입력값 검증
	if (!validateForm()) {
		return;
	}

	const getIntValue = (selector) => {
		const val = $(selector).val();
		return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : 0;
	};

	// 기본 데이터
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		claim_no: $('#claimNo').val() || '',
		creditor_principal: getIntValue('#creditor_principal'),
		creditor_interest: getIntValue('#creditor_interest'),
		undisputed_principal: getIntValue('#undisputed_principal'),
		undisputed_interest: getIntValue('#undisputed_interest'),
		dispute_reason: $('#dispute_reason').val(),
		litigation_status: $('#litigation_status').val()
	};

	$.ajax({
		url: '../../api/application_recovery/save_other_claim.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					// 부모 창에 메시지 전송 및 데이터 전달
					window.opener.postMessage({
						type: 'otherClaimSaved', 
						creditorCount: current_creditor_count,
						hasData: true
					}, '*');
					
					// 저장 후 현재 창 새로고침
					location.reload();
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

// 폼 유효성 검사 함수
function validateForm() {
	// 기본 필수 필드 검증
	if (!$('#creditor_principal').val().trim() && !$('#creditor_interest').val().trim()) {
		alert('채권자 주장 금액을 입력해주세요.');
		$('#creditor_principal').focus();
		return false;
	}
	
	if (!$('#dispute_reason').val().trim()) {
		alert('다툼의 원인을 입력해주세요.');
		$('#dispute_reason').focus();
		return false;
	}
	
	return true;
}

// 폼 삭제
function deleteForm() {
	const claim_no = $('#claimNo').val();
	if (!claim_no) {
		alert('삭제할 데이터가 없습니다.');
		return;
	}

	$.ajax({
		url: '../../api/application_recovery/delete_other_claim.php',
		method: 'POST',
		data: {
			claim_no: claim_no
		},
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert('삭제되었습니다.');
					window.opener.postMessage({
						type: 'otherClaimDeleted',
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