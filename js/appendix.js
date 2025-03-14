$(document).ready(function() {
    // 초기화
    initializeFormEvents();
    loadSavedData();

    // 목적물 선택 버튼 이벤트
    $('#propertySelectBtn').on('click', function() {
        $.ajax({
            url: '../../api/application_recovery/get_assets.php',
            method: 'GET',
            data: { case_no: currentCaseNo },
            success: function(response) {
                let data = response;
                // response가 이미 객체인 경우 JSON.parse 생략
                if (typeof response === 'string') {
                    try {
                        data = JSON.parse(response);
                    } catch (e) {
                        console.error('목적물 데이터 파싱 오류:', e);
                        return;
                    }
                }
                
                if (data.success) {
                    console.log(data.data)
                    showPropertySelector(data.data);
                } else {
                    console.error('목적물 데이터 로드 실패:', data.message || 'Unknown error');
                }
            },
            error: function(xhr) {
                console.error('목적물 데이터 요청 오류:', xhr.responseText);
            }
        });
    });

    // 숫자 입력 필드 이벤트
    $('.number-input').on('input', function() {
        formatNumber($(this));
    });

    // 평가비율 입력 이벤트
    $('#evaluation_rate').on('input', function() {
        let value = $(this).val();
        // 숫자와 소수점만 허용
        value = value.replace(/[^\d.]/g, '');
        // 소수점이 2개 이상 입력되는 것 방지
        const decimalParts = value.split('.');
        if (decimalParts.length > 2) {
            value = decimalParts[0] + '.' + decimalParts.slice(1).join('');
        }
        // 소수점 이하 2자리로 제한
        if (decimalParts.length > 1) {
            value = decimalParts[0] + '.' + decimalParts[1].slice(0, 2);
        }
        $(this).val(value);
    });

    // 저장 버튼 이벤트
    $('#saveButton').on('click', function() {
        saveForm();
    });

    // 삭제 버튼 이벤트
    $('#deleteButton').on('click', function() {
        if (confirm('정말 삭제하시겠습니까?')) {
            deleteForm();
        }
    });

    // 닫기 버튼 이벤트
    $('#closeButton').on('click', function() {
        window.close();
    });
});

// 폼 이벤트 초기화
function initializeFormEvents() {
    // 숫자 입력 필드 초기화
    $('.number-input').each(function() {
        formatNumber($(this));
    });
}

// 숫자 포맷팅 함수
function formatNumber(input) {
    let value = input.val().replace(/[^\d]/g, '');
    if (value) {
        value = Number(value).toLocaleString('ko-KR');
        input.val(value);
    }
}

// 기존 데이터 로드
function loadSavedData() {
    if (!currentCaseNo || !current_creditor_count) {
        console.error('필수 파라미터 누락: case_no 또는 creditor_count');
        return;
    }

    $.ajax({
        url: '../../api/application_recovery/get_appendix.php',
        method: 'GET',
        data: {
            case_no: currentCaseNo,
            mortgage_no: current_creditor_count
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        fillFormData(data.data[0]);
                    } else {
                        clearForm();
                    }
                } else {
                    console.error('데이터 로드 실패:', data.message);
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

// 목적물 선택 창 열기
function showPropertySelector(properties) {
    const popupWindow = window.open(
        `../../api/application_recovery/property_select.php?case_no=${currentCaseNo}&count=${current_creditor_count}`,
        'propertySelect',
        'width=1200,height=400'
    );

    // 선택 데이터 수신 이벤트
    window.addEventListener('message', function(event) {
        if (event.data.type === 'propertySelected') {
            const property = properties.find(p => p.id === event.data.propertyId);
            if (property) {
                fillPropertyData(property);
                appendDataToMainForm(property);
            }
        }
    });
}

// 선택한 데이터를 메인 폼에 자동 입력
function appendDataToMainForm(property) {
    const formData = {
        bond_number: property.bond_number || '',
        creditor_name: property.creditor_name || '',
        property_detail: property.property_detail || '',
        expected_value: property.expected_value || '',
        evaluation_rate: property.evaluation_rate || '',
        max_claim: property.max_claim || '',
        registration_date: property.registration_date || ''
    };

    // 데이터를 메인 폼에 채우기
    for (const [key, value] of Object.entries(formData)) {
        const $input = $(`#${key}`);
        if ($input.length) {
            if ($input.hasClass('number-input') && value) {
                $input.val(Number(value).toLocaleString('ko-KR'));
            } else {
                $input.val(value);
            }
        }
    }

    // 데이터 저장 요청
    $.ajax({
        url: '../../api/application_recovery/save_appendix.php',
        method: 'POST',
        data: {
            case_no: currentCaseNo,
            creditor_count: current_creditor_count,
            ...formData
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.status === 'success') {
                    alert('목적물 정보가 저장되었습니다.');
                } else {
                    console.error('저장 실패:', result);
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

// 폼 데이터 채우기
function fillFormData(data) {
    
    // 상단 테이블 데이터 채우기
    for (const [key, value] of Object.entries(data)) {
        const $input = $(`#${key}`);
        if ($input.length) {
            if ($input.hasClass('number-input')) {
                $input.val(Number(value).toLocaleString('ko-KR'));
            } else {
                $input.val(value);
            }
        }
    }

    // 하단 테이블 데이터 채우기
    const bottomFields = [
        'property_detail',
        'expected_value', 
        'evaluation_rate',
        'max_claim',
        'registration_date',
        'secured_expected_claim',
        'unsecured_remaining_claim',
        'rehabilitation_secured_claim'
    ];

    bottomFields.forEach(field => {
        const $input = $(`#${field}`);
        if ($input.length) {
            const value = data[field] || '';
            
            if ($input.hasClass('number-input')) {
                if (value) {
                    const numValue = typeof value === 'string' ?
                        parseInt(value.replace(/,/g, '')) :
                        value;
                    $input.val(numValue.toLocaleString('ko-KR'));
                } else {
                    $input.val('');
                }
            } else {
                $input.val(value);
            }
        } else {
            console.error(`Input not found: #${field}`);
        }
    });
}

// 목적물 데이터 채우기
function fillPropertyData(property) {
    $('#property_detail').val(property.detail);
    if (property.expected_value) {
        $('#expected_value').val(Number(property.expected_value).toLocaleString('ko-KR'));
    }
    if (property.evaluation_rate) {
        $('#evaluation_rate').val(property.evaluation_rate);
    }
}

// 폼 저장
function saveForm() {
        const getIntValue = (selector) => {
            const val = $(selector).val();
            return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : null;
        };

        const formData = {
            property_detail: $('#property_detail').val() || '',
            expected_value: getIntValue('#expected_value'),
            evaluation_rate: getIntValue('#evaluation_rate'),
            max_claim: getIntValue('#max_claim'),
            registration_date: $('#registration_date').val() || '',
            secured_expected_claim: getIntValue('#secured_expected_claim'),
            unsecured_remaining_claim: getIntValue('#unsecured_remaining_claim'),
            rehabilitation_secured_claim: getIntValue('#rehabilitation_secured_claim')
        };

    $.ajax({
        url: '../../api/application_recovery/save_appendix.php',
        method: 'POST',
        data: {
            case_no: currentCaseNo,
            creditor_count: current_creditor_count,
            mortgage_no: $('#mortgageNo').val() || 1,  // 기본값 1 설정
            ...formData
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.status === 'success') {
                    alert(result.message || '저장되었습니다.');
                    window.opener.postMessage({
                        type: 'appendixSaved', 
                        creditorCount: current_creditor_count
                    }, '*');
                    location.reload();
                } else {
                    console.log('저장 실패 응답:', result);
                    alert('저장 중 오류가 발생했습니다.');
                }
            } catch (e) {
                console.error('저장 오류:', e);
                alert('저장 처리 중 오류가 발생했습니다.');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.error('서버 오류:', response);
                if (response.message) {
                    alert(`서버 오류: ${response.message}\nSQL: ${response.sql}`);
                } else {
                    alert('알 수 없는 오류가 발생했습니다.');
                }
            } catch (e) {
                console.error('오류 처리 실패:', e);
                alert('알 수 없는 오류가 발생했습니다.');
            }
        }
    });
}

// 폼 초기화
function clearForm() {
    $('input[type="text"]').val('');
    $('input[type="number"]').val('');
    $('.number-input').val('');
    $('#property_detail').val('');
    $('#expected_value').val('');
    $('#evaluation_rate').val('');
    $('#max_claim').val('');
    $('#registration_date').val('');
    $('#secured_expected_claim').val('');
    $('#unsecured_remaining_claim').val('');
    $('#rehabilitation_secured_claim').val('');
}

// 계산 버튼 이벤트 핸들러
document.getElementById('calculateButton').onclick = function() {
    const getIntValue = (selector) => {
        const val = $(selector).val();
        return val && val.trim() !== '' ? parseInt(val.replace(/,/g, '')) : null;
    };

    // 입력 값 가져오기
    const expectedValue = getIntValue('#expected_value');
    const evaluationRate = getIntValue('#evaluation_rate') || 70; // 기본값 70%
    const maxClaim = getIntValue('#max_claim');
    // URL에서 capital과 interest 값 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    const capital = parseInt(urlParams.get('capital')) || 0;
    const interest = parseInt(urlParams.get('interest')) || 0;

    if (!expectedValue || !maxClaim || !capital || !interest) {
        alert('필수 입력값이 없습니다.');
        return;
    }

    // 계산 로직
    const securedExpectedClaim = Math.min(
        maxClaim,
        Math.floor((expectedValue * evaluationRate) / 100)
    );

    const unsecuredRemainingClaim = Math.max(
        0,
        Math.min(
            capital + interest,
            maxClaim - securedExpectedClaim
        )
    );

    const rehabilitationSecuredClaim = Math.min(
        securedExpectedClaim,
        capital + interest
    );

    // 결과 값 설정
    $('#secured_expected_claim').val(securedExpectedClaim.toLocaleString('ko-KR'));
    $('#unsecured_remaining_claim').val(unsecuredRemainingClaim.toLocaleString('ko-KR'));
    $('#rehabilitation_secured_claim').val(rehabilitationSecuredClaim.toLocaleString('ko-KR'));
};

// 폼 삭제
function deleteForm() {
    $.ajax({
        url: '../../api/application_recovery/delete_appendix.php',
        method: 'POST',
        data: {
            case_no: currentCaseNo,
            creditor_count: current_creditor_count,
            mortgage_no: $('#mortgageNo').val()
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('삭제되었습니다.');
                    window.opener.postMessage({
                        type: 'appendixDeleted',
                        creditorCount: current_creditor_count
                    }, '*');
                    window.close();
                } else {
                    alert('삭제 중 오류가 발생했습니다.');
                }
            } catch (e) {
                console.error('삭제 오류:', e);
                alert('삭제 처리 중 오류가 발생했습니다.');
            }
        },
        error: function() {
            alert('서버와의 통신 중 오류가 발생했습니다.');
        }
    });
}
