<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance'])){http_response_code(403);die("Access denied.");}

$message = '';
$messageClass = '';
$mpesaMode = currentMpesaMode('live');

$pdo->prepare("UPDATE mpesa_transactions SET status='failed', result_description=COALESCE(result_description,'Timed out or cancelled by user'), updated_at=CURRENT_TIMESTAMP WHERE status='pending' AND created_at < datetime('now', '-10 minutes')")->execute();
$failedMpesaTxns = $pdo->query("SELECT mt.transaction_id, mt.amount, mt.phone_number, mt.status, mt.result_description, mt.updated_at, s.name AS student_name, c.course_code, fs.fee_type FROM mpesa_transactions mt JOIN students s ON s.student_id = mt.student_id JOIN fee_structure fs ON fs.fee_structure_id = mt.fee_structure_id JOIN courses c ON c.course_id = fs.course_id WHERE mt.status='failed' ORDER BY mt.updated_at DESC LIMIT 10")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'initiate_mpesa') {
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $structureId = filter_input(INPUT_POST, 'fee_structure_id', FILTER_VALIDATE_INT);
    $amountPaid = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $phone = trim($_POST['phone_number'] ?? '');
    $accountReference = 'ERP-' . $studentId . '-' . $structureId . '-' . date('ymdHis');
    try {
        if (!$studentId || !$structureId || $amountPaid <= 0 || !validPhoneNumber($phone)) {
            throw new Exception('Student, fee item, amount, and exactly 10 phone digits are required.');
        }
        $match = $pdo->prepare("SELECT COUNT(*) FROM students s JOIN fee_structure fs ON fs.course_id=s.course_id WHERE s.student_id=? AND fs.fee_structure_id=? AND ".residencyFeeSql());
        $match->execute([$studentId, $structureId]);
        if (!$match->fetchColumn()) { throw new Exception('The selected fee structure does not belong to this student course.'); }
        $balanceStmt=$pdo->prepare("SELECT fs.amount-COALESCE((SELECT SUM(amount_paid) FROM fee_payments WHERE student_id=? AND fee_structure_id=? AND amount_paid>0),0) FROM fee_structure fs WHERE fs.fee_structure_id=?");
        $balanceStmt->execute([$studentId,$structureId,$structureId]);
        if ($amountPaid > max(0,(float)$balanceStmt->fetchColumn())) { throw new Exception('Payment exceeds the remaining balance for this fee item.'); }
        $response = mpesaStkPush($amountPaid, $phone, $accountReference, 'College fee payment');
        if (($response['ResponseCode'] ?? '1') !== '0' || empty($response['CheckoutRequestID'])) {
            throw new Exception($response['ResponseDescription'] ?? 'M-Pesa did not accept the payment request.');
        }
        $normalizedPhone = mpesaNormalizePhone($phone);
        $stmt = $pdo->prepare('INSERT INTO mpesa_transactions (student_id,fee_structure_id,amount,phone_number,account_reference,checkout_request_id,merchant_request_id,status,mpesa_receipt_no,result_code,result_description) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        if (mpesaIsOffline()) {
            $receiptNo = 'OFFLINE-' . time();
            $stmt->execute([$studentId, $structureId, $amountPaid, $normalizedPhone, $accountReference, $response['CheckoutRequestID'], $response['MerchantRequestID'] ?? null, 'completed', $receiptNo, 0, 'Offline MPESA test mode accepted.']);
            $feeInsert = $pdo->prepare("INSERT INTO fee_payments (student_id,fee_structure_id,amount_paid,payment_date,payment_method,receipt_no,reference_no,received_by) VALUES (?,?,?,CURRENT_DATE,'mpesa',?,?,?)");
            $clerk = $pdo->query("SELECT admin_id FROM admin_users WHERE status='active' ORDER BY admin_id LIMIT 1")->fetchColumn();
            if (!$clerk) { throw new Exception('No active finance clerk is available for local offline payment logging.'); }
            $feeInsert->execute([$studentId,$structureId,$amountPaid,$receiptNo,$response['CheckoutRequestID'],$clerk]);
            $message = 'Offline MPESA test payment accepted and recorded successfully.';
        } else {
            $stmt->execute([$studentId, $structureId, $amountPaid, $normalizedPhone, $accountReference, $response['CheckoutRequestID'], $response['MerchantRequestID'] ?? null, 'pending', null, null, 'Pending Safaricom confirmation']);
            $message = 'STK Push sent. The payment will appear in the ledger after Safaricom confirms it.';
        }
        $messageClass = 'alert-success';
    } catch (Throwable $e) {
        $message = 'M-Pesa request failed: ' . $e->getMessage();
        $messageClass = 'alert-danger';
    }
}

// --- 1. CORE PAYMENT RECEIPT POSTING ENGINE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $studentId     = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $feeType       = trim($_POST['fee_type_filter'] ?? '');
    $structureId   = filter_input(INPUT_POST, 'fee_structure_id', FILTER_VALIDATE_INT);
    $amountPaid    = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $receiptNo     = trim($_POST['receipt_no'] ?? '');
    $receivedBy    = null;

    $validMethods = ['bank','online','equity_bank'];

    if (!$structureId && $studentId && in_array($feeType, ['tuition','exam'], true)) {
        $structureStmt = $pdo->prepare("SELECT fs.fee_structure_id FROM fee_structure fs WHERE fs.course_id=(SELECT course_id FROM students WHERE student_id=?) AND fs.fee_type=? ORDER BY fs.academic_year DESC, fs.semester DESC LIMIT 1");
        $structureStmt->execute([$studentId, $feeType]);
        $structureId = (int)($structureStmt->fetchColumn() ?: 0);
    }

    if ($studentId && $structureId && $amountPaid > 0 && !empty($receiptNo) && in_array($paymentMethod, $validMethods, true) && in_array($feeType, ['tuition','exam'], true)) {
        try {
            $pdo->beginTransaction();

            $match = $pdo->prepare("SELECT COUNT(*) FROM students s JOIN fee_structure fs ON fs.course_id=s.course_id WHERE s.student_id=? AND fs.fee_structure_id=? AND ".residencyFeeSql());
            $match->execute([$studentId, $structureId]);
            if (!$match->fetchColumn()) { throw new Exception('The selected fee structure does not belong to this student course.'); }
            $balanceStmt=$pdo->prepare("SELECT fs.amount-COALESCE((SELECT SUM(amount_paid) FROM fee_payments WHERE student_id=? AND fee_structure_id=? AND amount_paid>0),0) FROM fee_structure fs WHERE fs.fee_structure_id=?");
            $balanceStmt->execute([$studentId,$structureId,$structureId]);
            if ($amountPaid > max(0,(float)$balanceStmt->fetchColumn())) { throw new Exception('Payment exceeds the remaining balance for this fee item.'); }

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

            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, fee_structure_id, amount_paid, payment_date, payment_method, receipt_no, received_by) VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?)");
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
        $message = "Validation processing failed. Select a student, choose Fee or Exam, and enter a valid receipt and amount.";
        $messageClass = "alert-danger";
    }
}

// --- 2. FILTERED DATA QUERY RETRIEVAL BLOCK ---
$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: 0;
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
$semester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT) ?: 0;
$academicYear = trim($_GET['academic_year'] ?? '');
$search = trim($_GET['search'] ?? '');
$filters = [];
$params = [];
if ($departmentId) { $filters[] = 'c.department_id = ?'; $params[] = $departmentId; }
if ($courseId) { $filters[] = 'c.course_id = ?'; $params[] = $courseId; }
if ($semester >= 1 && $semester <= 3) { $filters[] = 'fs.semester = ?'; $params[] = $semester; }
if ($academicYear !== '') { $filters[] = 'fs.academic_year = ?'; $params[] = $academicYear; }
if ($search !== '') { $filters[] = '(s.name LIKE ? OR s.id_no LIKE ? OR c.course_code LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';

$query = "SELECT p.payment_id, p.amount_paid, p.payment_date, p.payment_method, p.receipt_no,
                 s.name AS student_name, s.id_no AS student_adm, c.course_code, c.course_name, c.duration_months, fs.fee_type, fs.semester, fs.academic_year, d.department_name, u.full_name AS clerk_name,
                 (SELECT COALESCE(SUM(fs2.amount),0) FROM fee_structure fs2 WHERE fs2.course_id=s.course_id) AS student_billed,
                 (SELECT COALESCE(SUM(fp2.amount_paid),0) FROM fee_payments fp2 WHERE fp2.student_id=s.student_id AND fp2.amount_paid>0) AS student_paid
          FROM fee_payments p
          JOIN students s ON p.student_id = s.student_id
          JOIN fee_structure fs ON p.fee_structure_id = fs.fee_structure_id
          JOIN courses c ON fs.course_id = c.course_id
          LEFT JOIN departments d ON c.department_id = d.department_id
          JOIN admin_users u ON p.received_by = u.admin_id
          $where
          ORDER BY p.payment_date DESC, p.payment_id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();
$studentFilters = ["s.status='active'"];
$studentParams = [];
if ($departmentId) { $studentFilters[] = 'c.department_id = ?'; $studentParams[] = $departmentId; }
if ($courseId) { $studentFilters[] = 's.course_id = ?'; $studentParams[] = $courseId; }
if ($search !== '') { $studentFilters[] = '(s.name LIKE ? OR s.id_no LIKE ?)'; $studentParams[] = "%$search%"; $studentParams[] = "%$search%"; }
$studentStmt = $pdo->prepare("SELECT s.student_id, s.id_no, s.name, s.course_id, s.residency, s.study_start_date, s.expected_end_date, c.course_code, c.duration_months, d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE " . implode(' AND ', $studentFilters) . " ORDER BY s.name");
$studentStmt->execute($studentParams);
$students = $studentStmt->fetchAll();
$feeStructures = $pdo->query("SELECT fs.fee_structure_id, fs.course_id, fs.fee_type, fs.amount, fs.academic_year, fs.semester, fs.residency_scope, c.course_code FROM fee_structure fs JOIN courses c ON c.course_id=fs.course_id ORDER BY c.course_code, fs.academic_year DESC, fs.semester, fs.fee_type")->fetchAll();
$departments = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll();
$courses = $pdo->query("SELECT course_id, course_code, course_name, department_id FROM courses WHERE status='active' ORDER BY course_code")->fetchAll();
$years = $pdo->query("SELECT DISTINCT academic_year FROM fee_structure ORDER BY academic_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$dashboardLink = '../' . user()['home'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
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
        .badge-mpesa { background: #c6f6d5; color: #22543d; }
        .badge-equity_bank { background: #e9d8fd; color: #553c9a; }
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
        .field-hint { display: block; margin-top: 5px; color: #718096; font-size: 11px; line-height: 1.4; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; border-top: 1px solid #edf2f7; padding-top: 15px; }
        .btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .filter-bar { display: grid; grid-template-columns: 1.4fr repeat(4, 1fr) auto; gap: 10px; align-items: end; margin-bottom: 18px; padding: 16px; background: #fff; border: 1px solid #edf2f7; border-radius: 8px; }
        .filter-bar label { display: block; margin-bottom: 5px; color: #4a5568; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .filter-bar input, .filter-bar select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; background: #f7fafc; }
        .filter-button { background: #1e3d73; color: #fff; border: 0; border-radius: 6px; padding: 10px 16px; cursor: pointer; font-weight: 600; }
        .clear-link { display: inline-block; padding: 10px 4px; color: #1e3d73; font-size: 13px; font-weight: 700; text-decoration: none; }
        @media(max-width: 950px) { .filter-bar { grid-template-columns: repeat(3, 1fr); } }
        @media(max-width: 600px) { .filter-bar { grid-template-columns: 1fr; } }
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
            <div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div>
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

            <?php if (!empty($failedMpesaTxns)): ?>
                <div class="alert alert-danger" id="stkStatusNotice">
                    <strong>STK status notice:</strong> <?= count($failedMpesaTxns) ?> payment request(s) were cancelled or timed out and are visible below as failed M-Pesa attempts.
                </div>
            <?php endif; ?>

            <form class="filter-bar" method="get">
                <div><label for="search">Search student</label><input id="search" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Name, admission no. or course"></div>
                <div><label for="department_id">Department</label><select id="department_id" name="department_id"><option value="">All departments</option><?php foreach($departments as $department):?><option value="<?= (int)$department['department_id']?>" <?= $departmentId===(int)$department['department_id']?'selected':''?>><?=htmlspecialchars($department['department_name'])?></option><?php endforeach;?></select></div>
                <div><label for="course_id">Course</label><select id="course_id" name="course_id"><option value="">All courses</option><?php foreach($courses as $course):?><option value="<?= (int)$course['course_id']?>" data-department="<?= (int)$course['department_id']?>" <?= $courseId===(int)$course['course_id']?'selected':''?>><?=htmlspecialchars($course['course_code'].' - '.$course['course_name'])?></option><?php endforeach;?></select></div>
                <div><label for="semester">Term</label><select id="semester" name="semester"><option value="">All terms</option><?php for($term=1;$term<=3;$term++):?><option value="<?=$term?>" <?= $semester===$term?'selected':''?>>Term <?=$term?></option><?php endfor;?></select></div>
                <div><label for="academic_year">Academic year</label><select id="academic_year" name="academic_year"><option value="">All years</option><?php foreach($years as $year):?><option value="<?=htmlspecialchars($year)?>" <?= $academicYear===$year?'selected':''?>><?=htmlspecialchars($year)?></option><?php endforeach;?></select></div>
                <div><button class="filter-button" type="submit">Search</button><a class="clear-link" href="payments.php">Clear</a></div>
            </form>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt / Ref</th>
                            <th>Student Details</th>
                            <th>Department / Course Period</th>
                            <th>Allocation Type</th>
                            <th>Channel Pathway</th>
                            <th>Amount Paid</th>
                            <th>Course Balance</th>
                            <th>Handled By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="9" style="text-align: center; color: #a0aec0; padding: 30px;">No operational payment entries found in database ledgers.</td></tr>
                        <?php else: ?>
                            <?php foreach($payments as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d-M-Y', strtotime($p['payment_date']))) ?></td>
                                    <td><span style="font-weight:700; color:#2d3748; display:block;"><?= htmlspecialchars($p['receipt_no']) ?></span></td>
                                    <td>
                                        <div><b><?= htmlspecialchars($p['student_name']) ?></b></div>
                                        <small style="color:#718096;"><?= htmlspecialchars($p['student_adm']) ?> | <?= htmlspecialchars($p['course_code']) ?> - <?= htmlspecialchars($p['course_name']) ?></small>
                                    </td>
                                    <td><small><?=htmlspecialchars($p['department_name'] ?? 'Unassigned department')?></small><br><small style="color:#718096;"><?=htmlspecialchars($p['duration_months'])?> months · Term <?=htmlspecialchars($p['semester'])?> · <?=htmlspecialchars($p['academic_year'])?></small></td>
                                    <td><small class="badge" style="background:#ebf8ff; color:#2b6cb0; text-transform:none; font-weight:600;"><?= htmlspecialchars($p['course_code']) ?> - <?= str_replace('_', ' ', ucfirst($p['fee_type'])) ?></small></td>
                                    <td><span class="badge badge-<?= htmlspecialchars($p['payment_method']) ?>"><?= htmlspecialchars(ucfirst($p['payment_method'])) ?></span></td>
                                    <td><?= money($p['amount_paid']) ?></td>
                                    <td><?=money(max(0,(float)$p['student_billed']-(float)$p['student_paid']))?></td>
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

<div class="form-group" style="max-width:300px; margin: 20px 0 10px;">
    <label for="mpesa_mode_selector">MPESA mode</label>
    <select class="form-control" id="mpesa_mode_selector">
        <option value="live" <?= $mpesaMode === 'live' ? 'selected' : '' ?>>Live / Sandbox</option>
        <option value="offline" <?= $mpesaMode === 'offline' ? 'selected' : '' ?>>Offline test mode</option>
    </select>
</div>

<div class="modal" id="paymentModal">
    <div class="modal-content">
        <h2>Record Payment</h2>
        <form method="post">
            <input type="hidden" name="action" id="paymentAction" value="record_payment">
            <input type="hidden" name="mpesa_mode" id="mpesa_mode_hidden" value="<?= htmlspecialchars($mpesaMode) ?>">
            <div class="form-group"><label for="admission_search">Search student by Admission No.</label><input class="form-control" id="admission_search" type="text" placeholder="Enter admission number or name" autocomplete="off"></div>
            <div class="form-group"><label for="student_id">Student</label><select class="form-control" id="student_id" name="student_id" required><option value="">Select student</option><?php foreach($students as $student): ?><option value="<?= (int)$student['student_id'] ?>" data-course="<?= (int)($student['course_id'] ?? 0) ?>" data-residency="<?=htmlspecialchars($student['residency']??'')?>" data-search="<?=htmlspecialchars(strtolower($student['id_no'].' '.$student['name']))?>"><?= htmlspecialchars($student['id_no'].' - '.$student['name'].' ('.($student['course_code'] ?? 'Unassigned').' / '.($student['residency'] ?? 'Classification pending').' · '.($student['duration_months'] ?? '?').' months · '.($student['study_start_date'] ?? 'start pending').' to '.($student['expected_end_date'] ?? 'end pending').')') ?></option><?php endforeach; ?></select><small class="field-hint">The selected course, residency, and study period determine the applicable fee record.</small></div>
            <div class="form-group"><label for="fee_type_filter">Payment type</label><select class="form-control" id="fee_type_filter" name="fee_type_filter" required><option value="">Select fee type</option><option value="tuition">Tuition fee</option><option value="exam">Exam fee</option></select></div>
            <input type="hidden" id="fee_structure_id" name="fee_structure_id">
            <div class="form-group"><label>Selected fee item</label><div id="feeHint" class="field-hint" style="margin-top:0; padding:10px 12px; border:1px solid #dbeafe; border-radius:6px; background:#f8fbff; color:#1e3d73;">Choose a student and fee type to load the matching fee record automatically.</div></div>
            <div class="form-row"><div class="form-group"><label for="amount_paid">Amount paid (KES)</label><input class="form-control" id="amount_paid" name="amount_paid" type="number" min="0.01" step="0.01" inputmode="decimal" autocomplete="off" required><small class="field-hint">Partial payments are allowed. Cash payments are not accepted.</small></div><div class="form-group"><label for="payment_method">Payment method</label><select class="form-control" id="payment_method" name="payment_method" required><option value="">Select method</option><option value="bank">Bank transfer</option><option value="equity_bank">Equity Bank</option><option value="online">Online</option><option value="mpesa">M-Pesa STK Push</option></select></div></div>
            <div id="mpesaContainer" style="display:none; border:1px solid #dbeafe; background:#f8fbff; border-radius:8px; padding:16px; margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="margin:0; font-size:16px; color:#1e3d73;">M-Pesa payment</h3>
                    <span style="background:#d1fae5; color:#065f46; border-radius:999px; padding:4px 10px; font-size:11px; font-weight:700; text-transform:uppercase;">STK Push</span>
                </div>
                <div class="form-group" id="phoneGroup" style="margin-bottom:0"><label for="phone_number">M-Pesa phone number</label><input class="form-control" id="phone_number" name="phone_number" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" autocomplete="tel" placeholder="10 digits e.g. 0712345678"><small class="field-hint">The customer will receive a payment prompt on this number. If cancelled, the request stays pending and is marked failed after timeout.</small></div>
            </div>
            <div class="form-group" id="receiptGroup"><label for="receipt_no">Receipt number</label><input class="form-control" id="receipt_no" name="receipt_no" maxlength="50" autocomplete="off" placeholder="Enter the official receipt number"></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" onclick="toggleModal(false)">Cancel</button><button type="submit" class="btn-primary" id="submitPayment">Save payment</button></div>
        </form>
    </div>
</div>
<script>
function toggleModal(show){document.getElementById('paymentModal').classList.toggle('active',show);}
const method=document.getElementById('payment_method');
const feeStructure=document.getElementById('fee_structure_id');
const amount=document.getElementById('amount_paid');
const feeHint=document.getElementById('feeHint');
const action=document.getElementById('paymentAction');
const mpesaContainer=document.getElementById('mpesaContainer');
const phoneGroup=document.getElementById('phoneGroup');
const phone=document.getElementById('phone_number');
const receiptGroup=document.getElementById('receiptGroup');
const receipt=document.getElementById('receipt_no');
const submit=document.getElementById('submitPayment');
const student=document.getElementById('student_id');
const feeType=document.getElementById('fee_type_filter');
const admissionSearch=document.getElementById('admission_search');
const mpesaModeSelector=document.getElementById('mpesa_mode_selector');
const mpesaModeHidden=document.getElementById('mpesa_mode_hidden');

if (mpesaModeSelector && mpesaModeHidden) {
  mpesaModeSelector.addEventListener('change', function(){
    const value = this.value;
    mpesaModeHidden.value = value;
    const url = new URL(window.location.href);
    url.searchParams.set('mpesa_mode', value);
    window.history.replaceState({}, '', url);
  });
}

const stkStatusNotice=document.getElementById('stkStatusNotice');
if (stkStatusNotice) {
 setTimeout(function(){
  stkStatusNotice.style.transition='opacity 0.4s ease';
  stkStatusNotice.style.opacity='0';
  setTimeout(function(){stkStatusNotice.remove();},400);
 },5000);
}

function filterFeeStructureList(){
 const courseId=student.options[student.selectedIndex]?.dataset.course || '';
 const residency=student.options[student.selectedIndex]?.dataset.residency || '';
 const selectedType=feeType.value || '';
 if (!courseId || !selectedType) {
     feeStructure.value = '';
     amount.value = '';
     feeHint.textContent = 'Choose a student and fee type to load the matching fee record automatically.';
     return;
 }

 const matchedFee = window.allFeeStructures.find(function(item){
     return String(item.course_id) === String(courseId)
          && String(item.fee_type) === String(selectedType)
          && (String(item.residency_scope) === 'universal' || String(item.residency_scope) === String(residency));
 });

 if (matchedFee) {
     feeStructure.value = String(matchedFee.fee_structure_id);
     feeHint.textContent = 'Selected fee item: ' + matchedFee.label + ' — KES ' + Number(matchedFee.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 });
     if (!amount.value) amount.value = matchedFee.amount;
 } else {
     feeStructure.value = '';
     amount.value = '';
     feeHint.textContent = 'No matching fee record for this student and payment type.';
 }
}

admissionSearch.addEventListener('input', function(){
 const query = this.value.trim().toLowerCase();
 Array.from(student.options).forEach(function(option){
    if (!option.value) { option.hidden = false; return; }
    const match = !query || (option.dataset.search || '').includes(query);
    option.hidden = !match;
 });
 if (!query) {
    student.value = '';
    feeStructure.value = '';
 }
});

student.addEventListener('change',function(){
 const courseId=this.options[this.selectedIndex].dataset.course||'';
 const residency=this.options[this.selectedIndex].dataset.residency||'';
 feeStructure.value='';
 amount.value='';
 if (courseId && feeType.value) {
     filterFeeStructureList();
 } else {
     feeHint.textContent = courseId ? 'Choose the payment type for this student.' : 'Select a student first.';
 }
});

feeType.addEventListener('change', function(){
 filterFeeStructureList();
});

window.allFeeStructures = <?php echo json_encode(array_map(function($fee){ return [
     'fee_structure_id' => (int)$fee['fee_structure_id'],
     'course_id' => (int)$fee['course_id'],
     'fee_type' => $fee['fee_type'],
     'residency_scope' => $fee['residency_scope'],
     'amount' => (float)$fee['amount'],
     'label' => $fee['course_code'].' - Term '.$fee['semester'].' - '.ucfirst($fee['fee_type']).' - KES '.number_format($fee['amount'],2).' ('.$fee['academic_year'].')'
]; }, $feeStructures)); ?>;

method.addEventListener('change',function(){
 const mpesa=this.value==='mpesa';
 action.value=mpesa?'initiate_mpesa':'record_payment';
 mpesaContainer.style.display=mpesa ? 'block' : 'none';
 phoneGroup.style.display=mpesa ? 'flex' : 'none';
 receiptGroup.style.display=mpesa ? 'none' : 'flex';
 phone.required=mpesa;
 receipt.required=!mpesa;
 submit.textContent=mpesa ? 'Send STK Push' : 'Save payment';
});
const departmentFilter=document.getElementById('department_id');
const courseFilter=document.getElementById('course_id');
if(departmentFilter&&courseFilter){
 departmentFilter.addEventListener('change',function(){
  Array.from(courseFilter.options).forEach(function(option){
   option.hidden=option.value!==''&&option.dataset.department!==departmentFilter.value;
  });
  if(courseFilter.selectedOptions[0]&&courseFilter.selectedOptions[0].hidden) courseFilter.value='';
 });
}
</script>
</body>
</html>
