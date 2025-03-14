<?php
// api/application_bankruptcy/living_status/living_status_api.php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

// 디버깅을 위한 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['employee_no'])) {
   echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
   exit;
}

$case_no = isset($_GET['case_no']) ? $_GET['case_no'] : (isset($_POST['case_no']) ? $_POST['case_no'] : null);

if (!$case_no) {
   echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
   exit;
}

try {
   // GET 요청 처리 - 데이터 조회
   if ($_SERVER['REQUEST_METHOD'] === 'GET') {
   	// 기본 정보 조회
   	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
   	$stmt->execute(['case_no' => $case_no]);
   	$basicData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

   	// 수입 정보 조회
   	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_income WHERE case_no = :case_no");
   	$stmt->execute(['case_no' => $case_no]);
   	$incomeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

   	// 추가 정보 조회
   	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_additional WHERE case_no = :case_no");
   	$stmt->execute(['case_no' => $case_no]);
   	$additionalData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

   	// 세금 정보 조회
   	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_tax WHERE case_no = :case_no");
   	$stmt->execute(['case_no' => $case_no]);
   	$taxData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

   	// 가족 구성원 정보 조회
   	$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_family WHERE case_no = :case_no ORDER BY member_id");
   	$stmt->execute(['case_no' => $case_no]);
   	$familyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

   	// 데이터 병합
   	$data = array_merge(
		$basicData ?: [], 
		$incomeData ?: [], 
		$additionalData ?: [], 
		$taxData ?: []
	);
   	$data['family_members'] = $familyData ?: [];

   	echo json_encode(['success' => true, 'data' => $data]);
   	exit;
   }
   
   // POST 요청 처리 - 데이터 저장 및 삭제
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   	// 가족 구성원 삭제 처리
   	if (isset($_POST['member_id']) && isset($_POST['action']) && $_POST['action'] == 'delete_family_member') {
   		$stmt = $pdo->prepare("DELETE FROM application_bankruptcy_living_status_family WHERE member_id = :member_id AND case_no = :case_no");
   		$result = $stmt->execute([
   			'member_id' => $_POST['member_id'],
   			'case_no' => $case_no
   		]);
   		
   		echo json_encode(['success' => $result, 'message' => $result ? '삭제되었습니다.' : '삭제 중 오류가 발생했습니다.']);
   		exit;
   	}
   	
   	// 가족 구성원 저장 처리
   	if (isset($_POST['name']) && isset($_POST['relation'])) {
   		$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;
   		
   		$data = [
   			'case_no' => $case_no,
   			'name' => $_POST['name'],
   			'relation' => $_POST['relation'],
   			'age' => $_POST['age'] ?? '',
   			'job' => $_POST['job'] ?? '',
   			'income' => (int)($_POST['income'] ?? 0)
   		];
   		
   		// 기존 데이터 확인
   		if ($memberId) {
   			$checkStmt = $pdo->prepare("SELECT member_id FROM application_bankruptcy_living_status_family WHERE member_id = :member_id");
   			$checkStmt->execute(['member_id' => $memberId]);
   			$exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
   			
   			if ($exists) {
   				// UPDATE
   				$data['member_id'] = $memberId;
   				$sql = "UPDATE application_bankruptcy_living_status_family 
   						SET name = :name, relation = :relation, age = :age, job = :job, income = :income
   						WHERE member_id = :member_id";
   			} else {
   				// INSERT
   				$sql = "INSERT INTO application_bankruptcy_living_status_family 
   						(case_no, name, relation, age, job, income) 
   						VALUES (:case_no, :name, :relation, :age, :job, :income)";
   			}
   		} else {
   			// 새 데이터 INSERT
   			$sql = "INSERT INTO application_bankruptcy_living_status_family 
   					(case_no, name, relation, age, job, income) 
   					VALUES (:case_no, :name, :relation, :age, :job, :income)";
   		}
   		
   		$stmt = $pdo->prepare($sql);
   		$result = $stmt->execute($data);
   		
   		if ($result) {
   			// 새 멤버 ID 반환
   			if (!$memberId || !$exists) {
   				$memberId = $pdo->lastInsertId();
   			}
   			echo json_encode(['success' => true, 'message' => '저장되었습니다.', 'data' => ['member_id' => $memberId]]);
   		} else {
   			echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
   		}
   		exit;
   	}
   	
   	// 기본 정보 저장
   	if (isset($_POST['job_type']) || isset($_POST['job_industry'])) {
   		$stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
   		$stmt->execute(['case_no' => $case_no]);
   		$exists = $stmt->fetch(PDO::FETCH_ASSOC);
   		
   		$data = [
   			'case_no' => $case_no,
   			'job_type' => $_POST['job_type'] ?? '',
   			'job_industry' => $_POST['job_industry'] ?? '',
   			'company_name' => $_POST['company_name'] ?? '',
   			'employment_period' => $_POST['employment_period'] ?? '',
   			'job_position' => $_POST['job_position'] ?? ''
   		];
   		
   		if ($exists) {
   			$sql = "UPDATE application_bankruptcy_living_status_basic 
   					SET job_type = :job_type, job_industry = :job_industry, 
   					company_name = :company_name, employment_period = :employment_period,
   					job_position = :job_position 
   					WHERE case_no = :case_no";
   		} else {
   			$sql = "INSERT INTO application_bankruptcy_living_status_basic 
   					(case_no, job_type, job_industry, company_name, employment_period, job_position) 
   					VALUES (:case_no, :job_type, :job_industry, :company_name, :employment_period, :job_position)";
   		}
   		
   		$stmt = $pdo->prepare($sql);
   		$result = $stmt->execute($data);
   		
   		echo json_encode(['success' => $result, 'message' => $result ? '저장되었습니다.' : '저장 중 오류가 발생했습니다.']);
   		exit;
   	}
   	
   	// 수입 정보 저장
   	if (isset($_POST['self_income']) || isset($_POST['monthly_salary'])) {
   		$stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_living_status_income WHERE case_no = :case_no");
   		$stmt->execute(['case_no' => $case_no]);
   		$exists = $stmt->fetch(PDO::FETCH_ASSOC);
   		
   		$data = [
   			'case_no' => $case_no,
   			'self_income' => (int)($_POST['self_income'] ?? 0),
   			'monthly_salary' => (int)($_POST['monthly_salary'] ?? 0),
   			'pension' => (int)($_POST['pension'] ?? 0),
   			'living_support' => (int)($_POST['living_support'] ?? 0),
   			'other_income' => (int)($_POST['other_income'] ?? 0)
   		];
   		
   		if ($exists) {
   			$sql = "UPDATE application_bankruptcy_living_status_income 
   					SET self_income = :self_income, monthly_salary = :monthly_salary, 
   					pension = :pension, living_support = :living_support,
   					other_income = :other_income
   					WHERE case_no = :case_no";
   		} else {
   			$sql = "INSERT INTO application_bankruptcy_living_status_income 
   					(case_no, self_income, monthly_salary, pension, living_support, other_income) 
   					VALUES (:case_no, :self_income, :monthly_salary, :pension, :living_support, :other_income)";
   		}
   		
   		$stmt = $pdo->prepare($sql);
   		$result = $stmt->execute($data);
   		
   		echo json_encode(['success' => $result, 'message' => $result ? '저장되었습니다.' : '저장 중 오류가 발생했습니다.']);
   		exit;
   	}
   	
   	// 추가 정보 저장
   	if (isset($_POST['living_start_date']) || isset($_POST['family_status'])) {
   		$stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_living_status_additional WHERE case_no = :case_no");
   		$stmt->execute(['case_no' => $case_no]);
   		$exists = $stmt->fetch(PDO::FETCH_ASSOC);
   		
   		$data = [
   			'case_no' => $case_no,
   			'living_start_date' => $_POST['living_start_date'] ?? '',
   			'family_status' => $_POST['family_status'] ?? '',
   			'family_status_etc' => $_POST['family_status_etc'] ?? '',
   			'monthly_rent' => (int)($_POST['monthly_rent'] ?? 0),
   			'rent_deposit' => (int)($_POST['rent_deposit'] ?? 0),
   			'rent_arrears' => (int)($_POST['rent_arrears'] ?? 0),
   			'tenant_name' => $_POST['tenant_name'] ?? '',
   			'tenant_relation' => $_POST['tenant_relation'] ?? '',
   			'owner_name' => $_POST['owner_name'] ?? '',
   			'owner_relation' => $_POST['owner_relation'] ?? '',
   			'residence_reason' => $_POST['residence_reason'] ?? ''
   		];
   		
   		if ($exists) {
   			$sql = "UPDATE application_bankruptcy_living_status_additional 
   					SET living_start_date = :living_start_date, family_status = :family_status, 
   					family_status_etc = :family_status_etc, monthly_rent = :monthly_rent,
   					rent_deposit = :rent_deposit, rent_arrears = :rent_arrears,
   					tenant_name = :tenant_name, tenant_relation = :tenant_relation,
   					owner_name = :owner_name, owner_relation = :owner_relation,
   					residence_reason = :residence_reason
   					WHERE case_no = :case_no";
   		} else {
   			$sql = "INSERT INTO application_bankruptcy_living_status_additional 
   					(case_no, living_start_date, family_status, family_status_etc, monthly_rent, 
   					rent_deposit, rent_arrears, tenant_name, tenant_relation, owner_name, 
   					owner_relation, residence_reason) 
   					VALUES (:case_no, :living_start_date, :family_status, :family_status_etc, 
   					:monthly_rent, :rent_deposit, :rent_arrears, :tenant_name, :tenant_relation, 
   					:owner_name, :owner_relation, :residence_reason)";
   		}
   		
   		$stmt = $pdo->prepare($sql);
   		$result = $stmt->execute($data);
   		
   		echo json_encode(['success' => $result, 'message' => $result ? '저장되었습니다.' : '저장 중 오류가 발생했습니다.']);
   		exit;
   	}
   	
   	// 세금 정보 저장
   	if (isset($_POST['income_tax_status']) || isset($_POST['income_tax_amount'])) {
   		$stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_living_status_tax WHERE case_no = :case_no");
   		$stmt->execute(['case_no' => $case_no]);
   		$exists = $stmt->fetch(PDO::FETCH_ASSOC);
   		
   		$taxTypes = ['income_tax', 'residence_tax', 'property_tax', 'pension_tax', 'car_tax', 'other_tax', 'health_insurance'];
   		
   		$data = ['case_no' => $case_no];
   		$fields = [];
   		$updateParams = [];
   		
   		foreach ($taxTypes as $type) {
   			$data["{$type}_status"] = $_POST["{$type}_status"] ?? '';
   			$data["{$type}_amount"] = (int)($_POST["{$type}_amount"] ?? 0);
   			
   			if ($exists) {
   				$updateParams[] = "{$type}_status = :{$type}_status";
   				$updateParams[] = "{$type}_amount = :{$type}_amount";
   			} else {
   				$fields[] = "{$type}_status";
   				$fields[] = "{$type}_amount";
   			}
   		}
   		
   		if ($exists) {
   			$sql = "UPDATE application_bankruptcy_living_status_tax SET " . implode(", ", $updateParams) . " WHERE case_no = :case_no";
   		} else {
   			$placeholders = array_map(function($field) { return ":{$field}"; }, $fields);
   			$sql = "INSERT INTO application_bankruptcy_living_status_tax (case_no, " . implode(", ", $fields) . ") 
   					VALUES (:case_no, " . implode(", ", $placeholders) . ")";
   		}
   		
   		$stmt = $pdo->prepare($sql);
   		$result = $stmt->execute($data);
   		
   		echo json_encode(['success' => $result, 'message' => $result ? '저장되었습니다.' : '저장 중 오류가 발생했습니다.']);
   		exit;
   	}
   	
   	// 처리할 요청이 없는 경우
   	echo json_encode(['success' => false, 'message' => '유효하지 않은 요청입니다.']);
   }
} catch (PDOException $e) {
   error_log('Database error: ' . $e->getMessage());
   echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()]);
}
?>