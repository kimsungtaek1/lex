// 현재 선택된 사건 번호가 있으면 사용
if (typeof window.currentCaseNo !== 'undefined') {
    currentCaseNo = window.currentCaseNo;
}

$(document).ready(function() {
    let creditorCount = 0;
    let creditors = [];
    
    // 초기화
    initCreditorEvents();

    function initCreditorEvents() {
        if (typeof window.creditorBoxTemplate === 'undefined') {
            console.error('채권자 박스 템플릿이 로드되지 않았습니다');
            return;
        }
        // 템플릿 로드
        addNewCreditor();

        // 추가 버튼
        $(document).on('click', '.btn-add-creditor', function() {
            addNewCreditor();
        });

        // 복사 버튼
        $(document).on('click', '.btn-copy-creditor', function() {
            copySelectedCreditors();
        });

        // 삭제 버튼
        $(document).on('click', '.btn-delete-creditor', function() {
            deleteSelectedCreditorsBankruptcy();
        });

        // 금액 입력 시 자동 콤마
        $(document).on('input', '.number-input', function() {
            formatNumber($(this));
        });
        // 페이지 로드 시 기존 값들에 대한 포맷팅
        $('.number-input').each(function() {
            if (this.value) {
                formatNumber($(this));
            }
        });

        // 채권자 저장 버튼 클릭 이벤트
        $(document).on('click', '[id^="saveCreditor_b"]', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $creditorBox = $button.closest('.creditor-box');
            const count = $creditorBox.data('count');

            // 중복 클릭 방지
            if ($button.prop('disabled')) return;
            if (!window.currentCaseNo) {
                alert('사건을 먼저 선택해주세요.');
                return;
            }
            // 필수 입력값 검증
            const $financialInstitution = $creditorBox.find(`#financialInstitution_b${count}`);
            if (!$financialInstitution.val()) {
                alert('금융기관을 선택해주세요.');
                $financialInstitution.focus();
                return;
            }
            $button.prop('disabled', true);
            // 데이터 수집
            const creditorData = getCreditorData(count);
            creditorData.case_no = window.currentCaseNo;
            creditorData.creditor_count = count;
            console.log(creditorData);

            $.ajax({
                url: 'api/application_bankruptcy/save_creditor.php',
                type: 'POST',
                data: creditorData,
                dataType: 'json',
                success: function(response) {
                    $button.prop('disabled', false);
                    if (response.success) {
                        alert('채권자 정보가 저장되었습니다.');
                    } else {
                        alert(response.message || '저장 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    alert('서버 통신 중 오류가 발생했습니다.');
                }
            });
        });

        // 채권자 삭제 버튼 클릭 이벤트
        $(document).on('click', '[id^="deleteCreditor_b"]', function() {
            const count = $(this).attr('id').replace('deleteCreditor_b', '');
            if (!confirm('해당 채권자를 삭제하시겠습니까?')) return;
            $.ajax({
                url: 'api/application_bankruptcy/delete_creditor.php',
                type: 'POST',
                data: {
                    case_no: window.currentCaseNo,
                    creditor_ids: JSON.stringify([count])
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`#creditorBox_b${count}`).remove();
                        if ($('.creditor-box').length === 0) {
                            if (typeof addNewCreditor === 'function') addNewCreditor();
                        }
                        alert('채권자가 삭제되었습니다.');
                    } else {
                        alert(response.message || '삭제 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    alert('서버 통신 중 오류가 발생했습니다.');
                }
            });
        });
        
        // 주소 검색 버튼 이벤트
        $(document).on('click', '.address-search', function() {
            const target = $(this).data('target');
            const count = target.replace('address_b', '');
            searchAddress(count);
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

        // source, count 파라미터를 URL에 추가
        const searchWindow = window.open(
            `api/application_bankruptcy/search_financial_institution.php?source=creditor&count=${count}`,
            'SearchFinancialInstitution',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
        );

        if (searchWindow === null) {
            alert('팝업이 차단되어 있습니다. 팝업 차단을 해제해주세요.');
            return;
        }

        // 금융기관 선택 이벤트 리스너 (중복 등록 방지 위해 remove 후 add)
        window.removeEventListener('message', window._financialInstitutionListener);
        window._financialInstitutionListener = function(event) {
            if (event.data.type === 'financialInstitutionSelectedForCreditor') {
                fillFinancialInstitution(event.data.institution, count);
                searchWindow.close();
            }
        };
        window.addEventListener('message', window._financialInstitutionListener);
    });
    
    // 주소 검색 함수
    function searchAddress(count) {
        const width = 500;
        const height = 500;
        const left = (window.screen.width / 2) - (width / 2);
        const top = (window.screen.height / 2) - (height / 2);

        new daum.Postcode({
            width: width,
            height: height,
            oncomplete: function(data) {
                $(`#address_b${count}`).val(data.address);
                // 팝업 창 닫기
                const frame = document.getElementsByClassName('daum_postcode_layer')[0];
                if (frame) {
                    frame.style.display = 'none';
                }
            },
            onclose: function() {
                const frame = document.getElementsByClassName('daum_postcode_layer')[0];
                if (frame) {
                    frame.style.display = 'none';
                }
            }
        }).open();
    }

    // 새 채권자 추가
    function addNewCreditor(data = null) {
        creditorCount++;
        let boxHtml = window.creditorBoxTemplate.replace(/\{count\}/g, creditorCount);
        $('#creditorList').append(boxHtml);
        if (data) {
            fillCreditorData(creditorCount, data);
        }
        initializeCreditorForm(creditorCount);
        return creditorCount;
    }

    // 채권자 데이터 채우기
    function fillCreditorData(count, data) {
        $(`#financialInstitution_b${count}`).val(data.financial_institution || '');
        $(`#address_b${count}`).val(data.address || '');
        $(`#phone_b${count}`).val(formatPhoneNumber(data.phone || ''));
        $(`#fax_b${count}`).val(formatFaxNumber(data.fax || ''));
        $(`#calculationDate_b${count}`).val(data.borrowing_date || '');
        $(`#separateBond_b${count}`).val(data.separate_bond || '금원차용');
        $(`#reasonDetail_b${count}`).val(data.reason_detail || '');
        $(`#usageDetail_b${count}`).val(data.usage_detail || '');
        $(`#initialClaim_b${count}`).val(numberWithCommas(data.initial_claim || 0));
        $(`#remainingPrincipal_b${count}`).val(numberWithCommas(data.remaining_principal || 0));
        $(`#remainingInterest_b${count}`).val(numberWithCommas(data.remaining_interest || 0));
    }

    // 채권자 폼 초기화
    function initializeCreditorForm(count) {
        // 금액 입력 필드에 숫자만 허용
        $(`#initialClaim_b${count}, #remainingPrincipal_b${count}, #remainingInterest_b${count}`).on('input', function() {
            this.value = this.value.replace(/[^\d]/g, '');
        });
    }

    // 금액 포맷팅
    function formatNumber(input) {
        if (!input || !input.val) return; // input이 없거나 val 메서드가 없으면 종료
        
        let value = input.val();
        if (!value) return; // 값이 없으면 종료
        
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

    // 숫자 콤마 추가
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 복사 기능
    function copySelectedCreditors() {
        $('.creditor-checkbox:checked').each(function() {
            const count = $(this).data('count');
            const data = getCreditorData(count);
            addNewCreditor(data);
        });
    }

    // 채권자 데이터 가져오기
    function getCreditorData(count) {
        return {
            address: $(`#address_b${count}`).val() || '',
            borrowing_date: $(`#borrowingDate_b${count}`).val() || '',
            claim_reason: $(`#claimReason_b${count}`).val() || '',
            fax: $(`#fax_b${count}`).val() || '',
            financial_institution: $(`#financialInstitution_b${count}`).val() || '',
            initial_claim: $(`#initialClaim_b${count}`).val() || 0,
            phone: $(`#phone_b${count}`).val() || '',
            reason_detail: $(`#reasonDetail_b${count}`).val() || '',
            remaining_interest: $(`#remainingInterest_b${count}`).val() || 0,
            remaining_principal: $(`#remainingPrincipal_b${count}`).val() || 0,
            separate_bond: $(`#separateBond_b${count}`).val() || '',
            usage_detail: $(`#usageDetail_b${count}`).val() || ''
        };
    }

    // 선택된 채권자 삭제
    function deleteSelectedCreditorsBankruptcy() {
        const selectedBoxes = $('.creditor-checkbox:checked');
        if (selectedBoxes.length === 0) {
            alert('삭제할 채권자를 선택해주세요.');
            return;
        }
        if (!confirm('선택한 채권자를 삭제하시겠습니까?')) return;
        const creditorIds = [];
        selectedBoxes.each(function() {
            const count = $(this).closest('.creditor-box').data('count');
            if (count) creditorIds.push(count);
        });
        $.ajax({
            url: 'api/application_bankruptcy/delete_creditor.php',
            type: 'POST',
            data: {
                case_no: window.currentCaseNo,
                creditor_ids: JSON.stringify(creditorIds)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    selectedBoxes.each(function() {
                        $(this).closest('.creditor-box').remove();
                    });
                    if ($('.creditor-box').length === 0) {
                        if (typeof addNewCreditor === 'function') addNewCreditor();
                    }
                    alert('선택한 채권자가 삭제되었습니다.');
                } else {
                    alert(response.message || '삭제 중 오류가 발생했습니다.');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#financialInstitution_b${count}`).val(institution.name);
        $(`#address_b${count}`).val(institution.address);
        $(`#phone_b${count}`).val(formatPhoneNumber(institution.phone));
        $(`#fax_b${count}`).val(formatFaxNumber(institution.fax));
    }

    // 금융기관 선택 메시지 수신 및 필드 자동 입력 (creditor용)
    window.addEventListener('message', function(event) {
        if (event.data && (event.data.type === 'financialInstitutionSelected' || event.data.type === 'financialInstitutionSelectedForCreditor')) {
            const institution = event.data.institution;
            const count = event.data.count;
            if (institution && typeof count !== 'undefined') {
                $(`#financialInstitution_b${count}`).val(institution.name);
                $(`#address_b${count}`).val(institution.address);
                $(`#phone_b${count}`).val(institution.phone);
                $(`#fax_b${count}`).val(institution.fax);
            }
        }
    });

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
    
    // 전화번호 필드 이벤트 처리
    $(document).on('input', '[id^=phone]', function(e) {
        let input = $(this);
        let value = input.val();
        let formatted = formatPhoneNumber(value);
        
        // 커서 위치 저장
        let cursorPos = this.selectionStart;
        let beforeLength = value.length;
        
        // 포맷된 값으로 업데이트
        input.val(formatted);
        
        // 커서 위치 조정
        if (formatted.length > beforeLength) {
            cursorPos++;
        }
        this.setSelectionRange(cursorPos, cursorPos);
    });

    // 팩스번호 포맷팅 함수
    function formatFaxNumber(value) {
        if (!value) return '';
        
        // 숫자만 추출
        value = value.replace(/[^\d]/g, '');
        
        // 지역번호 패턴
        const areaCode = value.substring(0, 2);
        
        // 서울(02)인 경우
        if (areaCode === '02') {
            if (value.length <= 2) {
                return value;
            } else if (value.length <= 6) {
                return value.slice(0, 2) + '-' + value.slice(2);
            } else {
                return value.slice(0, 2) + '-' + value.slice(2, 6) + '-' + value.slice(6, 10);
            }
        }
        // 그 외 지역번호(031~066)인 경우
        else {
            if (value.length <= 3) {
                return value;
            } else if (value.length <= 7) {
                return value.slice(0, 3) + '-' + value.slice(3);
            } else {
                return value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
            }
        }
    }

    // 팩스번호 입력 필드 이벤트 처리
    $(document).on('input', '[id^=fax]', function(e) {
        let input = $(this);
        let value = input.val();
        let formatted = formatFaxNumber(value);
        
        // 커서 위치 저장
        let cursorPos = this.selectionStart;
        let beforeLength = value.length;
        
        // 포맷된 값으로 업데이트
        input.val(formatted);
        
        // 커서 위치 조정
        if (formatted.length > beforeLength) {
            cursorPos++;
        }
        this.setSelectionRange(cursorPos, cursorPos);
    });

    // 보증인 관리 버튼 클릭 이벤트
    $(document).on('click', '[id^="guarantorManage_b"]', function() {
        const count = $(this).attr('id').replace('guarantorManage_b', '');
        openGuarantorManager(count);
    });

    // 보증인 관리 팝업 열기
    function openGuarantorManager(count) {
        if (!currentCaseNo) {
            alert('사건을 먼저 선택해주세요.');
            return;
        }
        const width = 1200;
        const height = 800;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        const url = `./api/application_bankruptcy/guarantor_manager.php?case_no=${currentCaseNo}&creditor_count=${count}`;
        const guarantorWindow = window.open(
            url,
            'GuarantorManager',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
        );
        if (guarantorWindow === null) {
            alert('팝업이 차단되어 있습니다. 팝업 차단을 해제해주세요.');
        }
    }

    // 보증인 수 업데이트 (각 채권자 로드 시 호출)
    function updateGuarantorCount(count) {
        $.ajax({
            url: './api/application_bankruptcy/guarantor_api.php',
            type: 'GET',
            data: {
                action: 'count',
                case_no: currentCaseNo,
                creditor_count: count
            },
            success: function(response) {
                if (response.success) {
                    $(`#guarantorCount_b${count}`).text(response.count);
                }
            }
        });
    }

    // 전역 함수로 노출
    window.addNewCreditor = addNewCreditor;
    window.searchAddress = searchAddress;
    window.loadCreditors = function(caseNo) {
        if (!caseNo) return;
        $.ajax({
            url: './api/application_bankruptcy/get_creditors.php',
            type: 'GET',
            data: { case_no: caseNo },
            beforeSend: function() {
                if (window.showLoading) window.showLoading();
            },
            success: function(response) {
                if (response.success) {
                    $('#creditorList').empty();
                    creditorCount = 0;
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(creditor) {
                            const newCount = addNewCreditor(creditor);
                            // 필요시: 보증인 등 부가 데이터 로드 가능
                        });
                    } else {
                        addNewCreditor(); // 데이터가 없으면 빈 폼 추가
                    }
                } else {
                    console.error('채권자 데이터 로드 실패:', response.message);
                    alert('채권자 정보를 불러오는데 실패했습니다.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('서버 통신 중 오류가 발생했습니다.');
            },
            complete: function() {
                if (window.hideLoading) window.hideLoading();
            }
        });
    };
    window.formatNumber = function(input) {
        if (!input || !input.value) return;
        
        // 현재 커서 위치 저장
        const position = input.selectionStart;
        const length = input.value.length;
        
        // 숫자와 소수점만 남기고 모두 제거
        let value = input.value.replace(/[^\d.]/g, '');
        
        // 숫자가 있을 경우에만 포맷팅
        if (value) {
            // 정수 부분만 콤마 처리
            value = Number(value).toLocaleString('ko-KR');
            input.value = value;
            
            // 커서 위치 조정
            const newLength = input.value.length;
            const cursorPos = position + (newLength - length);
            input.setSelectionRange(cursorPos, cursorPos);
        }
    };
});