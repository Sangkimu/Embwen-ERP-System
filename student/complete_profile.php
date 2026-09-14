<?php
require_once "../config.php";
requireLogin();

if(!allowed(['students'])){http_response_code(403);die("Access denied.");}

// 1. Fetch current profile data to evaluate pre-existing parameters
$student=currentStudent($pdo);

if(!$student) { die("System Profile mismatch error."); }

$courses=$pdo->query("SELECT c.course_id,c.course_code,c.course_name,c.duration_months,d.department_name FROM courses c LEFT JOIN departments d ON d.department_id=c.department_id WHERE c.status='active' ORDER BY d.department_name,c.course_name")->fetchAll();

$error = "";
$profileRequired = isset($_GET['required']) && $_GET['required'] === '1';

// 2. Handle Profile Data Form Submission Postback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_no   = trim($_POST['id_no'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $birthCertificateNo = trim($_POST['birth_certificate_no'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $gender  = trim($_POST['gender'] ?? '');
    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $residency = $_POST['residency'] ?? '';

    if ($id_no !== '' && (validIdentityNumber($nationalId, 'national_id') || validBirthCertificateNumber($birthCertificateNo)) && validPhoneNumber($phone) && !empty($email) && !empty($gender) && $courseId && in_array($residency, ['boarder','dayscholar'], true)) {
        try {
            $update = $pdo->prepare("UPDATE students SET national_id = ?, birth_certificate_no = ?, phone = ?, email = ?, gender = ?, course_id = ?, residency = ?, status = 'active' WHERE student_id = ?");
            $update->execute([$nationalId !== '' ? $nationalId : null, $birthCertificateNo !== '' ? $birthCertificateNo : null, $phone, $email, $gender, $courseId, $residency, $student['student_id']]);
            
            // Success! Unlock workspace by routing straight back to the index dashboard
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = "System registry error during submission: " . $e->getMessage();
        }
    } else {
        $error = "Admission ID is required. Provide either a National ID (maximum 8 digits) or a Birth Certificate number, plus a 10-digit phone number.";
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Form-specific field controls built to ride on top of your core stylesheet definitions */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        @media(max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; background: #fff; }
        .form-control:focus { outline: none; border-color: #1e3d73; box-shadow: 0 0 0 3px rgba(30,61,115,0.15); }
        .alert-warning-banner { background: #fffbeb; border-left: 4px solid #d97706; color: #92400e; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.88rem; line-height: 1.4; }
        .alert-danger-banner { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.88rem; }
        .profile-required-modal { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; padding: 20px; background: rgba(16,43,97,.48); }
        .profile-required-box { width: min(440px, 100%); padding: 26px; border-radius: 12px; background: #fff; box-shadow: 0 20px 60px rgba(16,43,97,.28); }
        .profile-required-box strong { display: block; color: #92400e; font-size: 18px; margin-bottom: 8px; }
        .profile-required-box p { color: #475569; font-size: 14px; line-height: 1.5; margin: 0 0 18px; }
        .profile-required-box button { border: 0; border-radius: 5px; padding: 11px 16px; background: #1e3d73; color: #fff; font-weight: 700; cursor: pointer; }
        .btn-save-profile { background: #1e3d73; color: #fff; border: none; padding: 12px 25px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .btn-save-profile:hover { background: #142a52; }
        .btn-clear-profile { background: #e2e8f0; color: #1e293b; border: none; padding: 12px 20px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; cursor: pointer; margin-right: 10px; }
    </style>
</head>
<body>
<div class="app">
    <!-- Links your shared layout sidebar block cleanly on the left pane -->
    <aside class="sidebar"><?php include "../partials/sidebar.php";?></aside>
    
    <main class="main">
        <header class="topbar">
            <div class="module-tag">Workspace / <b>Account Verification</b></div>
            <div class="top-user">
                <div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div>
                <?=htmlspecialchars(user()['name'])?>
            </div>
            <img class="header-logo" src="../Images/logo.gif" alt="Polytechnic ERP logo">
        </header>
        
        <section class="content">
            <div class="page-head">
                <div>
                    <p class="eyebrow" style="color: #d97706; font-weight: bold;">MANDATORY STEP REQUIRED</p>
                    <h1>Verify Your Registry Record</h1>
                    <p>Provide your remaining institutional information to unlock full access to student portal functionalities.</p>
                </div>
            </div>

            <!-- Mandatory Alert Banner Block Notice -->
            <div class="alert-warning-banner">
                <strong>🔒 Portal Access Restricted:</strong> National examination guidelines (KNEC/TVET CDACC) require your file to have a valid ID/Passport reference, contact phone number, and gender specification before processing marks or receiving fee receipts.
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-danger-banner"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="grid" style="grid-template-columns: 1fr;">
                <section class="card">
                    <h2>Profile Registration Questionnaire</h2>
                    
                    <form method="POST" action="complete_profile.php" autocomplete="off" style="margin-top: 20px;">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Official Full Name (Read-Only)</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($student['name'] ?? '') ?>" disabled style="background:#f1f5f9; color:#64748b; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Student Admission Code (Read-Only)</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($student['student_id'] ?? '') ?>" disabled style="background:#f1f5f9; color:#64748b; cursor: not-allowed;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="course_id">Course and Department <span style="color:#dc2626;">*</span></label>
                            <select id="course_id" name="course_id" class="form-control" required>
                                <option value="">-- Select your course --</option>
                                <?php foreach($courses as $course): ?>
                                    <option value="<?= (int)$course['course_id'] ?>" <?= (int)($student['course_id'] ?? 0)===(int)$course['course_id']?'selected':'' ?>><?= htmlspecialchars(($course['department_name'] ?? 'Department pending').' - '.$course['course_code'].' - '.$course['course_name'].' ('.$course['duration_months'].' months)') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small style="display:block;margin-top:5px;color:#64748b;">The Dean's Office can review or correct this course assignment after registration.</small>
                        </div>

                        <div class="form-group">
                            <label for="residency">Fee classification <span style="color:#dc2626;">*</span></label>
                            <select id="residency" name="residency" class="form-control" required>
                                <option value="">-- Select fee classification --</option>
                                <option value="boarder" <?=($student['residency']??'')==='boarder'?'selected':''?>>Boarder</option>
                                <option value="dayscholar" <?=($student['residency']??'')==='dayscholar'?'selected':''?>>Dayscholar / Commuter</option>
                            </select>
                            <small style="display:block;margin-top:5px;color:#64748b;">This determines which boarding or dayscholar fee schedule applies to your account.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_no">Admission / Student ID</label>
                                <input type="text" id="id_no" name="id_no" class="form-control" readonly required value="<?= htmlspecialchars($student['id_no'] ?? '') ?>">
                                <label for="national_id">National ID <small>(optional if Birth Certificate is provided)</small></label>
                                <input type="text" id="national_id" name="national_id" class="form-control" inputmode="numeric" pattern="\d{1,8}" maxlength="8" placeholder="Maximum 8 digits" value="<?= htmlspecialchars($student['national_id'] ?? '') ?>">
                                <label for="birth_certificate_no">Birth Certificate No. <small>(required if no National ID)</small></label>
                                <input type="text" id="birth_certificate_no" name="birth_certificate_no" class="form-control" inputmode="numeric" pattern="\d{1,30}" maxlength="30" placeholder="Digits only" value="<?= htmlspecialchars($student['birth_certificate_no'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Primary Mobile Phone Number (Safaricom format) <span style="color:#dc2626;">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" inputmode="numeric" pattern="\d{10}" maxlength="10" required placeholder="10 digits e.g. 0712345678" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Active Personal Email Address <span style="color:#dc2626;">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" required placeholder="e.g., scholar@example.com" value="<?= htmlspecialchars($student['email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender Identification <span style="color:#dc2626;">*</span></label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">-- Choose Gender --</option>
                                    <option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 15px;">
                            <button type="reset" class="btn-clear-profile">Clear</button>
                            <button type="submit" class="btn-save-profile">Save Metadata & Unlock Dashboard</button>
                        </div>
                    </form>
                </section>
            </div>
        </section>
    </main>
</div>
<?php if ($profileRequired): ?>
<div class="profile-required-modal" role="alertdialog" aria-modal="true" aria-labelledby="profileRequiredTitle">
    <div class="profile-required-box">
        <strong id="profileRequiredTitle">Profile completion required</strong>
        <p>Complete your student profile before accessing the dashboard, fees, coursework, or notices.</p>
        <button type="button" onclick="document.querySelector('.profile-required-modal').remove(); document.getElementById('id_no').focus();">Continue to profile</button>
    </div>
</div>
<script>document.getElementById('id_no').focus();</script>
<?php endif; ?>
</body>
</html>
