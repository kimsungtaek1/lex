// 채권자 관리 스크립트
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
            calculateTotals();
        });
        // 페이지 로드 시 기존 값들에 대한 포맷팅
        $('.number-input').each(function() {
            if (this.value) {
                formatNumber(this);
            }
        });

        // 날짜 변경 시 자동 계산
        $(document).on('change', '[id^=calculationDate]', function() {
            const count = this.id.replace('calculationDate', '');
            updateCalculations(count);
        });

        // 담보/무담보 체크박스 변경 시
        $(document).on('change', '[id^=priorityPayment]', function() {
            calculateTotals();
        });
        
        // 주소 검색 버튼 이벤트
        $(document).on('click', '.btn-search.address-search', function() {
            const count = $(this).closest('.creditor-box').data('count');
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
        let newBox = creditorBoxTemplate.replace(/{count}/g, creditorCount);
        $('#creditorList').append(newBox);
        
        if (data) {
            fillCreditorData(creditorCount, data);
        }
        
        initCreditorFormListeners(creditorCount);
        initializeCreditorForm(creditorCount);
        loadCreditorSpecificData(creditorCount);
        
        return creditorCount;
    }

    // 채권자 데이터 채우기
    function fillCreditorData(count, data) {
        $(`#entityType${count}`).val(data.entity_type);
        $(`#financialInstitution${count}`).val(data.financial_institution);
        $(`#address${count}`).val(data.address);
        $(`#phone${count}`).val(formatPhoneNumber(data.phone));
        $(`#fax${count}`).val(data.fax);
        $(`#principal${count}`).val(numberWithCommas(data.principal));
        $(`#principalCalculation${count}`).val(data.principal_calculation);
        $(`#interest${count}`).val(numberWithCommas(data.interest));
        $(`#interestCalculation${count}`).val(data.interest_calculation);
        $(`#defaultRate${count}`).val(data.default_rate);
        $(`#claimReason${count}`).val(data.claim_reason);
        $(`#claimContent${count}`).val(data.claim_content);
        $(`#priorityPayment${count}`).prop('checked', data.priority_payment == 1);
        $(`#undeterminedClaim${count}`).prop('checked', data.undetermined_claim == 1);
        $(`#pensionDebt${count}`).prop('checked', data.pension_debt == 1);
        $(`#mortgageRestructuring${count}`).prop('checked', data.mortgage_restructuring == 1);
    }

    // 채권자 폼 리스너 초기화
    function initCreditorFormListeners(count) {
        // 자동입력 버튼
        $(document).on('click', `.btn.auto-fill[data-count="${count}"]`, function() {
            const count = $(this).data('count');
            const principal = parseFloat($(`#principal${count}`).val().replace(/,/g, '')) || 0;
            const interest = parseFloat($(`#interest${count}`).val().replace(/,/g, '')) || 0;
            const defaultRate = $(`#defaultRate${count}`).val();
            
            const calculationDate = $(`#principalCalculation${count}`).val().match(/\d{4}\.\d{2}\.\d{2}/);
            if (!calculationDate) {
                alert('채권현재액 산정근거에 유효한 날짜가 없습니다.');
                return;
            }
            
            const nextDay = addOneDay(calculationDate[0]);
            const content = `원리금 ${numberWithCommas(principal + interest)}원 및 그 중 원금 ${numberWithCommas(principal)}원에 대한 ${nextDay}부터 완제일까지 연 ${defaultRate}%의 비율에 의한 지연손해금`;
            
            $(`#claimContent${count}`).val(content);
        });

        $(`#openAppendix${count}`).on('click', function() {
            openClaimWindow(count, 'appendix');
        });

        $(`#openOtherClaim${count}`).on('click', function() {
            openClaimWindow(count, 'disputed');
        });

        $(`#openGuaranteedDebt${count}`).on('click', function() {
            openClaimWindow(count, 'guaranteed');
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

    // 채권자 폼 초기화
    function initializeCreditorForm(count) {
        $(`#principal${count}, #interest${count}`).on('input', function() {
            formatNumber($(this));
            calculateTotals();
        });
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
            entity_type: $(`#entityType${count}`).val(),
            financial_institution: $(`#financialInstitution${count}`).val(),
            address: $(`#address${count}`).val(),
            phone: $(`#phone${count}`).val().replace(/-/g, ''),
            fax: $(`#fax${count}`).val(),
            principal: $(`#principal${count}`).val().replace(/,/g, ''),
            principal_calculation: $(`#principalCalculation${count}`).val(),
            interest: $(`#interest${count}`).val().replace(/,/g, ''),
            interest_calculation: $(`#interestCalculation${count}`).val(),
            default_rate: $(`#defaultRate${count}`).val(),
            claim_reason: $(`#claimReason${count}`).val(),
            claim_content: $(`#claimContent${count}`).val(),
            priority_payment: $(`#priorityPayment${count}`).is(':checked') ? 1 : 0,
            undetermined_claim: $(`#undeterminedClaim${count}`).is(':checked') ? 1 : 0,
            pension_debt: $(`#pensionDebt${count}`).is(':checked') ? 1 : 0,
            mortgage_restructuring: $(`#mortgageRestructuring${count}`).is(':checked') ? 1 : 0
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
            url: 'api/application_recovery/delete_creditor.php',
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
                    calculateTotals();
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
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 날짜 포맷팅
    function formatDate(date) {
        return date.getFullYear() + '.' + 
               String(date.getMonth() + 1).padStart(2, '0') + '.' + 
               String(date.getDate()).padStart(2, '0');
    }

    // 하루 추가
    function addOneDay(dateString) {
        const parts = dateString.split('.');
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        date.setDate(date.getDate() + 1);
        return formatDate(date);
    }

    // 계산서 업데이트
    function updateCalculations(count) {
        const date = $(`#calculationDate${count}`).val();
        if (!date) return;
        
        const formattedDate = formatDate(new Date(date));
        $(`#principalCalculation${count}`).val(`부채증명서 참조(산정기준일: ${formattedDate})`);
        $(`#interestCalculation${count}`).val(`부채증명서 참조(산정기준일: ${formattedDate})`);
    }

    // 금액 합계 계산
    function calculateTotals() {
        let totalSum = 0;
        let securedSum = 0;
        let unsecuredSum = 0;

        $('.creditor-box').each(function() {
            const count = $(this).data('count');
            const principal = parseFloat($(`#principal${count}`).val().replace(/,/g, '')) || 0;
            const interest = parseFloat($(`#interest${count}`).val().replace(/,/g, '')) || 0;
            const total = principal + interest;

            if ($(`#priorityPayment${count}`).prop('checked')) {
                securedSum += total;
            } else {
                unsecuredSum += total;
            }

            totalSum += total;
        });

        $('#totalSum').text(numberWithCommas(totalSum) + '원');
        $('#securedSum').text(numberWithCommas(securedSum) + '원');
        $('#unsecuredSum').text(numberWithCommas(unsecuredSum) + '원');

        checkLimits(securedSum, unsecuredSum);
    }

    // 한도 체크
    function checkLimits(securedSum, unsecuredSum) {
        const securedLimit = 1500000000; // 15억
        const unsecuredLimit = 1000000000; // 10억

        if (securedSum >= securedLimit || unsecuredSum >= unsecuredLimit) {
            alert('주의: 담보부채무 15억원 이상 또는 무담보채무 10억원 이상은 개인회생신청 대상이 아닙니다.');
        }
    }

    // 채권내용 자동입력
    function autoFillClaimContent(count) {
        const principal = parseFloat($(`#principal${count}`).val().replace(/,/g, '')) || 0;
        const interest = parseFloat($(`#interest${count}`).val().replace(/,/g, '')) || 0;
        const defaultRate = $(`#defaultRate${count}`).val();
        
        const calculationDate = $(`#principalCalculation${count}`).val().match(/\d{4}\.\d{2}\.\d{2}/);
        if (!calculationDate) {
            alert('채권현재액 산정근거에 유효한 날짜가 없습니다.');
            return;
        }
        
        const nextDay = addOneDay(calculationDate[0]);
        const content = `원리금 ${numberWithCommas(principal + interest)}원 및 그 중 원금 ${numberWithCommas(principal)}원에 대한 ${nextDay}부터 완제일까지 연 ${defaultRate}%의 비율에 의한 지연손해금`;
        
        $(`#claimContent${count}`).val(content);
    }

    // 금융기관 정보 채우기
    function fillFinancialInstitution(institution, count) {
        $(`#financialInstitution${count}`).val(institution.name);
        $(`#address${count}`).val(institution.address);
        $(`#phone${count}`).val(formatPhoneNumber(institution.phone));
        $(`#fax${count}`).val(institution.fax);
    }
    
    // 채권 관련 창 열기 통합 함수
    function openClaimWindow(count, claimType) {
        if (!currentCaseNo) {
            alert('사건을 먼저 선택해주세요.');
            return;
        }

        // 채권자 정보가 저장되었는지 확인
        $.ajax({
            url: 'api/application_recovery/check_creditor_exists.php',
            type: 'GET',
            data: {
                case_no: currentCaseNo,
                creditor_count: count
            },
            success: function(response) {
                if (response.exists) {
                    // 채권자 정보가 존재하면 해당 창 열기
                    const width = 1200;
                    const height = 750;
                    const left = (screen.width - width) / 2;
                    const top = (screen.height - height) / 2;
                    
                    let pageUrl = '';
                    let windowName = '';
                    
                    // 채권 유형에 따라 페이지와 창 이름 설정
                    switch(claimType) {
                        case 'appendix':
                            pageUrl = `api/application_recovery/appendix.php?case_no=${currentCaseNo}&count=${count}`;
                            windowName = 'AppendixWindow';
                            // 필요시 전달할 파라미터 추가
                            const capital = $(`#principal${count}`).val().replace(/,/g, '');
                            const interest = $(`#interest${count}`).val().replace(/,/g, '');
                            pageUrl += `&capital=${capital}&interest=${interest}`;
                            break;
                        case 'disputed':
                            pageUrl = `api/application_recovery/other_claim.php?case_no=${currentCaseNo}&creditor_count=${count}`;
                            windowName = 'DisputedClaimWindow';
                            break;
                        case 'assigned':
                            pageUrl = `api/application_recovery/assigned_claim.php?case_no=${currentCaseNo}&creditor_count=${count}`;
                            windowName = 'AssignedClaimWindow';
                            break;
                        case 'otherDebt':
                            const principal = parseFloat($(`#principal${count}`).val().replace(/,/g, '')) || 0;
                            pageUrl = `api/application_recovery/other_debt.php?case_no=${currentCaseNo}&creditor_count=${count}&principal=${principal}`;
                            windowName = 'OtherDebtWindow';
                            break;
                        case 'undetermined':
                            pageUrl = `api/application_recovery/undetermined_claim.php?case_no=${currentCaseNo}&creditor_count=${count}`;
                            windowName = 'UndeterminedClaimWindow';
                            break;
                        case 'guaranteed':
                            pageUrl = `api/application_recovery/guaranteed_debt.php?case_no=${currentCaseNo}&creditor_count=${count}`;
                            windowName = 'GuaranteedDebtWindow';
                            break;
                        default:
                            alert('유효하지 않은 채권 유형입니다.');
                            return;
                    }
                    
                    window.open(
                        pageUrl,
                        windowName,
                        `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
                    );
                } else {
                    // 채권자 정보가 없으면 저장 요청
                    alert('채권자 정보를 먼저 저장해주세요.');
                    $(`#saveCreditor${count}`).focus();
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // 부속서류 창 열기 (기존 함수 - 통합 함수 사용)
    function openAppendixWindow(count) {
        openClaimWindow(count, 'appendix');
    }

    // 다툼있는 채권 창 열기 (기존 함수 - 통합 함수 사용)
    function openOtherClaimWindow(count) {
        openClaimWindow(count, 'disputed');
    }

    // 보증인채무 창 열기 (기존 함수 - 통합 함수 사용)
    function openGuaranteedDebtWindow(count) {
        openClaimWindow(count, 'guaranteed');
    }

	// 메시지 이벤트 리스너
	window.addEventListener('message', function(event) {
		console.log('수신된 메시지:', event.data);
		
		// 부속서류 저장 이벤트 처리
		if (event.data.type === 'appendixSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			const clearOthers = event.data.clearOthers || false;
			
			// 해당 채권자의 별제권부채권 버튼 색상 변경
			if (hasData) {
				$(`#openAppendix${count}`).addClass('btn-appendix-saved');
			}
			
			// 다른 채권 유형 버튼 색상 원래대로 되돌리기
			if (clearOthers) {
				$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
				$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
				$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
			}
			
			// 금액 합계 재계산
			calculateTotals();
		}
		
		// 부속서류 삭제 이벤트 처리
		if (event.data.type === 'appendixDeleted') {
			const count = event.data.creditorCount;
			
			// 해당 채권자의 별제권부채권 버튼 색상 원래대로
			$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
			
			// 금액 합계 재계산
			calculateTotals();
		}
		
		// 다툼있는 채권 저장 이벤트 처리
		if (event.data.type === 'otherClaimSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			const clearOthers = event.data.clearOthers || false;
			
			// 해당 채권자의 다툼있는 채권 버튼 색상 변경
			if (hasData) {
				$(`#openOtherClaim${count}`).addClass('btn-other-claim-saved');
			}
			
			// 다른 채권 유형 버튼 색상 원래대로 되돌리기
			if (clearOthers) {
				$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
				$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
				$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
			}
		}
		
		// 다툼있는 채권 삭제 이벤트 처리
		if (event.data.type === 'otherClaimDeleted') {
			const count = event.data.creditorCount;
			
			// 해당 채권자의 다툼있는 채권 버튼 색상 원래대로
			$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
		}
		
		// 전부명령된 채권 저장 이벤트 처리
		if (event.data.type === 'assignedClaimSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			const clearOthers = event.data.clearOthers || false;
			
			if (hasData) {
				$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).addClass('btn-claim-saved');
			}
			
			// 다른 채권 유형 버튼 색상 원래대로 되돌리기
			if (clearOthers) {
				$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
				$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
				$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
			}
		}
		
		// 전부명령된 채권 삭제 이벤트 처리
		if (event.data.type === 'assignedClaimDeleted') {
			const count = event.data.creditorCount;
			
			$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
		}
		
		// 기타채무 저장 이벤트 처리
		if (event.data.type === 'otherDebtSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			const clearOthers = event.data.clearOthers || false;
			
			if (hasData) {
				$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).addClass('btn-claim-saved');
			}
			
			// 다른 채권 유형 버튼 색상 원래대로 되돌리기
			if (clearOthers) {
				$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
				$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
				$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
			}
		}
		
		// 기타채무 삭제 이벤트 처리
		if (event.data.type === 'otherDebtDeleted') {
			const count = event.data.creditorCount;
			
			$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
		}
		
		// 기타미확정채권 저장 이벤트 처리
		if (event.data.type === 'undeterminedClaimSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			
			if (hasData) {
				$(`button[onclick="openClaimWindow(${count}, 'undetermined')"]`).addClass('btn-claim-saved');
			}
			
			// 개수 새로고침
			loadUndeterminedClaimCount(count);
		}
		
		// 기타미확정채권 삭제 이벤트 처리
		if (event.data.type === 'undeterminedClaimDeleted') {
			const count = event.data.creditorCount;
			
			$(`button[onclick="openClaimWindow(${count}, 'undetermined')"]`).removeClass('btn-claim-saved');
			
			// 개수 새로고침
			loadUndeterminedClaimCount(count);
		}
		
		// 보증인채무 저장 이벤트 처리
		if (event.data.type === 'guaranteedDebtSaved') {
			const count = event.data.creditorCount;
			const hasData = event.data.hasData;
			
			if (hasData) {
				$(`button[onclick="openClaimWindow(${count}, 'guaranteed')"]`).addClass('btn-claim-saved');
			}
			
			// 개수 새로고침
			loadGuaranteedDebtCount(count);
		}
		
		// 보증인채무 삭제 이벤트 처리
		if (event.data.type === 'guaranteedDebtDeleted') {
			const count = event.data.creditorCount;
			
			$(`button[onclick="openClaimWindow(${count}, 'guaranteed')"]`).removeClass('btn-claim-saved');
			
			// 개수 새로고침
			loadGuaranteedDebtCount(count);
		}
	});
	
	// 모든 채권 유형 상태 확인 및 버튼 색상 업데이트
	function updateAllClaimButtonsStatus(count) {
		// 별제권부채권 확인
		$.ajax({
			url: 'api/application_recovery/get_appendix_data.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					$(`#openAppendix${count}`).addClass('btn-appendix-saved');
				} else {
					$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
				}
			}
		});
		
		// 다툼있는 채권 확인
		$.ajax({
			url: 'api/application_recovery/get_other_claims.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					$(`#openOtherClaim${count}`).addClass('btn-other-claim-saved');
				} else {
					$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
				}
			}
		});
		
		// 전부명령된 채권 확인
		$.ajax({
			url: 'api/application_recovery/get_assigned_claims.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).addClass('btn-claim-saved');
				} else {
					$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
				}
			}
		});
		
		// 기타채무 확인
		$.ajax({
			url: 'api/application_recovery/get_other_debts.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				const data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data.success && data.data && data.data.length > 0) {
					$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).addClass('btn-claim-saved');
				} else {
					$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
				}
			}
		});
	}



	// 부속정보 로드
	function loadCreditorSpecificData(count) {
		loadUndeterminedClaimCount(count);
		loadGuaranteedDebtCount(count);
		
		// 별제권부채권 데이터 확인 및 버튼 색상 설정
		checkAppendixExists(count);
		// 다툼있는 채권 데이터 확인 및 버튼 색상 설정
		checkOtherClaimExists(count);
		// 전부명령된 채권 데이터 확인 및 버튼 색상 설정
		checkAssignedClaimExists(count);
		// 기타채무 데이터 확인 및 버튼 색상 설정
		checkOtherDebtExists(count);
		// 기타미확정채권 데이터 확인 및 버튼 색상 설정
		checkUndeterminedClaimExists(count);
		// 보증인채무 데이터 확인 및 버튼 색상 설정
		checkGuaranteedDebtExists(count);
	}

	// 별제권부채권 데이터 존재 여부 확인
	function checkAppendixExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_appendix_data.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`#openAppendix${count}`).addClass('btn-appendix-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`#openAppendix${count}`).removeClass('btn-appendix-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

	// 다툼있는 채권 데이터 존재 여부 확인
	function checkOtherClaimExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_other_claims.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`#openOtherClaim${count}`).addClass('btn-other-claim-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`#openOtherClaim${count}`).removeClass('btn-other-claim-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

	function checkAssignedClaimExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_assigned_claims.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).addClass('btn-claim-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`button[onclick="openClaimWindow(${count}, 'assigned')"]`).removeClass('btn-claim-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

	// 기타채무 데이터 존재 여부 확인
	function checkOtherDebtExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_other_debts.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).addClass('btn-claim-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`button[onclick="openClaimWindow(${count}, 'otherDebt')"]`).removeClass('btn-claim-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

	// 기타미확정채권 데이터 존재 여부 확인
	function checkUndeterminedClaimExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_undetermined_claims.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`button[onclick="openClaimWindow(${count}, 'undetermined')"]`).addClass('btn-claim-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`button[onclick="openClaimWindow(${count}, 'undetermined')"]`).removeClass('btn-claim-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

	// 보증인채무 데이터 존재 여부 확인
	function checkGuaranteedDebtExists(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_guaranteed_debts.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				try {
					// 응답이 문자열이면 JSON으로 파싱
					const data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.success && data.data && data.data.length > 0) {
						// 데이터가 있으면 버튼 색상 변경
						$(`button[onclick="openClaimWindow(${count}, 'guaranteed')"]`).addClass('btn-claim-saved');
					} else {
						// 데이터가 없으면 버튼 색상 원래대로
						$(`button[onclick="openClaimWindow(${count}, 'guaranteed')"]`).removeClass('btn-claim-saved');
					}
				} catch (e) {
					console.error('JSON 파싱 오류:', e);
				}
			},
			error: function(xhr) {
				console.error('서버 오류:', xhr.responseText);
			}
		});
	}

    // 보증인채무 개수 로드
    function loadGuaranteedDebtCount(count) {
        if (!currentCaseNo) return;
        
        $.ajax({
            url: 'api/application_recovery/get_guaranteed_debt_count.php',
            type: 'GET',
            data: {
                case_no: currentCaseNo,
                creditor_count: count
            },
            success: function(response) {
                if (response.success) {
                    $(`#guaranteedDebtCount${count}`).text(response.count);
                }
            }
        });
    }
	
	// 기타미확정채권 개수 로드
	function loadUndeterminedClaimCount(count) {
		if (!currentCaseNo) return;
		
		$.ajax({
			url: 'api/application_recovery/get_undetermined_claim_count.php',
			type: 'GET',
			data: {
				case_no: currentCaseNo,
				creditor_count: count
			},
			success: function(response) {
				if ($(`#undeterminedCount${count}`).length) {
				  $(`#undeterminedCount${count}`).text(response.count);
				}
			}
		});
	}

    // 채권자 설정 저장 버튼 클릭 이벤트
    $('.btn-save-settings').click(function() {
        if (!currentCaseNo) {
            alert('사건을 먼저 선택해주세요.');
            return;
        }

        const settings = {
            principal_interest_sum: $('#combinedPayment').prop('checked') ? 1 : 0,
            list_creation_date: $('#listCreationDate').val(),
            claim_calculation_date: $('#debtCalculationDate').val()
        };

        saveCreditorSettings(settings);
    });

    // 설정 저장 함수
    function saveCreditorSettings(settings) {
        $.ajax({
            url: 'api/application_recovery/save_creditors_settings.php',
            type: 'POST',
            data: {
                case_no: currentCaseNo,
                ...settings
            },
            success: function(response) {
                if (response.success) {
                    alert('설정이 저장되었습니다.');
                } else {
                    alert(response.message || '설정 저장 중 오류가 발생했습니다.');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            }
        });
    }

    // 설정 로드 함수
    function loadCreditorSettings() {
        if (!currentCaseNo) return;

        $.ajax({
            url: 'api/application_recovery/get_creditors_settings.php',
            type: 'GET',
            data: { case_no: currentCaseNo },
            success: function(response) {
                if (response.success && response.data) {
                    $('#combinedPayment').prop('checked', 
                        response.data.principal_interest_sum === '1' || 
                        response.data.principal_interest_sum === 1
                    );
                    $('#listCreationDate').val(response.data.list_creation_date);
                    $('#debtCalculationDate').val(response.data.claim_calculation_date);
                }
            }
        });
    }
    
    // 채권자 저장 기능
    $(document).on('click', '[id^="saveCreditor"]', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $creditorBox = $button.closest('.creditor-box');
        const count = $creditorBox.data('count');
        
        // 중복 클릭 방지
        if ($button.prop('disabled')) {
            return;
        }
        
        if (!currentCaseNo) {
            alert('사건을 먼저 선택해주세요.');
            return;
        }

        // 필수 입력값 검증
        const $financialInstitution = $creditorBox.find(`#financialInstitution${count}`);
        if (!$financialInstitution.val()) {
            alert('금융기관을 선택해주세요.');
            $financialInstitution.focus();
            return;
        }

        // 저장 버튼 비활성화
        $button.prop('disabled', true);

        // 데이터 수집
        const creditorData = {
            case_no: currentCaseNo,
            creditor_count: count,
            entity_type: $creditorBox.find(`#entityType${count}`).val(),
            financial_institution: $financialInstitution.val(),
            address: $creditorBox.find(`#address${count}`).val(),
            phone: $creditorBox.find(`#phone${count}`).val().replace(/-/g, ''),
            fax: $creditorBox.find(`#fax${count}`).val(),
            principal: $creditorBox.find(`#principal${count}`).val().replace(/,/g, ''),
            principal_calculation: $creditorBox.find(`#principalCalculation${count}`).val(),
            interest: $creditorBox.find(`#interest${count}`).val().replace(/,/g, ''),
            interest_calculation: $creditorBox.find(`#interestCalculation${count}`).val(),
            default_rate: $creditorBox.find(`#defaultRate${count}`).val(),
            claim_reason: $creditorBox.find(`#claimReason${count}`).val(),
            claim_content: $creditorBox.find(`#claimContent${count}`).val(),
            priority_payment: $creditorBox.find(`#priorityPayment${count}`).prop('checked') ? 1 : 0,
            undetermined_claim: $creditorBox.find(`#undeterminedClaim${count}`).prop('checked') ? 1 : 0,
            pension_debt: $creditorBox.find(`#pensionDebt${count}`).prop('checked') ? 1 : 0,
            mortgage_restructuring: $creditorBox.find(`#mortgageRestructuring${count}`).prop('checked') ? 1 : 0
        };

        // AJAX 요청
        $.ajax({
            url: './api/application_recovery/save_creditor.php',
            type: 'POST',
            data: creditorData,
            beforeSend: function() {
                window.showLoading();
            },
            success: function(response) {
                if (response.success) {
                    alert('채권자 정보가 저장되었습니다.');
                    // 금액 합계 재계산
                    calculateTotals();
                    // 부속정보 개수 업데이트
                    loadCreditorSpecificData(count);
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
                    responseText: xhr.responseText,
                    statusCode: xhr.status,
                    statusText: xhr.statusText
                });
                alert('서버 통신 중 오류가 발생했습니다.');
            },
            complete: function() {
                $button.prop('disabled', false);
                window.hideLoading();
            }
        });
    });
    
    // 채권자 데이터 불러오기
    function loadCreditors(caseNo) {
        if (!caseNo) return;
        
        $.ajax({
            url: './api/application_recovery/get_creditors.php',
            type: 'GET',
            data: { case_no: caseNo },
            beforeSend: function() {
                window.showLoading();
            },
            success: function(response) {
                if (response.success) {
                    $('#creditorList').empty();
                    creditorCount = 0;
                    
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(creditor) {
                            const newCount = addNewCreditor(creditor);
                            if (newCount) {
                                loadCreditorSpecificData(newCount);
                            }
                        });
                    } else {
                        addNewCreditor(); // 데이터가 없으면 빈 폼 추가
                    }
                    
                    calculateTotals();
                    loadCreditorSettings();
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
                window.hideLoading();
            }
        });
    }

    // 전역 함수로 노출
    window.addNewCreditor = addNewCreditor;
    window.openAppendixWindow = openAppendixWindow;
    window.openOtherClaimWindow = openOtherClaimWindow;
    window.openGuaranteedDebtWindow = openGuaranteedDebtWindow;
    window.openClaimWindow = openClaimWindow;
    window.loadCreditorSettings = loadCreditorSettings;
    window.searchAddress = searchAddress;
    window.loadCreditors = loadCreditors;
	window.updateAllClaimButtonsStatus = updateAllClaimButtonsStatus;
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
