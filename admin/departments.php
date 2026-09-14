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
$selectedDepartmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
$selectedDepartment = null;
if ($selectedDepartmentId) {
    foreach ($departments as $department) {
        if ((int)$department['department_id'] === (int)$selectedDepartmentId) {
            $selectedDepartment = $department;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Departments | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:block; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
        .registry-form { display:flex; gap:10px; margin-top:18px; }
        .registry-form input { flex:1; padding:10px; border:1px solid #cbd5e0; border-radius:6px; }
        .registry-form button { background:#1e3d73; color:#fff; border:0; border-radius:6px; padding:10px 16px; font-weight:700; }
        .clear-button { background:#e2e8f0 !important; color:#1e293b !important; }
        .registry-message { padding:10px 12px; background:#e6f4ea; color:#137333; border-radius:6px; margin-top:15px; }
        @media(max-width:700px){.sidebar{position:static;width:100%;min-height:auto;transform:none}.main{margin-left:0;width:100%}.content{padding:20px}.topbar{padding:0 18px}.registry-form{display:grid;grid-template-columns:1fr}}
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
            <form class="registry-form" method="post"><input name="department_name" placeholder="e.g. Electrical Engineering" required><button type="reset" class="clear-button">Clear</button><button type="submit">Add department</button></form>
            <div style="margin-top: 25px; margin-bottom: 15px;">
                <label for="departmentDirectorySelector" style="display:block; margin-bottom:8px; font-size: 14px; font-weight: 600; color: #4a5568;">Department directory</label>
                <select id="departmentDirectorySelector" style="width:100%; max-width:420px; padding:10px 12px; border:1px solid #cbd5e0; border-radius:6px; background:#fff; font-size:14px;" onchange="showDepartmentDetails(this)">
                    <option value="">Select a department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-created="<?= htmlspecialchars(date('d M Y', strtotime($department['created_at'])), ENT_QUOTES, 'UTF-8') ?>"
                            <?= $selectedDepartmentId && (int)$department['department_id'] === (int)$selectedDepartmentId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="departmentDirectoryDetail" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; color:#4a5568; min-height:70px; display:flex; align-items:center;">
                <?php if ($selectedDepartment): ?>
                    <strong><?= htmlspecialchars($selectedDepartment['department_name']) ?></strong><br>
                    Created: <?= htmlspecialchars(date('d M Y', strtotime($selectedDepartment['created_at']))) ?>
                <?php else: ?>
                    Select a department to view its registration date.
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function showDepartmentDetails(select) {
    const detailBox = document.getElementById('departmentDirectoryDetail');
    const selected = select.options[select.selectedIndex];

    if (!selected || !selected.value) {
        detailBox.innerHTML = 'Select a department to view its registration date.';
        return;
    }

    detailBox.innerHTML = '<strong>' + selected.value + '</strong><br>Created: ' + selected.dataset.created;
}

window.addEventListener('DOMContentLoaded', function () {
    const departmentSelect = document.getElementById('departmentDirectorySelector');
    if (departmentSelect && departmentSelect.value) {
        showDepartmentDetails(departmentSelect);
    }
});
</script>
</body>
</html>
