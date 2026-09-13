<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance'])){http_response_code(403);die("Access denied.");}

$message = '';
$messageClass = '';

// --- 1. CORE BULK INVOICING PROCESSING ROUTINE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'run_invoicing') {
    $academicYear = trim($_POST['academic_year'] ?? '');
    $semester     = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);
    $courseIdFilter = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT); // 0 or empty means ALL courses

    if (!empty($academicYear) && $semester) {
        try {
            $pdo->beginTransaction();

            // 1. Gather all active billing structure blueprints matching this academic cycle window
            $structureSql = "SELECT course_id, fee_structure_id, fee_type, amount 
                             FROM fee_structure 
                             WHERE academic_year = ? AND semester = ?";
            $structStmt = $pdo->prepare($structureSql);
            $structStmt->execute([$academicYear, $semester]);
            $blueprints = $structStmt->fetchAll();

            if (empty($blueprints)) {
                throw new Exception("Billing Engine Terminated: No fee structure configuration blueprints found matching Academic Year: '{$academicYear}' and Semester: {$semester}.");
            }

            // Organize blueprint structures into arrays grouped by course [course_id => [items]]
            $courseBlueprints = [];
            foreach ($blueprints as $bp) {
                $courseBlueprints[$bp['course_id']][] = $bp;
            }

            // 2. Fetch all targeted students marked as 'active' inside registration systems
            if ($courseIdFilter > 0) {
                $studentSql = "SELECT student_id, course_id, name, id_no FROM students WHERE status = 'active' AND course_id = ?";
                $studStmt = $pdo->prepare($studentSql);
                $studStmt->execute([$courseIdFilter]);
            } else {
                $studentSql = "SELECT student_id, course_id, name, id_no FROM students WHERE status = 'active'";
                $studStmt = $pdo->query($studentSql);
            }
            $activeStudents = $studStmt->fetchAll();

            if (empty($activeStudents)) {
                throw new Exception("Zero Targets: No active student enrollment records found matching the designated filter parameters.");
            }

            $invoicesCreatedCount = 0;
            $duplicateSkippedCount = 0;

            // Prepared lookup guard-rail to check for pre-existing fee assignments (prevents double-billing)
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id = ? AND fee_structure_id = ?");

            // Prepared insert line entry mock placeholder tracking mechanism
            // Note: Since original schema posts payments directly against structure rules, we record a $0 placeholder payment 
            // instance to safely flag that this specific block has already been assigned/billed to the target student ledger profile.
            $insertInvoiceStmt = $pdo->prepare("INSERT INTO fee_payments (student_id, fee_structure_id, amount_paid, payment_date, payment_method, receipt_no, reference_no, received_by) VALUES (?, ?, 0.00, CURDATE(), 'online', ?, 'INVOICE_GEN', ?)");

            $currentClerkId = user()['admin_id'] ?? user()['id'] ?? 1;

            // 3. Process each student through the invoicing engine loops
            foreach ($activeStudents as $student) {
                $sId = $student['student_id'];
                $cId = $student['course_id'];

                // Skip if this student's course has no fee structure configuration blueprint saved
                if (!isset($courseBlueprints[$cId])) continue;

                foreach ($courseBlueprints[$cId] as $item) {
                    $fsId = $item['fee_structure_id'];

                    // Verification Check: If this item structure was already pushed to the student, skip it securely
                    $checkStmt->execute([$sId, $fsId]);
                    if ($checkStmt->fetchColumn() > 0) {
                        $duplicateSkippedCount++;
                        continue;
                    }

                    // Generate a distinct invoice code key tracking index line item
                    $uniqueInvoiceReceiptNo = "INV-" . $sId . "-" . $fsId . "-" . rand(100, 999);

                    // Commit initialization billing placeholder records into transactional ledgers
                    $insertInvoiceStmt->execute([$sId, $fsId, $uniqueInvoiceReceiptNo, $currentClerkId]);
                    $invoicesCreatedCount++;
                }
            }

            $pdo->commit();
            $message = "Automated Billing Routine Concluded! Successfully deployed {$invoicesCreatedCount} new fee line assignments. (Skipped {$duplicateSkippedCount} pre-existing configurations).";
            $messageClass = "alert-success";

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Critical Billing Error: " . $e->getMessage();
            $messageClass = "alert-danger";
        }
    } else {
        $message = "Invoicing validation parameters failed. Ensure academic fields contain structured target profiles.";
        $messageClass = "alert-danger";
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Automated Invoicing Engine</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .engine-card { background: white; padding: 30px; border-radius: 8px; border: 1px solid #edf2f7; max-width: 650px; margin: 20px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: #f7fafc; }
        .form-control:focus { border-color: #1e3d73; outline: none; background: #fff; }
        .btn-run { background: #1e3d73; color: white; border: none; padding: 14px 28px; border-radius: 6px; font-weight: 700; font-size: 15px; cursor: pointer; transition: background 0.2s; width: 100%; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
        .btn-run:hover { background: #162e58; }
        .alert { padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; font-size: 14px; font-weight: 500; line-height: 1.5; }
        .alert-success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-danger { background: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        .warning-notice { background: #fffaf0; border-left: 4px solid #dd6b20; padding: 15px; border-radius: 4px; font-size: 13px; color: #7b341e; margin-bottom: 20px; line-height: 1.4; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / Finance / <b>Bulk Billing Engine</b></div>
            <div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div>
        </header>
        
        <section class="content">
            <div class="engine-card">
                <h2 style="margin-top:0; margin-bottom:6px; color:#1a202c;">Semester Invoicing Engine</h2>
                <p class="text-muted" style="font-size:13px; margin-bottom:24px;">Automatically generate billing requirements for student accounts using active fee structural rules.</p>
                
                <?php if (!empty($message)): ?>
                    <div class="alert <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="warning-notice">
                    <b>🛡️ Safe Billing Lock:</b> This engine running algorithm cross-references transaction codes prior to generation. Running this routine multiple times for the same cycle **will not** duplicate student balances or corrupt historic ledger tracking.
                </div>

                <form action="bulk_invoice.php" method="POST" onsubmit="return confirm('Are you sure you want to deploy bulk invoice statements to student accounts across the selected criteria? This operation will lock active records.');">
                    <input type="hidden" name="action" value="run_invoicing">
                    
                    <div class="form-group">
                        <label>Target Academic Year Cycle</label>
                        <input type="text" class="form-control" name="academic_year" placeholder="e.g., 2026/2027" required>
                    </div>

                    <div class="form-group">
                        <label>Target Semester Block</label>
                        <select class="form-control" name="semester" required>
                            <option value="1">Semester 1 Billing Cycle</option>
                            <option value="2">Semester 2 Billing Cycle</option>
                            <option value="3">Semester 3 Billing Cycle</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Course Enrollment Filter (Scope Constraint)</label>
                        <select class="form-control" name="course_id" required>
                            <option value="0">-- Apply Globally (Bill All Active Institutional Students) --</option>
                            <?php
