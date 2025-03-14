// 전역 변수 및 유틸리티 함수
let charts = {
	recoveryTrend: null,
	bankruptcyTrend: null,
	recoveryRates: null,
	bankruptcyRates: null,
	weeklyTrend: null,
};

// 디바운스 함수 정의
function debounce(func, wait) {
	let timeout;
	return function(...args) {
		clearTimeout(timeout);
		timeout = setTimeout(() => func(...args), wait);
	};
}

// vw, vh를 픽셀로 변환하는 유틸리티 함수
function vwToPx(vw) {
	return Math.round((window.innerWidth * vw) / 100);
}

function vhToPx(vh) {
	return Math.round((window.innerHeight * vh) / 100);
}

// 데이터 포맷팅 유틸리티 함수
const formatUtils = {
	// 숫자 포맷팅 (천단위 구분기호)
	formatNumber: (number) => number.toLocaleString('ko-KR'),
	
	// 날짜 포맷팅
	formatDate: (dateString) => {
		const date = new Date(dateString);
		return new Intl.DateTimeFormat('ko-KR', {
			year: 'numeric',
			month: 'long',
			day: 'numeric'
		}).format(date);
	}
};

// 차트 컬러 테마 설정
const chartColors = {
	primary: '#00e6c3',
	secondary: '#b5b5b5',
	background: '#ffffff',
	grid: '#f0f0f0',
	border: '#b5b5b5',
	text: '#000000'
};

// 문서 로드 완료 시 초기화
$(document).ready(initializeApp);

function initializeApp() {
	// 이벤트 리스너 설정
	setupEventListeners();
	
	// 차트 전역 설정
	setupGlobalChartSettings();
	
	// 초기 페이지 로드 시 기본 탭 내용 표시
	loadInitialTabContent();
	
	// 윈도우 리사이즈 이벤트 처리
	$(window).resize(debounce(handleWindowResize, 250));
	
	// 테이블 정렬 기능 활성화
	enableTableSort();
	
	// 날짜 필터 드롭다운 초기화
	initDateFilterDropdown();
}

// 이벤트 리스너 설정
function setupEventListeners() {
	// 탭 클릭 이벤트 핸들러
	$('.stat-tab').click(handleTabClick);
	
	// 사무장 탭 클릭 이벤트 처리
	$('#managerTab').click(handleManagerTabClick);
	
	// 드롭다운 옵션 클릭 이벤트
	$('.dropdown-option').click(handleDropdownOptionClick);
	
	// 다른 곳 클릭 시 드롭다운 닫기
	$(document).click(function(e) {
		if(!$(e.target).closest('#managerTab, #managerDropdown').length) {
			$('#managerDropdown').hide();
		}
	});
	
	// 열에 마우스 올릴 때 효과 적용
	$(document).on('mouseenter', '.manager-column, .manager-stats', handleColumnHover);
	
	// 마우스가 떠날 때 스타일 복원
	$(document).on('mouseleave', '.manager-column, .manager-stats', handleColumnUnhover);
}

// 전역 차트 설정
function setupGlobalChartSettings() {
	Chart.defaults.set('plugins.tooltip.backgroundColor', 'rgba(255, 255, 255, 0.9)');
	Chart.defaults.set('plugins.tooltip.titleColor', chartColors.text);
	Chart.defaults.set('plugins.tooltip.bodyColor', chartColors.text);
	Chart.defaults.set('plugins.tooltip.borderColor', chartColors.border);
	Chart.defaults.set('plugins.tooltip.borderWidth', 1);
}

// 초기 탭 내용 로드
function loadInitialTabContent() {
	// 기본값으로 일간 통계 옵션 설정
	$('.dropdown-option[data-stat-type="daily"]').addClass('active');
	
	// 초기 페이지 로드 시 기본 탭 내용만 표시
	const activeTabType = $('.stat-tab.active').data('type');
	
	// 모든 통계 컨텐츠 영역 숨김
	$('#bankruptcyStats, #caseStats, #managerDailyStats, #managerWeeklyStats, #managerMonthlyStats, #documentStats').hide();
	
	// 활성 탭에 해당하는 컨텐츠만 표시
	$(`#${activeTabType}Stats`).show();
	
	// 활성 탭에 맞는 데이터 로드
	if(activeTabType === 'case') {
		loadManagersData();
	} else if(activeTabType === 'bankruptcy') {
		loadAllStats();
	} else if(activeTabType === 'manager') {
		loadManagerDailyStats();
		initWeekFilterDropdown();
	}
}

// 윈도우 리사이즈 핸들러
function handleWindowResize() {
	if($('.stat-tab.active').data('type') === 'bankruptcy') {
		loadAllStats();
	}
}

// 탭 클릭 핸들러
function handleTabClick() {
	// 사무장 탭이면 드롭다운만 표시하고 return
	if($(this).attr('id') === 'managerTab') {
		return;
	}
	
	// 모든 탭에서 active 클래스 제거 후 현재 탭에만 추가
	$('.stat-tab').removeClass('active');
	$(this).addClass('active');
	
	// 모든 통계 컨텐츠 영역 숨김
	$('#bankruptcyStats, #caseStats, #managerDailyStats, #managerWeeklyStats, #managerMonthlyStats, #documentStats').hide();
	
	// 클릭한 탭에 해당하는 컨텐츠만 표시
	const type = $(this).data('type');
	$(`#${type}Stats`).show();
	
	// 각 탭에 맞는 데이터 로드
	if(type === 'case') {
		loadManagersData();
	} else if(type === 'bankruptcy') {
		loadAllStats();
	}
}

// 사무장 탭 클릭 핸들러
function handleManagerTabClick(e) {
	e.stopPropagation(); // 상위 이벤트 전파 방지
	
	// 드롭다운 위치 설정
	const tabPosition = $(this).offset();
	const tabWidth = $(this).outerWidth();
	const tabHeight = $(this).outerHeight();
	
	$('#managerDropdown').css({
		top: tabPosition.top + tabHeight - 40 + 'px',
		left: tabPosition.left + tabWidth - 110 + 'px'
	}).toggle();
}

// 드롭다운 옵션 클릭 핸들러
function handleDropdownOptionClick() {
	// 모든 통계 컨텐츠 영역 숨김
	$('#bankruptcyStats, #caseStats, #managerDailyStats, #managerWeeklyStats, #managerMonthlyStats, #documentStats').hide();
	
	// 드롭다운 메뉴 닫기
	$('#managerDropdown').hide();
	
	// 모든 탭에서 active 클래스 제거 후 사무장 탭에만 추가
	$('.stat-tab').removeClass('active');
	$('#managerTab').addClass('active');
	
	// 클릭한 옵션에 active 클래스 추가
	$('.dropdown-option').removeClass('active');
	$(this).addClass('active');
	
	// 클릭한 옵션에 따라 컨텐츠 표시
	const statType = $(this).data('stat-type');
	
	if(statType === 'daily') {
		$('#managerDailyStats').show();
		loadManagerDailyStats();
	} else if(statType === 'weekly') {
		$('#managerWeeklyStats').show();
		loadManagerWeeklyStats();
	} else if(statType === 'monthly') {
		$('#managerMonthlyStats').show();
		// 월간 통계 로드 함수 (필요시 구현)
		// loadManagerMonthlyStats();
	}
}

// 컬럼 호버 효과 핸들러
function handleColumnHover() {
	let columnIndex = $(this).index();
	
	// 헤더의 같은 인덱스 열의 배경색 변경
	$('.manager-stats-header .manager-column').eq(columnIndex).css('background-color', '#6c6c6c');
	
	// 바디의 모든 행에서 같은 인덱스 열의 배경색 변경
	$('.manager-stats-body .stats-row').each(function() {
		$(this).find('.manager-stats').eq(columnIndex).css('background-color', '#f9f9f9');
	});
	
	// 푸터의 해당 열 배경색 변경
	$('.manager-stats-footer .manager-column').eq(columnIndex).css('background-color', '#6c6c6c');
}

// 컬럼 언호버 효과 핸들러
function handleColumnUnhover() {
	let columnIndex = $(this).index();
	
	// 헤더 스타일 복원 (마지막 열은 원래 #6c6c6c)
	if(columnIndex === $('.manager-stats-header .manager-column').length - 1) {
		$('.manager-stats-header .manager-column').eq(columnIndex).css('background-color', '#6c6c6c');
	} else {
		$('.manager-stats-header .manager-column').eq(columnIndex).css('background-color', '#b0b0b0');
	}
	
	// 바디 스타일 복원
	$('.manager-stats-body .stats-row').each(function() {
		$(this).find('.manager-stats').eq(columnIndex).css('background-color', '');
	});
	
	// 푸터 스타일 복원 (마지막 열은 원래 #6c6c6c)
	if(columnIndex === $('.manager-stats-footer .manager-column').length - 1) {
		$('.manager-stats-footer .manager-column').eq(columnIndex).css('background-color', '#6c6c6c');
	} else {
		$('.manager-stats-footer .manager-column').eq(columnIndex).css('background-color', '#b0b0b0');
	}
}

// 날짜 필터 드롭다운 초기화 함수
function initDateFilterDropdown() {
	// 현재 년도와 월 구하기
	const currentDate = new Date();
	const currentYear = currentDate.getFullYear();
	const currentMonth = currentDate.getMonth() + 1;
	const startYear = currentYear - 20;
	
	// 연도 옵션 생성
	const yearSection = $('#dateFilterDropdown .dropdown-section').first().find('.dropdown-scroll');
	yearSection.empty();
	
	for (let year = currentYear; year >= startYear; year--) {
		const isCurrentYear = year === currentYear;
		yearSection.append(`
			<div class="dropdown-option year-option ${isCurrentYear ? 'selected' : ''}" data-year="${year}">
				${year}년
			</div>
		`);
	}
	
	// 월 옵션 생성
	const monthSection = $('#dateFilterDropdown .dropdown-section').last().find('.dropdown-scroll');
	monthSection.empty();
	
	for (let month = 1; month <= 12; month++) {
		const isCurrentMonth = month === currentMonth;
		const paddedMonth = month.toString().padStart(2, '0');
		monthSection.append(`
			<div class="dropdown-option month-option ${isCurrentMonth ? 'selected' : ''}" data-month="${paddedMonth}">
				${month}월
			</div>
		`);
	}
	
	// 날짜 컬럼 클릭 이벤트
	$(document).on('click', '.date-column', handleDateColumnClick);
	
	// 연도 선택 이벤트
	$(document).on('click', '.year-option', handleYearOptionClick);
	
	// 월 선택 이벤트
	$(document).on('click', '.month-option', handleMonthOptionClick);
	
	// 적용 버튼 클릭 이벤트
	$(document).on('click', '.apply-button', handleApplyButtonClick);
	
	// 초기화 버튼 클릭 이벤트
	$(document).on('click', '.reset-button', handleResetButtonClick);
	
	// 다른 곳 클릭 시 드롭다운 닫기
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#dateFilterDropdown, .date-column').length) {
			$('#dateFilterDropdown').hide();
		}
	});
}

// 주간 필터 드롭다운 초기화 함수
function initWeekFilterDropdown() {
	// 현재 년도와 월 구하기
	const currentDate = new Date();
	const currentYear = currentDate.getFullYear();
	const currentMonth = currentDate.getMonth() + 1;
	const startYear = currentYear - 5;
	
	// 연도 옵션 생성
	const yearSection = $('#weekFilterDropdown .dropdown-section').first().find('.dropdown-scroll');
	yearSection.empty();
	
	for (let year = currentYear; year >= startYear; year--) {
		const isCurrentYear = year === currentYear;
		yearSection.append(`
			<div class="dropdown-option year-option ${isCurrentYear ? 'selected' : ''}" data-year="${year}">
				${year}년
			</div>
		`);
	}
	
	// 월 옵션 생성
	const monthSection = $('#weekFilterDropdown .dropdown-section').last().find('.dropdown-scroll');
	monthSection.empty();
	
	for (let month = 1; month <= 12; month++) {
		const isCurrentMonth = month === currentMonth;
		const paddedMonth = month.toString().padStart(2, '0');
		monthSection.append(`
			<div class="dropdown-option month-option ${isCurrentMonth ? 'selected' : ''}" data-month="${paddedMonth}">
				${month}월
			</div>
		`);
	}
	
	// 주간 날짜 컬럼 클릭 이벤트
	$(document).on('click', '.weekly-stats-header .date-column', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		// 모든 드롭다운 숨기기
		$('#weekFilterDropdown').hide();
		
		// 현재 클릭된 컬럼의 위치 계산
		const $this = $(this);
		const headerPosition = $this.offset();
		const headerHeight = $this.outerHeight();
		
		// 드롭다운 위치 설정
		$('#weekFilterDropdown').css({
			display: 'block',
			position: 'absolute',
			top: (headerPosition.top + headerHeight) + 'px',
			left: headerPosition.left + 'px',
			zIndex: 1000
		});
	});
	
	// 연도 선택 이벤트
	$(document).on('click', '#weekFilterDropdown .year-option', function() {
		$('#weekFilterDropdown .year-option').removeClass('selected');
		$(this).addClass('selected');
	});
	
	// 월 선택 이벤트
	$(document).on('click', '#weekFilterDropdown .month-option', function() {
		$('#weekFilterDropdown .month-option').removeClass('selected');
		$(this).addClass('selected');
	});
	
	// 적용 버튼 클릭 이벤트
	$(document).on('click', '#weekFilterDropdown .apply-button', function() {
		const selectedYear = $('#weekFilterDropdown .year-option.selected').data('year');
		const selectedMonth = $('#weekFilterDropdown .month-option.selected').data('month');
		
		if (selectedYear && selectedMonth) {
			// 선택된 년도와 월로 데이터 로드
			loadFilteredManagerWeeklyStats(selectedYear, selectedMonth);
			$('#weekFilterDropdown').hide();
		} else {
			alert('연도와 월을 모두 선택해주세요.');
		}
	});
	
	// 초기화 버튼 클릭 이벤트
	$(document).on('click', '#weekFilterDropdown .reset-button', function() {
		// 모든 선택 해제
		$('#weekFilterDropdown .dropdown-option').removeClass('selected');
		
		// 현재 년도와 월 다시 선택
		$(`#weekFilterDropdown .year-option[data-year="${currentYear}"]`).addClass('selected');
		$(`#weekFilterDropdown .month-option[data-month="${currentMonth.toString().padStart(2, '0')}"]`).addClass('selected');
		
		// 현재 월 데이터 로드
		loadFilteredManagerWeeklyStats(currentYear, currentMonth);
		$('#weekFilterDropdown').hide();
	});
}

// 필터링된 사무장 주간 통계 로드 함수
function loadFilteredManagerWeeklyStats(year, month) {
	const formattedMonth = month.toString().padStart(2, '0');

	$.ajax({
		url: '../adm/api/stats/get_manager_weekly_stats.php',
		method: 'GET',
		data: {
			year: year,
			month: formattedMonth
		},
		dataType: 'json',
		success: function(response) {
			if(response.success) {
				// 헤더 업데이트 - "주간 통계 ▼"로 표시
				$('.weekly-stats-header .date-column').html(`주간 통계&nbsp;&nbsp;<span class="sort-icon date-dropdown-toggle">▼</span>`);
				
				// 월 정보 추가
				const monthInfo = `${year}. ${month}월`;
				
				// 렌더링 함수 호출 시 월 정보 전달
				renderManagerWeeklyStats(response.data, response.managers, monthInfo);
				renderWeeklyTrendChart(response.trend);
			} else {
				console.error('통계 로드 실패:', response);
				alert('통계 데이터를 불러올 수 없습니다: ' + response.message);
			}
		},
		error: function(xhr, status, error) {
			try {
				const errorResponse = JSON.parse(xhr.responseText);
				console.error('서버 오류 상세 정보:', errorResponse);
				alert('서버 오류: ' + errorResponse.message);
			} catch(e) {
				console.error('AJAX 오류:', status, error);
				console.error('원시 응답:', xhr.responseText);
				alert('데이터를 불러오는 중 심각한 오류가 발생했습니다.');
			}
		}
	});
}

// 사무장 주간 통계 로드 함수
function loadManagerWeeklyStats() {
	// 현재 연도와 월 구하기
	const currentDate = new Date();
	const currentYear = currentDate.getFullYear();
	const currentMonth = currentDate.getMonth() + 1;
	
	// 초기 로드 시 현재 연도와 월 데이터 사용
	loadFilteredManagerWeeklyStats(currentYear, currentMonth);
}

// 주간 통계 데이터 렌더링 함수
function renderManagerWeeklyStats(weeklyData, managers, monthInfo) {
	const statsBody = $('#managerWeeklyStatsBody');
	statsBody.empty();

	// HTML 테이블 생성
	let tableHtml = '<table class="weekly-stats-table">';
	
	// 테이블 헤더
	tableHtml += '<thead><tr>';
	tableHtml += '<th>주간 통계 ▼</th>'; // 여기를 "주간 통계 ▼"로 변경
	tableHtml += '<th>상담건수</th>';
	tableHtml += '<th>계약체결건수</th>';
	tableHtml += '<th>계약체결률</th>';
	tableHtml += '</tr></thead>';
	
	// 테이블 바디
	tableHtml += '<tbody>';
	
	// 월별 합계 계산을 위한 변수 초기화
	let monthlyTotals = {
		inflow: 0,
		contract: 0
	};
	
	// 각 주차별 데이터
	weeklyData.forEach(item => {
		tableHtml += '<tr>';
		// 주차 정보에 월 정보 포함하여 표시
		tableHtml += `<td>${monthInfo} ${item.week}주차<br>(${item.date_range})</td>`;
		
		// 상담건수(유입)
		tableHtml += `<td>${item.total.inflow}</td>`;
		
		// 계약체결건수
		tableHtml += `<td>${item.total.contract}</td>`;
		
		// 계약체결률 계산
		const contractRate = item.total.inflow > 0 ? 
			Math.round((item.total.contract / item.total.inflow) * 100) : 0;
		tableHtml += `<td>${contractRate}%</td>`;
		
		// 월 합계에 추가
		monthlyTotals.inflow += item.total.inflow;
		monthlyTotals.contract += item.total.contract;
		
		tableHtml += '</tr>';
	});
	
	tableHtml += '</tbody></table>';
	
	// 테이블 렌더링
	statsBody.html(tableHtml);
	
	// 푸터 업데이트 (월간 합계)
	updateWeeklyStatsFooter(monthlyTotals);
}

// 주간 통계 푸터 업데이트 함수
function updateWeeklyStatsFooter(monthlyTotals) {
	const footer = $('.weekly-stats-footer');
	footer.empty();
	
	// 월 전체 계약체결률 계산
	const monthlyContractRate = monthlyTotals.inflow > 0 ? 
		Math.round((monthlyTotals.contract / monthlyTotals.inflow) * 100) : 0;
	
	let footerHtml = '<table>';
	footerHtml += '<tr>';
	footerHtml += '<th>월별 합계</th>';
	footerHtml += `<th>${monthlyTotals.inflow}</th>`;
	footerHtml += `<th>${monthlyTotals.contract}</th>`;
	footerHtml += `<th>${monthlyContractRate}%</th>`;
	footerHtml += '</tr>';
	footerHtml += '</table>';
	
	footer.html(footerHtml);
}

// 날짜 컬럼 클릭 핸들러
function handleDateColumnClick(e) {
	e.preventDefault();
	e.stopPropagation();
	
	// 모든 드롭다운 숨기기
	$('#dateFilterDropdown').hide();
	
	// 현재 클릭된 컬럼의 위치 계산
	const $this = $(this);
	const headerPosition = $this.offset();
	const headerHeight = $this.outerHeight();
	
	// 드롭다운 위치 설정
	$('#dateFilterDropdown').css({
		display: 'block',
		position: 'absolute',
		top: (headerPosition.top + headerHeight) + 'px',
		left: headerPosition.left + 'px',
		zIndex: 1000
	});
}

// 연도 옵션 클릭 핸들러
function handleYearOptionClick() {
	$('.year-option').removeClass('selected');
	$(this).addClass('selected');
}

// 월 옵션 클릭 핸들러
function handleMonthOptionClick() {
	$('.month-option').removeClass('selected');
	$(this).addClass('selected');
}

// 적용 버튼 클릭 핸들러
function handleApplyButtonClick() {
	const selectedYear = $('.year-option.selected').data('year');
	const selectedMonth = $('.month-option.selected').data('month');
	
	if (selectedYear && selectedMonth) {
		// 선택된 년도와 월로 데이터 로드
		loadFilteredManagerDailyStats(selectedYear, selectedMonth);
		$('#dateFilterDropdown').hide();
	} else {
		alert('연도와 월을 모두 선택해주세요.');
	}
}

// 초기화 버튼 클릭 핸들러
function handleResetButtonClick() {
	const currentDate = new Date();
	const currentYear = currentDate.getFullYear();
	const currentMonth = currentDate.getMonth() + 1;
	
	// 모든 선택 해제
	$('.dropdown-option').removeClass('selected');
	
	// 현재 년도와 월 다시 선택
	$(`.year-option[data-year="${currentYear}"]`).addClass('selected');
	$(`.month-option[data-month="${currentMonth.toString().padStart(2, '0')}"]`).addClass('selected');
	
	// 현재 월 데이터 로드
	loadFilteredManagerDailyStats(currentYear, currentMonth);
	$('#dateFilterDropdown').hide();
}

// 필터링된 사무장 일간 통계 로드 함수
function loadFilteredManagerDailyStats(year, month) {
	const formattedMonth = month.toString().padStart(2, '0');

	$.ajax({
		url: '../adm/api/stats/get_manager_daily_stats.php',
		method: 'GET',
		data: {
			year: year,
			month: formattedMonth
		},
		dataType: 'json',
		success: function(response) {
			if(response.success) {
				$('.date-column').html(`${year}. ${month}월 상담 통계&nbsp;&nbsp;<span class="sort-icon date-dropdown-toggle">▼</span>`);
				renderManagerDailyStats(response.data);
			} else {
				console.error('통계 로드 실패:', response);
				alert('통계 데이터를 불러올 수 없습니다: ' + response.message);
			}
		},
		error: function(xhr, status, error) {
			handleAjaxError(xhr, status, error);
		}
	});
}

// AJAX 에러 핸들러
function handleAjaxError(xhr, status, error) {
	try {
		const errorResponse = JSON.parse(xhr.responseText);
		console.error('서버 오류 상세 정보:', errorResponse);
		alert('서버 오류: ' + errorResponse.message);
	} catch(e) {
		console.error('AJAX 오류:', status, error);
		console.error('원시 응답:', xhr.responseText);
		alert('데이터를 불러오는 중 심각한 오류가 발생했습니다.');
	}
}

// 사무장 일별 통계 데이터 로드 함수
function loadManagerDailyStats() {
	// 현재 연도와 월 구하기
	const currentDate = new Date();
	const currentYear = currentDate.getFullYear();
	const currentMonth = currentDate.getMonth() + 1; // JavaScript의 month는 0부터 시작
	
	// 초기 로드 시 현재 연도와 월 데이터 사용
	loadFilteredManagerDailyStats(currentYear, currentMonth);
}

// 사무장 일별 통계 렌더링 함수
function renderManagerDailyStats(data) {
	const statsBody = $('#managerDailyStatsBody');
	statsBody.empty();
	
	// 관리자 계정 정보 가져오기(API 호출)
	$.ajax({
		url: '../adm/api/stats/get_manager_stats.php',
		method: 'GET',
		dataType: 'json',
		async: false,
		success: function(response) {
			if(response.success) {
				// 사무장 수에 따라 컨테이너 클래스 설정
				const managerCount = response.data.length;
				const statsContainer = $('.manager-stats-container');
				
				if (managerCount > 6) {
					statsContainer.removeClass('has-few-managers').addClass('has-many-managers');
				} else {
					statsContainer.removeClass('has-many-managers').addClass('has-few-managers');
				}
				
				// 헤더 섹션 업데이트
				updatemanagerDailyStatsHeader(response.data);
				
				// 이제 일별 통계 데이터 표시
				renderDailyStatsData(data, response.data);
				
				// 각 스크롤 영역 동기화를 위한 이벤트 처리
				$('.managers-scroll-area').on('scroll', function() {
					const scrollLeft = $(this).scrollLeft();
					$('.managers-scroll-area').scrollLeft(scrollLeft);
				});
			} else {
				console.error('사무장 목록을 불러오는데 실패했습니다:', response.message);
			}
		},
		error: function(xhr, status, error) {
			console.error('사무장 목록을 불러오는데 실패했습니다:', error);
		}
	});
}

// 일별 통계 데이터 렌더링 함수 (renderManagerDailyStats에서 분리)
function renderDailyStatsData(data, managers) {
	const statsBody = $('#managerDailyStatsBody');
	
	// 최소 컬럼 수 설정 (6명)
	const minColumns = 6;
	const actualManagerCount = managers.length;
	const columnsToShow = Math.max(minColumns, actualManagerCount);
	
	// 월별 합계 계산을 위한 변수 초기화
	let monthlyTotals = {
		managers: Array(columnsToShow).fill().map(() => ({ inflow: 0, contract: 0 })),
		total: { inflow: 0, contract: 0 }
	};
	
	const displayData = data && data.length > 0 ? data : [];
	
	displayData.forEach(item => {
		const row = $('<div class="stats-row"></div>');
		
		// 날짜 열
		row.append(`
			<div class="date-cell">
				${item.date} <span class="day-name">${item.day}</span>
			</div>
		`);
		
		// 스크롤 영역을 위한 컨테이너
		const managersArea = $('<div class="managers-scroll-area"></div>');
		const managersContainer = $('<div class="manager-columns-container"></div>');
		
		// 각 사무장 데이터
		for(let i = 0; i < columnsToShow; i++) {
			let inflowValue = 0;
			let contractValue = 0;
			
			if(i < item.managers.length && i < actualManagerCount) {
				// 실제 사무장 데이터가 있는 경우
				inflowValue = item.managers[i].inflow;
				contractValue = item.managers[i].contract;
				
				// 월별 합계에 더함
				monthlyTotals.managers[i].inflow += inflowValue;
				monthlyTotals.managers[i].contract += contractValue;
			}
			
			managersContainer.append(`
				<div class="manager-stats">
					<div class="stat-value">${inflowValue}</div>
					<div class="stat-value">${contractValue}</div>
				</div>
			`);
		}
		
		// 합계 열
		monthlyTotals.total.inflow += item.total.inflow;
		monthlyTotals.total.contract += item.total.contract;
		
		managersContainer.append(`
			<div class="manager-stats">
				<div class="stat-value">${item.total.inflow}</div>
				<div class="stat-value">${item.total.contract}</div>
			</div>
		`);
		
		managersArea.append(managersContainer);
		row.append(managersArea);
		statsBody.append(row);
	});
	
	// 푸터 업데이트 - 월간 총합 표시
	updatemanagerDailyStatsFooter(monthlyTotals, columnsToShow);
}

// 푸터 업데이트 함수
function updatemanagerDailyStatsFooter(monthlyTotals, columnsToShow) {
	$('.manager-stats-footer').empty();
	$('.manager-stats-footer').append(`<div class="date-column-total">합계</div>`);
	
	const footerScrollArea = $('<div class="managers-scroll-area"></div>');
	const footerColumnsContainer = $('<div class="manager-columns-container"></div>');
	
	// 사무장 푸터 추가
	for (let i = 0; i < columnsToShow; i++) {
		const managerTotal = i < monthlyTotals.managers.length ? monthlyTotals.managers[i] : { inflow: 0, contract: 0 };
		
		footerColumnsContainer.append(`
			<div class="manager-column">
				<div class="stats-footer">
					<div class="stat-footer">${managerTotal.inflow}</div>
					<div class="stat-footer">${managerTotal.contract}</div>
				</div>
			</div>
		`);
	}
	
	// 합계 칼럼 추가
	footerColumnsContainer.append(`
		<div class="manager-column">
			<div class="stats-footer">
				<div class="stat-footer">${monthlyTotals.total.inflow}</div>
				<div class="stat-footer">${monthlyTotals.total.contract}</div>
			</div>
		</div>
	`);
	
	footerScrollArea.append(footerColumnsContainer);
	$('.manager-stats-footer').append(footerScrollArea);
}

// 헤더 섹션 업데이트 함수
function updatemanagerDailyStatsHeader(managers) {
	// 먼저 기존 헤더 내용을 비우기
	$('.manager-stats-header').empty();
	// date-column (날짜 헤더)를 다시 추가
	$('.manager-stats-header').append(`<div class="date-column">1일 상담 통계&nbsp;&nbsp;<span class="sort-icon date-dropdown-toggle">▼</span></div>`);
	
	// 사무장 컬럼 영역 추가
	const headerScrollArea = $('<div class="managers-scroll-area"></div>');
	const headerColumnsContainer = $('<div class="manager-columns-container"></div>');
	
	// 최소 컬럼 수 설정
	const minColumns = 6;
	const actualManagerCount = managers.length;
	const columnsToShow = Math.max(minColumns, actualManagerCount);
	
	// 실제 사무장 수만큼 칼럼 추가
	for (let i = 0; i < columnsToShow; i++) {
		let managerName = i < actualManagerCount ? managers[i].name + ' 사무장' : '사무장';
		
		headerColumnsContainer.append(`
			<div class="manager-column">
				<div class="manager-header">${managerName}</div>
				<div class="stats-header">
					<div class="stat-header">유입</div>
					<div class="stat-header">계약</div>
				</div>
			</div>
		`);
	}
	
	// 합계 칼럼 추가
	headerColumnsContainer.append(`
		<div class="manager-column">
			<div class="manager-header">합계</div>
			<div class="stats-header">
				<div class="stat-header">유입</div>
				<div class="stat-header">계약</div>
			</div>
		</div>
	`);
	
	headerScrollArea.append(headerColumnsContainer);
	$('.manager-stats-header').append(headerScrollArea);
	
	// 푸터 초기화
	$('.manager-stats-footer').empty();
	$('.manager-stats-footer').append(`<div class="date-column-total">합계</div>`);
	
	const footerScrollArea = $('<div class="managers-scroll-area"></div>');
	const footerColumnsContainer = $('<div class="manager-columns-container"></div>');
	
	// 사무장 푸터 추가
	for (let i = 0; i < columnsToShow; i++) {
		footerColumnsContainer.append(`
			<div class="manager-column">
				<div class="stats-footer">
					<div class="stat-footer">0</div>
					<div class="stat-footer">0</div>
				</div>
			</div>
		`);
	}
	
	// 합계 칼럼 추가
	footerColumnsContainer.append(`
		<div class="manager-column">
			<div class="stats-footer">
				<div class="stat-footer">0</div>
				<div class="stat-footer">0</div>
			</div>
		</div>
	`);
	
	footerScrollArea.append(footerColumnsContainer);
	$('.manager-stats-footer').append(footerScrollArea);
}

// 사무장 데이터 로드 함수
function loadManagersData() {
	$.ajax({
		url: '../adm/api/stats/get_manager_stats.php',
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			if(response.success) {
				updatemanagerDailyStatsUI(response.data);
			} else {
				console.error('사무장 데이터를 불러오는데 실패했습니다:', response.message);
				// 오류 메시지 표시
				$('#caseStats').append('<div class="error-message">사무장 데이터를 불러오는데 실패했습니다.</div>');
			}
		},
		error: function(xhr, status, error) {
			console.error('사무장 데이터를 불러오는데 실패했습니다:', error);
			// 오류 메시지 표시
			$('#caseStats').append('<div class="error-message">사무장 데이터를 불러오는데 실패했습니다.</div>');
		}
	});
}

// 사무장 통계 UI 업데이트 함수 (이어서)
function updatemanagerDailyStatsUI(data) {
	const $nameColumn = $('.name-column');
	const $dataColumns = $('.data-column');
	
	// 사무장 이름 행을 비움
	$nameColumn.empty();
	
	// 각 데이터 컬럼 비움
	$dataColumns.each(function() {
		$(this).empty();
	});
	
	// 데이터 행 최소 10개 만들기
	const minRows = 10;
	const rowCount = Math.max(minRows, data.length);
	
	// 사무장 수에 맞게 행 생성
	for (let i = 0; i < rowCount; i++) {
		// 이름 컬럼 추가
		const $nameRow = $('<div class="name-row"></div>');
		
		// 데이터가 있는 경우에만 실제 데이터 표시
		if (i < data.length) {
			const manager = data[i];
			$nameRow.append('<div class="name-value">' + manager.name + ' 사무장</div>');
			$nameRow.attr('data-employee-id', manager.employee_no);
			$nameColumn.append($nameRow);
			
			// 수임건수 / 평균 수임건수 순위 컬럼
			const $caseCountRow = $('<div class="data-row"></div>');
			$caseCountRow.html(
				'<div class="data-value">' + manager.case_count.toLocaleString() + ' 건</div>' +
				'<div class="info-icon">' + manager.case_count_rank + '</div>'
			);
			$($dataColumns[0]).append($caseCountRow);
			
			// 평균 수임료 / 평균 수임료 순위 컬럼
			const $avgFeeRow = $('<div class="data-row"></div>');
			$avgFeeRow.html(
				'<div class="data-value">' + manager.avg_fee.toLocaleString() + ' 원</div>' +
				'<div class="info-icon">' + manager.avg_fee_rank + '</div>'
			);
			$($dataColumns[1]).append($avgFeeRow);
			
			// 성사율 컬럼
			const $successRateRow = $('<div class="data-row"></div>');
			$successRateRow.html(
				'<div class="data-value">' + manager.success_rate.toLocaleString() + ' %</div>'
			);
			$($dataColumns[2]).append($successRateRow);
		} else {
			// 데이터가 없는 경우는 빈 행 추가
			$nameRow.append('<div class="name-value"></div>');
			$nameColumn.append($nameRow);
			
			// 빈 데이터 행 추가
			const $emptyRow1 = $('<div class="data-row"></div>');
			$($dataColumns[0]).append($emptyRow1);
			
			const $emptyRow2 = $('<div class="data-row"></div>');
			$($dataColumns[1]).append($emptyRow2);
			
			const $emptyRow3 = $('<div class="data-row"></div>');
			$($dataColumns[2]).append($emptyRow3);
		}
	}
	
	// 데이터가 하나도 없는 경우 안내 메시지 추가
	if(data.length === 0) {
		// 첫 번째 행에 데이터 없음 메시지 표시
		$($nameColumn.find('.name-row')[0]).find('.name-value').text('데이터 없음');
		
		// 첫 번째 행의 데이터 셀에 기본값 설정
		const $firstDataRow1 = $($dataColumns[0]).find('.data-row')[0];
		$($firstDataRow1).html('<div class="data-value">0 건</div><div class="info-icon">-</div>');
		
		const $firstDataRow2 = $($dataColumns[1]).find('.data-row')[0];
		$($firstDataRow2).html('<div class="data-value">0 원</div><div class="info-icon">-</div>');
		
		const $firstDataRow3 = $($dataColumns[2]).find('.data-row')[0];
		$($firstDataRow3).html('<div class="data-value">0 %</div>');
	}
}

// 모든 통계 데이터 로드
function loadAllStats() {
	Promise.all([
		$.ajax({
			url: '/adm/api/stats/get_yearly_stats.php',
			method: 'GET'
		}),
		$.ajax({
			url: '/adm/api/stats/get_court_stats.php',
			method: 'GET'
		})
	]).then(([yearlyResponse, courtResponse]) => {
		if (yearlyResponse.success) {
			renderYearlyTables(yearlyResponse.data);
			renderYearlyCharts(yearlyResponse.data);
			updateTotalStats(yearlyResponse.data);
		}
		if (courtResponse.success) {
			renderCourtTables(courtResponse.data);
			renderCourtCharts(courtResponse.data);
		}
	}).catch(error => {
		console.error('데이터 로드 중 오류 발생:', error);
		handleDataLoadError(error);
	});
}

// 데이터 로드 에러 처리
function handleDataLoadError(error) {
	console.error('API 요청 실패:', error);
	
	// 사용자에게 에러 메시지 표시
	const errorMessage = `
		<div class="error-message" style="
			padding: 20px;
			margin: 10px 0;
			background-color: #fff3f3;
			border: 1px solid #ffcdd2;
			border-radius: 4px;
			color: #b71c1c;
		">
			<h4 style="margin: 0 0 10px 0;">데이터 로드 실패</h4>
			<p style="margin: 0;">통계 데이터를 불러오는데 실패했습니다. 잠시 후 다시 시도해주세요.</p>
		</div>
	`;
	
	$('.statistics-content').prepend(errorMessage);
}

// 총계 통계 업데이트
function updateTotalStats(data) {
	const totalRecovery = data.reduce((sum, item) => sum + item.recovery_count, 0);
	const totalBankruptcy = data.reduce((sum, item) => sum + item.bankruptcy_count, 0);
	
	$('#totalRecovery').text(`총 ${totalRecovery.toLocaleString()} 건`);
	$('#totalBankruptcy').text(`총 ${totalBankruptcy.toLocaleString()} 건`);
}

// 연간 통계 테이블 렌더링
function renderYearlyTables(data) {
	const recoveryBody = $('#recoveryYearlyBody');
	const bankruptcyBody = $('#bankruptcyYearlyBody');
	
	recoveryBody.empty();
	bankruptcyBody.empty();
	
	data.forEach(item => {
		recoveryBody.append(`
			<tr>
				<td>${item.year}</td>
				<td>${item.recovery_count.toLocaleString()}</td>
			</tr>
		`);
		
		bankruptcyBody.append(`
			<tr>
				<td>${item.year}</td>
				<td>${item.bankruptcy_count.toLocaleString()}</td>
			</tr>
		`);
	});
}

// 법원별 통계 테이블 렌더링
function renderCourtTables(data) {
	const recoveryBody = $('#recoveryCourtBody');
	const recoveryStatsBody = $('#recoveryStatsBody');
	const bankruptcyBody = $('#bankruptcyCourtBody');
	const bankruptcyStatsBody = $('#bankruptcyStatsBody');
	
	recoveryBody.empty();
	recoveryStatsBody.empty();
	bankruptcyBody.empty();
	bankruptcyStatsBody.empty();
	
	data.forEach(item => {
		recoveryBody.append(`
			<tr>
				<td>${item.court_name}</td>
				<td>${item.recovery_count.toLocaleString()}</td>
			</tr>
		`);
		
		recoveryStatsBody.append(`
			<tr>
				<td>${item.court_name}</td>
				<td>${item.recovery_start_rate}%</td>
				<td>${item.recovery_reject_rate}%</td>
			</tr>
		`);
		
		bankruptcyBody.append(`
			<tr>
				<td>${item.court_name}</td>
				<td>${item.bankruptcy_count.toLocaleString()}</td>
			</tr>
		`);
		
		bankruptcyStatsBody.append(`
			<tr>
				<td>${item.court_name}</td>
				<td>${item.bankruptcy_discharge_rate}%</td>
				<td>${item.bankruptcy_reject_rate}%</td>
			</tr>
		`);
	});
}

// Chart.js 공통 옵션 생성 함수
function createCommonChartOptions() {
	return {
		responsive: true,
		maintainAspectRatio: false,
		scales: {
			y: {
				beginAtZero: true,
				grid: {
					color: '#f0f0f0',
					drawBorder: false
				},
				ticks: {
					color: '#000000',
					callback: value => value.toLocaleString(),
					font: {
						size: vwToPx(0.7)
					}
				},
				border: {
					display: true,
					width: vwToPx(0.05),
					color: '#b5b5b5'
				}
			},
			x: {
				grid: {
					display: false
				},
				ticks: {
					color: '#000000',
					maxRotation: 0,
					minRotation: 0,
					padding: vwToPx(0.5),
					font: {
						size: vwToPx(0.7)
					}
				},
				border: {
					display: true,
					width: vwToPx(0.05),
					color: '#b5b5b5'
				}
			}
		},
		plugins: {
			legend: {
				display: false
			},
			title: {
				display: true,
				text: '사건접수',
				color: '#000000',
				position: 'top',
				align: 'start',
				padding: {
					top: vhToPx(1),
					bottom: vhToPx(1)
				},
				font: {
					size: vwToPx(0.7),
					weight: '500'
				}
			},
			tooltip: {
				callbacks: {
					label: function(context) {
						return `사건수: ${context.formattedValue.toLocaleString()}`;
					}
				},
				titleColor: '#000000',
				bodyColor: '#000000',
				backgroundColor: 'rgba(255, 255, 255, 0.9)',
				borderColor: '#b5b5b5',
				borderWidth: 1
			}
		}
	};
}

// 연간 차트 렌더링
function renderYearlyCharts(data) {
	const sortedData = [...data].sort((a, b) => a.year - b.year);
	const years = sortedData.map(item => item.year);
	
	// Chart.js 기본 설정
	Chart.defaults.color = '#000000';
	Chart.defaults.font.family = "'Noto Sans KR', sans-serif";
	
	const commonOptions = createCommonChartOptions();

	// 기존 차트 제거
	if (charts.recoveryTrend) {
		charts.recoveryTrend.destroy();
	}
	if (charts.bankruptcyTrend) {
		charts.bankruptcyTrend.destroy();
	}

	// 회생 사건 추이 차트
	charts.recoveryTrend = new Chart($('#recoveryTrendChart')[0], {
		type: 'bar',
		data: {
			labels: years,
			datasets: [{
				label: '사건수',
				data: sortedData.map(item => item.recovery_count),
				backgroundColor: '#00e6c3',
				borderColor: '#00e6c3',
				borderWidth: 0,
				barThickness: vwToPx(1)
			}]
		},
		options: commonOptions
	});

	// 파산 사건 추이 차트
	charts.bankruptcyTrend = new Chart($('#bankruptcyTrendChart')[0], {
		type: 'bar',
		data: {
			labels: years,
			datasets: [{
				label: '사건수',
				data: sortedData.map(item => item.bankruptcy_count),
				backgroundColor: '#00e6c3',
				borderColor: '#00e6c3',
				borderWidth: 0,
				barThickness: vwToPx(1)
			}]
		},
		options: commonOptions
	});
}

// 법원 이름 정리 함수
function getRegionName(courtName) {
	if (courtName.includes('강릉지원')) return '강릉';
	if (courtName.includes('원주지원')) return '원주';
	return courtName.replace(/회생법원|지방법원/g, '').trim();
}

// 법원별 차트 렌더링
function renderCourtCharts(data) {
	const courts = data.map(item => getRegionName(item.court_name));
	
	// Chart.js 기본 설정
	Chart.defaults.color = '#000000';
	Chart.defaults.font.family = "'Noto Sans KR', sans-serif";
	
	const commonOptions = {
		responsive: true,
		maintainAspectRatio: false,
		scales: {
			y: {
				beginAtZero: true,
				max: 100,
				grid: {
					color: '#f0f0f0',
					drawBorder: false
				},
				ticks: {
					color: '#000000',
					callback: value => value,
					font: {
						size: vwToPx(0.7)
					}
				}
			},
			x: {
				grid: {
					display: false
				},
				ticks: {
					color: '#000000',
					maxRotation: 0,
					minRotation: 0,
					padding: vwToPx(0.5),
					font: {
						size: vwToPx(0.7)
					}
				},
				stacked: true
			}
		},
		plugins: {
			legend: {
				position: 'top',
				align: 'end',
				labels: {
					color: '#000000',
					font: {
						size: vwToPx(0.7)
					},
					padding: vwToPx(1),
					boxWidth: vwToPx(2),
					boxHeight: vwToPx(2),
					usePointStyle: true,
					pointStyle: 'rectRounded'
				}
			}
		}
	};

	const barChartOptions = {
		...commonOptions,
		barPercentage: 1,
		categoryPercentage: 0.8,
		plugins: {
			...commonOptions.plugins,
			tooltip: {
				callbacks: {
					label: function(context) {
						return `${context.dataset.label}: ${context.formattedValue}%`;
					}
				},
				titleColor: '#000000',
				bodyColor: '#000000',
				backgroundColor: 'rgba(255, 255, 255, 0.9)',
				borderColor: '#b5b5b5',
				borderWidth: 1
			}
		}
	};

	// 기존 차트 제거
	if (charts.recoveryRates) {
		charts.recoveryRates.destroy();
	}
	if (charts.bankruptcyRates) {
		charts.bankruptcyRates.destroy();
	}

	// 회생 비율 차트
	charts.recoveryRates = new Chart($('#recoveryRatesChart')[0], {
		type: 'bar',
		data: {
			labels: courts,
			datasets: [
				{
					label: '개시율(%)',
					data: data.map(item => item.recovery_start_rate),
					backgroundColor: '#00e6c3',
					barThickness: vwToPx(1),
					grouped: false,
					order: 2
				},
				{
					label: '기각/취소율(%)',
					data: data.map(item => item.recovery_reject_rate),
					backgroundColor: '#b5b5b5',
					barThickness: vwToPx(1),
					grouped: false,
					order: 1
				}
			]
		},
		options: barChartOptions
	});

	// 파산 비율 차트
	charts.bankruptcyRates = new Chart($('#bankruptcyRatesChart')[0], {
		type: 'bar',
		data: {
			labels: courts,
			datasets: [
				{
					label: '면책율(%)',
					data: data.map(item => item.bankruptcy_discharge_rate),
					backgroundColor: '#00e6c3',
					barThickness: vwToPx(1),
					grouped: false,
					order: 2
				},
				{
					label: '기각율(%)',
					data: data.map(item => item.bankruptcy_reject_rate),
					backgroundColor: '#b5b5b5',
					barThickness: vwToPx(1),
					grouped: false,
					order: 1
				}
			]
		},
		options: barChartOptions
	});
}

// 차트 생성 실패 시 에러 처리 함수
function handleChartError(error, chartContainer) {
	console.error('차트 생성 실패:', error);
	
	// 차트 컨테이너에 에러 메시지 표시
	const errorMessage = `
		<div class="chart-error" style="
			height: 200px;
			display: flex;
			align-items: center;
			justify-content: center;
			background-color: #f5f5f5;
			border: 1px solid #e0e0e0;
			border-radius: 4px;
			color: #666;
		">
			<p>차트를 표시할 수 없습니다.</p>
		</div>
	`;
	
	$(chartContainer).html(errorMessage);
}

// 테이블 정렬 기능
function enableTableSort() {
	$('.statistics-table th').click(function() {
		const table = $(this).parents('table').first();
		const rows = table.find('tr:gt(0)').toArray();
		const col = $(this).index();
		const isNumeric = $(this).hasClass('numeric-sort');
		
		rows.sort((a, b) => {
			const A = $(a).children('td').eq(col).text();
			const B = $(b).children('td').eq(col).text();
			
			if (isNumeric) {
				// 숫자 정렬 (쉼표 제거 후 비교)
				return parseFloat(A.replace(/,/g, '')) - parseFloat(B.replace(/,/g, ''));
			} else {
				// 문자열 정렬
				return A.localeCompare(B, 'ko-KR');
			}
		});
		
		if ($(this).hasClass('sort-asc')) {
			rows.reverse();
			$(this).removeClass('sort-asc').addClass('sort-desc');
		} else {
			$(this).addClass('sort-asc').removeClass('sort-desc');
		}
		
		table.find('tr:gt(0)').remove();
		table.append(rows);
	});
}

// 주간 트렌드 차트 렌더링 함수
function renderWeeklyTrendChart(trendData) {
	// 기존 차트 제거
	if (charts.weeklyTrend) {
		charts.weeklyTrend.destroy();
	}
	
	const ctx = document.getElementById('weeklyTrendChart').getContext('2d');
	
	charts.weeklyTrend = new Chart(ctx, {
		type: 'line',
		data: {
			labels: trendData.map(item => item.week),
			datasets: [
				{
					label: '유입 건수',
					data: trendData.map(item => item.inflow),
					borderColor: '#00e6c3',
					backgroundColor: 'rgba(0, 230, 195, 0.1)',
					borderWidth: 2,
					tension: 0.4,
					fill: true
				},
				{
					label: '계약 건수',
					data: trendData.map(item => item.contract),
					borderColor: '#6c6c6c',
					backgroundColor: 'rgba(108, 108, 108, 0.1)',
					borderWidth: 2,
					tension: 0.4,
					fill: true
				}
			]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					position: 'top',
					align: 'end',
					labels: {
						boxWidth: 12,
						padding: 15
					}
				},
				tooltip: {
					backgroundColor: 'rgba(255, 255, 255, 0.9)',
					titleColor: '#000',
					bodyColor: '#000',
					borderColor: '#ddd',
					borderWidth: 1,
					padding: 10,
					displayColors: false,
					callbacks: {
						label: function(context) {
							const label = context.dataset.label || '';
							const value = context.parsed.y;
							return `${label}: ${value}건`;
						}
					}
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					grid: {
						color: '#f0f0f0'
					},
					ticks: {
						precision: 0
					}
				},
				x: {
					grid: {
						display: false
					}
				}
			}
		}
	});
}

// 데이터 내보내기 기능
function exportTableToExcel(tableId, fileName) {
	const table = document.getElementById(tableId);
	const ws = XLSX.utils.table_to_sheet(table);
	const wb = XLSX.utils.book_new();
	XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
	XLSX.writeFile(wb, `${fileName}.xlsx`);
}

// 반응형 처리를 위한 차트 리사이즈 함수
function resizeCharts() {
	Object.values(charts).forEach(chart => {
		if (chart) {
			chart.resize();
		}
	});
}