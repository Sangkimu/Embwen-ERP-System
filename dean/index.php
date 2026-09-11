<?php
require_once "../config.php";
requireLogin();
if(!allowed(['dean'])){http_response_code(403);die("Access denied.");}

// 1. Core KPIs Stats array including a dynamic counter for Fully Occupied/Allocated Hostel Rooms
$stats=[
 ['Applications',$pdo->query('SELECT COUNT(*) FROM admissions')->fetchColumn()],
 ['Pending',$pdo->query("SELECT COUNT(*) FROM admissions WHERE admission_status='pending'")->fetchColumn()],
 ['Allocated',$pdo->query('SELECT COUNT(*) FROM class_allocations')->fetchColumn()],
 ['Open Cases',$pdo->query("SELECT COUNT(*) FROM student_welfare WHERE status='open'")->fetchColumn()],
 ['Hostel Occupants',$pdo->query("SELECT COUNT(*) FROM hostel_allocations WHERE status='allocated'")->fetchColumn()]
];

// 2. Fetch Pending Admissions queue 
$pending_admissions = $pdo->query("
    SELECT a.*, c.course_name 
    FROM admissions a 
    LEFT JOIN courses c ON c.course_id = a.course_id 
    WHERE a.admission_status = 'pending' 
    ORDER BY a.created_at DESC LIMIT 5
")->fetchAll();

// 3. Fetch Active Student Support/Welfare tickets
$active_welfare = $pdo->query("
    SELECT w.*, s.name AS student_name 
    FROM student_welfare w 
    JOIN students s ON s.student_id = w.student_id 
    WHERE w.status = 'open' 
    ORDER BY w.created_at DESC LIMIT 5
")->fetchAll();

// 4. NEW: Fetch Recent Hostel Accommodations Room Assignments 
$recent_hostels = $pdo->query("
    SELECT ha.*, s.name AS student_name 
    FROM hostel_allocations ha 
    JOIN students s ON s.student_id = ha.student_id 
    WHERE ha.status = 'allocated' 
    ORDER BY ha.allocated_at DESC LIMIT 5
")->fetchAll();

$services=moduleServices('dean',user()['role']);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dean Dashboard</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    .dean-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .dean-table th, .dean-table td { text-align: left; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.85rem; }
    .dean-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
    .badge-alert { background: #fef2f2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
    .badge-neutral { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }
    .badge-success { background: #e6f4ea; color: #137333; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Dean</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">DEAN MODULE</p><h1>Dean Dashboard</h1><p>Oversee polytechnic admissions, accommodations, and student support services.</p></div></div>

<!-- The stats wrapper dynamically scales to display your 5th card tracking element cleanly -->
<div class="stats">
    <?php foreach($stats as $s):?>
        <div class="stat"><span><?=htmlspecialchars($s[0])?></span><strong><?=htmlspecialchars($s[1])?></strong></div>
    <?php endforeach;?>
</div>

<div class="grid">
    <!-- Left Column: Dean's Administrative Workloads & Core Modules -->
    <section class="card">
        <h2>Pending Admission Applications</h2>
        <?php if(!empty($pending_admissions)): ?>
            <table class="dean-table">
                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Target Course</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_admissions as $adm): ?>
                        <tr>
                            <td><b><?=htmlspecialchars($adm['applicant_name'] ?? 'Unknown')?></b></td>
                            <td><?=htmlspecialchars($adm['course_name'] ?? 'Unassigned Module')?></td>
                            <td><a href="admissions/view.php?id=<?=$adm['admission_id']?>" class="badge-neutral" style="text-decoration:none;">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service"><small style="color:#666;">No pending admission verification requests requiring attention.</small></div>
        <?php endif; ?>

        <!-- NEW ACCOMMODATION TRACKING MATRIX PANEL -->
        <h2 style="margin-top: 35px;">Recent Hostel Room Allocations</h2>
        <?php if(!empty($recent_hostels)): ?>
            <table class="dean-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Hostel & Room</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_hostels as $room): ?>
                        <tr>
                            <td><b><?=htmlspecialchars($room['student_name'])?></b></td>
                            <td><?=htmlspecialchars($room['hostel_name'] ?? 'Main Block')?> — Room <?=htmlspecialchars($room['room_no'])?></td>
                            <td><span class="badge-success">Assigned</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service"><small style="color:#666;">No active room allocation files found for this term.</small></div>
        <?php endif; ?>

        <h2 style="margin-top: 35px;">Your Modules</h2>
        <?php foreach($services as $s):?>
            <a class="service" href="<?=htmlspecialchars($s['path'])?>"><div class="service-icon">◆</div><div><b><?=htmlspecialchars($s['label'])?></b><small><?=htmlspecialchars($s['description'])?></small></div></a>
        <?php endforeach;?>
    </section>

    <!-- Right Column: Student Welfare & Open Resolution Tracking -->
    <section class="card">
        <h2>Active Welfare & Discipline Cases</h2>
        <?php if(!empty($active_welfare)): ?>
            <table class="dean-table">
                <thead>
                    <tr>
                        <th>Student Case</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($active_welfare as $case): ?>
                        <tr>
                            <td>
                                <b><?=htmlspecialchars($case['student_name'])?></b>
                                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;"><?=htmlspecialchars(substr($case['description'] ?? '', 0, 45))?>...</div>
                            </td>
                            <td><span class="badge-alert"><?=htmlspecialchars(ucfirst($case['category'] ?? 'Welfare'))?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service"><small style="color:#666;">All student welfare logs and accommodation files are currently cleared.</small></div>
        <?php endif; ?>
    </section>
</div>

</section></main></div></body></html>
