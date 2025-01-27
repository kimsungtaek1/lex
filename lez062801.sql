-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- 생성 시간: 25-01-17 13:17
-- 서버 버전: 10.6.17-MariaDB-log
-- PHP 버전: 8.2.7p1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `lez062801`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `application_bankruptcy`
--

CREATE TABLE `application_bankruptcy` (
  `bankruptcy_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `resident_number` varchar(14) DEFAULT NULL,
  `registered_address` varchar(255) DEFAULT NULL,
  `now_address` varchar(255) DEFAULT NULL,
  `work_address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `workplace` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT 0,
  `debt_total` int(11) DEFAULT NULL,
  `income_monthly` int(11) DEFAULT NULL,
  `expense_monthly` int(11) DEFAULT NULL,
  `assets_total` int(11) DEFAULT NULL,
  `memo` text DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `unspecified_date` tinyint(1) DEFAULT 0,
  `court_name` varchar(50) DEFAULT NULL,
  `case_year` varchar(4) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('신청','면책','기각','취하') DEFAULT '신청',
  `assigned_employee` int(11) DEFAULT NULL,
  `work_period` varchar(100) DEFAULT NULL,
  `other_income` varchar(100) DEFAULT NULL,
  `other_income_name` varchar(100) DEFAULT NULL,
  `income_source` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery`
--

CREATE TABLE `application_recovery` (
  `recovery_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `resident_number` varchar(14) DEFAULT NULL,
  `registered_address` varchar(255) DEFAULT NULL,
  `now_address` varchar(255) DEFAULT NULL,
  `work_address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `workplace` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT 0,
  `debt_total` int(11) DEFAULT NULL,
  `income_monthly` int(11) DEFAULT NULL,
  `expense_monthly` int(11) DEFAULT NULL,
  `repayment_monthly` int(11) DEFAULT NULL,
  `assets_total` int(11) DEFAULT NULL,
  `memo` text DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `unspecified_date` tinyint(1) DEFAULT 0,
  `repayment_start_date` date DEFAULT NULL,
  `court_name` varchar(50) DEFAULT NULL,
  `case_year` varchar(4) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('신청','개시','인가','폐지','기각','취하') DEFAULT '신청',
  `assigned_employee` int(11) DEFAULT NULL,
  `work_period` varchar(100) DEFAULT NULL,
  `other_income` varchar(100) DEFAULT NULL,
  `other_income_name` varchar(100) DEFAULT NULL,
  `income_source` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_assets`
--

CREATE TABLE `application_recovery_assets` (
  `asset_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `asset_type` enum('cash','deposits','insurance','vehicles','leaseDeposits','realEstate','businessAssets','loanReceivables','salesReceivables','severancePay','attachedDeposits','courtDeposits','otherAssets','exemptionRequests1','exemptionRequests2') NOT NULL,
  `description` text DEFAULT NULL COMMENT '재산 세부 상황',
  `memo` text DEFAULT NULL COMMENT '비고',
  `amount` decimal(15,2) DEFAULT 0.00 COMMENT '금액',
  `is_seized` enum('Y','N') DEFAULT 'N' COMMENT '압류여부',
  `calculation_date` date DEFAULT NULL COMMENT '산정기준일',
  `location` varchar(255) DEFAULT NULL COMMENT '소재지',
  `area` decimal(10,2) DEFAULT NULL COMMENT '면적',
  `is_spouse` tinyint(1) DEFAULT 0 COMMENT '배우자명의',
  `expected_value` decimal(15,2) DEFAULT 0.00 COMMENT '환가예상액',
  `secured_debt` decimal(15,2) DEFAULT 0.00 COMMENT '피담보채무액',
  `deposit_debt` decimal(15,2) DEFAULT 0.00 COMMENT '보증금채무액',
  `liquidation_value` decimal(15,2) DEFAULT 0.00 COMMENT '청산가치',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_asset_details`
--

CREATE TABLE `application_recovery_asset_details` (
  `detail_no` int(11) NOT NULL,
  `asset_no` int(11) NOT NULL,
  `detail_type` varchar(50) NOT NULL COMMENT '상세정보 유형',
  `detail_key` varchar(50) NOT NULL COMMENT '키',
  `detail_value` text DEFAULT NULL COMMENT '값',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_creditor`
--

CREATE TABLE `application_recovery_creditor` (
  `creditor_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL COMMENT '사건번호',
  `creditor_count` int(11) NOT NULL COMMENT '채권자 순번',
  `entity_type` enum('자연인','법인','권리능력없는법인','국가','지방자치단체') NOT NULL COMMENT '인격구분',
  `financial_institution` varchar(100) DEFAULT NULL COMMENT '금융기관명',
  `postal_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `address` varchar(255) DEFAULT NULL COMMENT '주소',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `fax` varchar(20) DEFAULT NULL COMMENT '팩스번호',
  `principal` int(11) DEFAULT NULL COMMENT '원금',
  `principal_calculation` text DEFAULT NULL COMMENT '원금 산정근거',
  `interest` int(11) DEFAULT NULL COMMENT '이자',
  `interest_calculation` text DEFAULT NULL COMMENT '이자 산정근거',
  `default_rate` decimal(5,2) DEFAULT NULL COMMENT '연체이율',
  `claim_reason` varchar(255) DEFAULT NULL COMMENT '채권의 원인',
  `claim_content` text DEFAULT NULL COMMENT '채권의 내용',
  `priority_payment` tinyint(1) DEFAULT 0 COMMENT '우선변제',
  `undetermined_claim` tinyint(1) DEFAULT 0 COMMENT '미확정채권',
  `pension_debt` tinyint(1) DEFAULT 0 COMMENT '연금채무',
  `mortgage_restructuring` tinyint(1) DEFAULT 0 COMMENT '주택담보대출채권',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_creditor_appendix`
--

CREATE TABLE `application_recovery_creditor_appendix` (
  `appendix_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `creditor_count` int(11) NOT NULL,
  `appendix_type` enum('별제권부채권','다툼있는채권','전부명령된채권','기타') NOT NULL,
  `content` text NOT NULL COMMENT '내용',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_creditor_guaranteed_debts`
--

CREATE TABLE `application_recovery_creditor_guaranteed_debts` (
  `debt_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `creditor_count` int(11) NOT NULL,
  `guarantor_name` varchar(100) NOT NULL COMMENT '보증인명',
  `guarantor_address` varchar(255) DEFAULT NULL COMMENT '보증인주소',
  `guarantee_amount` decimal(15,2) DEFAULT NULL COMMENT '보증금액',
  `guarantee_date` date DEFAULT NULL COMMENT '보증일자',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_creditor_other_claims`
--

CREATE TABLE `application_recovery_creditor_other_claims` (
  `claim_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `creditor_count` int(11) NOT NULL,
  `claim_type` varchar(50) NOT NULL COMMENT '채권종류',
  `amount` decimal(15,2) DEFAULT NULL COMMENT '금액',
  `description` text DEFAULT NULL COMMENT '설명',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_creditor_settings`
--

CREATE TABLE `application_recovery_creditor_settings` (
  `setting_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `principal_interest_sum` tinyint(1) DEFAULT 0,
  `list_creation_date` date DEFAULT NULL,
  `claim_calculation_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_financial_institutions`
--

CREATE TABLE `application_recovery_financial_institutions` (
  `institution_no` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '금융기관명',
  `postal_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `address` varchar(255) DEFAULT NULL COMMENT '주소',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `fax` varchar(20) DEFAULT NULL COMMENT '팩스번호',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_financial_institution_requests`
--

CREATE TABLE `application_recovery_financial_institution_requests` (
  `request_no` int(11) NOT NULL,
  `original_institution_no` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `address` varchar(255) DEFAULT NULL COMMENT '주소',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `fax` varchar(20) DEFAULT NULL COMMENT '팩스번호',
  `request_type` enum('추가','수정') NOT NULL DEFAULT '추가',
  `status` enum('대기','승인','반려') NOT NULL DEFAULT '대기',
  `request_source` varchar(100) DEFAULT NULL COMMENT '요청 출처 도메인',
  `source_ip` varchar(45) DEFAULT NULL COMMENT '요청 IP',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_mortgage`
--

CREATE TABLE `application_recovery_mortgage` (
  `mortgage_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `creditor_count` int(11) NOT NULL,
  `property_detail` varchar(255) DEFAULT NULL COMMENT '목적물',
  `expected_value` decimal(15,0) DEFAULT NULL COMMENT '예상가액',
  `evaluation_rate` decimal(5,2) DEFAULT NULL COMMENT '평가비율',
  `max_claim` decimal(15,0) DEFAULT NULL COMMENT '최대청구액',
  `registration_date` date DEFAULT NULL COMMENT '등기일자',
  `secured_expected_claim` decimal(15,0) DEFAULT NULL COMMENT '③ 별제권 행사 등으로 변제가 예상되는 채권액',
  `unsecured_remaining_claim` decimal(15,0) DEFAULT NULL COMMENT '④ 별제권 행사 등으로도 변제 받을 수 없는 채권액',
  `rehabilitation_secured_claim` decimal(15,0) DEFAULT NULL COMMENT '➄ 담보부 회생채권액',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_prohibition_orders`
--

CREATE TABLE `application_recovery_prohibition_orders` (
  `order_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `application` text NOT NULL,
  `purpose` text NOT NULL,
  `reason` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `application_recovery_stay_orders`
--

CREATE TABLE `application_recovery_stay_orders` (
  `order_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `application` text NOT NULL COMMENT '중지명령 신청서',
  `purpose` text NOT NULL COMMENT '신청취지',
  `reason` text NOT NULL COMMENT '신청원인',
  `method` text NOT NULL COMMENT '소명방법',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `case_management`
--

CREATE TABLE `case_management` (
  `case_no` int(11) NOT NULL,
  `consult_no` int(11) DEFAULT NULL,
  `paper_no` int(11) DEFAULT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `category` enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  `case_number` varchar(50) DEFAULT NULL,
  `contract_date` date DEFAULT NULL,
  `application_fee` int(11) DEFAULT 0,
  `payment_amount` int(11) DEFAULT 0,
  `unpaid_amount` int(11) DEFAULT 0,
  `name` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `consultant` int(11) DEFAULT NULL,
  `paper` int(11) DEFAULT NULL,
  `court_name` varchar(50) DEFAULT NULL,
  `assign_date` date DEFAULT NULL,
  `accept_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `status` enum('접수','개시','인가','종결','기각','취하','폐지','기타') DEFAULT '접수',
  `memo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `case_management_content`
--

CREATE TABLE `case_management_content` (
  `content_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bank` varchar(50) DEFAULT NULL,
  `checker_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `chatbot`
--

CREATE TABLE `chatbot` (
  `no` int(11) NOT NULL,
  `conversation_id` varchar(100) NOT NULL,
  `member` int(11) NOT NULL,
  `question` text DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `file_metadata` text DEFAULT NULL,
  `feedback` enum('helpful','not_helpful') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `chatbot_files`
--

CREATE TABLE `chatbot_files` (
  `file_id` int(11) NOT NULL,
  `chat_no` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `saved_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `chatbot_settings`
--

CREATE TABLE `chatbot_settings` (
  `setting_id` int(11) NOT NULL,
  `member` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `config`
--

CREATE TABLE `config` (
  `no` int(11) NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `customer_number` varchar(255) NOT NULL,
  `icode_key` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `consult_manager`
--

CREATE TABLE `consult_manager` (
  `consult_no` int(11) NOT NULL,
  `inflow_no` int(11) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `inflow` varchar(50) DEFAULT NULL,
  `category` enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `consultant` int(11) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `debt_amount` varchar(50) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `consultation_time` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `result` text DEFAULT NULL,
  `contract` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `prospect` enum('부재','불가','낮음','높음') DEFAULT NULL,
  `paper` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `consult_manager_content`
--

CREATE TABLE `consult_manager_content` (
  `content_no` int(11) NOT NULL,
  `consult_no` int(11) NOT NULL,
  `content` text NOT NULL,
  `manager_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `consult_paper`
--

CREATE TABLE `consult_paper` (
  `paper_no` int(11) NOT NULL,
  `consult_no` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `category` enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  `assign_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `accept_date` date DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `case_number` varchar(50) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `status` enum('접수','개시','인가','종결','기각','취하','폐지','기타') DEFAULT '접수',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `consult_paper_content`
--

CREATE TABLE `consult_paper_content` (
  `content_no` int(11) NOT NULL,
  `paper_no` int(11) NOT NULL,
  `content` text NOT NULL,
  `manager_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `employee`
--

CREATE TABLE `employee` (
  `employee_no` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `hire_date` date DEFAULT NULL,
  `access_date` date DEFAULT NULL,
  `status` enum('재직','휴직','퇴사') NOT NULL DEFAULT '재직',
  `auth` int(11) NOT NULL DEFAULT 0,
  `initial_page` varchar(255) NOT NULL DEFAULT '["case.php"]',
  `font_size` varchar(10) NOT NULL DEFAULT '11px',
  `memo` text DEFAULT NULL,
  `internal_db_access` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `employee_department`
--

CREATE TABLE `employee_department` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `use_yn` char(1) DEFAULT 'Y',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `employee_position`
--

CREATE TABLE `employee_position` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(50) NOT NULL,
  `position_order` int(11) NOT NULL,
  `use_yn` char(1) DEFAULT 'Y',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `inflow`
--

CREATE TABLE `inflow` (
  `no` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `inflow_page` varchar(255) DEFAULT NULL,
  `inflow` varchar(255) DEFAULT NULL,
  `category` enum('개인회생급여','개인회생영업','개인파산') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `manager` int(11) DEFAULT NULL,
  `sms_sent` tinyint(1) DEFAULT 0,
  `content` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `debt_amount` varchar(50) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `consultation_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip` varchar(50) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT '신규'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `schedule`
--

CREATE TABLE `schedule` (
  `schedule_no` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL COMMENT '구분',
  `name` varchar(100) DEFAULT NULL COMMENT '이름',
  `date` date NOT NULL COMMENT '일자',
  `time` time DEFAULT NULL COMMENT '시간',
  `content` text DEFAULT NULL COMMENT '내용',
  `location` varchar(255) DEFAULT NULL COMMENT '기관/장소',
  `memo` text DEFAULT NULL COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `statistics_court`
--

CREATE TABLE `statistics_court` (
  `id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `recovery_count` int(11) NOT NULL,
  `bankruptcy_count` int(11) NOT NULL,
  `recovery_start_rate` decimal(5,2) NOT NULL,
  `recovery_reject_rate` decimal(5,2) NOT NULL,
  `bankruptcy_discharge_rate` decimal(5,2) NOT NULL,
  `bankruptcy_reject_rate` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `statistics_yearly`
--

CREATE TABLE `statistics_yearly` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `recovery_count` int(11) NOT NULL,
  `bankruptcy_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `stay_orders`
--

CREATE TABLE `stay_orders` (
  `order_no` int(11) NOT NULL,
  `case_no` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
