<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance'])) {
    http_response_code(403);
    die("Access denied.");
}

$notices = $pdo->query("SELECT n.notice_id, n.title, n.content, n.posted_on, u.full_name FROM notices n LEFT JOIN admin_users u ON u.admin_id = n.posted_by ORDER BY n.posted_on DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Notices | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:grid; grid-template-columns: 260px 1fr; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        .notice { border:1px solid #e2e8f0; border-left:4px solid #2563eb; padding:16px; border-radius:8px; margin-top:16px; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Notices</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Notice Bulletin</h1>
            <p class="text-muted">Published notices for the college community.</p>
            <?php foreach ($notices as $notice): ?>
                <div class="notice">
                    <h3><?= htmlspecialchars($notice['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
                    <small>Posted by <?= htmlspecialchars($notice['full_name'] ?? 'System') ?> on <?= htmlspecialchars(date('d M Y', strtotime($notice['posted_on']))) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
