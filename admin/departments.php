<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance', 'dean'])) {
    http_response_code(403);
    die("Access denied.");
}

$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $departmentName=trim($_POST['department_name']??'');
    if($departmentName===''){$message='Enter a department name.';}
    else{try{$stmt=$pdo->prepare('INSERT INTO departments (department_name) VALUES (?)');$stmt->execute([$departmentName]);$message='Department added successfully.';}catch(PDOException $e){$message='That department already exists or could not be saved.';}}
}
$departments = $pdo->query("SELECT department_id, department_name, created_at FROM departments ORDER BY department_name")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Departments | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:grid; grid-template-columns: 260px 1fr; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
        .registry-form { display:flex; gap:10px; margin-top:18px; }
        .registry-form input { flex:1; padding:10px; border:1px solid #cbd5e0; border-radius:6px; }
        .registry-form button { background:#1e3d73; color:#fff; border:0; border-radius:6px; padding:10px 16px; font-weight:700; }
        .registry-message { padding:10px 12px; background:#e6f4ea; color:#137333; border-radius:6px; margin-top:15px; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Departments</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Departments</h1>
            <p class="text-muted">Academic departments registered in the system.</p>
            <?php if($message): ?><div class="registry-message"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <form class="registry-form" method="post"><input name="department_name" placeholder="e.g. Electrical Engineering" required><button type="submit">Add department</button></form>
            <table>
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?= htmlspecialchars($department['department_name']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($department['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
