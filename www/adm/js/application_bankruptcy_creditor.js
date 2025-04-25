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
            deleteSelectedCreditors();
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

        // 날짜 변경 시 자동 계산
        $(document).on('change', '[id^=calculationDate_b]', function() {
            const count = this.id.replace('calculationDate_b', '');
            updateCalculations(count);
        });

        // 채권자 저장 버튼 클릭 이벤트
        $(document).on('click', '[id^="saveCreditor_b"]', function() {
            const count = $(this).attr('id').replace('saveCreditor_b', '');
            saveCreditor(count);
        });

        // 채권자 삭제 버튼 클릭 이벤트
        $(document).on('click', '[id^="deleteCreditor_b"]', function() {
            const count = $(this).attr('id').replace('deleteCreditor_b', '');
            deleteSingleCreditor(count);
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

        const searchWindow = window.open(
            'api/application_bankruptcy/search_financial_institution.php',
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
        $(`#phone_b${count}`).val(data.phone || '');
        $(`#fax_b${count}`).val(data.fax || '');
        $(`#calculationDate_b${count}`).val(data.borrowing_date || '');
        $(`#separateBond_b${count}`).val(data.separate_bond || '금원차용');
        $(`#reasonDetail_b${count}`).val(data.usage_detail || '');
        $(`#claimReason_b${count}`).val(data.claim_reason || '');
        $(`#initialClaim_b${count}`).val(data.initial_claim || '');
        $(`#remainingPrincipal_b${count}`).val(data.remaining_principal || '');
        $(`#remainingInterest_b${count}`).val(data.remaining_interest || '');
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
        if (!input || !input.val) return;
        let value = input.val().replace(/[^\d]/g, '');
        if (value) {
            value = Number(value).toLocaleString('ko-KR');
            input.val(value);
        }
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
            financial_institution: $(`#financialInstitution_b${count}`).val(),
            address: $(`#address_b${count}`).val(),
            phone: $(`#phone_b${count}`).val(),
            fax: $(`#fax_b${count}`).val(),
            borrowing_date: $(`#calculationDate_b${count}`).val(),
            separate_bond: $(`#separateBond_b${count}`).val(),
            usage_detail: $(`#reasonDetail_b${count}`).val(),
            claim_reason: $(`#claimReason_b${count}`).val(),
            initial_claim: $(`#initialClaim_b${count}`).val(),
            remaining_principal: $(`#remainingPrincipal_b${count}`).val(),
            remaining_interest: $(`#remainingInterest_b${count}`).val()
        };
    }

    // 선택된 채권자 삭제
    function deleteSelectedCreditors() {
        $('.creditor-checkbox:checked').each(function() {
            const box = $(this).closest('.creditor-box');
            box.remove();
        });
    }

    // 단일 채권자 삭제
    function deleteSingleCreditor(count) {
        $(`#financialInstitution_b${count}`).closest('.creditor-box').remove();
    }

    // 계산서 업데이트 (차용 또는 구입일자 변경 시)
    function updateCalculations(count) {
        // 필요 시 계산 로직 추가
    }

    // 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#financialInstitution_b${count}`).val(institution);
    }

    // 채권자 저장
    function saveCreditor(count) {
        const data = getCreditorData(count);
        data.case_no = window.currentCaseNo;
        alert(count);
        data.creditor_count = count;
        $.ajax({
            url: 'api/application_bankruptcy/save_creditor.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('채권자 정보가 저장되었습니다.');
                } else {
                    alert(response.message || '저장 중 오류가 발생했습니다.');
                }
            },
            error: function(xhr, status, error) {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

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
        const width = 800;
        const height = 600;
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
        // bankruptcy-specific AJAX load logic can be implemented here if needed
    };
    window.formatNumber = formatNumber;
});