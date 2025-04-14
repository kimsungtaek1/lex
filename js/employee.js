$(document).ready(function() {
    let employees = [];
    let departments = [];
    let positions = [];
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredEmployees = [];
    let sortField = 'name';
    let sortDirection = 'asc';
	const AUTH_LEVELS = {
        5: '대표',
        4: '총괄',
        3: '사무장',
        2: '서류',
        1: '신입'
    };

    // 초기 데이터 로드
    initializeData();

    // 탭 전환
    $('.stat-tab').click(function() {
        $('.stat-tab').removeClass('active');
        $(this).addClass('active');
        
        const type = $(this).data('type');
        $('.section-content').hide();
        
        switch(type) {
            case 'employee':
                $('#employeeSection').show();
                initializeData();
                break;
            case 'department':
                $('#departmentSection').show();
                loadDepartments();
                break;
            case 'position':
                $('#positionSection').show();
                loadPositions();
                break;
			case 'information':
                $('#informationSection').show();
                loadPositions();
                break;
        }
    });

    // 초기 데이터 로드 함수
    function initializeData() {
        Promise.all([
            $.ajax({
                url: '/adm/api/department/get_departments.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        departments = response.data;
                    }
                }
            }),
            $.ajax({
                url: '/adm/api/position/get_positions.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        positions = response.data;
                    }
                }
            })
        ]).then(() => {
            $.ajax({
                url: '/adm/api/employee/get_employees.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        employees = response.data;
                        filteredEmployees = [...employees];
                        renderEmployees();
                    }
                }
            });
        });
    }

    // 부서 데이터 로드 함수
    function loadDepartments() {
        $.ajax({
            url: '/adm/api/department/get_departments.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    departments = response.data;
                    renderDepartments();
                } else {
                    alert(response.message || '부서 데이터 로드 실패');
                }
            }
        });
    }

    // 직위 데이터 로드 함수
    function loadPositions() {
        $.ajax({
            url: '/adm/api/position/get_positions.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    positions = response.data;
                    renderPositions();
                } else {
                    alert(response.message || '직위 데이터 로드 실패');
                }
            }
        });
    }

    // 직원 목록 렌더링
	function renderEmployees() {
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = filteredEmployees.slice(startIndex, endIndex);
		
		const tbody = $('#employeeList');
		tbody.empty();
		
		pageData.forEach(function(emp) {
			const row = `
				<tr data-id="${emp.employee_no}">
					<td class="editable" data-field="name">${emp.name || ''}</td>
					<td class="editable" data-field="employee_id">${emp.employee_id || ''}</td>
					<td>
						<select class="dept-select" data-field="department">
							<option value="">선택</option>
							${departments.map(dept => 
								`<option value="${dept.dept_name}" ${emp.department === dept.dept_name ? 'selected' : ''}>
									${dept.dept_name}
								</option>`
							).join('')}
						</select>
					</td>
					<td>
						<select class="position-select" data-field="position">
							<option value="">선택</option>
							${positions.map(pos => 
								`<option value="${pos.position_name}" ${emp.position === pos.position_name ? 'selected' : ''}>
									${pos.position_name}
								</option>`
							).join('')}
						</select>
					</td>
					<td class="editable" data-field="email">${emp.email || ''}</td>
					<td class="editable" data-field="phone">${emp.phone || ''}</td>
					<td class="editable" data-field="hire_date">${emp.hire_date || ''}</td>
					<td class="editable" data-field="access_date">${emp.access_date || ''}</td>
					<td>
						<select class="status-field" data-field="status">
							<option value="재직" ${emp.status === '재직' ? 'selected' : ''}>재직</option>
							<option value="휴직" ${emp.status === '휴직' ? 'selected' : ''}>휴직</option>
							<option value="퇴사" ${emp.status === '퇴사' ? 'selected' : ''}>퇴사</option>
						</select>
					</td>
					<td>
						<select class="auth-field" data-field="auth">
							${Object.entries(AUTH_LEVELS)
								.reverse()
								.map(([level, title]) => 
									`<option value="${level}" ${parseInt(emp.auth) === parseInt(level) ? 'selected' : ''}>
										${level}(${title})
									</option>`
								).join('')}
						</select>
					</td>
					<td>
						<div><button type="button" class="btn-save" data-id="${emp.employee_no}">저장</button></div>
						<div><button type="button" class="btn-delete" data-id="${emp.employee_no}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});

		renderPagination(filteredEmployees.length);
		bindEvents();
	}

    // 부서 목록 렌더링
	function renderDepartments() {
		const tbody = $('#departmentList');
		tbody.empty();
		
		departments.forEach(function(dept) {
			const row = `
				<tr data-id="${dept.dept_id}">
					<td class="editable" data-field="dept_name">${dept.dept_name || ''}</td>
					<td>
						<select class="manager-select" data-field="manager_id">
							<option value="">선택</option>
							${employees.map(emp => 
								`<option value="${emp.employee_no}" ${dept.manager_id == emp.employee_no ? 'selected' : ''}>
									${emp.name}
								</option>`
							).join('')}
						</select>
					</td>
					<td>
						<select class="use-yn-select" data-field="use_yn">
							<option value="Y" ${dept.use_yn === 'Y' ? 'selected' : ''}>사용</option>
							<option value="N" ${dept.use_yn === 'N' ? 'selected' : ''}>미사용</option>
						</select>
					</td>
					<td>
						<div><button type="button" class="btn-save-dept" data-id="${dept.dept_id}">저장</button></div>
						<div><button type="button" class="btn-delete-dept" data-id="${dept.dept_id}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});
		
		bindDepartmentEvents();
	}

    // 직위 목록 렌더링
	function renderPositions() {
		const tbody = $('#positionList');
		tbody.empty();
		
		positions.forEach(function(pos) {
			const row = `
				<tr data-id="${pos.position_id}">
					<td class="editable" data-field="position_name">${pos.position_name || ''}</td>
					<td class="editable" data-field="position_order">${pos.position_order || ''}</td>
					<td>
						<select class="use-yn-select" data-field="use_yn">
							<option value="Y" ${pos.use_yn === 'Y' ? 'selected' : ''}>사용</option>
							<option value="N" ${pos.use_yn === 'N' ? 'selected' : ''}>미사용</option>
						</select>
					</td>
					<td>
						<div><button type="button" class="btn-save-pos" data-id="${pos.position_id}">저장</button></div>
						<div><button type="button" class="btn-delete-pos" data-id="${pos.position_id}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});
		
		bindPositionEvents();
	}

    // 이벤트 바인딩
    function bindEvents() {
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
        // 날짜 입력 필드 이벤트 핸들러
		$('.editable[data-field="hire_date"], .editable[data-field="access_date"]').on('blur', 'input', function() {
			let value = $(this).val();
			if (value) {
				try {
					const date = new Date(value);
					if (!isNaN(date.getTime())) {
						value = date.toISOString().split('T')[0];
					}
				} catch (e) {
					console.error('날짜 변환 실패:', e);
				}
			}
			$(this).val(value);
		});
        
        // 셀 클릭시 편집 모드
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input').length > 0) return;
			
			const currentValue = $(this).text();
			const fieldType = $(this).data('field');
			
			if (fieldType === 'hire_date' || fieldType === 'access_date') {
				$(this).html(`<input type="date" value="${currentValue}" data-original="${currentValue}">`);
			} else {
				$(this).html(`<input type="text" value="${currentValue}" data-original="${currentValue}">`);
			}
			
			$(this).find('input').focus().select();
		});

        // 입력 완료시 값 저장
		$('.editable').off('blur', 'input').on('blur', 'input', function() {
			const $row = $(this).closest('tr');
			const newValue = $(this).val();
			const originalValue = $(this).data('original');
			$(this).parent().text(newValue || originalValue);
			
			// editing 클래스 제거
			$row.removeClass('editing');
		});

        // 저장 버튼 - 직원
		$('#employeeList .btn-save').off('click').on('click', function() {
			const $row = $(this).closest('tr');
			const employeeId = $row.data('id');
			
			const data = {
				id: employeeId,
				name: $row.find('[data-field="name"]').text(),
				employee_id: $row.find('[data-field="employee_id"]').text(),
				department: $row.find('.dept-select').val(),
				position: $row.find('.position-select').val(),
				phone: $row.find('[data-field="phone"]').text(),
				email: $row.find('[data-field="email"]').text(),
				hire_date: formatDateForSubmit($row.find('[data-field="hire_date"]').text()),
				access_date: formatDateForSubmit($row.find('[data-field="access_date"]').text()),
				status: $row.find('.status-field').val(),
				auth: $row.find('.auth-field').val()
			};

			if (!validateEmployeeData(data)) return;

			const url = employeeId === 'new' ? '/adm/api/employee/add_employee.php' : '/adm/api/employee/update_employee.php';

			$.ajax({
				url: url,
				method: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert('저장되었습니다.');
						initializeData();
					} else {
						alert(response.message || '저장 실패');
					}
				}
			});
		});

        // 삭제 버튼
        $('.btn-delete').off('click').on('click', function() {
            if (!confirm('정말 삭제하시겠습니까?')) return;

            const id = $(this).data('id');
            
            $.ajax({
                url: '/adm/api/employee/delete_employee.php',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        initializeData();
                    } else {
                        alert(response.message || '삭제 실패');
                    }
                }
            });
        });
    }

	// 부서 관련 이벤트 바인딩
	function bindDepartmentEvents() {
		// 부서 편집 가능 셀 클릭 이벤트
		$('#departmentList .editable').off('click').on('click', function() {
			if ($(this).find('input').length > 0) return;
			
			const currentValue = $(this).text();
			$(this).html(`<input type="text" value="${currentValue}" data-original="${currentValue}">`);
			$(this).find('input').focus().select();
		});

		// 부서 입력 완료시 값 저장
		$('#departmentList .editable').off('blur', 'input').on('blur', 'input', function() {
			const newValue = $(this).val();
			const originalValue = $(this).data('original');
			$(this).parent().text(newValue || originalValue);
		});

		// 부서 저장 버튼
		$('.btn-save-dept').off('click').on('click', function() {
			const $row = $(this).closest('tr');
			const deptId = $row.data('id');
			
			const data = {
				id: deptId,  // position_id에서 id로 변경
				dept_name: $row.find('[data-field="dept_name"]').text(),
				manager_id: $row.find('.manager-select').val() || null,
				use_yn: $row.find('.use-yn-select').val()
			};

			if (!validateDepartmentData(data)) return;

			$.ajax({
				url: '/adm/api/department/update_department.php',
				method: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert('저장되었습니다.');
						loadDepartments();
					} else {
						alert(response.message || '저장 실패');
					}
				},
				error: function(xhr, status, error) {  // 에러 핸들링 추가
					console.error('부서 저장 실패:', xhr.responseText);
					alert(xhr.responseJSON?.message || '저장 중 오류가 발생했습니다.');
				}
			});
		});

		// 부서 삭제 버튼
		$('.btn-delete-dept').off('click').on('click', function() {
			if (!confirm('정말 삭제하시겠습니까?')) return;

			const id = $(this).data('id');
			
			$.ajax({
				url: '/adm/api/department/delete_department.php',
				method: 'POST',
				data: { dept_id: id },
				success: function(response) {
					if (response.success) {
						alert('삭제되었습니다.');
						loadDepartments();
					} else {
						alert(response.message || '삭제 실패');
					}
				}
			});
		});
	}

	// 직위 관련 이벤트 바인딩
	function bindPositionEvents() {
		// 직위 편집 가능 셀 클릭 이벤트
		$('#positionList .editable').off('click').on('click', function() {
			if ($(this).find('input').length > 0) return;
			
			const currentValue = $(this).text();
			$(this).html(`<input type="text" value="${currentValue}" data-original="${currentValue}">`);
			$(this).find('input').focus().select();
		});

		// 직위 입력 완료시 값 저장
		$('#positionList .editable').off('blur', 'input').on('blur', 'input', function() {
			const newValue = $(this).val();
			const originalValue = $(this).data('original');
			$(this).parent().text(newValue || originalValue);
		});

		// 직위 저장 버튼
		$('.btn-save-pos').off('click').on('click', function() {
			const $row = $(this).closest('tr');
			const positionId = $row.data('id');
			
			const data = {
				id: positionId === 'new' ? 'new' : positionId,
				position_name: $row.find('[data-field="position_name"]').text().trim(),
				position_order: parseInt($row.find('[data-field="position_order"]').text()) || 0,
				use_yn: $row.find('.use-yn-select').val()
			};

			// 데이터 유효성 검사
			if (!validatePositionData(data)) return;

			// API 호출
			$.ajax({
				url: '/adm/api/position/update_position.php',
				method: 'POST',
				data: data,
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						alert('저장되었습니다.');
						loadPositions(); // 목록 새로고침
					} else {
						alert(response.message || '저장 실패');
					}
				},
				error: function(xhr, status, error) {
					const response = xhr.responseJSON;
					const errorMessage = response?.message || '저장 중 오류가 발생했습니다.';
					console.error('직위 저장 실패:', {
						status: xhr.status,
						error: error,
						response: xhr.responseText
					});
					alert(errorMessage);
				}
			});
		});

		// 직위 삭제 버튼
		$('.btn-delete-pos').off('click').on('click', function() {
			if (!confirm('정말 삭제하시겠습니까?')) return;

			const id = $(this).data('id');
			
			$.ajax({
				url: '/adm/api/position/delete_position.php',
				method: 'POST',
				data: { position_id: id },
				success: function(response) {
					if (response.success) {
						alert('삭제되었습니다.');
						loadPositions();
					} else {
						alert(response.message || '삭제 실패');
					}
				}
			});
		});
	}

   // 추가 버튼 이벤트
   $('.add-employee-btn').click(function() {
       const newRow = {
           employee_no: 'new',
           name: '',
           department: '',
           position: '',
           phone: '',
           employee_id: '',
           email: '',
           hire_date: '',
           access_date: '',
           status: '재직',
           auth: '1'
       };
       
       employees.unshift(newRow);
       filteredEmployees.unshift(newRow);
       currentPage = 1;
       renderEmployees();
   });

   // 검색 기능
   $('#searchInput').on('keyup', function() {
       const searchTerm = $(this).val().toLowerCase();
       filteredEmployees = employees.filter(emp => 
           (emp.name || '').toLowerCase().includes(searchTerm) ||
           (emp.employee_id || '').toLowerCase().includes(searchTerm) ||
           (emp.department || '').toLowerCase().includes(searchTerm) ||
           (emp.position || '').toLowerCase().includes(searchTerm)
       );
       currentPage = 1;
       renderEmployees();
   });

   // 상태 필터링
   $('.filter-select').change(function() {
       const status = $(this).val();
       filteredEmployees = status ? 
           employees.filter(emp => emp.status === status) : 
           [...employees];
       currentPage = 1;
       renderEmployees();
   });
   
   function formatDateForSubmit(dateStr) {
		if (!dateStr) return null;
		const date = new Date(dateStr);
		if (isNaN(date.getTime())) return null;
		return date.toISOString().split('T')[0];
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
               renderEmployees();
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
           renderEmployees();
       }
   });

   $('.next-btn').click(function() {
       const totalPages = Math.ceil(filteredEmployees.length / itemsPerPage);
       if (currentPage < totalPages) {
           currentPage++;
           renderEmployees();
       }
   });

   // 데이터 유효성 검사 함수들
   function validateEmployeeData(data) {
       if (!data.name.trim()) {
           alert('이름을 입력해주세요.');
           return false;
       }
       if (!data.department) {
           alert('부서를 선택해주세요.');
           return false;
       }
       if (!data.position) {
           alert('직위를 선택해주세요.');
           return false;
       }
       if (data.phone && !validatePhoneFormat(data.phone)) {
           alert('전화번호 형식이 올바르지 않습니다. (예: 010-1234-5678)');
           return false;
       }
       if (data.email && !validateEmailFormat(data.email)) {
           alert('이메일 형식이 올바르지 않습니다.');
           return false;
       }
       if (data.hire_date && !validateDate(data.hire_date)) {
           alert('입사일 형식이 올바르지 않습니다. (예: 2024-01-01)');
           return false;
       }
       if (data.access_date && !validateDate(data.access_date)) {
           alert('최종접속일 형식이 올바르지 않습니다. (예: 2024-01-01)');
           return false;
       }
       return true;
   }

   function validateDepartmentData(data) {
       if (!data.dept_name.trim()) {
           alert('부서명을 입력해주세요.');
           return false;
       }
       return true;
   }

	// 직위 데이터 유효성 검사
	function validatePositionData(data) {
		if (!data.position_name.trim()) {
			alert('직위명을 입력해주세요.');
			return false;
		}
		
		if (isNaN(data.position_order)) {
			alert('순서는 숫자만 입력 가능합니다.');
			return false;
		}
		
		return true;
	}

   // 형식 검사 함수들
   function validatePhoneFormat(phone) {
       return /^01[016789]-\d{3,4}-\d{4}$/.test(phone);
   }

   function validateEmailFormat(email) {
       return /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(email);
   }
   
   // 날짜 포맷 함수
	function formatDate(dateString) {
		if (!dateString) return '';
		const date = new Date(dateString);
		if (isNaN(date.getTime())) return dateString;
		return date.toISOString().split('T')[0];
	}

   function validateDate(dateStr) {
		if (!dateStr) return true;
		const regex = /^\d{4}-\d{2}-\d{2}$/;
		if (!regex.test(dateStr)) return false;
		
		const date = new Date(dateStr);
		return !isNaN(date.getTime());
	}


   // 에러 처리 함수
   function handleAjaxError(error, context) {
       console.error(`Error in ${context}:`, error);
       alert(`작업 중 오류가 발생했습니다. (${context})`);
   }

   // Ajax 에러 핸들러 설정
   $(document).ajaxError(function(event, jqXHR, settings, error) {
       console.error('Ajax error:', {
           url: settings.url,
           status: jqXHR.status,
           error: error
       });
       alert('서버와 통신 중 오류가 발생했습니다.');
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
       filteredEmployees.sort((a, b) => {
           let valueA = (a[field] || '').toString();
           let valueB = (b[field] || '').toString();

           // 날짜 필드 특별 처리
           if (field === 'hire_date' || field === 'access_date') {
               valueA = new Date(valueA.replace(/\./g, '-'));
               valueB = new Date(valueB.replace(/\./g, '-'));
           }
           
           // 숫자 필드 처리
           else if (field === 'auth') {
               valueA = parseInt(valueA) || 0;
               valueB = parseInt(valueB) || 0;
           }

           if (sortDirection === 'asc') {
               return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
           } else {
               return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
           }
       });

       renderEmployees();
   });
});