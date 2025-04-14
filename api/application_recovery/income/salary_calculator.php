<?php
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
	<link rel="stylesheet" href="../../../css/salary_calculator.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
	<div class="content-wrapper">
		<div class="appendix-title">월평균소득계산기</div>
		
		<div class="section-header">
			<div class="creditor-title">
				<div class="checkbox-group">
					<span>기준 기간</span>
					<span class="separator">|</span>
					<select id="salary_year">
						<?php
						$current_year = date('Y');
						for ($i = $current_year; $i >= $current_year - 10; $i--) {
							echo "<option value=\"{$i}\">{$i}년</option>";
						}
						?>
					</select>
					<span class="separator">년</span>
					<select id="salary_month">
						<?php
						for ($i = 1; $i <= 11; $i++) {
							echo "<option value=\"{$i}\">{$i}</option>";
						}
						echo "<option value=\"12\" selected>12</option>";
						?>
					</select>
					<span class="separator">월 부터 12개월(간)</span>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<span class="separator">소득산정개월수</span>
					<select id="salary_period">
						<?php
						for ($i = 1; $i <= 11; $i++) {
							echo "<option value=\"{$i}\">{$i}</option>";
						}
						echo "<option value=\"12\" selected>12</option>";
						?>
					</select>
					<span class="separator">개월(간)으로 월평균 소득금액 및 연간환산금액 계산</span>
				</div>
			</div>
		</div>
		
		<!-- 소득내역 섹션 -->
		<div class="section-header">
			<div class="creditor-title">
				<div class="checkbox-group">
					<span>소득내역</span>
				</div>
				<div class="button-group">
					<button type="button" class="btn btn-add2" id="add_income_row">추가</button>
				</div>
			</div>
		</div>
		<div class="salary-table">
			<div class="table-header" id="income_header">
				<div class="col">소득내역</div>
				<div class="col month-header" data-month="1">1월</div>
				<div class="col month-header" data-month="2">2월</div>
				<div class="col month-header" data-month="3">3월</div>
				<div class="col month-header" data-month="4">4월</div>
				<div class="col month-header" data-month="5">5월</div>
				<div class="col month-header" data-month="6">6월</div>
				<div class="col month-header" data-month="7">7월</div>
				<div class="col month-header" data-month="8">8월</div>
				<div class="col month-header" data-month="9">9월</div>
				<div class="col month-header" data-month="10">10월</div>
				<div class="col month-header" data-month="11">11월</div>
				<div class="col month-header" data-month="12">12월</div>
				<div class="col">합계</div>
				<div class="col">삭제</div>
			</div>
			<div id="income_container">
				<!-- 기본 소득 행 -->
				<div class="form-content" data-row-id="income_1">
					<div class="col"><input type="text" class="income-name" value="" placeholder=""></div>
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
				<div class="col"></div>
			</div>
		</div>
		
		<!-- 공제내역 섹션 -->
		<div class="section-header">
			<div class="creditor-title">
				<div class="checkbox-group">
					<span>공제내역</span>
				</div>
				<div class="button-group">
					<button type="button" class="btn btn-add2" id="add_deduction_row">추가</button>
				</div>
			</div>
		</div>
		<div class="deduction-table">
			<div class="table-header" id="deduction_header">
				<div class="col">공제내역</div>
				<div class="col month-header" data-month="1">1월</div>
				<div class="col month-header" data-month="2">2월</div>
				<div class="col month-header" data-month="3">3월</div>
				<div class="col month-header" data-month="4">4월</div>
				<div class="col month-header" data-month="5">5월</div>
				<div class="col month-header" data-month="6">6월</div>
				<div class="col month-header" data-month="7">7월</div>
				<div class="col month-header" data-month="8">8월</div>
				<div class="col month-header" data-month="9">9월</div>
				<div class="col month-header" data-month="10">10월</div>
				<div class="col month-header" data-month="11">11월</div>
				<div class="col month-header" data-month="12">12월</div>
				<div class="col">합계</div>
				<div class="col">삭제</div>
			</div>
			<div id="deduction_container">
				<!-- 기본 공제 행 -->
				<div class="form-content" data-row-id="deduction_1">
					<div class="col"><input type="text" class="deduction-name" value="" placeholder=""></div>
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
				<div class="col"></div>
			</div>
		</div>
		
		<!-- 실수령액 섹션 -->
		<div class="section-header">
			<div class="creditor-title">
				<div class="checkbox-group">
					<span>실수령액</span>
				</div>
			</div>
		</div>
		<div class="net-amount-table">
			<div class="table-header" id="net_amount_header">
				<div class="col">실수령액</div>
				<div class="col month-header" data-month="1">1월</div>
				<div class="col month-header" data-month="2">2월</div>
				<div class="col month-header" data-month="3">3월</div>
				<div class="col month-header" data-month="4">4월</div>
				<div class="col month-header" data-month="5">5월</div>
				<div class="col month-header" data-month="6">6월</div>
				<div class="col month-header" data-month="7">7월</div>
				<div class="col month-header" data-month="8">8월</div>
				<div class="col month-header" data-month="9">9월</div>
				<div class="col month-header" data-month="10">10월</div>
				<div class="col month-header" data-month="11">11월</div>
				<div class="col month-header" data-month="12">12월</div>
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
		
		<!-- 계산 옵션 섹션 삭제 -->
		
		<!-- 계산 결과 섹션 -->
		<div class="calculation-result">
		  <div class="left-section">
			<div class="form">
			  <div class="form-title"><span>연 소득 총액 ①</span></div>
			  <div class="form-content">
				<span id="yearly_income">0</span>원
			  </div>
			</div>
			<div class="form">
			  <div class="form-title"><span>연 공제 총액 ②</span></div>
			  <div class="form-content">
				<span id="yearly_deduction">0</span>원
			  </div>
			</div>
			<div class="form">
			  <div class="form-title"><span>연 실수령액 (①-②)</span></div>
			  <div class="form-content">
				<span id="yearly_net_amount">0</span>원
			  </div>
			</div>
		  </div>
		  <div class="right-section">
			<div class="form">
			  <div class="form-title"><span>월평균 소득금액</span></div>
			  <div class="form-content">
				<span id="monthly_average_income">0</span>원
			  </div>
			</div>
			<div class="form">
			  <div class="form-title"><span>연간환산금액</span></div>
			  <div class="form-content">
				<span id="annualized_income">0</span>원
			  </div>
			</div>
			<div class="form">
			  <div class="form-title"></div>
			  <div class="form-content btn-right">
				<button type="button" id="btn_close">닫기</button>
				<button type="button" id="btn_delete">삭제</button>
				<button type="button" id="btn_save">저장</button>
			  </div>
			</div>
		  </div>
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
				// 비활성화된 필드도 포함하여 계산
				if (!$(this).prop('disabled') || $(this).data('include-disabled')) {
					total += extractNumber($(this).val());
				}
			});
			$(row).find('.row-total').text(formatNumber(total));
			return total;
		}
		
		// 월별 합계 계산 - 수정됨
		function calculateMonthlyTotals() {
			// 소득 합계 계산
			for (let month = 1; month <= 12; month++) {
				let incomeTotal = 0;
				$('#income_container input[data-month="' + month + '"]').each(function() {
					// 비활성화된 필드도 포함하여 계산
					if (!$(this).prop('disabled') || $(this).data('include-disabled')) {
						incomeTotal += extractNumber($(this).val());
					}
				});
				// 소득합계 업데이트 - 수정된 부분
				$('.month-total[data-month="' + month + '"]').first().text(formatNumber(incomeTotal));
				
				// 공제 합계 계산
				let deductionTotal = 0;
				$('#deduction_container input[data-month="' + month + '"]').each(function() {
					// 비활성화된 필드도 포함하여 계산
					if (!$(this).prop('disabled') || $(this).data('include-disabled')) {
						deductionTotal += extractNumber($(this).val());
					}
				});
				// 공제합계 업데이트 - 수정된 부분
				$('.deduction-table .month-total[data-month="' + month + '"]').text(formatNumber(deductionTotal));
				
				// 실수령액 계산
				const netAmount = incomeTotal - deductionTotal;
				$(`.net-amount[data-month="${month}"]`).text(formatNumber(netAmount));
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
			let netAmountGrandTotal = 0;
			
			for (let month = 1; month <= 12; month++) {
				// 수정된 부분 - 올바른 선택자 사용
				const incomeTotal = extractNumber($('.month-total[data-month="' + month + '"]').first().text());
				const deductionTotal = extractNumber($('.deduction-table .month-total[data-month="' + month + '"]').text());
				const netAmount = incomeTotal - deductionTotal;
				$(`.net-amount[data-month="${month}"]`).text(formatNumber(netAmount));
				
				// 총 실수령액에 더함
				netAmountGrandTotal += netAmount;
			}
			
			$('#net_amount_grand_total').text(formatNumber(netAmountGrandTotal));
			
			// 소득 및 공제 총액
			const incomeGrandTotal = extractNumber($('#income_grand_total').text());
			const deductionGrandTotal = extractNumber($('#deduction_grand_total').text());
			
			// 연간 및 월평균 금액 업데이트
			$('#yearly_income').text(formatNumber(incomeGrandTotal));
			$('#yearly_deduction').text(formatNumber(deductionGrandTotal));
			$('#yearly_net_amount').text(formatNumber(netAmountGrandTotal));
			
			// 월평균 소득금액 및 연간환산금액 계산
			// salary_period 값 사용 (사용자가 지정한 개월수)
			const monthCount = parseInt($('#salary_period').val()) || 12;
			
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
					<div class="col"><input type="text" class="income-name" value=""></div>
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
			
			// 기준월 및 소득산정개월수 설정 적용
			applyMonthDisplay();
			applyPeriodLimitation();
			
			// 이벤트 바인딩
			bindEvents();
		}
		
		// 공제 행 추가
		function addDeductionRow() {
			deductionRowCount++;
			const newRow = `
				<div class="form-content" data-row-id="deduction_${deductionRowCount}">
					<div class="col"><input type="text" class="deduction-name" value=""></div>
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
			
			// 기준월 및 소득산정개월수 설정 적용
			applyMonthDisplay();
			applyPeriodLimitation();
			
			// 이벤트 바인딩
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
			calculateMonthlyTotals();
			
			// 총액 계산
			calculateGrandTotal('#income_container', 'income_grand_total');
			calculateGrandTotal('#deduction_container', 'deduction_grand_total');
			
			// 실수령액 계산
			calculateNetAmount();
		}
		
		// 기준월에 따른 월 표시 조정 (1월 -> n월 등)
		function applyMonthDisplay() {
			const startMonth = parseInt($('#salary_month').val()) || 1;
			
			// 각 월 헤더 업데이트
			for (let i = 1; i <= 12; i++) {
				const displayMonth = ((startMonth - 1 + i - 1) % 12) + 1;
				$(`.month-header[data-month="${i}"]`).text(displayMonth + '월');
			}
		}
		
		// 소득산정개월수에 따른 입력 제한
		function applyPeriodLimitation() {
			const periodMonths = parseInt($('#salary_period').val()) || 12;
			
			// 모든 입력 필드 초기화 (활성화)
			$('.income-amount, .deduction-amount').prop('disabled', false).css('background-color', '');
			
			// 소득산정개월수에 따라 입력 필드 제한
			if (periodMonths < 12) {
				// 소득 테이블 처리
				$('#income_container .form-content').each(function() {
					for (let month = 1; month <= 12; month++) {
						if (month > periodMonths) {
							$(this).find(`input[data-month="${month}"]`)
								.prop('disabled', true)
								.css('background-color', '#f0f0f0')
								.data('include-disabled', false)
								.val(''); // 값도 비움
						}
					}
				});
				
				// 공제 테이블 처리
				$('#deduction_container .form-content').each(function() {
					for (let month = 1; month <= 12; month++) {
						if (month > periodMonths) {
							$(this).find(`input[data-month="${month}"]`)
								.prop('disabled', true)
								.css('background-color', '#f0f0f0')
								.data('include-disabled', false)
								.val(''); // 값도 비움
						}
					}
				});
			}
			
			// 다시 계산 실행
			recalculateAll();
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
				month: $('#salary_month').val(),
				period: $('#salary_period').val(),
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
					const inputField = $(this).find(`input[data-month="${month}"]`);
					// disabled된 필드도 포함하여 저장
					monthlyData[`month${month}`] = extractNumber(inputField.val());
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
					const inputField = $(this).find(`input[data-month="${month}"]`);
					// disabled된 필드도 포함하여 저장
					monthlyData[`month${month}`] = extractNumber(inputField.val());
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
						alert(response.message+response.error || '저장 중 오류가 발생했습니다.');
					}
				},
				error: function(xhr, status, error) {
					// 더 자세한 오류 정보 출력
					console.error('데이터 삭제 중 오류가 발생했습니다.');
					console.error('상태 코드:', xhr.status);
					console.error('오류 유형:', status);
					console.error('오류 메시지:', error);
					
					// 응답 내용 확인
					try {
						const responseJson = JSON.parse(xhr.responseText);
						console.error('서버 응답:', responseJson);
						if (responseJson.error) {
							console.error('서버 오류 상세:', responseJson.error);
							alert('삭제 오류: ' + responseJson.error);
						} else {
							alert('서버 통신 중 오류가 발생했습니다.');
						}
					} catch (e) {
						console.error('응답 내용:', xhr.responseText);
						alert('서버 통신 중 오류가 발생했습니다.');
					}
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
						$('#salary_month').val(data.month);
						$('#salary_period').val(data.period);
						
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
						
						// 기준월 및 소득산정개월수 설정 적용
						applyMonthDisplay();
						applyPeriodLimitation();
						
						bindEvents();
						recalculateAll();
					}
				},
				error: function(xhr, status, error) {
					// 더 자세한 오류 정보 출력
					console.error('데이터 로드 중 오류가 발생했습니다.');
					console.error('상태 코드:', xhr.status);
					console.error('오류 메시지:', error);
					
					// 응답 내용 확인
					try {
						const responseJson = JSON.parse(xhr.responseText);
						console.error('서버 응답:', responseJson);
						if (responseJson.error) {
							console.error('서버 오류 상세:', responseJson.error);
						}
					} catch (e) {
						console.error('응답 내용:', xhr.responseText);
					}
					
					// 기본 행 추가
					$('#income_container, #deduction_container').empty();
					addIncomeRow();
					addDeductionRow();
					bindEvents();
					recalculateAll();
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
				error: function(xhr, status, error) {
					// 더 자세한 오류 정보 출력
					console.error('데이터 삭제 중 오류가 발생했습니다.');
					console.error('상태 코드:', xhr.status);
					console.error('오류 유형:', status);
					console.error('오류 메시지:', error);
					
					// 응답 내용 확인
					try {
						const responseJson = JSON.parse(xhr.responseText);
						console.error('서버 응답:', responseJson);
						if (responseJson.error) {
							console.error('서버 오류 상세:', responseJson.error);
							alert('삭제 오류: ' + responseJson.error);
						} else {
							alert('서버 통신 중 오류가 발생했습니다.');
						}
					} catch (e) {
						console.error('응답 내용:', xhr.responseText);
						alert('서버 통신 중 오류가 발생했습니다.');
					}
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
			
			// 기준 월 변경 이벤트
			$('#salary_month').change(function() {
				applyMonthDisplay();
				recalculateAll();
			});
			
			// 소득산정개월수 변경 이벤트 추가
			$('#salary_period').change(function() {
				applyPeriodLimitation();
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
			
			// 기준월 및 소득산정개월수 설정 적용
			applyMonthDisplay();
			applyPeriodLimitation();
		});
	</script>
</body>
</html>