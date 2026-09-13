USE vocational_erp;

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

SET @add_student_identity_type = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'identity_type') = 0,
  "ALTER TABLE students ADD COLUMN identity_type ENUM('national_id','maisha_card') NULL AFTER name",
  'SELECT 1'
);
PREPARE stmt_student_identity_type FROM @add_student_identity_type;
EXECUTE stmt_student_identity_type;
DEALLOCATE PREPARE stmt_student_identity_type;

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

-- Add identity fields for Admin, Finance, and Dean module accounts.
SET @add_module_id_number = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'id_number') = 0,
  'ALTER TABLE module_users ADD COLUMN id_number VARCHAR(50) NULL AFTER full_name',
  'SELECT 1'
);
PREPARE stmt_module_id_number FROM @add_module_id_number;
EXECUTE stmt_module_id_number;
DEALLOCATE PREPARE stmt_module_id_number;

SET @add_module_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'phone') = 0,
  'ALTER TABLE module_users ADD COLUMN phone VARCHAR(30) NULL AFTER id_number',
  'SELECT 1'
);
PREPARE stmt_module_phone FROM @add_module_phone;
EXECUTE stmt_module_phone;
DEALLOCATE PREPARE stmt_module_phone;

SET @add_module_staff_number = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_users' AND COLUMN_NAME = 'staff_number') = 0,
  'ALTER TABLE module_users ADD COLUMN staff_number VARCHAR(50) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_module_staff_number FROM @add_module_staff_number;
EXECUTE stmt_module_staff_number;
DEALLOCATE PREPARE stmt_module_staff_number;

-- Add the fields required by student/complete_profile.php.
SET @add_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'phone') = 0,
  'ALTER TABLE students ADD COLUMN phone VARCHAR(30) NULL AFTER id_no',
  'SELECT 1'
);
PREPARE stmt_phone FROM @add_phone;
EXECUTE stmt_phone;
DEALLOCATE PREPARE stmt_phone;

SET @add_parent_name = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'parent_name') = 0,
  'ALTER TABLE students ADD COLUMN parent_name VARCHAR(150) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_parent_name FROM @add_parent_name;
EXECUTE stmt_parent_name;
DEALLOCATE PREPARE stmt_parent_name;

SET @add_parent_phone = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'parent_phone') = 0,
  'ALTER TABLE students ADD COLUMN parent_phone VARCHAR(30) NULL AFTER parent_name',
  'SELECT 1'
);
PREPARE stmt_parent_phone FROM @add_parent_phone;
EXECUTE stmt_parent_phone;
DEALLOCATE PREPARE stmt_parent_phone;

SET @add_previous_academic_level = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'previous_academic_level') = 0,
  'ALTER TABLE students ADD COLUMN previous_academic_level VARCHAR(100) NULL AFTER parent_phone',
  'SELECT 1'
);
PREPARE stmt_previous_academic_level FROM @add_previous_academic_level;
EXECUTE stmt_previous_academic_level;
DEALLOCATE PREPARE stmt_previous_academic_level;

SET @add_year_of_completion = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'year_of_completion') = 0,
  'ALTER TABLE students ADD COLUMN year_of_completion YEAR NULL AFTER previous_academic_level',
  'SELECT 1'
);
PREPARE stmt_year_of_completion FROM @add_year_of_completion;
EXECUTE stmt_year_of_completion;
DEALLOCATE PREPARE stmt_year_of_completion;

SET @add_email = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'email') = 0,
  'ALTER TABLE students ADD COLUMN email VARCHAR(150) NULL AFTER phone',
  'SELECT 1'
);
PREPARE stmt_email FROM @add_email;
EXECUTE stmt_email;
DEALLOCATE PREPARE stmt_email;

SET @add_gender = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'gender') = 0,
  'ALTER TABLE students ADD COLUMN gender VARCHAR(20) NULL AFTER email',
  'SELECT 1'
);
PREPARE stmt_gender FROM @add_gender;
EXECUTE stmt_gender;
DEALLOCATE PREPARE stmt_gender;

-- New student accounts start without a course and can be assigned later by
-- an administrator after the student completes the profile.
SET @allow_unassigned_course = IF(
  (SELECT IS_NULLABLE FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'course_id') = 'NO',
  'ALTER TABLE students MODIFY COLUMN course_id INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt_course FROM @allow_unassigned_course;
EXECUTE stmt_course;
DEALLOCATE PREPARE stmt_course;

-- Confirm the resulting profile columns.
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'students'
  AND COLUMN_NAME IN ('id_no', 'national_id', 'birth_certificate_no', 'phone', 'parent_name', 'parent_phone', 'previous_academic_level', 'year_of_completion', 'email', 'gender')
ORDER BY ORDINAL_POSITION;
