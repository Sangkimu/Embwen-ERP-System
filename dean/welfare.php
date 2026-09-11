<?php
require_once "../config.php";
requireLogin();

// Enforce role access control matching your system requirements
if(!allowed(['dean', 'admin'])){
    http_response_code(403);
    die("Access denied. Executive clearance required.");
}

$message = "";

// 1. Handle Closing an Active Case
if (isset($_GET['action']) && $_GET['action'] === 'close' && isset($_GET['id'])) {
    try {
        $case_id = (int)$_GET['id'];
        $close_stmt = $pdo->prepare("UPDATE student_welfare SET status = 'closed' WHERE welfare_id = ?");
        $close_stmt->execute([$case_id]);
        $message = "<div class='alert-success'>✅ Welfare case status resolved and closed successfully.</div>";
    } catch (Exception $e) {
        $message = "<div class='alert-danger'>❌ Error updating case status: " . $e->getMessage() . "</div>";
    }
}

// 2. Handle Logging a New Ticket Case
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = trim($_POST['student_id'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($student_id) && !empty($category) && !empty($description)) {
        try {
            // Verify if the student exists in the main registry
            $check_stud = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $check_stud->execute([$student_id]);
            
            if ($check_stud->fetchColumn() > 0) {
                $insert_stmt = $pdo->prepare("INSERT INTO student_welfare (student_id, category, description, status) VALUES (?, ?, ?, 'open')");
                $insert_stmt->execute([$student_id, $category, $description]);
                
                $message = "<div class='alert-success'>✅ New welfare case logged successfully for Student ID: " . htmlspecialchars($student_id) . "</div>";
            } else {
                $message = "<div class='alert-danger'>❌ Error: Specified Student ID does not exist in the database registry.</div>";
            }
        } catch (Exception $e) {
            $message = "<div class='alert-danger'>❌ System failure logging welfare case: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert-danger'>❌ Please fulfill all input mapping parameters.</div>";
    }
}

// 3. Fetch all open and closed welfare cases to display on the dashboard matrix
$cases_list = $pdo->query("
    SELECT w.*, s.name AS student_name 
    FROM student_welfare w 
    JOIN students s ON s.student_id = w.student_id 
    ORDER BY w.status ASC, w.created_at DESC
")->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Student Welfare & Discipline</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        @media(max-width:768px){ .form-row { grid-template-columns: 1fr; } }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; background: #fff; }
        .btn-submit { background: #1e3a8a; color: #fff; padding: 11px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.9rem; }
        .welfare-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .welfare-table th, .welfare-table td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.88rem; }
        .welfare-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
        .badge-open { background: #fee2e2; color: #991b1b; padding: 3px 7px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
        .badge-closed { background: #e6f4ea; color: #137333; padding: 3px 7px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
        .action-link { color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.8rem; }
        .action-link:hover { text-decoration: underline; }
        .alert-success { background: #e6f4ea; border: 1px solid #34a853; color: #137333; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Welfare & Discipline</b></div></header>
        <section class="content">
            
            <div class="page-head">
                <div>
                    <p class="eyebrow">STUDENT AFFAIRS</p>
                    <h1>Welfare & Disciplinary Cases</h1>
                    <p>Log student support tickets, bursary/financial aid documentation updates, or track disciplinary parameters.</p>
                </div>
            </div>

            <?= $message ?>

            <!-- Log New Case Card Panel -->
            <section class="card">
                <h2>File New Case Report</h2>
                <form method="POST" action="welfare.php" style="margin-top: 15px;">
                    <div class="form-row">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">STUDENT ID / ADMISSION</label>
                            <input type="text" name="student_id" required class="form-control" placeholder="e.g., ENG/2026/004">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">CASE CATEGORY</label>
                            <select name="category" required class="form-control">
                                <option value="">-- Choose Category --</option>
                                <option value="Bursary & Financial Aid">Bursary & Financial Aid</option>
                                <option value="Medical / Counseling">Medical / Counseling</option>
                                <option value="Disciplinary Issue">Disciplinary Issue</option>
                                <option value="General Guidance">General Guidance</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">CASE DETAILS & NOTES</label>
                        <textarea name="description" required class="form-control" rows="3" placeholder="Enter continuous case file details here..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Log Case File</button>
                </form>
            </section>

            <!-- Case History Ledger -->
            <section class="card" style="margin-top: 20px;">
                <h2>Master Welfare & Disciplinary Registry</h2>
                <?php if(!empty($cases_list)): ?>
                    <table class="welfare-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Case Notes Summary</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cases_list as $case): ?>
                                <tr>
                                    <td><b><?= htmlspecialchars($case['student_id']) ?></b></td>
                                    <td><?= htmlspecialchars($case['student_name']) ?></td>
                                    <td><span style="font-weight: 500; color: #475569;"><?= htmlspecialchars($case['category']) ?></span></td>
                                    <td>
                                        <div style="max-width: 300px; color: #334155; font-size: 0.82rem; line-height: 1.4;">
                                            <?= htmlspecialchars($case['description']) ?>
                                        </div>
                                        <small style="color: #94a3b8;"><?= date('d-M-Y H:i', strtotime($case['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if($case['status'] === 'open'): ?>
                                            <span class="badge-open">Active / Open</span>
                                        <?php else: ?>
                                            <span class="badge-closed">Resolved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($case['status'] === 'open'): ?>
                                            <a href="welfare.php?action=close&id=<?= $case['welfare_id'] ?>" 
                                               class="action-link" 
                                               onclick="return confirm('Mark this case as resolved and closed?');">
                                               Mark Resolved
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:0.8rem;">None</span>
                                        <?php endif; ?>
                                    </td>
