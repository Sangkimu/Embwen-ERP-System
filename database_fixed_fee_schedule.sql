-- Fixed 2026 institutional fee schedule.
-- Safe to rerun. It does not update or delete existing payment records.
USE vocational_erp;

CREATE TEMPORARY TABLE fixed_fee_seed (
  fee_type VARCHAR(40) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL
);

INSERT INTO fixed_fee_seed VALUES
('boarding_lunch_boarder',1,6500),('boarding_lunch_boarder',2,6500),('boarding_lunch_boarder',3,5300),
('admin_cost',1,500),('admin_cost',2,300),('admin_cost',3,200),
('p_emolument',1,3000),('p_emolument',2,2500),('p_emolument',3,2000),
('medical_boarder',1,500),('medical_boarder',2,300),('medical_boarder',3,200),
('lt_t_boarder',1,500),('lt_t_boarder',2,500),('lt_t_boarder',3,300),
('tuition',1,500),('tuition',2,300),('tuition',3,200),
('e_w_c_boarder',1,500),('e_w_c_boarder',2,200),('e_w_c_boarder',3,200),
('boarding_lunch_dayscholar',1,4500),('boarding_lunch_dayscholar',2,4500),('boarding_lunch_dayscholar',3,3500),
('admin_cost',1,500),('admin_cost',2,300),('admin_cost',3,200),
('p_emolument',1,3000),('p_emolument',2,2500),('p_emolument',3,1800),
('medical_dayscholar',1,300),('medical_dayscholar',2,300),('medical_dayscholar',3,200),
('lt_t_dayscholar',1,400),('lt_t_dayscholar',2,400),('lt_t_dayscholar',3,400),
('tuition',1,500),('tuition',2,300),('tuition',3,200),
('e_w_c_dayscholar',1,300),('e_w_c_dayscholar',2,200),('e_w_c_dayscholar',3,200),
('computer_packages',1,3500),('admission_fee',1,500),('attachment_fee',2,1500);

INSERT INTO fee_structure (course_id,fee_type,amount,academic_year,semester)
SELECT c.course_id,s.fee_type,s.amount,'2026',s.semester
FROM courses c CROSS JOIN fixed_fee_seed s
WHERE c.status='active'
AND NOT EXISTS (SELECT 1 FROM fee_structure f WHERE f.course_id=c.course_id AND f.fee_type=s.fee_type AND f.academic_year='2026' AND f.semester=s.semester);

DROP TEMPORARY TABLE fixed_fee_seed;
