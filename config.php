<?php
session_start();
$host="localhost"; $db="vocational_erp"; $user="root"; $pass="";
try {
 $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
 ]);
} catch(PDOException $e){ die("Database connection failed."); }
function appBasePath(){
 $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
 $segments = array_values(array_filter(explode('/', $scriptName), fn($segment) => $segment !== ''));
 if(empty($segments)){ return ''; }
 $moduleFolders = ['admin','finance','dean','student'];
 $last = end($segments);
 if($last !== false && strtolower(pathinfo($last, PATHINFO_EXTENSION)) === 'php'){
  array_pop($segments);
 }
 if(!empty($segments) && in_array(end($segments), $moduleFolders, true)){
  array_pop($segments);
 }
 if(empty($segments)){ return ''; }
 return '/' . implode('/', array_map('rawurlencode', $segments));
}
function appRedirectPath($relativePath){
 $base = appBasePath();
 $relative = (string)$relativePath;
 $query = '';
 if(strpos($relative, '?') !== false){
  [$relative, $query] = explode('?', $relative, 2);
 }
 $segments = array_values(array_filter(explode('/', $relative), fn($segment) => $segment !== ''));
 $encoded = implode('/', array_map('rawurlencode', $segments));
 $location = $base . '/' . $encoded;
 if($query !== ''){ $location .= '?' . $query; }
 $location = preg_replace('#/+#', '/', $location);
 header('Location: ' . $location);
 exit;
}
function appAssetPath($relativePath){
 return appBasePath() . '/' . implode('/', array_map('rawurlencode', array_values(array_filter(explode('/', ltrim((string)$relativePath, '/')), fn($segment) => $segment !== ''))));
}
function requireLogin(){ if(empty($_SESSION['user'])){ appRedirectPath('index.php'); } }
function validPhoneNumber($phone){ return preg_match('/^\d{10}$/', (string)$phone) === 1; }
function validIdentityNumber($number, $identityType){
function validBirthCertificateNumber($number){ return preg_match('/^\d{1,30}$/', (string)$number) === 1; }
 $length = $identityType === 'maisha_card' ? 9 : ($identityType === 'national_id' ? 8 : 0);
 return $length > 0 && preg_match('/^\d{1,'.$length.'}$/', (string)$number) === 1;
}
function user(){ return $_SESSION['user'] ?? null; }
function allowed($modules=[]){ return in_array(user()['module'] ?? '', $modules, true) || (user()['role'] ?? '')==='super_admin'; }
function tableExists($pdo,$table){
 $stmt=$pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
 $stmt->execute([$table]);
 return (bool)$stmt->fetchColumn();
}
function studentProfileComplete($student){
 $hasNationalId = validIdentityNumber($student['national_id'] ?? '', 'national_id');
 $hasBirthCertificate = validBirthCertificateNumber($student['birth_certificate_no'] ?? '');
 return is_array($student) && !empty($student['id_no']) && ($hasNationalId || $hasBirthCertificate) && validPhoneNumber($student['phone'] ?? '') && !empty($student['email']) && !empty($student['gender']) && !empty($student['course_id']) && in_array($student['residency']??'', ['boarder','dayscholar'], true);
}
function requireCompleteStudentProfile($student){
 if(!studentProfileComplete($student)){ header('Location: complete_profile.php?required=1'); exit; }
}
function currentStudent($pdo){
 $sessionUser=user();
 $username=(string)($sessionUser['username']??'');
 $email=trim((string)($sessionUser['email']??''));
 $stmt=$pdo->prepare("SELECT s.*,c.course_code,c.course_name,c.duration_months,d.department_id,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id LEFT JOIN student_accounts sa ON sa.student_id=s.student_id AND sa.username=? WHERE sa.student_id IS NOT NULL OR s.id_no=? LIMIT 1");
 $stmt->execute([$username,$username]);
 $student=$stmt->fetch();
 if($student){ return $student; }

 $fullName=trim((string)($sessionUser['name']??''));
 if($email!==''){
  try{
  $stmt=$pdo->prepare("SELECT s.*,c.course_code,c.course_name,c.duration_months,d.department_id,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE LOWER(TRIM(s.email))=LOWER(?) LIMIT 1");
   $stmt->execute([$email]);
   $student=$stmt->fetch();
   if($student){ return $student; }
  }catch(PDOException $e){
   // Older databases may not have the optional profile email column yet.
  }
 }
 if($fullName!==''){
  $stmt=$pdo->prepare("SELECT s.*,c.course_code,c.course_name,c.duration_months,d.department_id,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE LOWER(TRIM(s.name))=LOWER(TRIM(?)) LIMIT 1");
  $stmt->execute([$fullName]);
  $student=$stmt->fetch();
 }
 if(!$student && $username!==''){
  try{
   $temporaryId='ONBOARD-'.$sessionUser['id'];
   $stmt=$pdo->prepare("INSERT INTO students (id_no,name,course_id,status) VALUES (?,? ,NULL,'active')");
   $stmt->execute([substr($temporaryId,0,30),$fullName!==''?$fullName:$username]);
   $studentId=(int)$pdo->lastInsertId();
  $stmt=$pdo->prepare("SELECT s.*,c.course_code,c.course_name,c.duration_months,d.department_id,d.department_name FROM students s LEFT JOIN courses c ON c.course_id=s.course_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE s.student_id=? LIMIT 1");
   $stmt->execute([$studentId]);
   $student=$stmt->fetch();
  }catch(PDOException $e){
   $student=null;
  }
 }
 return $student ?: null;
}
function money($n){return "KES ".number_format((float)$n,2);}
function generateStudentIdNumber($pdo, $courseId = null){
    $courseCode = '';
    if ($courseId) {
        $stmt = $pdo->prepare('SELECT course_code FROM courses WHERE course_id = ? LIMIT 1');
        $stmt->execute([$courseId]);
        $courseCode = (string)($stmt->fetchColumn() ?: '');
    }
    $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $courseCode ?: 'REG'));
    $base = $base !== '' ? $base : 'REG';
    $year = date('y');
    $pattern = $base . '/' . $year . '/%';
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(id_no, '/', -1) AS UNSIGNED)), 0) + 1 FROM students WHERE id_no LIKE ?");
    $stmt->execute([$pattern]);
    $sequence = (int)$stmt->fetchColumn();
    return sprintf('%s/%s/%03d', $base, $year, $sequence);
}
function fixedFeeSchedule2026(){
 return [
  'year'=>'2026',
  'boarder'=>[
   ['Boarding / Lunch',6500,6500,5300],['Administration Cost',500,300,200],['P. Emolument',3000,2500,2000],['Medical',500,300,200],['L T & T',500,500,300],['Tuition',500,300,200],['E W & C',500,200,200]
  ],
  'dayscholar'=>[
   ['Boarding / Lunch',4500,4500,3500],['Administration Cost',500,300,200],['P. Emolument',3000,2500,1800],['Medical',300,300,200],['L T & T',400,400,400],['Tuition',500,300,200],['E W & C',300,200,200]
  ],
  'mandatory'=>[['Computer Packages',3500],['Admission Fee',500],['Attachment Fee',1500]]
 ];
}
function studentFeeSummary($pdo,$studentId){
 $stmt=$pdo->prepare("SELECT COALESCE(SUM(fs.amount),0) AS billed,COALESCE((SELECT SUM(fp.amount_paid) FROM fee_payments fp WHERE fp.student_id=? AND fp.fee_structure_id=fs.fee_structure_id AND fp.amount_paid>0),0) AS paid FROM fee_structure fs JOIN students s ON s.course_id=fs.course_id WHERE s.student_id=? AND (fs.residency_scope='universal' OR fs.residency_scope=s.residency)");
 $stmt->execute([$studentId,$studentId]);
 $summary=$stmt->fetch()?:['billed'=>0,'paid'=>0];
 $summary['billed']=(float)$summary['billed'];$summary['paid']=(float)$summary['paid'];$summary['balance']=max(0,$summary['billed']-$summary['paid']);
 return $summary;
}
function currentAcademicTerm(){
 $month=(int)date('n');
 return $month<=4?1:($month<=8?2:3);
}
function residencyFeeSql($feeColumn='fs.fee_type',$studentColumn='s.residency'){
 return "(fs.residency_scope='universal' OR fs.residency_scope=$studentColumn)";
}
function studentFeeCategorySummary($pdo,$studentId,$year=null,$term=null){
 $where=['s.student_id=?'];$params=[$studentId];
 if($year!==null){$where[]='fs.academic_year=?';$params[]=$year;}
 if($term!==null){$where[]='fs.semester=?';$params[]=$term;}
 $sql="SELECT CASE WHEN fs.residency_scope<>'universal' THEN 'boarding' WHEN fs.fee_type LIKE 'exam%' OR fs.fee_type='lab_practical' THEN 'examination' ELSE 'institution' END AS category,COALESCE(SUM(fs.amount),0) AS billed,COALESCE(SUM((SELECT COALESCE(SUM(fp.amount_paid),0) FROM fee_payments fp WHERE fp.student_id=? AND fp.fee_structure_id=fs.fee_structure_id AND fp.amount_paid>0)),0) AS paid FROM fee_structure fs JOIN students s ON s.course_id=fs.course_id WHERE ".implode(' AND ',$where)." AND (fs.residency_scope='universal' OR fs.residency_scope=s.residency) GROUP BY category";
 array_splice($params,1,0,$studentId);
 $stmt=$pdo->prepare($sql);$stmt->execute($params);
 $summary=['institution'=>['billed'=>0,'paid'=>0,'balance'=>0],'boarding'=>['billed'=>0,'paid'=>0,'balance'=>0],'examination'=>['billed'=>0,'paid'=>0,'balance'=>0]];
 foreach($stmt as $row){$summary[$row['category']]=['billed'=>(float)$row['billed'],'paid'=>(float)$row['paid'],'balance'=>max(0,(float)$row['billed']-(float)$row['paid'])];}
 return $summary;
}
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
  'users'=>['label'=>'Users','path'=>'staff.php','description'=>'Manage user accounts and access.'],
   'courses'=>['label'=>'Courses','path'=>'courses.php','description'=>'Maintain the academic catalogue.'],
   'departments'=>['label'=>'Departments','path'=>'departments.php','description'=>'Maintain college departments.'],
  'notices'=>['label'=>'Notices','path'=>'notices.php','description'=>'Publish official announcements.']
  ],
  'finance'=>[
   'payments'=>['label'=>'Payments','path'=>'payments.php','description'=>'Record fees and issue receipts.'],
  'fees'=>['label'=>'Fee Structure','path'=>'fee_structure.php','description'=>'Maintain student charges.'],
   'expenses'=>['label'=>'Expenses','path'=>'expenses.php','description'=>'Record approved expenses.'],
   'reports'=>['label'=>'Reports','path'=>'reports.php','description'=>'Review financial summaries.']
  ],
  'dean'=>[
    'courses'=>['label'=>'Courses','path'=>'../admin/courses.php','description'=>'Review course and department assignments.'],
    'departments'=>['label'=>'Departments','path'=>'../admin/departments.php','description'=>'Review academic departments.'],
    'admissions'=>['label'=>'Admissions & ID Generator','path'=>'admissions.php','description'=>'Review applications and generate admission numbers.'],
    'notices'=>['label'=>'Content & Notices','path'=>'notices.php','description'=>'Publish official notices and content for the campus.'],
    'accommodation'=>['label'=>'Boarding Management','path'=>'accommodation.php','description'=>'Allocate and release hostel rooms.'],
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
  'admin'=>['super_admin'=>['users','courses','departments','notices'],'admin'=>['courses','departments','notices'],'staff'=>['courses','departments','notices']],
  'finance'=>['finance_manager'=>['payments','fees','expenses','reports'],'finance_officer'=>['payments','fees','reports'],'cashier'=>['payments']],
    'dean'=>['dean'=>['courses','departments','admissions','notices','accommodation','welfare','students'],'admissions_officer'=>['courses','departments','admissions','notices'],'welfare_officer'=>['courses','departments','welfare'],'registrar'=>['courses','departments','admissions','notices','accommodation','students']],
  'students'=>['student'=>['profile','fees','notices']]
 ];
 $keys=$access[$module][$role] ?? [];
 return array_values(array_intersect_key($catalog[$module] ?? [], array_flip($keys)));
}
?>