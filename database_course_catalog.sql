-- Central course catalog for student profiles, Dean, and Finance.
-- Safe to rerun: existing departments and course codes are preserved.
USE vocational_erp;

INSERT INTO departments (department_name) VALUES
('Hospitality & Institutional Management'),
('Building Department'),
('Mechanical Engineering'),
('ICT Department'),
('Electrical Department')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

INSERT INTO courses (course_code, course_name, department_id, duration_months) VALUES
('HIM-TAI', 'Tailoring', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-DRE', 'Dressmaking', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-KNI', 'Knitting', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-CUR', 'Curtain Making', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-TIE', 'Tie & Dye Decoration', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-CUS', 'Cushion Making', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-BEA', 'Beauty Therapy', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-HAI', 'Hairdressing', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-NAI', 'Nail Technology', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-MUA', 'Make-Up Application', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('HIM-FBP', 'Food & Beverage Production', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-FBS', 'Food & Beverage Service', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 12),
('HIM-BAP', 'Baking & Pastry', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-HOU', 'Housekeeping', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-FPC', 'Food Production & Cookery', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 3),
('HIM-CMD', 'Cake Making & Decoration', (SELECT department_id FROM departments WHERE department_name='Hospitality & Institutional Management'), 1),
('BLD-CAR', 'Carpentry & Joinery', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-MAS', 'Masonry', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-PLU', 'Plumbing & Pipe Fittings', (SELECT department_id FROM departments WHERE department_name='Building Department'), 12),
('BLD-PNT', 'Painting & Decoration', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('BLD-TIL', 'Tiling', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('BLD-WAH', 'Water Harvesting', (SELECT department_id FROM departments WHERE department_name='Building Department'), 1),
('BLD-URO', 'Upholstery & Roofing', (SELECT department_id FROM departments WHERE department_name='Building Department'), 3),
('MEC-WEL', 'Welding & Fabrication (Grade III)', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-LVM', 'Light Vehicle Mechanic (MVM)', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-MVE', 'Motor Vehicle Electrician', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('MEC-PLM', 'Plant Mechanics', (SELECT department_id FROM departments WHERE department_name='Mechanical Engineering'), 12),
('ICT-COP', 'Computer Operator', (SELECT department_id FROM departments WHERE department_name='ICT Department'), 12),
('ICT-CPK', 'Computer Packages', (SELECT department_id FROM departments WHERE department_name='ICT Department'), 3),
('ELE-EWI', 'Electrical Wireman', (SELECT department_id FROM departments WHERE department_name='Electrical Department'), 12),
('ELE-BEW', 'Basic Electrical Wiring & Safety', (SELECT department_id FROM departments WHERE department_name='Electrical Department'), 3)
ON DUPLICATE KEY UPDATE
  course_name = VALUES(course_name),
  department_id = VALUES(department_id),
  duration_months = VALUES(duration_months),
  status = 'active';
