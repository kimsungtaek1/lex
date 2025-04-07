<?php
require_once '../../config.php';
require_once '../sms/component.php';
header('Content-Type: application/json');

function isValidProspect($prospect) {
    $validProspects = ['부재', '불가', '낮음', '높음'];
    return in_array($prospect, $validProspects);
}

function validatePhoneNumber($phone) {
    if (empty($phone)) return true;
    return preg_match('/^01[016789]-\d{3,4}-\d{4}$/', $phone);
}

function validateDate($date) {
    if (empty($date)) return true;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateDateTime($dateTime) {
    if (empty($dateTime)) return true;
    return (bool)strtotime($dateTime);
}

try {
    if (!isset($_POST['consult_no'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    writeLog('Received POST data: ' . print_r($_POST, true));

    // POST 데이터 유효성 검사 및 가공
    $consult_no = $_POST['consult_no'];
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $consultant = !empty($_POST['consultant']) ? $_POST['consultant'] : null;
    $paper = !empty($_POST['paper']) ? $_POST['paper'] : null;
    $prospect = isset($_POST['prospect']) && isValidProspect($_POST['prospect']) ? $_POST['prospect'] : null;
    
    $datetime = isset($_POST['datetime']) ? trim($_POST['datetime']) : null;
    $region = isset($_POST['region']) ? trim($_POST['region']) : '';
    $birth_date = !empty($_POST['birth_date']) ? date('Y-m-d', strtotime($_POST['birth_date'])) : null;
    $debt_amount = isset($_POST['debt_amount']) ? trim($_POST['debt_amount']) : '';
    $consultation_time = isset($_POST['consultation_time']) ? trim($_POST['consultation_time']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    // 기본 데이터 유효성 검사
    if (empty($name)) {
        throw new Exception('이름은 필수 입력 항목입니다.');
    }
    if (empty($phone)) {
        throw new Exception('연락처는 필수 입력 항목입니다.');
    }
    if (empty($category)) {
        throw new Exception('상담분야는 필수 선택 항목입니다.');
    }
    if (!validatePhoneNumber($phone)) {
        throw new Exception('올바른 전화번호 형식이 아닙니다.');
    }
    if (!validateDate($birth_date)) {
        throw new Exception('올바른 생년월일 형식이 아닙니다.');
    }
    if (!validateDateTime($datetime)) {
        throw new Exception('올바른 상담일시 형식이 아닙니다.');
    }

    // 현재 데이터 조회
    $stmt = $pdo->prepare("SELECT * FROM consult_manager WHERE consult_no = ?");
    $stmt->execute([$consult_no]);
    $current_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_data) {
        throw new Exception('상담 데이터를 찾을 수 없습니다.');
    }

    $pdo->beginTransaction();

    try {
        // 담당자가 변경되었는지 확인
        $paper_changed = $current_data['paper'] !== $paper && !empty($paper);

        if ($paper_changed) {
            // 상담자와 담당자의 부서 확인
            $stmt = $pdo->prepare("
                SELECT 
                    e1.department as consultant_dept, 
                    e2.department as paper_dept
                FROM employee e1
                LEFT JOIN employee e2 ON e2.employee_no = :paper_id
                WHERE e1.employee_no = :consultant_id
            ");
            
            $stmt->execute([
                ':consultant_id' => $consultant,
                ':paper_id' => $paper
            ]);
            $dept_info = $stmt->fetch(PDO::FETCH_ASSOC);

            // 동일 부서 체크
            if ($dept_info['consultant_dept'] !== $dept_info['paper_dept']) {
                throw new Exception('동일한 부서의 직원에게만 배정할 수 있습니다.');
            }

            // consult_paper 테이블 확인 및 데이터 삽입
            $stmt_check = $pdo->prepare("SELECT paper_no FROM consult_paper WHERE consult_no = ? AND manager_id = ?");
            $stmt_check->execute([$consult_no, $consultant]);
            
            if (!$stmt_check->fetch()) {
                // consult_paper 테이블에 데이터 삽입
                $paper_sql = "INSERT INTO consult_paper (
                    consult_no, manager_id, category, name, phone, 
                    case_number, datetime, assign_date, status
                ) VALUES (
                    :consult_no, :manager_id, :category, :name, :phone,
                    NULL, NOW(), NOW(), '접수'
                )";

                $stmt = $pdo->prepare($paper_sql);
                $paper_result = $stmt->execute([
                    ':consult_no' => $consult_no,
                    ':manager_id' => $consultant,
                    ':category' => $category,
                    ':name' => $name,
                    ':phone' => $phone
                ]);

                // case_management 테이블 확인 및 데이터 삽입
                $stmt_case_check = $pdo->prepare("SELECT case_no FROM case_management WHERE consult_no = ?");
                $stmt_case_check->execute([$consult_no]);

                if (!$stmt_case_check->fetch()) {
                    $case_sql = "INSERT INTO case_management (
                        consult_no, name, phone, category,
                        consultant, paper, assign_date, status, datetime,
                        created_at
                    ) VALUES (
                        :consult_no, :name, :phone, :category,
                        :consultant, :paper, :assign_date, '접수', NOW(),
                        NOW()
                    )";

                    $stmt = $pdo->prepare($case_sql);
                    $case_result = $stmt->execute([
                        ':consult_no' => $consult_no,
                        ':name' => $name,
                        ':phone' => $phone,
                        ':category' => $category,
                        ':consultant' => $consultant,
                        ':paper' => $paper,
                        ':assign_date' => date('Y-m-d')
                    ]);
                }

                // 담당자에게 SMS 발송
                $stmt = $pdo->prepare("SELECT name, phone FROM employee WHERE employee_no = ?");
                $stmt->execute([$paper]);
                $paper_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($paper_info && !empty($paper_info['phone'])) {
                    $sms = new SMS();
                    $sms->SMS_con($socket_host, $socket_port, $icode_key);

                    $sms_content = "[새로운 상담 배정] {$name}님의 상담이 {$paper_info['name']}님께 배정되었습니다.";
                    $phone_number = str_replace(['-', ' '], '', $paper_info['phone']);

                    $result = $sms->Add([$phone_number], $icode_number, $sms_content, '');

                    if ($result) {
                        $send_result = $sms->Send();
                        if (!$send_result) {
                            writeLog("SMS 발송 실패: " . print_r($sms->Result, true));
                        }
                    }
                }
            }
        }

        // consult_manager 테이블 업데이트
        $sql = "UPDATE consult_manager SET 
                name = :name,
                phone = :phone,
                category = :category,
                datetime = :datetime,
                region = :region,
                birth_date = :birth_date,
                debt_amount = :debt_amount,
                consultation_time = :consultation_time,
                content = :content,
                consultant = :consultant,
                paper = :paper,
                prospect = :prospect,
                updated_at = CURRENT_TIMESTAMP
                WHERE consult_no = :consult_no";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':category' => $category,
            ':datetime' => $datetime,
            ':region' => $region,
            ':birth_date' => $birth_date,
            ':debt_amount' => $debt_amount,
            ':consultation_time' => $consultation_time,
            ':content' => $content,
            ':consultant' => $consultant,
            ':paper' => $paper,
            ':prospect' => $prospect,
            ':consult_no' => $consult_no
        ]);

        if (!$result) {
            throw new Exception('데이터 저장에 실패했습니다.');
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '저장되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    writeLog('Error in update_consult_manager.php: ' . $e->getMessage());
    writeLog('Stack trace: ' . $e->getTraceAsString());
    writeLog('POST data: ' . print_r($_POST, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}