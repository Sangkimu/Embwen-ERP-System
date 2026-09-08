<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}

// --- 1. CORE DATA COMPILING ENGINE (Polytechnic Relational Calculations) ---
// Fetch analytical metrics safely from your tables
$paymentsCount  = $pdo->query('SELECT COUNT(DISTINCT receipt_no) FROM fee_payments')->fetchColumn();
$totalCollected = $pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments')->fetchColumn();
$totalExpenses  = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn();

// Calculate target collections based on current active student structure profiles
$totalExpectedBilling = $pdo->query("
    SELECT COALESCE(SUM(fs.amount), 0)
    FROM students s
    JOIN fee_structure fs ON s.course_id = fs.course_id
    WHERE s.status = 'active'
")->fetchColumn();

// Dynamic computation variables for UI tracking metrics
$arrearsBalance = max(0, $totalExpectedBilling - $totalCollected);
$collectionTargetPercentage = $totalExpectedBilling > 0 ? min(100, round(($totalCollected / $totalExpectedBilling) * 100, 1)) : 0;

// Fetch month-over-month transactional metric shifts
$currentMonthRevenue = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetchColumn();
$priorMonthRevenue   = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments WHERE MONTH(payment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(payment_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
$revenueGrowthPercent = $priorMonthRevenue > 0 ? round((($currentMonthRevenue - $priorMonthRevenue) / $priorMonthRevenue) * 100, 1) : 0;

// Compile data arrays for operational KPI card rendering loops
$stats = [
    ['Payments', number_format($paymentsCount), 'Transactions logged', '#1e3d73'],
    ['Collected', 'KES ' . number_format($totalCollected, 2), $collectionTargetPercentage . '% of semester target met', '#2f855a'],
    ['Expenses', 'KES ' . number_format($totalExpenses, 2), 'Operational outflows', '#c53030'],
    ['Outstanding Arrears', 'KES ' . number_format($arrearsBalance, 2), 'Total collectible balance', '#b7791f']
];

// --- 2. ALERT TRACKING PATTERNS ENGINE ---
$systemAlerts = [];
// Alert condition A: High institutional balance liabilities check
if ($arrearsBalance > ($totalExpectedBilling * 0.4)) {
    $systemAlerts[] = [
        'type' => 'warning',
        'title' => 'Critical Institutional Deficit Risk',
        'desc' => 'Outstanding student balance liabilities exceed 40% of standard billing projections.'
    ];
}
// Alert condition B: Multi-channel configuration verification check
$unassignedPayments = $pdo->query("SELECT COUNT(*) FROM fee_payments WHERE fee_structure_id NOT IN (SELECT fee_structure_id FROM fee_structure)")->fetchColumn();
if ($unassignedPayments > 0) {
    $systemAlerts[] = [
        'type' => 'danger',
        'title' => 'Unmapped Payment Entries Detected',
        'desc' => "There are {$unassignedPayments} transactions pointing to broken configuration entities."
    ];
}

$services = moduleServices('finance', user()['module'] === 'admin' ? 'finance_manager' : user()['role']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Polytechnic Finance Workspace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Modern Layout & Architecture Stylesheets */
        .dashboard-layout { display: grid; grid-template-columns: 3fr 1fr; gap: 24px; margin-top: 24px; }
        @media(max-width: 1024px) { .dashboard-layout { grid-template-columns: 1fr; } }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; position: relative; overflow: hidden; }
        .stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background: var(--accent-color, #1e3d73); }
        .stat-card span { font-size: 12px; text-transform: uppercase; color: #718096; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 6px; }
        .stat-card strong { font-size: 22px; color: #1a202c; font-weight: 700; margin-bottom: 4px; }
        .stat-card small { font-size: 12px; color: #4a5568; display: flex; align-items: center; gap: 4px; }

        .quick-actions-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .action-heading { font-size: 12px; font-weight: 700; color: #4a5568; text-transform: uppercase; margin-right: 8px; }
        .action-link { background: #f7fafc; border: 1px solid #cbd5e0; color: #2d3748; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .action-link:hover { background: #1e3d73; color: #fff; border-color: #1e3d73; }

        .alert-box { padding: 16px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid; display: flex; flex-direction: column; gap: 2px; }
        .alert-warning { background: #fffaf0; border-color: #dd6b20; color: #7b341e; }
        .alert-danger { background: #fff5f5; border-color: #e53e3e; color: #742a2a; }
        .alert-box h5 { margin: 0; font-size: 14px; font-weight: 700; }
        .alert-box p { margin: 0; font-size: 12px; }

        .services-list { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
        .service-row { display: flex; align-items: center; padding: 16px; border-radius: 8px; border: 1px solid #edf2f7; text-decoration: none; color: inherit; transition: all 0.2s; background: #fff; }
        .service-row:hover { border-color: #cbd5e0; background: #f7fafc; transform: translateX(2px); }
        .service-icon-box { background: #ebf8ff; color: #2b6cb0; width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 16px; flex-shrink: 0; }
        .service-row b { display: block; color: #2d3748; font-size: 15px; }
        .service-row small { color: #718096; font-size: 13px; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Finance</b></div>
            <div class="top-user">
                <div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div>
                <?=htmlspecialchars(user()['name'])?>
            </div>
        </header>
        
        <section class="content">
            <div class="page-head">
                <div>
                    <p class="eyebrow">FINANCE MODULE</p>
                    <h1>Finance Dashboard</h1>
                    <p>Manage the services assigned to your role.</p>
                </div>
            </div>

            <!-- Workflow Quick Actions Panel Component Block -->
            <div class="quick-actions-bar">
                <span class="action-heading">Quick Routing:</span>
                <a href="payments.php?action=new" class="action-link">⚡ Receipt Fee Payment</a>
                <a href="fee_structure.php?action=create" class="action-link">📝 Modify Billing Configurations</a>
                <a href="expenses.php?action=log" class="action-link">📉 File Expense Voucher</a>
            </div>

            <!-- Dynamic Multi-Metric Operational Cards Grid Matrix -->
            <div class="stats-grid">
                <?php foreach($stats as $s): ?>
                    <div class="stat-card" style="--accent-color: <?= $s[3] ?>;">
                        <span><?= htmlspecialchars($s[0]) ?></span>
                        <strong><?= htmlspecialchars($s[1]) ?></strong>
                        <small>
                            <?php if ($s[0] === 'Payments' && $revenueGrowthPercent !== 0): ?>
                                <b style="color: <?= $revenueGrowthPercent > 0 ? '#38a169' : '#e53e3e' ?>;">
                                    <?= $revenueGrowthPercent > 0 ? '▲' : '▼' ?> <?= abs($revenueGrowthPercent) ?>%
                                </b> vs last month
                            <?php else: ?>
                                <?= htmlspecialchars($s[2]) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Interface Dynamic Dashboard Content Columns split -->
            <div class="dashboard-layout">
                <!-- Primary Services Module Display Left Column Column -->
                <section class="card">
                    <h2 style="margin:0; font-size:16px; font-weight:700; color:#1a202c;">System Submodules</h2>
                    <div class="services-list">
                        <?php foreach($services as $s): ?>
                            <a class="service-row" href="<?=htmlspecialchars($s['path'])?>">
                                <div class="service-icon-box">◆</div>
                                <div>
                                    <b><?=htmlspecialchars($s['label'])?></b>
                                    <small><?=htmlspecialchars($s['description'])?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- System Operational Notification/Alert Pipeline Right Column -->
                <section class="sidebar-cards">
                    <div class="card" style="height: 100%;">
