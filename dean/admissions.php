<?php
require_once "../config.php";
requireLogin();
if (!allowed(['dean', 'admin'])) { http_response_code(403); die("Access denied."); }

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admissionId = filter_input(INPUT_POST, 'admission_id', FILTER_VALIDATE_INT);
    $decision = $_POST['decision'] ?? '';

    if (!$admissionId || !in_array($decision, ['admitted', 'rejected'], true)) {
        $message = 'Select a valid application decision.';
        $messageClass = 'alert-danger';
    } else {
        try {
            $pdo->beginTransaction();
            $applicationStmt = $pdo->prepare('SELECT a.*, c.course_code, c.course_name FROM admissions a LEFT JOIN courses c ON c.course_id=a.course_id WHERE a.admission_id=? FOR UPDATE');
            $applicationStmt->execute([$admissionId]);
            $application = $applicationStmt->fetch();
            if (!$application) { throw new Exception('Application not found.'); }
            if ($application['admission_status'] !== 'pending') { throw new Exception('This application has already been decided.'); }

            $studentId = null;
            if ($decision === 'admitted') {
                $admissionNumber = generateStudentIdNumber($pdo, (int)$application['course_id']);
                $studentStmt = $pdo->prepare("INSERT INTO students (id_no, name, course_id, status) VALUES (?, ?, ?, 'active')");
                $studentStmt->execute([$admissionNumber, $application['applicant_name'], $application['course_id']]);
                $studentId = (int)$pdo->lastInsertId();
            }

            $update = $pdo->prepare('UPDATE admissions SET admission_status=?, decision_date=CURDATE(), student_id=?, decided_by=? WHERE admission_id=?');
            $update->execute([$decision, $studentId, (int)(user()['id'] ?? 0), $admissionId]);
            $pdo->commit();
            $message = $decision === 'admitted' ? 'Applicant admitted and added to the student registry as ' . $admissionNumber . '.' : 'Application rejected successfully.';
            $messageClass = 'alert-success';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $message = 'Unable to update application: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'admitted', 'rejected', 'all'], true)) { $status = 'pending'; }
$where = $status === 'all' ? '' : 'WHERE a.admission_status=?';
$params = $status === 'all' ? [] : [$status];
$stmt = $pdo->prepare("SELECT a.*, c.course_code, c.course_name, s.id_no FROM admissions a LEFT JOIN courses c ON c.course_id=a.course_id LEFT JOIN students s ON s.student_id=a.student_id $where ORDER BY a.application_date DESC, a.admission_id DESC");
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admissions Management</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
.form-control{width:100%;box-sizing:border-box;padding:10px;border:1px solid #cbd5e1;border-radius:5px;background:#fff}.filter{display:flex;gap:10px;align-items:end;margin-bottom:18px}.filter label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px}.filter select{min-width:180px}.application-table{width:100%;border-collapse:collapse}.application-table th,.application-table td{text-align:left;padding:12px;border-bottom:1px solid #e2e8f0;font-size:14px}.application-table th{background:#f8fafc;color:#475569;font-size:12px}.decision{display:flex;gap:6px;align-items:center}.decision input{width:130px}.btn{border:0;border-radius:5px;padding:9px 12px;color:#fff;font-weight:700;cursor:pointer}.btn-admit{background:#15803d}.btn-reject{background:#b91c1c}.badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase}.pending{background:#fef3c7;color:#92400e}.admitted{background:#dcfce7;color:#166534}.rejected{background:#fee2e2;color:#991b1b}@media(max-width:700px){.filter{display:block}.filter select{width:100%}.application-table{min-width:760px}}
</style>
</head>
<body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Admissions</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content">
<div class="page-head"><div><p class="eyebrow">DEAN MODULE</p><h1>Admissions Management</h1><p>Review applications and place approved applicants into the student registry.</p></div></div>
<?php if ($message): ?><div class="alert <?=htmlspecialchars($messageClass)?>"><?=htmlspecialchars($message)?></div><?php endif; ?>
<section class="card"><form class="filter" method="get"><div><label for="status">Application status</label><select class="form-control" id="status" name="status"><option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option><option value="admitted" <?=$status==='admitted'?'selected':''?>>Admitted</option><option value="rejected" <?=$status==='rejected'?'selected':''?>>Rejected</option><option value="all" <?=$status==='all'?'selected':''?>>All applications</option></select></div><button class="btn" style="background:#1e3d73" type="submit">Filter</button></form>
<div style="overflow-x:auto"><table class="application-table"><thead><tr><th>Applicant</th><th>Course</th><th>Application date</th><th>Status</th><th>Admission no.</th><th>Decision</th></tr></thead><tbody>
<?php if (!$applications): ?><tr><td colspan="6" style="text-align:center;color:#64748b;padding:28px">No applications found.</td></tr><?php endif; ?>
<?php foreach ($applications as $application): ?><tr><td><b><?=htmlspecialchars($application['applicant_name'])?></b></td><td><?=htmlspecialchars(($application['course_code'] ?? '').' - '.($application['course_name'] ?? 'Unassigned'))?></td><td><?=htmlspecialchars(date('d-M-Y', strtotime($application['application_date'])))?></td><td><span class="badge <?=htmlspecialchars($application['admission_status'])?>"><?=htmlspecialchars($application['admission_status'])?></span></td><td><?=htmlspecialchars($application['id_no'] ?? ($application['admission_status'] === 'pending' ? 'Generated on approval' : '-'))?></td><td><?php if ($application['admission_status'] === 'pending'): ?><form class="decision" method="post"><input type="hidden" name="admission_id" value="<?= (int)$application['admission_id'] ?>"><button class="btn btn-admit" name="decision" value="admitted">Admit & Generate ID</button><button class="btn btn-reject" name="decision" value="rejected">Reject</button></form><?php else: ?><?=htmlspecialchars($application['decision_date'] ?? '-')?><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</section></main></div></body></html>
