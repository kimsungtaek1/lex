// application_recovery_applicant.js

$(document).ready(function() {
    // 신청인정보 초기화
    initApplicantEvents();

    function initApplicantEvents() {
        // 페이지 로드 시 기본 필드명 설정 (급여소득자)
        $('div.form-title span').each(function() {
            const text = $(this).text();
            if (text === '영업장주소') $(this).text('직장주소');
            if (text === '상호명') $(this).text('직장명');
            if (text === '업종') $(this).text('직위');
            if (text === '종사경력') $(this).text('근무기간');
        });

        // 소득유형 선택 이벤트
        $('input[name="incomeType"]').off('change').on('change', function() {
            const selectedLabel = $(`label[for="${this.id}"]`);
            const otherLabel = $(`label[for="${this.id === 'salaryType' ? 'businessType' : 'salaryType'}"]`);
            
            selectedLabel.attr('data-selected', 'true');
            otherLabel.removeAttr('data-selected');

            // 소득유형에 따라 필드명 변경
                if (this.id === 'businessType') {
                    $('div.form-title span').each(function() {
                        const text = $(this).text();
                        if (text === '직장주소') $(this).text('영업장주소');
                        if (text === '직장명') $(this).text('상호명');
                        if (text === '직위') $(this).text('업종');
                        if (text === '근무기간') $(this).text('종사경력');
                    });
                    
                    // 영업소득자로 변경 시 필드 초기화
                    $('#workAddress').val('');
                    $('#workplace').val('');
                    $('#position').val('');
                    $('#workPeriod').val('');
                    $('#otherIncomeName').val('');
                    $('#incomeSource').val('');
                    $('#remarks').val('');
                } else {
                    $('div.form-title span').each(function() {
                        const text = $(this).text();
                        if (text === '영업장주소') $(this).text('직장주소');
                        if (text === '상호명') $(this).text('직장명');
                        if (text === '업종') $(this).text('직위');
                        if (text === '종사경력') $(this).text('근무기간');
                    });
                    
                    // 급여소득자로 변경 시 필드 초기화
                    $('#workAddress').val('');
                    $('#workplace').val('');
                    $('#position').val('');
                    $('#workPeriod').val('');
                    $('#otherIncomeName').val('');
                    $('#incomeSource').val('');
                    $('#remarks').val('');
                }
        });

        // 주소 검색 버튼 이벤트
        $('.btn-search').off('click').on('click', function() {
            const targetId = $(this).data('target');
            searchAddress(targetId);
        });

        // 주소 동일 체크박스 이벤트
        $('#sameAsRegistered').off('change').on('change', function() {
            if (this.checked) {
                $('#nowAddress').val($('#registeredAddress').val());
            }
        });

        // 신청일과 변제개시일 관련 이벤트 핸들러
        $('#applicationDate, #unspecifiedDate').on('change', function() {
            updateRepaymentStartDate();
        });

        // 자동 입력 포맷 이벤트들
        initializeInputFormatting();

        // 저장 버튼 이벤트
        $('#save_applicant').click(function(e) {
            e.preventDefault();
            saveApplicantData();
        });

        // 날짜 입력 필드 초기화
        initializeDateFields();
    }

    // 주소 검색 함수
    function searchAddress(targetId) {
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
                $(`#${targetId}Detail`).focus();
                
                // 주소가 같게 설정되어 있으면 현재주소도 업데이트
                if ($('#sameAsRegistered').is(':checked') && targetId === 'registeredAddress') {
                    $('#nowAddress').val(addr);
                }
            }
        }).open();
    }

    // 신청인 데이터 저장
    function saveApplicantData() {
        const formData = {
            case_no: currentCaseNo || '',
            name: $('#name').val(),
            phone: $('#phone').val(),
            residentNumber: $('#residentNumber').val(),
            registeredAddress: $('#registeredAddress').val(),
            nowAddress: $('#nowAddress').val(),
            workAddress: $('#workAddress').val(),
            workplace: $('#workplace').val(),
            position: $('#position').val(),
            workPeriod: $('#workPeriod').val(),
            otherIncome: $('#otherIncome').val(),
            otherIncomeName: $('#otherIncomeName').val(),
            incomeSource: $('#incomeSource').val(),
            remarks: $('#remarks').val(),
            applicationDate: $('#applicationDate').val(),
            unspecifiedDate: $('#unspecifiedDate').is(':checked') ? 1 : 0,
            repaymentStartDate: $('#repaymentStartDate').val(),
            court: $('#court').val(),
            caseNumber: $('#caseNumber').val(),
            bankName: $('#bankName').val(),
            accountNumber: $('#accountNumber').val(),
            incomeType: parseInt($('input[name="incomeType"]:checked').val() || '0'),
            is_company: $('input[name="incomeType"]:checked').val() === 'business' ? 1 : 0
        };

        $.ajax({
            url: '/adm/api/application_recovery/save_recovery_data.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('저장되었습니다.');
                    if (response.case_no) {
                        currentCaseNo = response.case_no;
                    }
                    window.loadCases();
                    loadApplicantData(currentCaseNo);
                } else {
                    alert(response.message || '저장 중 오류가 발생했습니다.');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // 신청인 데이터 로드
    function loadApplicantData(caseNo) {
        if (!caseNo) return;
        
        $.ajax({
            url: '/adm/api/application_recovery/get_recovery_data.php',
            type: 'GET',
            data: { case_no: caseNo },
            dataType: 'json',
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                if (response.success) {
                    currentCaseNo = caseNo;
                    fillApplicantForm(response.data);
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
                hideLoading();
            }
        });
    }

    // 폼 데이터 채우기
    function fillApplicantForm(data) {
        clearApplicantForm(); // 폼 초기화

        // 기본 정보
        $('#name').val(data.name || '');
        $('#phone').val(data.phone || '');
        $('#residentNumber').val(data.resident_number || '');
        $('#registeredAddress').val(data.registered_address || '');
        $('#nowAddress').val(data.now_address || '');
        $('#workAddress').val(data.work_address || '');
        $('#workplace').val(data.workplace || '');
        $('#position').val(data.position || '');
        $('#workPeriod').val(data.work_period || '');
        
        // 추가 정보 
        $('#otherIncome').val(data.other_income || '');
        $('#otherIncomeName').val(data.other_income_name || '');
        $('#incomeSource').val(data.income_source || '');
        $('#debt_total').val(data.debt_total || '');
        $('#income_monthly').val(data.income_monthly || '');
        $('#expense_monthly').val(data.expense_monthly || '');
        $('#repayment_monthly').val(data.repayment_monthly || '');
        $('#assets_total').val(data.assets_total || '');

        // 신청 관련 정보 
        if (data.application_date) {
            $('#applicationDate').val(data.application_date);
        }
        
        $('#unspecifiedDate').prop('checked', data.unspecified_date);
        
        if (data.repayment_start_date && data.unspecified_date === 1) {
            $('#repaymentStartDate').val(data.repayment_start_date);
        }
        
        updateRepaymentStartDate();
        
        $('#court').val(data.court_name || '');
        $('#caseNumber').val(data.case_number || '');
        $('#bankName').val(data.bank_name || '');
        $('#accountNumber').val(data.account_number || '');
        
        // 소득 유형
        $(`input[name="incomeType"][value="${data.is_company === 1 ? '1' : '0'}"]`).prop('checked', true);

        $('#sameAsRegistered').prop('checked', data.registered_address === data.now_address);
        
        // 비고
        $('#remarks').val(data.memo || '');
        
        // 금액 데이터 천단위 콤마 적용
        ['debt_total', 'income_monthly', 'expense_monthly', 'repayment_monthly', 'assets_total', 'otherIncome'].forEach(field => {
            const value = $(`#${field}`).val();
            if(value) {
                $(`#${field}`).val(Number(value).toLocaleString());
            }
        });
    }

    // 폼 초기화
    function clearApplicantForm() {
        $('.applicant-form')[0].reset();
    }

    // 변제개시일 업데이트
    function updateRepaymentStartDate() {
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
    }

    // 입력 필드 포맷팅 초기화
    function initializeInputFormatting() {
        // 주민등록번호 포맷
        $('#residentNumber').on('input', function() {
            let value = this.value.replace(/[^0-9-]/g, '');
            if (value.length > 6 && value.indexOf('-') === -1) {
                value = value.substr(0, 6) + '-' + value.substr(6);
            }
            value = value.substr(0, 14);
            this.value = value;
        });

        // 전화번호 포맷
        $('#phone').on('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 3 && value.length <= 7) {
                value = value.substr(0, 3) + '-' + value.substr(3);
            } else if (value.length > 7) {
                value = value.substr(0, 3) + '-' + value.substr(3, 4) + '-' + value.substr(7);
            }
            this.value = value.substr(0, 13);
        });

        // 계좌번호 포맷
        $('#accountNumber').on('input', function() {
            this.value = this.value.replace(/[^0-9-]/g, '');
        });
    }

    // 날짜 필드 초기화
    function initializeDateFields() {
        $('input[type="date"]').each(function() {
            if (!$(this).val()) {
                $(this).val(new Date().toISOString().split('T')[0]);
            }
        });
    }

    // 전역 함수로 노출
    window.loadApplicantData = loadApplicantData;
    window.updateRepaymentStartDate = updateRepaymentStartDate;
});
