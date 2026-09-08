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
function moduleServices($module, $role=null){
 $catalog=[
  'admin'=>[
   'users'=>['label'=>'Users','path'=>'users.php','description'=>'Manage user accounts and access.'],
   'courses'=>['label'=>'Courses','path'=>'courses.php','description'=>'Maintain the academic catalogue.'],
   'departments'=>['label'=>'Departments','path'=>'departments.php','description'=>'Maintain college departments.'],
   'notices'=>['label'=>'Notices','path'=>'notices.php','description'=>'Publish official announcements.']
  ],
  'finance'=>[
   'payments'=>['label'=>'Payments','path'=>'payments.php','description'=>'Record fees and issue receipts.'],
   'fees'=>['label'=>'Fee Structure','path'=>'fees.php','description'=>'Maintain student charges.'],
   'expenses'=>['label'=>'Expenses','path'=>'expenses.php','description'=>'Record approved expenses.'],
   'reports'=>['label'=>'Reports','path'=>'reports.php','description'=>'Review financial summaries.']
  ],
  'dean'=>[
   'admissions'=>['label'=>'Admissions','path'=>'admissions.php','description'=>'Review applications and decisions.'],
   'classes'=>['label'=>'Class Allocation','path'=>'classes.php','description'=>'Assign students to classes.'],
    'welfare'=>['label'=>'Student Welfare','path'=>'welfare.php','description'=>'Manage student welfare cases.'],
    'students'=>['label'=>'Students','path'=>'../students.php','description'=>'Manage enrolled student records.']
  ],
  'students'=>[
   'profile'=>['label'=>'My Profile','path'=>'profile.php','description'=>'View personal and course details.'],
   'fees'=>['label'=>'My Fees','path'=>'fees.php','description'=>'View fees and payment history.'],
   'notices'=>['label'=>'Notices','path'=>'notices.php','description'=>'Read student announcements.']
  ]
 ];
 $access=[
  'admin'=>['super_admin'=>['users','courses','departments','notices'],'admin'=>['users','courses','departments','notices'],'staff'=>['courses','departments','notices']],
  'finance'=>['finance_manager'=>['payments','fees','expenses','reports'],'finance_officer'=>['payments','fees','reports'],'cashier'=>['payments']],
    'dean'=>['dean'=>['admissions','classes','welfare','students'],'admissions_officer'=>['admissions'],'welfare_officer'=>['welfare'],'registrar'=>['admissions','classes','students']],
  'students'=>['student'=>['profile','fees','notices']]
 ];
 $keys=$access[$module][$role] ?? [];
 return array_values(array_intersect_key($catalog[$module] ?? [], array_flip($keys)));
}
?>