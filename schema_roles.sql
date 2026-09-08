USE vocational_erp;

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
  PRIMARY KEY(user_id),
  UNIQUE KEY uq_module_username(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example role design:
-- admin: super_admin, admin, staff
-- finance: finance_manager, finance_officer, cashier
-- dean: dean, admissions_officer, welfare_officer, registrar
-- students: student
