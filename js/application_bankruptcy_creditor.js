// 현재 선택된 사건 번호가 있으면 사용
if (typeof window.currentCaseNo !== 'undefined') {
    currentCaseNo = window.currentCaseNo;
}

$(document).ready(function() {
    let creditorCount = 0;
    let creditors = [];
    
    // 초기화
    initCreditorEvents();

    // 채권자 관련 이벤트 바인딩
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
        $(document).on('change', '[id^=calculationDate]', function() {
            const count = this.id.replace('calculationDate', '');
            updateCalculations(count);
        });

        // 채권자 저장 버튼 클릭 이벤트
        $(document).on('click', '[id^="saveCreditor"]', function() {
            const count = $(this).attr('id').replace('saveCreditor', '');
            saveCreditor(count);
        });

        // 채권자 삭제 버튼 클릭 이벤트
        $(document).on('click', '[id^="deleteCreditor"]', function() {
            const count = $(this).attr('id').replace('deleteCreditor', '');
            deleteSingleCreditor(count);
        });
        
        // 주소 검색 버튼 이벤트
        $(document).on('click', '.address-search', function() {
            const target = $(this).data('target');
            const count = target.replace('address', '');
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
            'api/application_recovery/search_financial_institution.php',
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
                $(`#address${count}`).val(data.address);
                
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

    // 새 채권자 추가
    function addNewCreditor(data = null) {
        creditorCount++;
        let newBox = window.creditorBoxTemplate.replace(/{count}/g, creditorCount);
        $('#creditorList').append(newBox);
        
        if (data) {
            fillCreditorData(creditorCount, data);
        }
        
        initializeCreditorForm(creditorCount);
        
        return creditorCount;
    }

    // 채권자 데이터 채우기
    function fillCreditorData(count, data) {
        $(`#financialInstitution${count}`).val(data.financial_institution || '');
        $(`#address${count}`).val(data.address || '');
        $(`#phone${count}`).val(formatPhoneNumber(data.phone || ''));
        $(`#fax${count}`).val(data.fax || '');
        $(`#calculationDate${count}`).val(data.borrowing_date || '');
        $(`#separateBond${count}`).val(data.separate_bond || '금원차용');
        
        // 금액 관련 데이터
        if (data.initial_claim) {
            $(`#initialClaim${count}`).val(numberWithCommas(data.initial_claim));
        }
        if (data.remaining_principal) {
            $(`#remainingPrincipal${count}`).val(numberWithCommas(data.remaining_principal));
        }
        if (data.remaining_interest) {
            $(`#remainingInterest${count}`).val(numberWithCommas(data.remaining_interest));
        }
        
        // 사용처
        $(`#claimReason${count}`).val(data.usage_detail || '');
    }

    // 채권자 폼 초기화
    function initializeCreditorForm(count) {
        // 금액 입력 필드에 자동 콤마 추가
        $(`#initialClaim${count}, #remainingPrincipal${count}, #remainingInterest${count}`).on('input', function() {
            formatNumber($(this));
        });
        
        // 전화번호 포맷팅
        $(`#phone${count}`).on('input', function() {
            const formattedPhone = formatPhoneNumber($(this).val());
            $(this).val(formattedPhone);
        });
        
        // 팩스번호 포맷팅
        $(`#fax${count}`).on('input', function() {
            const formattedFax = formatFaxNumber($(this).val());
            $(this).val(formattedFax);
        });
    }

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

    // 선택된 채권자 복사
    function copySelectedCreditors() {
        $('.creditor-checkbox:checked').each(function() {
            const originalBox = $(this).closest('.creditor-box');
            const originalCount = originalBox.data('count');
            if (!originalCount) {
                console.error('채권자 번호를 찾을 수 없습니다');
                return;
            }
            console.log('복사 시도:', originalCount);
            const data = getCreditorData(originalCount);
            addNewCreditor(data);
        });
    }

    // 채권자 데이터 가져오기
    function getCreditorData(count) {
        return {
            financial_institution: $(`#financialInstitution${count}`).val(),
            address: $(`#address${count}`).val(),
            phone: $(`#phone${count}`).val().replace(/-/g, ''),
            fax: $(`#fax${count}`).val(),
            borrowing_date: $(`#calculationDate${count}`).val(),
            separate_bond: $(`#separateBond${count}`).val(),
            usage_detail: $(`#claimReason${count}`).val(),
            initial_claim: $(`#initialClaim${count}`).val().replace(/,/g, ''),
            remaining_principal: $(`#remainingPrincipal${count}`).val().replace(/,/g, ''),
            remaining_interest: $(`#remainingInterest${count}`).val().replace(/,/g, ''),
        };
    }

    // 선택된 채권자 삭제
    function deleteSelectedCreditors() {
        const selectedBoxes = $('.creditor-checkbox:checked');
        if (selectedBoxes.length === 0) {
            alert('삭제할 채권자를 선택해주세요.');
            return;
        }
        
        if (!confirm('선택한 채권자를 삭제하시겠습니까?')) return;

        const creditorIds = [];
        selectedBoxes.each(function() {
            const count = $(this).closest('.creditor-box').data('count');
            if (count) {
                creditorIds.push(count);
            }
        });

        $.ajax({
            url: 'api/application_bankruptcy/delete_creditor.php',
            type: 'POST',
            data: {
                case_no: currentCaseNo,
                creditor_ids: JSON.stringify(creditorIds)
            },
            success: function(response) {
                if (response.success) {
                    selectedBoxes.each(function() {
                        $(this).closest('.creditor-box').remove();
                    });
                    
                    // 모든 채권자가 삭제된 경우 새로운 빈 채권자 추가
                    if ($('.creditor-box').length === 0) {
                        addNewCreditor();
                    }
                    
                    updateCreditorNumbers();
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

    // 단일 채권자 삭제
    function deleteSingleCreditor(count) {
        if (!confirm('해당 채권자를 삭제하시겠습니까?')) return;
        
        $.ajax({
            url: 'api/application_bankruptcy/delete_creditor.php',
            type: 'POST',
            data: {
                case_no: currentCaseNo,
                creditor_ids: JSON.stringify([count])
            },
            success: function(response) {
                if (response.success) {
                    $(`.creditor-box[data-count="${count}"]`).remove();
                    
                    // 모든 채권자가 삭제된 경우 새로운 빈 채권자 추가
                    if ($('.creditor-box').length === 0) {
                        addNewCreditor();
                    }
                    
                    updateCreditorNumbers();
                    alert('채권자가 삭제되었습니다.');
                } else {
                    alert(response.message || '삭제 중 오류가 발생했습니다.');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // 채권자 번호 재정렬
    function updateCreditorNumbers() {
        let newCount = 1;  // 1부터 시작
        $('.creditor-box').each(function() {
            const oldCount = $(this).data('count');
            $(this).data('count', newCount);
            
            // 화면에 표시되는 채권번호 업데이트
            $(this).find('.creditor-number').val(newCount);
            
            // 모든 관련 ID와 name 속성 업데이트
            $(this).find('[id*="' + oldCount + '"]').each(function() {
                this.id = this.id.replace(oldCount, newCount);
            });
            
            $(this).find('[name*="' + oldCount + '"]').each(function() {
                this.name = this.name.replace(oldCount, newCount);
            });
            
            // 버튼의 data-count 속성 업데이트
            $(this).find('[data-count="' + oldCount + '"]').each(function() {
                $(this).data('count', newCount);
                $(this).attr('data-count', newCount);
            });
            
            newCount++;
        });
        
        creditorCount = newCount - 1;
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
        if (!x) return "0";
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 날짜 포맷팅
    function formatDate(date) {
        return date.getFullYear() + '.' + 
               String(date.getMonth() + 1).padStart(2, '0') + '.' + 
               String(date.getDate()).padStart(2, '0');
    }

    // 계산서 업데이트
    function updateCalculations(count) {
        const date = $(`#calculationDate${count}`).val();
        if (!date) return;
        
        const formattedDate = formatDate(new Date(date));
    }

    // 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#financialInstitution${count}`).val(institution.name);
        $(`#address${count}`).val(institution.address);
        $(`#phone${count}`).val(formatPhoneNumber(institution.phone));
        $(`#fax${count}`).val(institution.fax);
    }

    // 채권자 저장
    function saveCreditor(count) {
        if (!currentCaseNo) {
            alert('사건을 먼저 선택해주세요.');
            return;
        }

        const $creditorBox = $(`.creditor-box[data-count="${count}"]`);
        const $financialInstitution = $(`#financialInstitution${count}`);
        
        // 필수 입력값 검증
        if (!$financialInstitution.val()) {
            alert('채권자명을 입력해주세요.');
            $financialInstitution.focus();
            return;
        }

        // 데이터 수집
        const creditorData = {
            case_no: currentCaseNo,
            creditor_count: count,
            financial_institution: $financialInstitution.val(),
            address: $(`#address${count}`).val(),
            phone: $(`#phone${count}`).val().replace(/-/g, ''),
            fax: $(`#fax${count}`).val(),
            borrowing_date: $(`#calculationDate${count}`).val(),
            separate_bond: $(`#separateBond${count}`).val(),
            usage_detail: $(`#claimReason${count}`).val(),
            initial_claim: $(`#initialClaim${count}`).val().replace(/,/g, ''),
            remaining_principal: $(`#remainingPrincipal${count}`).val().replace(/,/g, ''),
            remaining_interest: $(`#remainingInterest${count}`).val().replace(/,/g, '')
        };

        // AJAX 요청
        $.ajax({
            url: './api/application_bankruptcy/save_creditor.php',
            type: 'POST',
            data: creditorData,
            beforeSend: function() {
                if (typeof window.showLoading === 'function') {
                    window.showLoading();
                }
            },
            success: function(response) {
                if (response.success) {
                    alert('채권자 정보가 저장되었습니다.');
                } else {
                    console.error('저장 실패:', response);
                    alert(response.message || '저장 중 오류가 발생했습니다.');
                    if (response.error) {
                        console.error('에러 상세:', response.error);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX 오류:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                alert('서버 통신 중 오류가 발생했습니다.');
            },
            complete: function() {
                if (typeof window.hideLoading === 'function') {
                    window.hideLoading();
                }
            }
        });
    }
    
    // 채권자 데이터 불러오기
    function loadCreditors(caseNo) {
        if (!caseNo) return;
        
        $.ajax({
            url: './api/application_bankruptcy/get_creditors.php',
            type: 'GET',
            data: { case_no: caseNo },
            beforeSend: function() {
                if (typeof window.showLoading === 'function') {
                    window.showLoading();
                }
            },
            success: function(response) {
                if (response.success) {
                    $('#creditorList').empty();
                    creditorCount = 0;
                    
                    if (response.data && response.data.length > 0) {
						response.data.forEach(function(creditor) {
							const count = addNewCreditor(creditor);
							updateGuarantorCount(count); // 보증인 수 업데이트
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
                if (typeof window.hideLoading === 'function') {
                    window.hideLoading();
                }
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
    window.loadCreditors = loadCreditors;
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