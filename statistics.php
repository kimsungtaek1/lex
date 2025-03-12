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
					<!-- JavaScript로 동적 생성 -->
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
		<div class="statistics-row">
			<div class="statistics-col">
				<div class="statistics-box">
					<h3 class="box-title">사무장 주간 통계</h3>
					<div class="empty-stats-message">
						<p>현재 주간 통계 데이터가 준비 중입니다.</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- 사무장 월간 통계 탭 -->
	<div id="managerMonthlyStats" class="statistics-content" style="display: none;">
		<div class="statistics-row">
			<div class="statistics-col">
				<div class="statistics-box">
					<h3 class="box-title">사무장 월간 통계</h3>
					<div class="empty-stats-message">
						<p>현재 월간 통계 데이터가 준비 중입니다.</p>
					</div>
				</div>
			</div>
		</div>
	</div>

    <!-- 서류담당 통계 탭 -->
    <div id="documentStats" class="statistics-content" style="display: none;">
        <div class="statistics-row">
            <!-- 서류담당 통계 내용 -->
        </div>
    </div>
</div>

<!-- 필수 스크립트 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script src="js/statistics.js"></script>

<?php include 'footer.php'; ?>