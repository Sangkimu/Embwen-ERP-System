<?php
require_once "../config.php";
header('Content-Type: application/json');

$payload=file_get_contents('php://input');
file_put_contents(__DIR__.'/mpesa_callback_log.txt', date('c')."\n".$payload."\n\n", FILE_APPEND);

$data=json_decode($payload,true);
$callback=$data['Body']['stkCallback'] ?? null;
if(!$callback || empty($callback['CheckoutRequestID'])){http_response_code(400);echo json_encode(['ResultCode'=>1,'ResultDesc'=>'Invalid callback payload']);exit;}

$checkoutRequestId=$callback['CheckoutRequestID'];
$resultCode=(int)($callback['ResultCode'] ?? 1);
$resultDescription=(string)($callback['ResultDesc'] ?? 'Unknown result');
try{
 $pdo->beginTransaction();
 $stmt=$pdo->prepare('SELECT * FROM mpesa_transactions WHERE checkout_request_id=? FOR UPDATE');
 $stmt->execute([$checkoutRequestId]);
 $transaction=$stmt->fetch();
 if(!$transaction){throw new Exception('Transaction not found.');}
 if($transaction['status']==='completed'){$pdo->commit();echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']);exit;}
 $receipt=null;
 if($resultCode===0){
  foreach(($callback['CallbackMetadata']['Item'] ?? []) as $item){
   if(($item['Name'] ?? '')==='MpesaReceiptNumber'){$receipt=(string)($item['Value'] ?? '');break;}
  }
  if(!$receipt){throw new Exception('Successful callback did not include an M-Pesa receipt.');}
  $clerk=$pdo->query("SELECT admin_id FROM admin_users WHERE status='active' ORDER BY admin_id LIMIT 1")->fetchColumn();
  if(!$clerk){throw new Exception('No active finance clerk is available.');}
  $insert=$pdo->prepare("INSERT INTO fee_payments (student_id,fee_structure_id,amount_paid,payment_date,payment_method,receipt_no,reference_no,received_by) VALUES (?,?,?,CURDATE(),'mpesa',?,?,?)");
  $insert->execute([$transaction['student_id'],$transaction['fee_structure_id'],$transaction['amount'],$receipt,$checkoutRequestId,$clerk]);
  $status='completed';
 }else{$status='failed';}
 $update=$pdo->prepare('UPDATE mpesa_transactions SET merchant_request_id=?,mpesa_receipt_no=?,result_code=?,result_description=?,status=?,callback_payload=? WHERE transaction_id=?');
 $update->execute([$callback['MerchantRequestID'] ?? null,$receipt,$resultCode,$resultDescription,$status,$payload,$transaction['transaction_id']]);
 $pdo->commit();
 echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Accepted']);
}catch(Throwable $e){
 if($pdo->inTransaction()){$pdo->rollBack();}
 error_log('M-Pesa callback: '.$e->getMessage());
 http_response_code(500);
 echo json_encode(['ResultCode'=>1,'ResultDesc'=>'Callback processing failed']);
}
