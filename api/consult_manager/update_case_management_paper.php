<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        throw new Exception('데이터베이스 연결이 설정되지 않았습니다.');
    }

    // 필수 파라미터 확인
    if (!isset($_POST['consult_no']) || !isset($_POST['category'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

	$name = $_POST['name'];
    $consult_no = (int)$_POST['consult_no'];
    $category = $_POST['category'];
    $paper = !empty($_POST['paper_no']) ? (int)$_POST['paper_no'] : null;
    $consultant = !empty($_POST['consultant']) ? (int)$_POST['consultant'] : null;

    // consult_paper의 paper_no 조회
    $paper_no = null;
    if ($paper !== null) {
        $stmt = $pdo->prepare("
            SELECT paper_no 
            FROM consult_paper 
            WHERE consult_no = ? 
            AND manager_id = ?
        ");
        $stmt->execute([$consult_no, $consultant]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $paper_no = $result['paper_no'];
        }
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // case_management 테이블에서 해당 consult_no의 데이터 확인
        $stmt = $pdo->prepare("SELECT case_no FROM case_management WHERE consult_no = ?");
        $stmt->execute([$consult_no]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($case) {
            // 기존 데이터가 있으면 업데이트
            $sql = "UPDATE case_management 
                   SET paper = :paper,
                       paper_no = :paper_no,
                       category = :category,
                       consultant = COALESCE(:consultant, consultant),
                       updated_at = NOW()
                   WHERE consult_no = :consult_no";
            
            $params = [
                ':consult_no' => $consult_no,
                ':paper' => $paper,
                ':paper_no' => $paper_no,
                ':category' => $category,
                ':consultant' => $consultant
            ];

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            $case_no = $case['case_no'];
        } else {
            // 상담 데이터 조회
            $stmt = $pdo->prepare("
                SELECT name, phone, consultant
                FROM consult_manager 
                WHERE consult_no = ?
            ");
            $stmt->execute([$consult_no]);
            $consult_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$consult_data) {
                throw new Exception('상담 데이터를 찾을 수 없습니다.');
            }

            // case_management에 새로운 데이터 삽입
            $sql = "INSERT INTO case_management (
                        consult_no, name, phone, category,
                        consultant, paper, paper_no, assign_date, status,
                        datetime, created_at
                    ) VALUES (
                        :consult_no, :name, :phone, :category,
                        :consultant, :paper, :paper_no, NOW(), '접수',
                        NOW(), NOW()
                    )";

            $params = [
                ':consult_no' => $consult_no,
                ':name' => $name,
                ':phone' => $consult_data['phone'],
                ':consultant' => $consult_data['consultant'],
                ':paper' => $paper,
                ':paper_no' => $paper_no,
                ':category' => $category
            ];

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            $case_no = $pdo->lastInsertId();
        }

        // 카테고리에 따라 신청서 테이블 처리
        if ($paper !== null) {
            if ($category === '개인파산') {
                // application_bankruptcy 테이블 확인
                $stmt = $pdo->prepare("SELECT bankruptcy_no FROM application_bankruptcy WHERE case_no = ?");
                $stmt->execute([$case_no]);
                $bankruptcy = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$bankruptcy) {
                    // 새로운 파산 신청서 생성
                    $sql = "INSERT INTO application_bankruptcy (
                        case_no, name, phone, assigned_employee,
                        status, created_at
                    ) VALUES (
                        :case_no, :name, :phone, :assigned_employee,
                        '신청', NOW()
                    )";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':case_no' => $case_no,
                        ':name' => $name,
                        ':phone' => $consult_data['phone'],
                        ':assigned_employee' => $paper
                    ]);
                }
            } elseif ($category === '개인회생급여' || $category === '개인회생영업') {
                // application_recovery 테이블 확인
                $stmt = $pdo->prepare("SELECT recovery_no FROM application_recovery WHERE case_no = ?");
                $stmt->execute([$case_no]);
                $recovery = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$recovery) {
                    // 새로운 개인회생 신청서 생성
                    $sql = "INSERT INTO application_recovery (
                        case_no, name, phone, assigned_employee,
                        status, created_at
                    ) VALUES (
                        :case_no, :name, :phone, :assigned_employee,
                        '신청', NOW()
                    )";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':case_no' => $case_no,
                        ':name' => $name,
                        ':phone' => $consult_data['phone'],
                        ':assigned_employee' => $paper
                    ]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '사건 정보가 업데이트되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error in update_case_management_paper.php: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}