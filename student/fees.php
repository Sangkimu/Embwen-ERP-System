<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);die("Access denied.");}

// 1. Fetch Student Details
$student=currentStudent($pdo);

requireCompleteStudentProfile($student);

$statement = [];
$outstanding_balance = 0;
$current_invoice = 0;
$payments_total = 0;
$active_sem_name = "No Active Semester";

if($student) {
    // 2. Fetch active semester details
    $active_semester = tableExists($pdo,'semesters') ? $pdo->query("SELECT semester_id, semester_name FROM semesters WHERE is_active = 1 LIMIT 1")->fetch() : null;
    $current_sem_id = $active_semester['semester_id'] ?? 0;
    $active_sem_name = $active_semester['semester_name'] ?? 'Active Semester';

    $feeSummary=studentFeeSummary($pdo,$student['student_id']);
    $current_invoice=$feeSummary['billed'];

    // 4. Fetch lifetime comprehensive statement details from fee_payments
    $statement_stmt = $pdo->prepare("SELECT payment_id, receipt_no, amount_paid, payment_method, payment_date FROM fee_payments WHERE student_id = ? ORDER BY payment_date DESC");
    $statement_stmt->execute([$student['student_id']]);
    $statement = $statement_stmt->fetchAll();

    // 5. Calculate lifetime total paid to date
    $payments_total=$feeSummary['paid'];

    // 6. Calculate net pending amount matching current semester balance requirements
    $outstanding_balance=$feeSummary['balance'];
}

$notices=$pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience IN ('all','students')")->fetchColumn();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Fees Statement</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    .fee-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .fee-table th, .fee-table td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.88rem; }
    .fee-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
    .receipt-btn { background: #475569; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; text-decoration: none; }
    .receipt-btn:hover { background: #334155; }
    .action-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; }
    .btn-pay-now { background: #22c55e; color: #fff; padding: 10px 20px; border-radius: 4px; font-weight: bold; text-decoration: none; font-size: 0.9rem; }
    @media print { body * { visibility: hidden; } .printable-area, .printable-area * { visibility: visible; } .printable-area { position: absolute; left: 0; top: 0; width: 100%; } .receipt-btn, .action-bar, .sidebar, .topbar { display: none !important; } }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>My Fees</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">FINANCIAL LEDGER</p><h1>Fee Statement & Receipts</h1><p>Review invoices and historical payments recorded for your student profile.</p></div></div>

<div class="stats">
    <div class="stat"><span>Semester Invoice</span><strong><?=money($current_invoice)?></strong></div>
    <div class="stat"><span>Total Paid to Date</span><strong style="color:#15803d;"><?=money($payments_total)?></strong></div>
    <div class="stat"><span>Outstanding Balance</span><strong style="color:#b91c1c;"><?=money($outstanding_balance)?></strong></div>
</div>

<div class="grid" style="grid-template-columns: 1fr;">
    <section class="card printable-area">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Payment History Ledger</h2>
            <span style="font-size:0.8rem; color:#666;">Term Context: <b><?=htmlspecialchars($active_sem_name)?></b></span>
        </div>
        
        <?php if(!empty($statement)): ?>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Receipt ID / Ref No.</th>
                        <th>Method</th>
                        <th>Date Processed</th>
                        <th>Amount Paid</th>
                        <th class="receipt-btn-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($statement as $pay): ?>
                        <tr>
                            <td><b><?=htmlspecialchars($pay['receipt_no'])?></b></td>
                            <td><?=htmlspecialchars(strtoupper($pay['payment_method']))?></td>
                            <td><?=date('d-M-Y', strtotime($pay['payment_date']))?></td>
                            <td style="color:#15803d; font-weight:600;"><?=money($pay['amount_paid'])?></td>
                            <td class="receipt-btn-col">
                                <button class="receipt-btn" onclick="window.print()">Print Receipt</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="service" style="margin-top:15px;"><div style="color:#666; font-size:0.85rem;">No historical payment transactions have been logged for this profile yet.</div></div>
        <?php endif; ?>

        <div class="action-bar">
                    <a class="receipt-btn" style="padding:10px 15px;" href="fee_schedule_pdf.php">Download 2026 Fee Structure PDF</a>
            <button class="receipt-btn" style="padding:10px 15px;" onclick="window.print()">Print Complete Statement</button>
            <?php if($outstanding_balance > 0): ?>
                <a href="payments/mpesa_trigger.php" class="btn-pay-now">Pay Balance via M-Pesa</a>
            <?php endif; ?>
        </div>
    </section>
</div>

</section></main></div></body></html>
