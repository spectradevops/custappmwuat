<?php

require("bitly.php");
 
require('sms_content.php');
 
require('email_content.php');

require("unifyApi.php");

require("crmApi.php"); 

require("offer.php");

require("checkMRTG.php");

require("affle_api.php");

define("Unify_URL","https://unifyuat.spectra.co/"); // Unify UAT
define("crm_url", "https://crmuat.spectra.co:9079/api/IVR"); // CRM UAT
define("crm_notes", "https://crmuat.spectra.co:9079"); // CRM UAT Notes
define("gpon_url", "https://gponuat.spectra.co:9077/api/FTTH/GetAcntInfo/"); // GPON UAT
define("CRM_LEAD","https://crmuat.spectra.co:9082/LeadCreation.svc?wsdl"); // LEAD UAT
define("mrtg_url","http://192.168.16.237/bw-util");
define("CRM_Update_Plan","https://crmuat.spectra.co:9079/api/MiscAPI/UpdatePlanB2C");
define("Affle_URL", "https://spectra.affleprojects.com/v0/notification/");
define("lead_url", "https://wdev.spectra.co/spectra/");
#define("CRM_Update_Plan","https://crmlapi.spectra.co:9079/api/MiscAPI/UpdatePlanB2C");

/*
define("Unify_URL","https://unify.spectra.co/"); // Unify LIVE
define("Unify_URL_Live","https://unify.spectra.co/"); // Unify LIVE for Session History
define("crm_url", "https://crmlapi.spectra.co:9079/api/IVR"); // CRM LIVE
define("crm_notes", "https://crmlapi.spectra.co:9079"); // CRM LIVE Notes
define("CRM_LEAD","https://spectranetcrm.spectranet.in:9082/LeadCreation.svc?wsdl");// LEAD LIVE
define("CRM_Update_Plan","https://crmuat.spectra.co:9079/api/MiscAPI/UpdatePlanB2C");
define("mrtg_url","http://192.168.16.237/bw-util");
*/
define('GST','1.18');

define('AUTHKEY','AdgT68HnjkehEqlkd4'); // generic User
if(AUTHKEY == "AdgT68HnjkehEqlkd4"){
	define('auth_id','generic');
}

function getBillCycleNo($array){
	$db = referal_db();
        try {
	$query = "select bill_no from bill_cycle_master where month_in_bill_cycle ='".$array['cycle']."' and segment ='".$array['segment']."' and days ='".$array['bill_day']."'"; 
	$stmt = $db->prepare($query);
          $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if(!empty($row)){
			return $row;	
		}else{
			return false;
		}

	} catch(PDOException $ex) {
                $res = $ex->getMessage();
        return  $response= array('Status'=>'Failure','Error'=>$res);
        }
}

	 function referal_db(){
                $db = "referal";
                $user = "root";
                $password = "";
                $host="localhost";

                $db = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", "$user", "$password") or die("DB Connection Error. Please try later.");
                return $db;
        }

	    function dbConnection(){
                $db = "customer_apps";
                $user = "root";
                $password = "";
                $host="localhost";

                $db = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", "$user", "$password") or die("DB Connection Error. Please try later.");
                return $db;
        }

	function scp_topup_plan_db(){
                $db = "scp_topup_plan";
                $user = "root";
                $password = "";
                $host="localhost";

                $db = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", "$user", "$password") or die("DB Connection Error. Please try later.");
                return $db;
        }

	function epay_db(){
                $db = "epay_db";
                $user = "root";
                $password = "";
                $host="localhost";

                $db = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", "$user", "$password") or die("DB Connection Error. Please try later.");
                return $db;
        }



function calculate_pgprice($base_price, $start_date, $end_date){
        $price = 0;
#echo	$start_date." ".$end_date; exit;
        $st_month = date("n", strtotime($start_date));
        $en_month = date("n", strtotime($end_date));
        for($i = $st_month ; $i <= $en_month ; $i++){
	    if($i == $st_month && $i == $en_month){
		$last_date = date("Y-m-d", strtotime($end_date));
                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($last_date);
                $interval = $datetime1->diff($datetime2);
                $date_dif = $interval->format('%a') + 1;
                $total_days = date("t", strtotime($start_date));
                $price  += ($base_price/$total_days)*$date_dif;
	    }elseif($i == $st_month && $i != $en_month){
                $last_date = date("Y-m-t", strtotime($start_date));
                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($last_date);
                $interval = $datetime1->diff($datetime2);
                $date_dif = $interval->format('%a') + 1;
                $total_days = date("t", strtotime($start_date));
                $price  += ($base_price/$total_days)*$date_dif;
            }elseif($i != $st_month && $i == $en_month){
                $date_dif = date("j", strtotime($end_date))-1;
                $total_days = date("t", strtotime($end_date));
                $price  += ($base_price/$total_days)*$date_dif;
            }else{
                $price  += $base_price;
            }
	#	echo $price."\n";
        }//End of Loop
        return  $price;
}


function calculate_RCpgprice($base_price, $start_date, $end_date){
        $price = 0;
#echo	$start_date." ".$end_date;
#        $st_month = date("n", strtotime($start_date));
#        $en_month = date("n", strtotime($end_date));
	$datetime1 = new DateTime($start_date);
	$datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
	$date_dif_m = $interval->format('%m');
#echo "\n";
	$price  += $base_price * $date_dif_m;
   	$date_dif_d = $interval->format('%d');	
	$price  += ($base_price/30) * $date_dif_d;

        return  round($price);
}

$verticalSegment = array(
"2"=>"Business Broadband",
"3"=>"Broadband",
"4"=>"HOME-Own",
"5"=>"HOME-Partner",
"6"=>"HOME-Own",
"7"=>"HOME-Own"
);
$industryType_master = array(
"5"=>"LE",
"6"=>"SMB",
"7"=>"SMB",
"8"=>"Prospect",
"9"=>"Managed services",
"10"=>"KAM",
"12"=>"SMB",
"11"=>"SP",
"13"=>"LE",
"14"=>"KAM",
"15"=>"SP",
"16"=>"Managed services",
"17"=>"SMB",
"18"=>"Managed services"
);


 function createcustomer($key,$secret,$name,$email,$contact){
       $url="https://api.razorpay.com/v1/customers";
       $arr=array('name'=>$name,'email'=>$email,'contact'=>$contact,'fail_existing'=>'0');
       $data_string = json_encode($arr);
	
	$LOG = date("Y-m-d H:i:s")." ".$url." ".$data_string;
        file_put_contents("logs/create_customer.log","\n".$LOG, FILE_APPEND | LOCK_EX);

       $ch = curl_init();
	
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string );
       #curl_setopt($ch, CURLOPT_HEADER, true);
       curl_setopt($ch, CURLOPT_USERPWD, $key . ":" . $secret);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/json',
       'Content-Length: ' . strlen($data_string)
       ));
         $output = curl_exec($ch);
        if (curl_error($ch)) {
                $error_msg = curl_error($ch);
        #print_r($error_msg);
        }
	$LOG = date("Y-m-d H:i:s")." ".$output;
        file_put_contents("logs/create_customer.log","\n".$LOG, FILE_APPEND | LOCK_EX);

       $response = json_decode($output,true);
       #print_r($response);
 return $response['id'];
   }


 function insert_ftthpaymentsi($post,$amt, $order,$customer_id,$source,$sourceip,$url)
		{ 
			$timestamp=date('Y-m-d H:i:s',$order->created_at);
			$userip= $_SERVER['REMOTE_ADDR'];;
			$time=date("Y-m-d H:i:s");
			$db  = epay_db();
		
			$qry = $db->prepare("INSERT INTO razorpay_si ( canid,
				returnurl,
				name,
				email,
				mobileno,
				amount,
				enable_req_date,
				enable_source,
				enable_source_ip,
				order_id,
				customer_id,					
				last_dt,
				si_status,source) 
                   	VALUES (:canid,
				:returnurl,
				:name,
				:email,
				:mobileno,
				:amount,
				:enable_req_date,
				:enable_source,
				:enable_source_ip,
				:order_id,
				:customer_id,
				:last_dt,
				:si_status,:source);");

//			$name=$post['fname'].' '.$post['lname'];
			$name=$post['name'];
			$req_source = 'APP';
			$orderR=$order->receipt;
			$orderid=$order->id;
			$si_status='0';	
                        $qry->bindParam(':canid', $orderR, PDO::PARAM_STR,50);
                        $qry->bindParam(':returnurl', $url, PDO::PARAM_STR,100);
			$qry->bindParam(':name', $name, PDO::PARAM_STR, 100);
			$qry->bindParam(':mobileno', $post['mobileno'], PDO::PARAM_STR, 50);
			$qry->bindParam(':email', $post['emailid'], PDO::PARAM_STR, 50);
		        $qry->bindParam(':amount', $amt, PDO::PARAM_STR, 20);
			$qry->bindParam(':enable_req_date', $timestamp, PDO::PARAM_STR, 50);
			$qry->bindParam(':enable_source', $source, PDO::PARAM_STR, 50);
 			$qry->bindParam(':enable_source_ip', $sourceip, PDO::PARAM_STR, 50);
 			$qry->bindParam(':order_id', $orderid, PDO::PARAM_STR, 100);
			$qry->bindParam(':customer_id', $customer_id, PDO::PARAM_STR, 100);
 			$qry->bindParam(':last_dt', $time, PDO::PARAM_STR, 50);
                        $qry->bindParam(':si_status', $si_status, PDO::PARAM_STR, 1);
			$qry->bindParam(':source', $req_source, PDO::PARAM_STR, 20);
			$qry->execute();

			return  $db->lastInsertId(); 
			 
		}

function update_payment($param){

	$query = "INSERT INTO razorpay_si_token (razorpay_si_id, canid, payment_id, amount, status ,order_id ,method,captured ,description,card_id,email,contact ,customer_id , token_id , error_code , error_description) values ('$razorpay_si_id', '$canid', '$payment_id', '$amount', '$status' ,'$order_id' ,'$method','$captured' ,'$description','$card_id','$email','$contact' ,'$customer_id' , '$token_id' , '$error_code' , '$error_description')";
}

?>
