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
	
	// 차이값 계산 입력 필드 이벤트
	$('#creditor_principal, #creditor_interest, #undisputed_principal, #undisputed_interest').on('input', function() {
		calculateDifference();
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
	const claimNo = $('#claimNo').val();
	
	if (!claimNo) {
		return; // 새로운 항목인 경우 로드 안함
	}
	
	$.ajax({
		url: '../../api/application_recovery/get_other_claims.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count,
			claim_no: claimNo
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					fillFormData(data.data[0]);
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
	$('#creditor_principal').val(formatNumberValue(data.creditor_principal));
	$('#creditor_interest').val(formatNumberValue(data.creditor_interest));
	$('#undisputed_principal').val(formatNumberValue(data.undisputed_principal));
	$('#undisputed_interest').val(formatNumberValue(data.undisputed_interest));
	$('#dispute_reason').val(data.dispute_reason || '');
	$('#litigation_status').val(data.litigation_status || '');
	
	calculateDifference();
}

// 차이 계산
function calculateDifference() {
	const getNumber = function(selector) {
		return parseFloat($(selector).val().replace(/,/g, '')) || 0;
	};
	
	const creditorPrincipal = getNumber('#creditor_principal');
	const creditorInterest = getNumber('#creditor_interest');
	const undisputedPrincipal = getNumber('#undisputed_principal');
	const undisputedInterest = getNumber('#undisputed_interest');
	
	const diffPrincipal = creditorPrincipal - undisputedPrincipal;
	const diffInterest = creditorInterest - undisputedInterest;
	
	$('#difference_principal').val(formatNumberValue(diffPrincipal));
	$('#difference_interest').val(formatNumberValue(diffInterest));
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
	// 데이터 수집
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		claim_type: '다툼있는채권', // 기본값 설정
		creditor_principal: $('#creditor_principal').val().replace(/,/g, ''),
		creditor_interest: $('#creditor_interest').val().replace(/,/g, ''),
		undisputed_principal: $('#undisputed_principal').val().replace(/,/g, ''),
		undisputed_interest: $('#undisputed_interest').val().replace(/,/g, ''),
		difference_principal: $('#difference_principal').val().replace(/,/g, ''),
		difference_interest: $('#difference_interest').val().replace(/,/g, ''),
		dispute_reason: $('#dispute_reason').val(),
		litigation_status: $('#litigation_status').val(),
		amount: $('#difference_principal').val().replace(/,/g, '') // amount 필드 설정
	};
	
	// claim_no가 있으면 추가
	const claimNo = $('#claimNo').val();
	if (claimNo) {
		formData.claim_no = claimNo;
	}
	
	$.ajax({
		url: '../../api/application_recovery/save_other_claim.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					// 부모 창에 메시지 전달
					window.opener.postMessage({
						type: 'otherClaimSaved', 
						creditorCount: current_creditor_count,
						hasData: true
					}, '*');
					
					// 현재 창 새로 로드
					location.reload();
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
		url: '../../api/application_recovery/delete_other_claim.php',
		method: 'POST',
		data: { claim_no: claimNo },
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '삭제되었습니다.');
					// 부모 창에 메시지 전달
					window.opener.postMessage({
						type: 'otherClaimDeleted',
						creditorCount: current_creditor_count
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