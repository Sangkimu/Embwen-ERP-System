<?php
require_once "config.php";
if(isset($_SESSION['user'])){ header("Location: ".$_SESSION['user']['home']); exit; }
$mode=$_GET['mode']??'login';
$error=''; $success='';
$adminRoles=['super_admin'=>'Super Administrator','admin'=>'Administrator','staff'=>'Staff'];
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
  $role=$module==='admin'?($_POST['role']??''):($module==='students'?'student':'');
    if($name===''||$username===''||strlen($password)<8||!isset($homes[$module])||($module==='admin'&&!isset($availableAdminRoles[$role]))||($module==='students'&&$role!=='student')){
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
  $q=$pdo->prepare("SELECT * FROM module_users WHERE username=? AND status='active' LIMIT 1");
  $q->execute([$username]); $account=$q->fetch();
  if($account && password_verify($password,$account['password_hash'])){
   $_SESSION['user']=['id'=>$account['user_id'],'name'=>$account['full_name'],'username'=>$account['username'],'module'=>$account['module'],'role'=>$account['role'],'home'=>$homes[$account['module']]];
   header('Location: '.$homes[$account['module']]); exit;
  }
  $error='Invalid username or password.';
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=ucfirst($mode)?> | College ERP</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page"><div class="login-card"><div class="brand center"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small>Management System</small></div></div>
<?php if($mode==='register'):?>
<h1>Create account</h1><p class="muted">Register once, then use your username and password to sign in.</p>
<?php else:?>
<h1>Welcome back</h1><p class="muted">Sign in to access your department workspace.</p>
<?php endif;?>
<?php if($error):?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=htmlspecialchars($success)?></div><?php endif;?>
<?php if($mode==='register'):?>
<form method="post"><input type="hidden" name="mode" value="register"><label>Account type<select name="module" id="module" onchange="toggleRegistrationFields()" required><option value="">Choose account type</option><?php if($availableAdminRoles):?><option value="admin">Admin module</option><?php endif;?><option value="students">Student</option></select></label><label>Full name<input name="full_name" required autocomplete="name"></label><label>Username<input name="username" required autocomplete="username"></label><label>Email <span class="muted">(optional)</span><input type="email" name="email" autocomplete="email"></label><label id="admin-role">Admin role<select name="role"><?php foreach($availableAdminRoles as $value=>$label):?><option value="<?=htmlspecialchars($value)?>"><?=htmlspecialchars($label)?></option><?php endforeach;?></select></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><button class="primary full">Create account</button></form><div class="login-note">Each admin role can be registered once. Student accounts use unique usernames.</div><p class="auth-switch">Already registered? <a href="login.php">Sign in</a></p>
<?php else:?>
<form method="post"><input type="hidden" name="mode" value="login"><label>Username<input name="username" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="primary full">Sign in</button></form><div class="login-note">Your account determines the module dashboard and services you can access.</div><p class="auth-switch">First time here? <a href="login.php?mode=register">Create an account</a></p>
<?php endif;?>
</div><script>function toggleRegistrationFields(){var module=document.getElementById('module').value;document.getElementById('admin-role').style.display=module==='admin'?'block':'none';}toggleRegistrationFields();</script></body></html>
