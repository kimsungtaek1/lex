$(document).ready(function() {
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;
    let schedules = [];

    // 초기 데이터 로드 및 달력 렌더링
    loadSchedules();

    function loadSchedules() {
        $.ajax({
            url: './api/schedule/get_schedules.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    schedules = response.data;
                    renderCalendar(currentYear, currentMonth);
                }
            }
        });
    }

    function renderCalendar(year, month) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const startingDay = firstDay.getDay();
        const totalDays = lastDay.getDate();
        
        $('.year-month').text(`${year}. ${String(month).padStart(2, '0')}`);

        let calendarHtml = '';
        let dayCount = 1;
        let weekCount = Math.ceil((startingDay + totalDays) / 7);

        for(let week = 0; week < weekCount; week++) {
            calendarHtml += '<tr>';
            
            for(let day = 0; day < 7; day++) {
                const isOtherMonth = (week === 0 && day < startingDay) || 
                                   (dayCount > totalDays);
                
                const currentDate = isOtherMonth ? '' : 
                    `${year}-${String(month).padStart(2, '0')}-${String(dayCount).padStart(2, '0')}`;
                
                let dayClass = isOtherMonth ? 'other-month' : '';
                if (currentDate === formatDate(new Date())) {
                    dayClass += ' today';
                }

                calendarHtml += `<td class="${dayClass}">`;
                if (!isOtherMonth) {
                    calendarHtml += `
                        <div class="date-number ${day === 0 ? 'sun' : day === 6 ? 'sat' : ''}">
                            ${dayCount}
                        </div>
                        ${getSchedulesForDate(currentDate)}
                    `;
                    dayCount++;
                }
                calendarHtml += '</td>';
            }
            
            calendarHtml += '</tr>';
        }

        $('#calendarBody').html(calendarHtml);
        bindEvents();
    }

    function getSchedulesForDate(date) {
        let html = '';
        const daySchedules = schedules.filter(s => s.date === date);
        
        daySchedules.sort((a, b) => {
            if (!a.time) return 1;
            if (!b.time) return -1;
            return a.time.localeCompare(b.time);
        });
        
        daySchedules.forEach(schedule => {
            let typeClass = '';
            switch(schedule.category) {
                case '기일': typeClass = 'type-1'; break;
                case '문서송달': typeClass = 'type-2'; break;
                case '의뢰인방문': typeClass = 'type-3'; break;
                default: typeClass = 'type-4';
            }
            
            html += `
                <div class="schedule-item ${typeClass}" data-id="${schedule.schedule_no}">
                    <div class="schedule-content">
                        ${schedule.time ? `<span class="schedule-time">${formatTime(schedule.time)}</span>` : ''}
                        <span class="schedule-text">${schedule.name || ''} ${schedule.content || ''}</span>
                    </div>
                </div>
            `;
        });
        
        return html;
    }

    function bindEvents() {
        // 이전/다음 월 네비게이션
        $('.prev-month').off('click').on('click', function() {
            if (currentMonth === 1) {
                currentYear--;
                currentMonth = 12;
            } else {
                currentMonth--;
            }
            renderCalendar(currentYear, currentMonth);
        });

        $('.next-month').off('click').on('click', function() {
            if (currentMonth === 12) {
                currentYear++;
                currentMonth = 1;
            } else {
                currentMonth++;
            }
            renderCalendar(currentYear, currentMonth);
        });

        // 일정 클릭 이벤트
        $('.schedule-item').off('click').on('click', function() {
            const scheduleId = $(this).data('id');
            // 일정 상세보기 또는 편집 모달 열기
            window.location.href = 'schedule.php?schedule_no=' + scheduleId;
        });

        // 날짜 셀 클릭 이벤트 (새 일정 추가)
        $('td').off('click').on('click', function(e) {
            if ($(e.target).closest('.schedule-item').length === 0) {
                const date = $(this).find('.date-number').text().trim();
                if (date) {
                    const selectedDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                    window.location.href = 'schedule.php?date=' + selectedDate;
                }
            }
        });
    }

    // 유틸리티 함수들
    function formatDate(date) {
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0');
    }

    function formatTime(timeStr) {
        if (!timeStr) return '';
        return timeStr.substring(0, 5);
    }
});