<<<<<<< HEAD
<?php 
$additional_css = '<link rel="stylesheet" href="css/employee.css">';
include 'header.php';
// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 5) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
?>

<div class="container">
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="stat-tab active" data-type="employee">|&nbsp;&nbsp;사원관리</th>
                    <th class="stat-tab" data-type="department">|&nbsp;&nbsp;부서관리</th>
                    <th class="stat-tab" data-type="position">|&nbsp;&nbsp;직위관리</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- 사원관리 섹션 -->
    <div id="employeeSection" class="section-content">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;성명</th>
                        <th>|&nbsp;&nbsp;사원 ID</th>
                        <th>|&nbsp;&nbsp;부서명</th>
                        <th>|&nbsp;&nbsp;직위</th>
                        <th>|&nbsp;&nbsp;이메일</th>
                        <th>|&nbsp;&nbsp;연락처</th>
                        <th>|&nbsp;&nbsp;입사일</th>
                        <th>|&nbsp;&nbsp;최종접속일</th>
                        <th>|&nbsp;&nbsp;상태</th>
                        <th>|&nbsp;&nbsp;권한</th>
                        <th>
                            <div class="control-box">
                                <select class="status-select filter-select">
                                    <option value="">전체</option>
                                    <option value="재직">재직</option>
                                    <option value="휴직">휴직</option>
                                    <option value="퇴사">퇴사</option>
                                </select>
                                <button type="button" class="add-employee-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="employeeList"></tbody>
            </table>
            <div class="search-box">
                <input type="text" placeholder="이름" id="searchInput">
                <button type="button" class="search-btn">Q</button>
            </div>
            <div class="pagination">
                <button type="button" class="page-btn prev-btn" disabled>&lt;</button>
                <div class="page-numbers"></div>
                <button type="button" class="page-btn next-btn">&gt;</button>
            </div>
        </div>
    </div>

    <!-- 부서관리 섹션 -->
    <div id="departmentSection" class="section-content" style="display: none;">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;부서명</th>
                        <th>|&nbsp;&nbsp;부서장</th>
                        <th>|&nbsp;&nbsp;사용여부</th>
                        <th>
                            <div class="control-box">
                                <button type="button" class="add-dept-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="departmentList"></tbody>
            </table>
        </div>
    </div>

    <!-- 직위관리 섹션 -->
    <div id="positionSection" class="section-content" style="display: none;">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;직위명</th>
                        <th>|&nbsp;&nbsp;순서</th>
                        <th>|&nbsp;&nbsp;사용여부</th>
                        <th>
                            <div class="control-box">
                                <button type="button" class="add-position-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="positionList"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
=======
<?php 
$additional_css = '<link rel="stylesheet" href="css/employee.css">';
include 'header.php';
// 권한 체크
if (!isset($_SESSION['auth']) || $_SESSION['auth'] < 5) {
    echo "<script>
        alert('접근 권한이 없습니다.');
        window.location.href = 'main.php';
    </script>";
    exit;
}
?>

<div class="container">
    <div class="tab">
        <table>
            <thead>
                <tr>
                    <th class="stat-tab active" data-type="employee">|&nbsp;&nbsp;사원관리</th>
                    <th class="stat-tab" data-type="department">|&nbsp;&nbsp;부서관리</th>
                    <th class="stat-tab" data-type="position">|&nbsp;&nbsp;직위관리</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- 사원관리 섹션 -->
    <div id="employeeSection" class="section-content">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;성명</th>
                        <th>|&nbsp;&nbsp;사원 ID</th>
                        <th>|&nbsp;&nbsp;부서명</th>
                        <th>|&nbsp;&nbsp;직위</th>
                        <th>|&nbsp;&nbsp;이메일</th>
                        <th>|&nbsp;&nbsp;연락처</th>
                        <th>|&nbsp;&nbsp;입사일</th>
                        <th>|&nbsp;&nbsp;최종접속일</th>
                        <th>|&nbsp;&nbsp;상태</th>
                        <th>|&nbsp;&nbsp;권한</th>
                        <th>
                            <div class="control-box">
                                <select class="status-select filter-select">
                                    <option value="">전체</option>
                                    <option value="재직">재직</option>
                                    <option value="휴직">휴직</option>
                                    <option value="퇴사">퇴사</option>
                                </select>
                                <button type="button" class="add-employee-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="employeeList"></tbody>
            </table>
            <div class="search-box">
                <input type="text" placeholder="이름" id="searchInput">
                <button type="button" class="search-btn">Q</button>
            </div>
            <div class="pagination">
                <button type="button" class="page-btn prev-btn" disabled>&lt;</button>
                <div class="page-numbers"></div>
                <button type="button" class="page-btn next-btn">&gt;</button>
            </div>
        </div>
    </div>

    <!-- 부서관리 섹션 -->
    <div id="departmentSection" class="section-content" style="display: none;">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;부서명</th>
                        <th>|&nbsp;&nbsp;부서장</th>
                        <th>|&nbsp;&nbsp;사용여부</th>
                        <th>
                            <div class="control-box">
                                <button type="button" class="add-dept-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="departmentList"></tbody>
            </table>
        </div>
    </div>

    <!-- 직위관리 섹션 -->
    <div id="positionSection" class="section-content" style="display: none;">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>|&nbsp;&nbsp;직위명</th>
                        <th>|&nbsp;&nbsp;순서</th>
                        <th>|&nbsp;&nbsp;사용여부</th>
                        <th>
                            <div class="control-box">
                                <button type="button" class="add-position-btn">추가</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="positionList"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
>>>>>>> 719d7c8 (Delete all files)
<script src="js/employee.js"></script>