-- WARNING: This permanently deletes ALL rows from every table in vocational_erp.
-- It preserves the database schema, tables, columns, indexes, and migrations.
-- Run only when you want a completely empty ERP database.

USE vocational_erp;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE mpesa_transactions;
TRUNCATE TABLE fee_payments;
TRUNCATE TABLE fee_structure;
TRUNCATE TABLE welfare_updates;
TRUNCATE TABLE student_welfare;
TRUNCATE TABLE class_allocations;
TRUNCATE TABLE hostel_allocations;
TRUNCATE TABLE admissions;
TRUNCATE TABLE student_accounts;
TRUNCATE TABLE students;
TRUNCATE TABLE expenses;
TRUNCATE TABLE notices;
TRUNCATE TABLE module_users;
TRUNCATE TABLE admin_users;
TRUNCATE TABLE courses;
TRUNCATE TABLE departments;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Database reset completed. All listed ERP tables are empty.' AS result;
