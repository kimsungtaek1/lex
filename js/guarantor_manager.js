// 전화번호 포맷팅 함수
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

// 팩스번호 포맷팅 함수 (application_bankruptcy_creditor.js와 동일)
function formatFaxNumber(value) {
    if (!value) return '';
    value = value.replace(/[^\d]/g, '');
    const areaCode = value.substring(0, 2);
    if (areaCode === '02') {
        if (value.length <= 2) {
            return value;
        } else if (value.length <= 6) {
            return value.slice(0, 2) + '-' + value.slice(2);
        } else {
            return value.slice(0, 2) + '-' + value.slice(2, 6) + '-' + value.slice(6, 10);
        }
    } else {
        if (value.length <= 3) {
            return value;
        } else if (value.length <= 7) {
            return value.slice(0, 3) + '-' + value.slice(3);
        } else {
            return value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
        }
    }
}

$(document).ready(function() {
	let selectedGuarantorNo = null;
	
	// 메시지 리스너는 최초 1회만 등록 (팝업에서 postMessage 받을 때 항상 동작)
	window.removeEventListener('message', window._financialInstitutionListener);
	window._financialInstitutionListener = function(event) {
		if (event.data.type === 'financialInstitutionSelectedForGuarantor') {
			fillFinancialInstitution(event.data.institution, currentCreditorCount);
		}
	};
	window.addEventListener('message', window._financialInstitutionListener);

	// 초기화
	loadGuarantors();
	setNextGuarantorNo(); // 페이지 진입 시 보증인번호 자동 세팅
	
	// 숫자 입력 필드에 자동 콤마 추가
	$("#guarantee_amount" + currentCreditorCount).on('input', function() {
		formatNumber($(this));
	});
	$("#difference_interest" + currentCreditorCount).on('input', function() {
		formatNumber($(this));
	});
	
	// 전화번호 입력 필드 이벤트 처리
    $(document).on('input', '[id^=guarantor_phone]', function(e) {
        let input = $(this);
        let value = input.val();
        let formatted = formatPhoneNumber(value);
        let cursorPos = this.selectionStart;
        let beforeLength = value.length;
        input.val(formatted);
        if (formatted.length > beforeLength) {
            cursorPos++;
        }
        this.setSelectionRange(cursorPos, cursorPos);
    });
    // 팩스번호 입력 필드 이벤트 처리
    $(document).on('input', '[id^=guarantor_fax]', function(e) {
        let input = $(this);
        let value = input.val();
        let formatted = formatFaxNumber(value);
        let cursorPos = this.selectionStart;
        let beforeLength = value.length;
        input.val(formatted);
        if (formatted.length > beforeLength) {
            cursorPos++;
        }
        this.setSelectionRange(cursorPos, cursorPos);
    });
	
	// 저장 버튼 클릭
	$("#saveButton").on('click', function() {
		saveGuarantor();
	});
	
	// 삭제 버튼 클릭
	$("#deleteButton").on('click', function() {
		deleteGuarantor();
	});
	
	// 닫기 버튼 클릭
	$("#closeButton").on('click', function() {
		window.close();
	});
	
	// 주소 검색 버튼 클릭
	$("#addressSearchBtn").on('click', function() {
		searchAddress();
	});
	
	// 보증인 행 클릭 이벤트
	$(document).on('click', '.guarantor-row', function() {
		const guarantorNo = $(this).data('guarantor-no');
		selectGuarantor(guarantorNo);
	});
	
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
				$("#guarantor_address" + currentCreditorCount).val(data.address);
				
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
	
	// 금융기관 검색 버튼 클릭 이벤트
    $(document).on('click', '.btn-financial-institution', function(e) {
        e.preventDefault();
        const count = $(this).data('count');
        const width = 1200;
        const height = 750;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        window.open(
            `search_financial_institution.php?source=guarantor&count=${count}`,
            'SearchFinancialInstitution',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
        );
    });
	
	// 보증인 목록 로드
	function loadGuarantors() {
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'GET',
			data: {
				action: 'list',
				case_no: currentCaseNo,
				creditor_count: currentCreditorCount
			},
			success: function(response) {
				if (response.success) {
					renderGuarantorTable(response.data);
				} else {
					console.error('보증인 데이터 로드 실패:', response.message);
					alert('보증인 정보를 불러오는데 실패했습니다.');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 보증인 테이블 렌더링
	function renderGuarantorTable(guarantors) {
		const $tableBody = $('#guarantorTableBody');
		$tableBody.empty();
		
		if (guarantors.length === 0) {
			$tableBody.append('<div class="no-data">보증인 데이터가 없습니다</div>');
			return;
		}

		guarantors.forEach(function(guarantor, index) {
			const guarantorNoFormatted = `${currentCreditorCount}-${index + 1}`;
			const $row = $(`
				<div class="table-row guarantor-row" data-guarantor-no="${guarantor.guarantor_no}">
					<div class="col">${guarantorNoFormatted}</div>
					<div class="col">${guarantor.guarantor_name || ''}</div>
					<div class="col">${guarantor.guarantor_address || ''}</div>
					<div class="col">${guarantor.dispute_reason || ''}</div>
					<div class="col">
						<button class="btn btn-sm btn-warning edit-guarantor" data-guarantor-no="${guarantor.guarantor_no}">수정</button>
						<button class="btn btn-sm btn-danger delete-guarantor" data-guarantor-no="${guarantor.guarantor_no}">삭제</button>
					</div>
				</div>
			`);
			$tableBody.append($row);
		});

		// 이벤트 핸들러 바인딩
		$('.edit-guarantor').off('click').on('click', function() {
			const guarantorNo = $(this).data('guarantor-no');
			selectGuarantor(guarantorNo);
		});
		$('.delete-guarantor').off('click').on('click', function() {
			const guarantorNo = $(this).data('guarantor-no');
			if (confirm('정말 삭제하시겠습니까?')) {
				deleteGuarantor(guarantorNo);
			}
		});
	}
	
	// 보증인 행 선택
	function selectGuarantor(guarantorNo) {
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'GET',
			data: {
				action: 'list',
				case_no: currentCaseNo,
				creditor_count: currentCreditorCount,
				guarantor_no: guarantorNo
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					const guarantor = response.data[0];
					console.log('보증인 선택', guarantor);
					fillGuarantorForm(guarantor);
					selectedGuarantorNo = guarantor.guarantor_no;
					// 행 선택 스타일 변경
					$('.guarantor-row').removeClass('selected');
					$(`.guarantor-row[data-guarantor-no="${guarantorNo}"]`).addClass('selected');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 보증인 폼 채우기
	function fillGuarantorForm(guarantor) {
		$('#guarantor_no' + currentCreditorCount).val(currentCreditorCount+'-'+ (guarantor.guarantor_no || ''));
		$('#guarantor_name' + currentCreditorCount).val(guarantor.guarantor_name || '');
		$('#guarantor_address' + currentCreditorCount).val(guarantor.guarantor_address || '');
		$('#guarantee_amount' + currentCreditorCount).val(numberWithCommas(guarantor.guarantee_amount) || '');
		$('#guarantee_date' + currentCreditorCount).val(guarantor.guarantee_date || '');
		$('#dispute_reason' + currentCreditorCount).val(guarantor.dispute_reason || '');
		$('#dispute_reason_content' + currentCreditorCount).val(guarantor.dispute_reason_content || '');
		$('#difference_interest' + currentCreditorCount).val(numberWithCommas(guarantor.difference_interest) || '');
		$('#guarantor_phone' + currentCreditorCount).val(guarantor.guarantor_phone || '');
		$('#guarantor_fax' + currentCreditorCount).val(guarantor.guarantor_fax || '');
	}
	
	// 보증인 저장
	function saveGuarantor() {
		const guarantorName = $('#guarantor_name' + currentCreditorCount).val();
		if (!guarantorName) {
			alert('보증인명을 입력해주세요.');
			$('#guarantor_name' + currentCreditorCount).focus();
			return;
		}
        // 필수값 추가 체크
        const guaranteeDate = $('#guarantee_date' + currentCreditorCount).val();
        if (!guaranteeDate) {
            alert('차용 또는 구입일자를 입력해주세요.');
            $('#guarantee_date' + currentCreditorCount).focus();
            return;
        }
        const guaranteeAmount = $('#guarantee_amount' + currentCreditorCount).val();
        if (!guaranteeAmount) {
            alert('잔존원금(대위변제금액)을 입력해주세요.');
            $('#guarantee_amount' + currentCreditorCount).focus();
            return;
        }
        const differenceInterest = $('#difference_interest' + currentCreditorCount).val();
        if (!differenceInterest) {
            alert('잔존이자ㆍ지연손해금을 입력해주세요.');
            $('#difference_interest' + currentCreditorCount).focus();
            return;
        }
		const formData = {
			action: 'save',
			case_no: currentCaseNo,
			creditor_count: currentCreditorCount,
			guarantor_no: $('#guarantor_no' + currentCreditorCount).val(),
			guarantor_name: guarantorName,
			guarantor_address: $('#guarantor_address' + currentCreditorCount).val(),
			guarantee_amount: $('#guarantee_amount' + currentCreditorCount).val().replace(/,/g, ''),
			dispute_reason: $('#dispute_reason' + currentCreditorCount).val(),
			dispute_reason_content: $('#dispute_reason_content' + currentCreditorCount).val(),
			difference_interest: $('#difference_interest' + currentCreditorCount).val().replace(/,/g, ''),
			guarantor_phone: $('#guarantor_phone' + currentCreditorCount).val(),
			guarantor_fax: $('#guarantor_fax' + currentCreditorCount).val(),
			guarantee_date: $('#guarantee_date' + currentCreditorCount).val()
		};
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					alert('보증인 정보가 저장되었습니다.');
					loadGuarantors();
					clearForm();
					updateOpenerGuarantorCount();
					setNextGuarantorNo();
				} else {
					console.log(response.message);
					alert(response.message || '저장 중 오류가 발생했습니다.');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 보증인 삭제
	function deleteGuarantor(guarantorNo) {
		if (!guarantorNo) {
			alert('삭제할 보증인을 선택해주세요.');
			return;
		}
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'POST',
			data: {
				action: 'delete',
				guarantor_no: guarantorNo
			},
			success: function(response) {
				if (response.success) {
					alert('보증인이 삭제되었습니다.');
					loadGuarantors();
					clearForm();
					updateOpenerGuarantorCount();
				} else {
					alert(response.message || '삭제 중 오류가 발생했습니다.');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 부모 창의 보증인 수 업데이트
	function updateOpenerGuarantorCount() {
		if (window.opener && !window.opener.closed) {
			$.ajax({
				url: '../../api/application_bankruptcy/guarantor_api.php',
				type: 'GET',
				data: {
					action: 'count',
					case_no: currentCaseNo,
					creditor_count: currentCreditorCount
				},
				success: function(response) {
					if (response.success) {
						window.opener.$('#guarantorCount_b' + currentCreditorCount).text(response.count);
					}
				}
			});
		}
	}
	
	// 폼 초기화
	function clearForm() {
		$('#guarantor_name' + currentCreditorCount).val('');
		$('#guarantor_address' + currentCreditorCount).val('');
		$('#guarantee_amount' + currentCreditorCount).val('');
		$('#guarantee_date' + currentCreditorCount).val('');
		$('#dispute_reason' + currentCreditorCount).val('금원차용');
		$('#dispute_reason_content' + currentCreditorCount).val('');
		$('#difference_interest' + currentCreditorCount).val('');
		$('#guarantor_phone' + currentCreditorCount).val('');
		$('#guarantor_fax' + currentCreditorCount).val('');
		selectedGuarantorNo = null;
		setNextGuarantorNo();
	}
	
	// 숫자 포맷팅 함수
	function formatNumber(input) {
		let value = input.val();
		if (!value) return;
		
		// 숫자만 남기고 모두 제거
		value = value.replace(/[^\d]/g, '');
		
		if (value) {
			// 천단위 콤마 추가
			value = numberWithCommas(value);
			input.val(value);
		}
	}
	
	// 천단위 콤마 추가 함수
	function numberWithCommas(x) {
		if (!x) return "0";
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}
	
	// 보증인번호(guarantor_no) 자동 세팅 함수
	function setNextGuarantorNo() {
		var $guarantorNoInput = $('#guarantor_no' + currentCreditorCount);
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'GET',
			data: {
				action: 'count',
				case_no: currentCaseNo,
				creditor_count: currentCreditorCount
			},
			success: function(response) {
				if (response.success) {
					var count = parseInt(response.count, 10);
					var nextGuarantorNo;
					if (count === 0) {
						nextGuarantorNo = currentCreditorCount + '-1';
					} else {
						nextGuarantorNo = currentCreditorCount + '-' + (count + 1);
					}
					$guarantorNoInput.val(nextGuarantorNo);
				} else {
					$guarantorNoInput.val(currentCreditorCount + '-1');
				}
			},
			error: function() {
				$guarantorNoInput.val(currentCreditorCount + '-1');
			}
		});
	}
	
	// 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#guarantor_name${count}`).val(institution.name);
        $(`#guarantor_address${count}`).val(institution.address);
        $(`#guarantor_phone${count}`).val(formatPhoneNumber(institution.phone));
        $(`#guarantor_fax${count}`).val(institution.fax);
    }
});