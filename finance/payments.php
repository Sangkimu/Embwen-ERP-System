<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}

$message = '';
$messageClass = '';

// --- 1. CORE PAYMENT RECEIPT POSTING ENGINE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $studentId     = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $structureId   = filter_input(INPUT_POST, 'fee_structure_id', FILTER_VALIDATE_INT);
    $amountPaid    = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $receiptNo     = trim($_POST['receipt_no'] ?? '');
    $receivedBy    = null;

    $validMethods = ['cash','bank','online'];

    if ($studentId && $structureId && $amountPaid > 0 && !empty($receiptNo) && in_array($paymentMethod, $validMethods, true)) {
        try {
            $pdo->beginTransaction();

            $clerk = $pdo->prepare("SELECT admin_id FROM admin_users WHERE username=? AND status='active' LIMIT 1");
            $clerk->execute([user()['username']]);
            $receivedBy = $clerk->fetchColumn();
            if (!$receivedBy) {
                throw new Exception('Your account is not linked to an active finance clerk record.');
            }

            $chk = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE receipt_no = ?");
            $chk->execute([$receiptNo]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception("Duplicate Receipt: Reference index number '{$receiptNo}' already exists in ledger logs.");
            }

            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, fee_structure_id, amount_paid, payment_date, payment_method, receipt_no, received_by) VALUES (?, ?, ?, CURDATE(), ?, ?, ?)");
            $stmt->execute([$studentId, $structureId, $amountPaid, $paymentMethod, $receiptNo, $receivedBy]);

            $pdo->commit();
            $message = "Transaction payment records successfully committed into the system database ledger!";
            $messageClass = "alert-success";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Transactional Abort: " . $e->getMessage();
            $messageClass = "alert-danger";
        }
    } else {
        $message = "Validation processing failed. Ensure all numerical data and selections are formatted properly.";
        $messageClass = "alert-danger";
    }
}

// --- 2. DATA QUERY RETRIEVAL BLOCK ---
$query = "SELECT p.payment_id, p.amount_paid, p.payment_date, p.payment_method, p.receipt_no,
                 s.name AS student_name, s.id_no AS student_adm, c.course_code, fs.fee_type, u.full_name AS clerk_name
          FROM fee_payments p
          JOIN students s ON p.student_id = s.student_id
          JOIN fee_structure fs ON p.fee_structure_id = fs.fee_structure_id
          JOIN courses c ON fs.course_id = c.course_id
          JOIN admin_users u ON p.received_by = u.admin_id
          ORDER BY p.payment_date DESC, p.payment_id DESC";
$payments = $pdo->query($query)->fetchAll();
$students = $pdo->query("SELECT student_id, id_no, name FROM students WHERE status='active' ORDER BY name")->fetchAll();
$feeStructures = $pdo->query("SELECT fs.fee_structure_id, fs.fee_type, fs.amount, fs.academic_year, c.course_code FROM fee_structure fs JOIN courses c ON c.course_id=fs.course_id ORDER BY c.course_code, fs.fee_type")->fetchAll();
$dashboardLink = '../' . user()['home'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payments Ledger</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-primary { background: #1e3d73; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-primary:hover { background: #162e58; }
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #edf2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .data-table th { background: #f7fafc; color: #4a5568; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .data-table tr:hover td { background: #fcfdfd; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .badge-cash { background: #edf2f7; color: #4a5568; }
        .badge-bank_deposit { background: #feebc8; color: #c05621; }
        .badge-mpesa_paybill { background: #c6f6d5; color: #22543d; }
        .badge-hef_capitation { background: #e9d8fd; color: #553c9a; }
        .badge-cdf_bursary { background: #feebc8; color: #9c4221; }
        .badge-county_bursary { background: #ebf8ff; color: #2b6cb0; }
        
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-danger { background: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; z-index: 1000; padding: 15px; box-sizing: border-box; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 28px; border-radius: 8px; width: 100%; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); box-sizing: border-box; max-height: 90vh; overflow-y: auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media(max-width: 500px) { .form-row { grid-template-columns: 1fr; gap: 12px; } }
        .form-group { margin-bottom: 16px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #4a5568; text-transform: uppercase; letter-spacing: 0.3px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: #f7fafc; }
        .form-control:focus { border-color: #1e3d73; outline: none; background: #fff; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; border-top: 1px solid #edf2f7; padding-top: 15px; }
        .btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / Finance / <b>Payments</b></div>
            <div class="top-user">
                <div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div>
                <?=htmlspecialchars(user()['name'])?>
            </div>
        </header>
        
        <section class="content">
            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <div>
                    <a href="<?=htmlspecialchars($dashboardLink)?>" style="display:inline-block;margin-bottom:12px;color:#174a9b;font-size:13px;font-weight:700;">← Back to dashboard</a>
                    <h1>Fee Payments Ledger</h1>
                    <p class="text-muted">Monitor multi-channel revenue collections including M-Pesa tracking records and HEF capitation books.</p>
                </div>
                <button class="btn-primary" onclick="toggleModal(true)">+ Record Payment</button>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt / Ref</th>
                            <th>Student Details</th>
                            <th>Allocation Type</th>
                            <th>Channel Pathway</th>
                            <th>Amount Paid</th>
                            <th>Handled By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #a0aec0; padding: 30px;">No operational payment entries found in database ledgers.</td></tr>
                        <?php else: ?>
                            <?php foreach($payments as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d-M-Y', strtotime($p['payment_date']))) ?></td>
                                    <td><span style="font-weight:700; color:#2d3748; display:block;"><?= htmlspecialchars($p['receipt_no']) ?></span></td>
                                    <td>
                                        <div><b><?= htmlspecialchars($p['student_name']) ?></b></div>
                                        <small style="color:#718096;"><?= htmlspecialchars($p['student_adm']) ?> | <?= htmlspecialchars($p['course_code']) ?></small>
                                    </td>
                                    <td><small class="badge" style="background:#ebf8ff; color:#2b6cb0; text-transform:none; font-weight:600;"><?= htmlspecialchars($p['course_code']) ?> - <?= str_replace('_', ' ', ucfirst($p['fee_type'])) ?></small></td>
                                    <td><span class="badge badge-<?= htmlspecialchars($p['payment_method']) ?>"><?= htmlspecialchars(ucfirst($p['payment_method'])) ?></span></td>
                                    <td><?= htmlspecialchars($p['clerk_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div class="modal" id="paymentModal">
    <div class="modal-content">
        <h2>Record Payment</h2>
        <form method="post">
            <input type="hidden" name="action" value="record_payment">
            <div class="form-group"><label for="student_id">Student</label><select class="form-control" id="student_id" name="student_id" required><option value="">Select student</option><?php foreach($students as $student): ?><option value="<?= (int)$student['student_id'] ?>"><?= htmlspecialchars($student['id_no'].' - '.$student['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="fee_structure_id">Fee structure</label><select class="form-control" id="fee_structure_id" name="fee_structure_id" required><option value="">Select fee item</option><?php foreach($feeStructures as $fee): ?><option value="<?= (int)$fee['fee_structure_id'] ?>"><?= htmlspecialchars($fee['course_code'].' - '.ucfirst($fee['fee_type']).' - KES '.number_format($fee['amount'],2).' ('.$fee['academic_year'].')') ?></option><?php endforeach; ?></select></div>
            <div class="form-row"><div class="form-group"><label for="amount_paid">Amount paid</label><input class="form-control" id="amount_paid" name="amount_paid" type="number" min="0.01" step="0.01" required></div><div class="form-group"><label for="payment_method">Payment method</label><select class="form-control" id="payment_method" name="payment_method" required><option value="">Select method</option><option value="cash">Cash</option><option value="bank">Bank</option><option value="online">Online</option></select></div></div>
            <div class="form-group"><label for="receipt_no">Receipt number</label><input class="form-control" id="receipt_no" name="receipt_no" maxlength="50" required></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="toggleModal(false)">Cancel</button><button type="submit" class="btn-primary">Save payment</button></div>
        </form>
    </div>
</div>
<script>function toggleModal(show){document.getElementById('paymentModal').classList.toggle('active',show);}</script>
</body>
</html>
