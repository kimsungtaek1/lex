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
        url: '/adm/api/application_recovery/get_prohibition_count.php',
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
        url: '/adm/api/application_recovery/get_stay_order_count.php',
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
        url: '/adm/api/application_recovery/get_recovery_cases.php',
        method: 'GET',
        cache: false,
        success: function(response) {
            if (response.success) {
                cases = response.data;
                filteredCases = [...cases];
                window.renderCases();
            } else {
                console.error('사건목록 로드 실패:', response.message);
                alert('사건목록을 불러오는데 실패했습니다.');
                window.resetCaseList();
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax 에러:', error);
            alert('서버 통신 중 오류가 발생했습니다.');
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
        url: '/adm/api/application_recovery/get_recovery_data.php',
        type: 'GET',
        data: { case_no: caseNo },
        dataType: 'json',
        beforeSend: function() {
            window.showLoading();
        },
        success: function(response) {
            if (response.success) {
                currentCaseNo = caseNo;
                window.loadApplicantData(caseNo);
                window.updateProhibitionCount();
                window.updateStayOrderCount();
            } else {
                console.error('데이터 로드 중 오류가 발생했습니다: ' + response.message);
                alert("권한이 없습니다");
                window.location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('서버 통신 중 오류가 발생했습니다.');
        },
        complete: function() {
            window.hideLoading();
        }
    });
};

window.loadApplicantData = function(caseNo) {
    if (!caseNo) return;
    
    $.ajax({
        url: '/adm/api/application_recovery/get_applicant_data.php',
        type: 'GET',
        data: { case_no: caseNo },
        dataType: 'json',
        beforeSend: function() {
            window.showLoading();
        },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#name').val(data.name || '');
                $('#phone').val(data.phone || '');
                $('#residentNumber').val(data.resident_number || '');
                $('#registeredAddress').val(data.registered_address || '');
                $('#nowAddress').val(data.now_address || '');
                $('#workAddress').val(data.work_address || '');
                $('#workplace').val(data.workplace || '');
                $('#position').val(data.position || '');
                $('#workPeriod').val(data.work_period || '');
                $('#otherIncome').val(data.other_income || '');
                $('#otherIncomeName').val(data.other_income_name || '');
                $('#incomeSource').val(data.income_source || '');
                $('#remarks').val(data.remarks || '');
                $('#applicationDate').val(data.application_date || '');
                $('#repaymentStartDate').val(data.repayment_start_date || '');
                $('#unspecifiedDate').prop('checked', data.unspecified_date === '1');
                $('#court').val(data.court || '');
                $('#caseNumber').val(data.case_number || '');
                $('#bankName').val(data.bank_name || '');
                $('#accountNumber').val(data.account_number || '');
                $('#sameAsRegistered').prop('checked', data.same_as_registered === '1');

                // 소득유형 설정
                if (data.income_type === '0') {
                    $('#salaryType').prop('checked', true);
                    $('#salaryType').attr('data-selected', 'true');
                    $('#businessType').attr('data-selected', 'false');
                } else if (data.income_type === '1') {
                    $('#businessType').prop('checked', true);
                    $('#businessType').attr('data-selected', 'true');
                    $('#salaryType').attr('data-selected', 'false');
                }
                
                // DB에서 가져온 소득유형 값을 hidden input에 저장
                if (!$('#incomeTypeValue').length) {
                    $('form').append('<input type="hidden" id="incomeTypeValue" name="incomeTypeValue">');
                }
                $('#incomeTypeValue').val(data.income_type || '0');
            } else {
                console.error('신청인 데이터 로드 실패:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax 에러:', error);
        },
        complete: function() {
            window.hideLoading();
        }
    });
};

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
<<<<<<< HEAD
=======
 
$(document).on('change','input[type="checkbox"]', function(){
  const isChecked = $(this).is(':checked');
  $(this).attr('data-selected', isChecked.toString());
  $(`label[for="${this.id}"]`).attr('data-selected', isChecked.toString());
});
>>>>>>> 719d7c8 (Delete all files)

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
}


$(document).ready(function() {
    // 초기화
    initializeView();
    initializeData();

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
		} else if (type === 'creditor') {
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
<<<<<<< HEAD
=======
        window.currentCaseNo = caseNo; // window 객체에도 설정
>>>>>>> 719d7c8 (Delete all files)
        
        $('#caseList tr').removeClass('active');
        $(this).addClass('active');
        
        switchTab('applicant');
        window.loadCaseData(caseNo);
<<<<<<< HEAD
=======
        
        // AssetManager 초기화 또는 재로드
        if (!window.assetManager) {
            window.assetManager = new AssetManager();
        } else {
            window.assetManager.loadAllAssets();
        }
		
        if (!window.incomeExpenditureManager) {
            window.incomeExpenditureManager = new ApplicationRecoveryIncomeExpenditure();
        }
		
		if (!window.statementManager) {
			window.statementManager = new StatementManager();
        }
>>>>>>> 719d7c8 (Delete all files)
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
            'api/application_recovery/prohibition_order.php?case_no=' + currentCaseNo + '&income_type=' + incomeType + '&income_type_changed=' + incomeTypeChanged,
            'prohibition_order_window',
            'width=' + width + 
            ',height=' + height + 
            ',left=' + left + 
            ',top=' + top + 
            ',scrollbars=yes,status=no,toolbar=no,location=no,directories=no,menubar=no,resizable=yes,fullscreen=no'
        );
    });
    
    // 중지명령 버튼 이벤트
<<<<<<< HEAD
    // 중지명령 버튼 이벤트
=======
>>>>>>> 719d7c8 (Delete all files)
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
            'api/application_recovery/stay_order_list.php?case_no=' + currentCaseNo,
            'stay_order_window',
            'width=' + width + 
            ',height=' + height + 
            ',left=' + left + 
            ',top=' + top + 
            ',scrollbars=yes'
        );
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
});
