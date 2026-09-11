-- Run once against the vocational_erp database before enabling M-Pesa callbacks.
ALTER TABLE fee_payments
  MODIFY payment_method ENUM('cash','bank','online','mpesa','equity_bank') NOT NULL;

SET @reference_column_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fee_payments'
    AND COLUMN_NAME = 'reference_no'
);
SET @add_reference_column = IF(
  @reference_column_exists = 0,
  'ALTER TABLE fee_payments ADD COLUMN reference_no VARCHAR(100) NULL AFTER receipt_no',
  'SELECT 1'
);
PREPARE add_reference_column FROM @add_reference_column;
EXECUTE add_reference_column;
DEALLOCATE PREPARE add_reference_column;

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
  CONSTRAINT fk_mpesa_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE RESTRICT,
  CONSTRAINT fk_mpesa_fee FOREIGN KEY (fee_structure_id) REFERENCES fee_structure (fee_structure_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
