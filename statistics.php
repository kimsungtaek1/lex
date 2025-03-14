<?php 
$additional_css = '<link rel="stylesheet" href="css/statistics.css">';
include 'header.php'; 
?>
<div class="statistics-container">
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="stat-tab active" data-type="bankruptcy">|&nbsp;&nbsp;개인회생 / 개인파산 통계</th>
                    <th class="stat-tab" data-type="case">|&nbsp;&nbsp;사건 통계</th>
                    <th class="stat-tab" data-type="manager" id="managerTab">|&nbsp;&nbsp;사무장 통계&nbsp;&nbsp;▼</th>
                    <th class="stat-tab" data-type="document">|&nbsp;&nbsp;서류담당 통계</th>
					<th></th>
                </tr>
            </thead>
        </table>
    </div>

	<!-- 드롭다운 메뉴 추가 -->
	<div id="managerDropdown" class="manager-dropdown">
		<div class="dropdown-option" data-stat-type="daily">일간 통계</div>
		<div class="dropdown-option" data-stat-type="weekly">주간 통계</div>
		<div class="dropdown-option" data-stat-type="monthly">월간 통계</div>
	</div>

    <div id="bankruptcyStats" class="statistics-content">
        <!-- 상단 통계 요약 -->
        <div class="summary-stats">
            <div class="summary-box">
                <div class="total-label">|&nbsp;&nbsp;개인회생통계</div>
                <div class="total-value" id="totalRecovery"></div>
            </div>
            <div class="summary-box">
                <div class="total-label">|&nbsp;&nbsp;개인파산통계</div>
                <div class="total-value" id="totalBankruptcy"></div>
            </div>
        </div>

        <div class="statistics-row">
            <!-- 좌측 컬럼 (회생) -->
            <div class="statistics-col">
                <!-- 연도별 회생 사건수 -->
                <div class="statistics-box">
                    <h3 class="box-title">연도별 개인회생 사건수</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>연도</th>
                                    <th>사건수</th>
                                </tr>
                            </thead>
                            <tbody id="recoveryYearlyBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- 법원별 회생 사건수 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인회생 사건수</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>법원</th>
                                    <th>사건수</th>
                                </tr>
                            </thead>
                            <tbody id="recoveryCourtBody"></tbody>
                        </table>
                    </div>
                </div>
				
				<!-- 연도별 회생 사건수 추이 그래프 -->
                <div class="statistics-box">
                    <h3 class="box-title">연도별 개인회생 사건수 추이</h3>
                    <div class="chart-container">
                        <canvas id="recoveryTrendChart"></canvas>
                    </div>
                </div>

                <!-- 법원별 회생 통계 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인회생 통계</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>법원</th>
                                    <th>개시율</th>
                                    <th>기각/취소율</th>
                                </tr>
                            </thead>
                            <tbody id="recoveryStatsBody"></tbody>
                        </table>
                    </div>
                </div>
				
				<!-- 법원별 회생 개시율/기각율/취소율 그래프 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인회생 개시율, 기각율/취소율</h3>
                    <div class="chart-container">
                        <canvas id="recoveryRatesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 우측 컬럼 (파산) -->
            <div class="statistics-col">
                <!-- 연도별 파산 사건수 -->
                <div class="statistics-box">
                    <h3 class="box-title">연도별 개인파산 사건수</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>연도</th>
                                    <th>사건수</th>
                                </tr>
                            </thead>
                            <tbody id="bankruptcyYearlyBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- 법원별 파산 사건수 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인파산 사건수</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>법원</th>
                                    <th>사건수</th>
                                </tr>
                            </thead>
                            <tbody id="bankruptcyCourtBody"></tbody>
                        </table>
                    </div>
                </div>
				
				<!-- 연도별 파산 사건수 추이 그래프 -->
                <div class="statistics-box">
                    <h3 class="box-title">연도별 개인파산 사건수 추이</h3>
                    <div class="chart-container">
                        <canvas id="bankruptcyTrendChart"></canvas>
                    </div>
                </div>

                <!-- 법원별 파산 통계 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인파산 통계</h3>
                    <div class="statistics-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>법원</th>
                                    <th>면책율</th>
                                    <th>기각율</th>
                                </tr>
                            </thead>
                            <tbody id="bankruptcyStatsBody"></tbody>
                        </table>
                    </div>
                </div>
				
				<!-- 법원별 파산 면책율/기각율 그래프 -->
                <div class="statistics-box">
                    <h3 class="box-title">법원별 개인파산 면책율/기각율</h3>
                    <div class="chart-container">
                        <canvas id="bankruptcyRatesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 사건 통계 탭 -->
    <div id="caseStats" style="display: none;">
        <div class="case-title">
            <div class="case-left">계약 통계</div>
            <div class="case-right">수임건수 / 평균 수임건수 순위</div>
            <div class="case-right">평균 수임료 / 평균 수임료 순위</div>
            <div class="case-right">성사율</div>
        </div>
        <div class="case-content">
            <div class="name-column">
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
                <div class="name-row"></div>
            </div>
            <div class="data-column">
                <div class="data-row">
                    <div class="data-value">0 건</div>
                    <div class="info-icon">1</div>
                </div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
            </div>
            <div class="data-column">
                <div class="data-row">
                    <div class="data-value">0 건</div>
                    <div class="info-icon">1</div>
                </div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
            </div>
            <div class="data-column">
                <div class="data-row">
					<div class="data-value">0 %</div>
				</div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
                <div class="data-row"></div>
            </div>
        </div>
    </div>

	<!-- 사무장 일간 통계 탭 -->
	<div id="managerDailyStats" class="statistics-content" style="display: none;">
		<div class="statistics-row">
			<div class="manager-stats-container has-few-managers">
				<div class="manager-stats-header">
					<div class="date-column">1일 상담 통계&nbsp;&nbsp;<span class="sort-icon date-dropdown-toggle">▼</span></div>
				</div>
				<div id="dateFilterDropdown" class="date-filter-dropdown">
					<div class="dropdown-section">
						<div class="dropdown-title">연도 선택</div>
						<div class="dropdown-scroll">
							<!-- 자바스크립트로 동적 생성될 연도 목록 -->
						</div>
					</div>
					<div class="dropdown-section">
						<div class="dropdown-title">월 선택</div>
						<div class="dropdown-scroll">
							<!-- 자바스크립트로 동적 생성될 월 목록 -->
						</div>
					</div>
					<div class="dropdown-buttons">
						<button class="apply-button">적용</button>
						<button class="reset-button">초기화</button>
					</div>
				</div>
				<div class="manager-stats-body" id="managerDailyStatsBody">
					<!-- JavaScript로 동적 생성 -->
				</div>
				<div class="manager-stats-footer">
					<!-- JavaScript로 동적 생성 -->
				</div>
			</div>
		</div>
	</div>

	<!-- 사무장 주간 통계 탭 -->
	<div id="managerWeeklyStats" class="statistics-content" style="display: none;">
		<div class="weekly-stats">
			<div class="weekly-stats-left">
				<div class="weekly-stats-container has-few-managers">
					<!-- 필터 드롭다운 요소 -->
					<div id="weekFilterDropdown" class="date-filter-dropdown"></div>
					<div class="weekly-stats-body" id="managerWeeklyStatsBody">
						<!-- JavaScript로 동적 생성 -->
					</div>
					<div class="weekly-stats-footer">
						<!-- JavaScript로 동적 생성 -->
					</div>
				</div>
			</div>
			<div class="weekly-stats-right">
				<div class="statistics-box">
					<h3 class="box-title">주간 상담 현황</h3>
					<div class="chart-container">
						<canvas id="weeklyTrendChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- 사무장 월간 통계 탭 -->
	<div id="managerMonthlyStats" class="statistics-content" style="display: none;">
		<div class="weekly-stats">
			<div class="weekly-stats-left">
				<div class="weekly-stats-container has-few-managers">
					<!-- 필터 드롭다운 요소 -->
					<div id="monthFilterDropdown" class="date-filter-dropdown"></div>
					<div class="weekly-stats-body" id="managerMonthlyStatsBody">
						<!-- JavaScript로 동적 생성 -->
					</div>
					<div class="monthly-stats-footer">
						<!-- JavaScript로 동적 생성 -->
					</div>
				</div>
			</div>
			<div class="weekly-stats-right">
				<div class="statistics-box">
					<h3 class="box-title">월간 상담 현황</h3>
					<div class="chart-container">
						<canvas id="monthlyTrendChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

    <!-- 서류담당 통계 탭 -->
	<div id="documentStats" class="statistics-content" style="display: none;">
		<div class="document-stats-container">
			<table class="document-stats-table">
				<thead>
					<tr>
						<th rowspan="2" class="team-col">팀명</th>
						<th rowspan="2" class="name-col">성명 / 직함</th>
						<th colspan="5" class="section-header">담당사건</th>
						<th colspan="5" class="section-header">개시 및 면책</th>
					</tr>
					<tr>
						<th>접수전</th>
						<th>신건접수</th>
						<th>개시전</th>
						<th>합계</th>
						<th>담월</th>
						<th>1개월전</th>
						<th>2개월전</th>
						<th>합계</th>
						<th>평균</th>
					</tr>
				</thead>
				<tbody id="documentStatsBody">
					<!-- 데이터는 JavaScript로 동적으로 추가됩니다 -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- 필수 스크립트 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script src="js/statistics.js"></script>

<?php include 'footer.php'; ?>