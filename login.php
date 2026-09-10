<?php
require_once "config.php";
if(isset($_SESSION['user'])){ header("Location: ".$_SESSION['user']['home']); exit; }
$mode=$_GET['mode']??'login';
$error=''; $success='';
$adminRoles=['super_admin'=>'Super Administrator','admin'=>'Administrator','staff'=>'Staff'];
$moduleRoles=[
 'admin'=>$adminRoles,
 'finance'=>['finance_manager'=>'Finance Manager','finance_officer'=>'Finance Officer','cashier'=>'Cashier'],
 'dean'=>['dean'=>'Dean','admissions_officer'=>'Admissions Officer','welfare_officer'=>'Welfare Officer','registrar'=>'Registrar'],
 'students'=>['student'=>'Student']
];
$homes=['admin'=>'admin/index.php','finance'=>'finance/index.php','dean'=>'dean/index.php','students'=>'student/index.php'];
$availableAdminRoles=$adminRoles;
$roleQuery=$pdo->query("SELECT role FROM module_users WHERE module='admin'");
foreach($roleQuery as $registeredRole){ unset($availableAdminRoles[$registeredRole['role']]); }

if($_SERVER['REQUEST_METHOD']==='POST'){
 $mode=$_POST['mode']??'login';
 $username=trim($_POST['username']??'');
 $password=$_POST['password']??'';
 if($mode==='register'){
  $name=trim($_POST['full_name']??'');
  $email=trim($_POST['email']??'');
  $module=$_POST['module']??'';
  $role=$_POST['role']??'';
    if($name===''||$username===''||strlen($password)<8||!isset($homes[$module])||!isset($moduleRoles[$module][$role])||($module==='admin'&&!isset($availableAdminRoles[$role]))){
   $error='Complete all fields. Passwords must contain at least 8 characters.';
  } else {
   $check=$pdo->prepare('SELECT user_id FROM module_users WHERE username=? LIMIT 1');
   $check->execute([$username]);
   if($check->fetch()) $error='That username is already in use.';
   elseif($module==='admin'){
    $check=$pdo->prepare("SELECT user_id FROM module_users WHERE module='admin' AND role=? LIMIT 1");
    $check->execute([$role]);
    if($check->fetch()) $error='That admin role has already been registered. Please sign in instead.';
   }
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
    'module'=>$module,
    'role'=>$account['role'] ?? 'admin',
    'home'=>$home
   ];
   header('Location: '.$home); exit;
  }
  $error='Invalid username or password.';
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=ucfirst($mode)?> | College ERP</title><link rel="stylesheet" href="assets/css/style.css"><style>
.auth-shell{width:min(940px,100%);display:grid;grid-template-columns:.9fr 1.1fr;background:#fff;border:1px solid #e4e9f1;border-radius:20px;overflow:hidden;box-shadow:0 24px 70px rgba(16,43,97,.13)}.auth-intro{padding:48px 40px;background:linear-gradient(145deg,#102b61,#174a9b);color:#fff;display:flex;flex-direction:column;justify-content:space-between;min-height:590px}.auth-intro .brand{padding:0 0 30px}.auth-intro .brand small{color:#b8c9e5}.auth-intro h2{font-size:36px;line-height:1.05;margin:0 0 17px;letter-spacing:-1.5px}.auth-intro p{color:#c8d6ec;line-height:1.6;font-size:14px;margin:0}.auth-points{margin:30px 0 0;padding:0;list-style:none;color:#dce7fb;font-size:13px;line-height:2.3}.auth-points li:before{content:'✓';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:9px;border-radius:50%;background:#fff;color:#174a9b;font-weight:700}.auth-form{padding:42px 44px}.auth-form .brand{display:none}.auth-form h1{margin:0 0 8px}.auth-form .muted{margin:0 0 25px;font-size:13px}.auth-tabs{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:4px;background:#f1f4f9;border-radius:10px;margin-bottom:25px}.auth-tabs a{padding:10px;text-align:center;border-radius:7px;font-size:13px;font-weight:700;color:#78849a}.auth-tabs a.active{background:#fff;color:#174a9b;box-shadow:0 2px 8px rgba(16,43,97,.08)}.auth-form form{display:grid;gap:14px}.auth-form label{font-size:12px;font-weight:700;color:#3e4d64}.auth-form input,.auth-form select{margin-top:6px}.role-hint{font-size:11px;color:#78849a;margin:-4px 0 2px}.auth-switch{font-size:12px;text-align:center;color:#78849a}.auth-switch a{color:#174a9b;font-weight:700}.auth-form .login-note{margin-top:18px;text-align:center}.auth-form .alert{margin-bottom:16px}@media(max-width:700px){.auth-shell{display:block;border-radius:14px}.auth-intro{min-height:auto;padding:30px 25px}.auth-intro h2{font-size:29px}.auth-points{display:none}.auth-form{padding:30px 25px}.login-page{padding:12px}}
</style><style>.auth-shell{display:block;max-width:500px}</style></head>
<body class="login-page"><div class="auth-shell"><section class="auth-form"><div class="brand center"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small>Management System</small></div></div>
<div class="auth-tabs"><a class="<?= $mode==='register'?'':'active' ?>" href="login.php">Sign in</a><a class="<?= $mode==='register'?'active':'' ?>" href="login.php?mode=register">Create account</a></div>
<?php if($mode==='register'):?>
<h1>Create account</h1><p class="muted">Register once, then use your username and password to sign in.</p>
<?php else:?>
<h1>Welcome back</h1><p class="muted">Sign in to access your department workspace.</p>
<?php endif;?>
<?php if($error):?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=htmlspecialchars($success)?></div><?php endif;?>
<?php if($mode==='register'):?>
<form method="post"><input type="hidden" name="mode" value="register"><label>Account type<select name="module" id="module" onchange="toggleRegistrationFields()" required><option value="">Choose account type</option><?php foreach($moduleRoles as $moduleValue=>$roles):?><?php if($moduleValue!=='admin'||$availableAdminRoles):?><option value="<?=htmlspecialchars($moduleValue)?>"><?=htmlspecialchars($moduleValue==='students'?'Student':ucfirst($moduleValue))?></option><?php endif;?><?php endforeach;?></select></label><label>Full name<input name="full_name" required autocomplete="name"></label><label>Username<input name="username" required autocomplete="username"></label><label>Email <span class="muted">(optional)</span><input type="email" name="email" autocomplete="email"></label><label id="registration-role">Role<select name="role" id="role" required><option value="">Choose role</option><?php foreach($moduleRoles as $moduleValue=>$roles): foreach($roles as $value=>$label): if($moduleValue!=='admin'||isset($availableAdminRoles[$value])):?><option data-module="<?=htmlspecialchars($moduleValue)?>" value="<?=htmlspecialchars($value)?>"><?=htmlspecialchars($label)?></option><?php endif; endforeach; endforeach;?></select></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><button class="primary full">Create account</button></form><div class="login-note">Admin roles can be registered once. Finance, Dean and Student accounts use their selected role.</div><p class="auth-switch">Already registered? <a href="login.php">Sign in</a></p>
<?php else:?>
<form method="post"><input type="hidden" name="mode" value="login"><label>Username<input name="username" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="primary full">Sign in securely</button></form><div class="login-note">Your account automatically opens its assigned dashboard.</div><p class="auth-switch">First time here? <a href="login.php?mode=register">Create an account</a></p>
<?php endif;?>
</section></div><script>function toggleRegistrationFields(){var module=document.getElementById('module');var role=document.getElementById('role');if(!module||!role){return;}Array.from(role.options).forEach(function(option){option.hidden=option.value!==''&&option.dataset.module!==module.value;});role.value='';}toggleRegistrationFields();</script></body></html>
