-- Fixed 2026 institutional fee schedule.
-- Safe to rerun. It does not update or delete existing payment records.
USE vocational_erp;

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

INSERT INTO fee_structure (course_id,fee_type,amount,academic_year,semester,residency_scope)
SELECT c.course_id,s.fee_type,s.amount,'2026',s.semester,s.residency_scope
FROM courses c CROSS JOIN fixed_fee_seed s
WHERE c.status='active'
AND NOT EXISTS (SELECT 1 FROM fee_structure f WHERE f.course_id=c.course_id AND f.fee_type=s.fee_type AND f.academic_year='2026' AND f.semester=s.semester AND f.residency_scope=s.residency_scope);

DROP TEMPORARY TABLE fixed_fee_seed;
