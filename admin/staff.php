<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance'])) {
    http_response_code(403);
    die("Access denied.");
}

$users = $pdo->query("SELECT admin_id, username, full_name, role, email, status, created_at FROM admin_users ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff Accounts | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:grid; grid-template-columns: 260px 1fr; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
        .badge { padding:4px 8px; border-radius:999px; font-size:11px; font-weight:700; }
        .badge-admin { background:#dbeafe; color:#1d4ed8; }
        .badge-staff { background:#e5e7eb; color:#374151; }
        .badge-super { background:#fef3c7; color:#92400e; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <div class="card">
            <h1>Staff Accounts</h1>
            <p class="text-muted">Manage admin and staff user accounts for the ERP.</p>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <span class="badge badge-<?= $user['role'] === 'super_admin' ? 'super' : ($user['role'] === 'admin' ? 'admin' : 'staff') ?>"><?= htmlspecialchars($user['role']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($user['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
