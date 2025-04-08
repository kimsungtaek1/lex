<?php
// 직접 실행 방지
if (!defined('INCLUDED_FROM_MAIN') && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

// 필수 파라미터 확인
$case_no = isset($_GET['case_no']) ? $_GET['case_no'] : '';
if (empty($case_no)) {
	echo "<script>alert('사건 번호가 필요합니다.'); window.close();</script>";
	exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>월평균소득계산기</title>
	<link rel="stylesheet" href="./css/salary_calculator.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
	<div class="content-wrapper">
		<div class="appendix-title">월평균소득계산기</div>
		
		<div class="calculator-header">
			<div class="form">
				<div class="form-title">
					<span>기준 기간</span>
				</div>
				<div class="form-content">
					<select id="salary_year">
						<?php
						$current_year = date('Y');
						for ($i = $current_year; $i >= $current_year - 5; $i--) {
							echo "<option value=\"{$i}\">{$i}년</option>";
						}
						?>
					</select>
					<span class="separator">|</span>
					<div class="radio-group">
						<input type="radio" id="option_year" name="calculation_type" value="year">
						<label for="option_year">년</label>
						<input type="radio" id="option_month" name="calculation_type" value="month" checked>
						<label for="option_month">월</label>
						<input type="radio" id="option_period1" name="calculation_type" value="period1">
						<label for="option_period1">개월(간)</label>
						<input type="radio" id="option_period2" name="calculation_type" value="period2">
						<label for="option_period2">개월(간)으로 월평균 소득금액 및 연간환산금액 계산</label>
					</div>
				</div>
			</div>
		</div>
		
		<!-- 소득내역 섹션 -->
		<div class="section-header">
			<span>소득내역</span>
		</div>
		<div class="salary-table">
			<div class="table-header">
				<div class="col">소득내역</div>
				<div class="col">1월</div>
				<div class="col">2월</div>
				<div class="col">3월</div>
				<div class="col">4월</div>
				<div class="col">5월</div>
				<div class="col">6월</div>
				<div class="col">7월</div>
				<div class="col">8월</div>
				<div class="col">9월</div>
				<div class="col">10월</div>
				<div class="col">11월</div>
				<div class="col">12월</div>
				<div class="col">합계</div>
				<div class="col">삭제</div>
			</div>
			<div id="income_container">
				<!-- 기본 소득 행 -->
				<div class="form-content" data-row-id="income_1">
					<div class="col"><input type="text" class="income-name" value="소득금액"></div>
					<div class="col"><input type="text" class="income-amount" data-month="1"></div>
					<div class="col"><input type="text" class="income-amount" data-month="2"></div>
					<div class="col"><input type="text" class="income-amount" data-month="3"></div>
					<div class="col"><input type="text" class="income-amount" data-month="4"></div>
					<div class="col"><input type="text" class="income-amount" data-month="5"></div>
					<div class="col"><input type="text" class="income-amount" data-month="6"></div>
					<div class="col"><input type="text" class="income-amount" data-month="7"></div>
					<div class="col"><input type="text" class="income-amount" data-month="8"></div>
					<div class="col"><input type="text" class="income-amount" data-month="9"></div>
					<div class="col"><input type="text" class="income-amount" data-month="10"></div>
					<div class="col"><input type="text" class="income-amount" data-month="11"></div>
					<div class="col"><input type="text" class="income-amount" data-month="12"></div>
					<div class="col"><span class="row-total">0</span></div>
					<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
				</div>
			</div>
			<div class="form-content total-row">
				<div class="col"><span>소득합계</span></div>
				<div class="col"><span class="month-total" data-month="1">0</span></div>
				<div class="col"><span class="month-total" data-month="2">0</span></div>
				<div class="col"><span class="month-total" data-month="3">0</span></div>
				<div class="col"><span class="month-total" data-month="4">0</span></div>
				<div class="col"><span class="month-total" data-month="5">0</span></div>
				<div class="col"><span class="month-total" data-month="6">0</span></div>
				<div class="col"><span class="month-total" data-month="7">0</span></div>
				<div class="col"><span class="month-total" data-month="8">0</span></div>
				<div class="col"><span class="month-total" data-month="9">0</span></div>
				<div class="col"><span class="month-total" data-month="10">0</span></div>
				<div class="col"><span class="month-total" data-month="11">0</span></div>
				<div class="col"><span class="month-total" data-month="12">0</span></div>
				<div class="col"><span id="income_grand_total">0</span></div>
				<div class="col"><button class="btn-add-row" id="add_income_row" type="button">추가</button></div>
			</div>
		</div>
		
		<!-- 공제내역 섹션 -->
		<div class="section-header">
			<span>공제내역</span>
		</div>
		<div class="deduction-table">
			<div class="table-header">
				<div class="col">공제내역</div>
				<div class="col">1월</div>
				<div class="col">2월</div>
				<div class="col">3월</div>
				<div class="col">4월</div>
				<div class="col">5월</div>
				<div class="col">6월</div>
				<div class="col">7월</div>
				<div class="col">8월</div>
				<div class="col">9월</div>
				<div class="col">10월</div>
				<div class="col">11월</div>
				<div class="col">12월</div>
				<div class="col">합계</div>
				<div class="col">삭제</div>
			</div>
			<div id="deduction_container">
				<!-- 기본 공제 행 -->
				<div class="form-content" data-row-id="deduction_1">
					<div class="col"><input type="text" class="deduction-name" value="공제금액"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="1"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="2"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="3"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="4"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="5"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="6"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="7"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="8"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="9"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="10"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="11"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="12"></div>
					<div class="col"><span class="row-total">0</span></div>
					<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
				</div>
			</div>
			<div class="form-content total-row">
				<div class="col"><span>공제합계</span></div>
				<div class="col"><span class="month-total" data-month="1">0</span></div>
				<div class="col"><span class="month-total" data-month="2">0</span></div>
				<div class="col"><span class="month-total" data-month="3">0</span></div>
				<div class="col"><span class="month-total" data-month="4">0</span></div>
				<div class="col"><span class="month-total" data-month="5">0</span></div>
				<div class="col"><span class="month-total" data-month="6">0</span></div>
				<div class="col"><span class="month-total" data-month="7">0</span></div>
				<div class="col"><span class="month-total" data-month="8">0</span></div>
				<div class="col"><span class="month-total" data-month="9">0</span></div>
				<div class="col"><span class="month-total" data-month="10">0</span></div>
				<div class="col"><span class="month-total" data-month="11">0</span></div>
				<div class="col"><span class="month-total" data-month="12">0</span></div>
				<div class="col"><span id="deduction_grand_total">0</span></div>
				<div class="col"><button class="btn-add-row" id="add_deduction_row" type="button">추가</button></div>
			</div>
		</div>
		
		<!-- 실수령액 섹션 -->
		<div class="section-header">
			<span>실수령액</span>
		</div>
		<div class="net-amount-table">
			<div class="table-header">
				<div class="col">실수령액</div>
				<div class="col">1월</div>
				<div class="col">2월</div>
				<div class="col">3월</div>
				<div class="col">4월</div>
				<div class="col">5월</div>
				<div class="col">6월</div>
				<div class="col">7월</div>
				<div class="col">8월</div>
				<div class="col">9월</div>
				<div class="col">10월</div>
				<div class="col">11월</div>
				<div class="col">12월</div>
				<div class="col">합계</div>
				<div class="col"></div>
			</div>
			<div class="form-content total-row">
				<div class="col"><span>실수령액</span></div>
				<div class="col"><span class="net-amount" data-month="1">0</span></div>
				<div class="col"><span class="net-amount" data-month="2">0</span></div>
				<div class="col"><span class="net-amount" data-month="3">0</span></div>
				<div class="col"><span class="net-amount" data-month="4">0</span></div>
				<div class="col"><span class="net-amount" data-month="5">0</span></div>
				<div class="col"><span class="net-amount" data-month="6">0</span></div>
				<div class="col"><span class="net-amount" data-month="7">0</span></div>
				<div class="col"><span class="net-amount" data-month="8">0</span></div>
				<div class="col"><span class="net-amount" data-month="9">0</span></div>
				<div class="col"><span class="net-amount" data-month="10">0</span></div>
				<div class="col"><span class="net-amount" data-month="11">0</span></div>
				<div class="col"><span class="net-amount" data-month="12">0</span></div>
				<div class="col"><span id="net_amount_grand_total">0</span></div>
				<div class="col"></div>
			</div>
		</div>
		
		<!-- 계산 결과 섹션 -->
		<div class="calculation-result">
			<div class="result-section">
				<div class="result-item">
					<div class="label">연 소득 총액</div>
					<div class="value"><span id="yearly_income">0</span>원</div>
				</div>
				<div class="result-item">
					<div class="label">연 공제 총액</div>
					<div class="value"><span id="yearly_deduction">0</span>원</div>
				</div>
				<div class="result-item">
					<div class="label">연 실수령액</div>
					<div class="value"><span id="yearly_net_amount">0</span>원</div>
				</div>
			</div>
			
			<div class="result-section">
				<div class="result-item">
					<div class="label">월평균 소득금액</div>
					<div class="value"><span id="monthly_average_income">0</span>원</div>
				</div>
				<div class="result-item">
					<div class="label">연간환산금액</div>
					<div class="value"><span id="annualized_income">0</span>원</div>
				</div>
			</div>
		</div>
		
		<!-- 버튼 섹션 -->
		<div class="button-section">
			<button type="button" id="btn_save">저장</button>
			<button type="button" id="btn_delete">삭제</button>
			<button type="button" id="btn_close">닫기</button>
		</div>
	</div>
	
	<script>
		// 전역 변수
		const caseNo = '<?php echo $case_no; ?>';
		let incomeRowCount = 1;
		let deductionRowCount = 1;
		
		// 금액 포맷팅 함수
		function formatNumber(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
		
		// 문자열에서 숫자만 추출하는 함수
		function extractNumber(str) {
			if (!str) return 0;
			return parseInt(str.replace(/[^\d]/g, '')) || 0;
		}
		
		// 행 합계 계산
		function calculateRowTotal(row) {
			let total = 0;
			$(row).find('input[data-month]').each(function() {
				total += extractNumber($(this).val());
			});
			$(row).find('.row-total').text(formatNumber(total));
			return total;
		}
		
		// 월별 합계 계산
		function calculateMonthlyTotals(container, totalClass) {
			for (let month = 1; month <= 12; month++) {
				let monthTotal = 0;
				$(`${container} input[data-month="${month}"]`).each(function() {
					monthTotal += extractNumber($(this).val());
				});
				$(`${totalClass}[data-month="${month}"]`).text(formatNumber(monthTotal));
			}
		}
		
		// 총액 계산
		function calculateGrandTotal(container, totalId) {
			let grandTotal = 0;
			$(`${container} .row-total`).each(function() {
				grandTotal += extractNumber($(this).text());
			});
			$(`#${totalId}`).text(formatNumber(grandTotal));
			return grandTotal;
		}
		
		// 실수령액 계산
		function calculateNetAmount() {
			for (let month = 1; month <= 12; month++) {
				const incomeTotal = extractNumber($(`.income-table .month-total[data-month="${month}"]`).text());
				const deductionTotal = extractNumber($(`.deduction-table .month-total[data-month="${month}"]`).text());
				const netAmount = incomeTotal - deductionTotal;
				$(`.net-amount[data-month="${month}"]`).text(formatNumber(netAmount));
			}
			
			// 총 실수령액
			const incomeGrandTotal = extractNumber($('#income_grand_total').text());
			const deductionGrandTotal = extractNumber($('#deduction_grand_total').text());
			const netAmountGrandTotal = incomeGrandTotal - deductionGrandTotal;
			$('#net_amount_grand_total').text(formatNumber(netAmountGrandTotal));
			
			// 연간 및 월평균 금액 업데이트
			$('#yearly_income').text(formatNumber(incomeGrandTotal));
			$('#yearly_deduction').text(formatNumber(deductionGrandTotal));
			$('#yearly_net_amount').text(formatNumber(netAmountGrandTotal));
			
			// 월평균 소득금액 및 연간환산금액 계산
			const calculationType = $('input[name="calculation_type"]:checked').val();
			let monthCount = 12; // 기본값
			
			if (calculationType === 'period1' || calculationType === 'period2') {
				// 입력된 데이터가 있는 월의 개수 계산
				let nonZeroMonths = 0;
				for (let month = 1; month <= 12; month++) {
					if (extractNumber($(`.net-amount[data-month="${month}"]`).text()) > 0) {
						nonZeroMonths++;
					}
				}
				monthCount = nonZeroMonths > 0 ? nonZeroMonths : 12;
			}
			
			const monthlyAverage = Math.round(netAmountGrandTotal / monthCount);
			const annualizedAmount = monthlyAverage * 12;
			
			$('#monthly_average_income').text(formatNumber(monthlyAverage));
			$('#annualized_income').text(formatNumber(annualizedAmount));
		}
		
		// 소득 행 추가
		function addIncomeRow() {
			incomeRowCount++;
			const newRow = `
				<div class="form-content" data-row-id="income_${incomeRowCount}">
					<div class="col"><input type="text" class="income-name" value="소득 항목 ${incomeRowCount}"></div>
					<div class="col"><input type="text" class="income-amount" data-month="1"></div>
					<div class="col"><input type="text" class="income-amount" data-month="2"></div>
					<div class="col"><input type="text" class="income-amount" data-month="3"></div>
					<div class="col"><input type="text" class="income-amount" data-month="4"></div>
					<div class="col"><input type="text" class="income-amount" data-month="5"></div>
					<div class="col"><input type="text" class="income-amount" data-month="6"></div>
					<div class="col"><input type="text" class="income-amount" data-month="7"></div>
					<div class="col"><input type="text" class="income-amount" data-month="8"></div>
					<div class="col"><input type="text" class="income-amount" data-month="9"></div>
					<div class="col"><input type="text" class="income-amount" data-month="10"></div>
					<div class="col"><input type="text" class="income-amount" data-month="11"></div>
					<div class="col"><input type="text" class="income-amount" data-month="12"></div>
					<div class="col"><span class="row-total">0</span></div>
					<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
				</div>
			`;
			$('#income_container').append(newRow);
			bindEvents();
		}
		
		// 공제 행 추가
		function addDeductionRow() {
			deductionRowCount++;
			const newRow = `
				<div class="form-content" data-row-id="deduction_${deductionRowCount}">
					<div class="col"><input type="text" class="deduction-name" value="공제 항목 ${deductionRowCount}"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="1"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="2"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="3"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="4"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="5"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="6"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="7"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="8"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="9"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="10"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="11"></div>
					<div class="col"><input type="text" class="deduction-amount" data-month="12"></div>
					<div class="col"><span class="row-total">0</span></div>
					<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
				</div>
			`;
			$('#deduction_container').append(newRow);
			bindEvents();
		}
		
		// 행 삭제
		function deleteRow(row) {
			$(row).remove();
			recalculateAll();
		}
		
		// 모든 계산 실행
		function recalculateAll() {
			// 행 합계 계산
			$('#income_container .form-content').each(function() {
				calculateRowTotal(this);
			});
			$('#deduction_container .form-content').each(function() {
				calculateRowTotal(this);
			});
			
			// 월별 합계 계산
			calculateMonthlyTotals('#income_container', '.income-table .month-total');
			calculateMonthlyTotals('#deduction_container', '.deduction-table .month-total');
			
			// 총액 계산
			calculateGrandTotal('#income_container', 'income_grand_total');
			calculateGrandTotal('#deduction_container', 'deduction_grand_total');
			
			// 실수령액 계산
			calculateNetAmount();
		}
		
		// 금액 입력 이벤트 처리
		function bindEvents() {
			// 금액 입력 필드 이벤트
			$('.income-amount, .deduction-amount').off('input').on('input', function() {
				// 숫자만 입력 가능하게 처리
				this.value = this.value.replace(/[^\d]/g, '');
				
				// 숫자 포맷팅
				if (this.value) {
					this.value = formatNumber(extractNumber(this.value));
				}
				
				// 다시 계산
				recalculateAll();
			});
			
			// 행 삭제 버튼 이벤트
			$('.btn-delete-row').off('click').on('click', function() {
				const row = $(this).closest('.form-content');
				deleteRow(row);
			});
		}
		
		// 저장 기능
		function saveData() {
			// 데이터 수집
			const data = {
				case_no: caseNo,
				year: $('#salary_year').val(),
				calculation_type: $('input[name="calculation_type"]:checked').val(),
				monthly_average: extractNumber($('#monthly_average_income').text()),
				yearly_amount: extractNumber($('#annualized_income').text()),
				income_rows: [],
				deduction_rows: []
			};
			
			// 소득 행 데이터 수집
			$('#income_container .form-content').each(function() {
				const rowId = $(this).data('row-id');
				const rowName = $(this).find('.income-name').val();
				const monthlyData = {};
				
				for (let month = 1; month <= 12; month++) {
					monthlyData[`month${month}`] = extractNumber($(this).find(`input[data-month="${month}"]`).val());
				}
				
				data.income_rows.push({
					id: rowId,
					name: rowName,
					monthly_data: monthlyData,
					total: extractNumber($(this).find('.row-total').text())
				});
			});
			
			// 공제 행 데이터 수집
			$('#deduction_container .form-content').each(function() {
				const rowId = $(this).data('row-id');
				const rowName = $(this).find('.deduction-name').val();
				const monthlyData = {};
				
				for (let month = 1; month <= 12; month++) {
					monthlyData[`month${month}`] = extractNumber($(this).find(`input[data-month="${month}"]`).val());
				}
				
				data.deduction_rows.push({
					id: rowId,
					name: rowName,
					monthly_data: monthlyData,
					total: extractNumber($(this).find('.row-total').text())
				});
			});
			
			// AJAX로 서버에 데이터 전송
			$.ajax({
				url: '/adm/api/application_recovery/income/save_salary_calculation.php',
				type: 'POST',
				data: JSON.stringify(data),
				contentType: 'application/json',
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						alert('월평균 소득이 저장되었습니다.');
						
						// 부모 창에 값 전달
						if (window.opener && !window.opener.closed) {
							window.opener.updateSalaryData(data.monthly_average, data.yearly_amount);
						}
					} else {
						alert(response.message || '저장 중 오류가 발생했습니다.');
					}
				},
				error: function() {
					alert('서버 통신 중 오류가 발생했습니다.');
				}
			});
		}
		
		// 데이터 로드
		function loadData() {
			$.ajax({
				url: '/adm/api/application_recovery/income/get_salary_calculation.php',
				type: 'GET',
				data: { case_no: caseNo },
				dataType: 'json',
				success: function(response) {
					if (response.success && response.data) {
						const data = response.data;
						
						// 기본 설정 로드
						$('#salary_year').val(data.year);
						$(`input[name="calculation_type"][value="${data.calculation_type}"]`).prop('checked', true);
						
						// 기존 행 제거
						$('#income_container, #deduction_container').empty();
						
						// 소득 행 로드
						if (data.income_rows && data.income_rows.length > 0) {
							data.income_rows.forEach(function(row, index) {
								incomeRowCount = Math.max(incomeRowCount, index + 1);
								
								let rowHtml = `
									<div class="form-content" data-row-id="${row.id}">
										<div class="col"><input type="text" class="income-name" value="${row.name}"></div>
								`;
								
								for (let month = 1; month <= 12; month++) {
									const amount = row.monthly_data[`month${month}`] || 0;
									rowHtml += `<div class="col"><input type="text" class="income-amount" data-month="${month}" value="${formatNumber(amount)}"></div>`;
								}
								
								rowHtml += `
										<div class="col"><span class="row-total">${formatNumber(row.total)}</span></div>
										<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
									</div>
								`;
								
								$('#income_container').append(rowHtml);
							});
						} else {
							// 기본 행 추가
							addIncomeRow();
						}
						
						// 공제 행 로드
						if (data.deduction_rows && data.deduction_rows.length > 0) {
							data.deduction_rows.forEach(function(row, index) {
								deductionRowCount = Math.max(deductionRowCount, index + 1);
								
								let rowHtml = `
									<div class="form-content" data-row-id="${row.id}">
										<div class="col"><input type="text" class="deduction-name" value="${row.name}"></div>
								`;
								
								for (let month = 1; month <= 12; month++) {
									const amount = row.monthly_data[`month${month}`] || 0;
									rowHtml += `<div class="col"><input type="text" class="deduction-amount" data-month="${month}" value="${formatNumber(amount)}"></div>`;
								}
								
								rowHtml += `
										<div class="col"><span class="row-total">${formatNumber(row.total)}</span></div>
										<div class="col"><button class="btn-delete-row" type="button">삭제</button></div>
									</div>
								`;
								
								$('#deduction_container').append(rowHtml);
							});
						} else {
							// 기본 행 추가
							addDeductionRow();
						}
						
						bindEvents();
						recalculateAll();
					}
				},
				error: function() {
					console.error('데이터 로드 중 오류가 발생했습니다.');
				}
			});
		}
		
		// 데이터 삭제
		function deleteData() {
			if (!confirm('현재 계산된 데이터를 삭제하시겠습니까?')) {
				return;
			}
			
			$.ajax({
				url: '/adm/api/application_recovery/income/delete_salary_calculation.php',
				type: 'POST',
				data: { case_no: caseNo },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						alert('데이터가 삭제되었습니다.');
						
						// 초기 상태로 리셋
						$('#income_container, #deduction_container').empty();
						addIncomeRow();
						addDeductionRow();
						recalculateAll();
						
						// 부모 창에 값 리셋
						if (window.opener && !window.opener.closed) {
							window.opener.updateSalaryData(0, 0);
						}
					} else {
						alert(response.message || '삭제 중 오류가 발생했습니다.');
					}
				},
				error: function() {
					alert('서버 통신 중 오류가 발생했습니다.');
				}
			});
		}
		
		// 초기화 및 이벤트 바인딩
		$(document).ready(function() {
			// 데이터 로드
			loadData();
			
			// 소득 행 추가 버튼
			$('#add_income_row').click(function() {
				addIncomeRow();
			});
			
			// 공제 행 추가 버튼
			$('#add_deduction_row').click(function() {
				addDeductionRow();
			});
			
			// 계산 방식 변경 이벤트
			$('input[name="calculation_type"]').change(function() {
				recalculateAll();
			});
			
			// 저장 버튼
			$('#btn_save').click(function() {
				saveData();
			});
			
			// 삭제 버튼
			$('#btn_delete').click(function() {
				deleteData();
			});
			
			// 닫기 버튼
			$('#btn_close').click(function() {
				window.close();
			});
			
			// 초기 이벤트 바인딩
			bindEvents();
		});
	</script>
</body>
</html>