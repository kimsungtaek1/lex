$(document).ready(function() {
    // 페이지 로드 시 각 파일의 첫 번째 결과(텍스트)를 자동으로 로드
    $('.result-card').each(function() {
        const idx = $(this).attr('id').split('-').pop();
        const textBtn = $(this).find('.text-view-btn');
        if (textBtn.length) {
            const path = textBtn.data('path');
            loadTextResult(path, idx);
        }
    });
    
    // 텍스트 결과 보기 버튼
    $(document).on('click', '.text-view-btn', function() {
        const idx = $(this).data('idx');
        const path = $(this).data('path');
        
        // 활성 버튼 스타일 변경
        $(this).closest('.list-group').find('.list-group-item').removeClass('active');
        $(this).addClass('active');
        
        // 뷰어 전환
        $(`.content-viewer[class*="-viewer-${idx}"]`).hide();
        $(`.text-viewer-${idx}`).show();
        
        // 내용이 없으면 로드
        if ($(`.text-viewer-${idx} .text-content`).html().includes('로딩 중')) {
            loadTextResult(path, idx);
        }
    });
    
    // 테이블 결과 보기 버튼
    $(document).on('click', '.table-view-btn', function() {
        const idx = $(this).data('idx');
        const path = $(this).data('path');
        
        // 활성 버튼 스타일 변경
        $(this).closest('.list-group').find('.list-group-item').removeClass('active');
        $(this).addClass('active');
        
        // 뷰어 전환
        $(`.content-viewer[class*="-viewer-${idx}"]`).hide();
        $(`.table-viewer-${idx}`).show();
        
        // 내용이 없으면 로드
        if ($(`.table-viewer-${idx} .table-content`).html().includes('로딩 중')) {
            loadTableResult(path, idx);
        }
    });
    
    // JSON 결과 보기 버튼
    $(document).on('click', '.json-view-btn', function() {
        const idx = $(this).data('idx');
        const path = $(this).data('path');
        
        // 활성 버튼 스타일 변경
        $(this).closest('.list-group').find('.list-group-item').removeClass('active');
        $(this).addClass('active');
        
        // 뷰어 전환
        $(`.content-viewer[class*="-viewer-${idx}"]`).hide();
        $(`.json-viewer-${idx}`).show();
        
        // 내용이 없으면 로드
        if ($(`.json-viewer-${idx} .json-content`).html().includes('로딩 중')) {
            loadJsonResult(path, idx);
        }
    });
    
    // 피드백 제공 버튼
    $(document).on('click', '.provide-feedback-btn', function() {
        const fileId = $(this).data('file-id');
        $(`#feedback-form-${fileId}`).slideToggle();
    });
    
    // 피드백 취소 버튼
    $(document).on('click', '.cancel-feedback-btn', function() {
        $(this).closest('.feedback-form').slideUp();
    });
    
    // 피드백 제출 버튼
    $(document).on('click', '.submit-feedback-btn', function() {
        const fileId = $(this).data('file-id');
        const jobId = $(this).data('job-id');
        const form = $(`#feedback-form-${fileId}`);
        
        const fieldName = form.find('.field-name').val();
        const originalText = form.find('.original-text').val();
        const correctedText = form.find('.corrected-text').val();
        
        if (!fieldName || !originalText || !correctedText) {
            showAlert('warning', '모든 필드를 입력해주세요.');
            return;
        }
        
        const feedbackData = {
            job_id: jobId,
            file_id: fileId,
            corrections: [
                {
                    type: 'field',
                    field: fieldName,
                    original: originalText,
                    corrected: correctedText
                }
            ]
        };
        
        // 피드백 저장 AJAX 요청
        $.ajax({
            url: 'ajax_save_feedback.php',
            type: 'POST',
            data: JSON.stringify(feedbackData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', '피드백이 저장되었습니다. 향후 OCR 인식률 향상에 사용됩니다.');
                    form.slideUp();
                    form.find('.field-name, .original-text, .corrected-text').val('');
                } else {
                    showAlert('danger', '피드백 저장 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', '요청 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 텍스트 결과 로드 함수
    function loadTextResult(path, idx) {
        $(`.text-viewer-${idx} .text-content`).html(`
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩중...</span>
            </div>
            <span class="ms-2">텍스트 결과 로딩 중...</span>
        `);
        
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: path },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // HTML 안전 처리 및 줄바꿈 유지
                    const formattedText = processTextForDisplay(response.content);
                    $(`.text-viewer-${idx} .text-content`).html(formattedText);
                } else {
                    $(`.text-viewer-${idx} .text-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function() {
                $(`.text-viewer-${idx} .text-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다.
                    </div>
                `);
            }
        });
    }
    
    // JSON 결과 로드 함수
    function loadJsonResult(path, idx) {
        $(`.json-viewer-${idx} .json-content`).html(`
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩중...</span>
            </div>
            <span class="ms-2">JSON 데이터 로딩 중...</span>
        `);
        
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: path },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    try {
                        const jsonObj = JSON.parse(response.content);
                        const formattedJson = JSON.stringify(jsonObj, null, 4);
                        $(`.json-viewer-${idx} .json-content`).html(escapeHtml(formattedJson));
                    } catch (e) {
                        $(`.json-viewer-${idx} .json-content`).html(escapeHtml(response.content));
                    }
                } else {
                    $(`.json-viewer-${idx} .json-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function() {
                $(`.json-viewer-${idx} .json-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다.
                    </div>
                `);
            }
        });
    }
    
    // 테이블 결과 로드 함수
    function loadTableResult(path, idx) {
        $(`.table-viewer-${idx} .table-content`).html(`
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩중...</span>
            </div>
            <span class="ms-2">테이블 결과 로딩 중...</span>
        `);
        
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: path, raw: true },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(`.table-viewer-${idx} .table-content`).html(response.content);
                } else {
                    $(`.table-viewer-${idx} .table-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function() {
                $(`.table-viewer-${idx} .table-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다.
                    </div>
                `);
            }
        });
    }
    
    // 텍스트 내용 처리 및 표시 개선
    function processTextForDisplay(text) {
        if (!text) return '';
        
        // HTML 이스케이프
        const escaped = escapeHtml(text);
        
        // 줄바꿈 유지
        const withLineBreaks = escaped.replace(/\n/g, '<br>');
        
        // 키-값 패턴 강조
        const highlighted = withLineBreaks.replace(
            /([가-힣\w]+)\s*:\s*([^<\n]+)/g, 
            '<span class="text-primary fw-bold">$1</span>: <span class="text-dark">$2</span>'
        );
        
        // 날짜 형식 강조
        const withDates = highlighted.replace(
            /(\d{4}-\d{2}-\d{2}|\d{4}\.\d{2}\.\d{2}|\d{4}\/\d{2}\/\d{2})/g,
            '<span class="text-success">$1</span>'
        );
        
        // 금액 형식 강조
        const withAmounts = withDates.replace(
            /(\d{1,3}(,\d{3})*(\.\d+)?)\s*(원|₩)/g,
            '<span class="text-danger">$1$4</span>'
        );
        
        return withAmounts;
    }
    
    // HTML 이스케이프 함수
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 알림 표시 함수
    function showAlert(type, message) {
        const alertDiv = $(`
            <div class="alert alert-${type} alert-dismissible fade show">
                <i class="bi bi-info-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('.result-container').prepend(alertDiv);
        
        // 5초 후 자동 닫기
        setTimeout(function() {
            alertDiv.alert('close');
        }, 5000);
    }

    // 파일 목록에서 결과 보기 버튼
    $(document).on('click', '.view-result-btn', function() {
        const fileId = $(this).data('file-id');
        const textPath = $(this).data('text-path');
        const jsonPath = $(this).data('json-path');
        const tablePath = $(this).data('table-path');
        
        // 텍스트 결과 로드
        if (textPath) {
            // 현재 탭에서 결과 보기
            $('#results-tab').tab('show');
            
            // 해당 파일의 결과 카드 찾기
            const resultCards = $('.result-card');
            resultCards.each(function() {
                const idx = $(this).attr('id').split('-').pop();
                const resultFileId = $(this).find('.submit-feedback-btn').data('file-id');
                
                if (resultFileId == fileId) {
                    // 텍스트 버튼 클릭 이벤트 트리거
                    $(this).find('.text-view-btn').click();
                    
                    // 해당 결과 카드로 스크롤
                    $('html, body').animate({
                        scrollTop: $(this).offset().top - 100
                    }, 500);
                    
                    return false; // 루프 종료
                }
            });
        }
    });
});