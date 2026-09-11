<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}
$dashboardLink = '../' . user()['home'];

$message = '';
$messageClass = '';

// --- 1. EXPENSE VOUCHER POSTING ENGINE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'record_expense') {
    $category         = trim($_POST['category'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $amount           = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $expenseDate      = $_POST['expense_date'] ?? '';
    $departmentCharge = $_POST['department_charge'] ?? 'General';
    $voucherNo        = trim($_POST['voucher_no'] ?? '');
    $approvedBy       = user()['admin_id'] ?? user()['id'] ?? 1;

    $validDepts = ['Engineering', 'Catering', 'ICT', 'Business', 'Cosmetology', 'General'];

    if (!empty($category) && $amount > 0 && !empty($expenseDate) && !empty($voucherNo) && in_array($departmentCharge, $validDepts)) {
        try {
            $pdo->beginTransaction();

            // Guardrail: Ensure unique expense vouchers to protect transaction tracking
            $chk = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE voucher_no = ?");
            $chk->execute([$voucherNo]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception("Duplicate Voucher: A tracking profile for reference number '{$voucherNo}' already exists.");
            }

            // Insert matching production table parameters cleanly
            $stmt = $pdo->prepare("INSERT INTO expenses (category, description, amount, expense_date, approved_by, department_charge, voucher_no) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category, !empty($description) ? $description : null, $amount, $expenseDate, $approvedBy, $departmentCharge, $voucherNo]);

            $pdo->commit();
            $message = "Expense voucher successfully committed to the central accounts registry!";
            $messageClass = "alert-success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Transactional Abort: " . $e->getMessage();
            $messageClass = "alert-danger";
        }
    } else {
        $message = "Validation processing failed. Ensure all forms contain correctly structured metrics.";
        $messageClass = "alert-danger";
    }
}

// --- 2. MOUNT DATA LIST QUERY ---
$query = "SELECT e.expense_id, e.category, e.description, e.amount, e.expense_date, e.department_charge, e.voucher_no, u.full_name AS approver_name
          FROM expenses e
          JOIN admin_users u ON e.approved_by = u.admin_id
          ORDER BY e.expense_date DESC, e.expense_id DESC";
$expenses = $pdo->query($query)->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Expense Tracker Workspace</title>
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
        .badge-dept { background: #fee2e2; color: #991b1b; }
        .badge-General { background: #edf2f7; color: #4a5568; }
        
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
            <div class="module-tag">Workspace / Finance / <b>Expense Vouchers</b></div>
            <div class="top-user">
                <div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div>
                <?=htmlspecialchars(user()['name'])?>
            </div>
            <div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div>
        </header>
        
        <section class="content">
            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <div>
                    <a href="<?=htmlspecialchars($dashboardLink)?>" style="display:inline-block;margin-bottom:12px;color:#174a9b;font-size:13px;font-weight:700;">← Back to dashboard</a>
                    <h1>Institutional Expense Logs</h1>
                    <p class="text-muted">Register and review expenditures across academic departments, inventory procurement, and general operating funds.</p>
                </div>
                <button class="btn-primary" onclick="toggleModal(true)">+ Log Expense Voucher</button>
            </div>

            <!-- Ledger Audit Trail Table Grid -->
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Voucher / Ref No</th>
                            <th>Cost Classification</th>
                            <th>Target Department</th>
                            <th>Description</th>
                            <th>Outflow Cost</th>
                            <th>Approved By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #a0aec0; padding: 30px;">No recorded outflow expense profiles tracked inside the database registry yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($expenses as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d-M-Y', strtotime($e['expense_date']))) ?></td>
                                    <td><strong style="color: #2d3748; font-family: monospace;"><?= htmlspecialchars($e['voucher_no']) ?></strong></td>
                                    <td><b><?= htmlspecialchars($e['category']) ?></b></td>
                                    <td>
                                        <span class="badge <?= $e['department_charge'] === 'General' ? 'badge-General' : 'badge-dept' ?>">
                                            <?= htmlspecialchars($e['department_charge']) ?>
                                        </span>
                                    </td>
                                    <td><span style="color: #4a5568; font-size:13px;"><?= htmlspecialchars($e['description'] ?? 'N/A') ?></span></td>
                                    <td><strong style="color: #c53030;">KES <?= number_format($e['amount'], 2) ?></strong></td>
                                    <td><small style="color: #718096;"><?= htmlspecialchars($e['approver_name']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Modal Entry Form Pop-up Overlay Widget Component -->
<div class="modal" id="expenseModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 4px; color:#1a202c;">Log Institutional Expense Voucher</h3>
        <p style="color:#718096; font-size:13px; margin-bottom:20px;">Record material procurement receipts or daily petty cash allocations into system balances.</p>
        
        <form action="expenses.php" method="POST">
