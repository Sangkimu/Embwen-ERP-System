<?php
require_once "config.php";

function countRows($pdo, $table, $where = "1=1") {
    return (int)$pdo->query("SELECT COUNT(*) FROM `$table` WHERE $where")->fetchColumn();
}

$students = countRows($pdo, "students");
$activeStudents = countRows($pdo, "students", "status='active'");
$pendingAdmissions = countRows($pdo, "admissions", "admission_status='pending'");
$openCases = countRows($pdo, "student_welfare", "status='open'");
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments")->fetchColumn();
$expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();

$recentPayments = $pdo->query("
    SELECT fp.receipt_no, s.name, fp.amount_paid, fp.payment_date, fp.payment_method
    FROM fee_payments fp
    JOIN students s ON s.student_id=fp.student_id
    ORDER BY fp.payment_id DESC LIMIT 6
")->fetchAll();

$recentAdmissions = $pdo->query("
    SELECT a.applicant_name, c.course_name, a.admission_status, a.application_date
    FROM admissions a
    JOIN courses c ON c.course_id=a.course_id
    ORDER BY a.admission_id DESC LIMIT 6
")->fetchAll();

$notices = $pdo->query("SELECT title, target_audience, posted_on FROM notices ORDER BY notice_id DESC LIMIT 4")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vocational College ERP</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-mark">VC</div>
    <div><strong>College ERP</strong><small>Management System</small></div>
  </div>
  <nav>
    <p class="nav-label">MAIN</p>
    <a class="active" href="index.php"><span>⌂</span> Dashboard</a>
    <a href="students.php"><span>◉</span> Students</a>
    <a href="admissions.php"><span>▣</span> Admissions</a>
    <a href="classes.php"><span>▤</span> Classes</a>
    <a href="welfare.php"><span>♡</span> Student Welfare</a>
    <p class="nav-label">FINANCE</p>
    <a href="payments.php"><span>▤</span> Payments</a>
    <a href="fees.php"><span>◆</span> Fee Structure</a>
    <a href="expenses.php"><span>↗</span> Expenses</a>
    <p class="nav-label">ADMINISTRATION</p>
    <a href="courses.php"><span>◫</span> Courses</a>
    <a href="departments.php"><span>◈</span> Departments</a>
    <a href="notices.php"><span>!</span> Notices</a>
    <a href="users.php"><span>♙</span> Users</a>
  </nav>
  <div class="sidebar-footer"><small>Academic Year</small><strong>2026 / 2027</strong></div>
</aside>

<main class="main">
<header class="topbar">
  <button class="menu" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
  <div class="search">⌕ <input placeholder="Search students, payments, courses..."></div>
  <div class="top-actions"><button>◌</button><div class="avatar">AD</div><div><strong>Administrator</strong><small>Super Admin</small></div></div>
</header>

<section class="content">
  <div class="page-head">
    <div><p class="eyebrow">OVERVIEW</p><h1>Good evening, Administrator</h1><p>Here’s what’s happening across the college today.</p></div>
    <button class="primary" onclick="location.href='students.php'">＋ Add Student</button>
  </div>

  <div class="stats">
    <div class="stat"><div class="icon blue">◉</div><div><span>Total Students</span><strong><?= $students ?></strong><small class="up">Active: <?= $activeStudents ?></small></div></div>
    <div class="stat"><div class="icon orange">▣</div><div><span>Pending Admissions</span><strong><?= $pendingAdmissions ?></strong><small>Applications awaiting decision</small></div></div>
    <div class="stat"><div class="icon green">◆</div><div><span>Fees Collected</span><strong><?= money($revenue) ?></strong><small>This system total</small></div></div>
    <div class="stat"><div class="icon red">♡</div><div><span>Open Welfare Cases</span><strong><?= $openCases ?></strong><small>Needs attention</small></div></div>
  </div>

  <div class="grid-2">
    <section class="card"><div class="card-head"><div><h2>Recent Payments</h2><p>Latest fee transactions</p></div><a href="payments.php">View all →</a></div>
      <div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Date</th><th>Method</th></tr></thead><tbody>
      <?php foreach($recentPayments as $p): ?><tr><td><b><?= htmlspecialchars($p['receipt_no']) ?></b></td><td><?= htmlspecialchars($p['name']) ?></td><td><b><?= money($p['amount_paid']) ?></b></td><td><?= htmlspecialchars($p['payment_date']) ?></td><td><span class="pill"><?= htmlspecialchars(ucfirst($p['payment_method'])) ?></span></td></tr><?php endforeach; ?>
      <?php if(!$recentPayments): ?><tr><td colspan="5" class="empty">No payments recorded yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </section>

    <section class="card"><div class="card-head"><div><h2>Admissions</h2><p>Latest applications</p></div><a href="admissions.php">View all →</a></div>
      <div class="list">
      <?php foreach($recentAdmissions as $a): ?>
        <div class="list-row"><div class="person-icon"><?= strtoupper(substr($a['applicant_name'],0,1)) ?></div><div class="grow"><b><?= htmlspecialchars($a['applicant_name']) ?></b><small><?= htmlspecialchars($a['course_name']) ?></small></div><span class="status <?= htmlspecialchars($a['admission_status']) ?>"><?= ucfirst($a['admission_status']) ?></span></div>
      <?php endforeach; ?>
      <?php if(!$recentAdmissions): ?><div class="empty">No admissions recorded yet.</div><?php endif; ?>
      </div>
    </section>
  </div>

  <div class="grid-2">
    <section class="card"><div class="card-head"><div><h2>Finance Summary</h2><p>Collected versus expenses</p></div></div>
      <div class="finance-box"><div><span>Total Collected</span><strong><?= money($revenue) ?></strong></div><div><span>Total Expenses</span><strong><?= money($expenses) ?></strong></div><div><span>Net Balance</span><strong><?= money($revenue-$expenses) ?></strong></div></div>
    </section>
    <section class="card"><div class="card-head"><div><h2>Latest Notices</h2><p>College announcements</p></div><a href="notices.php">Manage →</a></div>
      <?php foreach($notices as $n): ?><div class="notice"><div class="notice-dot">!</div><div><b><?= htmlspecialchars($n['title']) ?></b><small><?= ucfirst($n['target_audience']) ?> · <?= date("d M Y", strtotime($n['posted_on'])) ?></small></div></div><?php endforeach; ?>
      <?php if(!$notices): ?><div class="empty">No notices posted yet.</div><?php endif; ?>
    </section>
  </div>
</section>
</main>
</div>
<script src="assets/js/app.js"></script>
</body></html>