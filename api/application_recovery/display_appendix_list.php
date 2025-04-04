<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>동일 목적물 선택</title>
    <link rel="stylesheet" href="../../css/appendix.css"> <!-- appendix.css 재사용 -->
    <style>
        body { padding: 20px; font-family: sans-serif; }
        .appendix-table { margin-top: 15px; }
        .table-header, .table-row { display: flex; border-bottom: 1px solid #eee; padding: 8px 0; }
        .table-header { font-weight: bold; background-color: #f8f8f8; border-top: 1px solid #ddd; }
        .col { flex: 1; padding: 0 5px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .col:first-child { flex: 0 0 80px; } /* 채권번호 */
        .col:nth-child(2) { flex: 2; text-align: left; } /* 목적물 */
        .col:nth-child(3), .col:nth-child(4), .col:nth-child(5), .col:nth-child(6), .col:nth-child(7) { flex: 1; text-align: right; } /* 금액/비율 */
        .col-action { flex: 0 0 80px; } /* 선택 버튼 */
        .btn-select { padding: 3px 8px; cursor: pointer; }
        .no-data { text-align: center; padding: 20px; color: #888; }
    </style>
</head>
<body>
    <h2>동일 목적물 선택</h2>
    <p>기존에 입력된 부속서류 목록입니다. 동일한 목적물을 사용하는 경우 '선택' 버튼을 클릭하세요.</p>

    <?php
    include '../../config.php';

    $case_no = isset($_GET['case_no']) ? (int)$_GET['case_no'] : 0;
    $current_appendix_no = isset($_GET['current_appendix_no']) ? (int)$_GET['current_appendix_no'] : 0; // 현재 편집 중인 부속서류 번호

    if ($case_no === 0) {
        echo '<div class="no-data">사건 번호가 유효하지 않습니다.</div>';
        exit;
    }

    try {
        // get_appendix.php 로직을 직접 포함하거나 API 호출 대신 DB 쿼리 실행
        $sql = "SELECT 
                    m.*,
                    c.financial_institution AS creditor_name
                FROM application_recovery_creditor_appendix m
                LEFT JOIN application_recovery_creditor c
                  ON m.case_no = c.case_no
                  AND m.creditor_count = c.creditor_count
                WHERE m.case_no = ?
                ORDER BY m.creditor_count, m.appendix_no"; // 정렬 기준 추가

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$case_no]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            echo '<div class="appendix-table">';
            echo '<div class="table-header">';
            echo '<div class="col">채권번호</div>';
            echo '<div class="col">목적물</div>';
            echo '<div class="col">환가예상액</div>';
            echo '<div class="col">평가비율</div>';
            echo '<div class="col">예상채권액</div>';
            echo '<div class="col">없을채권액</div>';
            echo '<div class="col">회생채권액</div>';
            echo '<div class="col-action">선택</div>';
            echo '</div>';

            foreach ($results as $row) {
                // 현재 편집 중인 부속서류는 목록에서 제외 (선택적으로)
                // if ($row['appendix_no'] == $current_appendix_no) continue;

                echo '<div class="table-row" data-appendix=\''.htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8').'\'>';
                echo '<div class="col">' . htmlspecialchars($row['creditor_count'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<div class="col">' . htmlspecialchars($row['property_detail'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<div class="col">' . number_format($row['expected_value'] ?? 0) . '</div>';
                echo '<div class="col">' . htmlspecialchars($row['evaluation_rate'] ?? '', ENT_QUOTES, 'UTF-8') . '%</div>';
                echo '<div class="col">' . number_format($row['secured_expected_claim'] ?? 0) . '</div>';
                echo '<div class="col">' . number_format($row['unsecured_remaining_claim'] ?? 0) . '</div>';
                echo '<div class="col">' . number_format($row['rehabilitation_secured_claim'] ?? 0) . '</div>';
                echo '<div class="col-action"><button type="button" class="btn-select">선택</button></div>';
                echo '</div>';
            }
            echo '</div>'; // appendix-table end
        } else {
            echo '<div class="no-data">기존에 입력된 부속서류 데이터가 없습니다.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="no-data">데이터를 불러오는 중 오류가 발생했습니다: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectButtons = document.querySelectorAll('.btn-select');
            selectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('.table-row');
                    const appendixDataString = row.getAttribute('data-appendix');
                    if (appendixDataString) {
                        try {
                            const selectedAppendix = JSON.parse(appendixDataString);
                            // console.log("Selected Appendix Data:", selectedAppendix); // 디버깅용 로그

                            // 부모 창으로 데이터 전송
                            if (window.opener && !window.opener.closed) {
                                window.opener.postMessage({
                                    type: 'appendixItemSelected',
                                    selectedAppendix: selectedAppendix
                                }, '*'); // 보안을 위해 특정 origin 지정 권장
                                window.close(); // 팝업 창 닫기
                            } else {
                                alert('부모 창을 찾을 수 없습니다.');
                            }
                        } catch (e) {
                            console.error('데이터 파싱 오류:', e);
                            alert('데이터 처리 중 오류가 발생했습니다.');
                        }
                    } else {
                        alert('선택된 항목의 데이터를 찾을 수 없습니다.');
                    }
                });
            });
        });
    </script>
</body>
</html>
