<?php
require_once "../config.php";
requireLogin();
if(!allowed(['finance','admin'])){http_response_code(403);die("Access denied.");}

$message='';
$messageClass='';
$dashboardLink='../'.user()['home'];

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save_structure'){
    $courseId=filter_input(INPUT_POST,'course_id',FILTER_VALIDATE_INT);
    $feeType=$_POST['fee_type']??'';
    $amount=filter_input(INPUT_POST,'amount',FILTER_VALIDATE_FLOAT);
    $academicYear=trim($_POST['academic_year']??'');
    $semester=filter_input(INPUT_POST,'semester',FILTER_VALIDATE_INT);
    $validTypes=['tuition','exam','other'];

    if(!$courseId||$amount===false||$amount<0||$academicYear===''||$semester<1||$semester>3||!in_array($feeType,$validTypes,true)){
        $message='Enter a valid course, fee type, amount, academic year and term.';
        $messageClass='alert-danger';
    }else{
        try{
            $check=$pdo->prepare('SELECT COUNT(*) FROM fee_structure WHERE course_id=? AND fee_type=? AND academic_year=? AND semester=?');
            $check->execute([$courseId,$feeType,$academicYear,$semester]);
            if($check->fetchColumn()>0){
                throw new Exception('This fee item already exists for the selected course and academic year.');
            }
            $stmt=$pdo->prepare('INSERT INTO fee_structure (course_id,fee_type,amount,academic_year,semester) VALUES (?,?,?,?,?)');
            $stmt->execute([$courseId,$feeType,$amount,$academicYear,$semester]);
            $message='Fee structure saved successfully.';
            $messageClass='alert-success';
        }catch(Exception $e){
            $message=$e->getMessage();
            $messageClass='alert-danger';
        }
    }
}

$courses=$pdo->query("SELECT c.course_id,c.course_code,c.course_name,c.duration_months,d.department_name FROM courses c LEFT JOIN departments d ON d.department_id=c.department_id WHERE c.status='active' ORDER BY c.course_code")->fetchAll();
$structures=$pdo->query("SELECT fs.fee_structure_id,fs.fee_type,fs.amount,fs.academic_year,fs.semester,c.course_code,c.course_name,c.duration_months,d.department_name FROM fee_structure fs JOIN courses c ON c.course_id=fs.course_id LEFT JOIN departments d ON d.department_id=c.department_id ORDER BY fs.academic_year DESC,fs.semester,c.course_code,fs.fee_type")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fee Structure | College ERP</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.fee-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:24px}.back-link{display:inline-block;margin-bottom:12px;color:#174a9b;font-size:13px;font-weight:700}.fee-head h1{margin:0}.fee-head p{color:#78849a}.fee-button{background:#174a9b;color:#fff;border:0;border-radius:7px;padding:11px 16px;font-weight:700;cursor:pointer}.fee-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e4e9f1}.fee-table th,.fee-table td{padding:14px 16px;text-align:left;border-bottom:1px solid #e4e9f1;font-size:14px}.fee-table th{background:#f5f7fb;color:#4a5568;font-size:11px;text-transform:uppercase;letter-spacing:.05em}.fee-type{display:inline-block;padding:5px 8px;border-radius:5px;background:#e8f0fc;color:#174a9b;font-size:11px;font-weight:700;text-transform:uppercase}.fee-alert{padding:12px 16px;border-radius:7px;margin-bottom:20px;font-size:14px}.alert-success{background:#e5f6ed;color:#17633b}.alert-danger{background:#fde8e8;color:#9b2929}.fee-modal{display:none;position:fixed;inset:0;background:rgba(15,30,55,.45);align-items:center;justify-content:center;padding:16px;z-index:20}.fee-modal.active{display:flex}.fee-modal-box{width:min(520px,100%);background:#fff;border-radius:10px;padding:26px}.fee-modal-box h2{margin-top:0}.field{margin:0 0 16px}.field label{display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:#4a5568}.field input,.field select{width:100%;padding:11px;border:1px solid #cbd5e0;border-radius:6px;background:#f8fafc}.modal-actions{display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e4e9f1;padding-top:16px}.cancel-button{border:0;border-radius:7px;padding:11px 16px;background:#e2e8f0;color:#4a5568;font-weight:700;cursor:pointer}@media(max-width:650px){.fee-head{display:block}.fee-button{margin-top:14px}.fee-table th,.fee-table td{padding:11px 10px}.hide-mobile{display:none}}
</style></head>
<body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / Finance / <b>Fee Structure</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content">
<?php if($message):?><div class="fee-alert <?=htmlspecialchars($messageClass)?>"><?=htmlspecialchars($message)?></div><?php endif;?><div class="fee-head"><div><a class="back-link" href="<?=htmlspecialchars($dashboardLink)?>">← Back to dashboard</a><h1>Fee Structure</h1><p>Set course charges for each academic year.</p></div><button class="fee-button" type="button" onclick="toggleFeeModal(true)">+ Add fee item</button></div>
<div style="overflow-x:auto"><table class="fee-table"><thead><tr><th>Academic year</th><th>Term</th><th>Department</th><th>Course / Period</th><th>Fee type</th><th>Amount</th></tr></thead><tbody><?php if(!$structures):?><tr><td colspan="6" style="text-align:center;color:#78849a;padding:30px">No fee structure items found.</td></tr><?php else:foreach($structures as $structure):?><tr><td><?=htmlspecialchars($structure['academic_year'])?></td><td>Term <?=htmlspecialchars($structure['semester'])?></td><td><?=htmlspecialchars($structure['department_name']??'Unassigned')?></td><td><b><?=htmlspecialchars($structure['course_code'])?></b><br><small><?=htmlspecialchars($structure['course_name'])?> · <?=htmlspecialchars($structure['duration_months'])?> months</small></td><td><span class="fee-type"><?=htmlspecialchars($structure['fee_type'])?></span></td><td><b><?=money($structure['amount'])?></b></td></tr><?php endforeach;endif;?></tbody></table></div>
</section></main></div>
<div class="fee-modal" id="feeModal"><div class="fee-modal-box"><h2>Add fee item</h2><form method="post"><input type="hidden" name="action" value="save_structure"><div class="field"><label for="course_id">Course</label><select id="course_id" name="course_id" required><option value="">Select course</option><?php foreach($courses as $course):?><option value="<?= (int)$course['course_id']?>"><?=htmlspecialchars($course['course_code'].' - '.$course['course_name'])?></option><?php endforeach;?></select></div><div class="field"><label for="fee_type">Fee type</label><select id="fee_type" name="fee_type" required><option value="">Select fee type</option><option value="tuition">Tuition</option><option value="exam">Exam</option><option value="other">Other</option></select></div><div class="field"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0" step="0.01" required></div><div class="field"><label for="academic_year">Academic year</label><input id="academic_year" name="academic_year" placeholder="2026/2027" maxlength="20" required></div><div class="field"><label for="semester">Term</label><select id="semester" name="semester" required><option value="">Select term</option><option value="1">Term 1</option><option value="2">Term 2</option><option value="3">Term 3</option></select></div><div class="modal-actions"><button class="cancel-button" type="button" onclick="toggleFeeModal(false)">Cancel</button><button class="fee-button" type="submit">Save fee item</button></div></form></div></div><script>function toggleFeeModal(show){document.getElementById('feeModal').classList.toggle('active',show);}</script></body></html>
