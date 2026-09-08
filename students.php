<?php
require_once "config.php";
requireLogin();
if(!allowed(['admin','dean'])){http_response_code(403);die("Access denied.");}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt=$pdo->prepare("INSERT INTO students(id_no,name,course_id,status) VALUES(?,?,?,?)");
    $stmt->execute([trim($_POST['id_no']),trim($_POST['name']),$_POST['course_id'],$_POST['status']]);
    header("Location: students.php?added=1"); exit;
}
$courses=$pdo->query("SELECT course_id,course_code,course_name FROM courses WHERE status='active' ORDER BY course_name")->fetchAll();
$students=$pdo->query("SELECT s.*,c.course_code,c.course_name FROM students s JOIN courses c ON c.course_id=s.course_id ORDER BY s.student_id DESC")->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Students | College ERP</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="app"><aside class="sidebar"><?php include "partials/sidebar.php"; ?></aside><main class="main"><header class="topbar"><button class="menu">☰</button><div class="search">⌕ <input placeholder="Search students..."></div><div class="top-actions"><div class="avatar">AD</div><div><strong>Administrator</strong><small>Super Admin</small></div></div></header>
<section class="content"><div class="page-head"><div><p class="eyebrow">STUDENT MANAGEMENT</p><h1>Students</h1><p>Manage enrolled students and their course assignments.</p></div><button class="primary" onclick="openModal()">＋ Add Student</button></div>
<?php if(isset($_GET['added'])): ?><div class="alert success">Student added successfully.</div><?php endif; ?>
<section class="card"><div class="toolbar"><div class="searchbox">⌕ <input id="studentSearch" onkeyup="filterTable('studentSearch','studentTable')" placeholder="Search by name, ID or course"></div><select><option>All statuses</option><option>Active</option><option>Graduated</option><option>Withdrawn</option></select></div>
<div class="table-wrap"><table id="studentTable"><thead><tr><th>ID No.</th><th>Student</th><th>Course</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>
<?php foreach($students as $s): ?><tr><td><b><?= htmlspecialchars($s['id_no']) ?></b></td><td><?= htmlspecialchars($s['name']) ?></td><td><b><?= htmlspecialchars($s['course_code']) ?></b><small><?= htmlspecialchars($s['course_name']) ?></small></td><td><span class="status <?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td><td><?= date('d M Y',strtotime($s['created_at'])) ?></td><td>⋮</td></tr><?php endforeach; ?>
<?php if(!$students): ?><tr><td colspan="6" class="empty">No students found.</td></tr><?php endif; ?></tbody></table></div></section>
</section></main></div>
<div class="modal" id="studentModal"><div class="modal-box"><div class="modal-head"><h2>Add Student</h2><button onclick="closeModal()">×</button></div><form method="post"><label>Student ID Number<input name="id_no" required></label><label>Full Name<input name="name" required></label><label>Course<select name="course_id" required><option value="">Select course</option><?php foreach($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_name']) ?></option><?php endforeach; ?></select></label><label>Status<select name="status"><option value="active">Active</option><option value="graduated">Graduated</option><option value="withdrawn">Withdrawn</option></select></label><div class="modal-actions"><button type="button" class="secondary" onclick="closeModal()">Cancel</button><button class="primary">Save Student</button></div></form></div></div><script src="assets/js/app.js"></script></body></html>