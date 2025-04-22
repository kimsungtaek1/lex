<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// 세션 체크
if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
	exit;
}

// 요청 메소드와 액션 파라미터 확인
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET 요청 처리 (데이터 조회)
if ($method === 'GET') {
	switch ($action) {
		case 'list':
			getGuarantors();
			break;
		case 'count':
			getGuarantorCount();
			break;
		default:
			echo json_encode(['success' => false, 'message' => '유효하지 않은 액션입니다.']);
	}
}
// POST 요청 처리 (데이터 저장/삭제)
else if ($method === 'POST') {
    // action 파라미터가 없으면 기본적으로 save로 간주
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'save');
    switch ($action) {
        case 'save':
            saveGuarantor();
            break;
        case 'delete':
            deleteGuarantor();
            break;
        default:
            echo json_encode(['success' => false, 'message' => '유효하지 않은 액션입니다.']);
    }
}
else {
	echo json_encode(['success' => false, 'message' => '지원하지 않는 메소드입니다.']);
}

// guarantor_no 파싱 함수 ("3-1" → 1, "5" → 5)
function parse_guarantor_no($raw) {
    if (strpos($raw, '-') !== false) {
        $parts = explode('-', $raw);
        return intval($parts[1]);
    } else {
        return intval($raw);
    }
}

// 보증인 목록 조회 함수
function getGuarantors() {
	global $pdo;
	
	$case_no = $_GET['case_no'] ?? 0;
	$creditor_count = $_GET['creditor_count'] ?? 0;
	$guarantor_no_raw = $_GET['guarantor_no'] ?? null;
	$guarantor_no = $guarantor_no_raw ? parse_guarantor_no($guarantor_no_raw) : null;

	if (!$case_no || !$creditor_count) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$sql = "SELECT * FROM application_bankruptcy_creditor_guaranteed_debts 
				WHERE case_no = ? AND creditor_count = ?";
		$params = [$case_no, $creditor_count];
		
		// 특정 보증인 조회인 경우
		if ($guarantor_no) {
			$sql .= " AND guarantor_no = ?";
			$params[] = $guarantor_no;
		}
		
		$sql .= " ORDER BY guarantor_no ASC";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		$guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// null 값을 빈 문자열로 변환
		foreach ($guarantors as &$guarantor) {
			foreach ($guarantor as $key => $value) {
				if (is_null($value)) {
					$guarantor[$key] = '';
				}
			}
		}
		unset($guarantor);

		echo json_encode([
			'success' => true,
			'data' => $guarantors
		]);

	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'message' => '보증인 정보를 불러오는 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}

// 보증인 수 조회 함수
function getGuarantorCount() {
	global $pdo;
	
	$case_no = $_GET['case_no'] ?? 0;
	$creditor_count = $_GET['creditor_count'] ?? 0;

	if (!$case_no || !$creditor_count) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("
			SELECT COUNT(*) as count
			FROM application_bankruptcy_creditor_guaranteed_debts
			WHERE case_no = ? AND creditor_count = ?
		");
		
		$stmt->execute([$case_no, $creditor_count]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			'success' => true,
			'count' => $result['count']
		]);

	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'message' => '조회 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}

// 보증인 저장 함수
function saveGuarantor() {
    global $pdo;
    
    $case_no = $_POST['case_no'] ?? null;
    $creditor_count = $_POST['creditor_count'] ?? null;
    $guarantor_name = $_POST['guarantor_name'] ?? null;

    if (!$case_no || !$creditor_count || !$guarantor_name) {
        echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // 금액 데이터 전처리
        $guarantee_amount = !empty($_POST['guarantee_amount']) ? str_replace(',', '', $_POST['guarantee_amount']) : '0';

        // 숫자 유효성 검사
        if (!is_numeric($guarantee_amount)) {
            throw new Exception("유효하지 않은 금액이 입력되었습니다.");
        }

        // 날짜 데이터 전처리
        $guarantee_date_raw = $_POST['guarantee_date'] ?? null;
        $guarantee_date = ($guarantee_date_raw === '' || $guarantee_date_raw === null) ? null : $guarantee_date_raw;

        // guarantor_no 파싱 및 실제 존재여부 확인
        $guarantor_no_raw = $_POST['guarantor_no'] ?? null;
        $guarantor_no = $guarantor_no_raw ? parse_guarantor_no($guarantor_no_raw) : null;
        $exists = false;
        if ($guarantor_no && $case_no && $creditor_count) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM application_bankruptcy_creditor_guaranteed_debts WHERE guarantor_no = ? AND case_no = ? AND creditor_count = ?");
            $checkStmt->execute([$guarantor_no, $case_no, $creditor_count]);
            $exists = $checkStmt->fetchColumn() > 0;
        }

        if ($exists) {
            // UPDATE
            $sql = "UPDATE application_bankruptcy_creditor_guaranteed_debts SET guarantor_name = ?, guarantor_address = ?, guarantor_phone = ?, guarantor_fax = ?, guarantee_amount = ?, guarantee_date = ?, difference_interest = ?, dispute_reason = ?, dispute_reason_content = ?, updated_at = CURRENT_TIMESTAMP WHERE guarantor_no = ? ";
            $params = [
                $guarantor_name,
                $_POST['guarantor_address'] ?? '',
                $_POST['guarantor_phone'] ?? '',
                $_POST['guarantor_fax'] ?? '',
                $guarantee_amount,
                $guarantee_date,
                $_POST['difference_interest'] ?? '',
                $_POST['dispute_reason'] ?? '',
                $_POST['dispute_reason_content'] ?? '',
                $guarantor_no
            ];
        } else {
            // INSERT
            $sql = " INSERT INTO application_bankruptcy_creditor_guaranteed_debts ( guarantor_no, case_no, creditor_count, guarantor_name, guarantor_address, guarantor_phone, guarantor_fax, guarantee_amount, guarantee_date, difference_interest, dispute_reason, dispute_reason_content, created_at, updated_at ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  CURRENT_TIMESTAMP, CURRENT_TIMESTAMP ) ";
            $params = [
                $guarantor_no, // POST로 받은 보증인 번호
                $case_no,
                $creditor_count,
                $guarantor_name,
                $_POST['guarantor_address'] ?? '',
                $_POST['guarantor_phone'] ?? '',
                $_POST['guarantor_fax'] ?? '',
                $guarantee_amount,
                $guarantee_date,
                $_POST['difference_interest'] ?? '',
                $_POST['dispute_reason'] ?? '',
                $_POST['dispute_reason_content'] ?? ''
            ];
        }

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result && $stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
				'message' => '보증인 정보가 저장되었습니다.',
				'param' => $params,
                'guarantor_no' => $guarantor_no ? $guarantor_no : $pdo->lastInsertId()
            ]);
        } else {
            throw new Exception("쿼리 실행 실패 또는 영향받은 행 없음.");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
        ]);
    }
}

// 보증인 삭제 함수
function deleteGuarantor() {
	global $pdo;
	
	$guarantor_no_raw = $_POST['guarantor_no'] ?? 0;
	$guarantor_no = parse_guarantor_no($guarantor_no_raw);

	if (!$guarantor_no) {
		echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
		exit;
	}

	try {
		$pdo->beginTransaction();
		
		// 보증인 삭제
		$stmt = $pdo->prepare(" DELETE FROM application_bankruptcy_creditor_guaranteed_debts  WHERE guarantor_no = ? ");
		$stmt->execute([$guarantor_no]);

		if ($stmt->rowCount() > 0) {
			$pdo->commit();
			echo json_encode([
				'success' => true,
				'message' => '삭제되었습니다.'
			]);
		} else {
			throw new Exception("삭제 실패 또는 영향받은 행 없음.");
		}

	} catch (Exception $e) {
		$pdo->rollBack();
		echo json_encode([
			'success' => false,
			'message' => '삭제 중 오류가 발생했습니다.',
			'error' => $e->getMessage()
		]);
	}
}