$(document).ready(function() {
    let cases = [];
    let managers = [];
    const itemsPerPage = 15;
    let currentPage = 1;
    let filteredCases = [];
    let sortField = 'datetime';
    let sortDirection = 'desc';

    // 초기 데이터 로드
    initializeData();
	
	// contenteditable 요소에서 입력 처리
	$(document).on('input', '.detail-menu-row .content.money-field', function(e) {
		let selection = window.getSelection();
		let range = selection.getRangeAt(0);
		let position = range.startOffset;
		
		let value = $(this).text().replace(/[^\d]/g, '');
		if (value) {
			let formattedValue = formatCurrency(value);
			$(this).text(formattedValue);
			
			// 캐럿 위치 복원
			let newRange = document.createRange();
			newRange.setStart(this.firstChild, Math.min(position, formattedValue.length));
			newRange.collapse(true);
			selection.removeAllRanges();
			selection.addRange(newRange);
		}
	});

	// contenteditable 요소에서 키 입력 제한
	$(document).on('keypress', '.detail-menu-row .content.money-field', function(e) {
		// 숫자만 입력 허용
		if (e.which < 48 || e.which > 57) {
			e.preventDefault();
		}
	});

	// contenteditable 요소에서 붙여넣기 처리
	$(document).on('paste', '.detail-menu-row .content.money-field', function(e) {
		e.preventDefault();
		let text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
		let value = text.replace(/[^\d]/g, '');
		if (value) {
			$(this).text(formatCurrency(value));
		}
	});

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
            loadCases();
        });
    }

    function loadCases() {
        $.ajax({
            url: '/adm/api/case_management/get_case_managements.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    cases = response.data;
                    filteredCases = [...cases];
                    renderCases();
                }
            }
        });
    }

	function renderCases() {
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = filteredCases.slice(startIndex, endIndex);
		
		const tbody = $('#caseList');
		tbody.empty();
		
		if (pageData.length === 0) {
			tbody.append('<tr><td colspan="11" class="text-center">데이터가 없습니다.</td></tr>');
			return;
		}
		
		pageData.forEach(function(item) {
			// case_no 존재 여부 확인
			if (!item.case_no) {
				console.error('Missing case_no for item:', item);
				return;
			}

			const row = `
				<tr data-case-no="${item.case_no}" class="case-row">
					<td class="editable" data-field="name">${item.name || ''}</td>
					<td class="editable" data-field="phone">${item.phone || ''}</td>
					<td class="editable" data-field="case_number">${item.case_number || ''}</td>
					<td>
						<select class="court-select" data-field="court_name">
							<option value="">선택</option>
							<option value="서울회생법원" ${item.court_name === '서울회생법원' ? 'selected' : ''}>서울회생법원</option>
							<option value="의정부지방법원" ${item.court_name === '의정부지방법원' ? 'selected' : ''}>의정부지방법원</option>
							<option value="인천지방법원" ${item.court_name === '인천지방법원' ? 'selected' : ''}>인천지방법원</option>
							<option value="수원지방법원" ${item.court_name === '수원지방법원' ? 'selected' : ''}>수원지방법원</option>
							<option value="춘천지방법원" ${item.court_name === '춘천지방법원' ? 'selected' : ''}>춘천지방법원</option>
							<option value="춘천강릉지원" ${item.court_name === '춘천강릉지원' ? 'selected' : ''}>춘천강릉지원</option>
							<option value="대전지방법원" ${item.court_name === '대전지방법원' ? 'selected' : ''}>대전지방법원</option>
							<option value="청주지방법원" ${item.court_name === '청주지방법원' ? 'selected' : ''}>청주지방법원</option>
							<option value="대구지방법원" ${item.court_name === '대구지방법원' ? 'selected' : ''}>대구지방법원</option>
							<option value="부산지방법원" ${item.court_name === '부산지방법원' ? 'selected' : ''}>부산지방법원</option>
							<option value="울산지방법원" ${item.court_name === '울산지방법원' ? 'selected' : ''}>울산지방법원</option>
							<option value="창원지방법원" ${item.court_name === '창원지방법원' ? 'selected' : ''}>창원지방법원</option>
							<option value="광주지방법원" ${item.court_name === '광주지방법원' ? 'selected' : ''}>광주지방법원</option>
							<option value="전주지방법원" ${item.court_name === '전주지방법원' ? 'selected' : ''}>전주지방법원</option>
							<option value="제주지방법원" ${item.court_name === '제주지방법원' ? 'selected' : ''}>제주지방법원</option>
						</select>
					</td>
					<td>
						<select class="consultant-select" data-field="consultant">
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
							${managers.map(m => `
								<option value="${m.employee_no}" ${item.paper == m.employee_no ? 'selected' : ''}>
									${m.name}
								</option>
							`).join('')}
						</select>
					</td>
					<td class="detail-cell">
						<table class="detail-table">
							<tr>
								<th>계약일자 |</th>
								<td class="editable date-cell" data-field="contract_date">${formatDate(item.contract_date) || ''}</td>
							</tr>
							<tr>
								<th>접수일자 |</th>
								<td class="date-cell" data-field="accept_date">${formatDate(item.accept_date) || ''}</td>
							</tr>
							<tr>
								<th>개시일자 |</th>
								<td class="date-cell" data-field="start_date">${formatDate(item.start_date) || ''}</td>
							</tr>
						</table>
					</td>
					<td class="detail-cell">
						<table class="detail-table">
							<tr>
								<th>수임료 |</th>
								<td class="editable money-field" data-field="application_fee">
									${formatCurrency(item.application_fee) || ''} 원
								</td>
							</tr>
							<tr>
								<th>납부금 |</th>
								<td class="money-field readonly" data-field="payment_amount">
									${formatCurrency(item.payment_amount) || ''} 원
								</td>
							</tr>
							<tr>
								<th>미납금 |</th>
								<td class="money-field readonly" data-field="unpaid_amount">
									${formatCurrency(item.unpaid_amount) || ''} 원
								</td>
							</tr>
						</table>
					</td>
					<td>
						<div><button type="button" class="btn-save" data-case-no="${item.case_no}">저장</button></div>
						<div><button type="button" class="btn-delete" data-case-no="${item.case_no}">삭제</button></div>
					</td>
				</tr>
			`;
			tbody.append(row);
		});

		renderPagination(filteredCases.length);
		bindEvents();
	}
	
	function bindEvents() {
		$('.date-cell input[type="date"]').off('change').on('change', function() {
			const $row = $(this).closest('tr');
			const $cell = $(this).closest('.date-cell');
			const newValue = $(this).val();
			const fieldName = $cell.data('field');

			// consult_paper_content 테이블과 case_management 테이블 모두 업데이트
			$.ajax({
				url: '/adm/api/case_management/update_case_management_dates.php',
				method: 'POST',
				data: {
					paper_no: $row.data('id'),
					field_name: fieldName,
					value: newValue
				},
				success: function(response) {
					if (response.success) {
						// 상태도 자동 업데이트
						if (fieldName === 'accept_date') {
							$row.find('.status-select').val('접수');
						} else if (fieldName === 'start_date') {
							$row.find('.status-select').val('개시');
						} else if (fieldName === 'approval_date') {
							$row.find('.status-select').val('인가');
						}
					} else {
						alert(response.message || '날짜 저장 실패');
					}
				},
				error: function() {
					alert('날짜 저장 중 오류가 발생했습니다.');
				}
			});
		});
		
		function updatePaymentAmount(case_no) {
			const $row = $(`.case-row[data-case-no="${case_no}"]`);
			const $contents = $('.detail-menu-row .content');
			let totalPayment = 0;
			
			$contents.each(function() {
				const amount = unformatCurrency($(this).text()) || 0;
				totalPayment += amount;
			});
			
			// totalPayment가 0이고 이전에 저장된 payment_amount가 있다면 업데이트하지 않음
			if (totalPayment === 0) {
				const currentPayment = unformatCurrency($row.find('[data-field="payment_amount"]').text());
				if (currentPayment > 0) {
					return;
				}
			}
			
			const applicationFee = unformatCurrency($row.find('[data-field="application_fee"]').text()) || 0;
			const unpaidAmount = applicationFee - totalPayment;
			
			$row.find('[data-field="payment_amount"]').text(formatCurrency(totalPayment) + ' 원');
			$row.find('[data-field="unpaid_amount"]').text(formatCurrency(unpaidAmount) + ' 원');
			
			saveCase($row, totalPayment, unpaidAmount);
		}
		
		function saveCase($row, totalPayment, unpaidAmount) {
			const case_no = $row.attr('data-case-no');
			
			// totalPayment가 0이고 이전에 저장된 payment_amount가 있다면 저장하지 않음
			if (totalPayment === 0) {
				const currentPayment = unformatCurrency($row.find('[data-field="payment_amount"]').text());
				if (currentPayment > 0) {
					return;
				}
			}

			const data = {
				case_no: case_no,
				name: $row.find('[data-field="name"]').text().trim(),
				phone: $row.find('[data-field="phone"]').text().trim(),
				case_number: $row.find('[data-field="case_number"]').text().trim(),
				court_name: $row.find('.court-select').val() || null,
				contract_date: $row.find('[data-field="contract_date"]').text().trim(),
				consultant: $row.find('.consultant-select').val() || null,
				paper: $row.find('.paper-select').val() || null,
				application_fee: unformatCurrency($row.find('[data-field="application_fee"]').text()),
				payment_amount: totalPayment,
				unpaid_amount: unpaidAmount
			};
			
			if (!validateCaseData(data)) return;
			
			$.ajax({
				url: '/adm/api/case_management/update_case_management.php',
				method: 'POST',
				data: data,
				success: function(response) {
					if (!response.success) {
						alert(response.message || '저장 실패');
					}
				}
			});
		}
		
        function loadCaseContents(case_no, callback) {
			if(!case_no) {
				console.error('case_no is missing');
				return;
			}
			
			$.ajax({
				url: '/adm/api/case_management/get_case_management_contents.php',
				method: 'GET',
				data: { case_no: case_no },
				success: function(response) {
					if (response.success) {
						renderCaseContents(response.data);
						// 콜백 함수가 있으면 실행
						if (typeof callback === 'function') {
							callback();
						}
					} else {
						console.error('Failed to load case contents:', response.message);
					}
				},
				error: function(xhr, status, error) {
					console.error('Ajax error:', error);
				}
			});
		}

        $(document).off('click.removeActive').on('click.removeActive', function(e) {
			// 클릭된 요소가 데이터 행이나 상세 메뉴가 아닐 경우에만 처리
			if (!$(e.target).closest('.data-table tr, .detail-menu').length) {
				// 현재 편집 중인 내용이 있다면 먼저 저장
				const $editing = $('.detail-menu-row .content.editing');
				if ($editing.length) {
					$editing.trigger('blur'); // blur 이벤트를 트리거하여 저장
				}
				
				// active 클래스 제거 전에 현재 payment_amount와 unpaid_amount 저장
				const $activeRow = $('.data-table tbody tr.active');
				if ($activeRow.length) {
					const case_no = $activeRow.attr('data-case-no');
					const payment_amount = unformatCurrency($activeRow.find('[data-field="payment_amount"]').text());
					const unpaid_amount = unformatCurrency($activeRow.find('[data-field="unpaid_amount"]').text());
					
					// 납부금이 0이 아닐 경우에만 저장 진행
					if (payment_amount > 0) {
						saveCase($activeRow, payment_amount, unpaid_amount);
					}
				}

				// 이후 active 클래스 제거 및 detail-menu 제거
				$('.data-table tbody tr').removeClass('active');
				$('.detail-menu').remove();
			}
		});

        // 행 클릭 이벤트 
        $('.data-table tbody').on('click', 'tr.case-row', function(e) {
			if ($(e.target).is('button, input, select, textarea')) {
				return;
			}
			
			const $row = $(this);
			const case_no = $row.attr('data-case-no');
			
			if (!case_no) {
				console.error('Could not find case_no for row:', $row);
				return;
			}

			$('.data-table tbody tr').not($row).removeClass('active');
			$row.addClass('active');
			
			const $table = $('.data-table table');
			const tableWidth = $table.width();
			
			$('.detail-menu').remove();
			
			const menuContent = `
				<div class="detail-menu" style="width: ${tableWidth}px">
					<div class="detail-menu-container">
						<div class="detail-menu-title">
							<div class="detail-menu-text">수임료현황</div>
						</div>
						<div class="detail-menu-left">
							<div class="detail-menu-content">
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

			loadCaseContents(case_no);
		});
		
		// 메모 추가 버튼 클릭 이벤트
		$(document).off('click', '.btn-detail-add').on('click', '.btn-detail-add', function(e) {
			e.stopPropagation();
			
			const $row = $(this).closest('.detail-menu').prev('tr');
			const case_no = $row.attr('data-case-no');
			
			if (!case_no) {
				alert('사건 정보를 찾을 수 없습니다.');
				return;
			}

			const $btn = $(this);
			$btn.prop('disabled', true);
			
			$.ajax({
				url: '/adm/api/case_management/add_case_management_content.php',
				method: 'POST',
				data: {
					case_no: case_no,
					content: 0,
					checker_id: window.loggedInEmployee.employee_no
				},
				success: function(response) {
					if (response.success) {
						loadCaseContents(case_no, function() {
							// 새로 추가된 마지막 행의 content 요소 찾기
							const $lastContent = $('.detail-menu-content .detail-menu-row:first-child .content');
							
							// content 요소의 텍스트 선택
							const text = $lastContent.text();
							$lastContent.focus();
							
							// 텍스트의 시작부분에 커서 위치시키기
							const range = document.createRange();
							const sel = window.getSelection();
							range.setStart($lastContent[0].firstChild, 0);
							range.setEnd($lastContent[0].firstChild, text.length);
							sel.removeAllRanges();
							sel.addRange(range);
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
		
        // 셀 편집 이벤트 
		$('.editable').off('click').on('click', function() {
			if ($(this).find('input').length > 0) return;
			
			$(this).closest('tr').addClass('editing');
			
			const currentValue = $(this).text();
			const fieldType = $(this).data('field');
			
			let inputHtml;
			if ($(this).hasClass('date-cell') || fieldType === 'contract_date') {
				inputHtml = `<input type="date" value="${currentValue}" data-original="${currentValue}">`;
			} else if ($(this).hasClass('money-field')) {
				const rawValue = unformatCurrency(currentValue);
				inputHtml = `<input type="text" value="${rawValue}" data-original="${currentValue}">`;
			} else {
				inputHtml = `<input type="text" value="${currentValue}" data-original="${currentValue}">`;
			}
			
			$(this).html(inputHtml);
			const $input = $(this).find('input');
			
			if ($(this).hasClass('money-field')) {
				$input.on('input', function(e) {
					let value = $(this).val().replace(/[^\d]/g, '');
					if (value) {
						$(this).val(formatCurrency(value));
					}
				});
			}
			
			$input.focus().select();
		});

		// blur 이벤트 핸들러
        $('.editable').off('blur', 'input').on('blur', 'input', function() {
            const $row = $(this).closest('tr');
            const $cell = $(this).parent();
            let newValue = $(this).val();
            const originalValue = $(this).data('original');
            
            if ($cell.hasClass('money-field')) {
                newValue = formatCurrency(unformatCurrency(newValue));
            }
            
            $(this).parent().text(newValue || originalValue);
            $row.removeClass('editing');
        });

        // 메모 수정 이벤트
        $(document).on('focus', '.detail-menu-row .content', function() {
            $(this).addClass('editing');
        });
		
		// content 수정 시 금액 포맷팅 및 저장
		$(document).on('focus', '.detail-menu-row .content.money-field', function() {
			let value = $(this).text().replace(/[^\d]/g, '');
			$(this).text(value);
			$(this).addClass('editing');
		});

		$(document).on('blur', '.detail-menu-row .content.money-field', function() {
			const $this = $(this);
			const $row = $this.closest('.detail-menu-row');
			const content_no = $row.attr('data-content-no');
			const content = $this.text().replace(/[^\d]/g, '');
			const bank = $row.find('.bank-input').val();
			const case_no = $('.case-row.active').attr('data-case-no');
			
			$this.text(formatCurrency(content) + '원');
			$this.removeClass('editing');
			
			$.ajax({
				url: '/adm/api/case_management/update_case_management_content.php',
				method: 'POST',
				data: {
					content_no: content_no,
					content: content,
					bank: bank,
					checker_id: window.loggedInEmployee?.employee_no
				},
				success: function(response) {
					if (response.success) {
						// PHP에서 계산된 값으로 화면 업데이트
						const $caseRow = $(`.case-row[data-case-no="${case_no}"]`);
						$caseRow.find('[data-field="payment_amount"]').text(formatCurrency(response.payment_amount) + ' 원');
						$caseRow.find('[data-field="unpaid_amount"]').text(formatCurrency(response.unpaid_amount) + ' 원');
					} else {
						alert(response.message || '저장 실패');
					}
				},
				error: function() {
					alert('저장 중 오류가 발생했습니다.');
				}
			});
		});
		
		// 수임료 변경 이벤트
		$(document).on('blur', '[data-field="application_fee"] input', function() {
			const $row = $(this).closest('tr');
			const case_no = $row.data('case-no');
			const applicationFee = unformatCurrency($(this).val());
			
			// 서버에 수임료 변경사항 저장
			const data = {
				case_no: case_no,
				application_fee: applicationFee
			};

			$.ajax({
				url: '/adm/api/case_management/update_case_management.php',
				method: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						// 서버에서 계산된 값으로 화면 업데이트
						$row.find('[data-field="application_fee"]').text(formatCurrency(applicationFee) + ' 원');
						$row.find('[data-field="payment_amount"]').text(formatCurrency(response.payment_amount) + ' 원');
						$row.find('[data-field="unpaid_amount"]').text(formatCurrency(response.unpaid_amount) + ' 원');
					} else {
						alert(response.message || '저장 실패');
					}
				},
				error: function() {
					alert('저장 중 오류가 발생했습니다.');
				}
			});
		});

		// 은행명 입력과 확인자 선택 시 자동 저장
		$(document).on('change', '.detail-menu-row .bank-input, .detail-menu-row .checker-select', function() {
			const $row = $(this).closest('.detail-menu-row');
			const content_no = $row.attr('data-content-no');
			const content = unformatCurrency($row.find('.content').text());
			const bank = $row.find('.bank-input').val();
			const checker_id = $row.find('.checker-select').val();
			const $indicator = $row.find('.saving-indicator');
			
			$indicator.addClass('show');

			$.ajax({
				url: '/adm/api/case_management/update_case_management_content.php',
				method: 'POST',
				data: {
					content_no: content_no,
					content: content,
					bank: bank,
					checker_id: checker_id
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

        // 메모 내용 업데이트 이벤트
		$(document).on('blur', '.detail-menu-row .content', function() {
			const $this = $(this);
			if (!$this.hasClass('money-field')) return;  // money-field 클래스가 없으면 처리하지 않음
			
			const $row = $this.closest('.detail-menu-row');
			const content_no = $row.attr('data-content-no');
			const content = $this.text().replace(/[^\d]/g, '');
			const bank = $row.find('.bank-input').val();
			const checker_id = $row.find('.checker-select').val();
			const case_no = $('.case-row.active').attr('data-case-no');
			
			// 기존 값을 저장
			const originalBank = $row.find('.bank-input').data('original-value');
			const originalChecker = $row.find('.checker-select').data('original-value');
			
			$this.text(formatCurrency(content) + '원');
			$this.removeClass('editing');
			
			$.ajax({
				url: '/adm/api/case_management/update_case_management_content.php',
				method: 'POST',
				data: {
					content_no: content_no,
					content: content,
					bank: bank || originalBank,  // 빈 값이면 기존 값 사용
					checker_id: checker_id || originalChecker  // 빈 값이면 기존 값 사용
				},
				success: function(response) {
					if (response.success) {
						updatePaymentAmount(case_no);
						// 성공 시 현재 값을 original value로 저장
						$row.find('.bank-input').data('original-value', bank);
						$row.find('.checker-select').data('original-value', checker_id);
					} else {
						alert(response.message || '저장 실패');
						// 실패 시 원래 값으로 복원
						$row.find('.bank-input').val(originalBank);
						$row.find('.checker-select').val(originalChecker);
					}
				},
				error: function() {
					alert('저장 중 오류가 발생했습니다.');
					// 에러 시 원래 값으로 복원
					$row.find('.bank-input').val(originalBank);
					$row.find('.checker-select').val(originalChecker);
				}
			});
		});

        // 메모 삭제 버튼 이벤트
        $(document).on('click', '.btn-detail-delete', function(e) {
            e.stopPropagation();
            const $row = $(this).closest('.detail-menu-row');
            const content_no = $row.attr('data-content-no');  // .data()를 .attr()로 수정
            
            if(confirm('정말 삭제하시겠습니까?')) {
                $.ajax({
                    url: '/adm/api/case_management/delete_case_management_content.php',
                    method: 'POST',
                    data: { content_no: content_no },
                    success: function(response) {
                        if (response.success) {
                            $row.slideUp(200, function() {
                                $(this).remove();
                                if ($('.detail-menu-row').length === 0) {
                                    $('.detail-menu-content').html('<div class="detail-menu-row">등록된 메모가 없습니다.</div>');
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
            const case_no = row.attr('data-case-no');  // .data()를 .attr()로 수정
            
            const data = {
                case_no: case_no,
                name: row.find('[data-field="name"]').text().trim(),
                phone: row.find('[data-field="phone"]').text().trim(),
                case_number: row.find('[data-field="case_number"]').text().trim(),
                court_name: row.find('.court-select').val() || null,
                contract_date: row.find('[data-field="contract_date"]').text().trim(),
                consultant: row.find('.consultant-select').val() || null,
                paper: row.find('.paper-select').val() || null,
                application_fee: unformatCurrency(row.find('[data-field="application_fee"]').text()),
                payment_amount: unformatCurrency(row.find('[data-field="payment_amount"]').text()),
                unpaid_amount: unformatCurrency(row.find('[data-field="unpaid_amount"]').text())
            };

            // 데이터 검증
            if (!validateCaseData(data)) return;

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/adm/api/case_management/update_case_management.php',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert('저장되었습니다.');
                        loadCases();
                    } else {
                        alert(response.message || '저장 실패');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('저장 오류:', error);
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
            
            const case_no = $(this).attr('data-case-no');  // .data()를 .attr()로 수정
            
            $.ajax({
                url: '/adm/api/case_management/delete_case_management.php',
                method: 'POST',
                data: { case_no: case_no },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        loadCases();
                    } else {
                        alert(response.message || '삭제 실패');
                    }
                }
            });
        });
    }

	function renderCaseContents(contents) {
		const $content = $('.detail-menu-content');
		$content.empty();

		if (contents.length === 0) {
			$content.append('<div class="detail-menu-row">등록된 수임료가 없습니다.</div>');
			return;
		}
		
		contents.forEach(function(item) {
			const date = new Date(item.created_at);
			const formattedDate = `${date.getFullYear()}. ${String(date.getMonth() + 1).padStart(2, '0')}. ${String(date.getDate()).padStart(2, '0')}.`;
			const formattedTime = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
			const formattedAmount = formatCurrency(item.content) + '원';
			const checkerName = item.checker_id ? (managers.find(m => m.employee_no == item.checker_id)?.name || '') : '';

			$content.append(`
				<div class="detail-menu-row" data-content-no="${item.content_no}">
					<span class="date">${formattedDate}</span>
					<span class="time">${formattedTime}</span>
					<div class="content money-field" contenteditable="true" 
						 data-original-value="${item.content}">${formattedAmount}</div>
					<input type="text" class="bank-input" 
						   value="${item.bank || ''}" 
						   data-original-value="${item.bank || ''}" 
						   placeholder="은행명">
					<span class="checker-label">확인자: ${checkerName}</span>
					<button type="button" class="btn-detail-delete">삭제</button>
					<div class="saving-indicator">저장중...</div>
				</div>
			`);
		});
	}

    // 검색 기능
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        filteredCases = cases.filter(item => 
            (item.name || '').toLowerCase().includes(searchTerm) ||
            (item.phone || '').toLowerCase().includes(searchTerm) ||
            (item.case_number || '').toLowerCase().includes(searchTerm) ||
            (item.category || '').toLowerCase().includes(searchTerm) ||
            (item.court_name || '').toLowerCase().includes(searchTerm) ||
            (item.status || '').toLowerCase().includes(searchTerm)
        );
        
        currentPage = 1;
        renderCases();
    });

    // 유틸리티 함수들
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        return date.getFullYear() + '-' + 
            String(date.getMonth() + 1).padStart(2, '0') + '-' + 
            String(date.getDate()).padStart(2, '0');
    }
	
	function formatNumber(num) {
        if (!num) return 0;
        return new Intl.NumberFormat('ko-KR', { maximumFractionDigits: 0 }).format(num);
    }
    
    // 숫자를 통화 형식으로 변환 (1000000 -> 1,000,000)
    function formatCurrency(number) {
        if (!number) return 0;
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 통화 형식을 숫자로 변환 (1,000,000 -> 1000000)
	function unformatCurrency(formatted) {
		if (!formatted) return 0; // null이나 빈 문자열일 경우 0 반환
		return parseInt(formatted.replace(/[^\d]/g, '')) || 0; // NaN일 경우 0 반환
	}

    function validateCaseData(data) {
        // 기존 검증 로직
        if (!data.name.trim()) {
            alert('성명을 입력해주세요.');
            return false;
        }
        if (!data.phone.trim()) {
            alert('연락처를 입력해주세요.');
            return false;
        }
        if (!validatePhoneFormat(data.phone)) {
            alert('올바른 전화번호 형식이 아닙니다. (예: 010-1234-5678)');
            return false;
        }

        // 금액 필드 검증 추가
        const moneyFields = ['application_fee', 'payment_amount', 'unpaid_amount'];
        for (const field of moneyFields) {
            if (data[field]) {
                // 숫자가 아닌 문자 제거 (쉼표 등)
                const value = data[field].toString().replace(/[^\d]/g, '');
                if (isNaN(value)) {
                    alert('금액은 숫자만 입력 가능합니다.');
                    return false;
                }
                // 정수로 변환
                data[field] = parseInt(value, 10);
            }
        }

        return true;
    }

    // 전화번호 형식 검사
    function validatePhoneFormat(phone) {
        return /^01[016789]-\d{3,4}-\d{4}$/.test(phone);
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
                renderCases();
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
            renderCases();
        }
    });

    $('.next-btn').click(function() {
        const totalPages = Math.ceil(filteredCases.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderCases();
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

        filteredCases.sort((a, b) => {
            let valueA = (a[field] || '').toString();
            let valueB = (b[field] || '').toString();

            if (field === 'datetime' || field.endsWith('_date')) {
                valueA = new Date(valueA);
                valueB = new Date(valueB);
            }

            if (sortDirection === 'asc') {
                return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
            } else {
                return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
            }
        });

        renderCases();
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

    // 윈도우 리사이즈 이벤트
    $(window).resize(function() {
        if (this.resizeTO) clearTimeout(this.resizeTO);
        this.resizeTO = setTimeout(function() {
            const tableWidth = $('.data-table table').width();
            $('.detail-menu').width(tableWidth);
            $(this).trigger('resizeEnd');
        }, 10);
    });
});