<?php
require_once "config.php"; // Load session managers and database connection configurations

// Native PHP Session parameter safeguard: 
// Checks if the user is already authenticated. If yes, it completely bypasses the gate.
if (isset($_SESSION['user']['home'])) {
    header("Location: " . $_SESSION['user']['home']);
    exit;
}
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    $_SESSION['user'] = [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $_SESSION['name'] ?? $_SESSION['username'],
        'username' => $_SESSION['username'] ?? '',
        'module'   => 'admin',
        'role'     => $_SESSION['role'] ?? 'admin',
        'home'     => 'admin/index.php'
    ];
    header("Location: admin/index.php");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Query the admin users table using modern prepared statements
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verify the Argon2/Bcrypt password hash safely (Never store plaintext passwords)
        if ($user && password_verify($password, $user['password_hash'])) {
            // Establish session keys matching your config requirements
            $_SESSION['user_id']   = $user['admin_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['name']      = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['module']    = 'admin';
            $_SESSION['user']      = [
                'id'       => $user['admin_id'],
                'name'     => $user['full_name'],
                'username' => $user['username'],
                'module'   => 'admin',
                'role'     => $user['role'],
                'home'     => 'admin/index.php'
            ];
            
            header("Location: admin/index.php");
            exit;
        } else {
            $error = "Invalid credential combinations or account is deactivated.";
        }
    } else {
        $error = "Please fill in all identification fields.";
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>College ERP - Secure Sign In</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 100%; max-width: 400px; box-sizing: border-box; border: 1px solid #e2e8f0; }
        .brand-header { text-align: center; margin-bottom: 30px; }
        .brand-header h2 { margin: 0; color: #1e3d73; font-size: 24px; }
        .brand-header p { margin: 5px 0 0 0; color: #718096; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #4a5568; margin-bottom: 6px; letter-spacing: 0.5px; }
        .form-control { padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; background: #f7fafc; }
        .password-field { position: relative; display: flex; }
        .password-field .form-control { width: 100%; padding-right: 62px; }
        .password-toggle { position: absolute; top: 1px; right: 1px; bottom: 1px; border: 0; background: transparent; color: #1e3d73; cursor: pointer; font-size: 11px; font-weight: 700; padding: 0 12px; }
        .form-control:focus { border-color: #1e3d73; outline: none; background: #fff; }
        .btn-submit { background: #1e3d73; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 700; width: 100%; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; font-size: 14px; margin-top: 10px; transition: background 0.2s; }
        .btn-submit:hover { background: #162e58; }
        .error-banner { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; text-align: center; font-weight: 500; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <h2>Vocational College ERP</h2>
        <p>Internal Operations Gateway</p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="error-banner"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label>Username Registry Key</label>
            <input type="text" class="form-control" name="username" placeholder="e.g., aron.sang" required>
        </div>

        <div class="form-group">
            <label>Account Password</label>
            <div class="password-field">
                <input id="gateway-password" type="password" class="form-control" name="password" placeholder="••••••••" required>
                <button type="button" class="password-toggle" data-password-target="gateway-password" onclick="togglePasswordVisibility(this)" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
        </div>

        <button type="submit" class="btn-submit">Secure Authorization Login</button>
    </form>
</div>
<script src="assets/js/app.js"></script>

</body>
</html>
