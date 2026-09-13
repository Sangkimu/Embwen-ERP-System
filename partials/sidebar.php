<?php
// Determine the current active file name to toggle focus states dynamically
$currentPage = basename($_SERVER['PHP_SELF']);

// Extract the user role securely from active session context elements
$userRole = user()['role'] ?? 'staff'; 
$userModule = user()['module'] ?? '';
$logoutPath = appBasePath() . '/logout.php';

/**
 * Helper function to output active styles if the page matches
 */
function isNavActive($pageName, $currentPage) {
    if ($currentPage === $pageName) {
        return 'style="display: flex; align-items: center; gap: 12px; color: #ffffff; text-decoration: none; padding: 12px; border-radius: 6px; background: rgba(255,255,255,0.15); font-size: 14px; font-weight: 600; border-left: 3px solid #fff5f5;"';
    }
    return 'style="display: flex; align-items: center; gap: 12px; color: #e2e8f0; text-decoration: none; padding: 12px; border-radius: 6px; font-size: 14px; transition: background 0.2s; border-left: 3px solid transparent;"';
}
?>

<div class="sidebar-wrapper" style="background: #1e3d73; color: #ffffff; min-height: 100vh; padding: 20px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
    
    <div>
        <!-- Branding Block Header -->
        <div class="sidebar-brand" style="margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
            <h4 style="margin: 0; font-size: 18px; color: #ffffff; letter-spacing: 0.5px;">Polytechnic ERP</h4>
            <small style="color: #cbd5e0; font-size: 11px; text-transform: uppercase;">
                <?= $userModule === 'students' ? 'Student Portal' : (($userRole === 'dean') ? "Dean's Office" : (($userRole === 'lecturers') ? 'Faculty Space' : (($userRole === 'super_admin' || $userRole === 'manager') ? 'Manager Console' : (($userRole === 'admin') ? 'Officer Workspace' : 'Clerk Panel')))) ?>
            </small>
        </div>

        <!-- Main Navigation List Link Groups -->
        <ul class="nav-menu" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px;">
            
            <!-- ================= ROLE MODULE 1: STUDENTS ================= -->
            <?php if ($userModule === 'students'): ?>
                <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 5px; padding-left: 12px;">Student Services</li>
                <li class="nav-item"><a href="index.php" <?= isNavActive('index.php', $currentPage) ?>><span class="nav-icon">📊</span> Dashboard</a></li>
                <li class="nav-item"><a href="profile.php" <?= isNavActive('profile.php', $currentPage) ?>><span class="nav-icon">👤</span> My Profile</a></li>
                <li class="nav-item"><a href="fees.php" <?= isNavActive('fees.php', $currentPage) ?>><span class="nav-icon">💳</span> My Fees</a></li>
                <li class="nav-item"><a href="fee_schedule_pdf.php" <?= isNavActive('fee_schedule_pdf.php', $currentPage) ?>><span class="nav-icon">📄</span> Fee Structure PDF</a></li>
                <li class="nav-item"><a href="coursework.php" <?= isNavActive('coursework.php', $currentPage) ?>><span class="nav-icon">📚</span> Coursework & Marks</a></li>
                <li class="nav-item"><a href="notices.php" <?= isNavActive('notices.php', $currentPage) ?>><span class="nav-icon">📣</span> Notices</a></li>
            
            <!-- ================= ROLE MODULE 2: DEAN OF STUDENTS ================= -->
            <?php elseif ($userModule === 'dean'): ?>
                <li class="nav-item"><a href="index.php" <?= isNavActive('index.php', $currentPage) ?>><span class="nav-icon">📊</span> Dashboard Home</a></li>
                <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Admissions & Registry</li>
                <li class="nav-item"><a href="admissions.php" <?= isNavActive('admissions.php', $currentPage) ?>><span class="nav-icon">📝</span> Admissions & ID Generator</a></li>
                <li class="nav-item"><a href="../students.php" <?= isNavActive('students.php', $currentPage) ?>><span class="nav-icon">👥</span> Student Registry</a></li>
                <li class="nav-item"><a href="notices.php" <?= isNavActive('notices.php', $currentPage) ?>><span class="nav-icon">📣</span> Content & Notices</a></li>
                <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Boarding & Welfare</li>
                <li class="nav-item"><a href="accommodation.php" <?= isNavActive('accommodation.php', $currentPage) ?>><span class="nav-icon">🏢</span> Boarding Management</a></li>
                <li class="nav-item"><a href="welfare.php" <?= isNavActive('welfare.php', $currentPage) ?>><span class="nav-icon">🛡️</span> Student Welfare</a></li>

            <!-- ================= ROLE MODULE 3: LECTURERS / FACULTY ================= -->
            <?php elseif ($userRole === 'lecturers'): ?>
                <li class="nav-item"><a href="index.php" <?= isNavActive('index.php', $currentPage) ?>><span class="nav-icon">📊</span> Dashboard Home</a></li>
                <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Academic Evaluation</li>
                <li class="nav-item"><a href="lecturer_grades.php" <?= isNavActive('lecturer_grades.php', $currentPage) ?>><span class="nav-icon">📚</span> Workshop Gradebook</a></li>

            <!-- ================= ROLE MODULE 4: STAFF / FINANCE / ADMIN ================= -->
            <?php else: ?>
                <!-- Central Dashboard Entry -->
                <li class="nav-item"><a href="index.php" <?= isNavActive('index.php', $currentPage) ?>><span class="nav-icon">📊</span> Dashboard Home</a></li>

                <!-- Revenue Subsections -->
                <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Revenue & Collections</li>
                <li class="nav-item"><a href="payments.php" <?= isNavActive('payments.php', $currentPage) ?>><span class="nav-icon">💰</span> Payments Ledger</a></li>

                <?php if ($userRole === 'super_admin' || $userRole === 'manager' || $userRole === 'admin'): ?>
                    <li class="nav-item"><a href="fee_structure.php" <?= isNavActive('fee_structure.php', $currentPage) ?>><span class="nav-icon">📝</span> Billing Blueprints</a></li>
                <?php endif; ?>

                <?php if ($userRole === 'super_admin' || $userRole === 'manager'): ?>
                    <li class="nav-item"><a href="bulk_invoice.php" <?= isNavActive('bulk_invoice.php', $currentPage) ?>><span class="nav-icon">⚙️</span> Bulk Invoicing Engine</a></li>
                <?php endif; ?>

                <!-- Outflows Subsections -->
                <?php if ($userRole === 'super_admin' || $userRole === 'manager' || $userRole === 'admin'): ?>
                    <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Outflows & Operations</li>
                    <li class="nav-item"><a href="expenses.php" <?= isNavActive('expenses.php', $currentPage) ?>><span class="nav-icon">📉</span> Expense Vouchers</a></li>
                    <li class="nav-item"><a href="suppliers.php" <?= isNavActive('suppliers.php', $currentPage) ?>><span class="nav-icon">🏭</span> Procurement Portal</a></li>
                <?php endif; ?>

                <!-- Auditing Subsections -->
                <?php if ($userRole === 'super_admin' || $userRole === 'manager' || $userRole === 'admin'): ?>
                    <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">Auditing & Insights</li>
                    <li class="nav-item"><a href="reports.php" <?= isNavActive('reports.php', $currentPage) ?>><span class="nav-icon">📋</span> Financial Reports</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Quick Actions Panel Footer Profile Block Element -->
    <div class="sidebar-footer" style="padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; margin-top: 30px;">
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 13px; font-weight: 600; color: #ffffff;"><?= htmlspecialchars(user()['name'] ?? 'ERP User') ?></span>
            <span style="font-size: 11px; color: #cbd5e0; text-transform: capitalize;"><?= str_replace('_', ' ', $userRole) ?></span>
        </div>
        <a href="<?=htmlspecialchars($logoutPath)?>" title="Sign Out Session" style="color: #fc8181; text-decoration: none; font-size: 16px; transition: transform 0.2s; display: inline-block;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            🚪
        </a>
    </div>
</div>
