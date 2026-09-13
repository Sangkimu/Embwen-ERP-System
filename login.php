<?php
require_once "config.php";
if(!defined('ERP_ENTRY_POINT')){
 $mode=$_GET['mode']??'login';
 $redirect = 'index.php' . ($mode==='register' ? '?mode=register' : '');
 appRedirectPath($redirect);
}
$homes=['admin'=>'admin/index.php','finance'=>'finance/index.php','dean'=>'dean/index.php','students'=>'student/index.php'];
if(isset($_SESSION['user'])){
 $home=$_SESSION['user']['home']??($homes[$_SESSION['user']['module']??'']??null);
 if($home!==null){ appRedirectPath($home); }
 unset($_SESSION['user']);
}
$mode=$_GET['mode']??'login';
$error=''; $success='';
$moduleRoles=['students'=>['student'=>'Student']];

if($_SERVER['REQUEST_METHOD']==='POST'){
 $mode=$_POST['mode']??'login';
 $username=trim($_POST['username']??'');
 $password=$_POST['password']??'';
 if($mode==='register'){
  $name=trim($_POST['full_name']??'');
  $email=trim($_POST['email']??'');
  $module='students';
  $role='student';
    if($name===''||$username===''||strlen($password)<8){
   $error='Complete all fields. Passwords must contain at least 8 characters.';
  } else {
   $check=$pdo->prepare('SELECT user_id FROM module_users WHERE username=? LIMIT 1');
   $check->execute([$username]);
   if($check->fetch()) $error='That username is already in use.';
   if($error===''){
    try{
     $stmt=$pdo->prepare('INSERT INTO module_users (username,password_hash,full_name,module,role,email) VALUES (?,?,?,?,?,?)');
     $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT),$name,$module,$role,$email!==''?$email:null]);
     $success='Account created. You can now sign in.'; $mode='login';
    }catch(PDOException $e){ $error='This account or admin role is already registered.'; }
   }
  }
 } else {
  $account=null; $module='';
  $q=$pdo->prepare("SELECT * FROM module_users WHERE username=? AND status='active' LIMIT 1");
  $q->execute([$username]);
  $account=$q->fetch();

  if(!$account){
   $legacy=$pdo->prepare("SELECT * FROM admin_users WHERE username=? AND status='active' LIMIT 1");
   $legacy->execute([$username]);
   $account=$legacy->fetch();
   $module='admin';
  } else {
   $module=strtolower(trim((string)($account['module'] ?? '')));
  }

  if($account && password_verify($password, $account['password_hash'] ?? '') && isset($homes[$module])){
   $home=$module==='admin'?'admin/index.php':$homes[$module];
   $_SESSION['user']=[
    'id'=>$account['user_id'] ?? $account['admin_id'],
    'name'=>$account['full_name'],
    'username'=>$account['username'],
    'email'=>$account['email'] ?? '',
    'module'=>$module,
    'role'=>$account['role'] ?? 'admin',
    'home'=>$home
   ];
   appRedirectPath($home);
  }
  $error='Invalid username or password.';
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>"><title><?=ucfirst($mode)?> | College ERP</title><link rel="stylesheet" href="assets/css/style.css"><style>
.auth-shell{width:min(940px,100%);display:grid;grid-template-columns:.9fr 1.1fr;background:#fff;border:1px solid #e4e9f1;border-radius:20px;overflow:hidden;box-shadow:0 24px 70px rgba(16,43,97,.13)}.auth-intro{padding:48px 40px;background:linear-gradient(145deg,#102b61,#174a9b);color:#fff;display:flex;flex-direction:column;justify-content:space-between;min-height:590px}.auth-intro .brand{padding:0 0 30px}.auth-intro .brand small{color:#b8c9e5}.auth-intro h2{font-size:36px;line-height:1.05;margin:0 0 17px;letter-spacing:-1.5px}.auth-intro p{color:#c8d6ec;line-height:1.6;font-size:14px;margin:0}.auth-points{margin:30px 0 0;padding:0;list-style:none;color:#dce7fb;font-size:13px;line-height:2.3}.auth-points li:before{content:'✓';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:9px;border-radius:50%;background:#fff;color:#174a9b;font-weight:700}.auth-form{padding:42px 44px}.auth-form .brand{display:none}.auth-form h1{margin:0 0 8px}.auth-form .muted{margin:0 0 25px;font-size:13px}.auth-tabs{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:4px;background:#f1f4f9;border-radius:10px;margin-bottom:25px}.auth-tabs a{padding:10px;text-align:center;border-radius:7px;font-size:13px;font-weight:700;color:#78849a}.auth-tabs a.active{background:#fff;color:#174a9b;box-shadow:0 2px 8px rgba(16,43,97,.08)}.auth-form form{display:grid;gap:14px}.auth-form label{font-size:12px;font-weight:700;color:#3e4d64}.auth-form input,.auth-form select{margin-top:6px}.role-hint{font-size:11px;color:#78849a;margin:-4px 0 2px}.auth-switch{font-size:12px;text-align:center;color:#78849a}.auth-switch a{color:#174a9b;font-weight:700}.auth-form .login-note{margin-top:18px;text-align:center}.auth-form .alert{margin-bottom:16px}@media(max-width:700px){.auth-shell{display:block;border-radius:14px}.auth-intro{min-height:auto;padding:30px 25px}.auth-intro h2{font-size:29px}.auth-points{display:none}.auth-form{padding:30px 25px}.login-page{padding:12px}}
</style><style>.auth-shell{display:block;max-width:500px}.password-field{position:relative}.password-field input{width:100%;padding-right:42px;box-sizing:border-box}.password-toggle{position:absolute;right:10px;bottom:8px;border:0;background:transparent;color:#78849a;cursor:pointer;font-size:16px;padding:4px}.password-toggle:focus{outline:2px solid #174a9b;outline-offset:2px}</style></head>
<body class="login-page"><div class="auth-shell"><section class="auth-form"><div class="brand center"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small>Management System</small></div></div>
<div class="auth-tabs"><a class="<?= $mode==='register'?'':'active' ?>" href="index.php">Sign in</a><a class="<?= $mode==='register'?'active':'' ?>" href="index.php?mode=register">Create account</a></div>
<?php if($mode==='register'):?>
<h1>Create account</h1><p class="muted">Register once, then use your username and password to sign in.</p>
<?php else:?>
<h1>Welcome back</h1><p class="muted">Sign in to access your department workspace.</p>
<?php endif;?>
<?php if($error):?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=htmlspecialchars($success)?></div><?php endif;?>
<?php if($mode==='register'):?>
<form method="post"><input type="hidden" name="mode" value="register"><label>Full name<input name="full_name" required autocomplete="name"></label><label>Username<input name="username" required autocomplete="username"></label><label>Email <span class="muted">(optional)</span><input type="email" name="email" autocomplete="email"></label><label>Password<div class="password-field"><input id="registerPassword" type="password" name="password" required minlength="8" autocomplete="new-password"><button type="button" class="password-toggle" onclick="togglePassword('registerPassword', this)" aria-label="Show password" title="Show password">◉</button></div></label><button class="primary full">Create student account</button></form><div class="login-note">Only student accounts can be created here. Admin, Finance and Dean accounts are created by the Super Admin.</div><p class="auth-switch">Already registered? <a href="index.php">Sign in</a></p>
<?php else:?>
<form method="post"><input type="hidden" name="mode" value="login"><label>Username<input name="username" required autocomplete="username"></label><label>Password<div class="password-field"><input id="loginPassword" type="password" name="password" required autocomplete="current-password"><button type="button" class="password-toggle" onclick="togglePassword('loginPassword', this)" aria-label="Show password" title="Show password">◉</button></div></label><button class="primary full">Sign in securely</button></form><div class="login-note">Your account automatically opens its assigned dashboard.</div><p class="auth-switch">First time here? <a href="index.php?mode=register">Create an account</a></p>
<?php endif;?>
</section></div><script>function togglePassword(id, button){var input=document.getElementById(id);var visible=input.type==='text';input.type=visible?'password':'text';button.textContent=visible?'◉':'◎';button.setAttribute('aria-label',visible?'Show password':'Hide password');button.title=visible?'Show password':'Hide password';}</script></body></html>
