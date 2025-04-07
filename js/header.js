$(document).ready(function() {
    // 로그아웃 처리
    $('#logoutBtn').on('click', function() {
        if(confirm('로그아웃 하시겠습니까?')) {
            $.ajax({
                url: '../adm/api/logout.php',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        alert(response.message || '로그아웃 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    alert('서버와 통신 중 오류가 발생했습니다.');
                }
            });
        }
    });

    // 일정 버튼 클릭 이벤트
    $('#header_alert').click(function() {
        alert('일정 버튼 클릭');
    });

    // 현재 페이지 URL 가져오기
    const currentPage = window.location.pathname.split('/').pop();
    
    // 모든 메뉴 아이템의 active 클래스 제거
    $('.menu-item').removeClass('active');
    
    // 현재 페이지에 해당하는 메뉴 아이템에 active 클래스 추가
    $('.menu-item').each(function() {
        const href = $(this).attr('href');
        if (href === currentPage) {
            $(this).addClass('active');
        }
    });
});