<?php
//define("crm_url", "https://crmuat.spectra.co:9079/api/IVR");

	function getSRStatus($data){

		$data_string = json_encode($data);
		$fp       = fopen("logs/crm_srstatus.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".crm_url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => crm_url."/SRStatus",
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/crm_srstatus.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$output;
                fwrite($fp,$log_txt);

		$result = json_decode($output, true);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  		$result = "cURL Error #:" . $err;
		} 
    	return $result;
	}


	 function Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner){
                 $req_data = array("Type"=>$type, "SubType"=>$subType,
                   "SubSubType"=>$subSubType,
                   "CaseSource"=>$caseSource, "CaseCategory"=>$caseCategory,
                   "ComplaintDesc"=>$complainDesc,
                   "AccountID"=>$canid ,"Owner"=>$owner);
                   $request=json_encode($req_data);
                   $data_string = $request;
                   $log_message = "CRM request to Create SR :\n" . $data_string."\t with CAN ID :".$canid."\t";
                   $Create_SR_URL= crm_url."/CreateSR";
                   $output_reponse= Curl_CRM($data_string,$Create_SR_URL,$canid);
                   $log_message = " CRM Response for SR creation ". json_encode($output_reponse)." \t for CAN ID ".$canid;
                   $crm_data = json_decode($output_reponse[0]);
                   return $Case_message=trim($crm_data->Message);
                       }




	  function Close_sr($sr_no,$resolutioncode1,$resolutioncode2,$resolutioncode3,$rfo){
                  $req_data=array("CaseID"=>$sr_no,
                      "Resolutioncode1"=>$resolutioncode1,
                      "Resolutioncode2"=>$resolutioncode2,
                      "Resolutioncode3"=>$resolutioncode3,
                      "RFO"=>$rfo);
                       $request=json_encode($req_data);
                       $data_string = $request;
                       $log_message = "CRM request to Create SR :\n" . $data_string."\t with CAN ID :".$canid."\t";
                       $Create_SR_URL= crm_url."/CloseSR";
                       $output_reponse= Curl_CRM($data_string,$Create_SR_URL,$canid);
                       $log_message = " CRM Response for SR creation ". json_encode($output_reponse)." \t for CAN ID ".$canid;
                       $crm_data = json_decode($output_reponse[0]);
                       return $Case_message=trim($crm_data->Message);
                      }
		
		
	function CRM_update_email($NewEmail,$CANID){
	/* Create SR */
		   $type = "T_115";
		   $subType = "ST_417";
		   $subSubType = "SST_668";
		   $caseSource = "20";
		   $caseCategory = "3";
		   $complainDesc = "Requested for contact number update on APP via OTP";
		   if(substr($CANID,0,1) == "9"){
		   	$owner = "CS_SRM";
		   }else{
			$owner = "CS_Home_Backend";
		   }
		   $Case_message = Create_SR_CRM($CANID,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
		  # print_r($Case_message); exit;
		if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){
                 //    $data = array("status"=>"success","response"=>$Case_message,"message"=>"SR has been created");
			$data= array("AccountID"=>$CANID,   "EmailID"=>$NewEmail,  "MobileNo"=>"");
	                $data_string = json_encode($data);
        	        $Update_Email_URL=crm_url."/UpdateEmailMobilePhone";
                	$result= Curl_CRM($data_string,$Update_Email_URL);
        /* Close SR */
		   if($result[0] ==='"Updated"'){
                   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_671",  "Resolutioncode2"=>"RC2_673",   "Resolutioncode3"=>"RC3_17453", "RFO" => "Requested for contact number update on APP via OTP", "OLR"=>"Yes");
		   }else{
		    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_671",  "Resolutioncode2"=>"RC2_673",   "Resolutioncode3"=>"RC3_17454", "RFO" => "Contact number updation Failed via OTP", "OLR"=>"Yes");
		   }
                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
			#print_r($Case_message1); 
		     if($result[0] ==='"Updated"'){
	                $data =  array("status"=>"success","response"=>array(),"message"=>"Email id updated successfully");
        	     }else{
	                $data =  array("status"=>"failure","response"=>array(),"message"=>"Email id could not updated");
        	        }
			
                }elseif(preg_match('/active/', $Case_message)){
                     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
                }else{
                     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                }
	
	
                return $data;

		}

        function CRM_update_mobile($mobileNo,$CANID){
		  /* Create SR */
		   $type = "T_115";
                   $subType = "ST_417";
                   $subSubType = "SST_668";
                   $caseSource = "20";
                   $caseCategory = "3";
                   $complainDesc = "Requested for contact number update on APP via OTP";
                   if(substr($CANID,0,1) == "9"){
                        $owner = "CS_SRM";
                   }else{
                        $owner = "CS_Home_Backend";
                   }

                   $Case_message = Create_SR_CRM($CANID,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
                  # print_r($Case_message); exit;
                if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){

                $data= array("AccountID"=>$CANID,   "EmailID"=>'',  "MobileNo"=>$mobileNo);
                $data_string = json_encode($data);
                $Update_Email_URL=crm_url."/UpdateEmailMobilePhone";
                $result= Curl_CRM($data_string,$Update_Email_URL);
		 //  $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_1509",  "Resolutioncode2"=>"RC2_2411", "Resolutioncode3"=>"RC3_16607", "RFO" => "Requested for contact number updated successfully", "OLR"=>"Yes");
		     if($result[0] === '"Updated"'){
                   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_671",  "Resolutioncode2"=>"RC2_673",   "Resolutioncode3"=>"RC3_17453", "RFO" => "Requested for contact number update on APP via OTP", "OLR"=>"Yes");
                   }else{
                    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_671",  "Resolutioncode2"=>"RC2_673",   "Resolutioncode3"=>"RC3_17454", "RFO" => "Contact number updation Failed via OTP", "OLR"=>"Yes");
                   }

                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
                        #print_r($Case_message1); 
                     if($result[0] ==='"Updated"'){
                        $data =  array("status"=>"success","response"=>array(),"message"=>"Mobile no. updated successfully");
                     }else{
                        $data =  array("status"=>"failure","response"=>array(),"message"=>"Mobile no. could not updated");
                        }

                }elseif(preg_match('/active/', $Case_message)){
                     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
                }else{
                     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                }

		return $data;
                }

/*This function is used to Send request to CRM*/
	function Curl_CRM($data_string,$url){
    		$fp       = fopen("logs/crm_api.log","a+");
    		$log_txt = "\n".date("Y-m-d H:i:s")."\t".$url."\t".$data_string;
    		fwrite($fp,$log_txt);
		$curl = curl_init();
  		curl_setopt_array($curl, array(
//  CURLOPT_PORT => "9079",
  		CURLOPT_URL => $url,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 36000,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
 			 ),
			));
		$result = curl_exec($curl);
		$log_txt = "\n".date("Y-m-d H:i:s")."\t".$result;
		fwrite($fp,$log_txt);
		$statuscode =    curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  		$result = "CURL Error #:" . $err;
		}
		$response=array();
		$response=array($result,$ID,$statuscode);
		return $response;
	}


	function getMassoutage($data){
//		$data=array('phone'=>'','CANID'=>trim($id));
		$data_string = json_encode($data);
		$t = explode(" ",microtime());
		$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
		$fp       = fopen("logs/crm.log","a+");
		$FTTH_LOG = "CRM. request param:\n" . $data_string."<br>";
		$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
		fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
		//  CURLOPT_PORT => "9079",
  			CURLOPT_URL => crm_url,
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => "",
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 30,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => "POST",
  			CURLOPT_SSL_VERIFYPEER => "false",
  			CURLOPT_POSTFIELDS => $data_string,
  			CURLOPT_HTTPHEADER => array(
    				"username: User",
    				"password: User@123",
    				"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    				"cache-control: no-cache",
    				"content-type: application/json"
  			),
		));
		$result = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  			$result = "cURL Error #:" . $err;
		}

		$t = explode(" ",microtime());
        	$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
        	$FTTH_LOG = "CRM. response:\n" . $result;
        	$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
        	fwrite($fp,$log_txt);
        	fclose($fp);
    		return $result;
	}
	function getINASdetail($keyword){

		$xmlData='<soapenv:Envelope
			xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
			xmlns:nbi="http://203.122.58.152">
				  <soapenv:Header>
				   <nbi:Authentication>
				    <userName>GPON</userName>
				    <password>GponAp</password>
				    </nbi:Authentication>
				    </soapenv:Header>
				       <soapenv:Body>
				       <nbi:CTPRequestInfo>
					    <onuIdentifier>
				            <searchType>OnuSerial</searchType>
					    <keyWord>'.$keyword.'</keyWord>
					    </onuIdentifier>
					    </nbi:CTPRequestInfo>
					    </soapenv:Body>
					    </soapenv:Envelope>';
		return $xmlData;
	 }


        function planchangeb2c($data){
		$url = CRM_Update_Plan;
                $data_string = json_encode($data);
                $fp       = fopen("logs/crm_api.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$url."\t".$data_string;
                fwrite($fp,$log_txt);
                $curl = curl_init();
#                $url='https://crmuat.spectra.co:9079/api/MiscAPI/UpdatePlanB2C';
                curl_setopt_array($curl, array(
                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 36000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSL_VERIFYPEER => "false",
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HTTPHEADER => array(
                        "username: User",
                        "password: User@123",
                        "authorization: Basic VXNlcjpVc2VyQDEyMw==",
                        "cache-control: no-cache",
                        "content-type: application/json"
                         ),
                        ));
                $result = curl_exec($curl);
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$result;
                fwrite($fp,$log_txt);
                $statuscode =    curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $err = curl_error($curl);
                 curl_close($curl);
		if ($err) {
                        $result = "cURL Error #:" . $err;
                }

                $t = explode(" ",microtime());
                $timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
                $FTTH_LOG = "CRM. response:\n" . $result;
                $log_txt = "\n".$timestamp."\t".$FTTH_LOG;
                fwrite($fp,$log_txt);
                fclose($fp);
                return $result;
}

function getTrackOrder($canId){
	$url= "https://spectranetcrm.spectranet.in:9099/api/TrackOrder/".$canId;
   $policyURL= $url;
   $headers = array(
		"Content-Type: application/json",
	);

	/*Logging SOAP Request in a log file starts */
	$fp = fopen("logs/trackorder.log","a+");
	$log_txt = "\n".date("Y-m-d H:i:s")."\t".$_SERVER['REMOTE_ADDR']."\t Request URL: ".$policyURL."\n";
	fwrite($fp,$log_txt);
	fclose($fp);  
	/*Logging SOAP Request in a log file ends */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $policyURL);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $xmlResponse = curl_exec($ch);
	
	/*Logging SOAP Response in a log file starts */
	$fp = fopen("logs/trackorder.log","a+");
	$log_txt1 = "\n".date("Y-m-d H:i:s")."\t".$_SERVER['REMOTE_ADDR']."\t Response: \n";
	fwrite($fp,$log_txt1);
	fwrite($fp, $xmlResponse);
    	fclose($fp); 
	/*Logging SOAP Response in a log file ends */
    $ch_info = curl_getinfo($ch);
	/*if (curl_exec($ch) === FALSE) {
		echo "<br>";
   die("Curl Failed: " . curl_error($ch));
} else {
    
	//print_r($xmlResponse);
}*/
    curl_close($ch);
	
    return $xmlResponse;
}

	function updateContactDetails($data){
		$data_string = json_encode($data);
		$t = explode(" ",microtime());
		$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
		$fp       = fopen("logs/crm.log","a+");
		$FTTH_LOG = "CRM. request param:\n" . $data_string."<br>";
		$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
		fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
		//  CURLOPT_PORT => "9079",
  			CURLOPT_URL => crm_url."/UpdateContact",
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => "",
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 30,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => "POST",
  			CURLOPT_SSL_VERIFYPEER => "false",
  			CURLOPT_POSTFIELDS => $data_string,
  			CURLOPT_HTTPHEADER => array(
    				"username: User",
    				"password: User@123",
    				"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    				"cache-control: no-cache",
    				"content-type: application/json"
  			),
		));
		$result = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  			$result = "cURL Error #:" . $err;
		}

		$t = explode(" ",microtime());
        	$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
        	$FTTH_LOG = "CRM. response:\n" . $result;
        	$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
        	fwrite($fp,$log_txt);
        	fclose($fp);
    		return $result;
	}

	function getContactDetails($data){
		$data_string = json_encode($data);
		$t = explode(" ",microtime());
		$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
		$fp       = fopen("logs/crm.log","a+");
		$FTTH_LOG = "CRM. request param:\n" . $data_string."<br>";
		$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
		fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
		//  CURLOPT_PORT => "9079",
  			CURLOPT_URL => crm_url."/GetContact",
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => "",
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 30,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => "GET",
  			CURLOPT_SSL_VERIFYPEER => "false",
  			CURLOPT_POSTFIELDS => $data_string,
  			CURLOPT_HTTPHEADER => array(
    				"username: User",
    				"password: User@123",
    				"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    				"cache-control: no-cache",
    				"content-type: application/json"
  			),
		));
		$result = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  			$result = "cURL Error #:" . $err;
		}

		$t = explode(" ",microtime());
        	$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
        	$FTTH_LOG = "CRM. response:\n" . $result;
        	$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
        	fwrite($fp,$log_txt);
        	fclose($fp);
    		return $result;
	}

#define("gpon_url", "https://gponuat.spectra.co:9077/api/FTTH/GetAcntInfo/"); // GPON UAT

	function getGponInfo($can_id){
#		$data_string = json_encode($can_id);
		$t = explode(" ",microtime());
		$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
		$fp       = fopen("logs/gpon.log","a+");
		$FTTH_LOG = "CRM. request param:\n" . $can_id."<br>";
		$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
		fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
		//  CURLOPT_PORT => "9079",
  			CURLOPT_URL => gpon_url."/".$can_id,
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => "",
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 30,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => "GET",
  			CURLOPT_SSL_VERIFYPEER => "false",
  			#CURLOPT_POSTFIELDS => $data_string,
  			CURLOPT_HTTPHEADER => array(
    				"username: User",
    				"password: User@123",
    				"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    				"cache-control: no-cache",
    				"content-type: application/json"
  			),
		));
		$result = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  			$result = "cURL Error #:" . $err;
		}

		$t = explode(" ",microtime());
        	$timestamp = date("Y-m-d H:i:s",$t[1]).(string)$t[0];
        	$FTTH_LOG = "CRM. response:\n" . $result;
        	$log_txt = "\n".$timestamp."\t".$FTTH_LOG;
        	fwrite($fp,$log_txt);
        	fclose($fp);
    		return $result;
	}

function getsrnotes($data){

                $data_string = json_encode($data);
     		$timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$data_string;
                file_put_contents("logs/crm_api.log","\n".$LOG, FILE_APPEND | LOCK_EX);

	        $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => crm_notes."/api/MiscAPI/getSRNotes",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, 
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_SSL_VERIFYPEER => "false",
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HTTPHEADER => array(
                        "username: User",
                        "password: User@123",
                        "authorization: Basic VXNlcjpVc2VyQDEyMw==",
                        "cache-control: no-cache",
                        "content-type: application/json"
                ),
                ));
		$output=curl_exec($curl);
                $timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$output;
                file_put_contents("logs/crm_api.log","\n".$LOG, FILE_APPEND | LOCK_EX);

                $result = json_decode($output,true);


                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                $result = "cURL Error #:" . $err;
                }
        return $result;
        }

function getSRStatusExt($data){

                $data_string = json_encode($data);
     		$timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$data_string;
                file_put_contents("logs/crm_srstatusext.log","\n".$LOG, FILE_APPEND | LOCK_EX);

	        $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => crm_url."/SRStatusExt",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, 
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSL_VERIFYPEER => "false",
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HTTPHEADER => array(
                        "username: User",
                        "password: User@123",
                        "authorization: Basic VXNlcjpVc2VyQDEyMw==",
                        "cache-control: no-cache",
                        "content-type: application/json"
                ),
                ));
		$output=curl_exec($curl);
                $timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$output;
                file_put_contents("logs/crm_srstatusext.log","\n".$LOG, FILE_APPEND | LOCK_EX);

                $result = json_decode($output,true);


                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                $result = "cURL Error #:" . $err;
                }
        return $result;
        }


function routerPayment($data){

                $data_string = json_encode($data);
     		$timestamp = date("Y-m-d H:i:s");
		$url = crm_url."/RouterPayment";
                $LOG = $timestamp." ".$url." ".$data_string;
                file_put_contents("logs/router_payment.log","\n".$LOG, FILE_APPEND | LOCK_EX);

	        $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, 
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_SSL_VERIFYPEER => "false",
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HTTPHEADER => array(
                        "username: User",
                        "password: User@123",
                        "authorization: Basic VXNlcjpVc2VyQDEyMw==",
                        "cache-control: no-cache",
                        "content-type: application/json"
                ),
                ));
		$output=curl_exec($curl);
                $timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$output;
                file_put_contents("logs/router_payment.log","\n".$LOG, FILE_APPEND | LOCK_EX);

                $result = json_decode($output,true);


                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                $result = "cURL Error #:" . $err;
                }
        return $result;
        }

/************* Create/Update Lead ***********/

function CreateLead($data){
		$url = lead_url."create_lead";
		$data_string = json_encode($data);
		$fp       = fopen("logs/createlead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => $url,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/createlead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$output;
                fwrite($fp,$log_txt);

		$result = json_decode($output, true);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  		$result = "cURL Error #:" . $err;
		} 
    	return $result;
	}

function UpdateLead($data){
#print_r($data); exit;
		$url = lead_url."update_lead";
		$data_string = json_encode($data);
		$fp       = fopen("logs/updatelead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => $url,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/updatelead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$output;
                fwrite($fp,$log_txt);

		$result = json_decode($output, true);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  		$result = "cURL Error #:" . $err;
		} 
    	return $result;
	}
/************ End *****************/

/************** Trust band customer count *************/

	function trustBandCustomerCount($data){
		$url = crm_url."/GetTrustBandCustomerCount";
		$data_string = json_encode($data);
		$fp       = fopen("logs/trustband.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => $url,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "GET",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/trustband.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".$output;
                fwrite($fp,$log_txt);

		$result = json_decode($output, true);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
  		$result = "cURL Error #:" . $err;
		} 
    	return $result;
	}


/***************** End ********************/
?>
