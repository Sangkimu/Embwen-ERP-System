-- =====================================================================
-- Vocational College ERP - Master Import
-- Import this single file into phpMyAdmin.
-- Safe to rerun: it does not drop or delete existing data.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS vocational_erp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vocational_erp;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admin_users (
  admin_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role ENUM('super_admin','admin','staff') NOT NULL DEFAULT 'staff',
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id), UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  department_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (department_id), UNIQUE KEY uq_department_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
  course_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_code VARCHAR(30) NOT NULL,
  course_name VARCHAR(150) NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  duration_months SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (course_id), UNIQUE KEY uq_course_code (course_code),
  CONSTRAINT fk_courses_department FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notices (
  notice_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  target_audience ENUM('all','students','staff') NOT NULL DEFAULT 'all',
  posted_by INT UNSIGNED NOT NULL,
  posted_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notice_id),
  CONSTRAINT fk_notices_admin FOREIGN KEY (posted_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_no VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  course_id INT UNSIGNED NULL,
  status ENUM('active','graduated','withdrawn') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id), UNIQUE KEY uq_student_idno (id_no),
  CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_accounts (
  account_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (account_id), UNIQUE KEY uq_student_account_username (username), UNIQUE KEY uq_student_account_student (student_id),
  CONSTRAINT fk_studentaccounts_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admissions (
  admission_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  applicant_name VARCHAR(150) NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  application_date DATE NOT NULL,
  admission_status ENUM('pending','admitted','rejected') NOT NULL DEFAULT 'pending',
  decided_by INT UNSIGNED NULL,
  decision_date DATE NULL,
  student_id INT UNSIGNED NULL,
  PRIMARY KEY (admission_id),
  CONSTRAINT fk_admissions_course FOREIGN KEY (course_id) REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_admissions_decided_by FOREIGN KEY (decided_by) REFERENCES admin_users (admin_id) ON DELETE SET NULL,
  CONSTRAINT fk_admissions_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class_allocations (
  allocation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  class_section VARCHAR(50) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  allocated_by INT UNSIGNED NOT NULL,
  allocated_on DATE NOT NULL,
  PRIMARY KEY (allocation_id),
  CONSTRAINT fk_allocations_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_allocations_course FOREIGN KEY (course_id) REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_allocations_admin FOREIGN KEY (allocated_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_welfare (
  welfare_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  category ENUM('counseling','disciplinary','health','financial_aid','other') NOT NULL,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  description TEXT NOT NULL,
  action_taken TEXT NULL,
  handled_by INT UNSIGNED NOT NULL,
  record_date DATE NOT NULL,
  follow_up_date DATE NULL,
  status ENUM('open','resolved') NOT NULL DEFAULT 'open',
  PRIMARY KEY (welfare_id),
  CONSTRAINT fk_welfare_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfare_admin FOREIGN KEY (handled_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS welfare_updates (
  update_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  welfare_id INT UNSIGNED NOT NULL,
  note TEXT NOT NULL,
  updated_by INT UNSIGNED NOT NULL,
  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (update_id),
  CONSTRAINT fk_welfareupdates_welfare FOREIGN KEY (welfare_id) REFERENCES student_welfare (welfare_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfareupdates_admin FOREIGN KEY (updated_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_structure (
  fee_structure_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED NOT NULL,
  fee_type ENUM('boarding_lunch_boarder','boarding_lunch_dayscholar','admin_cost','p_emolument','medical_boarder','medical_dayscholar','lt_t_boarder','lt_t_dayscholar','tuition','e_w_c_boarder','e_w_c_dayscholar','computer_packages','admission_fee','attachment_fee','exam','exam_knec','exam_nita','exam_kasneb','lab_practical','attachment','other') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (fee_structure_id),
  CONSTRAINT fk_feestructure_course FOREIGN KEY (course_id) REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_payments (
  payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  fee_structure_id INT UNSIGNED NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method ENUM('bank','online','mpesa','equity_bank') NOT NULL,
  receipt_no VARCHAR(50) NOT NULL,
  reference_no VARCHAR(100) NULL,
  received_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (payment_id), UNIQUE KEY uq_receipt_no (receipt_no),
  CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_feestructure FOREIGN KEY (fee_structure_id) REFERENCES fee_structure (fee_structure_id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_admin FOREIGN KEY (received_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  expense_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(100) NOT NULL,
  description TEXT NULL,
  amount DECIMAL(10,2) NOT NULL,
  expense_date DATE NOT NULL,
  approved_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (expense_id),
  CONSTRAINT fk_expenses_admin FOREIGN KEY (approved_by) REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  module ENUM('admin','finance','dean','students') NOT NULL,
  role VARCHAR(50) NOT NULL,
  email VARCHAR(150) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  admin_role_slot VARCHAR(50) GENERATED ALWAYS AS (IF(module='admin', role, NULL)) STORED,
  PRIMARY KEY(user_id), UNIQUE KEY uq_module_username(username), UNIQUE KEY uq_admin_role_slot(admin_role_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Profile completion migration.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='phone')=0,'ALTER TABLE students ADD COLUMN phone VARCHAR(30) NULL AFTER id_no','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='email')=0,'ALTER TABLE students ADD COLUMN email VARCHAR(150) NULL AFTER phone','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='gender')=0,'ALTER TABLE students ADD COLUMN gender VARCHAR(20) NULL AFTER email','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='course_id')='NO','ALTER TABLE students MODIFY COLUMN course_id INT UNSIGNED NULL','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Payment and M-Pesa migration.
ALTER TABLE fee_payments MODIFY payment_method ENUM('bank','online','mpesa','equity_bank') NOT NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fee_structure' AND COLUMN_NAME='semester')=0,'ALTER TABLE fee_structure ADD COLUMN semester TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER academic_year','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
ALTER TABLE fee_structure MODIFY fee_type ENUM('boarding_lunch_boarder','boarding_lunch_dayscholar','admin_cost','p_emolument','medical_boarder','medical_dayscholar','lt_t_boarder','lt_t_dayscholar','tuition','e_w_c_boarder','e_w_c_dayscholar','computer_packages','admission_fee','attachment_fee','exam','exam_knec','exam_nita','exam_kasneb','lab_practical','attachment','other') NOT NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fee_payments' AND COLUMN_NAME='reference_no')=0,'ALTER TABLE fee_payments ADD COLUMN reference_no VARCHAR(100) NULL AFTER receipt_no','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
CREATE TABLE IF NOT EXISTS mpesa_transactions (
  transaction_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  fee_structure_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  account_reference VARCHAR(50) NOT NULL,
  checkout_request_id VARCHAR(100) NOT NULL,
  merchant_request_id VARCHAR(100) NULL,
  mpesa_receipt_no VARCHAR(50) NULL,
  result_code INT NULL,
  result_description VARCHAR(255) NULL,
  status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  callback_payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (transaction_id), UNIQUE KEY uq_mpesa_checkout (checkout_request_id), UNIQUE KEY uq_mpesa_receipt (mpesa_receipt_no), KEY idx_mpesa_status (status),
  CONSTRAINT fk_mpesa_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE RESTRICT,
  CONSTRAINT fk_mpesa_fee FOREIGN KEY (fee_structure_id) REFERENCES fee_structure (fee_structure_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Published 2026 fee schedule. Existing matching rows are preserved.
CREATE TEMPORARY TABLE fixed_fee_seed (fee_type VARCHAR(40) NOT NULL, semester TINYINT UNSIGNED NOT NULL, amount DECIMAL(10,2) NOT NULL);
INSERT INTO fixed_fee_seed VALUES
('boarding_lunch_boarder',1,6500),('boarding_lunch_boarder',2,6500),('boarding_lunch_boarder',3,5300),('admin_cost',1,500),('admin_cost',2,300),('admin_cost',3,200),('p_emolument',1,3000),('p_emolument',2,2500),('p_emolument',3,2000),('medical_boarder',1,500),('medical_boarder',2,300),('medical_boarder',3,200),('lt_t_boarder',1,500),('lt_t_boarder',2,500),('lt_t_boarder',3,300),('tuition',1,500),('tuition',2,300),('tuition',3,200),('e_w_c_boarder',1,500),('e_w_c_boarder',2,200),('e_w_c_boarder',3,200),
('boarding_lunch_dayscholar',1,4500),('boarding_lunch_dayscholar',2,4500),('boarding_lunch_dayscholar',3,3500),('admin_cost',1,500),('admin_cost',2,300),('admin_cost',3,200),('p_emolument',1,3000),('p_emolument',2,2500),('p_emolument',3,1800),('medical_dayscholar',1,300),('medical_dayscholar',2,300),('medical_dayscholar',3,200),('lt_t_dayscholar',1,400),('lt_t_dayscholar',2,400),('lt_t_dayscholar',3,400),('tuition',1,500),('tuition',2,300),('tuition',3,200),('e_w_c_dayscholar',1,300),('e_w_c_dayscholar',2,200),('e_w_c_dayscholar',3,200),('computer_packages',1,3500),('admission_fee',1,500),('attachment_fee',2,1500);
INSERT INTO fee_structure (course_id,fee_type,amount,academic_year,semester)
SELECT c.course_id,s.fee_type,s.amount,'2026',s.semester FROM courses c CROSS JOIN fixed_fee_seed s
WHERE c.status='active' AND NOT EXISTS (SELECT 1 FROM fee_structure f WHERE f.course_id=c.course_id AND f.fee_type=s.fee_type AND f.academic_year='2026' AND f.semester=s.semester);
DROP TEMPORARY TABLE fixed_fee_seed;

SET FOREIGN_KEY_CHECKS = 1;
