-- =====================================================================
-- Vocational College ERP — Clean Schema
-- Modules: Admin | Finance | Deen (Dean's Office) | Students
-- Sized for a ~200-student institution
-- Engine: InnoDB (foreign keys + transactions) | Charset: utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS vocational_erp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vocational_erp;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- ADMIN MODULE
-- Staff/admin accounts, departments, course catalog, notices
-- =====================================================================

CREATE TABLE IF NOT EXISTS admin_users (
  admin_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,          -- store bcrypt/argon2 hash, never plaintext
  full_name     VARCHAR(150) NOT NULL,
  role          ENUM('super_admin','admin','staff') NOT NULL DEFAULT 'staff',
  email         VARCHAR(150) NULL,
  phone         VARCHAR(30)  NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  department_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(150) NOT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (department_id),
  UNIQUE KEY uq_department_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
  course_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_code     VARCHAR(30)  NOT NULL,
  course_name     VARCHAR(150) NOT NULL,
  department_id   INT UNSIGNED NOT NULL,
  duration_months SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  entry_requirement VARCHAR(150) NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (course_id),
  UNIQUE KEY uq_course_code (course_code),
  CONSTRAINT fk_courses_department FOREIGN KEY (department_id)
    REFERENCES departments (department_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notices (
  notice_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title             VARCHAR(200) NOT NULL,
  content           TEXT NOT NULL,
  target_audience   ENUM('all','students','staff') NOT NULL DEFAULT 'all',
  posted_by         INT UNSIGNED NOT NULL,
  posted_on         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notice_id),
  CONSTRAINT fk_notices_admin FOREIGN KEY (posted_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- STUDENTS MODULE
-- Deliberately minimal — ID number, name, course. Nothing else here.
-- =====================================================================

CREATE TABLE IF NOT EXISTS students (
  student_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_no       VARCHAR(30)  NOT NULL,          -- college-issued student ID number
  name        VARCHAR(150) NOT NULL,
  national_id VARCHAR(8) NULL,
  birth_certificate_no VARCHAR(30) NULL,
  phone       VARCHAR(30) NULL,
  course_id   INT UNSIGNED NULL,
  status      ENUM('active','graduated','withdrawn') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_student_idno (id_no),
  CONSTRAINT fk_students_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_accounts (
  account_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  username      VARCHAR(100) NOT NULL,        -- typically same as id_no
  password_hash VARCHAR(255) NOT NULL,
  status        ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login    TIMESTAMP NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (account_id),
  UNIQUE KEY uq_student_account_username (username),
  UNIQUE KEY uq_student_account_student (student_id),  -- one login per student
  CONSTRAINT fk_studentaccounts_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- DEEN MODULE (Dean's Office)
-- Admissions workflow, class/section allocation, student welfare
-- =====================================================================

CREATE TABLE IF NOT EXISTS admissions (
  admission_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  applicant_name    VARCHAR(150) NOT NULL,
  course_id         INT UNSIGNED NOT NULL,
  application_date  DATE NOT NULL,
  admission_status  ENUM('pending','admitted','rejected') NOT NULL DEFAULT 'pending',
  decided_by        INT UNSIGNED NULL,
  decision_date     DATE NULL,
  student_id        INT UNSIGNED NULL,        -- filled in once admitted & enrolled
  PRIMARY KEY (admission_id),
  CONSTRAINT fk_admissions_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_admissions_decided_by FOREIGN KEY (decided_by)
    REFERENCES admin_users (admin_id) ON DELETE SET NULL,
  CONSTRAINT fk_admissions_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class_allocations (
  allocation_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id     INT UNSIGNED NOT NULL,
  course_id      INT UNSIGNED NOT NULL,
  class_section  VARCHAR(50)  NOT NULL,        -- e.g. "Year 1 - A"
  academic_year  VARCHAR(20)  NOT NULL,        -- e.g. "2026/2027"
  allocated_by   INT UNSIGNED NOT NULL,
  allocated_on   DATE NOT NULL,
  PRIMARY KEY (allocation_id),
  CONSTRAINT fk_allocations_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_allocations_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT,
  CONSTRAINT fk_allocations_admin FOREIGN KEY (allocated_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_welfare (
  welfare_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id     INT UNSIGNED NOT NULL,
  category       ENUM('counseling','disciplinary','health','financial_aid','other') NOT NULL,
  priority       ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  description    TEXT NOT NULL,
  action_taken   TEXT NULL,
  handled_by     INT UNSIGNED NOT NULL,
  record_date    DATE NOT NULL,
  follow_up_date DATE NULL,
  status         ENUM('open','resolved') NOT NULL DEFAULT 'open',
  PRIMARY KEY (welfare_id),
  CONSTRAINT fk_welfare_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfare_admin FOREIGN KEY (handled_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS welfare_updates (
  update_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  welfare_id     INT UNSIGNED NOT NULL,
  note           TEXT NOT NULL,
  updated_by     INT UNSIGNED NOT NULL,
  updated_on     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (update_id),
  CONSTRAINT fk_welfareupdates_welfare FOREIGN KEY (welfare_id)
    REFERENCES student_welfare (welfare_id) ON DELETE CASCADE,
  CONSTRAINT fk_welfareupdates_admin FOREIGN KEY (updated_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- FINANCE MODULE
-- Fee structure per course, payments received, general expenses
-- =====================================================================

CREATE TABLE IF NOT EXISTS fee_structure (
  fee_structure_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id         INT UNSIGNED NOT NULL,
  fee_type          ENUM('tuition','exam','other') NOT NULL,
  amount            DECIMAL(10,2) NOT NULL,
  academic_year     VARCHAR(20) NOT NULL,
  PRIMARY KEY (fee_structure_id),
  CONSTRAINT fk_feestructure_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_payments (
  payment_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id        INT UNSIGNED NOT NULL,
  fee_structure_id  INT UNSIGNED NOT NULL,
  amount_paid       DECIMAL(10,2) NOT NULL,
  payment_date      DATE NOT NULL,
  payment_method    ENUM('cash','bank','online') NOT NULL,
  receipt_no        VARCHAR(50) NOT NULL,
  received_by       INT UNSIGNED NOT NULL,
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
  expense_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category      VARCHAR(100) NOT NULL,
  description   TEXT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  expense_date  DATE NOT NULL,
  approved_by   INT UNSIGNED NOT NULL,
  PRIMARY KEY (expense_id),
  CONSTRAINT fk_expenses_admin FOREIGN KEY (approved_by)
    REFERENCES admin_users (admin_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

USE vocational_erp;

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
  PRIMARY KEY(user_id),
  UNIQUE KEY uq_module_username(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If module_users already exists, run this migration once to remove the old admin-role cap:
-- ALTER TABLE module_users DROP COLUMN admin_role_slot;
-- ALTER TABLE module_users DROP INDEX uq_admin_role_slot;

-- Example role design:
-- admin: super_admin, admin, staff
-- finance: finance_manager, finance_officer, cashier
-- dean: dean, admissions_officer, welfare_officer, registrar
-- students: student

