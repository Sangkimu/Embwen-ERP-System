<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance', 'dean'])) {
    http_response_code(403);
    die("Access denied.");
}

$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $courseCode=trim($_POST['course_code']??'');
    $courseName=trim($_POST['course_name']??'');
    $departmentId=filter_input(INPUT_POST,'department_id',FILTER_VALIDATE_INT);
    $duration=filter_input(INPUT_POST,'duration_months',FILTER_VALIDATE_INT);
    $entryRequirement=trim($_POST['entry_requirement']??'');
    if($courseCode===''||$courseName===''||!$departmentId||!$duration||$duration<1){$message='Enter a course code, name, department, and valid duration.';}
    else{try{$stmt=$pdo->prepare('INSERT INTO courses (course_code,course_name,department_id,duration_months,entry_requirement) VALUES (?,?,?,?,?)');$stmt->execute([$courseCode,$courseName,$departmentId,$duration,$entryRequirement!==''?$entryRequirement:null]);$message='Course added successfully.';}catch(PDOException $e){$message='That course code already exists or could not be saved.';}}
}
$departments=$pdo->query("SELECT department_id,department_name FROM departments ORDER BY department_name")->fetchAll();
$courses = $pdo->query("SELECT c.course_id, c.course_code, c.course_name, c.duration_months, c.entry_requirement, d.department_name, c.status FROM courses c LEFT JOIN departments d ON d.department_id = c.department_id ORDER BY d.department_name, c.course_name")->fetchAll();
$selectedCourseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$selectedCourse = null;
if ($selectedCourseId) {
    foreach ($courses as $course) {
        if ((int)$course['course_id'] === (int)$selectedCourseId) {
            $selectedCourse = $course;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Courses | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:block; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
        .registry-form { display:grid; grid-template-columns:1fr 1.5fr 1fr 1fr auto; gap:10px; margin-top:18px; }
        .registry-form input,.registry-form select { padding:10px; border:1px solid #cbd5e0; border-radius:6px; }
        .period { white-space:nowrap; }
        .registry-form button { background:#1e3d73; color:#fff; border:0; border-radius:6px; padding:10px 16px; font-weight:700; }
        .clear-button { background:#e2e8f0 !important; color:#1e293b !important; }
        .registry-message { padding:10px 12px; background:#e6f4ea; color:#137333; border-radius:6px; margin-top:15px; }
        @media(max-width:800px){.registry-form{grid-template-columns:1fr;}}
        @media(max-width:700px){.sidebar{position:static;width:100%;min-height:auto;transform:none}.main{margin-left:0;width:100%}.content{padding:20px}.topbar{padding:0 18px}}

        /* Filter layout formatting */
        .filter-container { margin: 20px 0 10px 0; display: flex; align-items: center; gap: 10px; }
        .filter-label { font-size: 14px; font-weight: 600; color: #4a5568; }
        .filter-select { padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; background-color: #fff; min-width: 320px; cursor: pointer; }
        .filter-select:focus { outline: none; border-color: #1e3d73; box-shadow: 0 0 0 3px rgba(30,61,115,0.15); }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Courses</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Courses</h1>
            <p class="text-muted">Academic courses available in the college registry.</p>
            <?php if($message): ?><div class="registry-message"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <form class="registry-form" method="post"><input name="course_code" placeholder="Course code" required><input name="course_name" placeholder="Course name" required><select name="department_id" required><option value="">Department</option><?php foreach($departments as $department):?><option value="<?= (int)$department['department_id']?>"><?=htmlspecialchars($department['department_name'])?></option><?php endforeach;?></select><input name="duration_months" type="number" min="1" placeholder="Months" required><input name="entry_requirement" placeholder="Entry requirement (optional)"><button type="reset" class="clear-button">Clear</button><button type="submit">Add course</button></form>
            
            <div class="filter-container" style="display:block; margin-top:25px; margin-bottom:15px;">
                <label class="filter-label" for="courseDirectorySelector">Course directory</label>
                <select id="courseDirectorySelector" class="filter-select" onchange="showCourseDetails(this)">
                    <option value="">Select a course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-code="<?= htmlspecialchars($course['course_code'], ENT_QUOTES, 'UTF-8') ?>"
                            data-department="<?= htmlspecialchars($course['department_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                            data-duration="<?= htmlspecialchars((int)$course['duration_months'], ENT_QUOTES, 'UTF-8') ?>"
                            data-requirement="<?= htmlspecialchars($course['entry_requirement'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($course['status'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $selectedCourseId && (int)$course['course_id'] === (int)$selectedCourseId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="courseDirectoryDetail" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; color:#4a5568; min-height:80px; display:flex; align-items:center;">
                <?php if ($selectedCourse): ?>
                    <strong><?= htmlspecialchars($selectedCourse['course_name']) ?></strong><br>
                    Code: <?= htmlspecialchars($selectedCourse['course_code']) ?><br>
                    Department: <?= htmlspecialchars($selectedCourse['department_name'] ?? '-') ?><br>
                    Duration: <?= (int)$selectedCourse['duration_months'] % 12 === 0 ? ((int)$selectedCourse['duration_months'] / 12) . ' year' . (((int)$selectedCourse['duration_months'] === 12) ? '' : 's') : (int)$selectedCourse['duration_months'] . ' months' ?><br>
                    Entry Requirement: <?= htmlspecialchars($selectedCourse['entry_requirement'] ?? 'Not specified') ?><br>
                    Status: <?= htmlspecialchars($selectedCourse['status']) ?>
                <?php else: ?>
                    Select a course to view its department, duration, entry requirement, and status.
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function showCourseDetails(select) {
    const detailBox = document.getElementById('courseDirectoryDetail');
    const selected = select.options[select.selectedIndex];

    if (!selected || !selected.value) {
        detailBox.innerHTML = 'Select a course to view its department, duration, entry requirement, and status.';
        return;
    }

    const duration = Number(selected.dataset.duration || 0);
    const durationLabel = duration % 12 === 0 ? (duration / 12) + ' year' + (duration === 12 ? '' : 's') : duration + ' months';

    detailBox.innerHTML = '<strong>' + selected.value + '</strong><br>' +
        'Code: ' + selected.dataset.code + '<br>' +
        'Department: ' + selected.dataset.department + '<br>' +
        'Duration: ' + durationLabel + '<br>' +
        'Entry Requirement: ' + selected.dataset.requirement + '<br>' +
        'Status: ' + selected.dataset.status;
}

window.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('courseDirectorySelector');
    if (courseSelect && courseSelect.value) {
        showCourseDetails(courseSelect);
    }
});
</script>
</body>
</html>
