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

    // 일정 데이터 저장 변수
    let schedules = [];

    // 일정 데이터 불러오기 (AJAX)
    function loadSchedulesAndRenderCalendar(year, month) {
        $.ajax({
            url: './api/schedule/get_schedules.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    schedules = response.data;
                } else {
                    schedules = [];
                }
                generateCalendar(year, month);
            },
            error: function() {
                schedules = [];
                generateCalendar(year, month);
            }
        });
    }

    // 날짜별 일정 상세 표시 함수 (시간, 이름, 내용, location은 다음 줄)
    function getScheduleDetailsForDate(date) {
        let html = '';
        const daySchedules = schedules.filter(s => s.date === date);
        daySchedules.forEach(schedule => {
            let typeClass = '';
            switch(schedule.category) {
                case '기일': typeClass = 'type-1'; break;
                case '문서송달': typeClass = 'type-2'; break;
                case '의뢰인방문': typeClass = 'type-3'; break;
                default: typeClass = 'type-4';
            }
            html += `<div class=\"schedule-detail ${typeClass}\">` +
                        `<div>` +
                            (schedule.time ? `<span class=\"schedule-time\">${schedule.time.substring(0,5)}</span> ` : '') +
                            `<span class=\"schedule-name\">${schedule.name || ''}</span> ` +
                            `<span class=\"schedule-content\">${schedule.content || ''}</span>` +
                        `</div>` +
                        (schedule.location ? `<div class=\"schedule-location\">${schedule.location}</div>` : '') +
                    `</div>`;
        });
        return html ? `<div class=\"schedule-details\">${html}</div>` : '';
    }

    // 달력 생성 함수 (기존 첫 디자인: flex/grid 레이아웃, 날짜 셀에만 일정 상세 디자인 적용)
    function generateCalendar(year, month) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const startingDay = firstDay.getDay();
        const monthLength = lastDay.getDate();
        let calendarHTML = '';
        let dayCount = 1;

        // 이전 달의 날짜들
        for (let i = 0; i < startingDay; i++) {
            calendarHTML += '<div class=\"calendar-day\"></div>';
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

            // 일정 상세 표시
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(dayCount).padStart(2, '0')}`;
            const scheduleDetails = getScheduleDetailsForDate(dateStr);

            calendarHTML += `<div class=\"calendar-day ${weekendClass}${todayClass}\">${dayCount}${scheduleDetails}</div>`;
            dayCount++;
        }

        // 남은 칸 채우기 (다음 달)
        const totalCells = Math.ceil((startingDay + monthLength) / 7) * 7;
        const remainingCells = totalCells - (startingDay + monthLength);
        for (let i = 0; i < remainingCells; i++) {
            calendarHTML += '<div class=\"calendar-day\"></div>';
        }

        $('#calendarGrid').html(calendarHTML);
        $('.month-display').text(`${String(month).padStart(2, '0')}`);
    }

    // 현재 날짜로 달력 초기화 (일정 포함)
    const currentDate = new Date();
    loadSchedulesAndRenderCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);

    // 메뉴 클릭 이벤트
    $('.menu-item').click(function() {
        const link = $(this).data('link');
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
                window.location.href = 'calendar.php';
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