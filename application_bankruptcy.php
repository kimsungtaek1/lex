<?php 
// application_bankruptcy.php

// CSS 추가 (추가 CSS가 필요한 경우)
$additional_css = '<link rel="stylesheet" href="css/application_bankruptcy.css">';

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
<div class="container">
    <!-- 상단 탭 영역 -->
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="doc-tab" data-type="case-list">|&nbsp;&nbsp;사건목록</th>
                    <th class="doc-tab" data-type="applicant">|&nbsp;&nbsp;신청서</th>
                    <th class="doc-tab" data-type="statement">|&nbsp;&nbsp;진술서</th> 
                    <th class="doc-tab" data-type="creditors">|&nbsp;&nbsp;채권자목록</th>
                    <th class="doc-tab" data-type="assets">|&nbsp;&nbsp;재산목록</th>
                    <th class="doc-tab" data-type="living-status">|&nbsp;&nbsp;생활상황</th>
                    <th class="doc-tab" data-type="income">|&nbsp;&nbsp;수입지출목록</th>
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
                <tbody id="caseList">
                    <!-- 사건 목록이 동적으로 추가됨 -->
                </tbody>
            </table>
            <div class="search-box">
                <input type="text" placeholder="검색" id="searchInput_b">
                <button type="button" class="search-btn">검색</button>
            </div>
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
                        <div class="form-content"><input type="text" name="name_b" id="name_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>주민등록번호</span></div>
                        <div class="form-content"><input type="text" name="residentNumber_b" id="residentNumber_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>주민등록상주소</span></div>
                        <div class="form-content">
                            <input type="text" id="registeredAddress_b" name="registeredAddress_b">
                            <button type="button" class="btn-search" data-target="registeredAddress_b">주소찾기</button>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle"><span>실거주지주소</span></div>
                        <div class="form-content form-nocontent">
                            <input type="text" id="nowAddress_b" name="nowAddress_b">
                            <button type="button" class="btn-search" data-target="nowAddress_b">주소찾기</button>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"></div>
                        <div class="form-content">
                            <input type="checkbox" id="sameAsRegistered_b" name="sameAsRegistered_b">
                            <label for="sameAsRegistered_b">주민등록상 주소와 동일</label>
                        </div>
                    </div>
                </div>
                <div class="right-section">
                    <div class="form">
                        <div class="form-title"><span>등록기준지</span></div>
                        <div class="form-content">
                            <input type="text" id="baseAddress_b" name="baseAddress_b">
                            <button type="button" class="btn-search" data-target="baseAddress_b">주소찾기</button>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>연락처</span></div>
                        <div class="form-content"><input type="text" name="phone_b" id="phone_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>연락처(자택/직장)</span></div>
                        <div class="form-content"><input type="text" name="workPhone_b" id="workPhone_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>이메일</span></div>
                        <div class="form-content"><input type="text" name="email_b" id="email_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span></span></div>
                        <div class="form-content"></div>
                    </div>
                </div>
            </div>
            
            <div class="section-header">신청관련정보</div>
            <div class="content-wrapper">
                <div class="left-section">
                    <div class="form">
                        <div class="form-title"><span>신청예정일</span></div>
                        <div class="form-content"><input type="date" name="applicationDate_b" id="applicationDate_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>관할법원</span></div>
                        <div class="form-content"><input type="text" name="court_b" id="court_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>사건번호</span></div>
                        <div class="form-content"><input type="text" name="caseNumber_b" id="caseNumber_b"></div>
                    </div>
                    <div class="form">
                        <div class="form-title"><span>채권자 수</span></div>
                        <div class="form-content"><input type="number" name="creditorCount_b" id="creditorCount_b"></div>
                    </div>
                </div>
                <div class="right-section">
                    <div class="form">
                        <div class="form-title"><span>신청</span></div>
                        <div class="form-content">
                            <input type="checkbox" id="stayOrderApply_b" name="stayOrderApply_b">
                            <label for="stayOrderApply_b">중지명령신청</label>
                            <input type="checkbox" id="exemptionApply_b" name="exemptionApply_b">
                            <label for="exemptionApply_b">면제재산신청</label>
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title form-notitle">
                            <span>법원 외 타기관을<br>통한 개인파산</span>
                        </div>
                        <div class="form-content form-nocontent">
                            지원기관&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="supportOrg_b" id="supportOrg_b" placeholder="예)신용회복위원회, 서울시복지재단, 법률구조공단 등">
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title">
                            <span>신청에 대한<br>지원여부</span>
                        </div>
                        <div class="form-content">
                            지원내역과 지원금액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" name="supportDetails_b" id="supportDetails_b" placeholder="예)신청서작성지원, 변호사수임료지원, 송달료지원, 파산관재인보수지원 등">
                        </div>
                    </div>
                    <div class="form">
                        <div class="form-title"></div>
                        <div class="form-content btn-right">
                            <button type="button" id="delete_applicant_b">삭제</button>
                            <button type="button" id="save_applicant_b">저장</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

	<!-- 진술서 섹션 -->
    <div id="statementSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/statement_template.php'); ?>
    </div>

    <!-- 채권자목록 섹션 -->
    <div id="creditorsSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/creditors_template.php'); ?>
    </div>

    <!-- 재산목록 섹션 -->
    <div id="assetsSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/asset_template.php'); ?>
    </div>
	
	<!-- 생활상황 섹션 -->
	<div id="living-statusSection" class="section-content" style="display:none;">
		<?php require_once('./api/application_bankruptcy/living_status_template.php'); ?>
	</div>

    <!-- 수입지출목록 섹션 -->
    <div id="incomeSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/income_expenditure_template.php'); ?>
    </div>
	
    <!-- 자료제출목록 섹션 -->
    <div id="documentsSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/document_list_template.php'); ?>
    </div>
    <!-- 열람/출력 섹션 -->
    <div id="viewSection" class="section-content" style="display:none;">
        <?php require_once('./api/application_bankruptcy/view_print_template.php'); ?>
    </div>
</div>

<!-- 스크립트 파일들 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script src="js/application_bankruptcy.js"></script>
<script src="js/application_bankruptcy_creditor_box.js"></script>
<script src="js/application_bankruptcy_applicant.js"></script>
<script src="js/application_bankruptcy_creditor.js"></script>
<script src="js/application_bankruptcy_assets.js"></script>
<script src="js/application_bankruptcy_living_status.js"></script>
<script src="js/application_bankruptcy_income_expenditure.js"></script>
<script src="js/application_bankruptcy_statement.js"></script>
<script src="js/document_list_manager.js"></script>
<script src="js/bankruptcy_view_print.js"></script>
</body>
</html>