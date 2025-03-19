// 전역 변수
let cases = [];
let managers = [];
const itemsPerPage = 15;
let currentPage = 1;
let filteredCases = [];
let sortField = 'case_number';
let sortDirection = 'desc';
let isEditing = false;
let currentCaseNo = null;

$(document).ready(function() {
	// 전역 유틸리티 함수
	window.showLoading = function() {
		if (!$('#loadingIndicator').length) {
			$('body').append('<div id="loadingIndicator"><div>로딩중...</div></div>');
		}
		$('#loadingIndicator').show();
	};

	window.hideLoading = function() {
		$('#loadingIndicator').hide();
	};

	window.updateProhibitionCount = function() {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/get_prohibition_count.php',
			type: 'GET',
			data: { case_no: currentCaseNo },
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					$('#prohibition-count').text(`[등록수 : ${response.count}]`);
				}
			},
			error: function(xhr, status, error) {
				console.error('금지명령서 개수 조회 실패:', error);
			}
		});
	};

	window.updateStayOrderCount = function() {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/get_stay_order_count.php',
			type: 'GET',
			data: { case_no: currentCaseNo },
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					$('#stay-count').text(`[등록수 : ${response.count}]`);
				}
			},
			error: function(xhr, status, error) {
				console.error('중지명령서 개수 조회 실패:', error);
			}
		});
	};

	// 사건 목록 관련 전역 함수
	window.loadCases = function() {
		window.showLoading();
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/get_bankruptcy_cases.php',
			method: 'GET',
			cache: false,
			success: function(response) {
				if (response.success) {
					cases = response.data;
					filteredCases = [...cases];
					window.renderCases();
				} else {
					console.error('사건목록 로드 실패:', response.message);
					alert('사건목록을 불러오는데 실패했습니다: ' + response.message);
					window.resetCaseList();
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax 에러:', error);
				alert('서버 통신 중 오류가 발생했습니다: ' + error);
				window.resetCaseList();
			},
			complete: function() {
				window.hideLoading();
			}
		});
	};

	window.resetCaseList = function() {
		$('#caseList').empty();
		$('.page-numbers').empty();
		currentPage = 1;
		cases = [];
		filteredCases = [];
	};

	window.renderCases = function() {
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = filteredCases.slice(startIndex, endIndex);
		
		const tbody = $('#caseList');
		tbody.empty();
		
		if (pageData.length === 0) {
			tbody.append('<tr><td colspan="4" style="text-align:center;">데이터가 없습니다.</td></tr>');
			return;
		}
		
		pageData.forEach(function(item, index) {
			const row = `
				<tr data-id="${item.case_no}" class="${item.case_no === currentCaseNo ? 'active' : ''}">
					<td>${startIndex + index + 1}</td>
					<td>${item.name || ''}</td>
					<td>${item.case_number || ''}</td>
					<td></td>
				</tr>
			`;
			tbody.append(row);
		});

		window.renderPagination(filteredCases.length);
	};

	window.renderPagination = function(totalItems) {
		const totalPages = Math.ceil(totalItems / itemsPerPage);
		const $pageNumbers = $('.page-numbers');
		$pageNumbers.empty();
		
		let startPage = Math.max(1, currentPage - 2);
		let endPage = Math.min(totalPages, startPage + 4);
		
		if (endPage - startPage < 4) {
			startPage = Math.max(1, endPage - 4);
		}

		for (let i = startPage; i <= endPage; i++) {
			const $pageBtn = $(`<button type="button" class="page-btn ${i === currentPage ? 'active' : ''}">${i}</button>`);
			$pageBtn.click(() => {
				currentPage = i;
				window.renderCases();
			});
			$pageNumbers.append($pageBtn);
		}

		$('.prev-btn').prop('disabled', currentPage === 1);
		$('.next-btn').prop('disabled', currentPage === totalPages || totalPages === 0);
	};

	window.loadCaseData = function(caseNo) {
		if (!caseNo) return;
		$.ajax({
			url: '/adm/api/application_bankruptcy/get_applicant_data.php',
			type: 'GET',
			data: { case_no: caseNo },
			dataType: 'json',
			beforeSend: function() {
				window.showLoading();
			},
			success: function(response) {
				if (response.success) {
					currentCaseNo = caseNo;
					try {
						window.loadBankruptcyApplicantData(caseNo);
					} catch (innerErr) {
						console.error("loadBankruptcyApplicantData 호출 중 오류:", innerErr);
					}
					window.updateProhibitionCount();
					window.updateStayOrderCount();
				} else {
					console.error('데이터 로드 중 오류가 발생했습니다: ' + response.message);
					alert("권한이 없습니다: " + response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
				console.log("응답 텍스트:", xhr.responseText); // 디버깅용
				alert('서버 통신 중 오류가 발생했습니다.');
			},
			complete: function() {
				window.hideLoading();
			}
		});
	};

	window.loadBankruptcyApplicantData = function(caseNo) {
		if (!caseNo) return;
		
		$.ajax({
			url: '/adm/api/application_bankruptcy/get_applicant_data.php',
			type: 'GET',
			data: { case_no: caseNo },
			dataType: 'json',
			beforeSend: function() {
				window.showLoading();
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					// 기본 정보 채우기
					$('#name_b').val(data.name || '');
					$('#phone_b').val(data.phone || '');
					$('#residentNumber_b').val(data.resident_number || '');
					$('#registeredAddress_b').val(data.registered_address || '');
					$('#nowAddress_b').val(data.now_address || '');
					$('#baseAddress_b').val(data.base_address || '');
					$('#workPhone_b').val(data.work_phone || '');
					$('#email_b').val(data.email || '');
					
					// 날짜 관련 정보
					if (data.application_date) {
						$('#applicationDate_b').val(data.application_date);
					}
					
					// 법원 및 기타 정보
					$('#court_b').val(data.court_name || '');
					$('#caseNumber_b').val(data.case_number || '');
					$('#creditorCount_b').val(data.creditor_count || '0');
					
					// 신청 관련 체크박스
					$('#stayOrderApply_b').prop('checked', data.stay_order_apply == 1);
					$('#exemptionApply_b').prop('checked', data.exemption_apply == 1);
					
					// 지원 관련 정보
					$('#supportOrg_b').val(data.support_org || '');
					$('#supportDetails_b').val(data.support_details || '');
					
					// 주소 같은지 체크하고 체크박스 설정
					$('#sameAsRegistered_b').prop('checked', data.now_address === data.registered_address);
					
					// 체크박스 상태에 따른 설정 적용
					initSameAddressCheckbox();
				} else {
					console.error('신청인 데이터 로드 실패:', response.message);
					alert('신청인 데이터 로드 실패: ' + response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax 에러:', error);
				console.log("응답 텍스트:", xhr.responseText); // 디버깅용
				alert('서버 통신 중 오류가 발생했습니다.');
			},
			complete: function() {
				window.hideLoading();
			}
		});
	};

	// 변제개시일 업데이트 함수
	window.updateRepaymentStartDate = function() {
		const isUnspecified = $('#unspecifiedDate').is(':checked');
		const applicationDate = $('#applicationDate').val();
		
		if (isUnspecified) {
			$('#repaymentStartDate').prop('readonly', false);
			if (!$('#repaymentStartDate').val()) {
				$('#repaymentStartDate').val('');
			}
		} else {
			$('#repaymentStartDate').prop('readonly', true);
			if (applicationDate) {
				const startDate = new Date(applicationDate);
				startDate.setDate(startDate.getDate() + 89);
				const formattedDate = startDate.toISOString().split('T')[0];
				$('#repaymentStartDate').val(formattedDate);
			}
		}
	};

	// 신청인 정보 저장 버튼 이벤트
	$('#save_applicant_b').on('click', function(e) {
		e.preventDefault();
		
		if (!$('#name_b').val().trim()) {
			alert('성명을 입력해주세요.');
			$('#name_b').focus();
			return;
		}
		
		const formData = {
			case_no: currentCaseNo || '',
			name_b: $('#name_b').val(),
			phone_b: $('#phone_b').val(),
			residentNumber_b: $('#residentNumber_b').val(),
			registeredAddress_b: $('#registeredAddress_b').val(),
			nowAddress_b: $('#nowAddress_b').val(),
			baseAddress_b: $('#baseAddress_b').val(),
			workPhone_b: $('#workPhone_b').val(),
			email_b: $('#email_b').val(),
			applicationDate_b: $('#applicationDate_b').val(),
			court_b: $('#court_b').val(),
			caseNumber_b: $('#caseNumber_b').val(),
			creditorCount_b: $('#creditorCount_b').val() || 0,
			stayOrderApply_b: $('#stayOrderApply_b').is(':checked') ? 1 : 0,
			exemptionApply_b: $('#exemptionApply_b').is(':checked') ? 1 : 0,
			supportOrg_b: $('#supportOrg_b').val(),
			supportDetails_b: $('#supportDetails_b').val()
		};

		$.ajax({
			url: '/adm/api/application_bankruptcy/save_applicant_data.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			beforeSend: function() {
				window.showLoading();
			},
			success: function(response) {
				if (response.success) {
					alert('저장되었습니다.');
					if (response.case_no) {
						currentCaseNo = response.case_no;
					}
					window.loadCases();
					window.loadBankruptcyApplicantData(currentCaseNo);
				} else {
					alert(response.message || '저장 중 오류가 발생했습니다.');
				}
			},
			error: function(xhr, status, error) {
				console.error('저장 중 오류 발생:', error);
				console.log("응답 텍스트:", xhr.responseText); // 디버깅용
				alert('서버 통신 중 오류가 발생했습니다.');
			},
			complete: function() {
				window.hideLoading();
			}
		});
	});

	// 소득유형 전용 이벤트 핸들러 
	$(document).on('change', 'input[name="incomeType"]', function() {
		const selectedValue = $(this).val();
		const salaryType = $('#salaryType');
		const businessType = $('#businessType');
		const salaryLabel = $('label[for="salaryType"]');
		const businessLabel = $('label[for="businessType"]');
		
		// data-selected 값 설정
		salaryType.attr('data-selected', selectedValue === '0' ? 'true' : 'false');
		businessType.attr('data-selected', selectedValue === '1' ? 'true' : 'false');
		salaryLabel.attr('data-selected', selectedValue === '0' ? 'true' : 'false');  
		businessLabel.attr('data-selected', selectedValue === '1' ? 'true' : 'false');
		
		// hidden input에 선택값 저장
		$('#incomeTypeValue').val(selectedValue);
		
		// 소득유형에 따라 필드명 변경
		if (selectedValue === '1') {
			$('#applicantSection .form-title span').each(function() {
				const text = $(this).text();
				if (text === '직장주소') $(this).text('영업장주소');
				if (text === '직장명') $(this).text('상호명');
				if (text === '직위') $(this).text('업종');
				if (text === '근무기간') $(this).text('종사경력');
			});
		} else {
			$('#applicantSection .form-title span').each(function() {
				const text = $(this).text();
				if (text === '영업장주소') $(this).text('직장주소');
				if (text === '상호명') $(this).text('직장명');
				if (text === '업종') $(this).text('직위');
				if (text === '종사경력') $(this).text('근무기간');
			});
		}
	});

	// 일반 라디오 버튼용 이벤트 핸들러 
	$(document).on('change', 'input[type="radio"]', function() {
		// incomeType은 별도 처리하므로 제외
		if($(this).attr('name') === 'incomeType') return;
		
		const name = $(this).attr('name');
		const selectedValue = $(this).val();
		const $radioGroup = $(`input[name="${name}"]`);
		
		// 같은 name을 가진 모든 라디오 버튼에 data-selected 설정
		$radioGroup.each(function() {
			const isSelected = $(this).val() === selectedValue;
			$(this).attr('data-selected', isSelected.toString());
			$(`label[for="${this.id}"]`).attr('data-selected', isSelected.toString());
		});
	});

	$(document).on('change','input[type="checkbox"]', function(){
		const isChecked = $(this).is(':checked');
		$(this).attr('data-selected', isChecked.toString());
		$(`label[for="${this.id}"]`).attr('data-selected', isChecked.toString());
	});

	// 초기화 함수
	function initializeView() {
		$('.section-content').hide();
		$('#caseListSection').show();
		$('.doc-tab').removeClass('active');
		$('.doc-tab[data-type="case-list"]').addClass('active');
	}

	function initializeData() {
		window.showLoading();
		Promise.all([
			$.ajax({
				url: '/adm/api/manager/get_managers.php',
				method: 'GET',
				success: function(response) {
					if (response.success) {
						managers = response.data;
					}
				}
			})
		]).then(() => {
			window.loadCases();
		}).catch((error) => {
			console.error('데이터 초기화 실패:', error);
			alert('데이터 초기화에 실패했습니다.');
		}).finally(() => {
			window.hideLoading();
		});
	}

	function switchTab(type) {
		$('.doc-tab').removeClass('active');
		$(`.doc-tab[data-type="${type}"]`).addClass('active');
		
		$('.section-content').hide();
		$(`#${type}Section`).show();
		
		// 채권자 탭을 클릭했을 때 데이터 로드
		if (type === 'creditors') {
			window.loadCreditors(currentCaseNo);
		}
		
		// AssetManager 초기화 또는 재로드
        if (!window.assetManager) {
            window.assetManager = new AssetManager();
        } else {
            window.assetManager.loadAllAssets();
        }

		if (type === 'living-status') {
			console.log('생활상황 탭 로드, currentCaseNo:', currentCaseNo);
			window.currentCaseNo = currentCaseNo; // 전역 변수에 명시적 할당
			
			if (window.livingStatusManager) {
				window.livingStatusManager.loadData();
			}
		}
	}


	// 초기화
	initializeView();
	initializeData();
	initSameAddressCheckbox();

	function initSameAddressCheckbox() {
		if ($('#sameAsRegistered_b').is(':checked')) {
			$('#nowAddress_b').prop('readonly', true);
			$('.btn-search[data-target="nowAddress_b"]').prop('disabled', true);
		} else {
			$('#nowAddress_b').prop('readonly', false);
			$('.btn-search[data-target="nowAddress_b"]').prop('disabled', false);
		}
	}

	// 탭 클릭 이벤트
	$('.doc-tab').click(function(e) {
		e.preventDefault();
		const type = $(this).data('type');
		
		if (!currentCaseNo && type !== 'case-list') {
			alert('사건을 먼저 선택해주세요.');
			initializeView();
			return false;
		}

		const currentTab = $('.doc-tab.active').data('type');
		
		if (currentTab === type) {
			return false;
		}
		
		if (type === 'case-list') {
			window.resetCaseList();
			$('.doc-tab').removeClass('active');
			$(`.doc-tab[data-type="${type}"]`).addClass('active');
			$('.section-content').hide();
			$('#caseListSection').show();
			window.loadCases();
		} else if (type === 'creditor' || type === 'creditors') {
			// 채권자 탭으로 전환 시 전역 변수 설정
			window.currentCaseNo = currentCaseNo;
			
			// 채권자 초기화 함수 호출
			if (typeof window.loadCreditorSettings === 'function') {
				window.loadCreditorSettings();
			}
			
			switchTab(type);
		} else {
			switchTab(type);
		}
	});

	// 사건 선택 이벤트
	$(document).on('click', '#caseList tr', function(e) {
		const caseNo = $(this).data('id');
		if (!caseNo) return;
		
		currentCaseNo = caseNo;
		window.currentCaseNo = caseNo; // window 객체에도 설정
		
		$('#caseList tr').removeClass('active');
		$(this).addClass('active');
		
		switchTab('applicant');
		window.loadCaseData(caseNo);
		window.loadBankruptcyApplicantData(caseNo);
		
		// AssetManager 초기화 또는 재로드
		if (window.assetManager) {
			window.assetManager.loadAllAssets();
		}
		
		if (window.incomeExpenditureManager) {
			window.incomeExpenditureManager.loadIncomeData();
		}
		
		if (window.bankruptcyStatementManager) {
			window.bankruptcyStatementManager.loadStatements();
		}
	});

	// 소득유형 초기값 설정
	const initialIncomeType = $('#incomeTypeValue').val() || '0';
	
	// 초기값에 해당하는 라디오 버튼 체크 및 이벤트 트리거
	$(`input[name="incomeType"][value="${initialIncomeType}"]`)
		.prop('checked', true)
		.trigger('change');
		
	// 각 라디오 버튼 별 data-selected 초기화
	$('input[name="incomeType"]').each(function() {
		const isSelected = $(this).val() === initialIncomeType;
		$(this).attr('data-selected', isSelected.toString());
		$(`label[for="${this.id}"]`).attr('data-selected', isSelected.toString());
	});

	// 검색 기능
	$('#searchInput').on('keyup', function() {
		const searchTerm = $(this).val().toLowerCase();
		
		filteredCases = cases.filter(item => 
			(item.case_number || '').toLowerCase().includes(searchTerm) ||
			(item.name || '').toLowerCase().includes(searchTerm)
		);
		
		currentPage = 1;
		window.renderCases();
	});

	// 페이지네이션 버튼 이벤트
	$('.prev-btn').click(function() {
		if (currentPage > 1) {
			currentPage--;
			window.renderCases();
		}
	});

	$('.next-btn').click(function() {
		const totalPages = Math.ceil(filteredCases.length / itemsPerPage);
		if (currentPage < totalPages) {
			currentPage++;
			window.renderCases();
		}
	});

	// 금지명령 버튼 이벤트
	$('#prohibitionOrder').on('click', function(e) {
		e.preventDefault();
		if (!currentCaseNo) {
			alert('사건을 먼저 선택해주세요.');
			return;
		}
		
		// 현재 선택된 소득유형 값 가져오기
		const incomeType = $('input[name="incomeType"]:checked').val() || '0';
		const previousIncomeType = $('#incomeTypeValue').val() || '0';
		const incomeTypeChanged = (incomeType !== previousIncomeType) ? 1 : 0;
		
		const width = 1200;
		const height = 800;
		const left = (screen.width - width) / 2;
		const top = (screen.height - height) / 2;
		
		window.open(
			'api/application_bankruptcy/prohibition_order.php?case_no=' + currentCaseNo + '&income_type=' + incomeType + '&income_type_changed=' + incomeTypeChanged,
			'prohibition_order_window',
			'width=' + width + 
			',height=' + height + 
			',left=' + left + 
			',top=' + top + 
			',scrollbars=yes,status=no,toolbar=no,location=no,directories=no,menubar=no,resizable=yes,fullscreen=no'
		);
	});
	
	// 중지명령 버튼 이벤트
	$('#stayOrder').on('click', function(e) {
		e.preventDefault();
		
		if (!currentCaseNo) {
			alert('사건을 먼저 선택해주세요.');
			return;
		}
		
		const width = 1000;
		const height = 800;
		const left = (screen.width - width) / 2;
		const top = (screen.height - height) / 2;
		
		window.open(
			'api/application_bankruptcy/stay_order_list.php?case_no=' + currentCaseNo,
			'stay_order_window',
			'width=' + width + 
			',height=' + height + 
			',left=' + left + 
			',top=' + top + 
			',scrollbars=yes'
		);
	});
	
	// 신청인 정보 저장 버튼 이벤트
	$('#save_applicant').on('click', function(e) {
		e.preventDefault();
		window.saveApplicantData();
	});
	
	// 신청일과 변제개시일 관련 이벤트 핸들러
	$('#applicationDate, #unspecifiedDate').on('change', function() {
		window.updateRepaymentStartDate();
	});
	
	// 주소 검색 버튼 이벤트
	$('.btn-search').on('click', function() {
		const targetId = $(this).data('target');
		
		new daum.Postcode({
			oncomplete: function(data) {
				let addr = data.address;
				let extraAddr = '';

				if (data.addressType === 'R') {
					if (data.bname !== '') {
						extraAddr += data.bname;
					}
					if (data.buildingName !== '') {
						extraAddr += (extraAddr !== '' ? ', ' + data.buildingName : data.buildingName);
					}
					if (extraAddr !== '') {
						extraAddr = ' (' + extraAddr + ')';
					}
					addr += extraAddr;
				}

				$(`#${targetId}`).val(addr);
				
				// 주소가 같게 설정되어 있으면 실거주지 주소도 업데이트
				if ($('#sameAsRegistered_b').is(':checked') && targetId === 'registeredAddress_b') {
					$('#nowAddress_b').val(addr);
				}
			}
		}).open();
	});
	
	// 주민등록번호 포맷
	$('#residentNumber_b').on('input', function() {
		let value = this.value.replace(/[^0-9-]/g, '');
		if (value.length > 6 && value.indexOf('-') === -1) {
			value = value.substr(0, 6) + '-' + value.substr(6);
		}
		value = value.substr(0, 14);
		this.value = value;
	});

	// 전화번호 포맷
	$('#phone_b, #workPhone_b').on('input', function() {
		let value = this.value.replace(/[^0-9]/g, '');
		if (value.length > 3 && value.length <= 7) {
			value = value.substr(0, 3) + '-' + value.substr(3);
		} else if (value.length > 7) {
			value = value.substr(0, 3) + '-' + value.substr(3, 4) + '-' + value.substr(7);
		}
		this.value = value.substr(0, 13);
	});
	
	// 주소 동일 체크박스 이벤트
	$('#sameAsRegistered_b').on('change', function() {
		if (this.checked) {
			// 체크되면 주민등록상 주소를 실거주지 주소에 복사
			$('#nowAddress_b').val($('#registeredAddress_b').val());
			// 실거주지 주소 입력 필드 비활성화
			$('#nowAddress_b').prop('readonly', true);
			// 주소찾기 버튼도 비활성화
			$('.btn-search[data-target="nowAddress_b"]').prop('disabled', true);
		} else {
			// 체크 해제시 실거주지 주소 입력 필드 활성화
			$('#nowAddress_b').prop('readonly', false);
			// 주소찾기 버튼도 활성화
			$('.btn-search[data-target="nowAddress_b"]').prop('disabled', false);
		}
	});

	// 주민등록상 주소가 변경될 때 체크박스가 체크되어 있으면 실거주지 주소도 같이 변경
	$('#registeredAddress_b').on('change', function() {
		if ($('#sameAsRegistered_b').is(':checked')) {
			$('#nowAddress_b').val($(this).val());
		}
	});

	// Ajax 시작/종료 시 로딩 표시
	$(document).ajaxStart(function() {
		window.showLoading();
	}).ajaxStop(function() {
		window.hideLoading();
	});

	// 엔터 키 이벤트 처리
	$('#searchInput').keypress(function(e) {
		if (e.which === 13) {
			e.preventDefault();
			$(this).trigger('keyup');
		}
	});
	
	// 삭제 버튼 이벤트
	$('#delete_applicant').on('click', function(e) {
		e.preventDefault();
		
		if (!currentCaseNo) {
			alert('사건을 먼저 선택해주세요.');
			return;
		}
		
		if (confirm('정말로 이 사건을 삭제하시겠습니까?')) {
			$.ajax({
				url: '/adm/api/application_bankruptcy/delete_bankruptcy.php',
				type: 'POST',
				data: { case_no: currentCaseNo },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						alert('삭제되었습니다.');
						currentCaseNo = null;
						window.loadCases();
						initializeView();
					} else {
						alert(response.message || '삭제 중 오류가 발생했습니다.');
					}
				},
				error: function() {
					alert('서버 통신 중 오류가 발생했습니다.');
				}
			});
		}
	});
});