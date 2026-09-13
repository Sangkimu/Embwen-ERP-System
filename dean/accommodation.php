<?php
require_once "../config.php";
requireLogin();

if(!allowed(['dean', 'admin'])){
    http_response_code(403);
    die("Access denied. Executive clearance required.");
}

$message = "";
$studentSearch = trim($_GET['student_search'] ?? '');

if (!tableExists($pdo, 'hostel_allocations')) {
    $pdo->exec("CREATE TABLE hostel_allocations (
        allocation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        student_id INT UNSIGNED NOT NULL,
        hostel_name VARCHAR(100) NOT NULL,
        room_no VARCHAR(50) NOT NULL,
        status ENUM('allocated','released') NOT NULL DEFAULT 'allocated',
        allocated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        released_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (allocation_id),
        KEY idx_hostel_status_room (hostel_name, room_no, status),
        KEY idx_hostel_student_status (student_id, status),
        CONSTRAINT fk_hostel_student FOREIGN KEY (student_id) REFERENCES students (student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Handle allocation request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? 'allocate';
    $allocationId = filter_input(INPUT_POST, 'allocation_id', FILTER_VALIDATE_INT);
    $student_id  = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $hostel_name = trim($_POST['hostel_name'] ?? '');
    $room_no     = trim($_POST['room_no'] ?? '');

    if ($action === 'release' && $allocationId) {
        $release = $pdo->prepare("UPDATE hostel_allocations SET status='released', released_at=NOW() WHERE allocation_id=? AND status='allocated'");
        $release->execute([$allocationId]);
        $message = $release->rowCount() ? "<div class='alert-success'>Room allocation released successfully.</div>" : "<div class='alert-danger'>This allocation is already released or does not exist.</div>";
    } elseif (!empty($student_id) && !empty($hostel_name) && !empty($room_no)) {
        try {
            // Verify if student exists first
            $check_student = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $check_student->execute([$student_id]);
            
            if ($check_student->fetchColumn() > 0) {
                $active = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE student_id=? AND status='allocated'");
                $active->execute([$student_id]);
                if ($active->fetchColumn() > 0) {
                    throw new Exception('This student already has an active boarding allocation. Release it before assigning another room.');
                }
                $room = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE hostel_name=? AND room_no=? AND status='allocated'");
                $room->execute([$hostel_name, $room_no]);
                if ($room->fetchColumn() > 0) {
                    throw new Exception('That room is already allocated.');
                }
                // Insert the room allocation into the system
                $stmt = $pdo->prepare("INSERT INTO hostel_allocations (student_id, hostel_name, room_no, status) VALUES (?, ?, ?, 'allocated')");
                $stmt->execute([$student_id, $hostel_name, $room_no]);
                $pdo->prepare("UPDATE students SET residency='boarder' WHERE student_id=?")->execute([$student_id]);
                
                $message = "<div class='alert-success'>✅ Room assigned successfully for Student ID: " . htmlspecialchars($student_id) . "</div>";
            } else {
                $message = "<div class='alert-danger'>❌ Error: Student ID does not exist in registry.</div>";
            }
        } catch (Exception $e) {
            $message = "<div class='alert-danger'>❌ System allocation failure: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert-danger'>❌ Please fulfill all input mapping parameters.</div>";
    }
}

// Fetch current allocated rooms to display below the input pane
$studentWhere = "s.status='active'";
$studentParams = [];
if ($studentSearch !== '') { $studentWhere .= " AND (s.name LIKE ? OR s.id_no LIKE ? OR c.course_code LIKE ?)"; $studentParams = ["%$studentSearch%", "%$studentSearch%", "%$studentSearch%"]; }
$studentStmt = $pdo->prepare("SELECT s.student_id,s.id_no,s.name,c.course_code,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE $studentWhere ORDER BY s.name");
$studentStmt->execute($studentParams);
$students = $studentStmt->fetchAll();
$allocations = $pdo->query("SELECT ha.*, s.name AS student_name, s.id_no, c.course_code, d.department_name FROM hostel_allocations ha JOIN students s ON s.student_id = ha.student_id LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE ha.status = 'allocated' ORDER BY ha.allocated_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Accommodation Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        @media(max-width:768px){ .form-row { grid-template-columns: 1fr; } }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn-assign { background: #1e3a8a; color: #fff; padding: 11px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.9rem; }
        .room-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .room-table th, .room-table td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.88rem; }
        .room-table th { background: rgba(0,0,0,0.02); color: #475569; font-weight: 600; }
        .alert-success { background: #e6f4ea; border: 1px solid #34a853; color: #137333; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .student-search { display:flex; gap:8px; margin-bottom:15px; }
        .student-search input { flex:1; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Accommodation</b></div><img class="header-logo" src="../Images/logo.gif" alt="Polytechnic ERP logo"></header>
        <section class="content">
            
            <div class="page-head">
                <div>
                    <p class="eyebrow">HOSTEL REGISTRY</p>
                    <h1>Manage Accommodations</h1>
                    <p>Allocate physical room entries and manage hostel spaces for registered polytechnic students.</p>
                </div>
            </div>

            <?= $message ?>

            <!-- Student search is separate so required allocation fields do not block it. -->
            <form method="get" action="accommodation.php" class="student-search">
                <input class="form-control" name="student_search" value="<?=htmlspecialchars($studentSearch)?>" placeholder="Search student name, admission number or course">
                <button class="btn-assign" type="submit">Search</button>
            </form>

            <!-- Allocation Entry Form -->
            <section class="card">
                <h2>Assign Hostel Space</h2>
                <form method="POST" action="accommodation.php" style="margin-top: 15px;">
                    <div class="form-row">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">STUDENT ID / ADMISSION</label>
                            <select name="student_id" required class="form-control"><option value="">-- Select student --</option><?php foreach($students as $student): ?><option value="<?= (int)$student['student_id'] ?>"><?=htmlspecialchars($student['id_no'].' - '.$student['name'].' | '.($student['department_name'] ?? 'Pending').' | '.($student['course_code'] ?? 'Course pending'))?></option><?php endforeach; ?></select>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">HOSTEL BLOCK NAME</label>
                            <select name="hostel_name" required class="form-control">
                                <option value="">-- Choose Hostel --</option>
                                <option value="Male Block A">Male Block A</option>
                                <option value="Female Block B">Female Block B</option>
                                <option value="Executive Wing">Executive Wing</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px; color:#475569;">ROOM NUMBER</label>
                            <input type="text" name="room_no" required class="form-control" placeholder="e.g., Room 104">
                        </div>
                    </div>
                    <button type="submit" class="btn-assign">Commit Allocation</button>
                </form>
            </section>

            <!-- Active Occupants Ledger -->
            <section class="card" style="margin-top: 20px;">
                <h2>Current Active Room Occupants</h2>
                <?php if(!empty($allocations)): ?>
                    <table class="room-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course / Department</th>
                                <th>Hostel Block</th>
                                <th>Assigned Room</th>
                                <th>Allocation Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allocations as $row): ?>
                                <tr>
                                    <td><b><?= htmlspecialchars($row['student_id']) ?></b></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?><br><small><?=htmlspecialchars($row['id_no'])?></small></td>
                                    <td><?=htmlspecialchars($row['course_code'] ?? 'Pending')?> / <?=htmlspecialchars($row['department_name'] ?? 'Pending')?></td>
                                    <td><?= htmlspecialchars($row['hostel_name']) ?></td>
                                    <td><span style="font-weight:600; color:#1e3a8a;"><?= htmlspecialchars($row['room_no']) ?></span></td>
                                    <td><?= date('d-M-Y', strtotime($row['allocated_at'])) ?></td>
                                    <td><form method="post" onsubmit="return confirm('Release this room allocation?');"><input type="hidden" name="action" value="release"><input type="hidden" name="allocation_id" value="<?= (int)$row['allocation_id'] ?>"><button type="submit" class="btn-assign" style="background:#b91c1c;padding:7px 10px;">Release</button></form></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#666; font-size:0.85rem; margin-top:15px;">No active room allocation files found on the registry ledger right now.</p>
                <?php endif; ?>
            </section>

        </section>
    </main>
</div>
</body>
</html>
