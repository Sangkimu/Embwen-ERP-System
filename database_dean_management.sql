-- Dean module migration: boarding allocation registry
-- Run once against the vocational_erp database.

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
