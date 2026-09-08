<?php
$u=user(); $m=$u['module'] ?? '';
?>
<div class="brand"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small><?=ucfirst($m)?> Workspace</small></div></div>
<nav>
<?php if($m==='admin'): ?>
<p class="nav-label">ADMINISTRATION</p>
<a href="index.php">⌂ Dashboard</a><a href="users.php">♙ Users</a><a href="courses.php">◫ Courses</a><a href="departments.php">◈ Departments</a><a href="notices.php">! Notices</a>
<?php elseif($m==='finance'): ?>
<p class="nav-label">FINANCE</p>
<a href="index.php">⌂ Dashboard</a><a href="payments.php">▤ Payments</a><a href="fees.php">◆ Fee Structure</a><a href="expenses.php">↗ Expenses</a><a href="reports.php">▥ Reports</a>
<?php elseif($m==='dean'): ?>
<p class="nav-label">DEAN'S OFFICE</p>
<a href="index.php">⌂ Dashboard</a><a href="admissions.php">▣ Admissions</a><a href="classes.php">▤ Class Allocation</a><a href="welfare.php">♡ Student Welfare</a>
<?php elseif($m==='students'): ?>
<p class="nav-label">STUDENT PORTAL</p>
<a href="index.php">⌂ My Dashboard</a><a href="profile.php">◎ My Profile</a><a href="fees.php">◆ My Fees</a><a href="notices.php">! Notices</a>
<?php endif; ?>
</nav>
<div class="user-foot"><div class="avatar"><?=strtoupper(substr($u['name']??'U',0,2))?></div><div><b><?=htmlspecialchars($u['name']??'User')?></b><small><?=htmlspecialchars(str_replace('_',' ',ucfirst($u['role']??'')))?></small></div><a href="../logout.php">↪</a></div>