$(document).ready(function() {
    let papers = [];
    let managers = [];
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredPapers = [];
    let sortField = 'datetime';
    let sortDirection = 'desc';

    // 초기 데이터 로드
    initializeData();

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
            })
        ]).then(() => {
            loadPapers();
        });
    }

    function loadPapers() {
        $.ajax({
            url: '/adm/api/consult_paper/get_consult_papers.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    papers = response.data;
                    filteredPapers = [...papers];
                    renderPapers();
                }
            }
        });
    }

    function renderPapers() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageData = filteredPapers.slice(startIndex, endIndex);
        
        const tbody = $('#paperList');
        tbody.empty();
        
        if (pageData.length === 0) {
            tbody.append('<tr><td colspan="11" class="text-center">데이터가 없습니다.</td></tr>');
            return;
        }
        
		pageData.forEach(function(item) {
			const row = `
				<tr data-id="${item.paper_no}">
					<td class="editable" data-field="name">${item.name || ''}</td>
					<td class="editable" data-field="phone">${item.phone || ''}</td>
					<td class="editable" data-field="case_number">${item.case_number || ''}</td>
					<td>
						<select class="category-select" data-field="category">
							<option value="">선택</option>
							<option value="개인회생급여" ${item.category === '개인회생급여' ? 'selected' : ''}>개인회생급여</option>
							<option value="개인회생영업" ${item.category === '개인회생영업' ? 'selected' : ''}>개인회생영업</option>
							<option value="개인파산" ${item.category === '개인파산' ? 'selected' : ''}>개인파산</option>
						</select>
					</td>
					<td class="editable date-cell" data-field="assign_date">${formatDate(item.assign_date) || ''}</td>
					<td class="editable date-cell" data-field="accept_date">${formatDate(item.accept_date) || ''}</td>
					<td class="editable date-cell" data-field="start_date">${formatDate(item.start_date) || ''}</td>
					<td class="editable date-cell" data-field="approval_date">${formatDate(item.approval_date) || ''}</td>
					<td>
						<select class="status-select" data-field="status">
							<option value="">선택</option>
							<option value="접수" ${item.status === '접수' ? 'selected' : ''}>접수</option>
							<option value="개시" ${item.status === '개시' ? 'selected' : ''}>개시</option>
							<option value="인가" ${item.status === '인가' ? 'selected' : ''}>인가</option>
							<option value="종결" ${item.status === '종결' ? 'selected' : ''}>종결</option>
							<option value="기각" ${item.status === '기각' ? 'selected' : ''}>기각</option>
							<option value="취하" ${item.status === '취하' ? 'selected' : ''}>취하</option>
							<option value="폐지" ${item.status === '폐지' ? 'selected' : ''}>폐지</option>
							<option value="기타" ${item.status === '기타' ? 'selected' : ''}>기타</option>
						</select>
					</td>
					<td>
						<select class="manager-select" data-field="manager_id">
							<option value="">선택</option>
							${managers.map(m => `
								<option value="${m.employee_no}" ${item.manager_id == m.employee_no ? 'selected' : ''}>
									${m.name}
								</option>
							`).join('')}
						</select>
					</td>
					<td>
						<div><button type="button" class="btn-save" data-id="${item.paper_no}">저장</button></div>
						<div><button type="button" class="btn-delete" data-id="${item.paper_no}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});

        renderPagination(filteredPapers.length);
        bindEvents();
    }

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
		
		// 날짜 필드 변경 이벤트
		$(document).on('blur', '.date-cell input[type="date"]', function() {
			const $row = $(this).closest('tr');
			const paper_no = $row.data('id');
			const fieldName = $(this).closest('.editable').data('field');
			const newValue = $(this).val();
			
			// case_management 테이블과 동기화
			$.ajax({
				url: '/adm/api/consult_paper/update_case_management_dates.php',
				method: 'POST',
				data: {
					paper_no: paper_no,
					field_name: fieldName,
					value: newValue
				},
				success: function(response) {
					if (response.success) {
						// 날짜가 변경되면 상태도 자동으로 업데이트
						let newStatus;
						if (fieldName === 'accept_date') {
							newStatus = '접수';
						} else if (fieldName === 'start_date') {
							newStatus = '개시';
						} else if (fieldName === 'approval_date') {
							newStatus = '인가';
						}
						
						if (newStatus) {
							$row.find('.status-select').val(newStatus);
							
							// case_management 상태도 함께 업데이트
							$.ajax({
								url: '/adm/api/case_management/update_status.php',
								method: 'POST',
								data: {
									paper_no: paper_no,
									status: newStatus
								}
							});
						}
					} else {
						alert(response.message || '날짜 동기화 실패');
					}
				}
			});
		});

        // 상담 내용 로드 함수
        function loadPaperContents(paper_no) {
            $.ajax({
                url: '/adm/api/consult_paper/get_consult_paper_contents.php',
                method: 'GET',
                data: { paper_no: paper_no },
                success: function(response) {
                    if (response.success) {
                        renderPaperContents(response.data);
                    }
                }
            });
        }

        // document 클릭 이벤트 - active 클래스 제거
        $(document).off('click.removeActive').on('click.removeActive', function(e) {
            if (!$(e.target).closest('.data-table tr, .detail-menu').length) {
                $('.data-table tbody tr').removeClass('active');
                $('.detail-menu').remove();
            }
        });

        // 행 클릭 이벤트
        $('.data-table tbody tr').off('click').on('click', function(e) {
            if ($(e.target).is('button, input, select, textarea')) {
                return;
            }
            
            $('.data-table tbody tr').not(this).removeClass('active');
            $(this).addClass('active');
            
            const $row = $(this);
            const paper_no = $row.data('id');
            const $table = $('.data-table table');
            const tableWidth = $table.width();
            
            $('.detail-menu').remove();
            
            const menuContent = `
                <div class="detail-menu" style="width: ${tableWidth}px">
                    <div class="detail-menu-container">
                        <div class="detail-menu-title">
                            <div class="detail-menu-text">메모</div>
                        </div>
                        <div class="detail-menu-left">
                            <div class="detail-menu-content">
                                <!-- 메모 내용이 여기에 동적으로 렌더링됩니다 -->
                            </div>
                        </div>
                        <div class="detail-menu-right">
                            <button type="button" class="btn-detail-add">추가</button>
                        </div>
                    </div>
                </div>
            `;
            
            $row.after(menuContent);
            
            setTimeout(() => {
                $row.next('.detail-menu').addClass('active');
            }, 10);

            loadPaperContents(paper_no);
        });

        // 상담 내용 추가 버튼 클릭 이벤트
        $(document).on('click', '.btn-detail-add', function(e) {
            e.stopPropagation();
            
            const $row = $(this).closest('.detail-menu').prev('tr');
            const paper_no = $row.data('id');
            const manager_id = $row.find('.manager-select').val();

            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: '/adm/api/consult_paper/add_consult_paper_content.php',
                method: 'POST',
                data: {
                    paper_no: paper_no,
                    manager_id: manager_id || null,
                    content: ' '
                },
                success: function(response) {
                    if (response.success) {
                        loadPaperContents(paper_no);
                        setTimeout(() => {
                            const $lastContent = $('.detail-menu-content .detail-menu-row:last-child .content');
                            $lastContent.focus();
                            const range = document.createRange();
                            const sel = window.getSelection();
                            range.selectNodeContents($lastContent[0]);
                            range.collapse(false);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }, 100);
                    } else {
                        alert(response.message || '저장 실패');
                    }
                },
                error: function() {
                    alert('저장 중 오류가 발생했습니다.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

		// 셀 편집 이벤트
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input').length > 0) return;
			
			$(this).closest('tr').addClass('editing');
			
			const currentValue = $(this).text();
			const fieldType = $(this).data('field');
			
			let inputHtml;
			if ($(this).hasClass('date-cell')) {
				inputHtml = `<input type="date" value="${currentValue}" data-original="${currentValue}">`;
			} else {
				inputHtml = `<input type="text" value="${currentValue}" data-original="${currentValue}">`;
			}
			
			$(this).html(inputHtml);
			const $input = $(this).find('input');
			
			$input.focus().select().on('blur', function() {
				const $row = $(this).closest('tr');
				const newValue = $(this).val();
				const originalValue = $(this).data('original');
				const $cell = $(this).parent();
				
				let displayValue = newValue || originalValue;
				
				if ($cell.hasClass('date-cell') && newValue) {
					displayValue = formatDate(newValue);
				}
				
				$(this).parent().text(displayValue);
				$row.removeClass('editing');
			});
		});

        // content 편집 이벤트
        $(document).on('focus', '.detail-menu-row .content', function() {
            $(this).addClass('editing');
        });

        $(document).on('blur', '.detail-menu-row .content', function() {
            const $this = $(this);
            const $row = $this.closest('.detail-menu-row');
            const content_no = $row.data('content-no');
            const content = $this.text().trim();
            const $indicator = $row.find('.saving-indicator');
            
            $this.removeClass('editing');
            $indicator.addClass('show');

            $.ajax({
                url: '/adm/api/consult_paper/update_consult_paper_content.php',
                method: 'POST',
                data: {
                    content_no: content_no,
                    content: content
                },
                success: function(response) {
                    if (!response.success) {
                        alert(response.message || '저장 실패');
                    }
                },
                complete: function() {
                    $indicator.removeClass('show');
                }
            });
        });

        // 상담 내용 삭제 버튼 이벤트
        $(document).on('click', '.btn-detail-delete', function(e) {
            e.stopPropagation();
            const $row = $(this).closest('.detail-menu-row');
            const content_no = $row.data('content-no');
            
            if(confirm('정말 삭제하시겠습니까?')) {
                $.ajax({
                    url: '/adm/api/consult_paper/delete_consult_paper_content.php',
                    method: 'POST',
                    data: { content_no: content_no },
                    success: function(response) {
                        if (response.success) {
                            $row.slideUp(200, function() {
                                $(this).remove();
                                if ($('.detail-menu-row').length === 0) {
                                    $('.detail-menu-content').html('<div class="detail-menu-row">등록된 상담 내용이 없습니다.</div>');
                                }
                            });
                        } else {
                            alert(response.message || '삭제 실패');
                        }
                    }
                });
            }
        });

        // 저장 버튼 이벤트
		$('.btn-save').off('click').on('click', function() {
			const row = $(this).closest('tr');
			const id = row.data('id');
			
			const data = {
				paper_no: id,
				name: row.find('[data-field="name"]').text().trim(),
				phone: row.find('[data-field="phone"]').text().trim(),
				case_number: row.find('[data-field="case_number"]').text().trim(),
				category: row.find('.category-select').val(),
				assign_date: row.find('[data-field="assign_date"]').text().trim(),
				start_date: row.find('[data-field="start_date"]').text().trim(),
				accept_date: row.find('[data-field="accept_date"]').text().trim(),
				approval_date: row.find('[data-field="approval_date"]').text().trim(),
				status: row.find('.status-select').val(),
				manager_id: row.find('.manager-select').val() || null
			};

            if (!validatePaperData(data)) return;

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/adm/api/consult_paper/update_consult_paper.php',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert('저장되었습니다.');
                        loadPapers();
                    } else {
                        alert(response.message || '저장 실패');
                    }
                },
                error: function() {
                    alert('저장 중 오류가 발생했습니다.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // 삭제 버튼 이벤트
        $('.btn-delete').off('click').on('click', function() {
            if (!confirm('정말 삭제하시겠습니까?')) return;
            
            const id = $(this).data('id');
            
            $.ajax({
                url: '/adm/api/consult_paper/delete_consult_paper.php',
                method: 'POST',
                data: { paper_no: id },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        loadPapers();
                    } else {
                        alert(response.message || '삭제 실패');
                    }
                }
            });
        });
    }

    // 상담 내용 렌더링 함수
    function renderPaperContents(contents) {
        const $content = $('.detail-menu-content');
        $content.empty();

        if (contents.length === 0) {
            $content.append('<div class="detail-menu-row">등록된 상담 내용이 없습니다.</div>');
            return;
        }

        contents.forEach(function(item) {
            const date = new Date(item.created_at);
            const formattedDate = `${date.getFullYear()}. ${String(date.getMonth() + 1).padStart(2, '0')}. ${String(date.getDate()).padStart(2, '0')}.`;
            const formattedTime = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

            $content.append(`
                <div class="detail-menu-row" data-content-no="${item.content_no}">
                    <span class="date">${formattedDate}</span>
                    <span class="time">${formattedTime}</span>
                    <div class="content" contenteditable="true">${item.content}</div>
                    <button type="button" class="btn-detail-delete">삭제</button>
                    <div class="saving-indicator">저장중...</div>
                </div>
            `);
        });
    }

    // 검색 기능
	$('#searchInput').on('keyup', function() {
		const searchTerm = $(this).val().toLowerCase();
		
		filteredPapers = papers.filter(item => 
			(item.name || '').toLowerCase().includes(searchTerm) ||
			(item.phone || '').toLowerCase().includes(searchTerm) ||
			(item.case_number || '').toLowerCase().includes(searchTerm) ||
			(item.category || '').toLowerCase().includes(searchTerm) ||
			(item.status || '').toLowerCase().includes(searchTerm)
		);
		
		currentPage = 1;
		renderPapers();
	});

    // 유틸리티 함수들
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

    // 데이터 유효성 검사
    function validatePaperData(data) {
        if (!data.name.trim()) {
            alert('성명을 입력해주세요.');
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
                renderPapers();
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
            renderPapers();
        }
    });

    $('.next-btn').click(function() {
        const totalPages = Math.ceil(filteredPapers.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderPapers();
        }
    });

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

        $('.sort-indicator').remove();
        $(this).append(`<span class="sort-indicator">${sortDirection === 'asc' ? '▲' : '▼'}</span>`);

        filteredPapers.sort((a, b) => {
            let valueA = (a[field] || '').toString();
            let valueB = (b[field] || '').toString();

            if (field === 'datetime' || field === 'birth_date') {
                valueA = new Date(valueA);
                valueB = new Date(valueB);
            }
            else if (field === 'debt_amount') {
                valueA = parseFloat(valueA.replace(/[^\d.-]/g, '')) || 0;
                valueB = parseFloat(valueB.replace(/[^\d.-]/g, '')) || 0;
            }

            if (sortDirection === 'asc') {
                return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
            } else {
                return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
            }
        });

        renderPapers();
    });

    // 로딩 표시/숨김 함수
    function showLoading() {
        $('#loadingIndicator').show();
        $('.data-table').css('opacity', '0.6');
    }

    function hideLoading() {
        $('#loadingIndicator').hide();
        $('.data-table').css('opacity', '1');
    }

    // Ajax 에러 핸들러
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('Ajax error:', {
            url: settings.url,
            status: jqXHR.status,
            error: error,
            response: jqXHR.responseText
        });
        hideLoading();
    });

    // Ajax 시작/종료 시 로딩 표시
    $(document).ajaxStart(function() {
        showLoading();
    }).ajaxStop(function() {
        hideLoading();
    });

    // 엔터 키 이벤트 처리
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).trigger('keyup');
        }
    });

    // 셀렉트 박스 변경 이벤트
    $('.category-select, .manager-select, .status-select').on('change', function() {
        $(this).closest('tr').find('.btn-save').addClass('highlight');
    });

    // 윈도우 리사이즈 이벤트
    $(window).resize(function() {
        if (this.resizeTO) clearTimeout(this.resizeTO);
        this.resizeTO = setTimeout(function() {
            const tableWidth = $('.data-table table').width();
            $('.detail-menu').width(tableWidth);
            $(this).trigger('resizeEnd');
        }, 10);
    });

    // 에러 처리 함수
    function handleAjaxError(error, context) {
        console.error(`Error in ${context}:`, error);
        alert(`작업 중 오류가 발생했습니다. (${context})`);
    }
});