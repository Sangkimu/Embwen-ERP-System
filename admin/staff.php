<?php
require_once "../config.php";
requireLogin();
$currentUser = user() ?? [];
$isSuperAdmin = ($currentUser['role'] ?? '') === 'super_admin';
if (!$isSuperAdmin || !allowed(['admin'])) {
    http_response_code(403);
    die("Access denied.");
}

$flash = consumeFlash();
$message = '';
$messageClass = '';
if ($flash && !empty($flash['message'])) {
    $message = (string)$flash['message'];
    $messageClass = ($flash['type'] ?? 'error') === 'success' ? 'alert-success' : 'alert-danger';
}
$moduleRoles = [
    'admin' => ['admin' => 'Administrator', 'staff' => 'Admin Staff'],
    'finance' => ['finance_manager' => 'Finance Manager', 'finance_officer' => 'Finance Officer', 'cashier' => 'Cashier'],
    'dean' => ['dean' => 'Dean', 'admissions_officer' => 'Admissions Officer', 'welfare_officer' => 'Welfare Officer', 'registrar' => 'Registrar']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSuperAdmin) {
    $action = (string)($_POST['action'] ?? 'create');

    if ($action === 'delete_user') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$targetId) {
            setFlash('error', 'No user was selected for deletion.');
            header('Location: staff.php');
            exit;
        }

        try {
            $deleteStmt = $pdo->prepare('DELETE FROM module_users WHERE user_id = ? LIMIT 1');
            $deleteStmt->execute([$targetId]);

            if ($deleteStmt->rowCount() > 0) {
                setFlash('success', 'The user was deleted successfully.');
            } else {
                setFlash('error', 'The selected user could not be found or was already removed.');
            }
            header('Location: staff.php');
            exit;
        } catch (Throwable $e) {
            setFlash('error', 'The user could not be deleted. Please try again.');
            header('Location: staff.php');
            exit;
        }
    }

    if ($action === 'edit_user') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$targetId) {
            setFlash('error', 'No user was selected for editing.');
            header('Location: staff.php');
            exit;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $idNumber = trim((string)($_POST['id_number'] ?? ''));
        $identityType = (string)($_POST['identity_type'] ?? '');
        $phone = trim((string)($_POST['phone'] ?? ''));
        $staffNumber = trim((string)($_POST['staff_number'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $module = (string)($_POST['module'] ?? '');
        $role = (string)($_POST['role'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        try {
            $existing = $pdo->prepare('SELECT * FROM module_users WHERE user_id = ? LIMIT 1');
            $existing->execute([$targetId]);
            $userRecord = $existing->fetch();
            if (!$userRecord) {
                throw new RuntimeException('The selected user could not be found.');
            }

            if ($username === '' || $fullName === '' || !validIdentityNumber($idNumber, $identityType) || !validPhoneNumber($phone) || $staffNumber === '' || !isset($moduleRoles[$module][$role])) {
                throw new RuntimeException('Please complete every required field, choose a valid module and role, and use a valid ID number and 10-digit phone number.');
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('The email address is invalid. Please enter a valid email or leave it blank.');
            }

            $duplicate = $pdo->prepare('SELECT user_id FROM module_users WHERE username = ? AND user_id != ? LIMIT 1');
            $duplicate->execute([$username, $targetId]);
            if ($duplicate->fetch()) {
                throw new RuntimeException('That username is already in use. Please choose a different username.');
            }

            $sql = 'UPDATE module_users SET username = ?, full_name = ?, id_type = ?, id_number = ?, phone = ?, staff_number = ?, module = ?, role = ?, email = ?';
            $params = [$username, $fullName, $identityType, $idNumber, $phone, $staffNumber, $module, $role, $email !== '' ? $email : null];
            if ($password !== '') {
                if (strlen($password) < 8) {
                    throw new RuntimeException('The new password must be at least 8 characters long.');
                }
                $sql .= ', password_hash = ?';
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE user_id = ?';
            $params[] = $targetId;

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);

            setFlash('success', 'User details updated successfully for ' . $username . '.');
            header('Location: staff.php');
            exit;
        } catch (Throwable $e) {
            setFlash('error', $e->getMessage());
            header('Location: staff.php?edit_user_id=' . (int)$targetId);
            exit;
        }
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $idNumber = trim((string)($_POST['id_number'] ?? ''));
    $identityType = (string)($_POST['identity_type'] ?? '');
    $phone = trim((string)($_POST['phone'] ?? ''));
    $staffNumber = trim((string)($_POST['staff_number'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $module = (string)($_POST['module'] ?? '');
    $role = (string)($_POST['role'] ?? '');
    if ($username === '' || $fullName === '' || !validIdentityNumber($idNumber, $identityType) || !validPhoneNumber($phone) || $staffNumber === '' || strlen($password) < 8 || !isset($moduleRoles[$module][$role])) {
        setFlash('error', 'Please complete every required field, choose a valid module and role, and use a valid ID number and 10-digit phone number.');
        header('Location: staff.php');
        exit;
    }

    try {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('The email address is invalid. Please enter a valid email or leave it blank.');
        }

        $check = $pdo->prepare('SELECT username FROM module_users WHERE username=? UNION SELECT username FROM admin_users WHERE username=? LIMIT 1');
        $check->execute([$username, $username]);
        if ($check->fetch()) {
            throw new RuntimeException('That username is already in use. Please choose a different username.');
        }

        $stmt = $pdo->prepare('INSERT INTO module_users (username,password_hash,full_name,id_type,id_number,phone,staff_number,module,role,email) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $identityType, $idNumber, $phone, $staffNumber, $module, $role, $email !== '' ? $email : null]);

        setFlash('success', 'Account created. Login username: ' . $username . ' | Module: ' . ucfirst($module) . ' | Role: ' . $moduleRoles[$module][$role] . '. Provide the password you entered to the user.');
        header('Location: staff.php');
        exit;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        if (str_contains($errorMessage, 'uq_module_username') || (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'username'))) {
            $errorMessage = 'That username is already in use. Please choose a different one.';
        } elseif (str_contains($errorMessage, 'uq_admin_role_slot') || str_contains($errorMessage, 'admin_role_slot')) {
            $errorMessage = 'This admin role is already assigned. Please choose a different role or update the existing account.';
        } else {
            $errorMessage = 'The account could not be created. Please review the details and try again.';
        }
        setFlash('error', $errorMessage);
        header('Location: staff.php');
        exit;
    }
}

$users = $pdo->query("SELECT admin_id, username, full_name, role, email, status, created_at FROM admin_users ORDER BY created_at DESC")->fetchAll();
$moduleUsers = $pdo->query("SELECT user_id, username, full_name, id_type, id_number, phone, staff_number, module, role, email, status, created_at FROM module_users WHERE module IN ('admin','finance','dean') ORDER BY created_at DESC")->fetchAll();
$staffDirectory = $pdo->query("SELECT admin_id, full_name, username, role, status FROM admin_users ORDER BY full_name")->fetchAll();
$editUserId = filter_input(INPUT_GET, 'edit_user_id', FILTER_VALIDATE_INT);
$editUser = null;
if ($editUserId) {
    $editStmt = $pdo->prepare('SELECT * FROM module_users WHERE user_id = ? LIMIT 1');
    $editStmt->execute([$editUserId]);
    $editUser = $editStmt->fetch();
}
$selectedStaffId = filter_input(INPUT_GET, 'staff_id', FILTER_VALIDATE_INT);
$selectedStaff = null;
if ($selectedStaffId) {
    foreach ($staffDirectory as $staff) {
        if ((int)$staff['admin_id'] === (int)$selectedStaffId) {
            $selectedStaff = $staff;
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
    <title>Staff Accounts | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:block; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #e5e7eb; padding:10px 12px; text-align:left; }
        th { background:#f8fafc; }
        .badge { padding:4px 8px; border-radius:999px; font-size:11px; font-weight:700; }
        .badge-admin { background:#dbeafe; color:#1d4ed8; }
        .badge-staff { background:#e5e7eb; color:#374151; }
        .badge-super { background:#fef3c7; color:#92400e; }
        .account-form { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:18px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
        .account-form input,.account-form select { width:100%; box-sizing:border-box; padding:10px; border:1px solid #cbd5e0; border-radius:6px; }
        .account-form button { background:#1e3d73; color:#fff; border:0; border-radius:6px; padding:10px 16px; font-weight:700; cursor:pointer; }
        .clear-button { background:#e2e8f0 !important; color:#1e293b !important; }
        .password-field { position:relative; }
        .password-field input { padding-right:40px; }
        .password-toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:#64748b; cursor:pointer; padding:4px; }
        .toast-container { position:fixed; top:18px; left:50%; transform:translateX(-50%); width:min(560px, calc(100% - 24px)); z-index:1200; pointer-events:none; }
        .toast {
            display:flex; align-items:center; justify-content:space-between; gap:12px; opacity:0; visibility:hidden; transform:translateY(-12px); transition:opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease;
            background:#fff; border-radius:10px; padding:14px 16px; box-shadow:0 12px 30px rgba(15, 23, 42, 0.18); border:1px solid #e2e8f0; border-left:6px solid #64748b; font-weight:600; color:#1f2937; pointer-events:auto;
        }
        .toast.show { opacity:1; visibility:visible; transform:translateY(0); }
        .toast.success { border-left-color:#16a34a; background:#ecfdf5; color:#166534; }
        .toast.error { border-left-color:#dc2626; background:#fef2f2; color:#991b1b; }
        @media(max-width:800px){.account-form{grid-template-columns:1fr}}
        @media(max-width:700px){.sidebar{position:static;width:100%;min-height:auto;transform:none}.main{margin-left:0;width:100%}.content{padding:20px}.topbar{padding:0 18px}table{display:block;overflow-x:auto;white-space:nowrap}}
    </style>
</head>
<body>
<?php if ($message): ?>
<div class="toast-container">
    <div id="pageToast" class="toast <?= htmlspecialchars($messageClass === 'alert-success' ? 'success' : 'error') ?> show" role="alert" aria-live="assertive">
        <span><?= htmlspecialchars($message) ?></span>
    </div>
</div>
<?php endif; ?>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Staff Accounts</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Staff Accounts</h1>
            <p class="text-muted">Manage admin and staff user accounts for the ERP.</p>
            <?php if ($message): ?><div class="alert <?=htmlspecialchars($messageClass)?>"><?=htmlspecialchars($message)?></div><?php endif; ?>

            <div style="margin-top:18px; margin-bottom:12px;">
                <label for="staffDirectorySelector" style="display:block; margin-bottom:8px; font-size: 14px; font-weight: 600; color: #4a5568;">Staff directory</label>
                <select id="staffDirectorySelector" style="width:100%; max-width:420px; padding:10px 12px; border:1px solid #cbd5e0; border-radius:6px; background:#fff; font-size:14px;" onchange="window.location.href = 'staff.php?staff_id=' + this.value">
                    <option value="">Select a staff account</option>
                    <?php foreach ($staffDirectory as $staff): ?>
                        <option value="<?= (int)$staff['admin_id'] ?>" <?= $selectedStaffId && (int)$staff['admin_id'] === (int)$selectedStaffId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($staff['full_name']) ?> (<?= htmlspecialchars($staff['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="staffDirectoryDetail" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; color:#4a5568; min-height:70px; display:flex; align-items:center;">
                <?php if ($selectedStaff): ?>
                    <strong><?= htmlspecialchars($selectedStaff['full_name']) ?></strong><br>
                    Username: <?= htmlspecialchars($selectedStaff['username']) ?><br>
                    Role: <?= htmlspecialchars($selectedStaff['role']) ?><br>
                    Status: <?= htmlspecialchars($selectedStaff['status']) ?>
                <?php else: ?>
                    Select a staff member to view their account details.
                <?php endif; ?>
            </div>

            <?php if ($isSuperAdmin): ?>
                <?php if ($editUser): ?>
                    <h2 style="margin:20px 0 0; font-size:16px;">Edit System User</h2>
                    <p class="text-muted">Update the selected system account. Leave the password blank to keep the current password.</p>
                    <form method="post" class="account-form">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" value="<?= (int)$editUser['user_id'] ?>">
                        <input name="full_name" placeholder="Full name" value="<?= htmlspecialchars((string)($editUser['full_name'] ?? '')) ?>" required>
                        <select name="identity_type" required>
                            <option value="">ID type</option>
                            <option value="national_id" <?= ($editUser['id_type'] ?? '') === 'national_id' ? 'selected' : '' ?>>National ID (8 digits)</option>
                            <option value="maisha_card" <?= ($editUser['id_type'] ?? '') === 'maisha_card' ? 'selected' : '' ?>>Maisha Card (9 digits)</option>
                        </select>
                        <input name="id_number" inputmode="numeric" pattern="\d{1,9}" maxlength="9" placeholder="Digits only; max 8 National ID / 9 Maisha Card" value="<?= htmlspecialchars((string)($editUser['id_number'] ?? '')) ?>" required>
                        <input name="phone" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" placeholder="Phone number (10 digits)" value="<?= htmlspecialchars((string)($editUser['phone'] ?? '')) ?>" required>
                        <input name="staff_number" placeholder="Staff number" value="<?= htmlspecialchars((string)($editUser['staff_number'] ?? '')) ?>" required>
                        <input name="username" placeholder="Username" value="<?= htmlspecialchars((string)($editUser['username'] ?? '')) ?>" required autocomplete="off">
                        <input name="email" type="email" placeholder="Email (optional)" value="<?= htmlspecialchars((string)($editUser['email'] ?? '')) ?>">
                        <div class="password-field"><input id="systemPassword" name="password" type="password" minlength="8" placeholder="New password (optional)"><button type="button" class="password-toggle" onclick="togglePassword('systemPassword', this)" aria-label="Show password" title="Show password">◉</button></div>
                        <select name="module" id="systemModule" required onchange="updateSystemRoles()">
                            <option value="">Choose module</option>
                            <option value="admin" <?= ($editUser['module'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="finance" <?= ($editUser['module'] ?? '') === 'finance' ? 'selected' : '' ?>>Finance</option>
                            <option value="dean" <?= ($editUser['module'] ?? '') === 'dean' ? 'selected' : '' ?>>Dean</option>
                        </select>
                        <select name="role" id="systemRole" required>
                            <option value="">Choose role</option>
                            <?php foreach (($moduleRoles[$editUser['module'] ?? ''] ?? []) as $roleValue => $roleLabel): ?>
                                <option value="<?= htmlspecialchars($roleValue) ?>" <?= ($editUser['role'] ?? '') === $roleValue ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="staff.php" class="button-like clear-button" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">Cancel</a>
                        <button type="submit">Save Changes</button>
                    </form>
                <?php else: ?>
                    <h2 style="margin:20px 0 0; font-size:16px;">Create System User</h2>
                    <p class="text-muted">Create Admin, Finance, or Dean login credentials. Students register from the public login page.</p>
                    <form method="post" class="account-form">
                        <input name="full_name" placeholder="Full name" required>
                        <select name="identity_type" required><option value="">ID type</option><option value="national_id">National ID (8 digits)</option><option value="maisha_card">Maisha Card (9 digits)</option></select>
                        <input name="id_number" inputmode="numeric" pattern="\d{1,9}" maxlength="9" placeholder="Digits only; max 8 National ID / 9 Maisha Card" required>
                        <input name="phone" type="tel" inputmode="numeric" pattern="\d{10}" maxlength="10" placeholder="Phone number (10 digits)" required>
                        <input name="staff_number" placeholder="Staff number" required>
                        <input name="username" placeholder="Username" required autocomplete="off">
                        <input name="email" type="email" placeholder="Email (optional)">
                        <div class="password-field"><input id="systemPassword" name="password" type="password" minlength="8" placeholder="Temporary password" required><button type="button" class="password-toggle" onclick="togglePassword('systemPassword', this)" aria-label="Show password" title="Show password">◉</button></div>
                        <select name="module" id="systemModule" required onchange="updateSystemRoles()"><option value="">Choose module</option><option value="admin">Admin</option><option value="finance">Finance</option><option value="dean">Dean</option></select>
                        <select name="role" id="systemRole" required><option value="">Choose role</option></select>
                        <button type="reset" class="clear-button">Clear</button>
                        <button type="submit">Create Login</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <span class="badge badge-<?= $user['role'] === 'super_admin' ? 'super' : ($user['role'] === 'admin' ? 'admin' : 'staff') ?>"><?= htmlspecialchars($user['role']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($user['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($moduleUsers): ?><h2 style="margin-top:28px; font-size:16px;">System Module Logins</h2><table><thead><tr><th>Name</th><th>ID Type</th><th>ID Number</th><th>Phone</th><th>Staff Number</th><th>Username</th><th>Module</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($moduleUsers as $moduleUser): ?><tr><td><?=htmlspecialchars($moduleUser['full_name'])?></td><td><?=htmlspecialchars($moduleUser['id_type'] === 'maisha_card' ? 'Maisha Card' : 'National ID')?></td><td><?=htmlspecialchars($moduleUser['id_number'])?></td><td><?=htmlspecialchars($moduleUser['phone'])?></td><td><?=htmlspecialchars($moduleUser['staff_number'])?></td><td><?=htmlspecialchars($moduleUser['username'])?></td><td><?=htmlspecialchars(ucfirst($moduleUser['module']))?></td><td><?=htmlspecialchars($moduleUser['role'])?></td><td><?=htmlspecialchars($moduleUser['status'])?></td><td><div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;"><a href="staff.php?edit_user_id=<?= (int)$moduleUser['user_id'] ?>" class="button-link" style="background:#e2e8f0;color:#1e293b;padding:6px 10px;border-radius:6px;text-decoration:none;">Edit</a><form method="post" onsubmit="return confirm('Delete this user? This action cannot be undone.');" style="display:inline; margin:0;"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$moduleUser['user_id'] ?>"><button type="submit" style="background:#dc2626;color:#fff;border:0;border-radius:6px;padding:6px 10px;cursor:pointer;">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
        </div>
    </main>
</div>
<?php if ($isSuperAdmin): ?><script>
const systemRoles = <?=json_encode($moduleRoles, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
function updateSystemRoles(){const module=document.getElementById('systemModule').value;const role=document.getElementById('systemRole');role.innerHTML='<option value="">Choose role</option>';Object.entries(systemRoles[module]||{}).forEach(([value,label])=>{const option=document.createElement('option');option.value=value;option.textContent=label;role.appendChild(option);});}
function togglePassword(id, button){const input=document.getElementById(id);const visible=input.type==='text';input.type=visible?'password':'text';button.textContent=visible?'◉':'◎';button.setAttribute('aria-label',visible?'Show password':'Hide password');button.title=visible?'Show password':'Hide password';}
</script><?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toast = document.getElementById('pageToast');
    if (!toast) return;
    setTimeout(function () {
        toast.classList.remove('show');
    }, 5000);
});
</script>
</body>
</html>
