<?php
// Determine the current active file name to toggle focus states dynamically
$currentPage = basename($_SERVER['PHP_SELF']);

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
            <h4 style="margin: 0; font-size: 18px; color: #ffffff; letter-spacing: 0.5px;">College ERP</h4>
            <small style="color: #cbd5e0; font-size: 11px; text-transform: uppercase;">Finance Workspace</small>
        </div>

        <!-- Main Navigation List Link Groups -->
        <ul class="nav-menu" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px;">
            
            <!-- 1. Central Command Console -->
            <li class="nav-item">
                <a href="index.php" <?= isNavActive('index.php', $currentPage) ?>>
                    <span class="nav-icon">📊</span> Dashboard Home
                </a>
            </li>

            <!-- Section Categorization Label -->
            <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">
                Revenue & Collections
            </li>

            <!-- 2. Fee Payments Ledger Manager Link Component -->
            <li class="nav-item">
                <a href="payments.php" <?= isNavActive('payments.php', $currentPage) ?>>
                    <span class="nav-icon">💰</span> Payments Ledger
                </a>
            </li>

            <!-- 3. Dynamic Fee Structure Setup Configuration Configurations -->
            <li class="nav-item">
                <a href="fee_structure.php" <?= isNavActive('fee_structure.php', $currentPage) ?>>
                    <span class="nav-icon">📝</span> Billing Blueprints
                </a>
            </li>

            <!-- Section Categorization Label -->
            <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">
                Outflows & Operations
            </li>

            <!-- 4. Departmental & Workshop Expenses Control Component -->
            <li class="nav-item">
                <a href="expenses.php" <?= isNavActive('expenses.php', $currentPage) ?>>
                    <span class="nav-icon">📉</span> Expense Vouchers
                </a>
            </li>

            <!-- 5. Procurement and Vendor Invoices Portal Section -->
            <li class="nav-item">
                <a href="suppliers.php" <?= isNavActive('suppliers.php', $currentPage) ?>>
                    <span class="nav-icon">🏭</span> Vendor Portals
                </a>
            </li>

            <!-- Section Categorization Label -->
            <li class="nav-section-title" style="font-size: 11px; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; font-weight: 700; margin-top: 15px; margin-bottom: 5px; padding-left: 12px;">
                Auditing & Insights
            </li>

            <!-- 6. Real-time Arrears Lists & Government Balances Compilation Ledgers -->
            <li class="nav-item">
                <a href="reports.php" <?= isNavActive('reports.php', $currentPage) ?>>
                    <span class="nav-icon">📋</span> Financial Reports
                </a>
            </li>
        </ul>
    </div>

    <!-- Quick Actions Panel Footer Profile Block Element -->
    <div class="sidebar-footer" style="padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; margin-top: 30px;">
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 13px; font-weight: 600; color: #ffffff;"><?= htmlspecialchars(user()['name'] ?? 'Finance User') ?></span>
            <span style="font-size: 11px; color: #cbd5e0;">Clerk ID: #<?= htmlspecialchars(user()['admin_id'] ?? '1') ?></span>
        </div>
        <a href="../logout.php" title="Sign Out Session" style="color: #fc8181; text-decoration: none; font-size: 16px; transition: transform 0.2s; display: inline-block;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            🚪
        </a>
    </div>
</div>
