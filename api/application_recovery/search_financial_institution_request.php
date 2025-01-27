<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config.php';

if (!isset($_SESSION['employee_no'])) {
    exit("권한이 없습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            INSERT INTO application_recovery_financial_institution_requests 
            (original_institution_no, name, postal_code, address, phone, fax, request_employee_no, request_date) 
            VALUES 
            (:original_institution_no, :name, :postal_code, :address, :phone, :fax, :employee_no, NOW())
        ");
        
        $stmt->execute([
            ':original_institution_no' => $data['original_institution_no'],
            ':name' => $data['name'],
            ':postal_code' => $data['postal_code'],
            ':address' => $data['address'],
            ':phone' => $data['phone'],
            ':fax' => $data['fax'],
            ':employee_no' => $_SESSION['employee_no']
        ]);

        echo json_encode(['success' => true]);
        exit;
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '데이터 저장 중 오류가 발생했습니다.'
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
    <title>채권자 추가(수정)요청</title>
    <link rel="stylesheet" href="../../css/search_financial_institution.css">
</head>
<body>
    <div class="container">
        <div class="header">채권자 추가(수정)요청</div>
        <form id="requestForm">
            <input type="hidden" id="originalInstitutionNo">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>채권자명</th>
                            <th>주소</th>
                            <th>우편번호</th>
                            <th>전화</th>
                            <th>팩스</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="text" id="requestName" required></td>
                            <td><input type="text" id="requestAddress"></td>
                            <td><input type="text" id="requestPostalCode"></td>
                            <td><input type="text" id="requestPhone" placeholder="02-1234-5678"></td>
                            <td><input type="text" id="requestFax" placeholder="02-1234-5678"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="button-area">
                <button type="submit" class="btn">요청</button>
                <button type="button" class="btn btn-cancel" onclick="window.close()">취소</button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // URL 파라미터에서 기관 정보 가져오기
        const params = new URLSearchParams(window.location.search);
        const institutionData = params.get('data');
        
        if (institutionData) {
            const data = JSON.parse(decodeURIComponent(institutionData));
            $('#originalInstitutionNo').val(data.institution_no || '');
            $('#requestName').val(data.name || '');
            $('#requestPostalCode').val(data.postal_code || '');
            $('#requestAddress').val(data.address || '');
            $('#requestPhone').val(formatPhoneNumber(data.phone || ''));
            $('#requestFax').val(data.fax || '');
        }

        // 전화번호 포맷팅 함수
        function formatPhoneNumber(value) {
            if (!value) return '';
            value = value.replace(/[^\d]/g, '');
            if (value.length <= 3) {
                return value;
            } else if (value.length <= 7) {
                return value.slice(0, 3) + '-' + value.slice(3);
            } else {
                return value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
            }
        }

        // 전화번호 입력 이벤트
        $('#requestPhone, #requestFax').on('input', function() {
            let input = $(this);
            let value = input.val();
            let formatted = formatPhoneNumber(value);
            let cursorPos = this.selectionStart;
            let beforeLength = value.length;
            
            input.val(formatted);
            
            if (formatted.length > beforeLength) {
                cursorPos++;
            }
            this.setSelectionRange(cursorPos, cursorPos);
        });

        // 폼 제출
        $('#requestForm').submit(function(e) {
            e.preventDefault();
            
            const requestData = {
                original_institution_no: $('#originalInstitutionNo').val(),
                name: $('#requestName').val(),
                postal_code: $('#requestPostalCode').val(),
                address: $('#requestAddress').val(),
                phone: $('#requestPhone').val(),
                fax: $('#requestFax').val()
            };

            $.ajax({
                url: 'search_financial_institution_request.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(requestData),
                success: function(response) {
                    if(response.success) {
                        alert('수정요청이 등록되었습니다.');
                        window.close();
                    } else {
                        alert(response.message || '요청 처리 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    alert('서버 통신 중 오류가 발생했습니다.');
                }
            });
        });
    });
    </script>
</body>
</html>