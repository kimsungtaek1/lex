SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE application_bankruptcy (
  bankruptcy_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  name varchar(100) NOT NULL COMMENT '성명',
  resident_number varchar(14) DEFAULT NULL COMMENT '주민등록번호',
  registered_address varchar(255) DEFAULT NULL COMMENT '주민등록상주소',
  now_address varchar(255) DEFAULT NULL COMMENT '실거주지주소',
  base_address varchar(255) DEFAULT NULL COMMENT '등록기준지',
  phone varchar(20) DEFAULT NULL COMMENT '연락처',
  work_phone varchar(20) DEFAULT NULL COMMENT '연락처(자택/직장)',
  email varchar(100) DEFAULT NULL COMMENT '이메일',
  application_date date DEFAULT NULL COMMENT '신청예정일',
  court_name varchar(50) DEFAULT NULL COMMENT '관할법원',
  case_number varchar(50) DEFAULT NULL COMMENT '사건번호',
  creditor_count int(11) DEFAULT 0 COMMENT '채권자 수',
  stay_order_apply tinyint(1) DEFAULT 0 COMMENT '중지명령신청',
  exemption_apply tinyint(1) DEFAULT 0 COMMENT '면제재산신청',
  support_org varchar(100) DEFAULT NULL COMMENT '지원기관',
  support_details text DEFAULT NULL COMMENT '지원내역과 지원금액'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_assets (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  asset_type enum('현금','예금','보험','부동산','자동차','임차보증금','기타') NOT NULL COMMENT '재산 유형',
  asset_description text DEFAULT NULL COMMENT '재산 설명',
  value int(11) DEFAULT 0 COMMENT '가치',
  secured_amount int(11) DEFAULT 0 COMMENT '담보금액',
  net_value int(11) DEFAULT 0 COMMENT '순가치',
  exemption_amount int(11) DEFAULT 0 COMMENT '면제금액',
  creditor_name varchar(100) DEFAULT NULL COMMENT '채권자명',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_asset_cash (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  property_detail text DEFAULT NULL,
  liquidation_value bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_deposits (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  bank_name varchar(255) DEFAULT NULL,
  account_number varchar(255) DEFAULT NULL,
  deposit_amount bigint(20) DEFAULT 0,
  deduction_amount bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_disposed (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  disposal_date date DEFAULT NULL,
  disposal_amount bigint(20) DEFAULT 0,
  disposal_usage text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_divorce (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  divorce_date date DEFAULT NULL,
  settlement_property text DEFAULT NULL,
  divorce_timing varchar(255) DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_inherited (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  inheritance_date date DEFAULT NULL,
  deceased_type varchar(255) DEFAULT NULL,
  inheritance_status text DEFAULT NULL,
  main_inheritance_property text DEFAULT NULL,
  acquisition_process text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_insurance (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  company_name varchar(255) DEFAULT NULL,
  securities_number varchar(255) DEFAULT NULL,
  refund_amount bigint(20) DEFAULT 0,
  is_coverage char(1) DEFAULT 'N',
  explanation text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_loan_receivables (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  debtor_name varchar(255) DEFAULT NULL,
  claim_amount bigint(20) DEFAULT 0,
  collectible_amount bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_other (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  asset_content text DEFAULT NULL,
  liquidation_value bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_real_estate (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  property_type varchar(255) DEFAULT NULL,
  property_area varchar(255) DEFAULT NULL,
  property_location text DEFAULT NULL,
  secured_debt_balance bigint(20) DEFAULT 0,
  seizure_details text DEFAULT NULL,
  seizure_creditor varchar(255) DEFAULT NULL,
  seizure_amount bigint(20) DEFAULT 0,
  market_value bigint(20) DEFAULT 0,
  liquidation_explanation text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_received_deposit (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  receipt_date date DEFAULT NULL,
  rental_property text DEFAULT NULL,
  contract_deposit bigint(20) DEFAULT 0,
  received_deposit bigint(20) DEFAULT 0,
  deposit_usage text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_rent_deposits (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  rent_location text DEFAULT NULL,
  rent_deposit bigint(20) DEFAULT 0,
  key_money bigint(20) DEFAULT 0,
  expected_refund bigint(20) DEFAULT 0,
  explanation text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_sales_receivables (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  debtor_name varchar(255) DEFAULT NULL,
  claim_amount bigint(20) DEFAULT 0,
  collectible_amount bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_severance (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  workplace varchar(255) DEFAULT NULL,
  expected_amount bigint(20) DEFAULT 0,
  deduction_amount bigint(20) DEFAULT 0,
  liquidation_value bigint(20) DEFAULT 0,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_asset_summary (
  summary_id int(11) NOT NULL,
  case_no varchar(20) NOT NULL,
  cash_exists enum('Y','N') DEFAULT 'N',
  deposit_exists enum('Y','N') DEFAULT 'N',
  insurance_exists enum('Y','N') DEFAULT 'N',
  rent_deposit_exists enum('Y','N') DEFAULT 'N',
  loan_receivables_exists enum('Y','N') DEFAULT 'N',
  sales_receivables_exists enum('Y','N') DEFAULT 'N',
  severance_pay_exists enum('Y','N') DEFAULT 'N',
  real_estate_exists enum('Y','N') DEFAULT 'N',
  vehicle_exists enum('Y','N') DEFAULT 'N',
  other_assets_exists enum('Y','N') DEFAULT 'N',
  disposed_assets_exists enum('Y','N') DEFAULT 'N',
  received_deposit_exists enum('Y','N') DEFAULT 'N',
  divorce_property_exists enum('Y','N') DEFAULT 'N',
  inherited_property_exists enum('Y','N') DEFAULT 'N',
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_asset_vehicles (
  asset_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  vehicle_info text DEFAULT NULL,
  registration_number varchar(255) DEFAULT NULL,
  security_debt_balance bigint(20) DEFAULT 0,
  market_value bigint(20) DEFAULT 0,
  liquidation_value bigint(20) DEFAULT 0,
  liquidation_explanation text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_creditor (
  creditor_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  financial_institution varchar(100) DEFAULT NULL COMMENT '금융기관명',
  address varchar(255) DEFAULT NULL COMMENT '주소',
  phone varchar(20) DEFAULT NULL COMMENT '연락처',
  fax varchar(20) DEFAULT NULL COMMENT '팩스번호',
  borrowing_date date DEFAULT NULL COMMENT '차용일자',
  separate_bond varchar(50) DEFAULT '금원차용' COMMENT '발생원인',
  usage_detail text DEFAULT NULL COMMENT '사용처',
  initial_claim int(11) DEFAULT 0 COMMENT '최초채권액',
  remaining_principal int(11) DEFAULT 0 COMMENT '잔존원금',
  remaining_interest int(11) DEFAULT 0 COMMENT '잔존이자',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_dependents (
  dependent_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  name varchar(50) DEFAULT NULL,
  age varchar(10) DEFAULT NULL,
  relation varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_income_expenditure (
  case_no int(11) NOT NULL,
  statement_month varchar(2) DEFAULT NULL,
  income_salary_applicant int(11) DEFAULT 0,
  income_salary_spouse int(11) DEFAULT 0,
  income_salary_others int(11) DEFAULT 0,
  income_pension_applicant int(11) DEFAULT 0,
  income_pension_spouse int(11) DEFAULT 0,
  income_pension_others int(11) DEFAULT 0,
  income_support int(11) DEFAULT 0,
  income_others int(11) DEFAULT 0,
  income_total int(11) DEFAULT 0,
  expense_housing int(11) DEFAULT 0,
  expense_food int(11) DEFAULT 0,
  expense_education int(11) DEFAULT 0,
  expense_utilities int(11) DEFAULT 0,
  expense_transportation int(11) DEFAULT 0,
  expense_communication int(11) DEFAULT 0,
  expense_medical int(11) DEFAULT 0,
  expense_insurance int(11) DEFAULT 0,
  expense_others int(11) DEFAULT 0,
  expense_total int(11) DEFAULT 0,
  debtor_monthly_income int(11) DEFAULT 0,
  household_size varchar(1) DEFAULT '1',
  disposable_income int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_living_status_additional (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  living_start_date varchar(100) DEFAULT NULL,
  family_status varchar(20) DEFAULT NULL,
  family_status_etc varchar(255) DEFAULT NULL,
  monthly_rent int(11) DEFAULT 0,
  rent_deposit int(11) DEFAULT 0,
  rent_arrears int(11) DEFAULT 0,
  tenant_name varchar(50) DEFAULT NULL,
  tenant_relation varchar(50) DEFAULT NULL,
  owner_name varchar(50) DEFAULT NULL,
  owner_relation varchar(50) DEFAULT NULL,
  residence_reason text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_living_status_basic (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  job_type varchar(50) DEFAULT NULL,
  job_industry varchar(100) DEFAULT NULL,
  company_name varchar(100) DEFAULT NULL,
  employment_period varchar(100) DEFAULT NULL,
  job_position varchar(50) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_living_status_family (
  member_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  name varchar(50) DEFAULT NULL,
  relation varchar(50) DEFAULT NULL,
  age varchar(20) DEFAULT NULL,
  job varchar(100) DEFAULT NULL,
  income int(11) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_living_status_income (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  self_income int(11) DEFAULT 0,
  monthly_salary int(11) DEFAULT 0,
  pension int(11) DEFAULT 0,
  living_support int(11) DEFAULT 0,
  other_income int(11) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_living_status_tax (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  income_tax_status varchar(50) DEFAULT NULL,
  income_tax_amount int(11) DEFAULT 0,
  residence_tax_status varchar(50) DEFAULT NULL,
  residence_tax_amount int(11) DEFAULT 0,
  property_tax_status varchar(50) DEFAULT NULL,
  property_tax_amount int(11) DEFAULT 0,
  pension_tax_status varchar(50) DEFAULT NULL,
  pension_tax_amount int(11) DEFAULT 0,
  car_tax_status varchar(50) DEFAULT NULL,
  car_tax_amount int(11) DEFAULT 0,
  other_tax_status varchar(50) DEFAULT NULL,
  other_tax_amount int(11) DEFAULT 0,
  health_insurance_status varchar(50) DEFAULT NULL,
  health_insurance_amount int(11) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_bankruptcy_prohibition_orders (
  order_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  application text NOT NULL COMMENT '면제명령 신청서',
  purpose text NOT NULL COMMENT '신청취지',
  reason text NOT NULL COMMENT '신청원인',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement (
  statement_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  statement_type varchar(50) NOT NULL COMMENT '진술서 유형',
  content text DEFAULT NULL COMMENT '내용',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_bankruptcy_history (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  date date DEFAULT NULL COMMENT '파산 일자',
  court varchar(100) DEFAULT NULL COMMENT '파산 법원',
  status text DEFAULT NULL COMMENT '파산 상태(취하/기각)',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_bankruptcy_reason (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  debt_reason text DEFAULT NULL COMMENT '채무 원인',
  dependents_count varchar(10) DEFAULT NULL COMMENT '부양가족수',
  living_expense_shortage_items text DEFAULT NULL COMMENT '부족한 생활비 항목',
  house_purchase_date date DEFAULT NULL COMMENT '주택구입시기',
  house_disposal_date date DEFAULT NULL COMMENT '주택처분시기',
  house_details text DEFAULT NULL COMMENT '주택 명세',
  business_start_date date DEFAULT NULL COMMENT '사업 시작일',
  business_end_date date DEFAULT NULL COMMENT '사업 종료일',
  business_type_detail text DEFAULT NULL COMMENT '사업 종류',
  fraud_perpetrator_name varchar(100) DEFAULT NULL COMMENT '사기 가해자 이름',
  fraud_perpetrator_relationship varchar(50) DEFAULT NULL COMMENT '사기 가해자 관계',
  fraud_damage_amount int(11) DEFAULT NULL COMMENT '사기 피해액',
  debt_reason_other_detail text DEFAULT NULL COMMENT '기타 채무 사유',
  inability_reason text DEFAULT NULL COMMENT '지급불능 원인',
  inability_reason_other_detail text DEFAULT NULL COMMENT '기타 지급불능 사유',
  inability_reason_other_date date DEFAULT NULL COMMENT '지급불능 시기',
  exact_date_unknown varchar(50) DEFAULT NULL COMMENT '별지 사용 여부',
  inability_timeline text DEFAULT NULL COMMENT '구체적 사정',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_career (
  career_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  company_type varchar(20) DEFAULT NULL COMMENT '자영/근무',
  business_type varchar(100) DEFAULT NULL COMMENT '업종',
  company_name varchar(100) DEFAULT NULL COMMENT '직장명/상호',
  position varchar(50) DEFAULT NULL COMMENT '직위',
  work_start_date date DEFAULT NULL COMMENT '근무 시작일',
  work_end_date date DEFAULT NULL COMMENT '근무 종료일',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_creditor_status (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  negotiation_experience varchar(10) DEFAULT NULL COMMENT '교섭 경험',
  agreed_creditors_count varchar(10) DEFAULT NULL COMMENT '협의 성립 채권자 수',
  payment_period_start date DEFAULT NULL COMMENT '협의 지급 시작일',
  payment_period_end date DEFAULT NULL COMMENT '협의 지급 종료일',
  monthly_payment_amount int(11) DEFAULT NULL COMMENT '월 지급액',
  creditor_payment_details text DEFAULT NULL COMMENT '지급 내역',
  legal_action varchar(10) DEFAULT NULL COMMENT '소송/압류 경험',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_debt_after_insolvency (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  debt_after_insolvency varchar(10) DEFAULT NULL COMMENT '사실 여부',
  date date DEFAULT NULL COMMENT '시기',
  reason text DEFAULT NULL COMMENT '원인',
  amount int(11) DEFAULT NULL COMMENT '금액',
  debt_condition text DEFAULT NULL COMMENT '조건',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_domestic_court (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  family_application varchar(10) DEFAULT NULL COMMENT '신청 가족 여부',
  spouse_name varchar(100) DEFAULT NULL COMMENT '배우자 성명',
  other_family_members text DEFAULT NULL COMMENT '배우자 외 다른 가족',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_education (
  education_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  graduation_date varchar(100) DEFAULT NULL COMMENT '이름',
  school_name varchar(100) DEFAULT NULL COMMENT '학교명',
  graduation_status varchar(20) DEFAULT NULL COMMENT '졸업여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_legal_action (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  court varchar(100) DEFAULT NULL COMMENT '법원',
  case_number varchar(50) DEFAULT NULL COMMENT '사건번호',
  creditor varchar(100) DEFAULT NULL COMMENT '채권자명',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_life_history (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  fraud_experience varchar(10) DEFAULT NULL COMMENT '사기죄 경험',
  fraud_reason text DEFAULT NULL COMMENT '사기죄 사유',
  past_bankruptcy varchar(10) DEFAULT NULL COMMENT '과거 파산신청 경험',
  past_bankruptcy_declared varchar(10) DEFAULT NULL COMMENT '과거 파산선고 경험',
  bankruptcy_declared_date date DEFAULT NULL COMMENT '파산선고 일자',
  bankruptcy_declared_court varchar(100) DEFAULT NULL COMMENT '파산선고 법원',
  past_discharge varchar(10) DEFAULT NULL COMMENT '면책 경험',
  discharge_date date DEFAULT NULL COMMENT '면책 일자',
  discharge_court varchar(100) DEFAULT NULL COMMENT '면책 법원',
  discharge_confirmed_date date DEFAULT NULL COMMENT '면책 확정일자',
  personal_rehabilitation varchar(10) DEFAULT NULL COMMENT '개인회생절차 경험',
  approval_date date DEFAULT NULL COMMENT '인가결정일자',
  approval_court varchar(100) DEFAULT NULL COMMENT '인가 법원',
  approval_case_number varchar(50) DEFAULT NULL COMMENT '인가 사건번호',
  cancellation_date date DEFAULT NULL COMMENT '폐지결정일자',
  cancellation_court varchar(100) DEFAULT NULL COMMENT '폐지 법원',
  cancellation_reason text DEFAULT NULL COMMENT '폐지사유',
  rehabilitation_discharge varchar(10) DEFAULT NULL COMMENT '개인회생 면책 경험',
  rehabilitation_discharge_date date DEFAULT NULL COMMENT '개인회생 면책 일자',
  rehabilitation_discharge_court varchar(100) DEFAULT NULL COMMENT '개인회생 면책 법원',
  rehabilitation_discharge_case_number varchar(50) DEFAULT NULL COMMENT '개인회생 면책 사건번호',
  unpaid_sales varchar(10) DEFAULT NULL COMMENT '미지급 물품 처분 경험',
  unpaid_goods_name varchar(100) DEFAULT NULL COMMENT '미지급 품명',
  unpaid_purchase_date date DEFAULT NULL COMMENT '미지급 구입시기',
  unpaid_price int(11) DEFAULT NULL COMMENT '미지급 가격',
  unpaid_disposal_date date DEFAULT NULL COMMENT '미지급 처분시기',
  unpaid_disposal_method varchar(100) DEFAULT NULL COMMENT '미지급 처분방법',
  business_record_type text DEFAULT NULL COMMENT '사업 정부 기재',
  unfair_sale varchar(10) DEFAULT NULL COMMENT '상품 염가 매각',
  unfair_sale_product varchar(100) DEFAULT NULL COMMENT '염가 매각 상품',
  unfair_sale_date date DEFAULT NULL COMMENT '염가 매각 시기',
  unfair_discount_rate varchar(50) DEFAULT NULL COMMENT '염가 할인율',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_statement_partial_repayment (
  id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  partial_repayment varchar(10) DEFAULT NULL COMMENT '경험 여부',
  date date DEFAULT NULL COMMENT '변제 시기',
  creditor_name varchar(100) DEFAULT NULL COMMENT '변제 채권자명',
  amount int(11) DEFAULT NULL COMMENT '변제 금액',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_bankruptcy_stay_orders (
  order_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  application text NOT NULL COMMENT '중지명령 신청서',
  purpose text NOT NULL COMMENT '신청취지',
  reason text NOT NULL COMMENT '신청원인',
  method text NOT NULL COMMENT '소명방법',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_income_living_expense_standard (
  id int(11) NOT NULL,
  year int(4) NOT NULL COMMENT '적용 연도',
  family_members int(2) NOT NULL COMMENT '가구원 수',
  standard_amount int(11) NOT NULL COMMENT '기준 생계비',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='개인회생 생계비 기준액 테이블';

CREATE TABLE application_recovery (
  recovery_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  name varchar(100) NOT NULL,
  resident_number varchar(14) DEFAULT NULL,
  registered_address varchar(255) DEFAULT NULL,
  now_address varchar(255) DEFAULT NULL,
  work_address varchar(255) DEFAULT NULL,
  phone varchar(20) DEFAULT NULL,
  workplace varchar(100) DEFAULT NULL,
  position varchar(50) DEFAULT NULL,
  is_company tinyint(1) DEFAULT 0,
  debt_total int(11) DEFAULT NULL,
  income_monthly int(11) DEFAULT NULL,
  expense_monthly int(11) DEFAULT NULL,
  repayment_monthly int(11) DEFAULT NULL,
  assets_total int(11) DEFAULT NULL,
  memo text DEFAULT NULL,
  application_date date DEFAULT NULL,
  unspecified_date tinyint(1) DEFAULT 0,
  repayment_start_date date DEFAULT NULL,
  court_name varchar(50) DEFAULT NULL,
  case_year varchar(4) DEFAULT NULL,
  bank_name varchar(50) DEFAULT NULL,
  account_number varchar(50) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  status enum('신청','개시','인가','폐지','기각','취하') DEFAULT '신청',
  assigned_employee int(11) DEFAULT NULL,
  work_period varchar(100) DEFAULT NULL,
  other_income varchar(100) DEFAULT NULL,
  other_income_name varchar(100) DEFAULT NULL,
  income_source varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_additional_claims (
  claim_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  claim_type varchar(100) DEFAULT NULL COMMENT '채권종류',
  amount int(11) DEFAULT NULL COMMENT '금액',
  description text DEFAULT NULL COMMENT '설명',
  payment_term varchar(100) DEFAULT NULL COMMENT '변제기',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_attached_deposits (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  seizure_content text DEFAULT NULL COMMENT '압류내용',
  custodian varchar(100) DEFAULT NULL COMMENT '보관자',
  liquidation_value int(11) DEFAULT 0,
  exclude_liquidation enum('Y','N') DEFAULT 'N' COMMENT '청산가치제외여부',
  repayment_input enum('Y','N') DEFAULT 'N' COMMENT '변제투입여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_business (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  item_name varchar(255) NOT NULL COMMENT '품목',
  purchase_date varchar(7) NOT NULL COMMENT '구입시기',
  quantity int(11) NOT NULL COMMENT '수량',
  used_price int(11) NOT NULL COMMENT '중고시세',
  total int(11) NOT NULL COMMENT '합계',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_cash (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_detail text DEFAULT NULL COMMENT '재산 세부 상황',
  liquidation_value int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_court_deposits (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  seizure_content text DEFAULT NULL COMMENT '압류내용',
  court_name varchar(100) DEFAULT NULL COMMENT '법원명',
  liquidation_value int(11) DEFAULT 0,
  exclude_liquidation enum('Y','N') DEFAULT 'N' COMMENT '청산가치제외여부',
  repayment_input enum('Y','N') DEFAULT 'N' COMMENT '변제투입여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_deposits (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  bank_name varchar(100) DEFAULT NULL COMMENT '은행명',
  account_number varchar(100) DEFAULT NULL COMMENT '계좌번호',
  deposit_amount int(11) DEFAULT 0,
  deduction_amount int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_disposed (
  asset_no int(11) NOT NULL,
  case_no varchar(50) NOT NULL,
  property_no int(11) NOT NULL,
  disposal_date date DEFAULT NULL,
  property_type varchar(100) DEFAULT NULL,
  disposal_amount int(11) DEFAULT NULL,
  disposal_reason text DEFAULT NULL,
  recipient varchar(100) DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_recovery_asset_divorce (
  asset_no int(11) NOT NULL,
  case_no varchar(50) NOT NULL,
  property_no int(11) NOT NULL,
  divorce_date date DEFAULT NULL,
  spouse_name varchar(100) DEFAULT NULL,
  settlement_date date DEFAULT NULL,
  property_type varchar(100) DEFAULT NULL,
  property_amount int(11) DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_recovery_asset_exemption1 (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  lease_contract enum('Y','N') DEFAULT 'N' COMMENT '임대차계약서',
  resident_registration enum('Y','N') DEFAULT 'N' COMMENT '주민등록등본',
  other_evidence enum('Y','N') DEFAULT 'N' COMMENT '기타증빙',
  other_evidence_detail varchar(100) DEFAULT NULL COMMENT '기타증빙상세',
  lease_location text DEFAULT NULL COMMENT '임차소재지',
  contract_date date DEFAULT NULL,
  lease_start_date date DEFAULT NULL,
  lease_end_date date DEFAULT NULL,
  fixed_date date DEFAULT NULL,
  has_fixed_date enum('Y','N') DEFAULT 'N' COMMENT '확정일자여부',
  registration_date date DEFAULT NULL,
  lease_deposit int(11) DEFAULT 0,
  rent_fee int(11) DEFAULT 0,
  overdue_months int(11) DEFAULT 0 COMMENT '연체기간',
  lessor_name varchar(100) DEFAULT NULL COMMENT '임대인성명',
  exemption_amount int(11) DEFAULT 0,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_exemption2 (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  evidence1 varchar(100) DEFAULT NULL COMMENT '증빙서류1',
  evidence2 varchar(100) DEFAULT NULL COMMENT '증빙서류2',
  evidence3 varchar(100) DEFAULT NULL COMMENT '증빙서류3',
  special_property_content text DEFAULT NULL COMMENT '특정재산내용',
  exemption_amount int(11) DEFAULT 0,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_inherited (
  asset_no int(11) NOT NULL,
  case_no varchar(50) NOT NULL,
  property_no int(11) NOT NULL,
  heir_name varchar(100) DEFAULT NULL,
  deceased_name varchar(100) DEFAULT NULL,
  inheritance_date date DEFAULT NULL,
  property_type varchar(100) DEFAULT NULL,
  property_amount int(11) DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_recovery_asset_insurance (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  company_name varchar(100) DEFAULT NULL COMMENT '보험사',
  securities_number varchar(100) DEFAULT NULL COMMENT '증권번호',
  refund_amount int(11) DEFAULT 0,
  is_coverage enum('Y','N') DEFAULT 'N' COMMENT '보장성보험여부',
  explanation text DEFAULT NULL COMMENT '부연설명',
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_loan_receivables (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  debtor_name varchar(100) DEFAULT NULL COMMENT '채무자명',
  has_evidence enum('Y','N') DEFAULT 'N' COMMENT '소명자료유무',
  liquidation_value int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_other (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  asset_content text DEFAULT NULL COMMENT '재산내용',
  liquidation_value int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_real_estate (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  property_right_type varchar(50) DEFAULT NULL COMMENT '권리종류',
  property_type varchar(50) DEFAULT NULL COMMENT '부동산종류',
  property_area int(11) DEFAULT NULL,
  property_location text DEFAULT NULL COMMENT '소재지',
  is_spouse tinyint(1) DEFAULT 0 COMMENT '배우자명의',
  property_expected_value int(11) DEFAULT 0,
  property_security_type varchar(100) DEFAULT NULL COMMENT '담보권종류',
  property_security_details text DEFAULT NULL COMMENT '담보권내용',
  property_secured_debt int(11) DEFAULT 0,
  property_deposit_debt int(11) DEFAULT 0,
  property_liquidation_value int(11) DEFAULT 0,
  property_liquidation_explain text DEFAULT NULL COMMENT '부연설명',
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_received_deposit (
  asset_no int(11) NOT NULL,
  case_no varchar(50) NOT NULL,
  property_no int(11) NOT NULL,
  receipt_date date DEFAULT NULL,
  lessor varchar(100) DEFAULT NULL,
  location text DEFAULT NULL,
  deposit_amount int(11) DEFAULT NULL,
  note text DEFAULT NULL,
  is_seized char(1) DEFAULT 'N',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE application_recovery_asset_rent_deposits (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  rent_location text DEFAULT NULL COMMENT '임차지',
  is_business_place enum('Y','N') DEFAULT 'N' COMMENT '영업장여부',
  contract_deposit int(11) DEFAULT 0,
  is_deposit_spouse tinyint(1) DEFAULT 0 COMMENT '보증금배우자명의',
  monthly_rent int(11) DEFAULT 0,
  refund_deposit int(11) DEFAULT 0,
  difference_reason text DEFAULT NULL COMMENT '차이나는이유',
  priority_deposit int(11) DEFAULT 0,
  liquidation_value int(11) DEFAULT 0,
  explanation text DEFAULT NULL COMMENT '부연설명',
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_sales_receivables (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  debtor_name varchar(100) DEFAULT NULL COMMENT '채무자명',
  has_evidence enum('Y','N') DEFAULT 'N' COMMENT '소명자료유무',
  liquidation_value int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_severance (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  is_public enum('Y','N') DEFAULT 'N' COMMENT '공무원여부',
  has_pension enum('Y','N') DEFAULT 'N' COMMENT '퇴직연금가입여부',
  workplace varchar(100) DEFAULT NULL COMMENT '근무지',
  expected_severance int(11) DEFAULT 0,
  deduction_amount int(11) DEFAULT 0,
  liquidation_value int(11) DEFAULT 0,
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_asset_vehicles (
  asset_no int(11) NOT NULL,
  property_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  vehicle_info text DEFAULT NULL COMMENT '차량정보',
  is_spouse tinyint(1) DEFAULT 0 COMMENT '배우자명의',
  security_type varchar(100) DEFAULT NULL COMMENT '담보권종류',
  max_bond int(11) DEFAULT 0,
  expected_value int(11) DEFAULT 0,
  financial_balance int(11) DEFAULT 0,
  liquidation_value int(11) DEFAULT 0,
  explanation text DEFAULT NULL COMMENT '부연설명',
  is_manual_calc enum('Y','N') DEFAULT 'N' COMMENT '수동계산여부',
  is_seized enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '압류여부',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor (
  creditor_no int(11) NOT NULL,
  case_no int(11) NOT NULL COMMENT '사건번호',
  creditor_count int(11) NOT NULL COMMENT '채권자 순번',
  entity_type enum('자연인','법인','권리능력없는법인','국가','지방자치단체') NOT NULL COMMENT '인격구분',
  financial_institution varchar(100) DEFAULT NULL COMMENT '금융기관명',
  postal_code varchar(10) DEFAULT NULL COMMENT '우편번호',
  address varchar(255) DEFAULT NULL COMMENT '주소',
  phone varchar(20) DEFAULT NULL COMMENT '전화번호',
  fax varchar(20) DEFAULT NULL COMMENT '팩스번호',
  principal int(11) DEFAULT NULL COMMENT '원금',
  principal_calculation text DEFAULT NULL COMMENT '원금 산정근거',
  interest int(11) DEFAULT NULL COMMENT '이자',
  interest_calculation text DEFAULT NULL COMMENT '이자 산정근거',
  default_rate decimal(5,2) DEFAULT NULL COMMENT '연체이율',
  claim_reason varchar(255) DEFAULT NULL COMMENT '채권의 원인',
  claim_content text DEFAULT NULL COMMENT '채권의 내용',
  priority_payment tinyint(1) DEFAULT 0 COMMENT '우선변제',
  undetermined_claim tinyint(1) DEFAULT 0 COMMENT '미확정채권',
  pension_debt tinyint(1) DEFAULT 0 COMMENT '연금채무',
  mortgage_restructuring tinyint(1) DEFAULT 0 COMMENT '주택담보대출채권',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_appendix (
  appendix_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  appendix_type enum('(근)저당권설정','질권설정/채권양도(전세보증금)','최우선변제임차권','우선변제임차권') NOT NULL DEFAULT '(근)저당권설정',
  property_detail varchar(255) DEFAULT NULL COMMENT '목적물',
  expected_value int(11) DEFAULT NULL,
  evaluation_rate decimal(5,2) DEFAULT NULL COMMENT '평가비율',
  max_claim int(11) DEFAULT NULL,
  registration_date date DEFAULT NULL COMMENT '등기일자',
  secured_expected_claim int(11) DEFAULT NULL,
  unsecured_remaining_claim int(11) DEFAULT NULL,
  rehabilitation_secured_claim int(11) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  pledge_deposit bigint(20) DEFAULT 0 COMMENT '보증금(전세/임대차)',
  pledge_amount bigint(20) DEFAULT 0 COMMENT '질권설정(채권양도)금',
  lease_start_date date DEFAULT NULL COMMENT '전세(임대차)시작일',
  lease_end_date date DEFAULT NULL COMMENT '전세(임대차)종료일',
  first_mortgage_date date DEFAULT NULL COMMENT '최초근저당권설정일',
  region varchar(50) DEFAULT NULL COMMENT '지역',
  lease_deposit bigint(20) DEFAULT 0 COMMENT '임대차보증금',
  top_priority_amount bigint(20) DEFAULT 0 COMMENT '최우선변제금',
  top_lease_start_date date DEFAULT NULL COMMENT '임대차시작일(최우선)',
  top_lease_end_date date DEFAULT NULL COMMENT '임대차종료일(최우선)',
  priority_deposit bigint(20) DEFAULT 0 COMMENT '임대차보증금(우선)',
  priority_lease_start_date date DEFAULT NULL COMMENT '임대차시작일(우선)',
  priority_lease_end_date date DEFAULT NULL COMMENT '임대차종료일(우선)',
  fixed_date date DEFAULT NULL COMMENT '확정일자'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_assigned_claims (
  claim_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  assignment_date date DEFAULT NULL,
  original_creditor varchar(100) DEFAULT NULL,
  assigned_creditor varchar(100) DEFAULT NULL,
  amount int(11) DEFAULT 0,
  assignment_reason text DEFAULT NULL,
  court_name varchar(100) DEFAULT NULL,
  court_case_number varchar(50) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_disputed_claims (
  claim_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  creditor_principal int(11) DEFAULT 0,
  creditor_interest int(11) DEFAULT 0,
  undisputed_principal int(11) DEFAULT 0,
  undisputed_interest int(11) DEFAULT 0,
  difference_principal int(11) DEFAULT 0,
  difference_interest int(11) DEFAULT 0,
  dispute_reason text DEFAULT NULL,
  litigation_status text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_guaranteed_debts (
  debt_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  guarantor_name varchar(100) NOT NULL COMMENT '보증인명',
  guarantor_address varchar(255) DEFAULT NULL COMMENT '보증인주소',
  guarantor_phone varchar(20) DEFAULT NULL,
  relationship varchar(50) DEFAULT NULL,
  guarantee_amount int(11) DEFAULT NULL,
  guarantee_type varchar(50) DEFAULT NULL,
  guarantee_date date DEFAULT NULL COMMENT '보증일자',
  notes text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_other_claims (
  claim_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  claim_type varchar(50) NOT NULL DEFAULT '다툼있는채권' COMMENT '채권종류',
  creditor_principal int(11) DEFAULT 0 COMMENT '채권자 주장(원금)',
  creditor_interest int(11) DEFAULT 0 COMMENT '채권자 주장(이자)',
  undisputed_principal int(11) DEFAULT 0 COMMENT '다툼없는 부분(원금)',
  undisputed_interest int(11) DEFAULT 0 COMMENT '다툼없는 부분(이자)',
  difference_principal int(11) DEFAULT 0 COMMENT '차이나는 부분(원금)',
  difference_interest int(11) DEFAULT 0 COMMENT '차이나는 부분(이자)',
  dispute_reason text DEFAULT NULL COMMENT '다툼의 원인',
  litigation_status text DEFAULT NULL COMMENT '소송제기 여부 및 진행경과',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_other_debts (
  debt_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  debt_description text DEFAULT NULL,
  amount int(11) DEFAULT 0,
  notes text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_settings (
  setting_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  principal_interest_sum tinyint(1) DEFAULT 0,
  list_creation_date date DEFAULT NULL,
  claim_calculation_date date DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_creditor_undetermined_claims (
  claim_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  creditor_count int(11) NOT NULL,
  claim_description text DEFAULT NULL,
  estimated_amount int(11) DEFAULT 0,
  determination_criteria text DEFAULT NULL,
  determination_date date DEFAULT NULL,
  status varchar(50) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_family_members (
  member_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  relation varchar(50) DEFAULT NULL,
  name varchar(100) DEFAULT NULL,
  age int(11) DEFAULT NULL,
  live_together enum('Y','N') DEFAULT 'N',
  live_period varchar(100) DEFAULT NULL,
  job varchar(100) DEFAULT NULL,
  income int(11) DEFAULT 0,
  assets int(11) DEFAULT 0,
  support enum('Y','N') DEFAULT 'N',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_financial_institutions (
  institution_no int(11) NOT NULL,
  name varchar(100) NOT NULL COMMENT '금융기관명',
  postal_code varchar(10) DEFAULT NULL COMMENT '우편번호',
  address varchar(255) DEFAULT NULL COMMENT '주소',
  phone varchar(20) DEFAULT NULL COMMENT '전화번호',
  fax varchar(20) DEFAULT NULL COMMENT '팩스번호',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_financial_institution_requests (
  request_no int(11) NOT NULL,
  original_institution_no int(11) DEFAULT NULL,
  name varchar(100) NOT NULL,
  postal_code varchar(10) DEFAULT NULL COMMENT '우편번호',
  address varchar(255) DEFAULT NULL COMMENT '주소',
  phone varchar(20) DEFAULT NULL COMMENT '전화번호',
  fax varchar(20) DEFAULT NULL COMMENT '팩스번호',
  request_type enum('추가','수정') NOT NULL DEFAULT '추가',
  status enum('대기','승인','반려') NOT NULL DEFAULT '대기',
  request_source varchar(100) DEFAULT NULL COMMENT '요청 출처 도메인',
  source_ip varchar(45) DEFAULT NULL COMMENT '요청 IP',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_income_business (
  business_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  type varchar(50) DEFAULT NULL,
  type_etc varchar(100) DEFAULT NULL,
  monthly_income int(11) DEFAULT 0,
  yearly_income int(11) DEFAULT 0,
  business_name varchar(100) DEFAULT NULL,
  sector varchar(100) DEFAULT NULL,
  career varchar(100) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_income_expenditure (
  income_no int(11) NOT NULL,
  case_no varchar(20) NOT NULL COMMENT '사건번호',
  year int(4) NOT NULL COMMENT '연도',
  month1_average int(11) DEFAULT 0,
  base_income int(11) DEFAULT 0,
  is_seized enum('Y','N') DEFAULT 'N',
  income_percentage int(11) DEFAULT 0,
  living_expense_type enum('Y','N') DEFAULT 'N' COMMENT '생계비 면제 유형(Y:기준변제내,N:기준변제조정)',
  living_expense_amount int(11) DEFAULT 0,
  living_expense_period int(3) DEFAULT 0 COMMENT '생계비 기간(개월)',
  other_exempt_amount int(11) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회생 소득/지출 관리';

CREATE TABLE application_recovery_income_salary (
  salary_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  monthly_income int(11) DEFAULT 0,
  yearly_income int(11) NOT NULL DEFAULT 0,
  is_seized enum('Y','N') DEFAULT 'N',
  company_name varchar(100) DEFAULT NULL,
  position varchar(50) DEFAULT NULL,
  work_period varchar(100) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_living_expenses (
  expense_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  type enum('생계비','주거비','의료비','교육비','기타') NOT NULL COMMENT '비목',
  amount int(11) NOT NULL DEFAULT 0 COMMENT '생계비 추가금액',
  reason text DEFAULT NULL COMMENT '추가사유',
  additional_note text DEFAULT NULL COMMENT '추가지출사유 보충기재사항',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_plan10 (
  plan_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  title varchar(255) DEFAULT NULL,
  content text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_prohibition_orders (
  order_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  application text NOT NULL,
  purpose text NOT NULL,
  reason text NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE application_recovery_statement_bankruptcy_reason (
  bankruptcy_reason_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  reasons text DEFAULT NULL,
  detail text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_career (
  career_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  company_type varchar(20) DEFAULT NULL,
  business_type varchar(100) DEFAULT NULL,
  company_name varchar(100) DEFAULT NULL,
  position varchar(50) DEFAULT NULL,
  work_start_date date DEFAULT NULL,
  work_end_date date DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_debt_relief (
  debt_relief_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  relief_type varchar(50) DEFAULT NULL,
  institution varchar(100) DEFAULT NULL,
  application_date date DEFAULT NULL,
  current_status varchar(100) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_education (
  education_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  school_name varchar(100) DEFAULT NULL,
  graduation_date date DEFAULT NULL,
  graduation_status varchar(20) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_housing (
  housing_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  housing_type varchar(100) DEFAULT NULL,
  deposit_amount decimal(15,0) DEFAULT NULL,
  monthly_rent decimal(15,0) DEFAULT NULL,
  overdue_amount decimal(15,0) DEFAULT NULL,
  owner_name varchar(50) DEFAULT NULL,
  relationship varchar(50) DEFAULT NULL,
  etc_description text DEFAULT NULL,
  residence_start_date date DEFAULT NULL,
  tenant_name varchar(50) DEFAULT NULL,
  additional_info text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_lawsuit (
  lawsuit_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  lawsuit_type varchar(100) DEFAULT NULL,
  creditor varchar(100) DEFAULT NULL,
  court varchar(100) DEFAULT NULL,
  case_number varchar(100) DEFAULT NULL,
  lawsuit_date date DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_statement_marriage (
  marriage_id int(11) NOT NULL,
  case_no int(11) NOT NULL,
  marriage_status varchar(20) DEFAULT NULL,
  marriage_date date DEFAULT NULL,
  spouse_name varchar(50) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_recovery_stay_orders (
  order_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  application text NOT NULL COMMENT '중지명령 신청서',
  purpose text NOT NULL COMMENT '신청취지',
  reason text NOT NULL COMMENT '신청원인',
  method text NOT NULL COMMENT '소명방법',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE case_management (
  case_no int(11) NOT NULL,
  consult_no int(11) DEFAULT NULL,
  paper_no int(11) DEFAULT NULL,
  datetime datetime NOT NULL DEFAULT current_timestamp(),
  category enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  case_number varchar(50) DEFAULT NULL,
  contract_date date DEFAULT NULL,
  application_fee int(11) DEFAULT 0,
  payment_amount int(11) DEFAULT 0,
  unpaid_amount int(11) DEFAULT 0,
  name varchar(50) DEFAULT NULL,
  phone varchar(15) DEFAULT NULL,
  consultant int(11) DEFAULT NULL,
  paper int(11) DEFAULT NULL,
  court_name varchar(50) DEFAULT NULL,
  assign_date date DEFAULT NULL,
  accept_date date DEFAULT NULL,
  start_date date DEFAULT NULL,
  approval_date date DEFAULT NULL,
  status enum('접수','개시','인가','종결','기각','취하','폐지','기타') DEFAULT '접수',
  memo text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE case_management_content (
  content_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  content text NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  bank varchar(50) DEFAULT NULL,
  checker_id int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE chatbot (
  no int(11) NOT NULL,
  conversation_id varchar(100) NOT NULL,
  member int(11) NOT NULL,
  question text DEFAULT NULL,
  answer text DEFAULT NULL,
  file_metadata text DEFAULT NULL,
  feedback enum('helpful','not_helpful') DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE chatbot_files (
  file_id int(11) NOT NULL,
  chat_no int(11) NOT NULL,
  original_name varchar(255) NOT NULL,
  saved_name varchar(255) NOT NULL,
  file_type varchar(100) NOT NULL,
  file_size int(11) NOT NULL,
  created_at datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE chatbot_settings (
  setting_id int(11) NOT NULL,
  member int(11) NOT NULL,
  setting_key varchar(50) NOT NULL,
  setting_value text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE config (
  no int(11) NOT NULL,
  customer_id varchar(255) NOT NULL,
  customer_number varchar(255) NOT NULL,
  icode_key varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE consult_manager (
  consult_no int(11) NOT NULL,
  inflow_no int(11) DEFAULT NULL,
  datetime datetime NOT NULL,
  inflow varchar(50) DEFAULT NULL,
  category enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  phone varchar(15) DEFAULT NULL,
  name varchar(50) DEFAULT NULL,
  consultant int(11) DEFAULT NULL,
  birth_date date DEFAULT NULL,
  debt_amount varchar(50) DEFAULT NULL,
  region varchar(50) DEFAULT NULL,
  consultation_time varchar(50) DEFAULT NULL,
  content text DEFAULT NULL,
  result text DEFAULT NULL,
  contract tinyint(1) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  prospect enum('부재','불가','낮음','높음') DEFAULT NULL,
  paper int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE consult_manager_content (
  content_no int(11) NOT NULL,
  consult_no int(11) NOT NULL,
  content text NOT NULL,
  manager_id int(11) NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE consult_paper (
  paper_no int(11) NOT NULL,
  consult_no int(11) NOT NULL,
  manager_id int(11) DEFAULT NULL,
  datetime datetime NOT NULL DEFAULT current_timestamp(),
  category enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  assign_date date DEFAULT NULL,
  start_date date DEFAULT NULL,
  accept_date date DEFAULT NULL,
  approval_date date DEFAULT NULL,
  phone varchar(15) DEFAULT NULL,
  case_number varchar(50) DEFAULT NULL,
  name varchar(50) DEFAULT NULL,
  status enum('접수','개시','인가','종결','기각','취하','폐지','기타') DEFAULT '접수',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE consult_paper_content (
  content_no int(11) NOT NULL,
  paper_no int(11) NOT NULL,
  content text NOT NULL,
  manager_id int(11) NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE employee (
  employee_no int(11) NOT NULL,
  employee_id varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  name varchar(100) NOT NULL,
  position varchar(100) NOT NULL,
  department varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  phone varchar(20) NOT NULL,
  hire_date date DEFAULT NULL,
  access_date date DEFAULT NULL,
  status enum('재직','휴직','퇴사') NOT NULL DEFAULT '재직',
  auth int(11) NOT NULL DEFAULT 0,
  initial_page varchar(255) NOT NULL DEFAULT '["case.php"]',
  font_size varchar(10) NOT NULL DEFAULT '11px',
  memo text DEFAULT NULL,
  internal_db_access text DEFAULT NULL,
  company_name varchar(100) DEFAULT NULL COMMENT '회사명',
  company_tel varchar(20) DEFAULT NULL COMMENT '회사 전화번호',
  company_fax varchar(20) DEFAULT NULL COMMENT '회사 팩스번호',
  company_address varchar(255) DEFAULT NULL COMMENT '회사 주소',
  business_number varchar(20) DEFAULT NULL COMMENT '사업자등록번호',
  member_type enum('personal','business') DEFAULT 'personal' COMMENT '회원 유형',
  representative varchar(100) DEFAULT NULL COMMENT '대표자명'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE employee_department (
  dept_id int(11) NOT NULL,
  dept_name varchar(50) NOT NULL,
  manager_id int(11) DEFAULT NULL,
  use_yn char(1) DEFAULT 'Y',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE employee_position (
  position_id int(11) NOT NULL,
  position_name varchar(50) NOT NULL,
  position_order int(11) NOT NULL,
  use_yn char(1) DEFAULT 'Y',
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE inflow (
  no int(11) NOT NULL,
  datetime datetime NOT NULL,
  inflow_page varchar(255) DEFAULT NULL,
  inflow varchar(255) DEFAULT NULL,
  category enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  phone varchar(20) DEFAULT NULL,
  name varchar(100) DEFAULT NULL,
  manager int(11) DEFAULT NULL,
  sms_sent tinyint(1) DEFAULT 0,
  content text DEFAULT NULL,
  birth_date date DEFAULT NULL,
  debt_amount varchar(50) DEFAULT NULL,
  region varchar(50) DEFAULT NULL,
  consultation_time varchar(50) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  ip varchar(50) NOT NULL,
  user_agent text DEFAULT NULL,
  device_type varchar(10) DEFAULT NULL,
  status varchar(20) DEFAULT '신규'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `schedule` (
  schedule_no int(11) NOT NULL,
  category varchar(50) DEFAULT NULL COMMENT '구분',
  name varchar(100) DEFAULT NULL COMMENT '이름',
  date date NOT NULL COMMENT '일자',
  time time DEFAULT NULL COMMENT '시간',
  content text DEFAULT NULL COMMENT '내용',
  location varchar(255) DEFAULT NULL COMMENT '기관/장소',
  memo text DEFAULT NULL COMMENT '비고',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE statistics_court (
  id int(11) NOT NULL,
  court_name varchar(50) NOT NULL,
  recovery_count int(11) NOT NULL,
  bankruptcy_count int(11) NOT NULL,
  recovery_start_rate decimal(5,2) NOT NULL,
  recovery_reject_rate decimal(5,2) NOT NULL,
  bankruptcy_discharge_rate decimal(5,2) NOT NULL,
  bankruptcy_reject_rate decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE statistics_yearly (
  id int(11) NOT NULL,
  year int(4) NOT NULL,
  recovery_count int(11) NOT NULL,
  bankruptcy_count int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stay_orders (
  order_no int(11) NOT NULL,
  case_no int(11) NOT NULL,
  content text NOT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE application_bankruptcy
  ADD PRIMARY KEY (bankruptcy_no),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_assets
  ADD PRIMARY KEY (asset_no),
  ADD KEY fk_bankruptcy_assets_case (case_no);

ALTER TABLE application_bankruptcy_asset_cash
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_disposed
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_divorce
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_inherited
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_insurance
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_loan_receivables
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_other
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_real_estate
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_received_deposit
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_rent_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_sales_receivables
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_severance
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_asset_summary
  ADD PRIMARY KEY (summary_id),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_bankruptcy_asset_vehicles
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no,property_no);

ALTER TABLE application_bankruptcy_creditor
  ADD PRIMARY KEY (creditor_no),
  ADD UNIQUE KEY case_creditor_unique (case_no,creditor_count),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_dependents
  ADD PRIMARY KEY (dependent_id),
  ADD KEY case_no (case_no);

ALTER TABLE application_bankruptcy_income_expenditure
  ADD PRIMARY KEY (case_no);

ALTER TABLE application_bankruptcy_living_status_additional
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_bankruptcy_living_status_basic
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_bankruptcy_living_status_family
  ADD PRIMARY KEY (member_id),
  ADD KEY case_no (case_no);

ALTER TABLE application_bankruptcy_living_status_income
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_bankruptcy_living_status_tax
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_bankruptcy_prohibition_orders
  ADD PRIMARY KEY (order_no),
  ADD KEY fk_bankruptcy_prohibition_orders_case (case_no);

ALTER TABLE application_bankruptcy_statement
  ADD PRIMARY KEY (statement_no),
  ADD KEY fk_bankruptcy_statement_case (case_no);

ALTER TABLE application_bankruptcy_statement_bankruptcy_history
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_bankruptcy_reason
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_career
  ADD PRIMARY KEY (career_id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_creditor_status
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_debt_after_insolvency
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_domestic_court
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_education
  ADD PRIMARY KEY (education_id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_legal_action
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_life_history
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_statement_partial_repayment
  ADD PRIMARY KEY (id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_bankruptcy_stay_orders
  ADD PRIMARY KEY (order_no),
  ADD KEY fk_bankruptcy_stay_orders_case (case_no);

ALTER TABLE application_income_living_expense_standard
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_year_family_members (year,family_members);

ALTER TABLE application_recovery
  ADD PRIMARY KEY (recovery_no),
  ADD KEY case_no (case_no),
  ADD KEY assigned_employee (assigned_employee);

ALTER TABLE application_recovery_additional_claims
  ADD PRIMARY KEY (claim_no);

ALTER TABLE application_recovery_asset_attached_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_business
  ADD PRIMARY KEY (asset_no);

ALTER TABLE application_recovery_asset_cash
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_court_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_disposed
  ADD PRIMARY KEY (asset_no);

ALTER TABLE application_recovery_asset_divorce
  ADD PRIMARY KEY (asset_no);

ALTER TABLE application_recovery_asset_exemption1
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_exemption2
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_inherited
  ADD PRIMARY KEY (asset_no);

ALTER TABLE application_recovery_asset_insurance
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_loan_receivables
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_other
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_real_estate
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no),
  ADD KEY property_no (property_no);

ALTER TABLE application_recovery_asset_received_deposit
  ADD PRIMARY KEY (asset_no);

ALTER TABLE application_recovery_asset_rent_deposits
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_sales_receivables
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_severance
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_asset_vehicles
  ADD PRIMARY KEY (asset_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_creditor
  ADD PRIMARY KEY (creditor_no),
  ADD UNIQUE KEY case_creditor_unique (case_no,creditor_count),
  ADD KEY idx_case_no (case_no);

ALTER TABLE application_recovery_creditor_appendix
  ADD PRIMARY KEY (appendix_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_assigned_claims
  ADD PRIMARY KEY (claim_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_disputed_claims
  ADD PRIMARY KEY (claim_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_guaranteed_debts
  ADD PRIMARY KEY (debt_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_other_claims
  ADD PRIMARY KEY (claim_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_other_debts
  ADD PRIMARY KEY (debt_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_creditor_settings
  ADD PRIMARY KEY (setting_no),
  ADD UNIQUE KEY unique_case_setting (case_no);

ALTER TABLE application_recovery_creditor_undetermined_claims
  ADD PRIMARY KEY (claim_no),
  ADD KEY idx_case_creditor (case_no,creditor_count);

ALTER TABLE application_recovery_family_members
  ADD PRIMARY KEY (member_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_financial_institutions
  ADD PRIMARY KEY (institution_no),
  ADD KEY idx_name (name);

ALTER TABLE application_recovery_financial_institution_requests
  ADD PRIMARY KEY (request_no),
  ADD KEY original_institution_no (original_institution_no);

ALTER TABLE application_recovery_income_business
  ADD PRIMARY KEY (business_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_income_expenditure
  ADD PRIMARY KEY (income_no),
  ADD UNIQUE KEY idx_case_year (case_no,year);

ALTER TABLE application_recovery_income_salary
  ADD PRIMARY KEY (salary_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_living_expenses
  ADD PRIMARY KEY (expense_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_plan10
  ADD PRIMARY KEY (plan_no),
  ADD UNIQUE KEY case_no (case_no);

ALTER TABLE application_recovery_prohibition_orders
  ADD PRIMARY KEY (order_no),
  ADD KEY case_no (case_no);

ALTER TABLE application_recovery_statement_bankruptcy_reason
  ADD PRIMARY KEY (bankruptcy_reason_id),
  ADD UNIQUE KEY case_no (case_no),
  ADD KEY idx_bankruptcy_reason_case_no (case_no);

ALTER TABLE application_recovery_statement_career
  ADD PRIMARY KEY (career_id),
  ADD KEY idx_career_case_no (case_no);

ALTER TABLE application_recovery_statement_debt_relief
  ADD PRIMARY KEY (debt_relief_id),
  ADD KEY idx_debt_relief_case_no (case_no),
  ADD KEY idx_debt_relief_type (relief_type);

ALTER TABLE application_recovery_statement_education
  ADD PRIMARY KEY (education_id),
  ADD UNIQUE KEY case_no (case_no),
  ADD KEY idx_education_case_no (case_no);

ALTER TABLE application_recovery_statement_housing
  ADD PRIMARY KEY (housing_id),
  ADD UNIQUE KEY case_no (case_no),
  ADD KEY idx_housing_case_no (case_no);

ALTER TABLE application_recovery_statement_lawsuit
  ADD PRIMARY KEY (lawsuit_id),
  ADD KEY idx_lawsuit_case_no (case_no);

ALTER TABLE application_recovery_statement_marriage
  ADD PRIMARY KEY (marriage_id),
  ADD KEY idx_marriage_case_no (case_no);

ALTER TABLE application_recovery_stay_orders
  ADD PRIMARY KEY (order_no),
  ADD KEY case_no (case_no);

ALTER TABLE case_management
  ADD PRIMARY KEY (case_no),
  ADD KEY consult_no (consult_no),
  ADD KEY paper_no (paper_no),
  ADD KEY manager_id (consultant),
  ADD KEY case_management_paper_fk (paper);

ALTER TABLE case_management_content
  ADD PRIMARY KEY (content_no),
  ADD KEY case_no (case_no),
  ADD KEY checker_id (checker_id);

ALTER TABLE chatbot
  ADD PRIMARY KEY (no),
  ADD KEY member (member),
  ADD KEY conversation_id (conversation_id);

ALTER TABLE chatbot_files
  ADD PRIMARY KEY (file_id),
  ADD KEY chat_no (chat_no);

ALTER TABLE chatbot_settings
  ADD PRIMARY KEY (setting_id),
  ADD UNIQUE KEY member_setting (member,setting_key);

ALTER TABLE config
  ADD PRIMARY KEY (no);

ALTER TABLE consult_manager
  ADD PRIMARY KEY (consult_no),
  ADD KEY consultant (consultant),
  ADD KEY inflow_no (inflow_no),
  ADD KEY paper (paper);

ALTER TABLE consult_manager_content
  ADD PRIMARY KEY (content_no),
  ADD KEY consult_no (consult_no),
  ADD KEY manager_id (manager_id);

ALTER TABLE consult_paper
  ADD PRIMARY KEY (paper_no),
  ADD KEY manager_id (manager_id),
  ADD KEY consult_no (consult_no);

ALTER TABLE consult_paper_content
  ADD PRIMARY KEY (content_no),
  ADD KEY paper_no (paper_no),
  ADD KEY manager_id (manager_id);

ALTER TABLE employee
  ADD PRIMARY KEY (employee_no),
  ADD UNIQUE KEY member_id (employee_id);

ALTER TABLE employee_department
  ADD PRIMARY KEY (dept_id);

ALTER TABLE employee_position
  ADD PRIMARY KEY (position_id);

ALTER TABLE inflow
  ADD PRIMARY KEY (no),
  ADD KEY manager (manager);

ALTER TABLE schedule
  ADD PRIMARY KEY (schedule_no);

ALTER TABLE statistics_court
  ADD PRIMARY KEY (id);

ALTER TABLE statistics_yearly
  ADD PRIMARY KEY (id);

ALTER TABLE stay_orders
  ADD PRIMARY KEY (order_no),
  ADD KEY case_no (case_no);


ALTER TABLE application_bankruptcy
  MODIFY bankruptcy_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_assets
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_cash
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_disposed
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_divorce
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_inherited
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_insurance
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_loan_receivables
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_other
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_real_estate
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_received_deposit
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_rent_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_sales_receivables
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_severance
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_summary
  MODIFY summary_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_asset_vehicles
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_creditor
  MODIFY creditor_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_dependents
  MODIFY dependent_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_living_status_additional
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_living_status_basic
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_living_status_family
  MODIFY member_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_living_status_income
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_living_status_tax
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_prohibition_orders
  MODIFY order_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement
  MODIFY statement_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_bankruptcy_history
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_bankruptcy_reason
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_career
  MODIFY career_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_creditor_status
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_debt_after_insolvency
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_domestic_court
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_education
  MODIFY education_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_legal_action
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_life_history
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_statement_partial_repayment
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_bankruptcy_stay_orders
  MODIFY order_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_income_living_expense_standard
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery
  MODIFY recovery_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_additional_claims
  MODIFY claim_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_attached_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_business
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_cash
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_court_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_disposed
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_divorce
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_exemption1
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_exemption2
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_inherited
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_insurance
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_loan_receivables
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_other
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_real_estate
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_received_deposit
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_rent_deposits
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_sales_receivables
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_severance
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_asset_vehicles
  MODIFY asset_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor
  MODIFY creditor_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_appendix
  MODIFY appendix_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_assigned_claims
  MODIFY claim_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_disputed_claims
  MODIFY claim_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_guaranteed_debts
  MODIFY debt_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_other_claims
  MODIFY claim_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_other_debts
  MODIFY debt_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_settings
  MODIFY setting_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_creditor_undetermined_claims
  MODIFY claim_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_family_members
  MODIFY member_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_financial_institutions
  MODIFY institution_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_financial_institution_requests
  MODIFY request_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_income_business
  MODIFY business_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_income_expenditure
  MODIFY income_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_income_salary
  MODIFY salary_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_living_expenses
  MODIFY expense_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_plan10
  MODIFY plan_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_prohibition_orders
  MODIFY order_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_bankruptcy_reason
  MODIFY bankruptcy_reason_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_career
  MODIFY career_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_debt_relief
  MODIFY debt_relief_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_education
  MODIFY education_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_housing
  MODIFY housing_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_lawsuit
  MODIFY lawsuit_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_statement_marriage
  MODIFY marriage_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE application_recovery_stay_orders
  MODIFY order_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE case_management
  MODIFY case_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE case_management_content
  MODIFY content_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE chatbot
  MODIFY no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE chatbot_files
  MODIFY file_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE chatbot_settings
  MODIFY setting_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE config
  MODIFY no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE consult_manager
  MODIFY consult_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE consult_manager_content
  MODIFY content_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE consult_paper
  MODIFY paper_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE consult_paper_content
  MODIFY content_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE employee
  MODIFY employee_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE employee_department
  MODIFY dept_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE employee_position
  MODIFY position_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE inflow
  MODIFY no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE schedule
  MODIFY schedule_no int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE statistics_court
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE statistics_yearly
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE stay_orders
  MODIFY order_no int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE application_bankruptcy_assets
  ADD CONSTRAINT fk_bankruptcy_assets_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_creditor
  ADD CONSTRAINT fk_bankruptcy_creditor_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_prohibition_orders
  ADD CONSTRAINT fk_bankruptcy_prohibition_orders_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement
  ADD CONSTRAINT fk_bankruptcy_statement_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_bankruptcy_history
  ADD CONSTRAINT fk_statement_bankruptcy_history_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_bankruptcy_reason
  ADD CONSTRAINT fk_statement_bankruptcy_reason_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_career
  ADD CONSTRAINT fk_statement_career_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_creditor_status
  ADD CONSTRAINT fk_statement_creditor_status_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_debt_after_insolvency
  ADD CONSTRAINT fk_statement_debt_after_insolvency_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_domestic_court
  ADD CONSTRAINT fk_statement_domestic_court_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_education
  ADD CONSTRAINT fk_statement_education_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_legal_action
  ADD CONSTRAINT fk_statement_legal_action_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_life_history
  ADD CONSTRAINT fk_statement_life_history_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_statement_partial_repayment
  ADD CONSTRAINT fk_statement_partial_repayment_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_bankruptcy_stay_orders
  ADD CONSTRAINT fk_bankruptcy_stay_orders_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery
  ADD CONSTRAINT application_recovery_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no),
  ADD CONSTRAINT application_recovery_ibfk_2 FOREIGN KEY (assigned_employee) REFERENCES employee (employee_no);

ALTER TABLE application_recovery_asset_attached_deposits
  ADD CONSTRAINT fk_attached_deposits_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_cash
  ADD CONSTRAINT fk_cash_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_court_deposits
  ADD CONSTRAINT fk_court_deposits_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_deposits
  ADD CONSTRAINT fk_deposits_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_exemption1
  ADD CONSTRAINT fk_exemption1_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_exemption2
  ADD CONSTRAINT fk_exemption2_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_insurance
  ADD CONSTRAINT fk_insurance_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_loan_receivables
  ADD CONSTRAINT fk_loan_receivables_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_other
  ADD CONSTRAINT fk_other_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_real_estate
  ADD CONSTRAINT fk_real_estate_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_rent_deposits
  ADD CONSTRAINT fk_lease_deposits_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_sales_receivables
  ADD CONSTRAINT fk_sales_receivables_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_severance
  ADD CONSTRAINT fk_severance_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_asset_vehicles
  ADD CONSTRAINT fk_vehicles_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor
  ADD CONSTRAINT fk_creditor_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_appendix
  ADD CONSTRAINT fk_mortgage_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_assigned_claims
  ADD CONSTRAINT fk_assigned_claims_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_disputed_claims
  ADD CONSTRAINT fk_disputed_claims_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_guaranteed_debts
  ADD CONSTRAINT fk_guaranteed_debts_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_other_debts
  ADD CONSTRAINT fk_other_debts_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_settings
  ADD CONSTRAINT application_recovery_creditor_settings_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_creditor_undetermined_claims
  ADD CONSTRAINT fk_undetermined_claims_creditor FOREIGN KEY (case_no,creditor_count) REFERENCES application_recovery_creditor (case_no, creditor_count) ON DELETE CASCADE;

ALTER TABLE application_recovery_family_members
  ADD CONSTRAINT fk_family_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_financial_institution_requests
  ADD CONSTRAINT institution_requests_original_fk FOREIGN KEY (original_institution_no) REFERENCES application_recovery_financial_institutions (institution_no) ON DELETE SET NULL;

ALTER TABLE application_recovery_income_business
  ADD CONSTRAINT fk_business_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_income_salary
  ADD CONSTRAINT fk_salary_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_living_expenses
  ADD CONSTRAINT fk_living_expenses_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_plan10
  ADD CONSTRAINT fk_plan10_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_prohibition_orders
  ADD CONSTRAINT application_recovery_prohibition_orders_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_bankruptcy_reason
  ADD CONSTRAINT application_recovery_statement_bankruptcy_reason_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_career
  ADD CONSTRAINT application_recovery_statement_career_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_debt_relief
  ADD CONSTRAINT application_recovery_statement_debt_relief_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_education
  ADD CONSTRAINT application_recovery_statement_education_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_housing
  ADD CONSTRAINT application_recovery_statement_housing_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_lawsuit
  ADD CONSTRAINT application_recovery_statement_lawsuit_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_statement_marriage
  ADD CONSTRAINT application_recovery_statement_marriage_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE;

ALTER TABLE application_recovery_stay_orders
  ADD CONSTRAINT application_recovery_stay_orders_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no);

ALTER TABLE case_management
  ADD CONSTRAINT case_management_consultant_fk FOREIGN KEY (consultant) REFERENCES employee (employee_no),
  ADD CONSTRAINT case_management_paper_fk FOREIGN KEY (paper) REFERENCES employee (employee_no);

ALTER TABLE case_management_content
  ADD CONSTRAINT case_content_case_fk FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE CASCADE,
  ADD CONSTRAINT case_management_content_ibfk_1 FOREIGN KEY (checker_id) REFERENCES employee (employee_no);

ALTER TABLE chatbot
  ADD CONSTRAINT chatbot_ibfk_1 FOREIGN KEY (member) REFERENCES employee (employee_no) ON DELETE CASCADE;

ALTER TABLE chatbot_files
  ADD CONSTRAINT chatbot_files_ibfk_1 FOREIGN KEY (chat_no) REFERENCES chatbot (no) ON DELETE CASCADE;

ALTER TABLE chatbot_settings
  ADD CONSTRAINT chatbot_settings_ibfk_1 FOREIGN KEY (member) REFERENCES employee (employee_no) ON DELETE CASCADE;

ALTER TABLE consult_manager
  ADD CONSTRAINT consult_ibfk_1 FOREIGN KEY (consultant) REFERENCES employee (employee_no),
  ADD CONSTRAINT consult_manager_ibfk_1 FOREIGN KEY (paper) REFERENCES employee (employee_no);

ALTER TABLE consult_manager_content
  ADD CONSTRAINT content_consult_fk FOREIGN KEY (consult_no) REFERENCES consult_manager (consult_no) ON DELETE CASCADE,
  ADD CONSTRAINT content_manager_fk FOREIGN KEY (manager_id) REFERENCES employee (employee_no);

ALTER TABLE consult_paper
  ADD CONSTRAINT consult_paper_ibfk_1 FOREIGN KEY (manager_id) REFERENCES employee (employee_no),
  ADD CONSTRAINT consult_paper_ibfk_2 FOREIGN KEY (consult_no) REFERENCES consult_manager (consult_no);

ALTER TABLE consult_paper_content
  ADD CONSTRAINT paper_content_manager_fk FOREIGN KEY (manager_id) REFERENCES employee (employee_no),
  ADD CONSTRAINT paper_content_paper_fk FOREIGN KEY (paper_no) REFERENCES consult_paper (paper_no) ON DELETE CASCADE;

ALTER TABLE inflow
  ADD CONSTRAINT inflow_ibfk_1 FOREIGN KEY (manager) REFERENCES employee (employee_no);

ALTER TABLE stay_orders
  ADD CONSTRAINT stay_orders_ibfk_1 FOREIGN KEY (case_no) REFERENCES case_management (case_no);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
