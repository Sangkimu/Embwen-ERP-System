PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'staff',
    email TEXT,
    phone TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS departments (
    department_id INTEGER PRIMARY KEY AUTOINCREMENT,
    department_name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS module_users (
    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    id_type TEXT,
    id_number TEXT,
    phone TEXT,
    staff_number TEXT,
    module TEXT NOT NULL,
    role TEXT NOT NULL,
    email TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS courses (
    course_id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_code TEXT NOT NULL UNIQUE,
    course_name TEXT NOT NULL,
    department_id INTEGER NOT NULL,
    duration_months INTEGER NOT NULL DEFAULT 12,
    entry_requirement TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS notices (
    notice_id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    target_audience TEXT NOT NULL DEFAULT 'all',
    posted_by INTEGER NOT NULL,
    posted_on TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS students (
    student_id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    identity_type TEXT,
    national_id TEXT,
    birth_certificate_no TEXT,
    phone TEXT,
    email TEXT,
    gender TEXT,
    parent_name TEXT,
    parent_phone TEXT,
    previous_academic_level TEXT,
    year_of_completion INTEGER,
    course_id INTEGER,
    study_start_date TEXT,
    expected_end_date TEXT,
    residency TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS student_accounts (
    account_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL UNIQUE,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    last_login TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS admissions (
    admission_id INTEGER PRIMARY KEY AUTOINCREMENT,
    applicant_name TEXT NOT NULL,
    course_id INTEGER NOT NULL,
    residency TEXT,
    application_date TEXT NOT NULL,
    admission_status TEXT NOT NULL DEFAULT 'pending',
    decided_by INTEGER,
    decision_date TEXT,
    student_id INTEGER,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT,
    FOREIGN KEY (decided_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS class_allocations (
    allocation_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    class_section TEXT NOT NULL,
    academic_year TEXT NOT NULL,
    allocated_by INTEGER NOT NULL,
    allocated_on TEXT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT,
    FOREIGN KEY (allocated_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS hostel_allocations (
    allocation_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    hostel_name TEXT NOT NULL,
    room_no TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'allocated',
    allocated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    released_at TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS student_welfare (
    welfare_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    priority TEXT NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    action_taken TEXT,
    handled_by INTEGER,
    record_date TEXT,
    follow_up_date TEXT,
    status TEXT NOT NULL DEFAULT 'open',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS welfare_updates (
    update_id INTEGER PRIMARY KEY AUTOINCREMENT,
    welfare_id INTEGER NOT NULL,
    note TEXT NOT NULL,
    updated_by INTEGER NOT NULL,
    updated_on TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (welfare_id) REFERENCES student_welfare(welfare_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS fee_structure (
    fee_structure_id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    fee_type TEXT NOT NULL,
    amount NUMERIC NOT NULL,
    academic_year TEXT NOT NULL,
    semester INTEGER NOT NULL DEFAULT 1,
    residency_scope TEXT NOT NULL DEFAULT 'universal',
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS fee_payments (
    payment_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    fee_structure_id INTEGER NOT NULL,
    amount_paid NUMERIC NOT NULL,
    payment_date TEXT NOT NULL,
    payment_method TEXT NOT NULL,
    receipt_no TEXT NOT NULL UNIQUE,
    reference_no TEXT,
    received_by INTEGER NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(fee_structure_id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,
    description TEXT,
    amount NUMERIC NOT NULL,
    expense_date TEXT NOT NULL,
    approved_by INTEGER NOT NULL,
    department_charge TEXT,
    voucher_no TEXT UNIQUE,
    FOREIGN KEY (approved_by) REFERENCES admin_users(admin_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    fee_structure_id INTEGER NOT NULL,
    amount NUMERIC NOT NULL,
    phone_number TEXT NOT NULL,
    account_reference TEXT NOT NULL,
    checkout_request_id TEXT NOT NULL UNIQUE,
    merchant_request_id TEXT,
    mpesa_receipt_no TEXT UNIQUE,
    result_code INTEGER,
    result_description TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    callback_payload TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(fee_structure_id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name TEXT NOT NULL,
    contact_person TEXT,
    phone TEXT,
    email TEXT,
    category TEXT
);
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_number TEXT NOT NULL UNIQUE,
    supplier_id INTEGER NOT NULL,
    order_date TEXT NOT NULL,
    estimated_cost NUMERIC NOT NULL,
    delivery_status TEXT NOT NULL DEFAULT 'Ordered',
    created_by INTEGER NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES admin_users(admin_id)
);
CREATE TABLE IF NOT EXISTS po_items (
    item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_id INTEGER NOT NULL,
    item_name TEXT NOT NULL,
    quantity_ordered INTEGER NOT NULL,
    unit_price NUMERIC NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS semesters (
    semester_id INTEGER PRIMARY KEY AUTOINCREMENT,
    semester_name TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS course_marks (
    mark_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    semester_id INTEGER,
    subject_name TEXT,
    marks NUMERIC,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(semester_id)
);

INSERT OR IGNORE INTO departments (department_name) VALUES
('Hospitality & Institutional Management'), ('Building Department'),
('Mechanical Engineering'), ('ICT Department'), ('Electrical Department');
INSERT OR IGNORE INTO semesters (semester_name, is_active) VALUES ('Semester 1', 1);
