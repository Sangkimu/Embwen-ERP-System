ROLE-BASED VOCATIONAL COLLEGE ERP

This version changes the ERP to module-specific authentication.

Modules:
1. Admin
2. Finance
3. Dean's Office
4. Student Portal

On login, module_users.module determines the dashboard:
admin -> /admin/index.php
finance -> /finance/index.php
dean -> /dean/index.php
students -> /student/index.php

IMPORTANT:
The original supplied schema only has admin_users for staff/admin accounts and
student_accounts for students. It does NOT contain separate Finance/Dean user
tables. schema_roles.sql therefore adds module_users to support your requested
architecture.

Suggested roles:
Admin: super_admin, admin, staff
Finance: finance_manager, finance_officer, cashier
Dean: dean, admissions_officer, welfare_officer, registrar
Students: student

For production, create accounts using password_hash() and verify with
password_verify(). Never store plaintext passwords.

The module dashboards are intentionally separated. A user signed into Finance
cannot access Dean/Admin/Student workspaces because each module checks the
session module server-side.

ACCOUNT SETUP

First-time users can register from login.php. Admin registration provides one
account for each admin role (super_admin, admin and staff); claimed roles are
removed from the registration form and later users must sign in. Students can
register unique student usernames and then sign in normally.

If module_users was created before the admin role constraint was added, run the
two ALTER TABLE statements at the top of schema_roles.sql once.
