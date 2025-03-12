<?php 
<<<<<<< HEAD
$additional_css = '<link rel="stylesheet" href="css/application_recovery.css">';
include 'header.php';
include 'config.php';
// 권한 체크 
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
?>

=======
// application_recovery.php

// CSS 추가 (추가 CSS가 필요한 경우)
$additional_css = '<link rel="stylesheet" href="css/application_recovery.css">';

// 헤더 및 설정파일 포함
include 'header.php';
include 'config.php';

// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 1) {
    echo "<script>
            alert('접근 권한이 없습니다.');
            window.location.href = 'main.php';
          </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>재산/신청 관리</title>
    <?php echo $additional_css; ?>
    <!-- 필요에 따라 추가적인 메타, CSS 파일 포함 -->
</head>
<body>
>>>>>>> 719d7c8 (Delete all files)
<div class="container">
    <!-- 상단 탭 영역 -->
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="doc-tab" data-type="case-list">|&nbsp;&nbsp;사건목록</th>
                    <th class="doc-tab" data-type="applicant">|&nbsp;&nbsp;신청인정보</th>
                    <th class="doc-tab" data-type="creditors">|&nbsp;&nbsp;채권자목록</th> 
                    <th class="doc-tab" data-type="assets">|&nbsp;&nbsp;재산목록</th>
                    <th class="doc-tab" data-type="income">|&nbsp;&nbsp;수입지출목록</th>
                    <th class="doc-tab" data-type="statement">|&nbsp;&nbsp;진술서</th>
<<<<<<< HEAD
                    <th class="doc-tab" data-type="repayment">|&nbsp;&nbsp;변제계획안</th>
=======
>>>>>>> 719d7c8 (Delete all files)
                    <th class="doc-tab" data-type="documents">|&nbsp;&nbsp;자료제출목록</th>
                    <th class="doc-tab" data-type="view">|&nbsp;&nbsp;열람/출력</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- 사건 목록 섹션 -->
    <div id="caseListSection" class="section-content">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;번호</th>
                        <th>|&nbsp;&nbsp;성명</th>
                        <th>|&nbsp;&nbsp;사건번호</th>
                        <th></th>
                    </tr>
                </thead>
<<<<<<< HEAD
                <tbody id="caseList"></tbody>
            </table>

            <div class="search-box">
                <input type="text" placeholder="검색" id="searchInput">
                <button type="button" class="search-btn">Q</button>
            </div>

=======
                <tbody id="caseList">
                    <!-- 사건 목록이 동적으로 추가됨 -->
                </tbody>
            </table>
            <div class="search-box">
                <input type="text" placeholder="검색" id="searchInput">
                <button type="button" class="search-btn">검색</button>
            </div>
>>>>>>> 719d7c8 (Delete all files)
            <div class="pagination">
                <button type="button" class="page-btn prev-btn" disabled>&lt;</button>
                <div class="page-numbers"></div>
                <button type="button" class="page-btn next-btn">&gt;</button>
            </div>
        </div>
    </div>

    <!-- 신청인정보 섹션 -->
    <div id="applicantSection" class="section-content" style="display:none;">
        <form class="applicant-form">
            <div class="section-header">신청인 인적사항</div>
            <div class="content-wrapper">
                <div class="left-section">
                    <div class="form">
                        <div class="form-title"><span>성명</span></div>
                        <div class="form-content"><input type="text" name="name" id="name"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>연락처</span></div>
                        <div class="form-content"><input type="text" name="phone" id="phone"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>주민등록번호</span></div>
                        <div class="form-content"><input type="text" name="residentNumber" id="residentNumber"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>주민등록상주소</span></div>
                        <div class="form-content">
                            <input type="text" id="registeredAddress" name="registeredAddress">
                            <button type="button" class="btn-search" data-target="registeredAddress">주소찾기</button>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle"><span>실거주지주소</span></div>
                        <div class="form-content form-nocontent">
                            <input type="text" id="nowAddress" name="nowAddress">
                            <button type="button" class="btn-search" data-target="nowAddress">주소찾기</button>
                        </div>
                    </div>
                    <div class="form">
<<<<<<< HEAD
                        <div class="form-title "><span></span></div>
                        <div class="form-content ">
=======
                        <div class="form-title"></div>
                        <div class="form-content">
>>>>>>> 719d7c8 (Delete all files)
                            <input type="checkbox" id="sameAsRegistered">
                            <label for="sameAsRegistered">주민등록상 주소와 동일</label>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>소득유형</span></div>
                        <div class="form-content">
<<<<<<< HEAD
                            <input type="radio" name="incomeType" value="0" id="salaryType" data-selected="false">
                            <label for="salaryType">급여소득자</label>
                            <input type="radio" name="incomeType" value="1" id="businessType" data-selected="false">
=======
                            <input type="radio" name="incomeType" value="0" id="salaryType">
                            <label for="salaryType">급여소득자</label>
                            <input type="radio" name="incomeType" value="1" id="businessType">
>>>>>>> 719d7c8 (Delete all files)
                            <label for="businessType">영업소득자</label>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>영업장주소</span></div>
                        <div class="form-content">
                            <input type="text" id="workAddress" name="workAddress">
                            <button type="button" class="btn-search" data-target="workAddress">주소찾기</button>
                        </div>
                    </div>
                </div>
                <div class="right-section">
                    <div class="form">
                        <div class="form-title"><span>상호명</span></div>
                        <div class="form-content"><input type="text" name="workplace" id="workplace"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>업종</span></div>
                        <div class="form-content"><input type="text" name="position" id="position"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>종사경력</span></div>
                        <div class="form-content"><input type="text" name="workPeriod" id="workPeriod" placeholder="O년 O개월"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>기타소득</span></div>
<<<<<<< HEAD
                        <div class="form-content">이중소득자인 경우 아래 입력란에 기입하시기 바랍니다.<input type="text" name="otherIncome" id="otherIncome" style="display:none;" readonly></div>
=======
                        <div class="form-content">
                            이중소득자인 경우 아래 입력란에 기입하시기 바랍니다.
                            <input type="text" name="otherIncome" id="otherIncome" style="display:none;" readonly>
                        </div>
>>>>>>> 719d7c8 (Delete all files)
                    </div>
                    <div class="form">
                        <div class="form-title"><span>기타소득명칭</span></div>
                        <div class="form-content"><input type="text" name="otherIncomeName" id="otherIncomeName"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>소득처</span></div>
                        <div class="form-content"><input type="text" name="incomeSource" id="incomeSource"></div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle"><span>비고</span></div>
                        <div class="form-content form-nocontent"><input type="text" name="remarks" id="remarks"></div>
                    </div>
<<<<<<< HEAD
                    <div class="form">
                        <div class="form-title "></div>
                        <div class="form-content "></div>
=======
					<div class="form">
                        <div class="form-title"><span></span></div>
                        <div class="form-content"></div>
>>>>>>> 719d7c8 (Delete all files)
                    </div>
                </div>
            </div>
            
            <div class="section-header">신청관련정보</div>
            <div class="content-wrapper">
                <div class="left-section">
<<<<<<< HEAD
					<div class="form">
=======
                    <div class="form">
>>>>>>> 719d7c8 (Delete all files)
                        <div class="form-title"><span>신청일</span></div>
                        <div class="form-content"><input type="date" name="applicationDate" id="applicationDate"></div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle"><span>변제개시일</span></div>
                        <div class="form-content form-nocontent">
                            <input type="date" name="repaymentStartDate" id="repaymentStartDate" placeholder="신청일 기준 + 90일" readonly>
                        </div>
                    </div>
                    <div class="form">
<<<<<<< HEAD
                        <div class="form-title "><span></span></div>
                        <div class="form-content ">
                            <input type="checkbox" id="unspecifiedDate">
                            <label for="unspecifiedDate">변제개시일자를 특정할 수 없는 경우(예:급여 (가)압류가 되어 있는 경우 등)</label>
=======
                        <div class="form-title"></div>
                        <div class="form-content">
                            <input type="checkbox" id="unspecifiedDate">
                            <label for="unspecifiedDate">변제개시일자를 특정할 수 없는 경우(예:급여 (가)압류 등)</label>
>>>>>>> 719d7c8 (Delete all files)
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>관할법원</span></div>
                        <div class="form-content"><input type="text" name="court" id="court"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>사건번호</span></div>
                        <div class="form-content"><input type="text" name="caseNumber" id="caseNumber"></div>
                    </div>
                </div>
                <div class="right-section">
                    <div class="form">
                        <div class="form-title"><span>반환계좌은행명</span></div>
                        <div class="form-content"><input type="text" name="bankName" id="bankName"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>계좌번호</span></div>
                        <div class="form-content"><input type="text" name="accountNumber" id="accountNumber"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>금지명령</span></div>
                        <div class="form-content">
<<<<<<< HEAD
                            <button class="form-row" id="prohibitionOrder">금지명령신청</button>
=======
                            <button type="button" class="form-row" id="prohibitionOrder">금지명령신청</button>
>>>>>>> 719d7c8 (Delete all files)
                            <span class="form-row" id="prohibition-count">[등록수 : 0]</span>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle"><span>중지명령</span></div>
                        <div class="form-content form-nocontent">
<<<<<<< HEAD
                            <button class="form-row" id="stayOrder">중지명령신청</button>
=======
                            <button type="button" class="form-row" id="stayOrder">중지명령신청</button>
>>>>>>> 719d7c8 (Delete all files)
                            <span class="form-row" id="stay-count">[등록수 : 0]</span>
                        </div>
                    </div>
                    <div class="form">
<<<<<<< HEAD
                        <div class="form-title "></div>
                        <div class="form-content btn-right">
                            <button id="delete_applicant">삭제</button>
                            <button id="save_applicant">저장</button>
=======
                        <div class="form-title"></div>
                        <div class="form-content btn-right">
                            <button type="button" id="delete_applicant">삭제</button>
                            <button type="button" id="save_applicant">저장</button>
>>>>>>> 719d7c8 (Delete all files)
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<<<<<<< HEAD
    <!-- 나머지 섹션들 -->
	<!-- 채권자목록 섹션 -->
	<div id="creditorsSection" class="section-content" style="display:none;">
		<div class="content-wrapper">
			<div class="left-section">
				<div class="section-header">설정사항</div>
				<div class="form">
					<div class="form-title"><span>원리금 합산변제</span></div>
					<div class="form-content">
                        <input type="checkbox" id="combinedPayment">
                        <label for="combinedPayment">원금과 이자를 합산하여 변제</label>
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span>목록작성일</span></div>
					<div class="form-content">
						<input type="date" id="listCreationDate" name="listCreationDate">
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span>채권액 산정기준일<br>(부채발급일)</span></div>
					<div class="form-content">
						<input type="date" id="debtCalculationDate" name="debtCalculationDate">
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span></span></div>
					<div class="form-content">
                        <span>* 원금(이자) 산정근거일자로 표기</span>
                        <button type="button" class="btn-save-settings">저장</button>
					</div>
				</div>
			</div>

			<div class="right-section">
				<div class="section-header">회생채권액 합계정보</div>
				<div class="form">
					<div class="form-title"><span>채권 현재액 총 합계</span></div>
					<div class="form-content row2">
						<input type="text" id="totalDebt" value="" readonly>
						<span>원</span>
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span>담보부 회생 채권액<br>합계</span></div>
					<div class="form-content row2">
						<input type="text" id="securedDebt" value="" readonly>
						<span>원</span>
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span>무담보 회생 채권액<br>합계</span></div>
					<div class="form-content row2">
						<input type="text" id="unsecuredDebt" value="" readonly>
						<span>원</span>
					</div>
				</div>
				<div class="form">
					<div class="form-title"><span>주의사항</span></div>
					<div class="form-content">
                        담보부채무(원금+이자 합산액) 15억원 이상, 무담보채무(원금+이자 합산액) 10억원 이상인 경우에는<br>개인회생신청 대상이 아닙니다.
					</div>
				</div>
			</div>
		</div>

		<div class="content-wrapper">
			<div id="creditorList">
				<!-- 채권자 목록이 동적으로 추가되는 영역 -->
			</div>
		</div>
	</div>
    
=======
    <!-- 채권자목록 섹션 -->
    <div id="creditorsSection" class="section-content" style="display:none;">
        <div class="content-wrapper">
            <div class="left-section">
                <div class="section-header">설정사항</div>
                <div class="form">
                    <div class="form-title"><span>원리금 합산변제</span></div>
                    <div class="form-content">
                        <input type="checkbox" id="combinedPayment">
                        <label for="combinedPayment">원금과 이자를 합산하여 변제</label>
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span>목록작성일</span></div>
                    <div class="form-content">
                        <input type="date" id="listCreationDate" name="listCreationDate">
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span>채권액 산정기준일<br>(부채발급일)</span></div>
                    <div class="form-content">
                        <input type="date" id="debtCalculationDate" name="debtCalculationDate">
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span></span></div>
                    <div class="form-content">
                        <span>* 원금(이자) 산정근거일자로 표기</span>
                        <button type="button" class="btn-save-settings">저장</button>
                    </div>
                </div>
            </div>
            <div class="right-section">
                <div class="section-header">회생채권액 합계정보</div>
                <div class="form">
                    <div class="form-title"><span>채권 현재액 총 합계</span></div>
                    <div class="form-content row2">
                        <input type="text" id="totalDebt" value="" readonly>
                        <span>원</span>
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span>담보부 회생 채권액<br>합계</span></div>
                    <div class="form-content row2">
                        <input type="text" id="securedDebt" value="" readonly>
                        <span>원</span>
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span>무담보 회생 채권액<br>합계</span></div>
                    <div class="form-content row2">
                        <input type="text" id="unsecuredDebt" value="" readonly>
                        <span>원</span>
                    </div>
                </div>
                <div class="form">
                    <div class="form-title"><span>주의사항</span></div>
                    <div class="form-content">
                        담보부채무(원금+이자 합산액) 15억원 이상, 무담보채무(원금+이자 합산액) 10억원 이상인 경우에는<br>
                        개인회생신청 대상이 아닙니다.
                    </div>
                </div>
            </div>
        </div>
        <div class="content-wrapper">
            <div id="creditorList">
                <!-- 채권자 목록이 동적으로 추가되는 영역 -->
            </div>
        </div>
    </div>

>>>>>>> 719d7c8 (Delete all files)
    <!-- 재산목록 섹션 -->
    <div id="assetsSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_recovery/asset_template.php'); ?>
    </div>

<<<<<<< HEAD
    <div id="incomeSection" class="section-content" style="display: none;">
        <!-- 수입지출목록 내용 -->
    </div>
    
    <div id="statementSection" class="section-content" style="display: none;">
        <!-- 진술서 내용 -->
    </div>
    
    <div id="repaymentSection" class="section-content" style="display: none;">
        <!-- 변제계획안 내용 -->
    </div>
    
    <div id="documentsSection" class="section-content" style="display: none;">
        <!-- 자료제출목록 내용 -->
    </div>
    
    <div id="viewSection" class="section-content" style="display: none;">
        <!-- 열람/출력 내용 -->
    </div>
</div>

=======
    <!-- 수입지출목록 섹션 -->
    <div id="incomeSection" class="section-content" style="display:none;">
		<?php require_once('./api/application_recovery/income_expenditure_template.php'); ?>
    </div>

    <!-- 진술서 섹션 -->
	<div id="statementSection" class="section-content" style="display:none;">
		<?php require_once('./api/application_recovery/statement_template.php'); ?>
	</div>
	<!-- 자료제출목록 섹션 -->
	<div id="documentsSection" class="section-content" style="display:none;">
		<?php require_once('./api/application_recovery/document_list_template.php'); ?>
	</div>
	<!-- 열람/출력 섹션 -->
	<div id="viewSection" class="section-content" style="display:none;">
		<?php require_once('./api/application_recovery/view_print_template.php'); ?>
	</div>
</div>

<!-- 스크립트 파일들 -->
>>>>>>> 719d7c8 (Delete all files)
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script src="js/application_recovery_creditor_box.js"></script>
<script src="js/application_recovery.js"></script>
<script src="js/application_recovery_applicant.js"></script>
<script src="js/application_recovery_creditor.js"></script>
<script src="js/application_recovery_assets.js"></script>
<<<<<<< HEAD
=======
<script src="js/application_recovery_income_expenditure.js"></script>
<script src="js/application_recovery_statement.js"></script>
>>>>>>> 719d7c8 (Delete all files)
</body>
</html>
