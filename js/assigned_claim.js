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
	// 텍스트 영역 자동 높이 조정
	$('textarea').each(function() {
		this.style.height = 'auto';
		this.style.height = (this.scrollHeight) + 'px';
	}).on('input', function() {
		this.style.height = 'auto';
		this.style.height = (this.scrollHeight) + 'px';
	});
}

// 이벤트 리스너 등록
function registerEventListeners() {
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
		url: '../../api/application_recovery/get_assigned_claims.php',
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
	$('#court_case_number').val('');
	$('#debtor_name').val('');
	$('#service_date').val('');
	$('#confirmation_date').val('');
	$('#claim_range').val('');
}

// 폼 데이터 채우기
function fillFormData(data) {
	$('#court_case_number').val(data.court_case_number || '');
	$('#debtor_name').val(data.debtor_name || '');
	$('#service_date').val(data.service_date || '');
	$('#confirmation_date').val(data.confirmation_date || '');
	$('#claim_range').val(data.claim_range || '');
	
	// 텍스트 영역 높이 조정
	$('textarea').each(function() {
		this.style.height = 'auto';
		this.style.height = (this.scrollHeight) + 'px';
	});
}

// 폼 저장
function saveForm() {
	// 데이터 수집
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		court_case_number: $('#court_case_number').val(),
		debtor_name: $('#debtor_name').val(),
		service_date: $('#service_date').val(),
		confirmation_date: $('#confirmation_date').val(),
		claim_range: $('#claim_range').val()
	};
	
	// claim_no가 있으면 추가
	const claimNo = $('#claimNo').val();
	if (claimNo) {
		formData.claim_no = claimNo;
	}
	
	$.ajax({
		url: '../../api/application_recovery/save_assigned_claim.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'assignedClaimSaved', 
						creditorCount: current_creditor_count,
						hasData: true
					}, '*');
					
					// claim_no 업데이트
					if (result.claim_no) {
						$('#claimNo').val(result.claim_no);
					}
				} else {
					console.error('저장 실패 응답:', result);
					alert(result.message || '저장 중 오류가 발생했습니다.');
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
		url: '../../api/application_recovery/delete_assigned_claim.php',
		method: 'POST',
		data: { claim_no: claimNo },
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '삭제되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'assignedClaimDeleted',
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