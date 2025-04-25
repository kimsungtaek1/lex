/**
 * OCR 인식률 향상 시스템 - 결과 분석 및 피드백 기능 (디버깅 개선 버전)
 */

// 전역 변수로 OCR 결과 처리 함수 선언
var ocrResultProcessor = null;
var debugMode = true; // 디버깅 모드 켜기

$(document).ready(function() {
    // 전역 변수
    let currentJobId = null;
    
    // UI에 디버그 메시지 표시 함수
    function debugToUI(message, type = 'info') {
        if (!debugMode) return;
        
        // 디버그 컨테이너가 없으면 생성
        if ($('#debug-container').length === 0) {
            $('<div id="debug-container" class="mt-3 p-3 border rounded bg-light" style="display:none;">' +
              '<h6><i class="bi bi-bug"></i> 디버그 정보 <button id="toggle-debug" class="btn btn-sm btn-outline-secondary float-end">숨기기</button></h6>' +
              '<div id="debug-messages" style="max-height: 200px; overflow-y: auto;"></div>' +
              '</div>').insertAfter('#chatbot-container');
            
            // 토글 버튼 이벤트
            $(document).on('click', '#toggle-debug', function() {
                const $messages = $('#debug-messages');
                if ($messages.is(':visible')) {
                    $messages.hide();
                    $(this).text('보이기');
                } else {
                    $messages.show();
                    $(this).text('숨기기');
                }
            });
        }
        
        // 컨테이너 표시
        $('#debug-container').show();
        
        // 타입에 따른 스타일 클래스
        const typeClass = type === 'error' ? 'text-danger' :
                         type === 'warning' ? 'text-warning' : 'text-info';
        
        // 타임스탬프 추가
        const now = new Date();
        const timestamp = `${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}.${now.getMilliseconds()}`;
        
        // 메시지 추가
        $('#debug-messages').append(
            `<div class="${typeClass} small">[${timestamp}] ${message}</div>`
        );
        
        // 콘솔에도 출력 (브라우저 콘솔용)
        if (type === 'error') {
            console.error(message);
        } else if (type === 'warning') {
            console.warn(message);
        } else {
            console.log(message);
        }
        
        // 스크롤 최하단으로
        const $debugMessages = $('#debug-messages');
        $debugMessages.scrollTop($debugMessages[0].scrollHeight);
    }
    
    // 초기화 함수 - 페이지 로드 시 실행
    function initialize() {
        debugToUI('초기화 함수 실행 시작');
        
        // 현재 작업 ID 확인
        currentJobId = $('#cancelJobBtn').data('job-id') || $('#deleteJobBtn').data('job-id');
        debugToUI(`현재 작업 ID: ${currentJobId}`);
        
        // 페이지 로드 시 각 파일의 첫 번째 결과(텍스트)를 자동으로 로드
        $('.result-card').each(function() {
            const idx = $(this).attr('id').split('-').pop();
            const textBtn = $(this).find('.text-view-btn');
            if (textBtn.length) {
                debugToUI(`텍스트 뷰 버튼 발견: 인덱스 ${idx}`);
                const path = textBtn.data('path');
                loadTextResult(path, idx);
            }
        });

        // 자동 새로고침 설정
        if ($('#autoRefresh').length) {
            debugToUI('자동 새로고침 기능 설정');
            setupAutoRefresh();
        }
        
        // 결과 탭이 활성화되어 있는지 확인
        const resultsTabActive = $('#results-tab').hasClass('active');
        debugToUI(`결과 탭 활성화 상태: ${resultsTabActive}`);
        
        // 결과 탭이 활성화되어 있으면 OCR 결과 로드
        if (resultsTabActive) {
            debugToUI('결과 탭이 활성화되어 있어 OCR 결과 로드 시작');
            setTimeout(function() {
                loadAndProcessOcrResults();
            }, 500); // 약간의 지연 추가
        }
        
        debugToUI('초기화 함수 실행 완료');
    }
    
    // 텍스트 결과 보기 버튼
    $(document).on('click', '.text-view-btn', function() {
        const idx = $(this).data('idx');
        const path = $(this).data('path');
        debugToUI(`텍스트 뷰 버튼 클릭: 인덱스 ${idx}, 경로 ${path}`);
        
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
        debugToUI(`테이블 뷰 버튼 클릭: 인덱스 ${idx}, 경로 ${path}`);
        
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
        debugToUI(`JSON 뷰 버튼 클릭: 인덱스 ${idx}, 경로 ${path}`);
        
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
    
    // 파일 목록에서 결과 보기 버튼
    $(document).on('click', '.view-result-btn', function() {
        const fileId = $(this).data('file-id');
        const textPath = $(this).data('text-path');
        debugToUI(`결과 보기 버튼 클릭: 파일 ID ${fileId}, 텍스트 경로 ${textPath}`);
        
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
                    debugToUI(`해당 결과 카드 발견: 인덱스 ${idx}, 파일 ID ${resultFileId}`);
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
    
    // 결과 탭 클릭 시 OCR 결과 자동 로드
    $('#results-tab').on('click', function() {
        debugToUI('결과 탭 클릭됨');
        const chatMessagesEmpty = $('#chatbot-container .chat-messages').is(':empty');
        debugToUI(`채팅 메시지 비어있음: ${chatMessagesEmpty}`);
        
        if (chatMessagesEmpty) {
            debugToUI('OCR 결과 로드 시작 (탭 클릭에 의해)');
            loadAndProcessOcrResults();
        }
    });
    
    // 텍스트 결과 로드 함수
    function loadTextResult(path, idx) {
        debugToUI(`텍스트 결과 로드 시작: 경로 ${path}, 인덱스 ${idx}`);
        
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
                debugToUI(`텍스트 로드 AJAX 성공: ${path}`);
                
                if (response.success) {
                    // HTML 안전 처리 및 줄바꿈 유지
                    const formattedText = processTextForDisplay(response.content);
                    $(`.text-viewer-${idx} .text-content`).html(formattedText);
                    debugToUI(`텍스트 표시 완료: 길이 ${response.content?.length || 0}자`);
                } else {
                    debugToUI(`텍스트 로드 실패: ${response.message}`, 'error');
                    $(`.text-viewer-${idx} .text-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`텍스트 로드 AJAX 오류: ${status} - ${error}`, 'error');
                $(`.text-viewer-${idx} .text-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다: ${error}
                    </div>
                `);
            }
        });
    }
    
    // JSON 결과 로드 함수
    function loadJsonResult(path, idx) {
        debugToUI(`JSON 결과 로드 시작: 경로 ${path}, 인덱스 ${idx}`);
        
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
                debugToUI(`JSON 로드 AJAX 성공: ${path}`);
                
                if (response.success) {
                    try {
                        const jsonObj = JSON.parse(response.content);
                        const formattedJson = JSON.stringify(jsonObj, null, 4);
                        $(`.json-viewer-${idx} .json-content`).html(escapeHtml(formattedJson));
                        debugToUI(`JSON 표시 완료: 길이 ${formattedJson.length}자`);
                    } catch (e) {
                        debugToUI(`JSON 파싱 오류: ${e.message}`, 'error');
                        $(`.json-viewer-${idx} .json-content`).html(escapeHtml(response.content));
                    }
                } else {
                    debugToUI(`JSON 로드 실패: ${response.message}`, 'error');
                    $(`.json-viewer-${idx} .json-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`JSON 로드 AJAX 오류: ${status} - ${error}`, 'error');
                $(`.json-viewer-${idx} .json-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다: ${error}
                    </div>
                `);
            }
        });
    }
    
    // 테이블 결과 로드 함수
    function loadTableResult(path, idx) {
        debugToUI(`테이블 결과 로드 시작: 경로 ${path}, 인덱스 ${idx}`);
        
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
                debugToUI(`테이블 로드 AJAX 성공: ${path}`);
                
                if (response.success) {
                    $(`.table-viewer-${idx} .table-content`).html(response.content);
                    debugToUI(`테이블 표시 완료: 길이 ${response.content?.length || 0}자`);
                } else {
                    debugToUI(`테이블 로드 실패: ${response.message}`, 'error');
                    $(`.table-viewer-${idx} .table-content`).html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>파일을 로드할 수 없습니다: ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`테이블 로드 AJAX 오류: ${status} - ${error}`, 'error');
                $(`.table-viewer-${idx} .table-content`).html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>파일 로드 중 오류가 발생했습니다: ${error}
                    </div>
                `);
            }
        });
    }
    
    // OCR JSON 데이터를 로드하고 처리하는 함수
    function loadAndProcessOcrResults() {
        debugToUI("OCR 결과 로드 시작");
        
        if ($('.result-card').length === 0) {
            debugToUI("처리할 결과 카드를 찾을 수 없음", "warning");
            showChatbotError('처리할 결과 카드를 찾을 수 없습니다.');
            return;
        }
        
        // 첫 번째 결과 카드의 JSON 경로 가져오기
        const firstCard = $('.result-card').first();
        const jsonBtn = firstCard.find('.json-view-btn');
        
        if (jsonBtn.length === 0) {
            debugToUI("JSON 버튼을 찾을 수 없음 - 텍스트 기반 분석으로 대체", "warning");
            // JSON 버튼이 없으면 텍스트 기반 분석으로 대체
            const textBtn = firstCard.find('.text-view-btn');
            if (textBtn.length > 0) {
                const textPath = textBtn.data('path');
                debugToUI(`텍스트 경로 발견: ${textPath}`);
                loadAndProcessTextResults(textPath);
                return;
            }
            
            debugToUI("분석할 텍스트 경로도 찾을 수 없음", "error");
            showChatbotError('분석할 결과 데이터를 찾을 수 없습니다.');
            return;
        }
        
        const jsonPath = jsonBtn.data('path');
        if (!jsonPath) {
            debugToUI("JSON 파일 경로가 비어있음", "error");
            showChatbotError('JSON 파일 경로를 찾을 수 없습니다.');
            return;
        }
        
        debugToUI(`JSON 파일 경로: ${jsonPath}`);
        
        // 로딩 표시
        $('#chatbot-container .chat-loading').show();
        $('#chatbot-container .chat-messages').hide().empty();
        
        // JSON 데이터 로드
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: jsonPath },
            dataType: 'json',
            success: function(response) {
                debugToUI(`AJAX 응답 받음: success=${response.success}`);
                
                if (response.success) {
                    try {
                        // JSON 문자열 처리 및 파싱
                        let jsonContent = response.content;
                        debugToUI(`JSON 콘텐츠 길이: ${jsonContent?.length || 0}`);
                        
                        // 빈 응답 확인
                        if (!jsonContent || jsonContent.trim() === '') {
                            debugToUI("JSON 내용이 비어있음 - 텍스트 기반 분석으로 대체", "warning");
                            // 텍스트 기반 분석으로 대체
                            const textBtn = firstCard.find('.text-view-btn');
                            if (textBtn.length > 0) {
                                loadAndProcessTextResults(textBtn.data('path'));
                                return;
                            }
                            
                            showChatbotError('JSON 내용이 비어있습니다.');
                            return;
                        }
                        
                        // JSON 유효성 확인 및 필요시 전처리
                        jsonContent = preprocessJsonContent(jsonContent);
                        debugToUI("JSON 전처리 완료");
                        
                        if (!isValidJSON(jsonContent)) {
                            debugToUI("유효하지 않은 JSON 형식 - 문자열 일부 표시: " + 
                                     jsonContent.substring(0, 100) + "...", "error");
                            
                            // 텍스트 기반 분석으로 대체
                            const textBtn = firstCard.find('.text-view-btn');
                            if (textBtn.length > 0) {
                                loadAndProcessTextResults(textBtn.data('path'));
                                return;
                            }
                            
                            showChatbotError('유효하지 않은 JSON 형식입니다. JSON 데이터 구조를 확인해주세요.');
                            return;
                        }
                        
                        debugToUI("JSON 유효성 검사 통과, 파싱 시작");
                        const ocrData = JSON.parse(jsonContent);
                        
                        // OCR 데이터 검증
                        if (!ocrData || !ocrData.images || !Array.isArray(ocrData.images) || ocrData.images.length === 0) {
                            debugToUI("유효한 OCR 데이터 구조가 아님 - 필수 필드 누락", "error");
                            
                            // 텍스트 기반 분석으로 대체
                            const textBtn = firstCard.find('.text-view-btn');
                            if (textBtn.length > 0) {
                                loadAndProcessTextResults(textBtn.data('path'));
                                return;
                            }
                            
                            showChatbotError('유효한 OCR 데이터 구조가 아닙니다. images 배열이 필요합니다.');
                            return;
                        }
                        
                        // 데이터 처리 및 표시
                        debugToUI("OCR 데이터 검증 통과, 처리 및 표시 시작");
                        processAndDisplayOcrData(ocrData);
                        
                    } catch (e) {
                        debugToUI(`JSON 파싱 오류: ${e.message}`, "error");
                        debugToUI(`스택 트레이스: ${e.stack}`, "error");
                        
                        // 텍스트 기반 분석으로 대체
                        const textBtn = firstCard.find('.text-view-btn');
                        if (textBtn.length > 0) {
                            loadAndProcessTextResults(textBtn.data('path'));
                            return;
                        }
                        
                        showChatbotError('JSON 파싱 중 오류가 발생했습니다: ' + e.message);
                    }
                } else {
                    debugToUI(`AJAX 성공했지만 응답 실패: ${response.message}`, "error");
                    
                    // 텍스트 기반 분석으로 대체
                    const textBtn = firstCard.find('.text-view-btn');
                    if (textBtn.length > 0) {
                        loadAndProcessTextResults(textBtn.data('path'));
                        return;
                    }
                    
                    showChatbotError('파일을 로드할 수 없습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`AJAX 요청 오류: ${status} - ${error}`, "error");
                if (xhr.responseText) {
                    debugToUI(`서버 응답: ${xhr.responseText.substring(0, 200)}...`, "error");
                }
                
                // 텍스트 기반 분석으로 대체
                const textBtn = firstCard.find('.text-view-btn');
                if (textBtn.length > 0) {
                    loadAndProcessTextResults(textBtn.data('path'));
                    return;
                }
                
                showChatbotError('파일 로드 중 오류가 발생했습니다: ' + error);
            }
        });
    }
    
    // 텍스트 파일 기반 분석 함수
    function loadAndProcessTextResults(textPath) {
        debugToUI(`텍스트 파일 기반 분석 시작: ${textPath}`);
        
        if (!textPath) {
            debugToUI("텍스트 파일 경로를 찾을 수 없음", "error");
            showChatbotError('텍스트 파일 경로를 찾을 수 없습니다.');
            return;
        }
        
        // 로딩 표시
        $('#chatbot-container .chat-loading').show();
        $('#chatbot-container .chat-messages').hide().empty();
        
        // 텍스트 파일 로드
        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: textPath },
            dataType: 'json',
            success: function(response) {
                debugToUI(`텍스트 파일 로드 AJAX 성공: ${textPath}`);
                
                if (response.success) {
                    const text = response.content || '';
                    debugToUI(`텍스트 내용 길이: ${text.length}`);
                    
                    if (!text || text.trim() === '') {
                        debugToUI("텍스트 내용이 비어있음", "warning");
                        showChatbotError('텍스트 내용이 비어있습니다.');
                        return;
                    }
                    
                    // 텍스트 기반 분석 및 표시
                    debugToUI("텍스트 분석 시작");
                    analyzeAndDisplayText(text);
                } else {
                    debugToUI(`텍스트 파일 로드 실패: ${response.message}`, "error");
                    showChatbotError('텍스트 파일을 로드할 수 없습니다: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`텍스트 파일 로드 AJAX 오류: ${status} - ${error}`, "error");
                showChatbotError('파일 로드 중 오류가 발생했습니다: ' + error);
            }
        });
    }
    
    // 텍스트 분석 및 표시 함수
    function analyzeAndDisplayText(text) {
        debugToUI("텍스트 분석 및 표시 시작");
        const chatMessages = $('#chatbot-container .chat-messages');
        chatMessages.empty();
        
        // 인사 메시지
        addChatbotMessage('안녕하세요! OCR 처리 결과를 분석했습니다.');
        
        // 문서 유형 추측
        const documentType = guessDocumentTypeFromText(text);
        if (documentType) {
            debugToUI(`문서 유형 추측: ${documentType}`);
            addChatbotMessage(`이 문서는 **${documentType}** 유형으로 보입니다.`);
        }
        
        // 키-값 쌍 찾기
        const keyValues = extractKeyValuePairsFromText(text);
        if (Object.keys(keyValues).length > 0) {
            debugToUI(`키-값 쌍 ${Object.keys(keyValues).length}개 추출`);
            let fieldsMessage = '문서에서 다음 정보를 확인했습니다:';
            for (const [key, value] of Object.entries(keyValues)) {
                fieldsMessage += `\n- **${key}**: ${value}`;
            }
            addChatbotMessage(fieldsMessage);
        }
        
        // 날짜 추출
        const dates = extractDatesFromText(text);
        if (dates.length > 0) {
            debugToUI(`날짜 정보 ${dates.length}개 추출`);
            addChatbotMessage(`문서에서 다음 날짜 정보를 발견했습니다: **${dates.join('**, **')}**`);
        }
        
        // 금액 추출
        const amounts = extractAmountsFromText(text);
        if (amounts.length > 0) {
            debugToUI(`금액 정보 ${amounts.length}개 추출`);
            addChatbotMessage(`문서에서 다음 금액 정보를 발견했습니다: **${amounts.join('**, **')}**`);
        }
        
        // 표가 있는지 확인
        if (hasTableInText(text)) {
            debugToUI("텍스트에서 테이블 구조 발견");
            addChatbotMessage('문서에 테이블이 포함되어 있는 것 같습니다. 테이블 탭에서 확인해보세요.');
        }
        
        // 텍스트 길이 정보
        const textLength = text.length;
        const lineCount = text.split('\n').length;
        debugToUI(`텍스트 길이: ${textLength}자, ${lineCount}줄`);
        addChatbotMessage(`문서 텍스트는 총 **${textLength}자**, **${lineCount}개** 줄로 구성되어 있습니다.`);
        
        // 전체 텍스트는 아래 탭에서 확인 가능
        addChatbotMessage('전체 텍스트 내용은 아래 텍스트 결과 탭에서 확인하실 수 있습니다.');
        
        // 로딩 숨기고 메시지 표시
        $('#chatbot-container .chat-loading').hide();
        chatMessages.show();
        debugToUI("텍스트 분석 및 표시 완료");
    }
    
    // JSON 문자열 전처리 함수
    function preprocessJsonContent(jsonContent) {
        debugToUI("JSON 문자열 전처리 시작");
        // 줄바꿈이나 불필요한 공백 제거
        let processed = jsonContent.trim();
        
        // 특수 케이스 처리: 잘못된 이스케이프 문자 처리
        processed = processed.replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        
        // JSON 시작/끝 확인 및 보정
        if (!processed.startsWith('{') && !processed.startsWith('[')) {
            const startIdx = processed.indexOf('{');
            if (startIdx > -1) {
                debugToUI(`JSON 시작 문자({)가 ${startIdx}번째 위치에서 발견됨, 앞부분 제거`, "warning");
                processed = processed.substring(startIdx);
            }
        }
        
        if (!processed.endsWith('}') && !processed.endsWith(']')) {
            const endIdx = processed.lastIndexOf('}');
            if (endIdx > -1) {
                debugToUI(`JSON 종료 문자(})가 끝에 없음, ${endIdx}번째 위치 이후 제거`, "warning");
                processed = processed.substring(0, endIdx + 1);
            }
        }
        
        return processed;
    }
    
    // JSON 유효성 검사 함수
    function isValidJSON(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            debugToUI(`JSON 유효성 검사 실패: ${e.message}`, "error");
            return false;
        }
    }
    
    // OCR 데이터 처리 및 결과 표시 함수
    function processAndDisplayOcrData(ocrData) {
        debugToUI("OCR 데이터 처리 및 표시 시작");
        const chatMessages = $('#chatbot-container .chat-messages');
        chatMessages.empty();
        
        // 인사 메시지
        addChatbotMessage('안녕하세요! OCR 처리 결과를 분석했습니다.');
        
        if (ocrData && ocrData.images && ocrData.images.length > 0) {
            const image = ocrData.images[0];
            debugToUI(`첫 번째 이미지 데이터 발견, 분석 시작`);
            
            // 1. 문서 유형 분석 및 추가 정보
            const documentType = analyzeDocumentType(image);
            if (documentType) {
                debugToUI(`문서 유형 분석 결과: ${documentType.type}`);
                addChatbotMessage(`이 문서는 **${documentType.type}** 유형으로 분석되었습니다.`);
                
                if (documentType.description) {
                    addChatbotMessage(documentType.description);
                }
            }
            
            // 2. 주요 필드 정보 추출 및 표시
            const extractedFields = extractKeyInformation(image);
            if (Object.keys(extractedFields).length > 0) {
                debugToUI(`핵심 정보 ${Object.keys(extractedFields).length}개 추출`);
                let fieldsMessage = '문서에서 다음 핵심 정보를 추출했습니다:';
                for (const [key, value] of Object.entries(extractedFields)) {
                    fieldsMessage += `\n- **${key}**: ${value}`;
                }
                addChatbotMessage(fieldsMessage);
            }
            
            // 3. 테이블 정보 분석 및 표시
            if (image.tables && image.tables.length > 0) {
                debugToUI(`테이블 ${image.tables.length}개 발견, 분석 시작`);
                const tableAnalysis = analyzeTableData(image.tables);
                addChatbotMessage(tableAnalysis.summary);
                
                if (tableAnalysis.preview) {
                    addChatbotMessage(tableAnalysis.preview);
                }
            }
            
            // 4. 텍스트 분석 추가 정보
            const textAnalysis = analyzeTextContent(image);
            if (textAnalysis) {
                debugToUI(`텍스트 분석 정보 추가`);
                addChatbotMessage(textAnalysis);
            }
            
            // 5. 품질 및 신뢰도 정보
            const qualityAnalysis = analyzeOcrQuality(image);
            if (qualityAnalysis) {
                debugToUI(`OCR 품질 분석 정보 추가`);
                addChatbotMessage(qualityAnalysis);
            }
        } else {
            debugToUI("OCR 처리 결과가 없거나 형식이 올바르지 않음", "warning");
            addChatbotMessage('OCR 처리 결과가 없거나 형식이 올바르지 않습니다. 아래 텍스트 결과 탭에서 원본 정보를 확인해보세요.');
        }
        
        // 로딩 숨기고 메시지 표시
        $('#chatbot-container .chat-loading').hide();
        chatMessages.show();
        debugToUI("OCR 데이터 처리 및 표시 완료");
    }
    
    // 문서 유형 분석 함수
    function analyzeDocumentType(imageData) {
        if (!imageData || !imageData.fields) return null;
        
        // 전체 텍스트 추출
        const allText = extractAllText(imageData).toLowerCase();
        
        // 문서 유형 정의
        const documentTypes = [
            {
                type: '영수증',
                keywords: ['영수증', '매출', '결제', 'pos', '카드', '금액', '부가세', '합계', '소계'],
                description: '결제 금액, 항목, 날짜 등의 정보가 포함된 영수증입니다.'
            },
            {
                type: '세금계산서',
                keywords: ['세금계산서', '공급가액', '부가가치세', '사업자등록번호', '공급자', '공급받는자'],
                description: '사업자 간 거래를 증빙하는 세금계산서입니다. 공급가액, 부가세, 사업자 정보 등이 포함되어 있습니다.'
            },
            {
                type: '견적서',
                keywords: ['견적서', '견적', '금액', '제안', '유효기간', '견적금액'],
                description: '제품이나 서비스에 대한 가격과 조건을 제시하는 견적서입니다.'
            },
            {
                type: '송장',
                keywords: ['송장', '인보이스', '배송', '배달', '택배', '운송장', '송하인', '수하인'],
                description: '배송 정보가 포함된 송장 또는 운송장입니다. 발신자, 수신자, 배송 항목 정보가 포함되어 있습니다.'
            },
            {
                type: '계약서',
                keywords: ['계약서', '계약', '동의', '당사자', '갑', '을', '계약조건', '서명'],
                description: '법적 합의 내용이 담긴 계약서입니다. 계약 당사자, 조건, 서명 등이 포함되어 있습니다.'
            },
            {
                type: '청구서',
                keywords: ['청구서', '인보이스', '납부', '지불', '청구액', '미납', '납기일'],
                description: '지불해야 할 금액과 기한이 명시된 청구서입니다.'
            },
            {
                type: '명세서',
                keywords: ['명세서', '내역', '상세', '품목', '목록'],
                description: '상품이나 서비스의 상세 내역이 포함된 명세서입니다.'
            }
        ];
        
        // 테이블 내용 검사
        let hasTable = false;
        let tableHeaders = [];
        
        if (imageData.tables && imageData.tables.length > 0) {
            hasTable = true;
            
            // 첫 번째 테이블의 헤더 추출
            const table = imageData.tables[0];
            if (table.cells) {
                const headerCells = table.cells.filter(cell => 
                    (cell.rowIndex === 0 || cell.rowSpan > 1) && cell.inferText
                );
                
                tableHeaders = headerCells.map(cell => cell.inferText.trim().toLowerCase());
            }
        }
        
        // 가장 적합한 문서 유형 찾기
        let bestMatch = null;
        let highestScore = 0;
        
        for (const docType of documentTypes) {
            let score = 0;
            
            // 키워드 매칭
            for (const keyword of docType.keywords) {
                if (allText.includes(keyword)) {
                    score += 2;
                }
            }
            
            // 테이블 헤더 분석 (특정 문서 유형에 특화된 테이블 헤더 검사)
            if (hasTable) {
                if (docType.type === '영수증' && 
                    (tableHeaders.some(h => h.includes('상품') || h.includes('품목')) && 
                     tableHeaders.some(h => h.includes('금액') || h.includes('가격')))) {
                    score += 3;
                }
                else if (docType.type === '세금계산서' && 
                        (tableHeaders.some(h => h.includes('품목')) && 
                         tableHeaders.some(h => h.includes('공급가액')))) {
                    score += 3;
                }
                else if (docType.type === '송장' && 
                        (tableHeaders.some(h => h.includes('품목')) && 
                         tableHeaders.some(h => h.includes('수량')))) {
                    score += 3;
                }
            }
            
            // 베스트 매치 업데이트
            if (score > highestScore) {
                highestScore = score;
                bestMatch = docType;
            }
        }
        
        // 최소 점수 이상인 경우만 반환
        if (highestScore >= 2) {
            return bestMatch;
        }
        
        // 기본 문서 유형
        return {
            type: '일반 문서',
            description: '특정 문서 유형을 식별할 수 없습니다. 일반적인 문서로 처리됩니다.'
        };
    }
    
    // 모든 텍스트 추출 함수
    function extractAllText(imageData) {
        let fullText = '';
        
        // 필드 텍스트 추출
        if (imageData.fields && imageData.fields.length > 0) {
            fullText += imageData.fields.map(field => field.inferText || '').join(' ');
        }
        
        // 테이블 텍스트 추출
        if (imageData.tables && imageData.tables.length > 0) {
            for (const table of imageData.tables) {
                if (table.cells && table.cells.length > 0) {
                    fullText += ' ' + table.cells.map(cell => cell.inferText || '').join(' ');
                }
            }
        }
        
        return fullText;
    }
    
    // 핵심 정보 추출 함수
    function extractKeyInformation(imageData) {
        const result = {};
        
        if (!imageData || !imageData.fields) return result;
        
        // 주요 필드 카테고리 및 관련 키워드
        const fieldCategories = {
            '날짜': ['날짜', '발행일', '작성일', '등록일', '계약일', '거래일', 'date'],
            '금액': ['금액', '합계', '총액', '총합계', '결제금액', '청구금액', '금액합계', '공급가액', '공급가액합계', '최종금액'],
            '거래처': ['상호', '업체명', '거래처', '회사명', '공급자', '공급받는자', '매입처', '매출처'],
            '사업자번호': ['사업자등록번호', '사업자번호', '등록번호', '사업자'],
            '담당자': ['담당자', '작성자', '연락처', '담당', '문의'],
            '품목': ['품목', '상품명', '제품명', '서비스명', '공급내역'],
            '결제방법': ['결제방법', '지불방법', '카드', '현금', '계좌이체', '결제수단']
        };
        
        // 키-값 쌍 패턴 추출
        const keyValuePairs = extractKeyValuePairs(imageData.fields);
        for (const [key, value] of Object.entries(keyValuePairs)) {
            for (const [category, keywords] of Object.entries(fieldCategories)) {
                for (const keyword of keywords) {
                    if (key.includes(keyword)) {
                        result[category] = value;
                        break;
                    }
                }
            }
        }
        
        // 특정 패턴 검색 (날짜, 금액, 사업자번호 등)
        const allText = extractAllText(imageData);
        
        // 날짜 패턴
        if (!result['날짜']) {
            const dateMatches = allText.match(/\d{4}[-\.\/](0?[1-9]|1[0-2])[-\.\/](0?[1-9]|[12][0-9]|3[01])/g);
            if (dateMatches && dateMatches.length > 0) {
                result['날짜'] = dateMatches[0];
            }
        }
        
        // 금액 패턴
        if (!result['금액']) {
            const amountMatches = allText.match(/((합계|총액|금액|총금액|결제금액|청구금액)\s*:?\s*)?([\d,]+)(\s*원|\s*₩)?/g);
            if (amountMatches && amountMatches.length > 0) {
                // 가장 큰 금액 선택
                let maxAmount = 0;
                let maxAmountStr = '';
                
                for (const match of amountMatches) {
                    const numStr = match.replace(/[^0-9]/g, '');
                    if (numStr) {
                        const amount = parseInt(numStr, 10);
                        if (amount > maxAmount) {
                            maxAmount = amount;
                            maxAmountStr = match.trim();
                        }
                    }
                }
                
                if (maxAmountStr) {
                    result['금액'] = maxAmountStr;
                }
            }
        }
        
        // 사업자번호 패턴
        if (!result['사업자번호']) {
            const bizNumMatches = allText.match(/\d{3}[-\s]?\d{2}[-\s]?\d{5}/g);
            if (bizNumMatches && bizNumMatches.length > 0) {
                result['사업자번호'] = bizNumMatches[0];
            }
        }
        
        return result;
    }
    
    // 키-값 쌍 추출 함수
    function extractKeyValuePairs(fields) {
        const result = {};
        
        if (!fields || !Array.isArray(fields)) return result;
        
        // 가능한 키-값 쌍 패턴들
        const patterns = [
            // '키 : 값' 패턴
            { regex: /^(.*?)[:\s]\s*(.+)$/, keyIndex: 1, valueIndex: 2 },
            // '키 값' 패턴 (특정 키워드에만 적용)
            { regex: /^(날짜|금액|담당자|상호|결제방법|사업자등록번호)\s+(.+)$/, keyIndex: 1, valueIndex: 2 }
        ];
        
        for (const field of fields) {
            if (!field.inferText) continue;
            
            const text = field.inferText.trim();
            let matched = false;
            
            // 패턴 매칭 시도
            for (const pattern of patterns) {
                const match = text.match(pattern.regex);
                if (match) {
                    const key = match[pattern.keyIndex].trim();
                    const value = match[pattern.valueIndex].trim();
                    
                    // 의미있는 키-값 쌍만 추가 (너무 짧거나 긴 경우 제외)
                    if (key.length >= 1 && key.length <= 20 && 
                        value.length >= 1 && value.length <= 50) {
                        result[key] = value;
                        matched = true;
                        break;
                    }
                }
            }
        }
        
        return result;
    }
    
    // 테이블 데이터 분석 함수
    function analyzeTableData(tables) {
        if (!tables || tables.length === 0) {
            return {
                summary: "문서에서 테이블 정보를 찾을 수 없습니다."
            };
        }
        
        const result = {
            summary: "",
            preview: null
        };
        
        // 테이블 개수 및 기본 정보
        result.summary = `문서에서 **${tables.length}개의 테이블**을 발견했습니다.`;
        
        // 첫 번째 테이블 분석
        const firstTable = tables[0];
        if (firstTable.cells && firstTable.cells.length > 0) {
            // 테이블 구조 분석
            const tableStructure = reconstructTable(firstTable);
            
            // 헤더 분석
            const headers = tableStructure.headers;
            if (headers && headers.length > 0) {
                result.summary += `\n\n첫 번째 테이블의 헤더는 다음과 같습니다: **${headers.join('**, **')}**`;
                
                // 테이블 유형 추론
                const tableType = inferTableType(headers);
                if (tableType) {
                    result.summary += `\n\n이는 **${tableType}** 유형의 테이블로 보입니다.`;
                }
            }
            
            // 테이블 데이터 행 수
            const rowCount = tableStructure.data ? tableStructure.data.length : 0;
            if (rowCount > 0) {
                result.summary += `\n\n테이블에는 헤더를 제외하고 **${rowCount}개의 데이터 행**이 있습니다.`;
                
                // 데이터 합계/평균 계산 (숫자 열이 있는 경우)
                const numericColumnSummary = analyzeNumericColumns(tableStructure);
                if (numericColumnSummary) {
                    result.summary += `\n\n${numericColumnSummary}`;
                }
            }
            
            // 테이블 미리보기 생성
            result.preview = generateTablePreview(tableStructure);
        }
        
        return result;
    }
    
    // 테이블 재구성 함수
    function reconstructTable(table) {
        if (!table.cells || table.cells.length === 0) {
            return { headers: [], data: [] };
        }
        
        const result = {
            headers: [],
            data: []
        };
        
        // 행과 열 최대 크기 파악
        let maxRow = 0;
        let maxCol = 0;
        
        for (const cell of table.cells) {
            const rowIndex = cell.rowIndex || 0;
            const colIndex = cell.colIndex || 0;
            const rowSpan = cell.rowSpan || 1;
            const colSpan = cell.colSpan || 1;
            
            maxRow = Math.max(maxRow, rowIndex + rowSpan);
            maxCol = Math.max(maxCol, colIndex + colSpan);
        }
        
        // 2D 배열 초기화
        const grid = Array(maxRow).fill().map(() => Array(maxCol).fill(null));
        
        // 셀 채우기
        for (const cell of table.cells) {
            const rowIndex = cell.rowIndex || 0;
            const colIndex = cell.colIndex || 0;
            const rowSpan = cell.rowSpan || 1;
            const colSpan = cell.colSpan || 1;
            const text = cell.inferText || '';
            
            // 셀 채우기 (rowSpan, colSpan 고려)
            for (let r = 0; r < rowSpan; r++) {
                for (let c = 0; c < colSpan; c++) {
                    if (r === 0 && c === 0) {
                        // 원본 셀
                        grid[rowIndex][colIndex] = text;
                    } else {
                        // 확장 셀 (spanCell 표시)
                        grid[rowIndex + r][colIndex + c] = ''; // 빈 문자열로 표시
                    }
                }
            }
        }
        
        // 헤더 행 추출 (첫 번째 행)
        if (grid.length > 0) {
            result.headers = grid[0].filter(cell => cell !== null);
        }
        
        // 데이터 행 추출 (두 번째 행부터)
        for (let r = 1; r < grid.length; r++) {
            const row = grid[r].filter(cell => cell !== null);
            if (row.some(cell => cell)) { // 빈 행 제외
                result.data.push(row);
            }
        }
        
        return result;
    }
    
    // 테이블 유형 추론 함수
    function inferTableType(headers) {
        if (!headers || headers.length === 0) return null;
        
        // 헤더 텍스트를 소문자로 변환
        const lowerHeaders = headers.map(h => h.toLowerCase());
        
        // 특정 테이블 유형 패턴
        const tablePatterns = [
            {
                type: '상품 목록',
                required: ['상품명', '품목', '제품명', '항목', '내역'],
                optional: ['단가', '수량', '금액', '가격', '소계', '부가세']
            },
            {
                type: '가격표',
                required: ['가격', '금액', '단가'],
                optional: ['할인', '할인율', '부가세', '합계']
            },
            {
                type: '거래 내역',
                required: ['날짜', '거래일', '일자'],
                optional: ['금액', '거래처', '비고', '적요', '내역']
            },
            {
                type: '배송 정보',
                required: ['배송', '배달', '주소', '수취인', '수령인'],
                optional: ['연락처', '배송료', '배송상태']
            }
        ];
        
        // 테이블 유형 매칭
        for (const pattern of tablePatterns) {
            // 필수 키워드 매칭
            const hasRequired = pattern.required.some(keyword => 
                lowerHeaders.some(header => header.includes(keyword))
            );
            
            // 옵션 키워드 매칭
            const hasOptional = pattern.optional.some(keyword => 
                lowerHeaders.some(header => header.includes(keyword))
            );
            
            if (hasRequired && hasOptional) {
                return pattern.type;
            }
        }
        
        return null;
    }
    
    // 숫자 열 분석 함수
    function analyzeNumericColumns(tableStructure) {
        if (!tableStructure.headers || !tableStructure.data || tableStructure.data.length === 0) {
            return null;
        }
        
        const headers = tableStructure.headers;
        const data = tableStructure.data;
        let summary = '';
        
        // 금액 관련 열 찾기
        const amountColumnIndices = [];
        
        headers.forEach((header, index) => {
            const lowerHeader = header.toLowerCase();
            if (lowerHeader.includes('금액') || 
                lowerHeader.includes('가격') || 
                lowerHeader.includes('합계') || 
                lowerHeader.includes('소계') || 
                lowerHeader.includes('부가세') || 
                lowerHeader.includes('원')) {
                amountColumnIndices.push(index);
            }
        });
        
        // 금액 열이 있으면 합계 계산
        if (amountColumnIndices.length > 0) {
            for (const colIndex of amountColumnIndices) {
                if (colIndex >= headers.length) continue;
                
                const columnName = headers[colIndex];
                let sum = 0;
                let validCount = 0;
                
                for (const row of data) {
                    if (colIndex < row.length) {
                        // 숫자만 추출
                        const numStr = row[colIndex].replace(/[^0-9]/g, '');
                        if (numStr) {
                            const num = parseInt(numStr, 10);
                            if (!isNaN(num)) {
                                sum += num;
                                validCount++;
                            }
                        }
                    }
                }
                
                if (validCount > 0) {
                    // 천 단위 구분자 추가
                    const formattedSum = sum.toLocaleString('ko-KR');
                    summary += `**${columnName}** 열의 합계: **${formattedSum}**\n`;
                }
            }
        }
        
        return summary.trim();
    }
    
    // 테이블 미리보기 생성 함수
    function generateTablePreview(tableStructure) {
        if (!tableStructure.headers || !tableStructure.data || tableStructure.data.length === 0) {
            return null;
        }
        
        const headers = tableStructure.headers;
        const data = tableStructure.data;
        
        // 최대 5개 행까지만 표시
        const maxRows = Math.min(5, data.length);
        let preview = '**테이블 미리보기:**\n\n';
        
        // 헤더 행
        let headerRow = '';
        for (const header of headers) {
            headerRow += `| ${header} `;
        }
        preview += headerRow + '|\n';
        
        // 구분선
        let separatorRow = '';
        for (let i = 0; i < headers.length; i++) {
            separatorRow += '| --- ';
        }
        preview += separatorRow + '|\n';
        
        // 데이터 행
        for (let i = 0; i < maxRows; i++) {
            let row = '';
            const dataRow = data[i];
            
            for (let j = 0; j < headers.length; j++) {
                if (j < dataRow.length) {
                    row += `| ${dataRow[j]} `;
                } else {
                    row += '|  ';
                }
            }
            
            preview += row + '|\n';
        }
        
        // 생략 표시
        if (data.length > maxRows) {
            preview += `\n_...외 ${data.length - maxRows}개의 행이 더 있습니다._`;
        }
        
        return preview;
    }
    
    // 텍스트 내용 분석 함수
    function analyzeTextContent(imageData) {
        if (!imageData || !imageData.fields || imageData.fields.length === 0) {
            return null;
        }
        
        // 텍스트 특성 분석
        const allText = extractAllText(imageData);
        const textLength = allText.length;
        
        // 결과 메시지 구성
        let analysis = `문서 텍스트 분석 결과, 총 **${textLength}자**의 텍스트가 추출되었습니다.`;
        
        // 텍스트 패턴 분석
        const patterns = [
            { type: '이메일', regex: /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, found: [] },
            { type: '전화번호', regex: /(\d{2,3}[-\s]?\d{3,4}[-\s]?\d{4})/g, found: [] },
            { type: '웹사이트', regex: /(https?:\/\/[^\s]+|www\.[^\s]+\.[^\s]+)/g, found: [] },
            { type: '주소', regex: /([가-힣]+(시|도|군|구|읍|면|동)\s[가-힣]+(로|길|가)\s?\d+)/g, found: [] }
        ];
        
        let foundPatterns = false;
        
        for (const pattern of patterns) {
            const matches = allText.match(pattern.regex);
            if (matches && matches.length > 0) {
                pattern.found = [...new Set(matches)]; // 중복 제거
                foundPatterns = true;
            }
        }
        
        if (foundPatterns) {
            analysis += '\n\n문서에서 다음 정보를 발견했습니다:';
            
            for (const pattern of patterns) {
                if (pattern.found.length > 0) {
                    analysis += `\n- **${pattern.type}**: ${pattern.found.join(', ')}`;
                }
            }
        }
        
        return analysis;
    }
    
    // OCR 품질 및 신뢰도 분석 함수
    function analyzeOcrQuality(imageData) {
        if (!imageData || !imageData.fields) return null;
        
        let totalConfidence = 0;
        let fieldsCount = 0;
        let lowConfidenceCount = 0;
        
        // 필드 신뢰도 분석
        if (imageData.fields && imageData.fields.length > 0) {
            for (const field of imageData.fields) {
                if (field.inferConfidence !== undefined) {
                    totalConfidence += field.inferConfidence;
                    fieldsCount++;
                    
                    if (field.inferConfidence < 0.7) {
                        lowConfidenceCount++;
                    }
                }
            }
        }
        
        // 테이블 셀 신뢰도 분석
        if (imageData.tables && imageData.tables.length > 0) {
            for (const table of imageData.tables) {
                if (table.cells && table.cells.length > 0) {
                    for (const cell of table.cells) {
                        if (cell.inferConfidence !== undefined) {
                            totalConfidence += cell.inferConfidence;
                            fieldsCount++;
                            
                            if (cell.inferConfidence < 0.7) {
                                lowConfidenceCount++;
                            }
                        }
                    }
                }
            }
        }
        
        // 평균 신뢰도 계산
        if (fieldsCount === 0) return null;
        
        const avgConfidence = totalConfidence / fieldsCount;
        const lowConfidencePercent = (lowConfidenceCount / fieldsCount) * 100;
        
        // 품질 레벨 결정
        let qualityLevel;
        if (avgConfidence >= 0.9) {
            qualityLevel = '매우 높음';
        } else if (avgConfidence >= 0.8) {
            qualityLevel = '높음';
        } else if (avgConfidence >= 0.7) {
            qualityLevel = '양호';
        } else if (avgConfidence >= 0.6) {
            qualityLevel = '보통';
        } else {
            qualityLevel = '낮음';
        }
        
        // 결과 메시지 구성
        let analysis = `OCR 인식 품질: **${qualityLevel}** (평균 신뢰도: ${(avgConfidence * 100).toFixed(1)}%)`;
        
        if (lowConfidenceCount > 0) {
            analysis += `\n\n전체 텍스트 중 약 ${lowConfidencePercent.toFixed(1)}%가 낮은 신뢰도로 인식되었습니다. `;
            
            if (lowConfidencePercent > 30) {
                analysis += '원본 이미지를 확인하고 필요시 수정하는 것이 좋습니다.';
            }
        }
        
        return analysis;
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
            { type: '세금계산서', keywords: ['세금계산서', '부가가치세', '공급가액', '사업자등록번호'] },
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
    function extractKeyValuePairsFromText(text) {
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
    
    // 텍스트에서 날짜 추출
    function extractDatesFromText(text) {
        const datePatterns = [
            /\d{4}[-\.\/](0?[1-9]|1[0-2])[-\.\/](0?[1-9]|[12][0-9]|3[01])/g,  // YYYY-MM-DD
            /(0?[1-9]|1[0-2])[-\.\/](0?[1-9]|[12][0-9]|3[01])[-\.\/]\d{4}/g,  // MM-DD-YYYY
            /\d{4}년\s*(0?[1-9]|1[0-2])월\s*(0?[1-9]|[12][0-9]|3[01])일/g     // YYYY년 MM월 DD일
        ];
        
        let dates = [];
        for (const pattern of datePatterns) {
            const matches = text.match(pattern) || [];
            dates = dates.concat(matches);
        }
        
        // 중복 제거
        return [...new Set(dates)];
    }
    
    // 텍스트에서 금액 추출
    function extractAmountsFromText(text) {
        const amountPatterns = [
            /((합계|총액|금액|총금액|결제금액|청구금액)\s*:?\s*)?([\d,]+)(\s*원|\s*₩)?/g,
            /([\d,]+)(원|₩)/g
        ];
        
        let amounts = [];
        for (const pattern of amountPatterns) {
            const matches = text.match(pattern) || [];
            amounts = amounts.concat(matches);
        }
        
        // 중복 제거
        return [...new Set(amounts)].map(amt => amt.trim());
    }
    
    // 텍스트에서 테이블 형태 확인
    function hasTableInText(text) {
        // 테이블 형태의 패턴 검색
        const tablePatterns = [
            /[+\-|]{3,}/,               // +---+---+ 형태
            /\|\s*[^|]+\s*\|/,          // | 내용 | 형태
            /\+[=\-]+\+[=\-]+\+/,       // +=====+=====+ 형태
            /[^\|]\|[^\|]+\|[^\|]+\|/   // 값|값|값 형태
        ];
        
        for (const pattern of tablePatterns) {
            if (pattern.test(text)) {
                return true;
            }
        }
        
        return false;
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
        debugToUI(`챗봇 오류 메시지 표시: ${message}`, "error");
        $('#chatbot-container .chat-loading').hide();
        const chatMessages = $('#chatbot-container .chat-messages');
        chatMessages.html(`
            <div class="chat-message">
                <div class="chat-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="chat-content">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>${message}
                    </div>
                </div>
            </div>
        `).show();
    }
    
    // 메시지 포맷팅 함수 (마크다운 스타일 지원)
    function formatMessage(message) {
        if (!message) return '';
        
        // 줄바꿈을 <br>로 변환
        let formatted = message.replace(/\n/g, '<br>');
        
        // 볼드 텍스트 처리 (**텍스트**)
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // 이탤릭 텍스트 처리 (*텍스트*)
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        // 인라인 코드 처리 (`텍스트`)
        formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // 테이블 마크다운 처리
        if (formatted.includes('|') && formatted.includes('---')) {
            const lines = formatted.split('<br>');
            let inTable = false;
            let tableHtml = '<table class="table table-sm table-bordered mb-3">';
            
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();
                
                if (line.includes('|')) {
                    if (!inTable) {
                        inTable = true;
                    }
                    
                    // 구분선 행 건너뛰기
                    if (line.includes('---')) continue;
                    
                    // 헤더 또는 데이터 행 처리
                    const isHeader = i === 0 || (i > 0 && lines[i-1].includes('|') && lines[i+1] && lines[i+1].includes('---'));
                    const cells = line.split('|').filter(cell => cell.trim() !== '');
                    
                    if (isHeader) {
                        tableHtml += '<thead><tr>';
                        cells.forEach(cell => {
                            tableHtml += `<th>${cell.trim()}</th>`;
                        });
                        tableHtml += '</tr></thead><tbody>';
                    } else {
                        tableHtml += '<tr>';
                        cells.forEach(cell => {
                            tableHtml += `<td>${cell.trim()}</td>`;
                        });
                        tableHtml += '</tr>';
                    }
                } else if (inTable && line === '') {
                    // 테이블 종료
                    inTable = false;
                    tableHtml += '</tbody></table>';
                    lines[i] = tableHtml;
                    tableHtml = '<table class="table table-sm table-bordered mb-3">';
                }
            }
            
            // 마지막 테이블 닫기
            if (inTable) {
                tableHtml += '</tbody></table>';
                lines.push(tableHtml);
            }
            
            formatted = lines.join('<br>');
        }
        
        return formatted;
    }
    
    // 자동 새로고침 기능 (처리 중인 작업의 경우)
    let refreshInterval;
    
    function setupAutoRefresh() {
        const autoRefresh = $('#autoRefresh').is(':checked');
        
        if (autoRefresh) {
            refreshInterval = setInterval(refreshJobStatus, 5000);
            debugToUI("자동 새로고침 활성화됨 (5초 간격)");
        } else {
            clearInterval(refreshInterval);
            debugToUI("자동 새로고침 비활성화됨");
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
                        debugToUI("작업 완료됨, 페이지 새로고침");
                        clearInterval(refreshInterval);
                        location.reload();
                    }
                }
            }
        });
    }
    
    // 페이지 초기화 실행
    initialize();
    
    // 전역 함수 참조 설정
    ocrResultProcessor = loadAndProcessOcrResults;
});