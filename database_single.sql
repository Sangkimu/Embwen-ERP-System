-- =====================================================================
-- Vocational College ERP - Single Database Import
-- Import this file into phpMyAdmin or MySQL as the only database setup file.
-- Safe to run more than once: it creates tables only if missing and uses
-- duplicate-safe inserts for reference data.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS vocational_erp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vocational_erp;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1) Core user and organizational tables
-- ---------------------------------------------------------------------

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
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  department_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (department_id),
  UNIQUE KEY uq_department_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  id_type ENUM('national_id','maisha_card') NULL,
  id_number VARCHAR(50) NULL,
  phone VARCHAR(30) NULL,
  staff_number VARCHAR(50) NULL,
  module ENUM('admin','finance','dean','students') NOT NULL,
  role VARCHAR(50) NOT NULL,
  email VARCHAR(150) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_module_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2) Academic tables
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS courses (
  course_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_code VARCHAR(30) NOT NULL,
  course_name VARCHAR(150) NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  duration_months SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  entry_requirement VARCHAR(150) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (course_id),
  UNIQUE KEY uq_course_code (course_code),
  CONSTRAINT fk_courses_department FOREIGN KEY (department_id)
    REFERENCES departments (department_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notices (
  notice_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  target_audience ENUM('all','students','staff') NOT NULL DEFAULT 'all',
  posted_by INT UNSIGNED NOT NULL,
  posted_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notice_id),
  CONSTRAINT fk_notices_admin FOREIGN KEY (posted_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_no VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  identity_type ENUM('national_id','maisha_card') NULL,
  national_id VARCHAR(8) NULL,
  birth_certificate_no VARCHAR(30) NULL,
  phone VARCHAR(30) NULL,
  parent_name VARCHAR(150) NULL,
  parent_phone VARCHAR(30) NULL,
  previous_academic_level VARCHAR(100) NULL,
  year_of_completion YEAR NULL,
  course_id INT UNSIGNED NULL,
  study_start_date DATE NULL,
  expected_end_date DATE NULL,
  residency ENUM('boarder','dayscholar') NULL,
  status ENUM('active','graduated','withdrawn') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_student_idno (id_no),
  CONSTRAINT fk_students_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_accounts (
  account_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (account_id),
  UNIQUE KEY uq_student_account_username (username),
  UNIQUE KEY uq_student_account_student (student_id),
  CONSTRAINT fk_studentaccounts_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admissions (
  admission_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  applicant_name VARCHAR(150) NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  residency ENUM('boarder','dayscholar') NULL,
  application_date DATE NOT NULL,
  admission_status ENUM('pending','admitted','rejected') NOT NULL DEFAULT 'pending',
  decided_by INT UNSIGNED NULL,
  decision_date DATE NULL,
  student_id INT UNSIGNED NULL,
  PRIMARY KEY (admission_id),
  CONSTRAINT fk_admissions_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_admissions_decided_by FOREIGN KEY (decided_by)
    REFERENCES admin_users (admin_id) ON DELETE SET NULL,
  CONSTRAINT fk_admissions_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE SET NULL
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
  CONSTRAINT fk_allocations_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_allocations_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_allocations_admin FOREIGN KEY (allocated_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hostel_allocations (
  allocation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  hostel_name VARCHAR(100) NOT NULL,
  room_no VARCHAR(50) NOT NULL,
  status ENUM('allocated','released') NOT NULL DEFAULT 'allocated',
  allocated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  released_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (allocation_id),
  KEY idx_hostel_status_room (hostel_name, room_no, status),
  KEY idx_hostel_student_status (student_id, status),
  CONSTRAINT fk_hostel_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE
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
  CONSTRAINT fk_welfare_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfare_admin FOREIGN KEY (handled_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS welfare_updates (
  update_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  welfare_id INT UNSIGNED NOT NULL,
  note TEXT NOT NULL,
  updated_by INT UNSIGNED NOT NULL,
  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (update_id),
  CONSTRAINT fk_welfareupdates_welfare FOREIGN KEY (welfare_id)
    REFERENCES student_welfare (welfare_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfareupdates_admin FOREIGN KEY (updated_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3) Finance and fee tables
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fee_structure (
  fee_structure_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED NOT NULL,
  fee_type ENUM('boarding_lunch','admin_cost','p_emolument','medical','lt_t','tuition','e_w_c','computer_packages','admission_fee','attachment_fee','exam','exam_knec','exam_nita','exam_kasneb','lab_practical','attachment','other','boarding_lunch_boarder','boarding_lunch_dayscholar','medical_boarder','medical_dayscholar','lt_t_boarder','lt_t_dayscholar','e_w_c_boarder','e_w_c_dayscholar') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL DEFAULT 1,
  residency_scope ENUM('universal','boarder','dayscholar') NOT NULL DEFAULT 'universal',
  PRIMARY KEY (fee_structure_id),
  CONSTRAINT fk_feestructure_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_payments (
  payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  fee_structure_id INT UNSIGNED NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method ENUM('cash','bank','online','mpesa','equity_bank') NOT NULL,
  receipt_no VARCHAR(50) NOT NULL,
  reference_no VARCHAR(100) NULL,
  received_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (payment_id),
  UNIQUE KEY uq_receipt_no (receipt_no),
  CONSTRAINT fk_payments_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_feestructure FOREIGN KEY (fee_structure_id)
    REFERENCES fee_structure (fee_structure_id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_admin FOREIGN KEY (received_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  expense_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(100) NOT NULL,
  description TEXT NULL,
  amount DECIMAL(10,2) NOT NULL,
  expense_date DATE NOT NULL,
  approved_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (expense_id),
  CONSTRAINT fk_expenses_admin FOREIGN KEY (approved_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  PRIMARY KEY (transaction_id),
  UNIQUE KEY uq_mpesa_checkout (checkout_request_id),
  UNIQUE KEY uq_mpesa_receipt (mpesa_receipt_no),
  KEY idx_mpesa_status (status),
  CONSTRAINT fk_mpesa_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE RESTRICT,
  CONSTRAINT fk_mpesa_fee FOREIGN KEY (fee_structure_id)
    REFERENCES fee_structure (fee_structure_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4) Add any missing schema columns that some older migration files added
-- ---------------------------------------------------------------------

SET @add_course_entry_requirement = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'entry_requirement') = 0,
  'ALTER TABLE courses ADD COLUMN entry_requirement VARCHAR(150) NULL AFTER duration_months',
  'SELECT 1'
);
PREPARE stmt_course_entry_requirement FROM @add_course_entry_requirement;
EXECUTE stmt_course_entry_requirement;
DEALLOCATE PREPARE stmt_course_entry_requirement;

SET @add_admission_residency = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admissions' AND COLUMN_NAME = 'residency') = 0,
  "ALTER TABLE admissions ADD COLUMN residency ENUM('boarder','dayscholar') NULL AFTER course_id",
  'SELECT 1'
);
PREPARE stmt_admission_residency FROM @add_admission_residency;
EXECUTE stmt_admission_residency;
DEALLOCATE PREPARE stmt_admission_residency;

SET @add_module_id_type = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'id_type') = 0,
  "ALTER TABLE module_users ADD COLUMN id_type ENUM('national_id','maisha_card') NULL AFTER full_name",
  'SELECT 1'
);
PREPARE stmt_module_id_type FROM @add_module_id_type;
EXECUTE stmt_module_id_type;
DEALLOCATE PREPARE stmt_module_id_type;

SET @add_module_id_number = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'id_number') = 0,
  'ALTER TABLE module_users ADD COLUMN id_number VARCHAR(50) NULL AFTER id_type',
  'SELECT 1'
);
PREPARE stmt_module_id_number FROM @add_module_id_number;
EXECUTE stmt_module_id_number;
DEALLOCATE PREPARE stmt_module_id_number;

SET @add_module_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'phone') = 0,
  'ALTER TABLE module_users ADD COLUMN phone VARCHAR(30) NULL AFTER id_number',
  'SELECT 1'
);
PREPARE stmt_module_phone FROM @add_module_phone;
EXECUTE stmt_module_phone;
DEALLOCATE PREPARE stmt_module_phone;

SET @add_module_staff_number = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'staff_number') = 0,
  'ALTER TABLE module_users ADD COLUMN staff_number VARCHAR(50) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_module_staff_number FROM @add_module_staff_number;
EXECUTE stmt_module_staff_number;
DEALLOCATE PREPARE stmt_module_staff_number;

SET @add_student_identity_type = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'identity_type') = 0,
  "ALTER TABLE students ADD COLUMN identity_type ENUM('national_id','maisha_card') NULL AFTER name",
  'SELECT 1'
);
PREPARE stmt_student_identity_type FROM @add_student_identity_type;
EXECUTE stmt_student_identity_type;
DEALLOCATE PREPARE stmt_student_identity_type;

SET @add_student_national_id = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'national_id') = 0,
  'ALTER TABLE students ADD COLUMN national_id VARCHAR(8) NULL AFTER identity_type',
  'SELECT 1'
);
PREPARE stmt_student_national_id FROM @add_student_national_id;
EXECUTE stmt_student_national_id;
DEALLOCATE PREPARE stmt_student_national_id;

SET @add_student_birth_certificate = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'birth_certificate_no') = 0,
  'ALTER TABLE students ADD COLUMN birth_certificate_no VARCHAR(30) NULL AFTER national_id',
  'SELECT 1'
);
PREPARE stmt_student_birth_certificate FROM @add_student_birth_certificate;
EXECUTE stmt_student_birth_certificate;
DEALLOCATE PREPARE stmt_student_birth_certificate;

SET @add_student_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'phone') = 0,
  'ALTER TABLE students ADD COLUMN phone VARCHAR(30) NULL AFTER id_no',
  'SELECT 1'
);
PREPARE stmt_student_phone FROM @add_student_phone;
EXECUTE stmt_student_phone;
DEALLOCATE PREPARE stmt_student_phone;

SET @add_parent_name = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'parent_name') = 0,
  'ALTER TABLE students ADD COLUMN parent_name VARCHAR(150) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_parent_name FROM @add_parent_name;
EXECUTE stmt_parent_name;
DEALLOCATE PREPARE stmt_parent_name;

SET @add_parent_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'parent_phone') = 0,
  'ALTER TABLE students ADD COLUMN parent_phone VARCHAR(30) NULL AFTER parent_name',
  'SELECT 1'
);
PREPARE stmt_parent_phone FROM @add_parent_phone;
EXECUTE stmt_parent_phone;
DEALLOCATE PREPARE stmt_parent_phone;

SET @add_previous_academic_level = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'previous_academic_level') = 0,
  'ALTER TABLE students ADD COLUMN previous_academic_level VARCHAR(100) NULL AFTER parent_phone',
  'SELECT 1'
);
PREPARE stmt_previous_academic_level FROM @add_previous_academic_level;
EXECUTE stmt_previous_academic_level;
DEALLOCATE PREPARE stmt_previous_academic_level;

SET @add_year_of_completion = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'year_of_completion') = 0,
  'ALTER TABLE students ADD COLUMN year_of_completion YEAR NULL AFTER previous_academic_level',
  'SELECT 1'
);
PREPARE stmt_year_of_completion FROM @add_year_of_completion;
EXECUTE stmt_year_of_completion;
DEALLOCATE PREPARE stmt_year_of_completion;

SET @add_study_start_date = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'study_start_date') = 0,
  'ALTER TABLE students ADD COLUMN study_start_date DATE NULL AFTER course_id',
  'SELECT 1'
);
PREPARE stmt_study_start_date FROM @add_study_start_date;
EXECUTE stmt_study_start_date;
DEALLOCATE PREPARE stmt_study_start_date;

SET @add_expected_end_date = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'expected_end_date') = 0,
  'ALTER TABLE students ADD COLUMN expected_end_date DATE NULL AFTER study_start_date',
  'SELECT 1'
);
PREPARE stmt_expected_end_date FROM @add_expected_end_date;
EXECUTE stmt_expected_end_date;
DEALLOCATE PREPARE stmt_expected_end_date;

SET @add_student_email = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'email') = 0,
  'ALTER TABLE students ADD COLUMN email VARCHAR(150) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_student_email FROM @add_student_email;
EXECUTE stmt_student_email;
DEALLOCATE PREPARE stmt_student_email;

SET @add_student_gender = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'gender') = 0,
  'ALTER TABLE students ADD COLUMN gender VARCHAR(20) NULL AFTER email',
  'SELECT 1'
);
PREPARE stmt_student_gender FROM @add_student_gender;
EXECUTE stmt_student_gender;
DEALLOCATE PREPARE stmt_student_gender;

SET @allow_unassigned_course = IF(
  (SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'course_id') = 'NO',
  'ALTER TABLE students MODIFY COLUMN course_id INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt_course FROM @allow_unassigned_course;
EXECUTE stmt_course;
DEALLOCATE PREPARE stmt_course;

-- ---------------------------------------------------------------------
-- 5) Seed data
-- ---------------------------------------------------------------------

INSERT INTO departments (department_name) VALUES
('Hospitality & Institutional Management'),
('Building Department'),
('Mechanical Engineering'),
('ICT Department'),
('Electrical Department')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

INSERT INTO courses (course_code, course_name, department_id, duration_months) VALUES
('HIM-TAI', 'Tailoring', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-DRE', 'Dressmaking', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-KNI', 'Knitting', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-CUR', 'Curtain Making', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-TIE', 'Tie & Dye Decoration', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-CUS', 'Cushion Making', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-BEA', 'Beauty Therapy', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-HAI', 'Hairdressing', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-NAI', 'Nail Technology', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-MUA', 'Make-Up Application', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-FBP', 'Food & Beverage Production', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-FBS', 'Food & Beverage Service', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-BAP', 'Baking & Pastry', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-HOU', 'Housekeeping', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-FPC', 'Food Production & Cookery', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-CMD', 'Cake Making & Decoration', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('BLD-CAR', 'Carpentry & Joinery', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-MAS', 'Masonry', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-PLU', 'Plumbing & Pipe Fittings', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-PNT', 'Painting & Decoration', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('BLD-TIL', 'Tiling', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('BLD-WAH', 'Water Harvesting', (SELECT department_id FROM departments WHERE department_name='Building Department'), 1),
('BLD-URO', 'Upholstery & Roofing', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('MEC-WEL', 'Welding & Fabrication (Grade III)', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-LVM', 'Light Vehicle Mechanic (MVM)', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-MVE', 'Motor Vehicle Electrician', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-PLM', 'Plant Mechanics', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('ICT-COP', 'Computer Operator', (SELECT department_id FROM departments WHERE department_name='ICT Department'), 12),
('ICT-CPK', 'Computer Packages', (SELECT department_id FROM departments WHERE department_name='ICT Department'), 3),
('ELE-EWI', 'Electrical Wireman', (SELECT department_id FROM departments WHERE department_name='Electrical Department'), 12),
('ELE-BEW', 'Basic Electrical Wiring & Safety', (SELECT department_id FROM departments WHERE department_name='Electrical Department'), 3)
ON DUPLICATE KEY UPDATE
  course_name = VALUES(course_name),
  department_id = VALUES(department_id),
  duration_months = VALUES(duration_months),
  status = 'active';

UPDATE courses SET course_name='Upholstering & Roofing' WHERE course_code='BLD-URO';
UPDATE courses SET entry_requirement='KCPE/KPSEA/KSSEA/KSCE'
WHERE course_code IN ('BLD-CAR','BLD-MAS','BLD-PLU','BLD-PNT','BLD-TIL','BLD-WAH','BLD-URO','MEC-WEL','MEC-LVM','MEC-MVE','MEC-PLM','ICT-COP','ICT-CPK','ELE-EWI','ELE-BEW');

CREATE TEMPORARY TABLE fixed_fee_seed (
  fee_type VARCHAR(40) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  residency_scope ENUM('universal','boarder','dayscholar') NOT NULL
);

INSERT INTO fixed_fee_seed VALUES
('boarding_lunch',1,6500,'boarder'),('boarding_lunch',2,6500,'boarder'),('boarding_lunch',3,5300,'boarder'),('admin_cost',1,500,'boarder'),('admin_cost',2,300,'boarder'),('admin_cost',3,200,'boarder'),('p_emolument',1,3000,'boarder'),('p_emolument',2,2500,'boarder'),('p_emolument',3,2000,'boarder'),('medical',1,500,'boarder'),('medical',2,300,'boarder'),('medical',3,200,'boarder'),('lt_t',1,500,'boarder'),('lt_t',2,500,'boarder'),('lt_t',3,300,'boarder'),('tuition',1,500,'boarder'),('tuition',2,300,'boarder'),('tuition',3,200,'boarder'),('e_w_c',1,500,'boarder'),('e_w_c',2,200,'boarder'),('e_w_c',3,200,'boarder'),
('boarding_lunch',1,4500,'dayscholar'),('boarding_lunch',2,4500,'dayscholar'),('boarding_lunch',3,3500,'dayscholar'),('admin_cost',1,500,'dayscholar'),('admin_cost',2,300,'dayscholar'),('admin_cost',3,200,'dayscholar'),('p_emolument',1,3000,'dayscholar'),('p_emolument',2,2500,'dayscholar'),('p_emolument',3,1800,'dayscholar'),('medical',1,300,'dayscholar'),('medical',2,300,'dayscholar'),('medical',3,200,'dayscholar'),('lt_t',1,400,'dayscholar'),('lt_t',2,400,'dayscholar'),('lt_t',3,400,'dayscholar'),('tuition',1,500,'dayscholar'),('tuition',2,300,'dayscholar'),('tuition',3,200,'dayscholar'),('e_w_c',1,300,'dayscholar'),('e_w_c',2,200,'dayscholar'),('e_w_c',3,200,'dayscholar'),
('computer_packages',1,3500,'universal'),('admission_fee',1,500,'universal'),('attachment_fee',2,1500,'universal');

INSERT INTO fee_structure (course_id, fee_type, amount, academic_year, semester, residency_scope)
SELECT c.course_id, s.fee_type, s.amount, '2026', s.semester, s.residency_scope
FROM courses c
CROSS JOIN fixed_fee_seed s
WHERE c.status = 'active'
AND NOT EXISTS (
  SELECT 1
  FROM fee_structure f
  WHERE f.course_id = c.course_id
    AND f.fee_type = s.fee_type
    AND f.academic_year = '2026'
    AND f.semester = s.semester
    AND f.residency_scope = s.residency_scope
);

DROP TEMPORARY TABLE fixed_fee_seed;

-- ---------------------------------------------------------------------
-- 6) Final sanity check
-- ---------------------------------------------------------------------

SELECT 'Database setup complete.' AS result;
