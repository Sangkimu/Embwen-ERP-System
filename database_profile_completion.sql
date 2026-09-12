USE vocational_erp;

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
  AND COLUMN_NAME IN ('id_no', 'phone', 'email', 'gender')
ORDER BY ORDINAL_POSITION;
