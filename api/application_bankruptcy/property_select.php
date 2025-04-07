<?php
require_once __DIR__ . '/../../config.php';

$caseNo = $_GET['case_no'] ?? null;
$count = $_GET['count'] ?? null;
if (!$caseNo) {
    die('Invalid case number');
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>목적물 선택</title>
    <link rel="stylesheet" href="../../css/appendix.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="content-wrapper">
        <div class="appendix-table">
            <div class="table-header">
                <div class="col">|&nbsp;&nbsp;채권번호</div>
                <div class="col">|&nbsp;&nbsp;채권자명</div>
                <div class="col">|&nbsp;&nbsp;목적물</div>
                <div class="col">|&nbsp;&nbsp;선택</div>
            </div>
            <div id="property-list">
                <!-- 목적물 데이터가 여기에 로드됩니다 -->
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const caseNo = "<?= $caseNo ?>";
        const appendix_no = "<?= $count ?>";
        // 목적물 데이터 로드
        $.ajax({
            url: 'get_appendix.php',
            method: 'GET',
            data: { case_no: caseNo,
                appendix_no: appendix_no
             },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success && response.data && response.data.length > 0) {
                    renderProperties(response.data);
                } else {
                    console.log('목적물 데이터가 없습니다:', response.message || '');
                }
            },
            error: function(xhr) {
                console.error('서버 오류:', xhr.responseText);
            }
        });

        // 선택 버튼 클릭 이벤트
        $(document).on('click', '.select-btn', function() {
            const propertyId = $(this).data('id');
            window.opener.postMessage({
                type: 'propertySelected',
                propertyId: propertyId
            }, '*');
            window.close();
        });
    });

    function renderProperties(properties) {
        const container = $('#property-list');
        container.empty();

        properties.forEach(property => {
            const row = `
                <div class="table-row">
                    <div class="col">${property.appendix_no || ''}</div>
                    <div class="col">${property.creditor_name || ''}</div>
                    <div class="col">${property.property_detail || ''}</div>
                    <div class="col">
                        <button type="button" 
                                class="select-btn" 
                                data-id="${property.id}">
                            선택
                        </button>
                    </div>
                </div>`;
            container.append(row);
        });
    }
    </script>
</body>
</html>
