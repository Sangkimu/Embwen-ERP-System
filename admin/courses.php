<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance'])) {
    http_response_code(403);
    die("Access denied.");
}

$courses = $pdo->query("SELECT c.course_id, c.course_code, c.course_name, d.department_name, c.status FROM courses c LEFT JOIN departments d ON d.department_id = c.department_id ORDER BY c.course_name")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Courses | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:grid; grid-template-columns: 260px 1fr; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Courses</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Courses</h1>
            <p class="text-muted">Academic courses available in the college registry.</p>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['course_code']) ?></td>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['department_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($course['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
