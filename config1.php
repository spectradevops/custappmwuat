<?php 
require('sms_content.php');
 
require('email_content.php');

require("unifyApi.php");

require("crmApi.php"); 

require("offer.php");

require("checkMRTG.php");

define("Unify_URL","https://unify.spectra.co/"); // Unify UAT
define("crm_url", "https://crmuat.spectra.co:9079/api/IVR"); // CRM UAT
define("crm_notes", "https://crmuat.spectra.co:9079"); // CRM UAT Notes
define("gpon_url", "https://gponuat.spectra.co:9077/api/FTTH/GetAcntInfo/"); // GPON UAT
define("CRM_LEAD","https://crmuat.spectra.co:9082/LeadCreation.svc?wsdl"); // LEAD UAT
define("mrtg_url","http://192.168.16.237/bw-util");

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

?>
