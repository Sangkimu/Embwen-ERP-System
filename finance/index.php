<?php
require_once "../config.php";
requireLogin();

// Prevent unauthorized entry outside the designated financial boundaries
if(!allowed(['finance'])){ http_response_code(403); die("Access denied."); }

// Fetch the current logged-in user profile attributes safely from session contexts
$userRole = user()['role'] ?? 'staff'; 
$userName = user()['name'] ?? 'Finance User';

// --- 1. TELEMETRY AGGREGATION BLOCK (Role-based scope restriction) ---
$paymentsCount  = $pdo->query('SELECT COUNT(*) FROM fee_payments')->fetchColumn();
$totalCollected = $pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments')->fetchColumn();
$totalExpenses  = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn();

// Compute global billing metrics (Managers and Officers access only)
$totalExpectedBilling = 0;
$arrearsBalance = 0;
if ($userRole === 'super_admin' || $userRole === 'admin' || $userRole === 'manager') {
    $totalExpectedBilling = $pdo->query("SELECT COALESCE(SUM(fs.amount), 0) FROM students s JOIN fee_structure fs ON s.course_id = fs.course_id WHERE s.status = 'active'")->fetchColumn();
    $arrearsBalance = max(0, $totalExpectedBilling - $totalCollected);
}

// --- 2. CONFIGURING LEVEL METRIC INFOGRAPHIC MATRICES ---
$stats = [];
if ($userRole === 'super_admin' || $userRole === 'manager') {
    // Finance Manager Scope: Global institutional analytics focus
    $stats = [
        ['Total Revenue Inflow', 'KES ' . number_format($totalCollected, 2), 'Total collections processed', '#2f855a'],
        ['Total Operating Outflows', 'KES ' . number_format($totalExpenses, 2), 'Approved cash vouchers', '#c53030'],
        ['Outstanding Liabilities', 'KES ' . number_format($arrearsBalance, 2), 'Total student arrears debt', '#b7791f'],
        ['System Activity', number_format($paymentsCount), 'Total processed transaction logs', '#1e3d73']
    ];
} elseif ($userRole === 'admin') {
    // Finance Officer Scope: Workflow monitoring focus
    $stats = [
        ['Collected Fees', 'KES ' . number_format($totalCollected, 2), 'Semester collection records', '#2f855a'],
        ['Outstanding Arrears', 'KES ' . number_format($arrearsBalance, 2), 'Pending student receivables', '#b7791f'],
        ['Log Operations', number_format($paymentsCount), 'Vouchers recorded under your cycle', '#1e3d73']
    ];
} else {
    // Finance Clerk Scope: Focused front-desk entry logging metrics
    $stats = [
        ['Transactions Processed', number_format($paymentsCount), 'Your logged transaction collections', '#1e3d73'],
        ['Total Fees Logged', 'KES ' . number_format($totalCollected, 2), 'Direct desk registries compiled', '#2f855a']
    ];
}

$services = moduleServices('finance', user()['module'] === 'admin' ? 'finance_manager' : user()['role']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Finance Workspace Terminal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-layout { display: grid; grid-template-columns: 3fr 1fr; gap: 24px; margin-top: 24px; }
        @media(max-width: 1024px) { .dashboard-layout { grid-template-columns: 1fr; } }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; position: relative; overflow: hidden; }
        .stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background: var(--accent-color, #1e3d73); }
        .stat-card span { font-size: 11px; text-transform: uppercase; color: #718096; font-weight: 700; margin-bottom: 6px; }
        .stat-card strong { font-size: 20px; color: #1a202c; font-weight: 700; margin-bottom: 4px; }
        .stat-card small { font-size: 12px; color: #718096; }

        .quick-actions-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .action-heading { font-size: 11px; font-weight: 700; color: #4a5568; text-transform: uppercase; margin-right: 8px; }
        .action-link { background: #f7fafc; border: 1px solid #cbd5e0; color: #2d3748; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .action-link:hover { background: #1e3d73; color: #fff; border-color: #1e3d73; }

        .service-row { display: flex; align-items: center; padding: 14px 16px; border-radius: 8px; border: 1px solid #edf2f7; text-decoration: none; color: inherit; transition: all 0.2s; background: #fff; margin-bottom: 10px; }
        .service-row:hover { border-color: #cbd5e0; background: #f7fafc; }
        .service-icon-box { background: #f0f4f8; color: #1e3d73; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 16px; }
        .document-actions { display:grid; gap:12px; margin-top:24px; }
        .document-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
        .document-row strong { display:block; color:#2d3748; font-size:13px; }
        .document-row small { color:#718096; font-size:11px; }
        .document-buttons { display:flex; gap:7px; flex-wrap:wrap; }
        .document-button { border:0; border-radius:6px; padding:8px 10px; background:#1e3d73; color:#fff; font-size:11px; font-weight:700; cursor:pointer; text-decoration:none; }
        .document-button.secondary { background:#e2e8f0; color:#2d3748; }
        @media(max-width:600px){.document-row{display:block}.document-buttons{margin-top:10px}}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Finance Workspace</b></div>
            <div class="top-user">
                <div class="avatar"><?= strtoupper(substr($userName, 0, 2)) ?></div>
                <div>
                    <span style="font-weight:600; display:block;"><?= htmlspecialchars($userName) ?></span>
                    <small style="color:#718096; font-size:11px;"><?= str_replace('_', ' ', ucfirst($userRole)) ?></small>
                </div>
            </div>
            <div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div>
        </header>
        
        <section class="content">
            <div class="page-head">
                <div>
                    <p class="eyebrow">FINANCE DASHBOARD</p>
                    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $userName)[0]) ?></h1>
                    <p class="text-muted">Here is an overview of the operations available to your tracking clearance privileges.</p>
                </div>
            </div>

            <!-- DYNAMIC QUICK ACTION BUTTONS BAR (Hides/Shows links based on user role permissions) -->
            <div class="quick-actions-bar">
                <span class="action-heading">Quick Actions:</span>
                <a href="payments.php" class="action-link">💰 Record Fee Receipt</a>
                
                <?php if ($userRole === 'super_admin' || $userRole === 'admin' || $userRole === 'manager'): ?>
                    <a href="expenses.php" class="action-link">📉 File Expense Voucher</a>
                    <a href="suppliers.php" class="action-link">🏭 Open Procurement</a>
                <?php endif; ?>

                <?php if ($userRole === 'super_admin' || $userRole === 'manager'): ?>
                    <a href="bulk_invoice.php" class="action-link" style="border-color:#1e3d73; color:#1e3d73;">⚙️ Execute Semester Bulk Billing</a>
                <?php endif; ?>
            </div>

            <!-- ROLE-SPECIFIC ANALYTICS CARD GRID -->
            <div class="stats-grid">
                <?php foreach($stats as $s): ?>
                    <div class="stat-card" style="--accent-color: <?= $s[3] ?>;">
                        <span><?= htmlspecialchars($s[0]) ?></span>
                        <strong><?= htmlspecialchars($s[1]) ?></strong>
                        <small><?= htmlspecialchars($s[2]) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ALLOCATED OPERATIONS LINKS BOX -->
            <div class="dashboard-layout">
                <section class="card" style="background:#fff; padding:24px; border-radius:8px; border:1px solid #e2e8f0;">
                    <h3 style="margin:0 0 15px 0; font-size:15px; text-transform:uppercase; color:#4a5568; letter-spacing:0.5px;">Authorized Workspace Submodules</h3>
                    <div style="margin-bottom:16px;">
                        <label for="financeQuickNav" style="display:block; margin-bottom:8px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Quick module access</label>
                        <select id="financeQuickNav" style="width:100%; padding:10px 12px; border:1px solid #cbd5e0; border-radius:8px; background:#fff; font-size:14px;" onchange="navigateToService(this.value)">
                            <option value="">Select a module</option>
                            <?php foreach($services as $service): ?>
                                <option value="<?= htmlspecialchars($service['path'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($service['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="services-list">
                        <div class="service-row">
                            <div class="service-icon-box">◆</div>
                            <div>
                                <b style="color:#2d3748; font-size:14px;">Use the quick-access dropdown above</b>
                                <small style="display:block; color:#718096; font-size:12px; margin-top:2px;">Jump directly to the finance tool you need without a long list.</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- INFORMATION BULLETINS BOX -->
                <section class="sidebar-cards">
                    <div class="card" style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 12px 0; font-size:12px; text-transform:uppercase; color:#718096;">Identity Parameters</h4>
                        <p style="margin:0; font-size:13px; color:#4a5568; line-height:1.4;">
                            Your account is assigned to the **Finance Department**. All logs submitted during this active session will be permanently tracked under your user name.
                        </p>
                    </div>
                    <div class="card document-actions" style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 2px; font-size:12px; text-transform:uppercase; color:#718096;">Reference Documents</h4>
                        <div class="document-row"><div><strong>2026 Fee Structure</strong><small>Boarder and day-scholar schedules</small></div><div class="document-buttons"><a class="document-button" href="<?=htmlspecialchars(appAssetPath('Fee structure.pdf'))?>" target="_blank">View</a><button class="document-button" type="button" onclick="printPdf('<?=htmlspecialchars(appAssetPath('Fee structure.pdf'))?>')">Print</button><a class="document-button secondary" href="<?=htmlspecialchars(appAssetPath('Fee structure.pdf'))?>" download>Download</a></div></div>
                        <div class="document-row"><div><strong>Payment Details</strong><small>Payment instructions and account details</small></div><div class="document-buttons"><a class="document-button" href="<?=htmlspecialchars(appAssetPath('Payment Details.pdf'))?>" target="_blank">View</a><button class="document-button" type="button" onclick="printPdf('<?=htmlspecialchars(appAssetPath('Payment Details.pdf'))?>')">Print</button><a class="document-button secondary" href="<?=htmlspecialchars(appAssetPath('Payment Details.pdf'))?>" download>Download</a></div></div>
                    </div>
                </section>
            </div>
        </section>
    </main>
</div>
<script>
function navigateToService(path) {
    if (!path) return;
    window.location.href = path;
}
function printPdf(url){var pdf=window.open(url,'_blank');if(pdf){setTimeout(function(){pdf.print();},1000);}}</script>
</body>
</html>
