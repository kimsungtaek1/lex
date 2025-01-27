$(document).ready(function() {
    let inflows = [];
    let managers = [];
    let departments = [];
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredInflows = [];
    let sortField = 'datetime';
    let sortDirection = 'desc';

    // 초기 데이터 로드
    initializeData();

    // 초기화 함수
    function initializeData() {
        Promise.all([
            $.ajax({
                url: '/adm/api/manager/get_managers.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        managers = response.data;
                    }
                }
            }),
            $.ajax({
                url: '/adm/api/department/get_departments.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        departments = response.data;
                    }
                }
            })
        ]).then(() => {
            $.ajax({
                url: '/adm/api/inflow/get_inflows.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        inflows = response.data;
                        filteredInflows = [...inflows];
                        renderInflows();
                    }
                }
            });
        });
    }

    // 검색 기능
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filteredInflows = inflows.filter(item => 
            (item.name || '').toLowerCase().includes(searchTerm) ||
            (item.phone || '').toLowerCase().includes(searchTerm) ||
            (item.category || '').toLowerCase().includes(searchTerm) ||
            (item.content || '').toLowerCase().includes(searchTerm) ||
            (item.inflow || '').toLowerCase().includes(searchTerm) ||
            (item.inflow_page || '').toLowerCase().includes(searchTerm) ||
            (item.region || '').toLowerCase().includes(searchTerm) ||
            (String(item.debt_amount) || '').toLowerCase().includes(searchTerm) ||
            (item.manager_name || '').toLowerCase().includes(searchTerm)
        );
        currentPage = 1;
        renderInflows();
    });

    // 인플로우 렌더링
    function renderInflows() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageData = filteredInflows.slice(startIndex, endIndex);
        
        const tbody = $('#inflowList');
        tbody.empty();
        
        if (pageData.length === 0) {
            tbody.append('<tr><td colspan="9" class="text-center">데이터가 없습니다.</td></tr>');
            return;
        }
        
        pageData.forEach(function(item) {
            const row = `
                <tr data-id="${item.no}">
                    <td class="editable" data-field="name">${item.name || ''}</td>
                    <td class="editable" data-field="phone">${item.phone || ''}</td>
					<td>
						<select class="category-select" data-field="category">
							<option value="">선택</option>
							<option value="개인회생급여" ${item.category === '개인회생급여' ? 'selected' : ''}>개인회생급여</option>
							<option value="개인회생영업" ${item.category === '개인회생영업' ? 'selected' : ''}>개인회생영업</option>
							<option value="개인파산" ${item.category === '개인파산' ? 'selected' : ''}>개인파산</option>
						</select>
                    </td>
                    <td class="editable" data-field="datetime">${formatDateTime(item.datetime) || ''}</td>
                    <td class="detail-cell">
                        <table class="detail-table">
                            <tr>
                                <th>지<span>지역</span>역 |</th>
                                <td class="editable" data-field="region">${item.region || ''}</td>
                            </tr>
                            <tr>
                                <th>생년월일 |</th>
                                <td class="editable" data-field="birth_date">${formatDate(item.birth_date) || ''}</td>
                            </tr>
                            <tr>
                                <th>채무금액 |</th>
                                <td class="editable" data-field="debt_amount">${item.debt_amount || ''}</td>
                            </tr>
                            <tr>
                                <th>상담시간 |</th>
                                <td class="editable" data-field="consultation_time">${item.consultation_time || ''}</td>
                            </tr>
                            <tr>
                                <th>문의사항 |</th>
                                <td class="editable" data-field="content">${item.content || ''}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="editable" data-field="inflow_page">${item.inflow_page || ''}</td>
                    <td class="editable" data-field="inflow">${item.inflow || ''}</td>
                    <td>
                        <select class="manager-select" data-field="manager">
                            <option value="">선택</option>
                            ${managers.map(m => `
                                <option value="${m.employee_no}" ${item.manager == m.employee_no ? 'selected' : ''}>
                                    ${m.name}
                                </option>
                            `).join('')}
                        </select>
                    </td>
                    <td>
                        <div><button type="button" class="btn-save" data-id="${item.no}">저장</button></div>
                        <div><button type="button" class="btn-delete" data-id="${item.no}">삭제</button></div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        renderPagination(filteredInflows.length);
        bindEvents();
    }

    // 이벤트 바인딩
    function bindEvents() {
		// 전화번호 자동 포맷팅
		$('.editable[data-field="phone"]').on('input', function() {
			let value = $(this).find('input').val().replace(/[^0-9]/g, '');
			if (value.length > 3 && value.length <= 7) {
				value = value.substr(0, 3) + '-' + value.substr(3);
			} else if (value.length > 7) {
				value = value.substr(0, 3) + '-' + value.substr(3, 4) + '-' + value.substr(7);
			}
			$(this).find('input').val(value.substr(0, 13));
		});
		
		// 행 클릭 이벤트
		$(document).off('click.removeActive').on('click.removeActive', function(e) {
			if (!$(e.target).closest('.data-table tr, button, input, select, textarea').length) {
				$('.data-table tbody tr').removeClass('active');
			}
		});

		$('.data-table tbody tr').off('click').on('click', function(e) {
			if ($(e.target).is('button, input, select, textarea')) {
				return;
			}
			
			$('.data-table tbody tr').not(this).removeClass('active');
			$(this).addClass('active');
		});
        // 셀 클릭시 편집 모드
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input, textarea').length > 0) return;
			
			const currentValue = $(this).text();
			const fieldType = $(this).data('field');
			
			let inputHtml;
			switch(fieldType) {
				case 'content':
					inputHtml = `<textarea rows="3" data-original="${currentValue}">${currentValue}</textarea>`;
					break;
				case 'birth_date':
					inputHtml = `<input type="date" value="${currentValue}" data-original="${currentValue}">`;
					break;
				case 'datetime':
					inputHtml = `<input type="datetime-local" value="${formatDateTimeLocal(currentValue)}" data-original="${currentValue}">`;
					break;
				default:
					inputHtml = `<input type="text" value="${currentValue}" data-original="${currentValue}">`;
			}
			
			$(this).html(inputHtml);
			const $input = $(this).find('input, textarea');
			
			// blur 이벤트를 직접 입력 요소에 바인딩
			$input.focus().select().on('blur', function() {
				const $row = $(this).closest('tr');
				const newValue = $(this).val();
				const originalValue = $(this).data('original');
				const fieldType = $(this).closest('.editable').data('field');
				
				let displayValue = newValue || originalValue;
				
				if (fieldType === 'datetime' && newValue) {
					displayValue = formatDateTime(newValue);
				} else if (fieldType === 'birth_date' && newValue) {
					displayValue = formatDate(newValue);
				}
				
				$(this).parent().text(displayValue);
				
				// editing 클래스 제거
				$row.removeClass('editing');
			});
		});

		$('.editable').off('blur', 'input, textarea');

		// 저장 버튼
		$('.btn-save').off('click').on('click', function() {
			const row = $(this).closest('tr');
			const id = row.data('id');
			
			const data = {
				no: id,
				name: row.find('[data-field="name"]').text().trim(),
				phone: row.find('[data-field="phone"]').text().trim(),
				category: row.find('.category-select').val(),
				datetime: row.find('[data-field="datetime"]').text().trim(),
				region: row.find('[data-field="region"]').text().trim(),
				birth_date: row.find('[data-field="birth_date"]').text().trim(),
				debt_amount: row.find('[data-field="debt_amount"]').text().trim(),
				consultation_time: row.find('[data-field="consultation_time"]').text().trim(),
				content: row.find('[data-field="content"]').text().trim(),
				inflow_page: row.find('[data-field="inflow_page"]').text().trim(),
				inflow: row.find('[data-field="inflow"]').text().trim(),
				manager: row.find('.manager-select').val() || null
			};

			if (!validateInflowData(data)) return;

			const url = id === 'new' ? './api/inflow/add_inflow.php' : './api/inflow/update_inflow.php';

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
						initializeData(); // 목록 새로고침
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
        $('.btn-delete').off('click').on('click', function() {
            if (!confirm('정말 삭제하시겠습니까?')) return;

            const id = $(this).data('id');
            
            $.ajax({
                url: './api/inflow/delete_inflow.php',
                method: 'POST',
                data: { no: id },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        initializeData();
                    } else {
                        alert(response.message || '삭제 실패');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('삭제 중 오류:', error);
                    alert('삭제 중 오류가 발생했습니다.');
                }
            });
        });
    }

    // 정렬 기능
    $('.data-table th[data-field]').click(function() {
        const field = $(this).data('field');
        if (!field) return;

        if (sortField === field) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDirection = 'asc';
        }

        // 정렬 표시자 업데이트
        $('.sort-indicator').remove();
        $(this).append(`<span class="sort-indicator">${sortDirection === 'asc' ? '▲' : '▼'}</span>`);

        // 데이터 정렬
        filteredInflows.sort((a, b) => {
            let valueA = (a[field] || '').toString();
            let valueB = (b[field] || '').toString();

            // 날짜 필드 특별 처리
            if (field === 'datetime' || field === 'birth_date') {
                valueA = new Date(valueA);
                valueB = new Date(valueB);
            }
            // 숫자 필드 처리
            else if (field === 'debt_amount') {
                valueA = parseFloat(valueA) || 0;
                valueB = parseFloat(valueB) || 0;
            }

            if (sortDirection === 'asc') {
                return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
            } else {
                return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
            }
        });

        renderInflows();
    });

    // 페이지네이션 렌더링
    function renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const $pageNumbers = $('.page-numbers');
        $pageNumbers.empty();
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const $pageBtn = $(`<button type="button" class="page-btn ${i === currentPage ? 'active' : ''}">${i}</button>`);
            $pageBtn.click(() => {
                currentPage = i;
                renderInflows();
            });
            $pageNumbers.append($pageBtn);
        }

        $('.prev-btn').prop('disabled', currentPage === 1);
        $('.next-btn').prop('disabled', currentPage === totalPages);
    }

    // 페이지네이션 버튼 이벤트
    $('.prev-btn').click(function() {
        if (currentPage > 1) {
            currentPage--;
            renderInflows();
        }
    });

    $('.next-btn').click(function() {
        const totalPages = Math.ceil(filteredInflows.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderInflows();
        }
    });

    // 데이터 유효성 검사
    function validateInflowData(data) {
        if (!data.name.trim()) {
            alert('이름을 입력해주세요.');
            return false;
        }
        if (!data.phone.trim()) {
            alert('연락처를 입력해주세요.');
            return false;
        }
        if (!data.category) {
            alert('상담분야를 선택해주세요.');
            return false;
        }
        if (!validatePhoneFormat(data.phone)) {
            alert('올바른 전화번호 형식이 아닙니다. (예: 010-1234-5678)');
            return false;
        }
        if (data.birth_date && !validateDate(data.birth_date)) {
            alert('올바른 생년월일 형식이 아닙니다. (예: 2000-01-01)');
            return false;
        }
        return true;
    }

    // 전화번호 형식 검사
    function validatePhoneFormat(phone) {
        return /^01[016789]-\d{3,4}-\d{4}$/.test(phone);
    }

    // 날짜 유효성 검사
    function validateDate(dateStr) {
        if (!dateStr) return true;
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateStr)) return false;
        
        const date = new Date(dateStr);
        return !isNaN(date.getTime());
    }

    // 날짜 포맷 함수
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '';
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return dateTimeStr;
        
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0') + ' ' + 
               String(date.getHours()).padStart(2, '0') + ':' + 
               String(date.getMinutes()).padStart(2, '0');
    }

    function formatDateTimeLocal(dateTimeStr) {
        if (!dateTimeStr) return '';
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return dateTimeStr;
        
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0') + 'T' + 
               String(date.getHours()).padStart(2, '0') + ':' + 
               String(date.getMinutes()).padStart(2, '0');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0');
    }

    // 추가 버튼 이벤트
    $('.btn-add').click(function() {
        const now = new Date();
        const newRow = {
            no: 'new',
            name: '',
            phone: '',
            category: '',
            datetime: formatDateTime(now),
            content: '',
            region: '',
            birth_date: '',
            debt_amount: '',
            consultation_time: '',
            inflow_page: '',
            inflow: '',
            manager: ''
        };
        
        inflows.unshift(newRow);
        filteredInflows.unshift(newRow);
        currentPage = 1;
        renderInflows();
    });

    // Ajax 에러 핸들러
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('Ajax error:', {
            url: settings.url,
            status: jqXHR.status,
            error: error,
            response: jqXHR.responseText
        });
    });

    // 로딩 표시 함수
    function showLoading() {
        $('.data-table').css('opacity', '0.6');
        if ($('#loadingIndicator').length === 0) {
            $('<div id="loadingIndicator">로딩중...</div>')
                .css({
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    padding: '10px',
                    background: 'rgba(255,255,255,0.8)',
                    borderRadius: '4px',
                    zIndex: 1000
                })
                .appendTo('.data-table');
        }
    }

    function hideLoading() {
        $('.data-table').css('opacity', '1');
        $('#loadingIndicator').remove();
    }
});