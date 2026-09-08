<?php
session_start();
$host="localhost"; $db="vocational_erp"; $user="root"; $pass="";
try {
 $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
 ]);
} catch(PDOException $e){ die("Database connection failed."); }
function requireLogin(){ if(empty($_SESSION['user'])){ header("Location: login.php"); exit; } }
function user(){ return $_SESSION['user'] ?? null; }
function allowed($modules=[]){ return in_array(user()['module'] ?? '', $modules, true) || (user()['role'] ?? '')==='super_admin'; }
function money($n){return "KES ".number_format((float)$n,2);}
?>