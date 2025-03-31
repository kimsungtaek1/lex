$(document).ready(function() {
	// 저장된 데이터 불러오기
	loadDebtData();
	
	// 이벤트 리스너 등록
	registerEventListeners();
});

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

// 저장된 데이터 불러오기
function loadDebtData() {
	// 사건 및 채권자 번호가 없으면 데이터를 조회할 수 없음
	if (!currentCaseNo || !current_creditor_count) {
		console.error('필수 파라미터 누락: case_no 또는 creditor_count');
		return;
	}
	
	$.ajax({
		url: '../../api/application_recovery/get_other_debts.php',
		method: 'GET',
		data: {
			case_no: currentCaseNo,
			creditor_count: current_creditor_count
		},
		success: function(response) {
			try {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					console.log(data.data);
					// 첫 번째 데이터로 폼 채우기
					fillFormData(data.data[0]);
					// debt_no 필드에 값 설정
					$('#debtNo').val(data.data[0].debt_no);
				} else {
					console.log('없음');
					clearForm(); // 데이터가 없으면 폼 초기화
					setDefaultDescription(); // 기본 설명 텍스트 설정
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
	$('#hasMortgage').prop('checked', false);
	$('#debtDescription').val('');
}

// 기본 설명 텍스트 설정
function setDefaultDescription() {
	if (current_creditor_count && principalAmount) {
		const formattedPrincipal = Number(principalAmount).toLocaleString('ko-KR');
		const descriptionText = `채권번호 ${current_creditor_count} : 해당 채권사에 대한 원금 ${formattedPrincipal}원의 채무는 연대보증 채무이며 채권원인(으)로 발생한 채무입니다.`;
		$('#debtDescription').val(descriptionText);
	}
}

// 폼 데이터 채우기
function fillFormData(data) {
	$('#hasMortgage').prop('checked', data.has_mortgage == 1);
	
	// 저장된 내용이 있으면 그대로 표시, 없으면 기본 텍스트 설정
	if (data.debt_description) {
		$('#debtDescription').val(data.debt_description);
	} else {
		setDefaultDescription();
	}
}

function saveForm() {
	// 데이터 수집
	const formData = {
		case_no: currentCaseNo,
		creditor_count: current_creditor_count,
		has_mortgage: $('#hasMortgage').prop('checked') ? 1 : 0,
		debt_description: $('#debtDescription').val()
	};
	
	// debt_no가 있으면 추가
	const debtNo = $('#debtNo').val();
	if (debtNo) {
		formData.debt_no = debtNo;
	}
	
	$.ajax({
		url: '../../api/application_recovery/save_other_debt.php',
		method: 'POST',
		data: formData,
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '저장되었습니다.');
					
					// 다른 채권 유형 데이터 삭제 API 호출
					$.ajax({
						url: '../../api/application_recovery/clear_other_claims.php',
						method: 'POST',
						data: {
							case_no: currentCaseNo,
							creditor_count: current_creditor_count,
							exclude_type: 'other_debt'
						},
						success: function(clearResponse) {
							// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
							window.opener.postMessage({
								type: 'otherClaimSaved', 
								creditorCount: current_creditor_count,
								hasData: true,
								clearOthers: true // 다른 채권 버튼 색상을 원래대로 되돌리기 위한 플래그
							}, '*');
							
							// claim_no 업데이트
							if (result.claim_no) {
								$('#claimNo').val(result.claim_no);
							}
						},
						error: function() {
							// 오류가 발생해도 기본 메시지는 보냄
							window.opener.postMessage({
								type: 'otherClaimSaved', 
								creditorCount: current_creditor_count,
								hasData: true,
								clearOthers: true // 오류 발생해도 다른 채권 버튼 색상을 원래대로 되돌리기
							}, '*');
						}
					});
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
	const debtNo = $('#debtNo').val();
	if (!debtNo) {
		alert('삭제할 항목이 없습니다.');
		return;
	}
	
	$.ajax({
		url: '../../api/application_recovery/delete_other_debt.php',
		method: 'POST',
		data: { debt_no: debtNo },
		success: function(response) {
			try {
				const result = typeof response === 'string' ? JSON.parse(response) : response;
				if (result.success) {
					alert(result.message || '삭제되었습니다.');
					
					// 부모 창에 메시지 전달 - 버튼 색상 변경을 위한 정보 포함
					window.opener.postMessage({
						type: 'otherDebtDeleted',
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