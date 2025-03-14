<?php
// api/application_bankruptcy/living_status/living_status_api.php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_no'])) {
	echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
	exit;
}

// 요청된 액션 파악
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$case_no = isset($_GET['case_no']) ? $_GET['case_no'] : (isset($_POST['case_no']) ? $_POST['case_no'] : null);

if (!$case_no) {
	echo json_encode(['success' => false, 'message' => '사건 번호가 필요합니다.']);
	exit;
}

try {
	switch ($action) {
		// 기본 정보
		case 'get_basic_info':
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			echo json_encode(['success' => true, 'data' => $data]);
			break;
			
		case 'save_basic_info':
			// 기존 데이터 확인
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_basic WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$data = [
				'case_no' => $case_no,
				'job_type' => $_POST['job_type'] ?? '',
				'job_industry' => $_POST['job_industry'] ?? '',
				'company_name' => $_POST['company_name'] ?? '',
				'employment_period' => $_POST['employment_period'] ?? '',
				'job_position' => $_POST['job_position'] ?? ''
			];
			
			if ($existingData) {
				// UPDATE
				$sql = "UPDATE application_bankruptcy_living_status_basic 
						SET job_type = :job_type, 
							job_industry = :job_industry, 
							company_name = :company_name, 
							employment_period = :employment_period,
							job_position = :job_position 
						WHERE case_no = :case_no";
			} else {
				// INSERT
				$sql = "INSERT INTO application_bankruptcy_living_status_basic 
						(case_no, job_type, job_industry, company_name, employment_period, job_position) 
						VALUES (:case_no, :job_type, :job_industry, :company_name, :employment_period, :job_position)";
			}
			
			$stmt = $pdo->prepare($sql);
			$result = $stmt->execute($data);
			
			if ($result) {
				echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
			}
			break;
			
		// 수입 정보
		case 'get_income_info':
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_income WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			echo json_encode(['success' => true, 'data' => $data]);
			break;
			
		case 'save_income_info':
			// 기존 데이터 확인
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_income WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$data = [
				'case_no' => $case_no,
				'self_income' => (int)($_POST['self_income'] ?? 0),
				'monthly_salary' => (int)($_POST['monthly_salary'] ?? 0),
				'pension' => (int)($_POST['pension'] ?? 0),
				'living_support' => (int)($_POST['living_support'] ?? 0),
				'other_income' => (int)($_POST['other_income'] ?? 0)
			];
			
			if ($existingData) {
				// UPDATE
				$sql = "UPDATE application_bankruptcy_living_status_income 
						SET self_income = :self_income, 
							monthly_salary = :monthly_salary, 
							pension = :pension, 
							living_support = :living_support,
							other_income = :other_income
						WHERE case_no = :case_no";
			} else {
				// INSERT
				$sql = "INSERT INTO application_bankruptcy_living_status_income 
						(case_no, self_income, monthly_salary, pension, living_support, other_income) 
						VALUES (:case_no, :self_income, :monthly_salary, :pension, :living_support, :other_income)";
			}
			
			$stmt = $pdo->prepare($sql);
			$result = $stmt->execute($data);
			
			if ($result) {
				echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
			}
			break;
			
		// 가족 구성원 정보
		case 'get_family_members':
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_family WHERE case_no = :case_no ORDER BY member_id");
			$stmt->execute(['case_no' => $case_no]);
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			echo json_encode(['success' => true, 'data' => $data]);
			break;
			
		case 'save_family_member':
			$member_id = $_POST['member_id'] ?? null;
			
			$data = [
				'case_no' => $case_no,
				'name' => $_POST['name'] ?? '',
				'relation' => $_POST['relation'] ?? '',
				'age' => $_POST['age'] ?? '',
				'job' => $_POST['job'] ?? '',
				'income' => (int)($_POST['income'] ?? 0)
			];
			
			// member_id가 DB에 존재하는지 검사
			if ($member_id) {
				$checkStmt = $pdo->prepare("SELECT member_id FROM application_bankruptcy_living_status_family WHERE member_id = :member_id");
				$checkStmt->execute(['member_id' => $member_id]);
				$exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
				
				if ($exists) {
					// UPDATE
					$data['member_id'] = $member_id;
					$sql = "UPDATE application_bankruptcy_living_status_family 
							SET name = :name, 
								relation = :relation, 
								age = :age, 
								job = :job,
								income = :income
							WHERE member_id = :member_id";
				} else {
					// INSERT (숫자 member_id는 있지만 DB에 없는 경우)
					$sql = "INSERT INTO application_bankruptcy_living_status_family 
							(case_no, name, relation, age, job, income) 
							VALUES (:case_no, :name, :relation, :age, :job, :income)";
				}
			} else {
				// INSERT (새로운 데이터)
				$sql = "INSERT INTO application_bankruptcy_living_status_family 
						(case_no, name, relation, age, job, income) 
						VALUES (:case_no, :name, :relation, :age, :job, :income)";
			}
			
			$stmt = $pdo->prepare($sql);
			$result = $stmt->execute($data);
			
			if ($result) {
				// 새로 추가된 경우 member_id 반환
				if (!$member_id || !$exists) {
					$member_id = $pdo->lastInsertId();
				}
				echo json_encode(['success' => true, 'message' => '저장되었습니다.', 'data' => ['member_id' => $member_id]]);
			} else {
				echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
			}
			break;
			
		case 'delete_family_member':
			$member_id = $_POST['member_id'] ?? null;
			
			if (!$member_id) {
				echo json_encode(['success' => false, 'message' => '멤버 ID가 필요합니다.']);
				exit;
			}
			
			$stmt = $pdo->prepare("DELETE FROM application_bankruptcy_living_status_family WHERE member_id = :member_id AND case_no = :case_no");
			$result = $stmt->execute(['member_id' => $member_id, 'case_no' => $case_no]);
			
			if ($result) {
				echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '삭제 중 오류가 발생했습니다.']);
			}
			break;
			
		// 추가 정보
		case 'get_additional_info':
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_additional WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			echo json_encode(['success' => true, 'data' => $data]);
			break;
			
		case 'save_additional_info':
			// 기존 데이터 확인
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_additional WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
			
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
			
			if ($existingData) {
				// UPDATE
				$sql = "UPDATE application_bankruptcy_living_status_additional 
						SET living_start_date = :living_start_date, 
							family_status = :family_status, 
							family_status_etc = :family_status_etc, 
							monthly_rent = :monthly_rent,
							rent_deposit = :rent_deposit,
							rent_arrears = :rent_arrears,
							tenant_name = :tenant_name,
							tenant_relation = :tenant_relation,
							owner_name = :owner_name,
							owner_relation = :owner_relation,
							residence_reason = :residence_reason
						WHERE case_no = :case_no";
			} else {
				// INSERT
				$sql = "INSERT INTO application_bankruptcy_living_status_additional 
						(case_no, living_start_date, family_status, family_status_etc, monthly_rent, rent_deposit, rent_arrears, tenant_name, tenant_relation, owner_name, owner_relation, residence_reason) 
						VALUES (:case_no, :living_start_date, :family_status, :family_status_etc, :monthly_rent, :rent_deposit, :rent_arrears, :tenant_name, :tenant_relation, :owner_name, :owner_relation, :residence_reason)";
			}
			
			$stmt = $pdo->prepare($sql);
			$result = $stmt->execute($data);
			
			if ($result) {
				echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
			}
			break;
			
		// 세금 정보
		case 'get_tax_info':
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_tax WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			echo json_encode(['success' => true, 'data' => $data]);
			break;
			
		case 'save_tax_info':
			// 기존 데이터 확인
			$stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_living_status_tax WHERE case_no = :case_no");
			$stmt->execute(['case_no' => $case_no]);
			$existingData = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$taxTypes = ['income_tax', 'residence_tax', 'property_tax', 'pension_tax', 'car_tax', 'other_tax', 'health_insurance'];
			
			$data = [
				'case_no' => $case_no
			];
			
			// 각 세금 유형별 데이터 처리
			foreach($taxTypes as $type) {
				$data["{$type}_status"] = $_POST["{$type}_status"] ?? '';
				$data["{$type}_amount"] = (int)($_POST["{$type}_amount"] ?? 0);
			}
			
			if ($existingData) {
				// UPDATE
				$sql = "UPDATE application_bankruptcy_living_status_tax SET ";
				
				$updateFields = [];
				foreach($taxTypes as $type) {
					$updateFields[] = "{$type}_status = :{$type}_status, {$type}_amount = :{$type}_amount";
				}
				
				$sql .= implode(", ", $updateFields);
				$sql .= " WHERE case_no = :case_no";
			} else {
				// INSERT
				$fields = ['case_no'];
				$values = [':case_no'];
				
				foreach($taxTypes as $type) {
					$fields[] = "{$type}_status";
					$fields[] = "{$type}_amount";
					$values[] = ":{$type}_status";
					$values[] = ":{$type}_amount";
				}
				
				$sql = "INSERT INTO application_bankruptcy_living_status_tax (".implode(", ", $fields).") VALUES (".implode(", ", $values).")";
			}
			
			$stmt = $pdo->prepare($sql);
			$result = $stmt->execute($data);
			
			if ($result) {
				echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
			} else {
				echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
			}
			break;
			
		default:
			echo json_encode(['success' => false, 'message' => '유효하지 않은 액션입니다.']);
	}
} catch (PDOException $e) {
	error_log('Database error: ' . $e->getMessage());
	echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()]);
}
?>