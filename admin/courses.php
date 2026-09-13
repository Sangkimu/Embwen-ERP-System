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
    if($courseCode===''||$courseName===''||!$departmentId||!$duration||$duration<1){$message='Enter a course code, name, department, and valid duration.';}
    else{try{$stmt=$pdo->prepare('INSERT INTO courses (course_code,course_name,department_id,duration_months) VALUES (?,?,?,?)');$stmt->execute([$courseCode,$courseName,$departmentId,$duration]);$message='Course added successfully.';}catch(PDOException $e){$message='That course code already exists or could not be saved.';}}
}
$departments=$pdo->query("SELECT department_id,department_name FROM departments ORDER BY department_name")->fetchAll();
$courses = $pdo->query("SELECT c.course_id, c.course_code, c.course_name, c.duration_months, d.department_name, c.status FROM courses c LEFT JOIN departments d ON d.department_id = c.department_id ORDER BY c.course_name")->fetchAll();
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
        .registry-form button { background:#1e3d73; color:#fff; border:0; border-radius:6px; padding:10px 16px; font-weight:700; }
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
            <form class="registry-form" method="post"><input name="course_code" placeholder="Course code" required><input name="course_name" placeholder="Course name" required><select name="department_id" required><option value="">Department</option><?php foreach($departments as $department):?><option value="<?= (int)$department['department_id']?>"><?=htmlspecialchars($department['department_name'])?></option><?php endforeach;?></select><input name="duration_months" type="number" min="1" placeholder="Months" required><button type="submit">Add course</button></form>
            
            <!-- Dynamic Institutional Course Selector Dropdown List Component -->
            <div class="filter-container">
                <label class="filter-label" for="courseListSelector">Select Course Profile:</label>
                <select id="courseListSelector" class="filter-select" onchange="filterCoursesTableSelection()">
                    <option value="ALL">Show All System Records</option>
                    
                    <optgroup label="Hospitality & Institutional Management">
                        <option value="Tailoring">Tailoring</option>
                        <option value="Dressmaking">Dressmaking</option>
                        <option value="Knitting">Knitting</option>
                        <option value="Curtain Making">Curtain Making</option>
                        <option value="Tie & Dye Decoration">Tie & Dye Decoration</option>
                        <option value="Cushion Making">Cushion Making</option>
                        <option value="Beauty Therapy">Beauty Therapy</option>
                        <option value="Hairdressing">Hairdressing</option>
                        <option value="Nail Technology">Nail Technology</option>
                        <option value="Make-Up Application">Make-Up Application</option>
                        <option value="Food & Beverage Production">Food & Beverage Production</option>
                        <option value="Food & Beverage Service">Food & Beverage Service</option>
                        <option value="Baking & Pastry">Baking & Pastry</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Food Production & Cookery">Food Production & Cookery</option>
                        <option value="Cake Making & Decoration">Cake Making & Decoration</option>
                    </optgroup>

                    <optgroup label="Building Department">
                        <option value="Carpentry & Joinery">Carpentry & Joinery</option>
                        <option value="Masonry">Masonry</option>
                        <option value="Plumbing & Pipe Fittings">Plumbing & Pipe Fittings</option>
                        <option value="Painting & Decoration">Painting & Decoration</option>
                        <option value="Tiling">Tiling</option>
                        <option value="Water Harvesting">Water Harvesting</option>
                        <option value="Upholstery & Roofing">Upholstery & Roofing</option>
                    </optgroup>

                    <optgroup label="Mechanical Engineering">
                        <option value="Welding & Fabrication">Welding & Fabrication (Grade III)</option>
                        <option value="Light Vehicle Mechanic">Light Vehicle Mechanic (MVM)</option>
                        <option value="Motor Vehicle Electrician">Motor Vehicle Electrician</option>
                        <option value="Plant Mechanics">Plant Mechanics</option>
                    </optgroup>

                    <optgroup label="ICT Department">
                        <option value="Computer Operator">Computer Operator</option>
                        <option value="Computer Packages">Computer Packages</option>
                    </optgroup>

                    <optgroup label="Electrical Department">
                        <option value="Electrical Wireman">Electrical Wireman</option>
                        <option value="Basic Electrical Wiring & Safety">Basic Electrical Wiring & Safety</option>
                    </optgroup>
                </select>
            </div>

            <table id="coursesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <!-- Custom normalization lowercase match string to prevent text structure checking conflicts -->
                        <tr data-name="<?= htmlspecialchars(strtolower($course['course_name'])) ?>">
                            <td><?= htmlspecialchars($course['course_code']) ?></td>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['department_name'] ?? '-') ?></td>
                            <td><?= (int)$course['duration_months'] ?> months</td>
                            <td><?= htmlspecialchars($course['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function filterCoursesTableSelection() {
    const selectedValue = document.getElementById('courseListSelector').value.toLowerCase();
    const tableRows = document.querySelectorAll('#coursesTable tbody tr');

    tableRows.forEach(row => {
        const courseNameAttr = row.getAttribute('data-name');
        
        // Match using a sub-string filter match configuration sequence matrix
        if (selectedValue === "all" || courseNameAttr.includes(selectedValue)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>
</body>
</html>
