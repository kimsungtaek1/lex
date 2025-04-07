<?php
session_start();
$additional_css = '<link rel="stylesheet" href="css/chatbot.css">';
include 'header.php';
// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 5) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
?>
<div class="container">
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="stat-tab active" data-type="recovery">|&nbsp;&nbsp;개인회생 법률 챗봇</th>
					<th class="stat-tab " data-type="bankruptcy">|&nbsp;&nbsp;개인파산 법률 챗봇</th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="chat-container">
        <div class="conversation-list"></div>
        <div class="chat-content-area">
            <div class="chat-messages"></div>
            <div class="chat-input">
                <label for="file-upload" id="file-upload-label">파일 첨부</label>
                <input type="file" id="file-upload" accept=".pdf,.jpg,.jpeg,.png,.txt,.docx">
                <span id="file-name"></span>
                <input type="text" id="user-input" placeholder="궁금하신 내용을 작성해주십시오.">
                <button id="send-button" disabled>전송</button>
                <button id="new-chat-button">새 대화</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        const $chatMessages = $('.chat-messages');
        const $userInput = $('#user-input');
        const $sendButton = $('#send-button');
        let selectedFile = null;
        let conversationId = '';
        const member = <?php echo $_SESSION['employee_no']; ?>;

        loadConversations();

        function loadConversations() {
            $.get('api/chatbot/get_chatbot_conversations.php', { member }, function(conversations) {
                const $list = $('.conversation-list');
                $list.empty();
                conversations.forEach(conv => {
                    const fileInfo = conv.file_metadata ? JSON.parse(conv.file_metadata) : null;
                    const displayText = fileInfo ? fileInfo.name : conv.question.substring(0, 30) + '...';
                    $list.append(
                        '<div class="conversation-item" data-id="' + conv.conversation_id + '">' +
                            displayText +
                        '</div>'
                    );
                });
            });
        }

        function loadConversation(id) {
            $.get('api/chatbot/get_chatbot_conversation.php', { conversation_id: id }, function(messages) {
                $chatMessages.empty();
                messages.forEach(msg => {
                    appendMessage(msg.role, msg.content, msg.file_metadata);
                });
                scrollToBottom();
            });
        }

        function appendMessage(role, content, fileMetadata = null) {
            let html = '<div class="message ' + role + '">';
            
            if (fileMetadata) {
                const fileInfo = JSON.parse(fileMetadata);
                html += '<div class="file-info">파일: ' + fileInfo.name + '</div>';
            }
            
            html += '<div class="message-content">' + content + '</div>';
            
            if (role === 'bot') {
                html += '<div class="feedback">' +
                    '<button onclick="sendFeedback(\'helpful\')">👍</button>' +
                    '<button onclick="sendFeedback(\'not_helpful\')">👎</button>' +
                    '</div>';
            }
            
            html += '</div>';
            $chatMessages.append(html);
            scrollToBottom();
        }

        function sendMessage() {
            const message = $userInput.val().trim();
            if (!message && !selectedFile) return;

            const formData = new FormData();
            formData.append('message', message);
            formData.append('member', member);
            formData.append('conversation_id', conversationId);
            
            if (selectedFile) {
                formData.append('file', selectedFile);
            }

            appendMessage('user', message, selectedFile ? JSON.stringify({
                name: selectedFile.name,
                type: selectedFile.type,
                size: selectedFile.size
            }) : null);

            $.ajax({
                url: 'api/chatbot/chatbot_api.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    appendMessage('bot', response.answer);
                    conversationId = response.conversation_id;
                    loadConversations();
                    resetInput();
                }
            });
        }

        function resetInput() {
            $userInput.val('');
            selectedFile = null;
            $('#file-name').text('');
            $sendButton.prop('disabled', true);
        }

        function scrollToBottom() {
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }

        $('#file-upload').change(function(e) {
            selectedFile = e.target.files[0];
            if (selectedFile) {
                if (selectedFile.size > 5 * 1024 * 1024) {
                    alert('파일 크기는 5MB를 초과할 수 없습니다.');
                    resetInput();
                    return;
                }
                $('#file-name').text(selectedFile.name);
                $sendButton.prop('disabled', false);
            }
        });

        $userInput.on('input', function() {
            $sendButton.prop('disabled', !this.value.trim() && !selectedFile);
        });

        $sendButton.click(sendMessage);

        $userInput.keypress(function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        $('#new-chat-button').click(function() {
            conversationId = '';
            $chatMessages.empty();
            resetInput();
        });

        $(document).on('click', '.conversation-item', function() {
            conversationId = $(this).data('id');
            loadConversation(conversationId);
        });
    });

    function sendFeedback(type) {
        $.post('chatbot_api.php', {
            feedback: type,
            conversation_id: conversationId
        });
    }
    </script>
</body>
</html>