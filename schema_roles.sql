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
  admin_role_slot VARCHAR(50) GENERATED ALWAYS AS (IF(module='admin', role, NULL)) STORED,
  PRIMARY KEY(user_id),
  UNIQUE KEY uq_module_username(username),
  UNIQUE KEY uq_admin_role_slot(admin_role_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If module_users already exists, run this migration once:
-- ALTER TABLE module_users ADD admin_role_slot VARCHAR(50) GENERATED ALWAYS AS (IF(module='admin', role, NULL)) STORED;
-- ALTER TABLE module_users ADD UNIQUE KEY uq_admin_role_slot(admin_role_slot);

-- Example role design:
-- admin: super_admin, admin, staff
-- finance: finance_manager, finance_officer, cashier
-- dean: dean, admissions_officer, welfare_officer, registrar
-- students: student
