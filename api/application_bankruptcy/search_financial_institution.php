<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

// AJAX 검색 요청 처리
if(isset($_GET['ajax_search'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;

    try {
        $query = "SELECT * FROM application_recovery_financial_institutions";
        $countQuery = "SELECT COUNT(*) FROM application_recovery_financial_institutions";
        $params = array();

        if(!empty($search)) {
            $query .= " WHERE name LIKE :search";
            $countQuery .= " WHERE name LIKE :search";
            $params[':search'] = "%$search%";
        }

        $query .= " ORDER BY name ASC LIMIT :offset, :perPage";

        // 전체 개수 조회
        $stmt = $pdo->prepare($countQuery);
        if(!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $totalCount = $stmt->fetchColumn();

        // 데이터 조회
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $institutions,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage)
        ]);
        exit;
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '데이터 조회 중 오류가 발생했습니다.'
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>금융기관검색</title>
	<link rel="stylesheet" href="../../css/search_financial_institution.css">
</head>
<body>
    <div class="container">
        <div class="header">금융기관검색</div>
        
        <div class="search-area">
            <div class="search-box">
                <div class="search-box-name">금융기관명</div>
                <input type="text" id="searchInput" name="search" placeholder="금융기관명을 입력하세요">
                <button type="button" class="btn btn-search" id="searchButton">검색</button>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>등록번호</th>
                        <th>금융기관명</th>
                        <th>주소</th>
                        <th>우편번호</th>
                        <th>전화</th>
                        <th>팩스</th>
                        <th>수정요청</th>
                    </tr>
                </thead>
                <tbody id="institutionList"></tbody>
            </table>
        </div>
        
        <div class="pagination-container">
            <div class="pagination"></div>
        </div>

        <div class="notice-area">
			<div class="notice">
				※ 최근 변경 사항이 있을 수 있으니 반드시 내용을 확인하시기 바랍니다.<br>
				※ 등록된 채권자가 없는 경우에는 추가 요청을 해주세요.
			</div>
			<button type="button" class="btn btn-add" onclick="openRequestModal({})">채권자추가요청</button>
		</div>

		<div class="button-area">
			<button type="button" class="btn btn-close" onclick="window.close()">닫기</button>
		</div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        let currentPage = 1;
        
        // URL 파라미터 추출 함수
        function getUrlParam(name) {
            const url = new URL(window.location.href);
            return url.searchParams.get(name);
        }
        const source = getUrlParam('source');
        const count = getUrlParam('count');

        // 금융기관 선택 시 호출 (institution 객체는 실제 선택된 데이터)
        function selectInstitution(institution) {
            if (window.opener) {
                if (source === 'creditor') {
                    window.opener.postMessage({
                        type: 'financialInstitutionSelectedForCreditor',
                        institution: institution,
                        count: count
                    }, '*');
                } else if (source === 'guarantor') {
                    window.opener.postMessage({
                        type: 'financialInstitutionSelectedForGuarantor',
                        institution: institution,
                        count: count
                    }, '*');
                }
                window.close();
            }
        }

        // 검색 함수
        function searchInstitutions(page = 1) {
            const searchTerm = $('#searchInput').val();
            currentPage = page;
            
            $.ajax({
                url: '?ajax_search=1',
                type: 'GET',
                data: {
                    search: searchTerm,
                    page: page
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#institutionList').html('<tr><td colspan="7" style="text-align:center;">검색중...</td></tr>');
                },
                success: function(response) {
                    if(response.success) {
                        renderTable(response.data);
                        renderPagination(response.pages);
                    } else {
                        alert(response.message || '검색 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    alert('서버 통신 중 오류가 발생했습니다.');
                }
            });
        }

        // 테이블 렌더링
        function renderTable(data) {
            let html = '';
            
            // 실제 데이터 행 생성
            data.forEach(function(item) {
                html += '<tr class="clickable-row" data-institution=\'' + JSON.stringify(item) + '\'>' +
                    '<td>' + item.institution_no + '</td>' +
                    '<td>' + item.name + '</td>' +
                    '<td>' + (item.address || '') + '</td>' +
                    '<td>' + (item.postal_code || '') + '</td>' +
                    '<td>' + (item.phone || '') + '</td>' +
                    '<td>' + (item.fax || '') + '</td>' +
                    '<td><button type="button" class="btn" onclick="selectInstitution(\'' + 
                        encodeURIComponent(JSON.stringify(item)) + '\')">수정요청</button></td>' +
                    '</tr>';
            });
            
            // 빈 행 추가
            const emptyRows = 15 - data.length;
            for (let i = 0; i < emptyRows; i++) {
                html += '<tr class="empty-row">' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '<td>&nbsp;</td>' +
                    '</tr>';
            }
            
            $('#institutionList').html(html);

            // 행 클릭 이벤트
            $('.clickable-row').click(function(e) {
                if(!$(e.target).is('button')) {
                    const institution = $(this).data('institution');
                    selectInstitution(institution);
                }
            });
        }

        // 페이지네이션 렌더링
        function renderPagination(totalPages) {
            let html = '';
            for(let i = 1; i <= totalPages; i++) {
                html += '<button type="button" class="page-btn ' + (i === currentPage ? 'active' : '') + 
                       '" data-page="' + i + '">' + i + '</button>';
            }
            $('.pagination').html(html);
        }

        // 검색 버튼 클릭 이벤트
        $('#searchButton').click(function() {
            searchInstitutions(1);
        });

        // 엔터키 검색 이벤트
        $('#searchInput').keypress(function(e) {
            if(e.which == 13) {
                e.preventDefault();
                searchInstitutions(1);
            }
        });

        // 페이지네이션 클릭 이벤트
        $(document).on('click', '.page-btn', function() {
            const page = $(this).data('page');
            searchInstitutions(page);
        });

        // 초기 검색
		searchInstitutions(1);
   });

   // 모달 열기 함수
	function openRequestModal(institutionData) {
		if (typeof institutionData === 'string') {
			institutionData = JSON.parse(decodeURIComponent(institutionData));
		}
		const url = 'search_financial_institution_request.php?data=' + encodeURIComponent(JSON.stringify(institutionData));
		window.open(url, 'requestWindow', 'width=1200,height=400');
	}
   </script>
</body>
</html>