<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);die("Access denied.");}

// 1. Fetch Student Details
$st=$pdo->prepare("SELECT s.*,c.course_code,c.course_name FROM students s JOIN student_accounts sa ON sa.student_id=s.student_id JOIN courses c ON c.course_id=s.course_id WHERE sa.username=? LIMIT 1");
$st->execute([user()['username']]);$student=$st->fetch();

$course_units = [];
$active_sem_name = "No Active Semester";
$completed_modules = 0;
$pending_modules = 0;

if($student) {
    // 2. Fetch current active semester details
    $sem_stmt = $pdo->query("SELECT semester_id, semester_name FROM semesters WHERE is_active = 1 LIMIT 1");
    $active_semester = $sem_stmt->fetch();
    $current_sem_id = $active_semester['semester_id'] ?? 0;
    $active_sem_name = $active_semester['semester_name'] ?? 'Active Semester';

    // 3. Fetch specific continuous assessment & workshop modular marks for this term
    $marks_stmt = $pdo->prepare("SELECT unit_code_name, cat_mark, practical_mark, exam_mark FROM course_marks WHERE student_id = ? AND semester_id = ?");
    $marks_stmt->execute([$student['student_id'], $current_sem_id]);
    $course_units = $marks_stmt->fetchAll();

    // 4. Calculate dynamic summary counts for the metrics grid
    foreach($course_units as $unit) {
        if($unit['exam_mark'] !== null) {
            $completed_modules++;
        } else {
            $pending_modules++;
        }
    }
}

$notices=$pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience IN ('all','students')")->fetchColumn();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Coursework & Marks</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    .poly-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .poly-table th, .poly-table td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.88rem; }
    .poly-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; display: inline-block; }
    .badge-success { background: #e6f4ea; color: #137333; }
    .badge-warning { background: #fef7e0; color: #b06000; }
    .print-bar { margin-top: 20px; display: flex; justify-content: flex-end; }
    .btn-print { background: #475569; color: #fff; border: none; padding: 10px 15px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; font-weight: bold; }
    @media print { body * { visibility: hidden; } .printable-area, .printable-area * { visibility: visible; } .printable-area { position: absolute; left: 0; top: 0; width: 100%; } .print-bar, .sidebar, .topbar { display: none !important; } }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Coursework & Marks</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">ACADEMIC LEDGER</p><h1>Modular Coursework & Progress</h1><p>Track your trade skills assessments, workshop exercises, and final terminal marks.</p></div></div>

<div class="stats">
    <div class="stat"><span>Active Semester</span><strong><?=htmlspecialchars($active_sem_name)?></strong></div>
    <div class="stat"><span>Registered Units</span><strong><?=count($course_units)?></strong></div>
    <div class="stat"><span>Graded Modules</span><strong style="color:#15803d;"><?=$completed_modules?></strong></div>
    <div class="stat"><span>Pending Final Exam</span><strong style="color:#b06000;"><?=$pending_modules?></strong></div>
</div>

<div class="grid" style="grid-template-columns: 1fr;">
    <section class="card printable-area">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid rgba(0,0,0,0.04); padding-bottom: 10px;">
            <h2>Semester Marksheet Report</h2>
            <span style="font-size:0.85rem; color:#666;">Course: <b><?=htmlspecialchars($student['course_code'] ?? 'N/A')?></b></span>
        </div>
        
        <?php if(!empty($course_units)): ?>
            <table class="poly-table">
                <thead>
                    <tr>
                        <th>Unit Code & Skill Description</th>
                        <th>Theory CAT (Max 30)</th>
                        <th>Workshop Practicals (Max 30)</th>
                        <th>Final Examination</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($course_units as $unit): ?>
                        <tr>
                            <td><b><?=htmlspecialchars($unit['unit_code_name'])?></b></td>
                            <td><?=$unit['cat_mark'] !== null ? $unit['cat_mark'] : '-'?></td>
                            <td><?=$unit['practical_mark'] !== null ? $unit['practical_mark'] : '-'?></td>
                            <td><b><?=$unit['exam_mark'] !== null ? $unit['exam_mark'] : '-'?></b></td>
                            <td>
                                <?php if($unit['exam_mark'] !== null): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Ongoing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service" style="margin-top:20px;"><div style="color:#666; font-size:0.85rem;">No registered trade units or coursework marks found for this specific semester.</div></div>
        <?php endif; ?>

        <div class="print-bar">
            <button class="btn-print" onclick="window.print()">Print Term Marksheet</button>
        </div>
    </section>
</div>

</section></main></div></body></html>
