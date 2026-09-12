<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);die("Access denied.");}

// Fetch Student Profile Meta-data
$student=currentStudent($pdo);
requireCompleteStudentProfile($student);

$notices=$pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience IN ('all','students')")->fetchColumn();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Profile</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 15px; }
    @media(max-width: 768px){ .profile-grid { grid-template-columns: 1fr; } }
    .avatar-large { width: 100px; height: 100px; background: #1e3a8a; color: #fff; display: flex; justify-content: center; align-items: center; font-size: 2.5rem; font-weight: bold; border-radius: 50%; margin: 0 auto 15px; }
    .meta-list { list-style: none; padding: 0; margin: 0; }
    .meta-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.9rem; }
    .meta-item span { color: #64748b; font-weight: 500; }
    .meta-item strong { color: #1e293b; }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>My Profile</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">REGISTRATION REGISTRY</p><h1>Student Profile Information</h1><p>Verified institutional details on record with the Academic Registrar.</p></div></div>

<div class="profile-grid">
    <!-- Left Profile Summary Card -->
    <section class="card" style="text-align: center;">
        <div class="avatar-large"><?=strtoupper(substr(user()['name'],0,2))?></div>
        <h2 style="margin-bottom:5px;"><?=htmlspecialchars($student['name']??user()['name'])?></h2>
        <p style="color:#64748b; font-size:0.85rem; margin:0 0 15px 0;">Status: <b style="color:#15803d;"><?=htmlspecialchars(strtoupper($student['status']??'ACTIVE'))?></b></p>
        <div class="service" style="text-align:left;">
            <div><b>Course Registered:</b><small><?=htmlspecialchars($student['course_name']??'N/A')?><?=!empty($student['duration_months'])?' · '.htmlspecialchars($student['duration_months']).' months':''?></small></div>
        </div>
    </section>

    <!-- Right Profile Metadata Fields -->
    <section class="card">
        <h2>Official Verification Metrics</h2>
        <ul class="meta-list">
            <li class="meta-item"><span>Student Admission Number</span><strong><?=htmlspecialchars($student['student_id']??'N/A')?></strong></li>
            <li class="meta-item"><span>National ID / Passport No.</span><strong><?=htmlspecialchars($student['id_no']??'N/A')?></strong></li>
            <li class="meta-item"><span>Course Reference Code</span><strong><?=htmlspecialchars($student['course_code']??'N/A')?></strong></li>
            <li class="meta-item"><span>Registered Email Address</span><strong><?=htmlspecialchars($student['email']??'N/A')?></strong></li>
            <li class="meta-item"><span>Primary Phone Contact</span><strong><?=htmlspecialchars($student['phone']??'N/A')?></strong></li>
            <li class="meta-item"><span>Date of Admission</span><strong><?=isset($student['created_at']) ? date('d-M-Y', strtotime($student['created_at'])) : 'N/A'?></strong></li>
        </ul>
    </section>
</div>

</section></main></div></body></html>
