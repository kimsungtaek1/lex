$(document).ready(function() {
    let schedules = [];
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredSchedules = [];
    let sortField = 'date';
    let sortDirection = 'desc';

    // 초기 데이터 로드
    loadSchedules();

    // 검색 기능
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filteredSchedules = schedules.filter(item => 
            (item.category || '').toLowerCase().includes(searchTerm) ||
            (item.name || '').toLowerCase().includes(searchTerm) ||
            (item.content || '').toLowerCase().includes(searchTerm) ||
            (item.location || '').toLowerCase().includes(searchTerm) ||
            (item.memo || '').toLowerCase().includes(searchTerm)
        );
        currentPage = 1;
        renderSchedules();
    });

    // 스케줄 로드
    function loadSchedules() {
        $.ajax({
            url: './api/schedule/get_schedules.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    schedules = response.data;
                    filteredSchedules = [...schedules];
                    renderSchedules();
                }
            }
        });
    }

    // 스케줄 렌더링
	function renderSchedules() {
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = filteredSchedules.slice(startIndex, endIndex);
		
		const tbody = $('#scheduleList');
		tbody.empty();
		
		if (pageData.length === 0) {
			tbody.append('<tr><td colspan="8" class="text-center">데이터가 없습니다.</td></tr>');
			return;
		}
		
		pageData.forEach(function(item) {
			const row = `
				<tr data-id="${item.schedule_no}">
					<td>
						<select class="category-select" data-field="category">
							<option value="">선택</option>
							<option value="기일" ${item.category === '기일' ? 'selected' : ''}>기일</option>
							<option value="문서송달" ${item.category === '문서송달' ? 'selected' : ''}>문서송달</option>
							<option value="의뢰인방문" ${item.category === '의뢰인방문' ? 'selected' : ''}>의뢰인방문</option>
							<option value="기타" ${item.category === '기타' ? 'selected' : ''}>기타</option>
						</select>
					</td>
					<td class="editable" data-field="name">${item.name || ''}</td>
					<td class="editable" data-field="date">${formatDate(item.date)}</td>
					<td class="editable" data-field="time">${formatTime(item.time)}</td>
					<td class="editable" data-field="content">${item.content || ''}</td>
					<td class="editable" data-field="location">${item.location || ''}</td>
					<td class="editable" data-field="memo">${item.memo || ''}</td>
					<td>
						<div><button type="button" class="btn-save" data-id="${item.schedule_no}">저장</button></div>
						<div><button type="button" class="btn-delete" data-id="${item.schedule_no}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});

		renderPagination();
		bindEvents();
	}

    // 페이지네이션 렌더링
    function renderPagination() {
        const totalPages = Math.ceil(filteredSchedules.length / itemsPerPage);
        const $pageNumbers = $('.page-numbers');
        $pageNumbers.empty();
        
        for (let i = 1; i <= totalPages; i++) {
            const $pageBtn = $('<button>')
                .addClass('page-btn')
                .toggleClass('active', i === currentPage)
                .text(i)
                .click(() => {
                    currentPage = i;
                    renderSchedules();
                });
            $pageNumbers.append($pageBtn);
        }
    }

    // 이벤트 바인딩
    function bindEvents() {
        // 셀 클릭시 편집 모드
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input, textarea').length > 0) return;
			
			const currentValue = $(this).text();
			const fieldType = $(this).data('field');
			
			let inputHtml;
			switch(fieldType) {
				case 'content':
				case 'memo':
					inputHtml = `<textarea rows="3" data-original="${currentValue}">${currentValue}</textarea>`;
					break;
				case 'date':
					inputHtml = `<input type="date" value="${currentValue}" data-original="${currentValue}">`;
					break;
				case 'time':
					inputHtml = `<input type="time" value="${currentValue}" data-original="${currentValue}">`;
					break;
				default:
					inputHtml = `<input type="text" value="${currentValue}" data-original="${currentValue}">`;
			}
			
			$(this).html(inputHtml);
			const $input = $(this).find('input, textarea');
			
			$input.focus().select().on('blur', function() {
				const newValue = $(this).val();
				const originalValue = $(this).data('original');
				const fieldType = $(this).closest('.editable').data('field');
				
				let displayValue = newValue || originalValue;
				
				if (fieldType === 'date' && newValue) {
					displayValue = formatDate(newValue);
				} else if (fieldType === 'time' && newValue) {
					displayValue = newValue; // HH:MM 형식 그대로 표시
				}
				
				$(this).parent().text(displayValue);
			});
		});

		// 저장 버튼
		$('.btn-save').off('click').on('click', function() {
			const row = $(this).closest('tr');
			const id = row.data('id');
			
			const data = {
				schedule_no: id,
				category: row.find('.category-select').val(),
				name: row.find('[data-field="name"]').text().trim(),
				date: row.find('[data-field="date"]').text().trim(),
				time: row.find('[data-field="time"]').text().trim(),
				content: row.find('[data-field="content"]').text().trim(),
				location: row.find('[data-field="location"]').text().trim(),
				memo: row.find('[data-field="memo"]').text().trim()
			};

			if (!validateScheduleData(data)) return;

			const url = id === 'new' ? './api/schedule/add_schedule.php' : './api/schedule/update_schedule.php';

			// 저장 버튼 비활성화
			const $btn = $(this);
			$btn.prop('disabled', true);

			$.ajax({
				url: url,
				method: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert('저장되었습니다.');
						loadSchedules();
					} else {
						alert(response.message || '저장 실패');
					}
				},
				error: function(xhr, status, error) {
					console.error('저장 중 오류:', error);
					alert('저장 중 오류가 발생했습니다.');
				},
				complete: function() {
					$btn.prop('disabled', false);
				}
			});
		});

        // 삭제 버튼
        $('.btn-delete').click(function() {
            if (!confirm('정말 삭제하시겠습니까?')) return;

            const id = $(this).data('id');
            
            $.ajax({
                url: './api/schedule/delete_schedule.php',
                method: 'POST',
                data: { schedule_no: id },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        loadSchedules();
                    } else {
                        alert(response.message || '삭제 실패');
                    }
                }
            });
        });
    }
	
	// 데이터 유효성 검사 함수 추가
	function validateScheduleData(data) {
		if (!data.category.trim()) {
			alert('구분을 선택해주세요.');
			return false;
		}
		if (!data.date.trim()) {
			alert('일자를 입력해주세요.');
			return false;
		}
		if (!validateDate(data.date)) {
			alert('올바른 날짜 형식이 아닙니다. (예: 2024-01-01)');
			return false;
		}
		if (data.time && !validateTime(data.time)) {
			alert('올바른 시간 형식이 아닙니다. (예: 09:00)');
			return false;
		}
		return true;
	}
	
	function formatTime(timeStr) {
		if (!timeStr) return '';
		// DB에서 받은 HH:MM:SS 형식을 HH:MM 형식으로 변환
		return timeStr.substring(0, 5);
	}

	// 날짜 유효성 검사
	function validateDate(dateStr) {
		const regex = /^\d{4}-\d{2}-\d{2}$/;
		if (!regex.test(dateStr)) return false;
		const date = new Date(dateStr);
		return !isNaN(date.getTime());
	}

	// 시간 유효성 검사
	function validateTime(timeStr) {
		if (!timeStr) return true; // 시간은 필수가 아닌 경우
		return /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(timeStr);
	}

    // 날짜 포맷 함수
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    // 추가 버튼
    $('.btn-add').click(function() {
        const newSchedule = {
            schedule_no: 'new',
            category: '',
            name: '',
            date: new Date().toISOString().split('T')[0],
            time: '',
            content: '',
            location: '',
            memo: ''
        };
        
        schedules.unshift(newSchedule);
        filteredSchedules.unshift(newSchedule);
        currentPage = 1;
        renderSchedules();
    });
});