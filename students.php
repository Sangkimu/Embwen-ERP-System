<?php
require_once "config.php";
requireLogin();
if(!allowed(['admin','dean'])){http_response_code(403);die("Access denied.");}
$dashboardLink = user()['home'] ?? 'index.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST['action'] ?? '') === 'delete_student') {
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        if (!$studentId) {
            header("Location: students.php?delete_error=Invalid+student+record"); exit;
        }
        try {
            $paymentCheck = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id=?");
            $paymentCheck->execute([$studentId]);
            $paymentCount = (int)$paymentCheck->fetchColumn();
            $mpesaCount = 0;
            if (tableExists($pdo, 'mpesa_transactions')) {
                $mpesaCheck = $pdo->prepare("SELECT COUNT(*) FROM mpesa_transactions WHERE student_id=?");
                $mpesaCheck->execute([$studentId]);
                $mpesaCount = (int)$mpesaCheck->fetchColumn();
            }
            if ($paymentCount > 0 || $mpesaCount > 0) {
                header("Location: students.php?delete_error=" . rawurlencode('This student has financial transactions and cannot be deleted.')); exit;
            }
            $delete = $pdo->prepare("DELETE FROM students WHERE student_id=?");
            $delete->execute([$studentId]);
            header("Location: students.php?deleted=1"); exit;
        } catch (PDOException $e) {
            header("Location: students.php?delete_error=" . rawurlencode('Student could not be deleted because linked records still exist.')); exit;
        }
    }
    $idNo = trim((string)($_POST['id_no'] ?? ''));
    if ($idNo === '') {
        $idNo = generateStudentIdNumber($pdo, isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null);
    }
    $nationalId = trim((string)($_POST['national_id'] ?? ''));
    $birthCertificateNo = trim((string)($_POST['birth_certificate_no'] ?? ''));
    if ((!validIdentityNumber($nationalId, 'national_id') && !validBirthCertificateNumber($birthCertificateNo)) || !validPhoneNumber($_POST['phone'] ?? '') || !validPhoneNumber($_POST['parent_phone'] ?? '')) {
        header("Location: students.php?delete_error=" . rawurlencode('Student and parent phone numbers must contain exactly 10 digits.')); exit;
    }
    $courseId = (int)($_POST['course_id'] ?? 0);
    $courseStmt = $pdo->prepare('SELECT duration_months FROM courses WHERE course_id=? AND status=\'active\' LIMIT 1');
    $courseStmt->execute([$courseId]);
    $durationMonths = (int)$courseStmt->fetchColumn();
    if (!$durationMonths) { header("Location: students.php?delete_error=" . rawurlencode('Select a valid active course.')); exit; }
    $studyStartDate = date('Y-m-d');
    $studyEndDate = (new DateTime($studyStartDate))->modify('+' . $durationMonths . ' months')->format('Y-m-d');
    $stmt=$pdo->prepare("INSERT INTO students(id_no,name,national_id,birth_certificate_no,phone,parent_name,parent_phone,previous_academic_level,year_of_completion,course_id,study_start_date,expected_end_date,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$idNo, trim($_POST['name']), $nationalId !== '' ? $nationalId : null, $birthCertificateNo !== '' ? $birthCertificateNo : null, trim($_POST['phone']), trim($_POST['parent_name']), trim($_POST['parent_phone']), trim($_POST['previous_academic_level']), $_POST['year_of_completion'] !== '' ? (int)$_POST['year_of_completion'] : null, $courseId, $studyStartDate, $studyEndDate, $_POST['status']]);
    <div class="modal" id="studentModal"><div class="modal-box"><div class="modal-head"><h2>Add Student</h2><button type="button" onclick="closeModal()">×</button></div><form method="post"><label>Admission / Student ID<input name="id_no" placeholder="Leave blank to auto-generate"></label><label>Full Name<input name="name" required></label><label>National ID <small>(optional if Birth Certificate is provided)</small><input name="national_id" inputmode="numeric" pattern="\d{1,8}" maxlength="8" placeholder="Maximum 8 digits"></label><label>Birth Certificate No. <small>(required if no National ID)</small><input name="birth_certificate_no" inputmode="numeric" pattern="\d{1,30}" maxlength="30" placeholder="Digits only"></label><label>Student Phone<input name="phone" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" placeholder="10 digits e.g. 0712345678" required></label><label>Parent/Guardian Name<input name="parent_name" required></label>
    header("Location: students.php?added=1"); exit;
}
$courses=$pdo->query("SELECT course_id,course_code,course_name FROM courses WHERE status='active' ORDER BY course_name")->fetchAll();
$students=$pdo->query("SELECT s.*,c.course_code,c.course_name,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id ORDER BY s.student_id DESC")->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>"><title>Students | College ERP</title><link rel="stylesheet" href="assets/css/style.css"><style>
.modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.45);padding:20px;z-index:1000}.modal.show,.modal.active{display:flex}.modal-box{background:#fff;border-radius:12px;max-width:560px;width:100%;box-shadow:0 20px 45px rgba(15,23,42,.25);overflow:hidden}.modal-head{display:flex;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid #e2e8f0}.modal-head button{border:0;background:transparent;font-size:22px;cursor:pointer;color:#475569}.modal-box form{padding:20px;display:grid;gap:14px}.modal-box label{font-weight:700;color:#334155;font-size:13px}.modal-box input,.modal-box select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cbd5e1;border-radius:6px;margin-top:6px}.modal-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:8px}.primary,.secondary{border:0;border-radius:6px;padding:10px 16px;font-weight:700;cursor:pointer}.primary{background:#1e3d73;color:#fff}.secondary{background:#e2e8f0;color:#1e293b}
</style></head><body>
<div class="app"><aside class="sidebar"><?php include "partials/sidebar.php"; ?></aside><main class="main"><header class="topbar"><button class="menu">☰</button><div class="search">⌕ <input placeholder="Search students..."></div><div class="top-actions"><div class="avatar"><?= strtoupper(substr(user()['name'] ?? 'ERP', 0, 2)) ?></div><div><strong><?= htmlspecialchars(user()['name'] ?? 'ERP User') ?></strong><small><?= htmlspecialchars(ucfirst((user()['role'] ?? 'admin'))) ?></small></div></div></header>
<section class="content"><div class="page-head"><div><a href="<?=htmlspecialchars($dashboardLink)?>" style="display:inline-block;margin-bottom:12px;color:#174a9b;font-size:13px;font-weight:700;">← Back to dashboard</a><p class="eyebrow">STUDENT MANAGEMENT</p><h1>Student Registry</h1><p>Manage enrolled students and their course assignments.</p></div><button class="primary" onclick="openModal()">＋ Add Student</button></div>
<?php if(isset($_GET['added'])): ?><div class="alert success">Student added successfully.</div><?php endif; ?>
<?php if(isset($_GET['deleted'])): ?><div class="alert success">Student removed from the database.</div><?php endif; ?>
<?php if(isset($_GET['delete_error'])): ?><div class="alert danger"><?=htmlspecialchars($_GET['delete_error'])?></div><?php endif; ?>
<section class="card"><div class="toolbar"><div class="searchbox">⌕ <input id="studentSearch" onkeyup="filterTable('studentSearch','studentTable')" placeholder="Search by name, ID or course"></div><select><option>All statuses</option><option>Active</option><option>Graduated</option><option>Withdrawn</option></select></div>
<div class="table-wrap"><table id="studentTable"><thead><tr><th>ID No.</th><th>Student</th><th>Phone</th><th>Parent/Guardian</th><th>Parent Phone</th><th>Previous Academic Level</th><th>Completion Year</th><th>Department</th><th>Course</th><th>Study Start</th><th>Expected End</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>
<?php foreach($students as $s): ?><tr><td><b><?= htmlspecialchars($s['id_no']) ?></b></td><td><?= htmlspecialchars($s['name']) ?></td><td><?= htmlspecialchars($s['phone'] ?? 'Not provided') ?></td><td><?= htmlspecialchars($s['parent_name'] ?? 'Not provided') ?></td><td><?= htmlspecialchars($s['parent_phone'] ?? 'Not provided') ?></td><td><?= htmlspecialchars($s['previous_academic_level'] ?? 'Not provided') ?></td><td><?= htmlspecialchars($s['year_of_completion'] ?? 'Not provided') ?></td><td><?= htmlspecialchars($s['department_name'] ?? 'Pending') ?></td><td><b><?= htmlspecialchars($s['course_code'] ?? 'Pending') ?></b><small><?= htmlspecialchars($s['course_name'] ?? 'Course not assigned') ?></small></td><td><?= htmlspecialchars($s['study_start_date'] ?? 'Not set') ?></td><td><?= htmlspecialchars($s['expected_end_date'] ?? 'Not set') ?></td><td><span class="status <?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td><td><?= date('d M Y',strtotime($s['created_at'])) ?></td><td><form method="post" onsubmit="return confirm('Remove this student from the database? This cannot be undone.');"><input type="hidden" name="action" value="delete_student"><input type="hidden" name="student_id" value="<?= (int)$s['student_id'] ?>"><button type="submit" title="Remove student" style="border:0;background:transparent;color:#bd4242;font-weight:700;font-size:12px;cursor:pointer;padding:6px 0">Remove</button></form></td></tr><?php endforeach; ?>
<?php if(!$students): ?><tr><td colspan="14" class="empty">No students found.</td></tr><?php endif; ?></tbody></table></div></section>
</section></main></div>
<div class="modal" id="studentModal"><div class="modal-box"><div class="modal-head"><h2>Add Student</h2><button type="button" onclick="closeModal()">×</button></div><form method="post"><label>Student ID Number<input name="id_no" placeholder="Leave blank to auto-generate"></label><label>Full Name<input name="name" required></label><label>Student Phone<input name="phone" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" placeholder="10 digits e.g. 0712345678" required></label><label>Parent/Guardian Name<input name="parent_name" required></label><label>Parent/Guardian Phone<input name="parent_phone" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" placeholder="10 digits e.g. 0712345678" required></label><label>Previous Academic Level<input name="previous_academic_level" placeholder="e.g. KCSE, Craft Certificate"></label><label>Year of Completion<input name="year_of_completion" type="number" min="1950" max="2100" placeholder="e.g. 2025"></label><label>Course<select name="course_id" required><option value="">Select course</option><?php foreach($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_name']) ?></option><?php endforeach; ?></select></label><label>Status<select name="status"><option value="active">Active</option><option value="graduated">Graduated</option><option value="withdrawn">Withdrawn</option></select></label><div class="modal-actions"><button type="button" class="secondary" onclick="closeModal()">Cancel</button><button type="submit" class="primary">Save Student</button></div></form></div></div><script src="assets/js/app.js"></script></body></html>