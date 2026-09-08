<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}

// Securely fetch and aggregate financial analytics from your production schema tables
$paymentsCount = $pdo->query('SELECT COUNT(*) FROM fee_payments')->fetchColumn();
$totalCollected = $pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments')->fetchColumn();
$totalExpenses  = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn();

$stats = [
    ['Payments', number_format($paymentsCount)],
    ['Collected', 'KES ' . number_format($totalCollected, 2)],
    ['Expenses', 'KES ' . number_format($totalExpenses, 2)],
    ['Receipts', number_format($paymentsCount)]
];

$services = moduleServices('finance', user()['module'] === 'admin' ? 'finance_manager' : user()['role']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Finance Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Modernized visual layout refinements matching your design system specs */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
        }
        .stat span {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .stat strong {
            font-size: 22px;
            color: #1a202c;
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        .service {
            display: flex;
            align-items: center;
            padding: 16px;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            border: 1px solid transparent;
        }
        .service:hover {
            background: #f7fafc;
            border-color: #e2e8f0;
        }
        .service-icon {
            color: #1e3d73;
            font-size: 14px;
            margin-right: 16px;
            width: 24px;
            text-align: center;
        }
        .service b {
            display: block;
            color: #2d3748;
            font-size: 15px;
            margin-bottom: 2px;
        }
        .service small {
            color: #718096;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <?php include "../partials/sidebar.php"; ?>
    </aside>
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
            
            <!-- Real-Time Analytical Operational Value KPI Metrics Cards -->
            <div class="stats">
                <?php foreach($stats as $s): ?>
                    <div class="stat">
                        <span><?=htmlspecialchars($s[0])?></span>
                        <strong><?=htmlspecialchars($s[1])?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="grid">
                <section class="card">
                    <h2>Your Modules</h2>
                    <div style="margin-top: 15px;">
                        <?php foreach($services as $s): ?>
                            <a class="service" href="<?=htmlspecialchars($s['path'])?>">
                                <div class="service-icon">◆</div>
                                <div>
                                    <b><?=htmlspecialchars($s['label'])?></b>
                                    <small><?=htmlspecialchars($s['description'])?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </section>
    </main>
</div>
</body>
</html>
