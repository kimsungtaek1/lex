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
    
    // OCR JSON 데이터를 로드하고 챗봇 스타일로 처리하는 함수
    function loadAndProcessChatbotResults() {
        if ($('.result-card').length === 0) return;
        
        // 첫 번째 결과 카드의 JSON 경로 가져오기
        const firstCard = $('.result-card').first();
        const jsonBtn = firstCard.find('.json-view-btn');
        
        if (jsonBtn.length === 0) return;
        
        const jsonPath = jsonBtn.data('path');
        if (!jsonPath) return;
        
        // 로딩 표시
        $('#chatbot-container .chat-loading').show();
        $('#chatbot-container .chat-messages').hide();
        
        // JSON 데이터 로드
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: jsonPath },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    try {
                        // JSON 문자열 전처리 - 잘못된 형식 수정 시도
                        let jsonContent = response.content;
                        
                        // 줄바꿈이나 불필요한 공백 제거
                        jsonContent = jsonContent.replace(/\n\s*/g, ' ').trim();
                        
                        // 대체 방법: 텍스트 파일 로드를 시도합니다
                        if (!isValidJSON(jsonContent)) {
                            // JSON 파싱에 실패했으므로 텍스트 기반 결과로 대체
                            showTextBasedResults(firstCard);
                            return;
                        }
                        
                        const ocrData = JSON.parse(jsonContent);
                        generateChatbotMessages(ocrData);
                    } catch (e) {
                        console.error("JSON 파싱 오류:", e);
                        // 텍스트 기반 결과로 대체
                        showTextBasedResults(firstCard);
                    }
                } else {
                    showChatbotError('파일을 로드할 수 없습니다: ' + response.message);
                }
            },
            error: function() {
                showChatbotError('파일 로드 중 오류가 발생했습니다.');
            }
        });
    }
    
    // JSON 유효성 검사 함수
    function isValidJSON(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    // 텍스트 기반 결과를 보여주는 함수
    function showTextBasedResults(card) {
        const textBtn = card.find('.text-view-btn');
        if (textBtn.length > 0) {
            const textPath = textBtn.data('path');
            
            // 텍스트 파일 내용 가져오기
            $.ajax({
                url: 'ajax_get_file_content.php',
                type: 'GET',
                data: { path: textPath },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const chatMessages = $('#chatbot-container .chat-messages');
                        chatMessages.empty();
                        
                        // 인사 메시지
                        addChatbotMessage('안녕하세요! OCR 처리 결과를 분석했습니다.');
                        
                        // 텍스트 내용으로 간단 분석
                        const text = response.content;
                        
                        // 문서 유형 추측
                        const documentType = guessDocumentTypeFromText(text);
                        if (documentType) {
                            addChatbotMessage(`이 문서는 **${documentType}** 유형으로 보입니다.`);
                        }
                        
                        // 키-값 쌍 찾기
                        const keyValues = extractKeyValuePairs(text);
                        if (Object.keys(keyValues).length > 0) {
                            let fieldsMessage = '문서에서 다음 정보를 확인했습니다:';
                            for (const [key, value] of Object.entries(keyValues)) {
                                fieldsMessage += `\n- **${key}**: ${value}`;
                            }
                            addChatbotMessage(fieldsMessage);
                        }
                        
                        // 표가 있는지 확인
                        if (text.includes('|') && text.includes('-')) {
                            addChatbotMessage('문서에 테이블이 포함되어 있는 것 같습니다. 테이블 탭에서 확인해보세요.');
                        }
                        
                        // 전체 텍스트는 아래 탭에서 확인 가능
                        addChatbotMessage('전체 텍스트 내용은 아래 텍스트 결과 탭에서 확인하실 수 있습니다.');
                        
                        // 로딩 숨기고 메시지 표시
                        $('#chatbot-container .chat-loading').hide();
                        chatMessages.show();
                    } else {
                        showChatbotError('텍스트 파일을 로드할 수 없습니다.');
                    }
                },
                error: function() {
                    showChatbotError('파일 로드 중 오류가 발생했습니다.');
                }
            });
        } else {
            showChatbotError('JSON 파싱에 실패했으며 대체 텍스트를 찾을 수 없습니다.');
        }
    }
    
    // 텍스트에서 문서 유형 추측
    function guessDocumentTypeFromText(text) {
        const lowerText = text.toLowerCase();
        
        const documentTypes = [
            { type: '영수증', keywords: ['영수증', '매출', '결제', 'pos', '카드'] },
            { type: '청구서', keywords: ['청구서', '청구금액', '납부', '고지서'] },
            { type: '계약서', keywords: ['계약서', '계약', '동의', '당사자'] },
            { type: '송장', keywords: ['송장', '인보이스', '배송', '배달', '택배'] },
            { type: '견적서', keywords: ['견적서', '견적', '금액', '제안'] },
            { type: '대금청구서', keywords: ['대금청구서', '세금계산서', '부가세', '공급가액'] },
            { type: '보고서', keywords: ['보고서', '리포트', '분석', '결과'] }
        ];
        
        for (const doc of documentTypes) {
            for (const keyword of doc.keywords) {
                if (lowerText.includes(keyword)) {
                    return doc.type;
                }
            }
        }
        
        return '일반 문서';
    }
    
    // 텍스트에서 키-값 쌍 추출
    function extractKeyValuePairs(text) {
        const result = {};
        const lines = text.split('\n');
        
        // 키:값 패턴 찾기
        const keyValuePattern = /([^:]+):\s*(.+)/;
        
        for (const line of lines) {
            const match = line.match(keyValuePattern);
            if (match) {
                const key = match[1].trim();
                const value = match[2].trim();
                
                // 의미있는 키-값 쌍만 추가
                if (key.length > 1 && value.length > 1) {
                    result[key] = value;
                }
            }
        }
        
        return result;
    }
    
    // 챗봇 메시지 생성 함수
    function generateChatbotMessages(ocrData) {
        const chatMessages = $('#chatbot-container .chat-messages');
        chatMessages.empty();
        
        // 인사 메시지
        addChatbotMessage('안녕하세요! OCR 처리 결과를 분석했습니다. 어떤 정보를 찾고 계신가요?');
        
        // OCR 데이터 분석
        if (ocrData && ocrData.images && ocrData.images.length > 0) {
            const image = ocrData.images[0];
            
            // 1. 텍스트 필드 정보 요약
            if (image.fields && image.fields.length > 0) {
                const keyFields = extractKeyFields(image.fields);
                if (Object.keys(keyFields).length > 0) {
                    let fieldsMessage = '문서에서 다음 정보를 확인했습니다:'
                    for (const [key, value] of Object.entries(keyFields)) {
                        fieldsMessage += `\n- **${key}**: ${value}`;
                    }
                    addChatbotMessage(fieldsMessage);
                }
            }
            
            // 2. 테이블 정보 요약
            if (image.tables && image.tables.length > 0) {
                addChatbotMessage(`문서에서 ${image.tables.length}개의 테이블을 찾았습니다. 테이블 정보가 필요하시면 알려주세요.`);
                
                // 첫 번째 테이블 요약 제공
                if (image.tables[0].cells && image.tables[0].cells.length > 0) {
                    const tablePreview = generateTablePreview(image.tables[0]);
                    if (tablePreview) {
                        addChatbotMessage('첫 번째 테이블 미리보기입니다:\n' + tablePreview);
                    }
                }
            } else {
                addChatbotMessage('이 문서에는 테이블이 없는 것 같습니다.');
            }
            
            // 3. 문서 유형 추측
            const documentType = guessDocumentType(image);
            if (documentType) {
                addChatbotMessage(`이 문서는 **${documentType}** 유형으로 보입니다. 특정 정보가 필요하시면 말씀해 주세요.`);
            }
            
            // 4. 추가 안내 메시지
            addChatbotMessage('전체 텍스트 내용이나 테이블 정보는 아래 탭을 통해 확인하실 수 있습니다.');
        } else {
            addChatbotMessage('OCR 처리 결과가 없거나 형식이 올바르지 않습니다.');
        }
        
        // 로딩 숨기고 메시지 표시
        $('#chatbot-container .chat-loading').hide();
        chatMessages.show();
    }
    
    // 챗봇 메시지 추가 함수
    function addChatbotMessage(message) {
        const chatMessages = $('#chatbot-container .chat-messages');
        const messageHtml = `
            <div class="chat-message">
                <div class="chat-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="chat-content">
                    ${formatMessage(message)}
                </div>
            </div>
        `;
        chatMessages.append(messageHtml);
    }
    
    // 챗봇 오류 메시지 표시
    function showChatbotError(message) {
        $('#chatbot-container .chat-loading').hide();
        $('#chatbot-container .chat-messages').html(`
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
            </div>
        `).show();
    }
    
    // 메시지 포맷팅 (마크다운 스타일 지원)
    function formatMessage(message) {
        // 줄바꿈을 <br>로 변환
        let formatted = message.replace(/\n/g, '<br>');
        
        // 볼드 텍스트 처리 (**텍스트**)
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // 이탤릭 텍스트 처리 (*텍스트*)
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        return formatted;
    }
    
    // 주요 필드 정보 추출
    function extractKeyFields(fields) {
        const result = {};
        const keywordMap = {
            '날짜': ['date', '기준일', '발행일', '작성일'],
            '금액': ['amount', '총액', '합계', '총합계', '공급가액', '결제금액'],
            '거래처': ['company', '상호', '업체명', '거래처', '공급자', '판매자'],
            '담당자': ['담당자', '작성자', '성명', '신청자'],
            '제목': ['title', '제목', '문서명', '건명']
        };
        
        // 키워드 기반 필드 분류
        fields.forEach(field => {
            if (!field.inferText) return;
            
            const text = field.inferText.trim();
            let matched = false;
            
            // 키:값 형태 체크
            const keyValueMatch = text.match(/([^:]+)\s*:\s*(.+)/);
            if (keyValueMatch) {
                const key = keyValueMatch[1].trim();
                const value = keyValueMatch[2].trim();
                result[key] = value;
                matched = true;
            }
            
            // 키워드 체크
            if (!matched) {
                for (const [category, keywords] of Object.entries(keywordMap)) {
                    for (const keyword of keywords) {
                        if (text.includes(keyword) && text.length < 30) {
                            // 이미 카테고리가 존재하면 추가하지 않음
                            if (!result[category]) {
                                result[category] = text;
                            }
                            break;
                        }
                    }
                }
            }
        });
        
        return result;
    }
    
    // 테이블 미리보기 생성
    function generateTablePreview(table) {
        if (!table.cells || table.cells.length === 0) return null;
        
        // 테이블 구조 분석
        const tableData = {};
        let maxRow = 0;
        let maxCol = 0;
        
        table.cells.forEach(cell => {
            const rowIndex = cell.rowIndex || 0;
            const colIndex = cell.colIndex || 0;
            
            if (!tableData[rowIndex]) tableData[rowIndex] = {};
            tableData[rowIndex][colIndex] = cell.inferText || '';
            
            maxRow = Math.max(maxRow, rowIndex);
            maxCol = Math.max(maxCol, colIndex);
        });
        
        // 최대 3행까지만 미리보기
        const maxPreviewRows = Math.min(3, maxRow + 1);
        
        // 마크다운 테이블 형식으로 생성
        let preview = '';
        
        // 헤더 (첫 번째 행)
        if (tableData[0]) {
            for (let col = 0; col <= maxCol; col++) {
                preview += (tableData[0][col] || '열' + (col + 1)) + ' | ';
            }
            preview = preview.slice(0, -2) + '\n';
        }
        
        // 구분선
        for (let col = 0; col <= maxCol; col++) {
            preview += '------ | ';
        }
        preview = preview.slice(0, -2) + '\n';
        
        // 데이터 행 (2~3행)
        for (let row = 1; row < maxPreviewRows; row++) {
            if (tableData[row]) {
                for (let col = 0; col <= maxCol; col++) {
                    preview += (tableData[row][col] || '') + ' | ';
                }
                preview = preview.slice(0, -2) + '\n';
            }
        }
        
        return preview;
    }
    
    // 문서 유형 추측
    function guessDocumentType(image) {
        if (!image.fields || image.fields.length === 0) return null;
        
        const fullText = image.fields.map(f => f.inferText || '').join(' ');
        const lowerText = fullText.toLowerCase();
        
        const documentTypes = [
            { type: '영수증', keywords: ['영수증', '매출', '결제', 'pos', '카드'] },
            { type: '청구서', keywords: ['청구서', '청구금액', '납부', '고지서'] },
            { type: '계약서', keywords: ['계약서', '계약', '동의', '당사자'] },
            { type: '송장', keywords: ['송장', '인보이스', '배송', '배달', '택배'] },
            { type: '견적서', keywords: ['견적서', '견적', '금액', '제안'] },
            { type: '대금청구서', keywords: ['대금청구서', '세금계산서', '부가세', '공급가액'] },
            { type: '보고서', keywords: ['보고서', '리포트', '분석', '결과'] }
        ];
        
        for (const doc of documentTypes) {
            for (const keyword of doc.keywords) {
                if (lowerText.includes(keyword)) {
                    return doc.type;
                }
            }
        }
        
        return '일반 문서';
    }
    
    // 작업 제어 버튼 이벤트
    $('#cancelJobBtn').click(function() {
        if (!confirm('정말로 이 작업을 취소하시겠습니까?')) return;
        
        const jobId = $(this).data('job-id');
        
        $.ajax({
            url: 'ajax_cancel_job.php',
            type: 'POST',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('작업이 취소되었습니다.');
                    location.reload();
                } else {
                    alert('작업 취소 중 오류: ' + response.message);
                }
            },
            error: function() {
                alert('요청 중 오류가 발생했습니다.');
            }
        });
    });
    
    $('#deleteJobBtn').click(function() {
        if (!confirm('정말로 이 작업을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return;
        
        const jobId = $(this).data('job-id');
        
        $.ajax({
            url: 'ajax_delete_job.php',
            type: 'POST',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('작업이 삭제되었습니다.');
                    window.location.href = 'jobs.php';
                } else {
                    alert('작업 삭제 중 오류: ' + response.message);
                }
            },
            error: function() {
                alert('요청 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 자동 새로고침 기능 (처리 중인 작업의 경우)
    let refreshInterval;
    
    function setupAutoRefresh() {
        const autoRefresh = $('#autoRefresh').is(':checked');
        
        if (autoRefresh) {
            refreshInterval = setInterval(refreshJobStatus, 5000);
        } else {
            clearInterval(refreshInterval);
        }
    }
    
    $('#autoRefresh').change(setupAutoRefresh);
    
    function refreshJobStatus() {
        const jobId = $('#cancelJobBtn').data('job-id');
        
        if (!jobId) return;
        
        $.ajax({
            url: 'ajax_get_job_status.php',
            type: 'GET',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const job = response.progress.job;
                    
                    // 진행 상태 업데이트
                    $('#lastUpdated').text(job.updated_at);
                    $('#processedFiles').text(job.processed_files);
                    
                    // 프로그레스 바 업데이트
                    $('#progressBar').css('width', job.progress + '%');
                    $('#progressBar').text(job.progress + '%');
                    $('#progressBar').attr('aria-valuenow', job.progress);
                    
                    // 완료 시 페이지 새로고침
                    if (job.status === 'completed') {
                        clearInterval(refreshInterval);
                        location.reload();
                    }
                }
            }
        });
    }
    
    // 페이지 로드 시 자동 새로고침 설정
    if ($('#autoRefresh').length) {
        setupAutoRefresh();
    }
    
    // 챗봇 결과 로드
    loadAndProcessChatbotResults();
    
    // 결과 탭 클릭 시 챗봇 결과 새로 로드
    $('#results-tab').on('click', function() {
        if ($('#chatbot-container .chat-messages').is(':empty')) {
            loadAndProcessChatbotResults();
        }
    });
});