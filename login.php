<?php
require_once "config.php";
if(isset($_SESSION['user'])){ header("Location: ".$_SESSION['user']['home']); exit; }
$error="";
if($_SERVER["REQUEST_METHOD"]==="POST"){
 $u=trim($_POST["username"]); $p=$_POST["password"];
 $q=$pdo->prepare("SELECT * FROM module_users WHERE username=? AND status='active' LIMIT 1");
 $q->execute([$u]); $x=$q->fetch();
 if($x && password_verify($p,$x["password_hash"])){
   $homes=['admin'=>'admin/index.php','finance'=>'finance/index.php','dean'=>'dean/index.php','students'=>'student/index.php'];
   $_SESSION['user']=['id'=>$x['user_id'],'name'=>$x['full_name'],'username'=>$x['username'],'module'=>$x['module'],'role'=>$x['role'],'home'=>$homes[$x['module']]];
   header("Location: ".$homes[$x['module']]); exit;
 }
 $error="Invalid username or password.";
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ERP Login</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page"><div class="login-card"><div class="brand center"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small>Management System</small></div></div><h1>Welcome back</h1><p class="muted">Sign in to access your department workspace.</p><?php if($error):?><div class="alert danger"><?=$error?></div><?php endif;?><form method="post"><label>Username<input name="username" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="primary full">Sign in</button></form><div class="login-note">Your account determines the module dashboard and services you can access.</div></div></body></html>