$(document).ready(function() {
    let consults = [];
    let managers = [];
	let currentManagerId = null;
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredConsults = [];
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
            loadConsults();
        });
    }

    function loadConsults() {
        $.ajax({
            url: '/adm/api/consult_manager/get_consult_managers.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
					console.log(response.data);
                    consults = response.data;
                    filteredConsults = [...consults];
                    renderConsults();
                }
            }
        });
    }

    function renderConsults() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageData = filteredConsults.slice(startIndex, endIndex);
        
        const tbody = $('#consultList');
        tbody.empty();
		
		if (pageData.length === 0) {
            tbody.append('<tr><td colspan="11" class="text-center">데이터가 없습니다.</td></tr>');
            return;
        }
        
        pageData.forEach(function(item) {
            const row = `
                <tr data-id="${item.consult_no}">
					<td class="editable" data-field="name">${item.name || ''}</td>
					<td class="editable" data-field="phone">${item.phone || ''}</td>
					<td>
						<select class="category-select" data-field="category" required>
							<option value="">선택</option>
							<option value="개인회생급여" ${item.category === '개인회생급여' ? 'selected' : ''}>개인회생급여</option>
							<option value="개인회생영업" ${item.category === '개인회생영업' ? 'selected' : ''}>개인회생영업</option>
							<option value="개인파산" ${item.category === '개인파산' ? 'selected' : ''}>개인파산</option>
						</select>
                    </td>
                    <td class="editable" data-field="datetime">${formatDateTime(item.datetime)}</td>
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
                    <td>
						<select class="prospect-select" data-field="prospect">
							<option value="">선택</option>
							<option value="부재" ${item.prospect === '부재' ? 'selected' : ''}>부재</option>
							<option value="불가" ${item.prospect === '불가' ? 'selected' : ''}>불가</option>
							<option value="낮음" ${item.prospect === '낮음' ? 'selected' : ''}>낮음</option>
							<option value="높음" ${item.prospect === '높음' ? 'selected' : ''}>높음</option>
						</select>
					</td>
                    <td>
                        <select class="manager-select" data-field="manager">
							<option value="">선택</option>
							${managers.map(m => `
								<option value="${m.employee_no}" ${item.consultant == m.employee_no ? 'selected' : ''}>
									${m.name}
								</option>
							`).join('')}
						</select>
                    </td>
					<td>
						<select class="paper-select" data-field="paper">
							<option value="">선택</option>
							${(() => {
								// 현재 선택된 상담자(consultant)의 부서 찾기
								const consultant = managers.find(m => m.employee_no == item.consultant);
								if (!consultant) return '';
								
								// 같은 부서 직원들만 필터링
								return managers
									.filter(m => m.department === consultant.department)
									.map(m => `
										<option value="${m.employee_no}" ${item.paper == m.employee_no ? 'selected' : ''}>
											${m.name}
										</option>
									`).join('');
							})()}
						</select>
					</td>
                    <td>
                        <div><button type="button" class="btn-save" data-id="${item.consult_no}">저장</button></div>
                        <div><button type="button" class="btn-delete" data-id="${item.consult_no}">삭제</button></div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        renderPagination(filteredConsults.length);
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
		
		// 상담 내용 로드 함수
		function loadConsultContents(consult_no) {
			$.ajax({
				url: '/adm/api/consult_manager/get_consult_manager_contents.php',
				method: 'GET',
				data: { consult_no: consult_no },
				success: function(response) {
					if (response.success) {
						renderConsultContents(response.data);
					}
				}
			});
		}
		
		// 상담 내용 추가 버튼 클릭 이벤트
		$(document).on('click', '.btn-detail-add', function(e) {
			e.stopPropagation();
			
			const $row = $(this).closest('.detail-menu').prev('tr');
			const consult_no = $row.data('id');
			const manager_id = $row.find('.manager-select').val();

			const $btn = $(this);
			$btn.prop('disabled', true);
			
			$.ajax({
				url: '/adm/api/consult_manager/add_consult_manager_content.php',
				method: 'POST',
				data: {
					consult_no: consult_no,
					manager_id: manager_id || null,
					content: ' '
				},
				success: function(response) {
					if (response.success) {
						loadConsultContents(consult_no);
						// 새로운 내용이 추가된 후 마지막 content 요소에 포커스
						setTimeout(() => {
							const $lastContent = $('.detail-menu-content .detail-menu-row:last-child .content');
							$lastContent.focus();
							// 커서를 끝으로 이동
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

		// content 편집 이벤트 수정
		$(document).on('focus', '.detail-menu-row .content', function() {
			$(this).addClass('editing');
		});

		$(document).on('blur', '.detail-menu-row .content', function() {
			const $this = $(this);
			const $row = $this.closest('.detail-menu-row');
			const content_no = $row.data('content-no');
			const content = $this.text().trim();
			const $indicator = $row.find('.saving-indicator');
			
			if (!content_no) {
				console.error('content_no가 없습니다.');
				return;
			}
			
			$this.removeClass('editing');
			$indicator.addClass('show');

			// blur 시 바로 저장
			$.ajax({
				url: '/adm/api/consult_manager/update_consult_manager_content.php',
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

		// input 이벤트 핸들러 제거 (blur에서 처리하므로)
		$(document).off('input', '.detail-menu-row .content');

		// 상담 내용 렌더링 함수
		function renderConsultContents(contents) {
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
			
			// 다른 행의 active 클래스 제거
			$('.data-table tbody tr').not(this).removeClass('active');
			// 클릭한 행에 active 클래스 추가
			$(this).addClass('active');
			
			const $row = $(this);
			const consult_no = $row.data('id');
			const $table = $('.data-table table');
			const tableWidth = $table.width();
			
			$('.detail-menu').remove();
			
			// 새로운 detail-menu div 생성
			const menuContent = `
				<div class="detail-menu" style="width: ${tableWidth}px">
					<div class="detail-menu-container">
						<div class="detail-menu-title">
							<div class="detail-menu-text">상담내용</div>
						</div>
						<div class="detail-menu-left">
							<div class="detail-menu-content">
								<!-- 상담 내용이 여기에 동적으로 렌더링됩니다 -->
							</div>
						</div>
						<div class="detail-menu-right">
							<button type="button" class="btn-detail-add">추가</button>
						</div>
					</div>
				</div>
			`;
			
			$row.after(menuContent);
			
			// 애니메이션 효과를 위해 setTimeout 사용
			setTimeout(() => {
				$row.next('.detail-menu').addClass('active');
			}, 10);

			// 상담 내용 로드
			loadConsultContents(consult_no);
		});

		// 셀 편집 이벤트
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input, textarea').length > 0) return;
			
			// 현재 행에 editing 클래스 추가
			$(this).closest('tr').addClass('editing');
			
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
			
			// blur 이벤트
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
				$row.removeClass('editing');
			});
		});

		// 상담 내용 삭제 버튼 이벤트
		$(document).on('click', '.btn-detail-delete', function(e) {
			e.stopPropagation();
			const $row = $(this).closest('.detail-menu-row');
			const content_no = $row.data('content-no');
			
			if(confirm('정말 삭제하시겠습니까?')) {
				$.ajax({
					url: '/adm/api/consult_manager/delete_consult_manager_content.php',
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
			
			// paper 값 처리를 명확하게 
			let paperValue = row.find('.paper-select').val();
			paperValue = (paperValue && paperValue !== '' && paperValue !== '선택') ? paperValue : null;
			
			// consultant(상담자) 값 처리 추가
			let consultantValue = row.find('.manager-select').val();
			consultantValue = (consultantValue && consultantValue !== '' && consultantValue !== '선택') ? consultantValue : null;
			
			const data = {
				consult_no: id,
				name: row.find('[data-field="name"]').text().trim(),
				phone: row.find('[data-field="phone"]').text().trim(),
				category: row.find('.category-select').val(),
				datetime: row.find('[data-field="datetime"]').text().trim(),
				region: row.find('[data-field="region"]').text().trim(),
				birth_date: row.find('[data-field="birth_date"]').text().trim(),
				debt_amount: row.find('[data-field="debt_amount"]').text().trim(),
				consultation_time: row.find('[data-field="consultation_time"]').text().trim(),
				content: row.find('[data-field="content"]').text().trim(),
				prospect: row.find('.prospect-select').val() || null,
				consultant: consultantValue,  // consultant 값 사용
				paper: paperValue
			};

			if (!validateConsultData(data)) return;

			const $btn = $(this);
			$btn.prop('disabled', true);

			// 상담정보 저장 후 case_management 테이블도 함께 업데이트
			$.ajax({
				url: '/adm/api/consult_manager/update_consult_manager.php',
				method: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						// case_management 테이블도 함께 업데이트
						$.ajax({
							url: '/adm/api/consult_manager/update_case_management_paper.php',
							method: 'POST',
							data: {
								name: data.name,
								consult_no: id,
								paper_no: paperValue,
								category: data.category,
								consultant: consultantValue  // consultant 값 추가
							},
							success: function(caseResponse) {
								if (caseResponse.success) {
									alert('저장되었습니다.');
									loadConsults();
								} else {
									alert(caseResponse.message || '사건 정보 업데이트 실패');
								}
							},
							error: function(xhr, status, error) {
								console.error('사건 정보 업데이트 중 오류:', error);
								alert('사건 정보 업데이트에 실패했습니다.');
							}
						});
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
				url: '/adm/api/consult_manager/delete_consult_manager.php',
				method: 'POST',
				data: { consult_no: id },
				success: function(response) {
					if (response.success) {
						alert('삭제되었습니다.');
						loadConsults();
					} else {
						alert(response.message || '삭제 실패');
					}
				}
			});
		});

		// 담당자 변경 이벤트
		$('.manager-select').off('change').on('change', function() {
			const $row = $(this).closest('tr');
			const consultantId = $(this).val();
			const $paperSelect = $row.find('.paper-select');
			
			$paperSelect.empty().append('<option value="">선택</option>');
			
			if (consultantId) {
				// 선택된 상담자의 부서 찾기
				const consultant = managers.find(m => m.employee_no == consultantId);
				if (consultant) {
					// 같은 부서 직원들로 옵션 채우기
					managers
						.filter(m => m.department === consultant.department)
						.forEach(m => {
							$paperSelect.append(`
								<option value="${m.employee_no}">${m.name}</option>
							`);
						});
				}
			}
		});

		// Enter 키 이벤트 - 상담 내용 추가
		$('.new-content').off('keydown').on('keydown', function(e) {
			if (e.keyCode === 13 && !e.shiftKey) {
				e.preventDefault();
				$(this).closest('.detail-menu-right').find('.btn-detail-add').click();
			}
		});
	}

    // 검색 기능
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        filteredConsults = consults.filter(item => 
            (item.name || '').toLowerCase().includes(searchTerm) ||
            (item.phone || '').toLowerCase().includes(searchTerm) ||
            (item.category || '').toLowerCase().includes(searchTerm) ||
            (item.region || '').toLowerCase().includes(searchTerm) ||
            (item.content || '').toLowerCase().includes(searchTerm) ||
            (item.prospect || '').toLowerCase().includes(searchTerm) ||
            (item.manager_name || '').toLowerCase().includes(searchTerm)
        );
        
        currentPage = 1;
        renderConsults();
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
    function validateConsultData(data) {
        if (!data.name.trim()) {
            alert('이름을 입력해주세요.');
            return false;
        }
        if (!data.phone.trim()) {
            alert('연락처를 입력해주세요.');
            return false;
        }
        if (!data.category) {
            alert('상담분야는 필수 선택 항목입니다.');
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
                renderConsults();
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
            renderConsults();
        }
    });

    $('.next-btn').click(function() {
        const totalPages = Math.ceil(filteredConsults.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderConsults();
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

        // 정렬 표시자 업데이트
        $('.sort-indicator').remove();
        $(this).append(`<span class="sort-indicator">${sortDirection === 'asc' ? '▲' : '▼'}</span>`);

        // 데이터 정렬
        filteredConsults.sort((a, b) => {
            let valueA = (a[field] || '').toString();
            let valueB = (b[field] || '').toString();

            // 날짜 필드 특별 처리
            if (field === 'datetime' || field === 'birth_date') {
                valueA = new Date(valueA);
                valueB = new Date(valueB);
            }
            // 숫자 필드 처리
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

        renderConsults();
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
    $('.category-select, .manager-select').on('change', function() {
        $(this).closest('tr').find('.btn-save').addClass('highlight');
    });

    // 윈도우 리사이즈 이벤트
    $(window).resize(function() {
		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function() {
			const tableWidth = $('#consultList').width();
			$('.detail-menu').width(tableWidth);
			$(this).trigger('resizeEnd');
		}, 10);
	});
});
