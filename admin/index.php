<?php
require_once "../config.php";
requireLogin();

// Prevent non-privileged roles from cracking the directory root
if(!allowed(['admin', 'finance'])){ http_response_code(403); die("Access denied."); }

$userRole = user()['role'] ?? 'staff';

// --- 1. COMPILE LIVE SYSTEM TELEMETRY (Based on Active Role) ---
$totalStaff   = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
$totalDepts   = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$activeNotices= $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();

// Fetch a quick stream of recent institutional announcements
$noticesQuery = "SELECT n.*, u.full_name FROM notices n JOIN admin_users u ON n.posted_by = u.admin_id ORDER BY n.posted_on DESC LIMIT 3";
$notices = $pdo->query($noticesQuery)->fetchAll();

// Fetch database records overview for Super Admins
$recentUsers = [];
if ($userRole === 'super_admin' || $userRole === 'admin') {
    $recentUsers = $pdo->query("SELECT username, full_name, role, status FROM admin_users ORDER BY created_at DESC LIMIT 5")->fetchAll();
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Administration Console</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px; }
        @media(max-width: 900px) { .admin-layout { grid-template-columns: 1fr; } }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-top: 4px solid #1e3d73; }
        .stat-card span { font-size: 11px; text-transform: uppercase; color: #718096; font-weight: 700; letter-spacing: 0.5px; }
        .stat-card h3 { margin: 6px 0 0 0; font-size: 24px; color: #1a202c; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; background: white; border: 1px solid #edf2f7; border-radius: 6px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #edf2f7; }
        .data-table th { background: #f7fafc; color: #4a5568; font-size: 11px; text-transform: uppercase; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-admin { background: #fed7d7; color: #9b2c2c; }
        .badge-staff { background: #e2e8f0; color: #4a5568; }
        .notice-item { background: #f7fafc; border-left: 3px solid #3182ce; padding: 12px; border-radius: 4px; margin-bottom: 12px; }
        .notice-item h4 { margin: 0 0 4px 0; color: #2d3748; }
        .notice-item p { margin: 0; font-size: 13px; color: #4a5568; }
    </style>
</head>
<body>
<div class="app">
    <!-- The automated sidebar will dynamically read filenames to activate links -->
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Administration</b></div>
            <div class="top-user">
                <div class="avatar"><?= strtoupper(substr(user()['name'], 0, 2)) ?></div>
                <?= htmlspecialchars(user()['name']) ?> <small style="margin-left:5px; color:#718096;">(<?= ucfirst($userRole) ?>)</small>
            </div>
        </header>

        <section class="content">
            <div class="page-head" style="margin-bottom: 24px;">
                <div>
                    <h1>Institutional Setup & Command Console</h1>
                    <p class="text-muted">Configure baseline course requirements, publish global bulletins, and supervise active operational accounts.</p>
                </div>
            </div>

            <!-- Dynamic Metrics Display Matrix -->
            <div class="stats-row">
                <div class="stat-card"><span>Active Catalog Tracks</span><h3><?= $totalCourses ?> Courses</h3></div>
                <div class="stat-card"><span>Registered Departments</span><h3><?= $totalDepts ?> Academic Units</h3></div>
                <div class="stat-card"><span>Active Staff Accounts</span><h3><?= $totalStaff ?> Logged Users</h3></div>
                <div class="stat-card"><span>Bulletins Posted</span><h3><?= $activeNotices ?> Memos</h3></div>
            </div>

            <div class="admin-layout">
                <!-- Left Action Column -->
                <div>
                    <?php if ($userRole === 'super_admin' || $userRole === 'admin'): ?>
                        <div class="card" style="margin-bottom: 24px; background:white; padding:20px; border-radius:8px; border:1px solid #edf2f7;">
                            <h3 style="margin-top:0; margin-bottom:15px; font-size:15px;">System Security Overview: Recent Users Registration Trail</h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Staff Full Name</th>
                                        <th>Username Handle</th>
                                        <th>Permission Role</th>
                                        <th>Account Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentUsers as $u): ?>
                                        <tr>
                                            <td><b><?= htmlspecialchars($u['full_name']) ?></b></td>
                                            <td style="font-family: monospace; color:#4a5568;"><?= htmlspecialchars($u['username']) ?></td>
                                            <td><span class="badge badge-<?= $u['role'] === 'staff' ? 'staff' : 'admin' ?>"><?= $u['role'] ?></span></td>
                                            <td><span style="color: <?= $u['status']==='active' ? '#2f855a' : '#c53030' ?>; font-weight:600;">● <?= ucfirst($u['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div style="margin-top:15px; text-align:right;">
                                <a href="staff.php" style="color:#1e3d73; font-size:13px; font-weight:600; text-decoration:none;">Manage All Accounts &rarr;</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card" style="background:white; padding:20px; border-radius:8px; border:1px solid #edf2f7;">
                        <h3 style="margin-top:0; margin-bottom:15px; font-size:15px;">Administrative Quick Links</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <a href="courses.php" style="padding:15px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:6px; color:#2d3748; text-decoration:none; font-weight:600; text-align:center;">📚 Setup Courses & Depts</a>
                            <a href="notices.php" style="padding:15px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:6px; color:#2d3748; text-decoration:none; font-weight:600; text-align:center;">📣 Post Notice Bulletin</a>
                        </div>
                    </div>
                </div>

                <!-- Right Bulletins Timeline Sidebar -->
                <div class="card" style="background:white; padding:20px; border-radius:8px; border:1px solid #edf2f7; height: fit-content;">
                    <h3 style="margin-top:0; margin-bottom:15px; font-size:14px; text-transform:uppercase; color:#718096; letter-spacing:0.5px;">Live Notices Pipeline</h3>
                    <?php if(empty($notices)): ?>
                        <p style="color:#a0aec0; font-size:13px; text-align:center; padding:20px;">No public announcements published yet.</p>
                    <?php else: ?>
                        <?php foreach($notices as $n): ?>
                            <div class="notice-item">
                                <h4><?= htmlspecialchars($n['title']) ?></h4>
                                <p><?= htmlspecialchars(substr($n['content'], 0, 80)) ?>...</p>
                                <small style="font-size:11px; color:#718096; display:block; margin-top:5px;">By: <?= htmlspecialchars($n['full_name']) ?> | <?= date('d M', strtotime($n['posted_on'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
