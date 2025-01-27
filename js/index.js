$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val().trim();
        
        if (!username || !password) {
            alert('아이디와 비밀번호를 모두 입력해주세요.');
            return;
        }
        
        const $loginBtn = $(this).find('button[type="submit"]');
        const originalText = $loginBtn.text();
        $loginBtn.prop('disabled', true).text('로그인 중...');
        
        $.ajax({
            url: 'api/login_process.php',
            type: 'POST',
            data: { username, password },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    alert(response.message || '로그인에 실패했습니다.');
                }
            },
            error: function() {
                alert('서버와 통신 중 오류가 발생했습니다.');
            },
            complete: function() {
                $loginBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    $('.input-group input').on({
        focus: function() {
            $(this).css('border-color', '#00e6c3');
        },
        blur: function() {
            $(this).css('border-color', '#ddd');
        }
    });
});