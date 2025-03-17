<?php
// api/application_bankruptcy/income_expenditure/income_expenditure_api.php
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
      // 수입지출 정보 조회
      $stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_income_expenditure WHERE case_no = :case_no");
      $stmt->execute(['case_no' => $case_no]);
      $incomeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

      // 부양가족 정보 조회
      $stmt = $pdo->prepare("SELECT * FROM application_bankruptcy_dependents WHERE case_no = :case_no ORDER BY dependent_id");
      $stmt->execute(['case_no' => $case_no]);
      $dependentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // 데이터 병합
      $data = $incomeData ?: [];
      $data['dependents'] = $dependentsData ?: [];

      echo json_encode(['success' => true, 'data' => $data]);
      exit;
   }
   
   // POST 요청 처리 - 데이터 저장 및 삭제
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // 부양가족 삭제 처리
      if (isset($_POST['dependent_id']) && isset($_POST['action']) && $_POST['action'] == 'delete_dependent') {
         $stmt = $pdo->prepare("DELETE FROM application_bankruptcy_dependents WHERE dependent_id = :dependent_id AND case_no = :case_no");
         $result = $stmt->execute([
            'dependent_id' => $_POST['dependent_id'],
            'case_no' => $case_no
         ]);
         
         echo json_encode(['success' => $result, 'message' => $result ? '삭제되었습니다.' : '삭제 중 오류가 발생했습니다.']);
         exit;
      }
      
      // 수입지출 정보 저장
      if (isset($_POST['income_total']) || isset($_POST['expense_total'])) {
         $stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_income_expenditure WHERE case_no = :case_no");
         $stmt->execute(['case_no' => $case_no]);
         $exists = $stmt->fetch(PDO::FETCH_ASSOC);
         
         $data = [
            'case_no' => $case_no,
            'statement_month' => $_POST['statement_month'] ?? '',
            'income_salary_applicant' => (int)($_POST['income_salary_applicant'] ?? 0),
            'income_salary_spouse' => (int)($_POST['income_salary_spouse'] ?? 0),
            'income_salary_others' => (int)($_POST['income_salary_others'] ?? 0),
            'income_pension_applicant' => (int)($_POST['income_pension_applicant'] ?? 0),
            'income_pension_spouse' => (int)($_POST['income_pension_spouse'] ?? 0),
            'income_pension_others' => (int)($_POST['income_pension_others'] ?? 0),
            'income_support' => (int)($_POST['income_support'] ?? 0),
            'income_others' => (int)($_POST['income_others'] ?? 0),
            'income_total' => (int)($_POST['income_total'] ?? 0),
            'expense_housing' => (int)($_POST['expense_housing'] ?? 0),
            'expense_food' => (int)($_POST['expense_food'] ?? 0),
            'expense_education' => (int)($_POST['expense_education'] ?? 0),
            'expense_utilities' => (int)($_POST['expense_utilities'] ?? 0),
            'expense_transportation' => (int)($_POST['expense_transportation'] ?? 0),
            'expense_communication' => (int)($_POST['expense_communication'] ?? 0),
            'expense_medical' => (int)($_POST['expense_medical'] ?? 0),
            'expense_insurance' => (int)($_POST['expense_insurance'] ?? 0),
            'expense_others' => (int)($_POST['expense_others'] ?? 0),
            'expense_total' => (int)($_POST['expense_total'] ?? 0)
         ];
         
         if ($exists) {
            $sql = "UPDATE application_bankruptcy_income_expenditure SET 
                  statement_month = :statement_month,
                  income_salary_applicant = :income_salary_applicant,
                  income_salary_spouse = :income_salary_spouse,
                  income_salary_others = :income_salary_others,
                  income_pension_applicant = :income_pension_applicant,
                  income_pension_spouse = :income_pension_spouse,
                  income_pension_others = :income_pension_others,
                  income_support = :income_support,
                  income_others = :income_others,
                  income_total = :income_total,
                  expense_housing = :expense_housing,
                  expense_food = :expense_food,
                  expense_education = :expense_education,
                  expense_utilities = :expense_utilities,
                  expense_transportation = :expense_transportation,
                  expense_communication = :expense_communication,
                  expense_medical = :expense_medical,
                  expense_insurance = :expense_insurance,
                  expense_others = :expense_others,
                  expense_total = :expense_total
                  WHERE case_no = :case_no";
         } else {
            $sql = "INSERT INTO application_bankruptcy_income_expenditure 
                  (case_no, statement_month, income_salary_applicant, income_salary_spouse, income_salary_others,
                  income_pension_applicant, income_pension_spouse, income_pension_others, income_support, income_others,
                  income_total, expense_housing, expense_food, expense_education, expense_utilities,
                  expense_transportation, expense_communication, expense_medical, expense_insurance, expense_others,
                  expense_total) 
                  VALUES (:case_no, :statement_month, :income_salary_applicant, :income_salary_spouse, :income_salary_others,
                  :income_pension_applicant, :income_pension_spouse, :income_pension_others, :income_support, :income_others,
                  :income_total, :expense_housing, :expense_food, :expense_education, :expense_utilities,
                  :expense_transportation, :expense_communication, :expense_medical, :expense_insurance, :expense_others,
                  :expense_total)";
         }
         
         $stmt = $pdo->prepare($sql);
         $result = $stmt->execute($data);
         
         echo json_encode(['success' => $result, 'message' => $result ? '저장되었습니다.' : '저장 중 오류가 발생했습니다.']);
         exit;
      }
      
      // 가용소득 정보 저장
      if (isset($_POST['debtor_monthly_income']) || isset($_POST['household_size'])) {
         $stmt = $pdo->prepare("SELECT 1 FROM application_bankruptcy_income_expenditure WHERE case_no = :case_no");
         $stmt->execute(['case_no' => $case_no]);
         $exists = $stmt->fetch(PDO::FETCH_ASSOC);
         
         $data = [
            'case_no' => $case_no,
            'debtor_monthly_income' => (int)($_POST['debtor_monthly_income'] ?? 0),
            'household_size' => $_POST['household_size'] ?? '1',
            'disposable_income' => (int)($_POST['disposable_income'] ?? 0)
         ];
         
         if ($exists) {
            $sql = "UPDATE application_bankruptcy_income_expenditure SET 
                  debtor_monthly_income = :debtor_monthly_income,
                  household_size = :household_size,
                  disposable_income = :disposable_income
                  WHERE case_no = :case_no";
         } else {
            $sql = "INSERT INTO application_bankruptcy_income_expenditure 
                  (case_no, debtor_monthly_income, household_size, disposable_income) 
                  VALUES (:case_no, :debtor_monthly_income, :household_size, :disposable_income)";
         }
         
         $stmt = $pdo->prepare($sql);
         $result = $stmt->execute($data);
         
         // 부양가족 정보 저장
         if (isset($_POST['dependents'])) {
            $dependents = json_decode($_POST['dependents'], true);
            
            if (is_array($dependents) && count($dependents) > 0) {
               // 기존 부양가족 삭제 (일괄 업데이트)
               $pdo->prepare("DELETE FROM application_bankruptcy_dependents WHERE case_no = :case_no")->execute(['case_no' => $case_no]);
               
               // 새 부양가족 정보 추가
               foreach ($dependents as $dependent) {
                  if (!empty($dependent['name']) || !empty($dependent['age']) || !empty($dependent['relation'])) {
                     $depData = [
                        'case_no' => $case_no,
                        'name' => $dependent['name'] ?? '',
                        'age' => $dependent['age'] ?? '',
                        'relation' => $dependent['relation'] ?? ''
                     ];
                     
                     $depSql = "INSERT INTO application_bankruptcy_dependents 
                              (case_no, name, age, relation) 
                              VALUES (:case_no, :name, :age, :relation)";
                     
                     $pdo->prepare($depSql)->execute($depData);
                  }
               }
            }
         }
         
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