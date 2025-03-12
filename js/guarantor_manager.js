$(document).ready(function() {
	let selectedGuarantorNo = null;
	
	// 초기화
	loadGuarantors();
	
	// 숫자 입력 필드에 자동 콤마 추가
	$("#guarantee_amount").on('input', function() {
		formatNumber($(this));
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
		const debtNo = $(this).data('debt-no');
		selectGuarantor(debtNo);
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
				$("#guarantor_address").val(data.address);
				
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

        const searchWindow = window.open(
            'search_financial_institution.php',
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
                fillFinancialInstitution(event.data.institution, count);
                searchWindow.close();
            }
        });
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
			const $row = $(`
				<div class="table-row guarantor-row" data-debt-no="${guarantor.debt_no}">
					<div class="col">${index + 1}</div>
					<div class="col">${guarantor.guarantor_name || ''}</div>
					<div class="col">${guarantor.guarantor_address || ''}</div>
					<div class="col">${numberWithCommas(guarantor.guarantee_amount) || '0'}</div>
					<div class="col">${guarantor.guarantee_date || ''}</div>
				</div>
			`);
			
			$tableBody.append($row);
		});
	}
	
	// 보증인 행 선택
	function selectGuarantor(debtNo) {
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'GET',
			data: {
				action: 'list',
				case_no: currentCaseNo,
				creditor_count: currentCreditorCount,
				debt_no: debtNo
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					const guarantor = response.data[0];
					fillGuarantorForm(guarantor);
					selectedGuarantorNo = guarantor.debt_no;
					
					// 행 선택 스타일 변경
					$('.guarantor-row').removeClass('selected');
					$(`.guarantor-row[data-debt-no="${debtNo}"]`).addClass('selected');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 보증인 폼 채우기
	function fillGuarantorForm(guarantor) {
		$('#guarantor_no').val(guarantor.debt_no);
		$('#guarantor_name').val(guarantor.guarantor_name);
		$('#guarantor_address').val(guarantor.guarantor_address);
		$('#guarantee_amount').val(numberWithCommas(guarantor.guarantee_amount));
		$('#guarantee_date').val(guarantor.guarantee_date);
	}
	
	// 보증인 저장
	function saveGuarantor() {
		const guarantorName = $('#guarantor_name').val();
		
		if (!guarantorName) {
			alert('보증인명을 입력해주세요.');
			$('#guarantor_name').focus();
			return;
		}
		
		const formData = {
			action: 'save',
			case_no: currentCaseNo,
			creditor_count: currentCreditorCount,
			debt_no: $('#guarantor_no').val(),
			guarantor_name: guarantorName,
			guarantor_address: $('#guarantor_address').val(),
			guarantee_amount: $('#guarantee_amount').val().replace(/,/g, ''),
			guarantee_date: $('#guarantee_date').val()
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
				} else {
					alert(response.message || '저장 중 오류가 발생했습니다.');
				}
			},
			error: function() {
				alert('서버 통신 중 오류가 발생했습니다.');
			}
		});
	}
	
	// 보증인 삭제
	function deleteGuarantor() {
		const debtNo = $('#guarantor_no').val();
		
		if (!debtNo) {
			alert('삭제할 보증인을 선택해주세요.');
			return;
		}
		
		if (!confirm('선택한 보증인을 삭제하시겠습니까?')) {
			return;
		}
		
		$.ajax({
			url: '../../api/application_bankruptcy/guarantor_api.php',
			type: 'POST',
			data: {
				action: 'delete',
				debt_no: debtNo
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
		$('#guarantor_no').val('');
		$('#guarantor_name').val('');
		$('#guarantor_address').val('');
		$('#guarantee_amount').val('');
		$('#guarantee_date').val('');
		selectedGuarantorNo = null;
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
	
	// 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#financialInstitution${count}`).val(institution.name);
        $(`#address${count}`).val(institution.address);
        $(`#phone${count}`).val(formatPhoneNumber(institution.phone));
        $(`#fax${count}`).val(institution.fax);
    }
});