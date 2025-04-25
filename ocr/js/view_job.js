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
        currentJobId = $('#cancelJobBtn').data('job-id') || $('#deleteJobBtn').data('job-id');
        debugToUI(`현재 작업 ID: ${currentJobId}`);
    
        // 페이지 로드 시 각 파일의 첫 번째 결과(텍스트) 자동 로드
        $('.result-card').each(function() {
            const idx = $(this).attr('id').split('-').pop();
            const textBtn = $(this).find('.text-view-btn');
            if (textBtn.length) {
                debugToUI(`자동 텍스트 로드: 인덱스 ${idx}`);
                const path = textBtn.data('path');
                // loadTextResult(path, idx); // 필요하면 텍스트 로드 유지
            }
        });
    
        if ($('#autoRefresh').length) {
            debugToUI('자동 새로고침 기능 설정');
            setupAutoRefresh();
        }
    
        const resultsTabActive = $('#results-tab').hasClass('active');
        debugToUI(`결과 탭 활성화 상태: ${resultsTabActive}`);
    
        // 결과 탭이 활성화 상태이고, 결과 카드가 있으면 바로 분석 시작
        if (resultsTabActive && $('.result-card').length > 0) {
            debugToUI('결과 탭 활성화 확인, OCR 결과 로드 시작 (초기화)');
            // 약간의 지연을 주어 다른 요소 로딩 시간을 확보할 수 있음
            setTimeout(loadAndProcessOcrResults, 500);
        } else {
             debugToUI("결과 탭이 비활성화 상태이거나 결과 카드가 없어 자동 분석 시작 안 함");
             // 만약 결과 카드가 있는데도 분석이 안된다면, 결과 탭이 기본 active 상태인지 PHP 코드 확인 필요
             if ($('.result-card').length > 0 && !resultsTabActive) {
                 debugToUI("결과 카드는 있으나 결과 탭이 active 상태가 아님. PHP 파일에서 #results-tab에 active 클래스 추가 고려.", "warning");
             }
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
        const jsonPath = $(this).data('json-path'); // JSON 경로도 가져옵니다.
    
        debugToUI(`결과 보기 버튼 클릭: 파일 ID ${fileId}, 텍스트 경로 ${textPath}, JSON 경로 ${jsonPath}`);
    
        // 1. 결과 탭으로 전환
        $('#results-tab').tab('show');
        debugToUI("결과 탭으로 전환됨");
    
        // 2. 해당 파일의 결과 카드 찾기 및 스크롤
        const resultCards = $('.result-card');
        let targetCard = null;
        resultCards.each(function() {
            const idx = $(this).attr('id').split('-').pop();
            const resultFileId = $(this).find('.provide-feedback-btn').data('file-id'); // 피드백 버튼에서 file-id 확인
    
            if (resultFileId == fileId) {
                debugToUI(`해당 결과 카드 발견: 인덱스 ${idx}, 파일 ID ${resultFileId}`);
                targetCard = $(this);
    
                // 해당 카드로 스크롤
                $('html, body').animate({
                    scrollTop: $(this).offset().top - 100 // 상단 네비게이션 바 높이 고려
                }, 500);
    
                // 3. 텍스트 뷰 활성화 (선택적이지만 사용자 경험상 좋음)
                // $(this).find('.text-view-btn').click(); // 이전에 이미 텍스트는 로드되었을 수 있음
    
                return false; // 루프 종료
            }
        });
    
        // 4. *** JSON 분석 시작 (가장 중요) ***
        if (targetCard && jsonPath) {
             debugToUI("타겟 카드와 JSON 경로 확인됨, loadAndProcessOcrResults 호출 시도");
             // loadAndProcessOcrResults 함수가 특정 카드에 종속되지 않고
             // 첫 번째 카드의 JSON을 기준으로 분석한다면, 그냥 호출해도 됩니다.
             // 만약 클릭된 카드의 JSON을 분석해야 한다면, loadAndProcessOcrResults 함수를 수정해야 합니다.
             // 현재 로직은 첫 번째 카드를 기준으로 하므로, 그냥 호출합니다.
             loadAndProcessOcrResults();
        } else if (!targetCard) {
             debugToUI(`파일 ID ${fileId}에 해당하는 결과 카드를 찾지 못함`, 'warning');
        } else if (!jsonPath) {
             debugToUI(`파일 ID ${fileId}에 대한 JSON 경로가 없음`, 'warning');
             // JSON 경로가 없으면 텍스트 기반 분석을 시도할 수 있습니다.
             if (textPath) {
                  debugToUI("JSON 경로가 없어 텍스트 기반 분석 시도");
                  // loadAndProcessTextResults(textPath); // 텍스트 분석 함수 호출 (필요시 주석 해제)
                  showChatbotError("JSON 결과 파일이 없어 분석을 진행할 수 없습니다. 텍스트 결과만 확인 가능합니다."); // 또는 오류 메시지
             } else {
                  showChatbotError("분석할 결과 데이터(JSON 또는 Text)를 찾을 수 없습니다.");
             }
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
        debugToUI("OCR 결과 로드 및 처리 시작 (loadAndProcessOcrResults)"); // 함수 시작 로그

        if ($('.result-card').length === 0) {
            debugToUI("처리할 결과 카드를 찾을 수 없음", "warning");
            showChatbotError('처리할 결과 카드를 찾을 수 없습니다.');
            return;
        }

        const firstCard = $('.result-card').first();
        const jsonBtn = firstCard.find('.json-view-btn');

        if (jsonBtn.length === 0) {
            // ... (기존 텍스트 대체 로직) ...
            return;
        }

        const jsonPath = jsonBtn.data('path');
        if (!jsonPath) {
            debugToUI("JSON 파일 경로가 비어있음", "error");
            showChatbotError('JSON 파일 경로를 찾을 수 없습니다.');
            return;
        }

        debugToUI(`JSON 파일 경로 확인: ${jsonPath}`);

        $('#chatbot-container .chat-loading').show();
        $('#chatbot-container .chat-messages').hide().empty();
        debugToUI("채팅 로딩 표시됨"); // 로딩 표시 확인

        $.ajax({
            url: 'ajax_get_file_content.php',
            type: 'GET',
            data: { path: jsonPath },
            dataType: 'json', // PHP가 application/json 헤더를 보내므로 jQuery가 자동으로 파싱 시도
            success: function(response) {
                debugToUI(`AJAX 응답 받음: success=${response.success}`);

                if (response.success && response.content) {
                    try {
                        let jsonContent = response.content;
                        debugToUI(`받은 JSON 콘텐츠 길이: ${jsonContent?.length || 0}`);

                        // *** 중요: PHP에서 이미 application/json으로 보내고, jQuery가 dataType:'json'으로 받으면
                        // response.content는 이미 파싱된 객체일 수 있습니다. 문자열인지 확인 후 파싱합니다. ***
                        let ocrData;
                        if (typeof jsonContent === 'string') {
                            debugToUI("콘텐츠가 문자열이므로 JSON 파싱 시도...");
                            // JSON 유효성 검사 및 전처리 (필요하다면)
                            jsonContent = preprocessJsonContent(jsonContent); // 필요시 사용
                            if (!isValidJSON(jsonContent)) { // isValidJSON 함수 필요
                                debugToUI("유효하지 않은 JSON 형식 - 텍스트 기반 분석으로 대체 시도", "error");
                                // ... (텍스트 대체 로직) ...
                                return;
                            }
                            ocrData = JSON.parse(jsonContent);
                            debugToUI("JSON 파싱 성공 (문자열에서)");
                        } else if (typeof jsonContent === 'object') {
                            debugToUI("콘텐츠가 이미 객체 형태임 (jQuery가 자동 파싱했을 수 있음)");
                            ocrData = jsonContent; // 이미 파싱된 객체 사용
                        } else {
                            debugToUI(`예상치 못한 콘텐츠 타입: ${typeof jsonContent}`, "error");
                            showChatbotError('잘못된 형식의 OCR 데이터를 받았습니다.');
                            return;
                        }


                        // OCR 데이터 유효성 검증 강화
                        if (!ocrData || typeof ocrData !== 'object') {
                            debugToUI("파싱 후 데이터가 유효한 객체가 아님", "error");
                            showChatbotError('OCR 데이터 구조가 올바르지 않습니다 (객체 아님).');
                            return;
                        }
                        debugToUI("OCR 데이터 객체 확인됨. images 배열 확인 시도...");

                        if (!ocrData.images || !Array.isArray(ocrData.images) || ocrData.images.length === 0) {
                            debugToUI("유효한 OCR 데이터 구조가 아님 - images 배열 문제", "error");
                            // 텍스트 대체 로직 실행 또는 오류 표시
                            showChatbotError('유효한 OCR 데이터 구조가 아닙니다 (images 배열 누락 또는 비어 있음).');
                            // 텍스트 대체 로직이 필요하면 여기에 추가
                            // const textBtn = firstCard.find('.text-view-btn');
                            // if (textBtn.length > 0) {
                            //    loadAndProcessTextResults(textBtn.data('path'));
                            // }
                            return; // 여기서 종료해야 함
                        }
                        debugToUI(`images 배열 확인됨 (길이: ${ocrData.images.length}). 데이터 처리 시작...`);

                        // 데이터 처리 및 표시 함수 호출
                        processAndDisplayOcrData(ocrData); // *** 여기가 다음 단계 ***

                        // *** 중요: processAndDisplayOcrData가 성공적으로 끝나면 로딩 숨김 ***
                        // 이 코드는 processAndDisplayOcrData 함수 *내부의 끝*으로 옮기는 것이 더 안전할 수 있음
                        // $('#chatbot-container .chat-loading').hide();
                        // $('#chatbot-container .chat-messages').show();
                        // debugToUI("채팅 로딩 숨김 및 메시지 표시됨 (성공)");

                    } catch (e) {
                        debugToUI(`JSON 처리 중 오류 발생: ${e.message}`, "error");
                        debugToUI(`오류 스택: ${e.stack}`, "error");
                        // 오류 발생 시 텍스트 대체 로직 실행 또는 오류 표시
                        showChatbotError('OCR 데이터 처리 중 오류가 발생했습니다: ' + e.message);
                        // 텍스트 대체 로직이 필요하면 여기에 추가
                        // const textBtn = firstCard.find('.text-view-btn');
                        // if (textBtn.length > 0) {
                        //     loadAndProcessTextResults(textBtn.data('path'));
                        // }
                    }
                } else {
                    // AJAX 요청은 성공했으나, PHP에서 success: false를 반환했거나 content가 없는 경우
                    const errorMessage = response.message || '알 수 없는 오류';
                    debugToUI(`AJAX 응답 실패 또는 콘텐츠 없음: ${errorMessage}`, "error");
                    showChatbotError('OCR 결과 파일을 로드할 수 없습니다: ' + errorMessage);
                    // 텍스트 대체 로직이 필요하면 여기에 추가
                }
            },
            error: function(xhr, status, error) {
                debugToUI(`AJAX 요청 오류: ${status} - ${error}`, "error");
                if (xhr.responseText) {
                    debugToUI(`서버 응답 내용 (일부): ${xhr.responseText.substring(0, 200)}...`, "error");
                }
                showChatbotError('OCR 결과 파일 로드 중 서버 오류가 발생했습니다: ' + error);
                // 텍스트 대체 로직이 필요하면 여기에 추가
            }
        });
        debugToUI("AJAX 요청 보냄");
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
        if (typeof str !== 'string') return false; // 문자열이 아니면 유효하지 않음
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
        debugToUI("OCR 데이터 처리 및 표시 시작 (processAndDisplayOcrData - JSON 필드 표시 버전)");
        const chatMessages = $('#chatbot-container .chat-messages');
        chatMessages.empty(); // 이전 메시지 지우기
    
        try {
            // 1. 기본 정보 표시
            addChatbotMessage(`**OCR 결과 분석 (JSON 기반)**\n요청 ID: \`${ocrData.requestId || 'N/A'}\`\n타임스탬프: ${ocrData.timestamp ? new Date(ocrData.timestamp).toLocaleString() : 'N/A'}`);
            debugToUI("기본 정보 메시지 추가됨");
    
            if (!ocrData.images || !Array.isArray(ocrData.images) || ocrData.images.length === 0) {
                debugToUI("처리할 이미지 데이터 없음", "warning");
                addChatbotMessage("오류: 분석할 이미지 데이터가 없습니다.");
                // 로딩 숨기기 및 종료
                $('#chatbot-container .chat-loading').hide();
                $('#chatbot-container .chat-messages').show();
                return;
            }
    
            // 여러 이미지가 있을 수 있으므로 반복 처리 (여기서는 첫 번째 이미지만 처리)
            const image = ocrData.images[0];
            if (typeof image !== 'object' || image === null) {
                debugToUI("첫 번째 이미지 데이터가 유효하지 않음", "warning");
                addChatbotMessage("오류: 첫 번째 이미지 데이터가 올바르지 않습니다.");
                // 로딩 숨기기 및 종료
                $('#chatbot-container .chat-loading').hide();
                $('#chatbot-container .chat-messages').show();
                return;
            }
    
            debugToUI(`이미지 분석 시작 (UID: ${image.uid || 'N/A'})`);
            let imageInfoMsg = `**이미지 정보**\n- UID: \`${image.uid || 'N/A'}\`\n- 이름: \`${image.name || 'N/A'}\``;
            imageInfoMsg += `\n- 처리 결과: ${image.inferResult || 'N/A'} (${image.message || 'N/A'})`;
            if (image.convertedImageInfo) {
                imageInfoMsg += `\n- 변환된 이미지 크기: ${image.convertedImageInfo.width || '?'} x ${image.convertedImageInfo.height || '?'}`;
                imageInfoMsg += `\n- 페이지 인덱스: ${image.convertedImageInfo.pageIndex !== undefined ? image.convertedImageInfo.pageIndex : 'N/A'}`;
                imageInfoMsg += `\n- 긴 이미지 여부: ${image.convertedImageInfo.longImage ? '예' : '아니오'}`;
            }
            addChatbotMessage(imageInfoMsg);
            debugToUI("이미지 정보 메시지 추가됨");
    
            // 2. 필드 정보 테이블 표시
            if (image.fields && Array.isArray(image.fields) && image.fields.length > 0) {
                debugToUI(`${image.fields.length}개의 필드 발견, 테이블 생성 시작...`);
                let fieldTable = '**인식된 필드 목록**\n\n';
                // 테이블 헤더 (마크다운)
                fieldTable += '| # | 인식된 텍스트 | 신뢰도 | 타입 | 줄바꿈 | 위치 (시작점) |\n';
                fieldTable += '|---|---------------|--------|------|-------|----------------|\n';
    
                // 테이블 내용 (마크다운)
                image.fields.forEach((field, index) => {
                    const text = field.inferText || '';
                    // 텍스트가 너무 길면 자르기 (테이블 깨짐 방지)
                    const displayText = text.length > 30 ? text.substring(0, 28) + '...' : text;
                    const confidence = field.inferConfidence !== undefined ? (field.inferConfidence * 100).toFixed(1) + '%' : 'N/A';
                    const type = field.type || 'N/A';
                    const lineBreak = field.lineBreak ? '예' : '아니오';
                    // 위치 정보 요약 (첫 번째 꼭지점)
                    let position = 'N/A';
                    if (field.boundingPoly && field.boundingPoly.vertices && field.boundingPoly.vertices.length > 0) {
                        const startVertex = field.boundingPoly.vertices[0];
                        position = `(${startVertex.x || '?'}, ${startVertex.y || '?'})`;
                    }
    
                    // 마크다운 테이블 행 추가 (파이프 문자가 텍스트에 포함될 경우 HTML 엔티티로 변경)
                    fieldTable += `| ${index + 1} | \`${displayText.replace(/\|/g, '|')}\` | ${confidence} | ${type} | ${lineBreak} | ${position} |\n`;
                });
    
                addChatbotMessage(fieldTable);
                debugToUI("필드 정보 테이블 메시지 추가됨");
    
                // 추가: 평균 신뢰도 계산 및 표시 (기존 OCR 품질 분석 함수 활용 가능)
                const qualityAnalysis = analyzeOcrQuality(image); // 기존 함수 재활용
                if (qualityAnalysis) {
                    addChatbotMessage("**전체 필드 품질 분석**\n" + qualityAnalysis);
                    debugToUI("품질 분석 메시지 추가됨");
                }
    
            } else {
                debugToUI("이미지에서 필드 정보를 찾을 수 없음", "warning");
                addChatbotMessage("이미지에서 인식된 텍스트 필드를 찾을 수 없습니다.");
            }
    
            // 3. 테이블 데이터 요약 (선택적)
            // JSON에 'tables' 필드가 있다면 간단히 요약 정보만 표시할 수 있습니다.
            // 상세 테이블 내용은 별도 뷰어에서 확인하도록 유도
            if (image.tables && Array.isArray(image.tables) && image.tables.length > 0) {
                debugToUI(`${image.tables.length}개의 테이블 구조 발견`);
                let tableSummary = `**인식된 테이블 구조**\n\n문서 내에서 ${image.tables.length}개의 테이블 구조가 인식되었습니다.`;
                // 첫 번째 테이블의 셀 개수 정도만 요약
                if (image.tables[0].cells && Array.isArray(image.tables[0].cells)) {
                    tableSummary += `\n첫 번째 테이블에는 약 ${image.tables[0].cells.length}개의 셀이 포함되어 있습니다.`;
                }
                tableSummary += "\n\n상세한 테이블 내용은 아래 '테이블 결과' 탭 또는 'JSON 데이터' 탭에서 확인하세요.";
                addChatbotMessage(tableSummary);
                debugToUI("테이블 요약 메시지 추가됨");
            } else {
                debugToUI("테이블 구조 데이터 없음");
            }
    
    
            // 모든 분석 및 메시지 추가 완료 후 로딩 숨기기
            $('#chatbot-container .chat-loading').hide();
            $('#chatbot-container .chat-messages').show();
            debugToUI("채팅 로딩 숨김 및 메시지 최종 표시됨 (processAndDisplayOcrData - JSON 필드 표시 버전 완료)");
    
        } catch (e) {
            debugToUI(`processAndDisplayOcrData (JSON 필드 표시 버전) 내부 오류: ${e.message}`, "error");
            debugToUI(`오류 스택: ${e.stack}`, "error");
            showChatbotError('JSON 결과 표시 중 오류 발생: ' + e.message + '. 개발자 콘솔 확인 요망.');
        }
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
    
    // OCR 품질 및 신뢰도 분석 함수
    function analyzeOcrQuality(imageData) {
        if (!imageData) return null;
    
        let totalConfidence = 0;
        let fieldCount = 0; // 필드 개수만 카운트 (테이블 셀 제외 가능)
        let lowConfidenceCount = 0;
        const lowConfidenceThreshold = 0.7; // 낮은 신뢰도 기준
    
        // 필드 신뢰도 분석
        if (imageData.fields && Array.isArray(imageData.fields)) {
            imageData.fields.forEach(field => {
                if (field.inferConfidence !== undefined && typeof field.inferConfidence === 'number') {
                    totalConfidence += field.inferConfidence;
                    fieldCount++;
                    if (field.inferConfidence < lowConfidenceThreshold) {
                        lowConfidenceCount++;
                    }
                } else {
                    // 신뢰도 값이 없거나 숫자가 아닌 필드도 카운트할 수 있음 (선택적)
                    // fieldCount++;
                }
            });
        }
    
        // 테이블 셀 신뢰도 분석 (선택적: 포함하려면 주석 해제)
        /*
        if (imageData.tables && Array.isArray(imageData.tables)) {
            imageData.tables.forEach(table => {
                if (table.cells && Array.isArray(table.cells)) {
                    table.cells.forEach(cell => {
                        if (cell.inferConfidence !== undefined && typeof cell.inferConfidence === 'number') {
                            totalConfidence += cell.inferConfidence;
                            fieldCount++; // fieldCount를 같이 사용하면 필드+셀 전체 평균이 됨
                            if (cell.inferConfidence < lowConfidenceThreshold) {
                                lowConfidenceCount++;
                            }
                        }
                    });
                }
            });
        }
        */
    
        if (fieldCount === 0) return "신뢰도를 계산할 필드가 없습니다."; // 필드가 없을 때 메시지
    
        const avgConfidence = totalConfidence / fieldCount;
        const lowConfidencePercent = (lowConfidenceCount / fieldCount) * 100;
    
        let qualityLevel;
        if (avgConfidence >= 0.95) qualityLevel = '매우 높음';
        else if (avgConfidence >= 0.85) qualityLevel = '높음';
        else if (avgConfidence >= 0.75) qualityLevel = '양호';
        else if (avgConfidence >= 0.60) qualityLevel = '보통';
        else qualityLevel = '낮음';
    
        let analysis = `평균 신뢰도: **${(avgConfidence * 100).toFixed(1)}%** (품질: **${qualityLevel}**)`;
        if (lowConfidenceCount > 0) {
            analysis += `\n- 낮은 신뢰도 필드 (<${lowConfidenceThreshold*100}%): ${lowConfidenceCount}개 (${lowConfidencePercent.toFixed(1)}%)`;
            if (lowConfidencePercent > 20) { // 낮은 신뢰도 비율이 높으면 경고 추가
                analysis += "\n- _주의: 낮은 신뢰도의 필드가 많아 일부 텍스트가 부정확할 수 있습니다._";
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
    
    // HTML 이스케이프 함수 (XSS 방지용)
    function escapeHtml(text) {
        if (typeof text !== 'string') return text; // 문자열 아니면 그대로 반환
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
    
        // 1. 기본 HTML 이스케이프 (XSS 방지) - 테이블 변환 전에 하면 안됨
        // let formatted = escapeHtml(message); // 여기서 하면 마크다운 태그까지 이스케이프됨
    
        // 2. 줄바꿈 처리
        let formatted = message.replace(/\n/g, '<br>');
    
        // 3. 마크다운 스타일 처리 (볼드, 이탤릭, 코드)
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/`(.*?)`/g, '<code>$1</code>');
    
        // 4. 테이블 마크다운 처리
        // 테이블 시작 패턴 확인 (| 헤더 | 형식과 |---| 구분선)
        if (formatted.includes('|') && formatted.includes('<br>|---')) {
            const lines = formatted.split('<br>');
            let tableHtml = '';
            let inTable = false;
            let headerProcessed = false;
    
            lines.forEach((line, index) => {
                const trimmedLine = line.trim();
                // 테이블 행 시작 조건: 파이프로 시작하고 끝나며, 구분선 다음 행이거나 첫 테이블 행
                if (trimmedLine.startsWith('|') && trimmedLine.endsWith('|')) {
                    if (!inTable) {
                        // 새 테이블 시작
                        tableHtml += '<div class="table-responsive mb-3"><table class="table table-sm table-bordered table-hover chatbot-table">';
                        inTable = true;
                        headerProcessed = false;
                    }
    
                    const cells = trimmedLine.split('|').slice(1, -1); // 양 끝 빈 문자열 제거
    
                    // 구분선인지 확인 (|---|---| 형식)
                    const isSeparator = cells.every(cell => /^\s*-{3,}\s*$/.test(cell));
    
                    if (isSeparator) {
                        if (!headerProcessed) {
                             // 헤더가 없는데 구분선만 있는 이상한 경우, 무시하거나 헤더 추가
                             tableHtml += '<tbody>'; // 바로 tbody 시작
                             headerProcessed = true; // 구분선 처리 완료 (헤더 없이)
                        }
                        // 구분선 자체는 HTML로 변환하지 않음
                    } else if (!headerProcessed) {
                        // 헤더 행 처리
                        tableHtml += '<thead><tr>';
                        cells.forEach(cell => {
                            // 헤더 셀 내부의 마크다운(`) 처리
                            const headerContent = cell.trim().replace(/`(.*?)`/g, '<code>$1</code>');
                            tableHtml += `<th>${headerContent}</th>`;
                        });
                        tableHtml += '</tr></thead><tbody>';
                        headerProcessed = true;
                    } else {
                        // 데이터 행 처리
                        tableHtml += '<tr>';
                        cells.forEach(cell => {
                             // 데이터 셀 내부의 마크다운(`) 처리 및 HTML 허용 안 함 (이스케이프 필요)
                             const cellContent = escapeHtml(cell.trim()).replace(/`(.*?)`/g, '<code>$1</code>');
                             tableHtml += `<td>${cellContent}</td>`; // 데이터는 escapeHtml 적용
                        });
                        tableHtml += '</tr>';
                    }
                } else {
                    // 테이블이 아닌 행
                    if (inTable) {
                        // 테이블 종료
                        tableHtml += '</tbody></table></div>';
                        inTable = false;
                        // 이전에 테이블이 아닌 행도 추가
                        formatted = formatted.replace(line, tableHtml + line); // 테이블 HTML 삽입
                        tableHtml = ''; // 테이블 HTML 초기화
                    }
                    // 테이블 아닌 행은 그대로 둠 (이미 <br> 처리됨)
                }
            });
    
            // 마지막 줄까지 테이블이었을 경우 닫기
            if (inTable) {
                tableHtml += '</tbody></table></div>';
                formatted += tableHtml; // 마지막 테이블 추가
            }
        }
    
        // 테이블 처리 후에는 일반 줄바꿈만 남도록 정리
        // (주의: 복잡한 중첩 구조에서는 문제가 될 수 있음)
        // formatted = formatted.replace(/<br>\s*$/, ''); // 마지막 줄바꿈 제거
    
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