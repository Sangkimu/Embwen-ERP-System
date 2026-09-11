<?php
require_once "../config.php";
requireLogin();

if(!allowed(['students'])){
    http_response_code(403);
    die("Access denied.");
}

// 1. Fetch Student Details and Course Information
$st=$pdo->prepare("SELECT s.*,c.course_code,c.course_name FROM students s JOIN student_accounts sa ON sa.student_id=s.student_id JOIN courses c ON c.course_id=s.course_id WHERE sa.username=? LIMIT 1");
$st->execute([user()['username']]);
$student=$st->fetch();

$payments=0;
$outstanding_balance = 0;
$course_units = [];
$active_sem_name = "No Active Semester";

if($student){
    // 2. Fetch Active Semester Configuration
    $sem_stmt = $pdo->query("SELECT semester_id, semester_name FROM semesters WHERE is_active = 1 LIMIT 1");
    $active_semester = $sem_stmt->fetch();
    $current_sem_id = $active_semester['semester_id'] ?? 0;
    if($active_semester) {
        $active_sem_name = $active_semester['semester_name'];
    }

    // 3. Fetch Financial Data for the Current Active Semester
    $fee_stmt = $pdo->prepare("
        SELECT current_fee_charged, fee_paid, (current_fee_charged - fee_paid) AS outstanding_balance 
        FROM enrollments 
        WHERE student_id = ? AND semester_id = ?
    ");
    $fee_stmt->execute([$student['student_id'], $current_sem_id]);
    $finances = $fee_stmt->fetch();
    
    // Set variables to populate your stats counters cleanly
    $payments = $finances['fee_paid'] ?? 0;
    $outstanding_balance = $finances['outstanding_balance'] ?? 0;

    // 4. Fetch Polytechnic Course Units (Theory CAT vs Workshop Practical Marks)
    $marks_stmt = $pdo->prepare("
        SELECT unit_code_name, cat_mark, practical_mark, exam_mark 
        FROM course_marks 
        WHERE student_id = ? AND semester_id = ?
    ");
    $marks_stmt->execute([$student['student_id'], $current_sem_id]);
    $course_units = $marks_stmt->fetchAll();
}

// 5. Retain your original system variables
$notices=$pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience IN ('all','students')")->fetchColumn();
$services=moduleServices('students',user()['role']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Extra inline styles specifically targeting the table alignment inside your utility layout -->
    <style>
        .poly-table { width: 100%; border-collapse: collapse; margin-top: 10px; text-align: left; }
        .poly-table th, .poly-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        .poly-table th { background: rgba(0,0,0,0.02); color: #555; }
        .balance-badge { background: #fef2f2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .btn-mpesa { display: block; text-align: center; background: #22c55e; color: #fff; padding: 10px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-top: 15px; border: none; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php";?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Student Portal</b></div>
            <div class="top-user">
                <div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div>
                <?=htmlspecialchars(user()['name'])?>
            </div>
        </header>
        
        <section class="content">
            <div class="page-head">
                <div>
                    <p class="eyebrow">POLYTECHNIC PORTAL — <?=htmlspecialchars($active_sem_name)?></p>
                    <h1>Welcome, <?=htmlspecialchars(user()['name'])?></h1>
                    <p>Access your modular academic records and semester statements.</p>
                </div>
            </div>
            
            <!-- Kept your exact stats wrapper, replacing broad counters with explicit polytechnic KPIs -->
            <div class="stats">
                <div class="stat"><span>Student ID</span><strong><?=htmlspecialchars($student['id_no']??'N/A')?></strong></div>
                <div class="stat"><span>Course Code</span><strong><?=htmlspecialchars($student['course_code']??'N/A')?></strong></div>
                <div class="stat"><span>Fees Paid (Sem)</span><strong><?=money($payments)?></strong></div>
                <div class="stat"><span>Pending Balance</span><strong class="text-danger"><?=money($outstanding_balance)?></strong></div>
            </div>
            
            <div class="grid">
                <!-- Quadrant 1: Active Semester Continuous Assessment & Practicals -->
                <section class="card">
                    <h2>Coursework & Practical Marksheet</h2>
                    <?php if (!empty($course_units)): ?>
                        <table class="poly-table">
                            <thead>
                                <tr>
                                    <th>Unit / Skill Module</th>
                                    <th>Theory CAT (30)</th>
                                    <th>Workshop Prac (30)</th>
                                    <th>Final Exam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($course_units as $unit): ?>
                                    <tr>
                                        <td><b><?= htmlspecialchars($unit['unit_code_name']) ?></b></td>
                                        <td><?= $unit['cat_mark'] !== null ? $unit['cat_mark'] : '-' ?></td>
                                        <td><?= $unit['practical_mark'] !== null ? $unit['practical_mark'] : '-' ?></td>
                                        <td><?= $unit['exam_mark'] !== null ? $unit['exam_mark'] : 'Pending' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="service"><small>No trade modules registered or recorded for this semester yet.</small></div>
                    <?php endif; ?>
                </section>
                
                <!-- Quadrant 2: Operational Financial & Attachment Status Options -->
                <section class="card">
                    <h2>Semester Actions</h2>
                    <div class="service">
                        <div>
                            <b><?=htmlspecialchars($student['name']??user()['name'])?></b>
                            <small><?=htmlspecialchars($student['course_name']??'Course info unavailable')?></small>
                        </div>
                    </div>
                    
                    <div class="service">
                        <div>
                            <b>Outstanding Balance</b>
                            <small class="balance-badge"><?=money($outstanding_balance)?></small>
                        </div>
                    </div>

                    <?php if ($outstanding_balance > 0): ?>
                        <!-- Form trigger target that routes directly to your payment module -->
                        <button class="btn-mpesa" onclick="location.href='payments/mpesa_trigger.php'">Pay via Lipa Na M-Pesa</button>
                    <?php endif; ?>

                    <h2 style="margin-top:25px;">Core Services</h2>
                    <?php foreach($services as $s):?>
                        <a class="service" href="<?=htmlspecialchars($s['path'])?>">
                            <div class="service-icon">◆</div>
                            <div>
                                <b><?=htmlspecialchars($s['label'])?></b>
                                <small><?=htmlspecialchars($s['description'])?></small>
                            </div>
                        </a>
                    <?php endforeach;?>
                </section>
            </div>
        </section>
    </main>
</div>
</body>
</html>
