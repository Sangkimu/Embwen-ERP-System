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
function mpesaConfig(){
 return [
  'consumer_key'=>getenv('MPESA_CONSUMER_KEY') ?: '',
  'consumer_secret'=>getenv('MPESA_CONSUMER_SECRET') ?: '',
  'shortcode'=>getenv('MPESA_SHORTCODE') ?: '',
  'passkey'=>getenv('MPESA_PASSKEY') ?: '',
  'callback_url'=>getenv('MPESA_CALLBACK_URL') ?: '',
  'base_url'=>getenv('MPESA_BASE_URL') ?: 'https://sandbox.safaricom.co.ke'
 ];
}
function mpesaRequest($url,$headers=[],$body=null){
 $ch=curl_init($url);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POST=>$body!==null]);
 if($body!==null){curl_setopt($ch,CURLOPT_POSTFIELDS,is_string($body)?$body:json_encode($body));}
 $response=curl_exec($ch);
 $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $error=curl_error($ch);
 curl_close($ch);
 if($response===false || $error){throw new Exception('M-Pesa connection failed.');}
 $decoded=json_decode($response,true);
 if($status<200 || $status>=300 || !is_array($decoded)){throw new Exception('M-Pesa returned an invalid response.');}
 return $decoded;
}
function mpesaAccessToken($config){
 if(empty($config['consumer_key']) || empty($config['consumer_secret'])){throw new Exception('M-Pesa API credentials are not configured.');}
 $credentials=base64_encode($config['consumer_key'].':'.$config['consumer_secret']);
 $response=mpesaRequest(rtrim($config['base_url'],'/').'/oauth/v1/generate?grant_type=client_credentials',['Authorization: Basic '.$credentials]);
 if(empty($response['access_token'])){throw new Exception('M-Pesa access token was not returned.');}
 return $response['access_token'];
}
function mpesaStkPush($amount,$phone,$accountReference,$description){
 $config=mpesaConfig();
 if(empty($config['shortcode']) || empty($config['passkey']) || empty($config['callback_url'])){throw new Exception('M-Pesa shortcode, passkey, and public callback URL are required.');}
 $phone=preg_replace('/\D+/','',$phone);
 if(substr($phone,0,1)==='0'){$phone='254'.substr($phone,1);}
 if(!preg_match('/^2547\d{8}$/',$phone)){throw new Exception('Enter a valid Kenyan mobile number.');}
 $timestamp=date('YmdHis');
 $password=base64_encode($config['shortcode'].$config['passkey'].$timestamp);
 return mpesaRequest(rtrim($config['base_url'],'/').'/mpesa/stkpush/v1/processrequest',['Authorization: Bearer '.mpesaAccessToken($config),'Content-Type: application/json'],[
  'BusinessShortCode'=>$config['shortcode'],'Password'=>$password,'Timestamp'=>$timestamp,'TransactionType'=>'CustomerPayBillOnline',
  'Amount'=>(int)round($amount),'PartyA'=>$phone,'PartyB'=>$config['shortcode'],'PhoneNumber'=>$phone,
  'CallBackURL'=>$config['callback_url'],'AccountReference'=>$accountReference,'TransactionDesc'=>$description
 ]);
}
function moduleServices($module, $role=null){
 $catalog=[
  'admin'=>[
   'users'=>['label'=>'Users','path'=>'users.php','description'=>'Manage user accounts and access.'],
   'courses'=>['label'=>'Courses','path'=>'courses.php','description'=>'Maintain the academic catalogue.'],
   'departments'=>['label'=>'Departments','path'=>'departments.php','description'=>'Maintain college departments.'],
  'notices'=>['label'=>'Notices','path'=>'notices.php','description'=>'Publish official announcements.'],
  'finance'=>['label'=>'Finance','path'=>'../finance/index.php','description'=>'Review payments, fees, expenses and reports.']
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
  'admin'=>['super_admin'=>['users','courses','departments','notices','finance'],'admin'=>['users','courses','departments','notices','finance'],'staff'=>['courses','departments','notices','finance']],
  'finance'=>['finance_manager'=>['payments','fees','expenses','reports'],'finance_officer'=>['payments','fees','reports'],'cashier'=>['payments']],
    'dean'=>['dean'=>['admissions','classes','welfare','students'],'admissions_officer'=>['admissions'],'welfare_officer'=>['welfare'],'registrar'=>['admissions','classes','students']],
  'students'=>['student'=>['profile','fees','notices']]
 ];
 $keys=$access[$module][$role] ?? [];
 return array_values(array_intersect_key($catalog[$module] ?? [], array_flip($keys)));
}
?>