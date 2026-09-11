<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);die("Access denied.");}
$st=$pdo->prepare("SELECT s.*,c.course_code,c.course_name FROM students s JOIN student_accounts sa ON sa.student_id=s.student_id JOIN courses c ON c.course_id=s.course_id WHERE sa.username=? LIMIT 1");
$st->execute([user()['username']]);$student=$st->fetch();

// --- MANDATORY PROFILE COMPLETION GATEWAY CHECK ---
if ($student) {
    if (empty($student['id_no']) || empty($student['phone']) || empty($student['email']) || empty($student['gender'])) {
        header("Location: complete_profile.php?required=1");
        exit;
    }
}
// --- END PROFILE COMPLETION GATEWAY CHECK ---

$payments=0;
if($student){$q=$pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments WHERE student_id=?");$q->execute([$student['student_id']]);$payments=$q->fetchColumn();}
$notices=$pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience IN ('all','students')")->fetchColumn();
$services=moduleServices('students',user()['role']);

// --- NEW POLYTECHNIC CORE LOGIC ---
$course_units = [];
$outstanding_balance = 0;
$active_sem_name = "No Active Semester";

if($student) {
    // 1. Fetch current active semester details
    $sem_stmt = $pdo->query("SELECT semester_id, semester_name FROM semesters WHERE is_active = 1 LIMIT 1");
    $active_semester = $sem_stmt->fetch();
    $current_sem_id = $active_semester['semester_id'] ?? 0;
    $active_sem_name = $active_semester['semester_name'] ?? 'Active Semester';

    // 2. Fetch specific continuous assessment & workshop modular marks for this term
    $marks_stmt = $pdo->prepare("SELECT unit_code_name, cat_mark, practical_mark, exam_mark FROM course_marks WHERE student_id = ? AND semester_id = ?");
    $marks_stmt->execute([$student['student_id'], $current_sem_id]);
    $course_units = $marks_stmt->fetchAll();

    // 3. Fetch this specific semester's fee invoice requirements
    $fee_stmt = $pdo->prepare("SELECT current_fee_charged FROM enrollments WHERE student_id = ? AND semester_id = ? LIMIT 1");
    $fee_stmt->execute([$student['student_id'], $current_sem_id]);
    $current_invoice = $fee_stmt->fetchColumn() ?: 0;
    
    // Calculate remaining balance dynamically
    $outstanding_balance = $current_invoice - $payments;
}
// --- END POLYTECHNIC CORE LOGIC ---
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Portal</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    /* Styling constraints configured cleanly to ride on top of your master css sheet rules */
    .poly-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .poly-table th, .poly-table td { text-align: left; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.85rem; }
    .poly-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
    .balance-highlight { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin-top: 10px; }
    .balance-highlight div { font-size: 1.4rem; font-weight: bold; color: #b91c1c; margin-top: 2px; }
    .btn-pay { display: block; text-align: center; background: #22c55e; color: #fff; padding: 10px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-top: 15px; font-size: 0.9rem; }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Student Portal</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">STUDENT PORTAL — <?=htmlspecialchars($active_sem_name)?></p><h1>Welcome, <?=htmlspecialchars(user()['name'])?></h1><p>Access your student services.</p></div></div><div class="stats"><div class="stat"><span>Student ID</span><strong><?=htmlspecialchars($student['id_no']??'N/A')?></strong></div><div class="stat"><span>Course</span><strong><?=htmlspecialchars($student['course_code']??'N/A')?></strong></div><div class="stat"><span>Payment Total</span><strong><?=money($payments)?></strong></div><div class="stat"><span>Notices</span><strong><?=htmlspecialchars($notices)?></strong></div></div>

<span id="notices"></span>
<div class="grid">
    <!-- Left Column: Lean Polytechnic Academics and Hands-on Workshop Tracking -->
    <section class="card" id="coursework">
        <h2>Semester Coursework & Practicals</h2>
        <?php if(!empty($course_units)): ?>
            <table class="poly-table">
                <thead>
                    <tr>
                        <th>Unit / Skill Module</th>
                        <th>Theory CAT (30)</th>
                        <th>Workshop Prac (30)</th>
                        <th>Exam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($course_units as $unit): ?>
                        <tr>
                            <td><b><?=htmlspecialchars($unit['unit_code_name'])?></b></td>
                            <td><?=$unit['cat_mark'] !== null ? $unit['cat_mark'] : '-'?></td>
                            <td><?=$unit['practical_mark'] !== null ? $unit['practical_mark'] : '-'?></td>
                            <td><?=$unit['exam_mark'] !== null ? $unit['exam_mark'] : 'Pending'?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service"><div style="color:#666; font-size:0.85rem;">No workshop modules or assessment grades posted for this semester yet.</div></div>
        <?php endif; ?>
        
        <h2 style="margin-top: 30px;">Your Modules</h2>
        <?php foreach($services as $s):?>
            <a class="service" href="<?=htmlspecialchars($s['path'])?>"><div class="service-icon">◆</div><div><b><?=htmlspecialchars($s['label'])?></b><small><?=htmlspecialchars($s['description'])?></small></div></a>
        <?php endforeach;?>
    </section>

    <!-- Right Column: Account Meta details and Semester Statements -->
    <section class="card" id="fee-balance">
        <h2>Semester Fee Reconciliation</h2>
        <div class="balance-highlight">
            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; color: #991b1b;">Outstanding Balance</span>
            <div><?=money($outstanding_balance)?></div>
        </div>
        <?php if($outstanding_balance > 0): ?>
            <a href="payments/mpesa_trigger.php" class="btn-pay">Pay Fees via M-Pesa</a>
        <?php endif; ?>

        <h2 id="account-information" style="margin-top: 30px;">Account Information</h2>
        <div class="service"><div><b><?=htmlspecialchars($student['name']??user()['name'])?></b><small><?=htmlspecialchars($student['course_name']??'Course information unavailable')?></small></div></div>
        <div class="service"><div><b>Status</b><small><?=htmlspecialchars(ucfirst($student['status']??'Unknown'))?></small></div></div>
    </section>
</div>

</section></main></div></body></html>
