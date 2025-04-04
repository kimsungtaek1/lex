<?php
require_once __DIR__ . '/../../config.php';

$caseNo = $_GET['case_no'] ?? null;
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
            <div class="table-header property_select">
                <div class="col">|&nbsp;&nbsp;채권번호</div>
                <div class="col">|&nbsp;&nbsp;채권자명</div>
                <div class="col">|&nbsp;&nbsp;목적물</div>
                <div class="col">|&nbsp;&nbsp;선택</div>
            </div>
            <div id="property-list" class="property_select">
                <!-- 목적물 데이터가 여기에 로드됩니다 -->
            </div>
        </div>
    </div>

    <script>
    let propertiesData = []; // Store properties data globally within the script scope

    $(document).ready(function() {
        const caseNo = "<?= $caseNo ?>";
        // 목적물 데이터 로드
        $.ajax({
            url: 'get_appendix.php',
            method: 'GET',
            data: { case_no: caseNo},
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success && response.data && response.data.length > 0) {
                    propertiesData = response.data; // Store the data
                    renderProperties(propertiesData);
                } else {
                    console.log('목적물 데이터가 없습니다:', response.message || '');
                    $('#property-list').html('<div class="no-data">데이터가 없습니다</div>');
                }
            },
            error: function(xhr) {
                console.error('서버 오류:', xhr.responseText);
            }
        });

        // 선택 버튼 클릭 이벤트
        $(document).on('click', '.select-btn', function() {
            const appendix_no = $(this).data('id');
            // propertiesData 배열에서 appendix_no와 일치하는 전체 데이터 찾기
            const selectedProperty = propertiesData.find(p => p.appendix_no == appendix_no); // Use == for potential type coercion

            if (selectedProperty) {
                window.opener.postMessage({
                    type: 'propertySelected',
                    data: selectedProperty // 선택된 전체 데이터 객체 전달
                }, '*');
                window.close();
            } else {
                alert('오류: 선택된 목적물 정보를 찾을 수 없습니다.');
                console.error('Could not find property with appendix_no:', appendix_no, 'in', propertiesData);
            }
        });
    });

    function renderProperties(properties) {
        const container = $('#property-list');
        container.empty();

        properties.forEach(property => {
            const row = `
                <div class="table-row">
                    <div class="col">${property.creditor_count || ''}</div>
                    <div class="col">${property.creditor_name || ''}</div>
                    <div class="col">${property.property_detail || ''}</div>
                    <div class="col">
                        <button type="button"
                                class="select-btn"
                                data-id="${property.appendix_no}">선택
                        </button>
                    </div>
                </div>`;
            container.append(row);
        });
    }
    </script>
