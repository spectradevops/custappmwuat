<?php
require("functions.php");

$db  = dbConnection();
$sql = "select * from airmesh_payment_link where link_send_status='sent' and (payment_status is NULL or payment_status ='')";
$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($row as $val){
	$id = $val['link_id'];
	$sr_no = $val['sr_no'];
	$LOG = $id." ".$sr_no;
        file_put_contents("logs/crm_payment.log","\t".$LOG, FILE_APPEND | LOCK_EX);

	$sql_pay = "select * from epay_db.payment_api where sub_segment = '$id' and source='inw_api'";
	$stmt = $db->prepare($sql_pay);
	$stmt->execute();
	$row_pay = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($row_pay as $value){
		$pg_status = $value['successTxn'];
		$pg_id = $value['pgTxnNo'];
		$unify_status = $value['unify_status'];
                $paymentmode = $value['paymentmode'];
                $res_date = $value['res_date'];
		$amount = $value['paid_amount'];

		if($pg_status == "SUCCESS" && $unify_status == "posted"){
				$update = "update airmesh_payment_link set payment_id='".$pg_id."' , payment_status='".$pg_status."', unify_status = '".$unify_status."' where link_id ='".$id."'";
		                $stmt = $db->prepare($update);
                		$stmt->execute();
				$transaction_details = "Payment Link Status: Closed, \nPayment Transaction ID: ".$pg_id.", \nMode of Payment: ".$paymentmode.", \nDate & Time of Payment: ".$res_date.", \nPayment Amount: ".$amount;
				$data = array("SRNo" => $sr_no, "TransactionDetails" => $transaction_details , "PaymentStatus" => "Success");
				$result = routerPayment($data);
				$LOG = json_encode($result);
			        file_put_contents("logs/crm_payment.log","\n".$LOG, FILE_APPEND | LOCK_EX);
				if(!is_array($result) && $result == "success")
                                {
                                $update = "update airmesh_payment_link set crm_api_status = 'success', crm_api_date=now() where link_id ='".$id."'";
                                }else{
                                $update = "update airmesh_payment_link set crm_api_status = '".$result['Message']."', crm_api_date=now() where link_id ='".$id."'";

				}
				$stmt = $db->prepare($update);
                                $stmt->execute();	
		}
	}

}


?>
