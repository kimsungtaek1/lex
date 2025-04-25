SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE ocr_dictionary (
  id int(11) NOT NULL,
  word varchar(255) NOT NULL,
  similar_words longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(similar_words)),
  frequency int(11) DEFAULT 1,
  is_active tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_documents (
  document_id int(11) NOT NULL,
  case_no int(11) DEFAULT NULL,
  document_type varchar(50) DEFAULT NULL COMMENT '문서 유형',
  file_name varchar(255) NOT NULL COMMENT '원본 파일명',
  file_path varchar(255) NOT NULL COMMENT '저장된 파일 경로',
  extracted_text longtext DEFAULT NULL COMMENT '추출된 텍스트',
  extracted_table_json longtext DEFAULT NULL COMMENT '추출된 표 데이터(JSON)',
  ocr_status enum('대기중','처리중','완료','실패') DEFAULT '대기중',
  ocr_error_message text DEFAULT NULL COMMENT '에러 메시지',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ocr_document_templates (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  description text DEFAULT NULL,
  fields longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  table_structure longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(table_structure)),
  is_public tinyint(1) DEFAULT 0,
  is_active tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  learning_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(learning_data)),
  learning_statistics longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(learning_statistics))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_feedback (
  id int(11) NOT NULL,
  job_id int(11) NOT NULL,
  file_id int(11) NOT NULL,
  corrections longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(corrections)),
  created_at datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_jobs (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  total_files int(11) DEFAULT 0,
  processed_files int(11) DEFAULT 0,
  progress float DEFAULT 0,
  status varchar(50) DEFAULT 'queued',
  options text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_job_files (
  id int(11) NOT NULL,
  job_id int(11) NOT NULL,
  file_path varchar(512) NOT NULL,
  status varchar(50) DEFAULT 'pending',
  result_path varchar(512) DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_job_logs (
  id int(11) NOT NULL,
  job_id int(11) NOT NULL,
  level varchar(20) DEFAULT NULL,
  message text DEFAULT NULL,
  context text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_job_metadata (
  id int(11) NOT NULL,
  job_id int(11) NOT NULL,
  key_name varchar(255) NOT NULL,
  value text DEFAULT NULL,
  meta_value text DEFAULT NULL,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE ocr_system_settings (
  name varchar(100) NOT NULL,
  value text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


ALTER TABLE ocr_dictionary
  ADD PRIMARY KEY (id);

ALTER TABLE ocr_documents
  ADD PRIMARY KEY (document_id),
  ADD KEY idx_case_no (case_no);

ALTER TABLE ocr_document_templates
  ADD PRIMARY KEY (id);

ALTER TABLE ocr_feedback
  ADD PRIMARY KEY (id),
  ADD KEY job_id (job_id),
  ADD KEY file_id (file_id);

ALTER TABLE ocr_jobs
  ADD PRIMARY KEY (id);

ALTER TABLE ocr_job_files
  ADD PRIMARY KEY (id),
  ADD KEY job_id (job_id);

ALTER TABLE ocr_job_logs
  ADD PRIMARY KEY (id),
  ADD KEY job_id (job_id);

ALTER TABLE ocr_job_metadata
  ADD PRIMARY KEY (id);

ALTER TABLE ocr_system_settings
  ADD PRIMARY KEY (name);


ALTER TABLE ocr_dictionary
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_documents
  MODIFY document_id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_document_templates
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_feedback
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_jobs
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_job_files
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_job_logs
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE ocr_job_metadata
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE ocr_documents
  ADD CONSTRAINT fk_ocr_case FOREIGN KEY (case_no) REFERENCES case_management (case_no) ON DELETE SET NULL;

ALTER TABLE ocr_feedback
  ADD CONSTRAINT ocr_feedback_ibfk_1 FOREIGN KEY (job_id) REFERENCES ocr_jobs (id) ON DELETE CASCADE,
  ADD CONSTRAINT ocr_feedback_ibfk_2 FOREIGN KEY (file_id) REFERENCES ocr_job_files (id) ON DELETE CASCADE;

ALTER TABLE ocr_job_files
  ADD CONSTRAINT ocr_job_files_ibfk_1 FOREIGN KEY (job_id) REFERENCES ocr_jobs (id) ON DELETE CASCADE;

ALTER TABLE ocr_job_logs
  ADD CONSTRAINT ocr_job_logs_ibfk_1 FOREIGN KEY (job_id) REFERENCES ocr_jobs (id) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
