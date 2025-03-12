// 전역 변수로 차트 인스턴스들을 저장
let charts = {
	recoveryTrend: null,
	bankruptcyTrend: null,
	recoveryRates: null,
	bankruptcyRates: null
};

// 디바운스 함수 정의
function debounce(func, wait) {
	let timeout;
	return function executedFunction(...args) {
		const later = () => {
			clearTimeout(timeout);
			func(...args);
		};
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
	};
}

// vw, vh를 픽셀로 변환하는 유틸리티 함수
function vwToPx(vw) {
	return Math.round((window.innerWidth * vw) / 100);
}

function vhToPx(vh) {
	return Math.round((window.innerHeight * vh) / 100);
}

// 문서 로드 완료 시 초기화
$(document).ready(function() {
	// 탭 클릭 이벤트 핸들러
	$('.stat-tab').click(function() {
		// 모든 탭에서 active 클래스 제거 후 현재 탭에만 추가
		$('.stat-tab').removeClass('active');
		$(this).addClass('active');
		
		// 모든 통계 컨텐츠 영역 숨김
		$('#bankruptcyStats, #caseStats, #managerStats, #documentStats').hide();
		
		// 클릭한 탭에 해당하는 컨텐츠만 표시
		const type = $(this).data('type');
		$(`#${type}Stats`).show();
		
		// 각 탭에 맞는 데이터 로드
		if(type === 'case') {
			loadManagersData();
		} else if(type === 'bankruptcy') {
			loadAllStats();
		} else if(type === 'manager') {
			loadManagerDailyStats();
		}
	});
	
	// 초기 페이지 로드 시 기본 탭 내용만 표시
	const activeTabType = $('.stat-tab.active').data('type');
	
	// 모든 통계 컨텐츠 영역 숨김
	$('#bankruptcyStats, #caseStats, #managerStats, #documentStats').hide();
	
	// 활성 탭에 해당하는 컨텐츠만 표시
	$(`#${activeTabType}Stats`).show();
	
	// 활성 탭에 맞는 데이터 로드
	if(activeTabType === 'case') {
		loadManagersData();
	} else if(activeTabType === 'bankruptcy') {
		loadAllStats();
	} else if(activeTabType === 'manager') {
		loadManagerDailyStats();
	}
	
	// 윈도우 리사이즈 이벤트 처리
	$(window).resize(debounce(function() {
		if($('.stat-tab.active').data('type') === 'bankruptcy') {
			loadAllStats();
		}
	}, 250));
	
	// 테이블 정렬 기능 활성화
	enableTableSort();
});

// 사무장 일별 통계 데이터 로드 함수
function loadManagerDailyStats() {
	$.ajax({
		url: '../adm/api/stats/get_manager_daily_stats.php',
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			if(response.success) {
				renderManagerDailyStats(response.data);
			} else {
				console.error('사무장 일별 통계를 불러오는데 실패했습니다:', response.message);
				$('#managerStatsBody').html('<div class="error-message">데이터를 불러오는데 실패했습니다.</div>');
			}
		},
		error: function(xhr, status, error) {
			console.error('사무장 일별 통계를 불러오는데 실패했습니다:', error);
			$('#managerStatsBody').html('<div class="error-message">데이터를 불러오는데 실패했습니다.</div>');
		}
	});
}

// 사무장 일별 통계 렌더링 함수 수정
function renderManagerDailyStats(data) {
    const statsBody = $('#managerStatsBody');
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
                updateManagerStatsHeader(response.data);
                
                // 이제 일별 통계 데이터 표시
                const displayData = data && data.length > 0 ? data : [];
                
                // 최소 컬럼 수 설정 (6명)
                const minColumns = 6;
                const actualManagerCount = response.data.length;
                const columnsToShow = Math.max(minColumns, actualManagerCount);
                
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
                        if(i < item.managers.length && i < actualManagerCount) {
                            // 실제 사무장 데이터가 있는 경우
                            managersContainer.append(`
                                <div class="manager-stats">
                                    <div class="stat-value">${item.managers[i].inflow}</div>
                                    <div class="stat-value">${item.managers[i].contract}</div>
                                </div>
                            `);
                        } else {
                            // 빈 컬럼 추가
                            managersContainer.append(`
                                <div class="manager-stats">
                                    <div class="stat-value">0</div>
                                    <div class="stat-value">0</div>
                                </div>
                            `);
                        }
                    }
                    
                    // 합계 열
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

// 헤더 섹션 업데이트 함수 수정
function updateManagerStatsHeader(managers) {
    // 기존 헤더 컨테이너 비우기
    $('.manager-stats-header').empty();
    
    // 날짜 열 추가
    $('.manager-stats-header').append(`
        <div class="date-column">1일 상담 통계<span class="sort-icon">▼</span></div>
    `);
    
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
    
    // 푸터 업데이트
    $('.manager-stats-footer').empty();
    $('.manager-stats-footer').append(`<div class="date-column">합계</div>`);
    
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
			console.log('사무장 데이터:', response);
			if(response.success) {
				updateManagerStatsUI(response.data);
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

// 사무장 통계 UI 업데이트 함수
function updateManagerStatsUI(data) {
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
		handleAjaxError(error);
	});
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

// 연간 차트 렌더링
function renderYearlyCharts(data) {
	const sortedData = [...data].sort((a, b) => a.year - b.year);
	const years = sortedData.map(item => item.year);
	
	// Chart.js 기본 설정
	Chart.defaults.color = '#000000';
	Chart.defaults.font.family = "'Noto Sans KR', sans-serif";
	
	const commonOptions = {
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

// 법원별 차트 렌더링
function renderCourtCharts(data) {
	// 법원 이름 정리 함수
	const getRegionName = (courtName) => {
		if (courtName.includes('강릉지원')) return '강릉';
		if (courtName.includes('원주지원')) return '원주';
		return courtName.replace(/회생법원|지방법원/g, '').trim();
	};
	
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
		options: {
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
		}
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
		options: {
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
		}
	});
}

// 데이터 요청 실패 시 에러 처리 함수
function handleAjaxError(error) {
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

// 데이터 포맷팅 유틸리티 함수들
const formatUtils = {
	// 숫자 포맷팅 (천단위 구분기호)
	formatNumber: (number) => {
		return number.toLocaleString('ko-KR');
	},
	
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

// 차트 컬러 테마 설정
const chartColors = {
	primary: '#00e6c3',
	secondary: '#b5b5b5',
	background: '#ffffff',
	grid: '#f0f0f0',
	border: '#b5b5b5',
	text: '#000000'
};

// 전역 차트 설정
Chart.defaults.set('plugins.tooltip.backgroundColor', 'rgba(255, 255, 255, 0.9)');
Chart.defaults.set('plugins.tooltip.titleColor', chartColors.text);
Chart.defaults.set('plugins.tooltip.bodyColor', chartColors.text);
Chart.defaults.set('plugins.tooltip.borderColor', chartColors.border);
Chart.defaults.set('plugins.tooltip.borderWidth', 1);