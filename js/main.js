$(document).ready(function() {
    // 로그아웃 처리
    $('.menu-item[data-link="logout"]').on('click', function() {
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
    });
    function generateCalendar(year, month) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const startingDay = firstDay.getDay();
        const monthLength = lastDay.getDate();
        let calendarHTML = '';
        let dayCount = 1;

        // 이전 달의 날짜들
        for (let i = 0; i < startingDay; i++) {
            calendarHTML += '<div class="calendar-day"></div>';
        }

        // 현재 달의 날짜들
        while (dayCount <= monthLength) {
            const dayOfWeek = (startingDay + dayCount - 1) % 7;
            const weekendClass = (dayOfWeek === 0 || dayOfWeek === 6) ? 'weekend' : 'weekday';
            const today = new Date();
            const isToday = dayCount === today.getDate() && 
                           month === (today.getMonth() + 1) && 
                           year === today.getFullYear();
            const todayClass = isToday ? ' today' : '';
            
            calendarHTML += `<div class="calendar-day ${weekendClass}${todayClass}">${dayCount}</div>`;
            dayCount++;
        }

        // 남은 칸 채우기 (다음 달)
        const totalCells = Math.ceil((startingDay + monthLength) / 7) * 7;
        const remainingCells = totalCells - (startingDay + monthLength);
        for (let i = 0; i < remainingCells; i++) {
            calendarHTML += '<div class="calendar-day"></div>';
        }

        $('#calendarGrid').html(calendarHTML);
        $('.month-display').text(`${String(month).padStart(2, '0')}`);
    }

    // 현재 날짜로 달력 초기화
    const currentDate = new Date();
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);

    $('.menu-item').click(function() {
		
		const link = $(this).data('link');
		
		// 각 메뉴별 페이지 연결
		switch(link) {
			case 'stats':
				window.location.href = 'statistics.php';
				break;
			case 'user':
				window.location.href = 'employee.php';
				break;
			case 'db':
				window.location.href = 'inflow.php';
				break;
			case 'manager':
				window.location.href = 'consult_manager.php';
				break;
			case 'paper':
				window.location.href = 'consult_paper.php';
				break;
			case 'cert':
				window.location.href = 'application_recovery.php';
				break;
			case 'time':
				window.location.href = 'case_management.php';
				break;
			case 'schedule':
				window.location.href = 'schedule.php';
				break;
			case 'calendar':
				window.location.href = 'index.php';
				break;
			case 'chat':
				window.location.href = 'chatbot.php';
				break;
			case 'home':
				window.location.href = '../adm/index.php';
				break;
			case 'logout':
				window.location.href = 'logout.php';
				break;
			case 'mypage':
				window.location.href = 'mypage.php';
				break;
		}
	});
});