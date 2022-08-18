<?php
/* 
This function will return user detail on the base of particular username & password or Mobile   
It works based on 2 logic:
1. it check the table: plan_canid where all offering for a perticular 
can id is stored with thier existing plan 
2. if offering is not available for that can id, it checks in another table
for the offer based on their existing rate_plan id
input: can id, base plan
output: can id, rate_plan id, plan details 

*/

#require("unifyApi.php");
#require("crmApi.php");
require("config.php");

function getAccountByPassword($username, $password){
	
	$getCredentials = getEntityCredentials($username);
	if(empty($getCredentials)){
		$data = array("status"=>"failure","response"=>array(),"message"=>"Oops! Something has gone wrong. Username does not exist");	
	}else{
	if($getCredentials['credentialValue'] == $password){
		$actID = $getCredentials['actIds'];
		$getData[] = getAccountData4($actID);
		if(empty($getData)){
                	$data = array("status"=>"failure", "response"=>array(), "message"=>"No Records Found");      
        	}else{
			$data = array("status"=>"success","response"=>$getData, "message"=>"Successfully Fetched");
		}
		
	}else{
		$data = array("status"=>"failure","response"=>array(), "message"=>"Your Username & password does not match");
	}
	}
return $data;
}


	function getAccountByMobile($mobile){
        	$getDataArr = getAccountDetailsByMobileNumber($mobile);
		#print_r($getDataArr); exit;
		usort($getDataArr, function($a, $b) {
                        return $b['billSetup']['actNo'] - $a['billSetup']['actNo'];
                });
			$getData=array();
			foreach($getDataArr as $getAcc){
			#	echo $getAcc['actid']; echo "\n";
				array_push($getData, getAccountData4($getAcc['actid']));
			}
	#	exit;	
        		if(empty($getData)){
                		$data = array("status"=>"failure","response"=>array(),"message"=>"You are trying to Login from an unregistered mobile no.");
        		}else{
                		$data = array("status"=>"success","response"=>$getData, "message"=>"Successfully Fetched");
        		}
		return $data;
	}

	function getsearchInvoice($param){
		$canid = $param['canid']; 
		$start_date = $param['start_date'];
		$end_date = $param['end_date'];
#		print_r($param); exit;
		$var = array();
		$data_L = getLedgerByAccountId($canid);
        #               print_r($data_L); exit;
                        $ledgerActNo    = $data_L['ledgerActNo'];
		if(empty($ledgerActNo)){$data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found"); return $data;}
                $data_SD = getStatutoryData($ledgerActNo);
	#               print_r($data_SD); exit;
                $TAN = "";
                if(!empty($data_SD)){
                $CountArr   =  count(array_column($data_SD, 'statutoryTypeNo'));
                   if($CountArr == 0){
                           $dataArr_SD1[0]    = $data_SD;
                   }else{
                        $dataArr_SD1      = $data_SD;
                   }
                foreach($dataArr_SD1 as $StatutoryData){
                  if($StatutoryData['statutoryTypeNo'] == 9){  $TAN = $StatutoryData['value'];}
                  if($StatutoryData['statutoryTypeNo'] == 12){  $TDS_slab = $StatutoryData['value'];}
                  if($StatutoryData['statutoryTypeNo'] == 13){  $GSTN = $StatutoryData['value'];}
                }
                }
#		echo $TAN;
#		echo $start_date." ".$end_date; exit;
		if(!empty($start_date) && !empty($end_date)){
		     if(validateDate($start_date) !== true || validateDate($end_date) !== true){
                             return   $data = array("status"=>"failure","response"=>array(),"message"=>"Invalid Date.");
                      }else{
			$req_param['canid'] = $canid;
			$st_date = date("Ymd", strtotime($start_date));
			$ed_date = date("Ymd", strtotime($end_date));
			$response=searchInvoice($req_param);
			if(!empty($response)){			
			$CountArr   =  count(array_column($response, 'invoiceNo'));
                        if($CountArr == 0) $dataArr[0] = $response;
                        else               $dataArr = $response;
			#print_r($dataArr); exit;
                        foreach($dataArr as $value){
                                if(!empty($value['invoiceNo'])){
                                $in_date = date("Ymd", strtotime($value['invoicedt']));
					#echo $st_date." ".$in_date." ".$ed_date;exit;
                                	if($st_date <= $in_date && $ed_date >= $in_date){
						$value['displayInvNo'] = substr($value['cslno'],-6);
						if(!empty($TAN)){ $tdsSlab= '10%'; $tds_amount = round(($value['invoiceCharge']*0.1),2);}
						else	{ $tdsSlab= '';	 $tds_amount = 0.00;}
						$value['tdsAmount'] = $tds_amount;
						$value['tdsSlab'] = $tdsSlab;
						$value['duedt'] = date("Y-m-d\TH:i:s+05:30", strtotime($value['duedt']. "-2 days"));
                                                $value['enddt'] = date("Y-m-d\TH:i:s+05:30", strtotime($value['enddt']. "-1 days"));
                                        	array_push($var,$value);
					#	print_r($var); exit;
                                	}
                                }
                        }// End of Loop
			}else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
                        }
		       }
		}else{
			$req_param['canid'] = $canid;
			$req_param['limit'] = 3;
			$response=searchInvoice($req_param);
			if(!empty($response)){
			$CountArr   =  count(array_column($response, 'invoiceNo'));
                        if($CountArr == 0) $dataArr[0] = $response;
                        else               $dataArr = $response;

                        foreach($dataArr as $value){
	
                                if(!empty($value['invoiceNo'])){
					$value['displayInvNo'] = substr($value['cslno'],-6);
					if(!empty($TAN)){ $tdsSlab= '10%'; $tds_amount = round(($value['invoiceCharge']*0.1),2);}
                                                else    { $tdsSlab= '';         $tds_amount = 0.00;}
                                                $value['tdsAmount'] = $tds_amount;
						$value['tdsSlab'] = $tdsSlab;
						$value['duedt'] = date("Y-m-d\TH:i:s+05:30", strtotime($value['duedt']. "-2 days"));
						$value['enddt'] = date("Y-m-d\TH:i:s+05:30", strtotime($value['enddt']. "-1 days"));
                                        	array_push($var,$value);
                                }
                        }
			}else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
                        }
		}
		#	print_r($var); exit;
		if(empty($var)){
			$data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
		}else{
			$data = array("status"=>"success","response"=>$var, "message"=>"Successfully fetched");
		}
		return $data; 
	}

	function getSRStatusList($param){
		$getSR = array();
		$response = getSRStatus($param);
#		print_r($response['Message']); exit;
		if($response['Message'] == "No Records Found" || !empty($response['Message'])){
                        $data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
                }else{
		$i = 0;
		foreach($response as $key =>$val){
		    if($val['casecategory'] != "Query" && $val['creationType'] != 'Enquiry'){
			$getSR[$i]['srNumber'] = $val['srNumber'];
			$getSR[$i]['problemType'] = $val['creationType'];
			$getSR[$i]['subType'] = $val['creationSubType'];
			$getSR[$i]['subSubType'] = $val['creationSubSubType'];
			$getSR[$i]['source'] = $val['casesource'];
			$getSR[$i]['lastUpdatedOn'] = $val['modifiedon'];
			$getSR[$i]['status'] = $val['statecode'];
			if(!empty($val['ExETR']) && $val['ExETR'] != 'NA'){
				$getSR[$i]['ETR'] = $val['ExETR'];
			}else{
				$getSR[$i]['ETR'] = $val['ETR'];
			}
		$i++;
		    }
		}// End of Loop
			$data = array("status"=>"success","response"=>$getSR, "message"=>"Successfully Fetched");
		}
                return $data;

	}	
	

	function getInvoiceContent($invoiceno){

		 $response=getInvoiceCont($invoiceno);
                if(empty($response)){
                        $data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
                }else{
                        $data = array("status"=>"success","response"=>$response,"message"=>"Successfully fetched");
                }
                return $data;
	}

	 function generateOTP(){
		$otp = rand(1000,9999);//4 digit	
		if(strlen($otp) != 4){ 
			$otp = rand(1000,9999);
		}else{
			$data = $otp;
		} 
		return $data;		
		}

	  /*This function is used to Send SMS*/
	function SendSMS($mobile_number,$message){
		$to = '91'.$mobile_number;
		$message   = urlencode($message);
		$strURL="http://smsgw.spectra.co/sendsms.php?uid=mwapi&passcode=sP@E-456tRa&to=$mobile_number&text=$message";
		$ch = curl_init($strURL);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$output_sms = curl_exec($ch);
	#	$output_sms = 'Accepted for Delivery';
		curl_close($ch);

		if (strpos($output_sms, 'Submit') !== false) {
			$sms_status = 'success';
			$sms_message='Message delivered successfully';
		}
		else{
			$sms_status = 'fail';
			#$sms_message= 'SMS Error: ' . $output_sms;
			$sms_message='Message not delivered';
		}
 		return array('status'=>$sms_status,'message'=>$sms_message);
 	    }

	 /*This function is used to Send Email*/
	function SendEmail($data){
		$data['from'] = 'donotreply@spectra.co';
		include("Mailer.class.php");
		$Mailer_obj = new Mailer();
		if(!$Mailer_obj->sentMail($data)) {
			$email_status=1;//Error in sending
		#	$email_message= '  Mailer Error: ' . $Mailer_obj->ErrorInfo;
			$email_message='Email not sent.';
		}
		else{
			$email_status=0;
			$email_message='Email sent successfully';
		}
		return array('status'=>$email_status,'message'=>$email_message);
	}

	 /*This function is used to Shorten url by bitly*/
		function shortenURL($url){
			$user_access_token = 'a5c4c247c7a04a7be1e5964e6c224e6faa27d63c';
			$params = array();
			$params['access_token'] = $user_access_token;
			$params['longUrl'] = $url;
			$params['domain'] = 'bit.ly';
			$results = bitly_get('shorten', $params);

			if($results['data']['url']!='')
				return $results['data']['url'];
			else
				return $url;
		}

	/*This function is used to Encrypt string */
		function EncryptLink($CANID,$IVRID)
		{
		        $date = date('Y-m-d H:i:s');
		        $textToEncrypt =  substr($CANID,-4)."_".substr(strtotime($date),-4)."_".$IVRID;
		        $secretKey = "middlewareapi";
		        $iv = "1234567812345678";
		        $encryptedString = openssl_encrypt($textToEncrypt, "CAMELLIA-256-OFB", $secretKey,0,$iv);
		        return $encryptedString;
		}


	/* This function is used to send sms & email for username & password */
	function forgotpwd($param){
		$can_id = $param['CANID'];
		$username = $param['username'];
		if(!empty($can_id)){
			$data_getcredentail1 = getSelfCareCredentialsbyActID($can_id);
		}elseif(!empty($username)){			
			$data_getcredentail1 = getEntityCredentials($username);	
		}else{
	#		$data = array("errorcode"=>"107","errormsg"=>"No User found.");
			return false;
		}

                if(!empty($data_getcredentail1)){
#			print_r($data_getcredentail1); exit;
			$CountArr   =  count(array_column($data_getcredentail1, 'contactNo'));
                        if($CountArr == 0) $data_getcredentail[0] = $data_getcredentail1;
                        else               $data_getcredentail = $data_getcredentail1;

			$username = $data_getcredentail[0]['credentialKey'];
			$password = $data_getcredentail[0]['credentialValue'];
			$contactId = $data_getcredentail[0]['contactNo'];
#			echo $username." ".$password; exit;
			/*  Send SMS & Email */
			$getCommArr = getContactCommMedium($contactId);
			#print_r($getCommArr); exit;
			if(!empty($getCommArr)){
				foreach($getCommArr as $getComm){
				if($getComm['commTypeNo'] == "2"){ 
					$mobile = $getComm['ident'];
					if(!empty($mobile)){
                                        $param['uid'] = $username;
                                        $param['pwd'] = $password;
                                        $sms_content = SMS_forgotpwd($param);
					$sms_status = SendSMS($mobile,$sms_content);
                                	}
				}
				if($getComm['commTypeNo'] == "4"){
					$email  = $getComm['ident'];
					if(!empty($email)){
					$param['uid'] = $username;
					$param['pwd'] = $password;
                			$param['email_id'] = $email;
					$email_content = email_forgotpwd($param);
				//	print_r($email_content);
					$email_status = SendEmail($email_content);			
					}
				}	
			    }// End of Foreach Loop
			  return true;
		        }
		 }
	}

	function changepass($param){
		$can_id = $param['canID'];
                $username = $param['username'];
		$oldpassword = $param['oldpassword'];
		$newpassword = $param['newpassword'];
		 if(strlen($newpassword) < 6 || strlen($newusername) > 10)
                {
			$data = array("status"=>"failure","response"=>array(),"message"=>"Password length should be 6 to 10.");
			return $data;
		}
                if(!empty($can_id)){
                        $data_getcredentail1 = getSelfCareCredentialsbyActID($can_id);
                }elseif(!empty($username)){
                        $data_getcredentail1 = getEntityCredentials($username);
                }else{
                        $data = array("status"=>"failure","response"=>array(),"message"=>"Oops! some thing went wrong. Please try later.");
			return $data;
                }
                if(!empty($data_getcredentail1)){
#                       print_r($data_getcredentail1); exit;
                        $CountArr   =  count(array_column($data_getcredentail1, 'contactNo'));
                        if($CountArr == 0) $data_getcredentail[0] = $data_getcredentail1;
                        else               $data_getcredentail = $data_getcredentail1;
			foreach($data_getcredentail as $data_get){
                        	$username = $data_get['credentialKey'];
	                        $password = $data_get['credentialValue'];
        	                $contactId = $data_get['contactNo'];
			}
			//echo $password." ".$oldpassword; exit;
			if($password != $oldpassword){
			$data = array("status"=>"failure","response"=>array(),"message"=>"Old password is incorrect.");		
			}else{

			if($contactId!='' && $username!='' && $newpassword!=''){
				$result = changepswd($contactId,$username,$newpassword);
				if(!empty($result['credentialKey'])){
				  /* Create OLR */
					$req_param['can_id'] = $can_id;
	                   		$req_param['type'] = "T_115";
        		                $req_param['subType'] = "ST_460";
			                $req_param['subSubType'] = "SST_862";
		                   	$req_param['caseSource'] = "20";
		                   	$req_param['caseCategory'] = "3";
		                   	$req_param['complaintDesc'] = "Update Profile Password- Success";
		                   	if(substr($can_id,0,1) == "9"){
		                	        $req_param['owner'] = "CS_SRM";
                   			}else{
	 		                       $req_param['owner'] = "CS_Home_Backend";
                   			}
					$req_param['RC1'] = "RC_2332";
					$req_param['RC2'] = "RC2_3515";
					$req_param['RC3'] = "RC3_18750";
					$req_param['RFO'] = "Password";
					$olr = createOLR($req_param);
				$data = array("status"=>"success","response"=>array(),"message"=>"Your password has been changed successfully");
				}
			}else{
				 /* Create OLR */
                                        $req_param['can_id'] = $can_id;
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_862";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = "Update Profile Password- Failure";
                                        if(substr($can_id,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2332";
                                        $req_param['RC2'] = "RC2_3515";
                                        $req_param['RC3'] = "RC3_18751";
                                        $req_param['RFO'] = "Password";
                                        $olr = createOLR($req_param);

				$data = array("status"=>"failure","response"=>array(),"message"=>"Oops! some thing went wrong. Please try later.");
			}
			}
		return $data;
		}
	}

	/* Function used to get Mobile Number and emailId*/
	function getCustomerContactInfo($orgID)
	{
		$dataArr =  getContactsByOrgId($orgID);
		#print_r($dataArr);
		for($i=0; $i<count($dataArr); $i++ ){

		}
	}

/*      function checkmassoutage($canID){
		$data=array('phone'=>'','CANID'=>trim($canID));
		$res=getMassoutage($data);
		return $res;
	 }
*/

	function sendotp($mobileno){
		if(!is_numeric($mobileno) || strlen($mobileno)<10){
		$data = array("status"=>"failure","response"=>array(),"message"=>"Please enter valid mobile no.");
		}else{
		    $detail=getAccountDetailsByMobileNumber($mobileno);		
		    if(!empty($detail) && $detail!=null ){
			$otp	= generateOTP();		
			$message=SMS_sendotp($otp);
			$mes=SendSMS($mobileno,$message);
		#print_r($mes); exit;
			if($mes['status'] == "success"){
				$data = array("status"=>"success","response"=>array("mobileNo"=>$mobileno, "OTP"=>$otp),"message"=>"OTP has been sent");
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
			}
		    }else{
			$data = array("status"=>"failure","response"=>array(),"message"=>"Please enter registered mobile no.");

			}
		}
	    return $data;
	} 

	function resendotp($mobileno, $otp){
			$mo='fail';
			$to = '';
		 if(is_numeric($mobileno) && strlen($mobileno)==10){
			$mo='tru';
		   }
		  if(is_numeric($otp) && strlen($otp)==4){
                         $to='tru';
                    }
		if($mo=='tru' && $to == 'tru'){
                	$message=SMS_sendotp($otp);
                	$mes=SendSMS($mobileno,$message);
                    if($mes['status'] == "success"){
                        $response = array("status"=>"success","response"=> array("mobileNo"=>$mobileno,"OTP"=>$otp), "message"=>"OTP has been sent.");
                    }else{
                        $response = array("status"=>"failure","response"=> array(), "message"=>"OTP has not sent.");
                   }
		}else{
			 $response = array("status"=>"failure","response"=> array(), "message"=>"Invalid mobile no or OTP.");

		}
	 return $response;
        }

	function update_mobile_sendotp($mobileno,$can_id){
                if(!is_numeric($mobileno) || strlen($mobileno)<10){
                $data = array("status"=>"failure","response"=>array(),"message"=>"Please enter valid mobile no.");
                }else{
                        $otp    = generateOTP();
                        $message = updateMobile_sendotp($otp);
                        $mes = SendSMS($mobileno,$message);
                        if($mes['status'] == "success"){
                                $data = array("status"=>"success","response"=>array("mobileNo"=>$mobileno, "OTP"=>$otp),"message"=>"OTP has been sent");
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
                        }
                }
            return $data;
        }

	function update_mobile_resendotp($mobileno,$otp){
		if(!is_numeric($otp) || strlen($otp) != 4){
                	$data = array("status"=>"failure","response"=>array(),"message"=>"Something went wrong.");
                }else{
                if(!is_numeric($mobileno) || strlen($mobileno)<10){
                	$data = array("status"=>"failure","response"=>array(),"message"=>"Please enter valid mobile no.");
                }else{
                        $message = updateMobile_sendotp($otp);
                        $mes = SendSMS($mobileno,$message);
                        if($mes['status'] == "success"){
                                $data = array("status"=>"success","response"=>array("mobileNo"=>$mobileno, "OTP"=>$otp),"message"=>"OTP has been sent");
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
                        }
                }
		}
            return $data;
        }


	function update_email_sendotp($email_id,$can_id){
                if(empty($email_id)){
                	$data = array("status"=>"failure","response"=>array(),"message"=>"Please enter valid email id.");
                }else{
                        $otp    = generateOTP();
			$param['otp'] = $otp;
                	$param['email_id'] = $email_id;
                        $message = updateEmail_sendotp($param);
			$email_status = SendEmail($message);
                        if($email_status['status'] == 0){
                                $data = array("status"=>"success","response"=>array("EmailID"=>$email_id, "OTP"=>$otp),"message"=>"OTP has been sent");
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
                        }
                }
            return $data;
        }

	function update_email_resendotp($email_id,$otp){
		if(!is_numeric($otp) || strlen($otp) != 4){
                        $data = array("status"=>"failure","response"=>array(),"message"=>"Something went wrong.");
                }else{
                if(empty($email_id)){
                        $data = array("status"=>"failure","response"=>array(),"message"=>"Please enter valid email id.");
                }else{
                      //  $otp    = generateOTP();
                        $param['otp'] = $otp;
                        $param['email_id'] = $email_id;
                        $message = updateEmail_sendotp($param);
                        $email_status = SendEmail($message);
                        if($email_status['status'] == 0){
                                $data = array("status"=>"success","response"=>array("EmailID"=>$email_id, "OTP"=>$otp),"message"=>"OTP has been sent");
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
                        }
                }
		}
            return $data;
        }

	
	function checkServiceBar($canID){
		$getData = getCustomerAccountDetail($canID);
		
                if(empty($getData)){
                        $data = array("status"=>"failure","response"=> array(), "message"=>"No Records Found.");
                }else{
                        $data = array("status"=>"success","response"=> $getData['barringFlag'], "message"=>"Successfully fetched");
                }
		return $data;

	}

	function getRatePlan($pkg_id){
		$output = array();
                $getData = getrateplanid($pkg_id);
#		print_r($getData);exit;
		$output['planNo'] = $getData['servicePlanNo'];
		$output['planName'] = $getData['description'];
		$output['planId'] = $getData['servicePlanId'];
		$output['rcCharge'] = array();
		$billRatePl=getBillingContractForRatePlan($getData['servicePlanNo']);
                $xmlResponse_bill    =  getXMLResponse($billRatePl,1);
                $xml_bill =  xml2array($xmlResponse_bill);
#		print_r($xml_bill);exit;
                $billArray=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingContractForRatePlanResponse']['return'];
                $contractNo=$billArray['cno'];
                $bnrcArr['contractNo']=$contractNo;
                $detail=getBillingChargesForBillingContract($contractNo);
                $xmlResponse_detail    =  getXMLResponse($detail,1);
                $xml_bill =  xml2array($xmlResponse_detail);
	#	print_r($xml_bill);exit;
		$billDetail=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingChargesForBillingContractResponse']['BillContractCharges']['return'];
		$i = $j =0;
		foreach($billDetail as  $val){
		if($val['chargetype'] == "R"){
			$brcno 		=  getRateClassList($val['brcno']);
                        $xmlResponse    =  getXMLResponse($brcno,3);
                        $xmlarray  	=  xml2array($xmlResponse);
                        $detail		=  $xmlarray['soap:Envelope']['soap:Body']['ns2:getRateClassListResponse']['rateClassList']['return'];
                #       print_r($detail); exit;
                        $output['rcCharge'][$i]['amount'] = !empty($detail['rate'])?$detail['rate']:'';
                        $output['rcCharge'][$i]['name'] = !empty($detail['rcid'])?$detail['rcid']:'';

			$i++;
		}elseif($val['chargetype'] == "N"){
			$bnrcno = getNonRC($val['bnrcno']);
                        $xmlResponsed    =  getXMLResponse($bnrcno,3);
                        $xmld =  xml2array($xmlResponsed);
                        $details=$xmld['soap:Envelope']['soap:Body']['ns2:getNonRCResponse']['return'];
                #       print_r($details); exit;
                        $output['nrcCharge'][$j]['amount'] = isset($details['rate'])?$details['rate']:'';
                        $output['nrcCharge'][$j]['name'] = isset($details['bnrcDesc'])?$details['bnrcDesc']:'';
			$j++;
		   }
        	}// End of Loop

                if(empty($output)){
                        $data = array("status"=>"failure","response"=> array(), "message"=>"No Records Found.");
                }else{
                        $data = array("status"=>"success","response"=> $output, "message"=>"Successfully fetched");
                }
                return $data;

        }

	function getRatePlanByCanID($can_id){
		$getCustomer =  getCustomerAccountDetail($can_id);		
		$orgID   = $getCustomer['orgId'];
		$pkg_id  = $getCustomer['subsDetails']['pkgname'];
		$subsno  = $getCustomer['subsDetails']['subsNo'];
		$output  = array();
                $getData = getrateplanid($pkg_id);
#               print_r($getData);exit;
                $output['planNo'] = $getData['servicePlanNo'];
                $output['planName'] = $getData['description'];
                $output['planId'] = $getData['servicePlanId'];
                $output['rcCharge'] = array();
		$dataRC             = getRcByOrgId($orgID);
#		print_r($dataRC); exit;
		$i = 0;
		foreach($dataRC as $rc){
			if($subsno == $rc['subsno']){
				if(strtoupper(substr($rc['brcId'],0,5)) != "TOPUP"){
			 	$output['rcCharge'][0]['amount'] += !empty($rc['amount'])?$rc['amount']:'';
				$output['rcCharge'][0]['id'] = !empty($rc['brcId'])?$rc['brcId']:'';
                	        $output['rcCharge'][0]['name'] = !empty($rc['brcDesc'])?$rc['brcDesc']:'';
			$i++;
				}
			}
		}
		$dataNRC            = getNrcByOrgId($orgID);
#		print_r($dataNRC); exit;
		$j = 0;
                foreach($dataNRC as $nrc){
                        if($subsno == $nrc['subsno']){
                                $output['nrcCharge'][$j]['amount'] = !empty($nrc['amount'])?$nrc['amount']:'';
                                $output['nrcCharge'][$j]['id'] = !empty($nrc['bnrcId'])?$nrc['bnrcId']:'';
                                $output['nrcCharge'][$j]['name'] = !empty($nrc['bnrcDesc'])?$nrc['bnrcDesc']:'';
                        $j++;
                        }
                }

#		print_r($output); exit;
		if(empty($output)){
                        $data = array("status"=>"failure","response"=> array(), "message"=>"No Records Found.");
                }else{
                        $data = array("status"=>"success","response"=> $output, "message"=>"Successfully fetched");
                }
                return $data;


	}

	function getTDSByCanId($canID){
                $getData = getLedgerByAccountId($canID);
                if(empty($getData)){
                        $data = array("errorcode"=>"102","errormsg"=>"No Records found.");
                }else{
                        $ledgerActNo = $getData['ledgerActNo'];
			$TDSdata = getStatutoryData($ledgerActNo);
			foreach($TDSdata as $value){
				if($value['statutoryTypeNo'] == '12'){
					$tds = $value['value'];
				}
			}
                }
		if(empty($tds)){
                        $data = array("errorcode"=>"102","errormsg"=>"No Records found.");
                }else{
                	$data = array("tds_percentage" => $tds);
		}
		return $data;
        }

		
	function getmrtg_graph($canid,$type){
		$data=get_startDate_endDate($type);
		$url='http://192.168.16.237/cgi-bin/graph_wt.cgi?rrdfile='.$canid.'.rrd&start='.$data['start'].'&end='.$data['end'];
	#	$url='http://192.168.16.237/cgi-bin/graph_plain.cgi?rrdfile='.$canid.'.rrd&start='.$data['start'].'&end='.$data['end'];
		$img=file_get_contents($url);
		$data = base64_encode($img);		
		return $data;
	}

	function get_startDate_endDate($reqType){
	$week=array('Sun'=>'0','Mon'=>'1','Tue'=>'2','Wed'=>'3','Thu'=>'4','Fri'=>'5','Sat'=>'6');
   switch($reqType){
/*2.1*/         case '1': /*24 hours*/
 			$today=date('d-m-Y H:i:s');
			$end=strtotime($today);
			$start_time = $end - (24 * 60 * 60);
			$starD=date('d-m-Y H:i:s',$start_time);
			$s=strtotime($starD);
			$data=array('end'=>$end,'start'=>$s);
		break;
		case '2': /*To Day */
			$today=date('d-m-Y H:i:s');
                        $end=strtotime($today);
                      #  $start_time = $end - (48 * 60 * 60);
                        $starD=date('d-m-Y 00:00:00');
                        $s=strtotime($starD);
			$data=array('end'=>$end,'start'=>$s);

		break;
		case '3': /* Yesterday or Last Day*/
                        $yesterday=date('d-m-Y', strtotime("-1 days"));
#                        $end=strtotime($today);
#                        $start_time = $end - (24 * 60 * 60);
                        $starD=date('d-m-Y 00:00:00', $yesterday);
			$endD=date('d-m-Y 00:00:00');
                        $end=strtotime($endD);
			$start=strtotime($starD);
                        $data=array('end'=>$end,'start'=>$start);

                break;

		   case '4': /*Last 7 Days*/
                        $today=date('d-m-Y H:i:s');
                        $end=strtotime($today);
			$ti=(7*24);
                        $start_time = $end - ($ti * 60 * 60);
                        $starD=date('d-m-Y H:i:s',$start_time);
                        $endD=date('d-m-Y H:i:s',$start_time);
                        $start=strtotime($starD);
                        $data=array('end'=>$end,'start'=>$start);

                break;

		case '5': /* This week */
			$today=date('d-m-Y H:i:s');
                        $tody=date("D-M-j-T-Y");
			$tday=explode('-',$tody);
		 	$we=$week[$tday[0]]; 
                        $end=strtotime($today);
                        $ti=($we*24);
                        $start_time = $end - ($ti * 60 * 60);
                        $starD=date('d-m-Y 00:00:00',$start_time);
                        $endD=date('d-m-Y 23:59:59',$start_time);
                        $start=strtotime($starD);
                        $data=array('end'=>$end,'start'=>$start);

                break;

		case '6': /* Last week*/
                   
			$std=date('d-m-Y H:i:s',strtotime('previous sunday -1 week'));
			$endD=date('d-m-Y 23:59:59',strtotime('previous saturday'));
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		 case '7': /*Last 30 days*/

                        $std=date('d-m-Y H:i:s',strtotime('-30 days'));
                        $endD=date('d-m-Y 23:59:59');
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		 case '8': /*This Month*/

                        $std=date('d-m-Y 00:00:00',strtotime('first day of this month'));
                        $endD=date('d-m-Y H:i:s');
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;

                case '9': /*Last Month*/

                        $std=date('d-m-Y 00:00:00',strtotime('first day of last month'));
                        $endD=date('d-m-Y 23:59:59' ,strtotime('last day of last month'));
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		 case '10': /*Last 365 Days*/

                        $std=date('d-m-Y H:i:s',strtotime('last year'));
                        $endD=date('d-m-Y H:i:s');
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		case '11': /*Last 2 years*/

                        $std=date('d-m-Y H:i:s',strtotime('-2 year'));
                        $endD=date('d-m-Y H:i:s');
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		case '12': /* This Year*/

                        $std=date('01-01-Y 00:00:00');
                        $endD=date('d-m-Y H:i:s');
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);
    
                break;
		 default:
                        $data = array();

		
	}
	return $data;
     }

function getAccountData4($CANID){
$verticalSegment = array(
"2"=>"Business Broadband",
"3"=>"Broadband",
"4"=>"HOME-Own",
"5"=>"HOME-Partner",
"6"=>"HOME-Own",
"7"=>"HOME-Own"
);

			$req_data = array("phone"=>'', "CANID"=>$CANID);
			$output   = getMassoutage($req_data);
			$output = utf8_encode($output); 
			$crm_dataArr = json_decode($output, true);
			#print_r($crm_dataArr);exit;
			
			foreach($crm_dataArr as $crm_data){
			if($crm_data != "No Records found"){
			#	print_r($crm_data); exit;
			$actID  = ($crm_data['CANId'])?$crm_data['CANId']:""; 
			$IsEnableSOHO = ($crm_data['IsEnableSOHO'])?$crm_data['IsEnableSOHO']:"";
			$segment =  ($crm_data['Segment'])?$crm_data['Segment']:"";
			$ProductSegment = ($crm_data['ProductSegment'])?$crm_data['ProductSegment']:"";
			$actStatus = ($crm_data['AccountStatus'])?$crm_data['AccountStatus']:"";
			$AccountActivationdate = ($crm_data['AccountActivationdate'])?$crm_data['AccountActivationdate']:"";
			$SRNumber = ($crm_data['SRNumber'])? $crm_data['SRNumber'] :"";
			$SRcasecategory = ($crm_data['SRcasecategory'])?$crm_data['SRcasecategory']:"";
			$SRCaseStatus = str_replace("\u00e2\u0080\u0093","-", $crm_data['SRCaseStatus']);
			$SRcreationTypeID = ($crm_data['SRcreationTypeID'])?$crm_data['SRcreationTypeID']:"";
			$SRcreationType = ($crm_data['SRcreationType'])? $crm_data['SRcreationType']:"";
			$SRcreationSubTypeID = ($crm_data['SRcreationSubTypeID'])?$crm_data['SRcreationSubTypeID']:"";
			$SRcreationSubType = ($crm_data['SRcreationSubType'])? $crm_data['SRcreationSubType']:"";
			$SRcreationSubSubTypeID =($crm_data['SRcreationSubSubTypeID'])? $crm_data['SRcreationSubSubTypeID'] :"";
			$SRcreationSubSubType = ($crm_data['SRcreationSubSubType'])? $crm_data['SRcreationSubSubType'] :"";
			$SRCreatedOn = ($crm_data['SRCreatedOn'])? $crm_data['SRCreatedOn'] :"";
			if(!empty($SRCreatedOn)){
			$temp_date = explode(" ",$SRCreatedOn);
			$split_date = explode("/", $temp_date[0]);
			$SRCreatedOn	= $split_date[2]."-".$split_date[1]."-".$split_date[0]." ".$temp_date[1];
			} 
			$SRETR = ($crm_data['SRETR'])? $crm_data['SRETR']:"";
			$SRExETR = ($crm_data['SRExETR'])? $crm_data['SRExETR'] :"";
                        $SRExETRFlag = isset($crm_data['SRExETRFlag'])? $crm_data['SRExETRFlag'] :"";
			$OpenSRCount = ($crm_data['OpenSRCount'])? $crm_data['OpenSRCount'] :"";
			if($OpenSRCount > 0){
				$OpenSRFlag = "true";
			}else{
				$OpenSRFlag = "false";
			}
			$MassOutage = ($crm_data['MassOutage'])? $crm_data['MassOutage'] :"";
			$MOpenSRCount = (trim($crm_data['MOpenSRCount']))? trim($crm_data['MOpenSRCount']) :"";
			if($OpenSRCount>1){
				$MultipleOpenSRFlag = "true";
			}else{
				$MultipleOpenSRFlag = "false";
			}
			$MSRNumber = ($crm_data['MSRNumber'])? $crm_data['MSRNumber'] :"";
			$Mcasecategory = ($crm_data['Mcasecategory'])? $crm_data['Mcasecategory'] :"";
			$MCaseStatus = ($crm_data['MCaseStatus'])? $crm_data['MCaseStatus'] :"";
			$McreationTypeID = ($crm_data['McreationTypeID'])? $crm_data['McreationTypeID']:"";
			$McreationType = ($crm_data['McreationType'])? $crm_data['McreationType'] :"";
			$McreationSubTypeID = ($crm_data['McreationSubTypeID'])?$crm_data['McreationSubTypeID']:"";
			$McreationSubType = ($crm_data['McreationSubType'])? $crm_data['McreationSubType'] :"";
			$McreationSubSubTypeID = ($crm_data['McreationSubSubTypeID'])?$crm_data['McreationSubSubTypeID']:"";
			$McreationSubSubType = ($crm_data['McreationSubSubType'])?$crm_data['McreationSubSubType'] :"";
			$MSRCreatedOn = ($crm_data['MSRCreatedOn'])? $crm_data['MSRCreatedOn'] :"";
			$METR = ($crm_data['METR'])? $crm_data['METR'] :"";
			$MExETR = ($crm_data['MExETR'])? $crm_data['MExETR'] :"";
			$MExETRFlag = ($crm_data['MExETRFlag'])? $crm_data['MExETRFlag'] :"";
			$MExETRCount = ($crm_data['MExETRCount'])? $crm_data['MExETRCount']:"";
			$guid = ($crm_data['guid'])? $crm_data['guid'] :"";
			$BandWidth         = ($crm_data["BandWidth"])? $crm_data["BandWidth"] :"";
                        $DownloadBandWidth = ($crm_data["DownloadBandWidth"])? $crm_data["DownloadBandWidth"] :"";
                        $UploadBandWidth   = ($crm_data["UploadBandWidth"])? $crm_data["UploadBandWidth"] :"";
				if(isset($MExETRFlag) && $MExETRFlag=='Y'){
					$METRD=$MExETR;
				}else{
					$METRD=$METR;
				}
			}else{
				if(substr($CANID,0,1)== "9"){
					$segment = "Business";
				}else{
					$segment = "Home";	
				}
			}
			}// End of Foreach
					$bandwithUtilisation = 0;
                                        $assignedBandwith = 0;
                                        $utilisationPercentage = 0;

			$getCustomer =  getCustomerAccountDetail($CANID);
			if(!empty($getCustomer)){
    				if($segment == "Home"){
                                        $verticalSegmentNo = $getCustomer['verticalSegmentNo'];
                                        if($verticalSegmentNo == "2" || $verticalSegmentNo == "3"){
                                                $segment = "Business";
                                                $ProductSegment = $verticalSegment[$verticalSegmentNo];
                                        }else{
                                                $ProductSegment = $verticalSegment[$verticalSegmentNo];
                                        }

                                }else{
                                        $industryTypeNo = $getCustomer['industryTypeNo'];
                                        $ProductSegment = $industryType_master[$industryTypeNo];
					$mrtg_param = array("canID" => $CANID, "durationType" => 1);
					$mrtg_data = checkUtilisation($mrtg_param);
					$bandwithUtilisation = !empty($mrtg_data['response']['util_bandwidth'])?$mrtg_data['response']['util_bandwidth']:0;
					$assignedBandwith = !empty($mrtg_data['response']['assigned_bandwidth'])?$mrtg_data['response']['assigned_bandwidth']:0;
					$utilisationPercentage = !empty($mrtg_data['response']['util_percentage'])?$mrtg_data['response']['util_percentage']:0;
                                }
                                if(empty($ProductSegment)){
                                        if($segment == "Home")
                                                $ProductSegment = "HOME-Own";
                                        else
                                                $ProductSegment = "SMB";
                                }
 
				$billfrequency = ($getCustomer['billCycleName']) ? $getCustomer['billCycleName'] : "";
				$OutStandingAmount = ($getCustomer['balance']) ? $getCustomer['balance']: "";
				$mobile 	= ($getCustomer['mobileno']) ? $getCustomer['mobileno']:"";
				$email         = ($getCustomer['email']) ? $getCustomer['email']:"";
//echo $OutStandingAmount; exit;
				$BillStartDate = ($getCustomer['billStartDate']) ?$getCustomer['billStartDate'] : "";
				$BillEndDate = ($getCustomer['billEndDate']) ?$getCustomer['billEndDate'] : "";
				$invoiceAmount = ($getCustomer['invoiceAmount']) ?$getCustomer['invoiceAmount'] : "";
				$invoiceCreationDate = ($getCustomer['invoiceCreationDate']) ? $getCustomer['invoiceCreationDate'] : "";
				$invoiceCreationDate = date("d/m/Y", strtotime($invoiceCreationDate));		
				$invoiceDueDate = ($getCustomer['invoiceDueDate']) ? $getCustomer['invoiceDueDate']:"";
				$invoiceDueDate = date("Y-m-d\TH:i:s+05:30", strtotime($invoiceDueDate. "-2 days"));
#echo $invoiceDueDate; exit;
				$lastPaymentAmount = ($getCustomer['lastPaymentAmount']) ? $getCustomer['lastPaymentAmount'] :"";
				$lastPaymentDate = ($getCustomer['lastPaymentDate'])? $getCustomer['lastPaymentDate'] :"";
//				$lastPaymentDate = date("d/m/Y", strtotime($lastPaymentDate));
				$AccountActivationdate = ($getCustomer['accountActivationDate'])? $getCustomer['accountActivationDate'] :"";
				$actName = ($getCustomer['accountName'])? $getCustomer['accountName'] :"";
				$product = ($getCustomer['subsDetails']['pkgname'])? $getCustomer['subsDetails']['pkgname'] :"";
				$speed 	 =  ($DownloadBandWidth) ? $DownloadBandWidth :"";
				@$fupEnabled = $getCustomer['subsDetails']['fupEnabled'];
				$FUPFlag = "false";
				if($fupEnabled == "true"){

					$planFup = explode(" ",$getCustomer['subsDetails']['planFupTotal']);
					$planFupTotal = $planFup[0];
				
					$cfFup = explode(" ",$getCustomer['subsDetails']['fupCarriedForward']);
                                        $cfFupTotal = $cfFup[0];
					
					$planDataVolume    = ($planFupTotal + $cfFupTotal);
	
					$fupCounter = explode(" ",$getCustomer['subsDetails']['fupCounterTotal']);
					if(strtoupper($fupCounter[1]) == "KB"){
						$fupCounterTotal = round($fupCounter[0]/(1024*1024),2);
					}elseif(strtoupper($fupCounter[1]) == "MB"){
						$fupCounterTotal = round($fupCounter[0]/(1024),2);
					}elseif(strtoupper($fupCounter[1]) == "GB"){
						$fupCounterTotal = round($fupCounter[0],2);
					}

					if($fupCounterTotal < 0){
						$available_data    = ($planFupTotal - $fupCounterTotal);
						$DataConsumed   = ($planDataVolume - $available_data);
					}else{
						$DataConsumed    = $fupCounterTotal;
					}

					$DataConsumptionBase = ($DataConsumed/$planDataVolume)*100; 
					$planDataVolume    =  $planDataVolume." GB";
					/*if($DataConsumptionBase > 49.1) {
						$FUPFlag = "true";
					}
					if($DataConsumptionBase > 49.1 && $DataConsumptionBase <= 79){
						$DataConsumption = 50;
					}else*/
					
					if($DataConsumptionBase > 79.1 && $DataConsumptionBase <= 99){
						$DataConsumption = 80;
					}elseif($DataConsumptionBase  > 99){
						$DataConsumption = 100;
						$FUPFlag = "true";
					}else{
						$DataConsumption = "";
					}
				}

				if(preg_match("/UL/i", $product)){
 				       $planDataVolume = "Unlimited";
				}
				
//				echo $invoiceCreationDate; exit;
				$split1 = explode("/", $invoiceCreationDate);
//				print_r($split1); exit;
				$invoiceCreationDate_format = date('Y-m-d', strtotime($split1[2]."-".$split1[1]."-".$split1[0]));
				$datetime1 = new DateTime($invoiceCreationDate_format);
				$datetime2 = new DateTime();
				$interval = $datetime1->diff($datetime2);
				$date_dif = $interval->format('%a');

				if($segment == "Home"){
                                        if($OutStandingAmount > 300)
                                                $OutstandingBalanceFlag = "true";
                                        else
                                                $OutstandingBalanceFlag = "false";
                                }else{
                                        if($OutStandingAmount > ($invoiceAmount * 0.1)){
                                                $OutstandingBalanceFlag = "true";
                                        }else{
                                                $OutstandingBalanceFlag = "false";
                                        }
                                }

				$PreBarredFlag = "false";
				if($segment == "Home"){
					if($OutstandingBalanceFlag == "true" && $date_dif > 9){
						$PreBarredFlag = "true";
						$BarringDate = date("d/m/Y", strtotime($nvoiceCreationDate_format ."+11 days"));
					}
				}
				$BarringFlag = $getCustomer['barringFlag'];
                                if($BarringFlag != 'false'){
                                        $BarringDate = $getCustomer['barringDate'];
                                        $BarringDate = date("d/m/Y", strtotime($BarringDate));
                                }

				$CancellationFlag = "false";

				if(isset($getCustomer['subsDetails']['status']) && $getCustomer['subsDetails']['status'] == 0){
					$CancellationFlag = "false";
				}else{
					$CancellationFlag = "true";
				}
				
				$PreCanceledFlag = "false";

			if($OutstandingBalanceFlag == "true"){
				if($segment == "Home"){
					if($date_dif > 25){
						$PreCanceledFlag = "true";
				                $CancellationDate = date("d/m/Y", strtotime($invoiceCreationDate_format ."+29 days"));
					}
				}
				if($segment == "Business"){
					if($ProductSegment == "SMB"){ // BIA
			
                                		if($date_dif > 75){
                                        		$PreCanceledFlag = "true";
							$CancellationDate = date("d/m/Y", strtotime($invoiceCreationDate_format ."+89 days"));
                                		}
					}else{
						if($date_dif > 50){
                                        		$PreCanceledFlag = "true";
							$CancellationDate = date("d/m/Y", strtotime($invoiceCreationDate_format ."+59 days"));
                                		}
					}
				}
			}

				/*$fupFlag = $getCustomer['subsList']['fupAction'];
				if($fupFlag == "None"){	$fupFlag = 'false';}
				else{
					$fupFlag = 'true';
					$fupStartDate = $getCustomer['subsList']['fupStartDate'];
	                                $fupStartDate = date("d/m/Y", strtotime($fupStartDate));
				}
                                $fupNextResetDate = $getCustomer['subsList']['fupNextResetDate'];
                                $fupNextResetDate = date("d/m/Y", strtotime($fupNextResetDate));
				*/
//				echo $AccountActivationdate; exit;
				//$split11 = explode("/", $AccountActivationdate);

				$AccountActivationdate_format = date('Y-m-d', strtotime($AccountActivationdate));
                                $datetime11 = new DateTime($AccountActivationdate_format);
                                $datetime21 = new DateTime();
                                $interval11 = $datetime11->diff($datetime21);
                                $date_dif11 = $interval11->format('%a');

				if($date_dif11 < 60){
					$BabyCareFlag = "true";
				}else{
					$BabyCareFlag = "false";
				}
			
				if($OutStandingAmount <= 0){
                                        $OutStandingAmount = 0;
                                }
				if($invoiceAmount <= 0){
                                        $invoiceAmount = 0;
                                }
				
				 // New Account is in progress   
                                if($CancellationFlag == "true" && $invoiceCreationDate == "01/01/1970"){
                                        $actInProgressFlag = "true";
					$CancellationFlag = "false";
                                }else{
                                        $actInProgressFlag = "false";
                                }

				$ivr_notification = array();
		
				if($CancellationFlag == "true"){
					$ivr_notification = array(array("message"=>"Your account is currently cancelled. To re-activate your account"));
				}elseif($BarringFlag == "true"){
					$ivr_notification = array(array("message"=>"Your service is currently barred. To resume your service, pay now"));
				}elseif($MassOutage == "Y"){
                                        $ivr_notification = array(array("message"=>"There is a technical issue in your area. The estimated resolution time is ".date("d/m/Y h:i a", strtotime($METRD)).". We are sorry for the inconvenience caused."));
                                }elseif($PreCanceledFlag == "true"){
					$ivr_notification = array(array("message"=>"Due to overdue outstanding balance in your account, your services will be cancelled. To resume your service, pay now"));
				}elseif($PreBarredFlag == "true"){
					if($FUPFlag == "true"){
					 $ivr_notification = array(array("message"=>array("Due to overdue outstanding, your services will be barred on ".$BarringDate.". Please clear the O/S immediately to avoid barring.", "You have consumed ".$DataConsumption."% of your data volume.")));
					}else{
                                        $ivr_notification = array(array("message"=>"Due to overdue outstanding, your services will be barred on ".$BarringDate.". Please clear the O/S immediately to avoid barring. "));
					}
				}elseif($FUPFlag == "true"){
					$ivr_notification = array(array("message"=>"You have consumed ".$DataConsumption."% of your data volume."));
				}
			}//end of if 

			
	$data = array(
					"CANId" => $CANID,
					"AccountName"=> empty($actName) ? '' : $actName, // Unify
					"Segment"=> empty($segment) ? 'Business' : $segment,
					"ProductSegment"=> empty($ProductSegment) ? 'SMB' : $ProductSegment, //In home same as 
					"Product"=> empty($product) ? '' : $product, // Unify
			#not required		"BandWidth"=> empty($BandWidth) ? '' : $BandWidth,
        		#not required		"DownloadBandWidth"=> empty($DownloadBandWidth) ? '' : $DownloadBandWidth,
        		#not required		"UploadBandWidth"=> empty($UploadBandWidth) ? '' : $UploadBandWidth,
					"Speed" => $speed,
					"BillFrequency"=> empty($billfrequency) ? '' : $billfrequency, // Unify
        				"AccountStatus"=> 'Active',  // Unify
				#	"AccountStatus"=> $actStatus,
//					"AccountActivationdate"=> date("d/m/Y h:i:s a", strtotime($AccountActivationdate)),
					"AccountActivationdate"=> empty($AccountActivationdate)?'':date("d/m/Y", strtotime($AccountActivationdate)),
        				"SRNumber"=> empty($SRNumber) ? '' : $SRNumber,
					"SRcasecategory"=> empty($SRcasecategory) ? '' : $SRcasecategory,
					"SRCaseStatus" => empty($SRCaseStatus) ? '' : $SRCaseStatus,
					"SRcreationTypeID" => empty($SRcreationTypeID) ? '' : $SRcreationTypeID,
					"SRcreationType" => empty($SRcreationType) ? '' : $SRcreationType,
					"SRcreationSubTypeID" => empty($SRcreationSubTypeID) ? '' : $SRcreationSubTypeID,
					"SRcreationSubType"  => empty($SRcreationSubType) ? '' : $SRcreationSubType,
					"SRcreationSubSubTypeID"  => empty($SRcreationSubSubTypeID) ? '' : $SRcreationSubSubTypeID,
					"SRcreationSubSubType"  => empty($SRcreationSubSubType) ? '' : $SRcreationSubSubType,
					"SRCreatedOn"  => empty($SRCreatedOn)?'':date("d/m/Y h:i a", strtotime($SRCreatedOn)),
					"SRETR"  => empty($SRETR)?'':date("d/m/Y h:i a", strtotime($SRETR)),

					"SRExETR"  => empty($SRExETR)?'':date("d/m/Y h:i a", strtotime($SRExETR)),
                                        "SRExETRFlag"  => empty($SRExETRFlag)? '': $SRExETRFlag,

					"OpenSRCount"  => empty($OpenSRCount) ? '' : $OpenSRCount,
					"OpenSRFlag" => empty($OpenSRFlag) ? '' : $OpenSRFlag,
					"MassOutage"=> empty($MassOutage) ? '' : $MassOutage,
					"MOpenSRCount"  => empty($MOpenSRCount) ? '' : $MOpenSRCount,
					"MultipleOpenSRFlag" => empty($MultipleOpenSRFlag) ? '' : $MultipleOpenSRFlag, // need to be added from CRM
					"MSRNumber"  => empty($MSRNumber) ? '' : $MSRNumber,
					"Mcasecategory"  => empty($Mcasecategory) ? '' : $Mcasecategory,
					"MCaseStatus"  => empty($MCaseStatus) ? '' : $MCaseStatus,
					"McreationTypeID"  => empty($McreationTypeID) ?'' : $McreationTypeID,
					"McreationType"  => empty($McreationType) ? '' : $McreationType ,
					"McreationSubTypeID"  => empty($McreationSubTypeID)?'' : $McreationSubTypeID,
					"McreationSubType"  => empty($McreationSubType)?'' : $McreationSubType,
					"McreationSubSubTypeID"  => empty($McreationSubSubTypeID)?'' : $McreationSubSubTypeID, 
					"McreationSubSubType"  => empty($McreationSubSubType)?'' : $McreationSubSubType,
					"MSRCreatedOn"  => empty($MSRCreatedOn)?'':date("d/m/Y h:i a", strtotime($MSRCreatedOn)),
					"ETR"  => empty($METR)?'':date("d/m/Y h:i a", strtotime($METR)),
					"ExtendedETR"  => empty($MExETR)?'':date("d/m/Y h:i a", strtotime($MExETR)),
					"ExETRFlag"  => empty($MExETRFlag)?'': $MExETRFlag,
					"ExETRCount"  => empty($MExETRCount)?'': $MExETRCount,
					"CancellationFlag" =>empty($CancellationFlag)?'': $CancellationFlag,
					"PreCanceledFlag" => empty($PreCanceledFlag)?'': $PreCanceledFlag,
					"CancelledDate"=> empty($CancellationDate)?'':$CancellationDate,
					"PreBarredFlag" =>empty($PreBarredFlag)?'': $PreBarredFlag,
					"BarringDate" => empty($BarringDate)?'':$BarringDate,
					"BarringFlag" => empty($BarringFlag)?'': $BarringFlag,
					"InvoiceCreationDate" => empty($invoiceCreationDate) ? '' : $invoiceCreationDate,
					"InvoiceAmount" => empty($invoiceAmount) ? '0' : $invoiceAmount,
					"OutstandingBalanceFlag" => empty($OutstandingBalanceFlag)? '': $OutstandingBalanceFlag,
					"OutStandingAmount" => empty($OutStandingAmount) ? '0' : $OutStandingAmount,
					"DueDate" => empty($invoiceDueDate) ? '' : date("d/m/Y", strtotime($invoiceDueDate)),
					"BillStartDate" => empty($BillStartDate) ? '' : date("d/m/Y", strtotime($BillStartDate)),
					"BillEndDate" =>  empty($BillEndDate) ? '' : date("d/m/Y", strtotime($BillEndDate)),
					"LastPaymentDate" => empty($lastPaymentDate) ? '' : date("d/m/Y", strtotime($lastPaymentDate)),
					"LastPayment" => empty($lastPaymentAmount) ? '' : $lastPaymentAmount,
					"FUPFlag" => empty($FUPFlag) ?'': $FUPFlag,
					"FUPEnabled" => empty($fupEnabled) ? '': $fupEnabled,
					//"FUPNextResetDate" => $fupNextResetDate,
					"planDataVolume" => empty($planDataVolume) ? "Unlimited" : $planDataVolume,
					#"DataConsumption" => empty($fupCounterTotal) ? "0" : $fupCounterTotal,
					"DataConsumption" => empty($DataConsumed) ? '' : $DataConsumed,
					"BabyCareFlag"  => empty($BabyCareFlag)? '': $BabyCareFlag,
					"CallRestrictionFlag" 	=> "false",
					"guid"=> empty($guid)? '':$guid ,
					"actInProgressFlag" => $actInProgressFlag,
					"mobile" => $mobile,
					"email" => $email,
					"ivrNotification" => $ivr_notification,
					"bandwithUtilisation" => $bandwithUtilisation,
					"assignedBandwith" => $assignedBandwith,
					"utilisationPercentage" => $utilisationPercentage
				);



	return $data;
//	return $return = array("status"=>"success","response"=>$data);
} // End of function

function getProfile($can_id){
$BillTo = $ShipTo = $BillingTo = $InstATo = $InstBTo = $gstn = $tan = "" ;
	$getOrg = getOrgByActID($can_id);
	$getdata =  getStatutoryData($getOrg['ledgerAccountNo']);
//	$getData     =   getLedgerByAccountId($can_id);
//	$getdata =  getStatutoryData($getData['ledgerActNo']);
	foreach($getdata as $val){
        	if($val['statutoryTypeNo'] == 13){
	        	$gstn = $val['value'];
        	}elseif($val['statutoryTypeNo'] == 9){
			$tan = $val['value'];
		}
        }//End of Loop

	if(!empty($getOrg)){
		$org_id = $getOrg['orgNo'];
		$getContacts =getContactsByOrgId($org_id);
		if(!empty($getContacts)){
		foreach($getContacts as $getContact){
#		print_r($getContact); exit;
		switch($getContact['contactTypeNo']){
                   case 1:
			$lname 	= !empty($getContact['lastName']) ?  $getContact['lastName']:'';
                        $BillTo['contactId']     = $getContact['contactNo'];
			$BillTo['name']         = $getContact['firstName'].", ".$lname;
                        $BillTo['address']      = $getContact['street'].", ".$getContact['pin'];
			$getCommM	      = getContactCommMedium($getContact['contactNo']);
			if(!empty($getCommM)){
				foreach($getCommM as $getComm){
					switch($getComm['commTypeNo']){
						case 2:
							$BillTo['mobile']   =  $getComm['ident'];
							break;
						case 4:
                                                        $BillTo['email']   =  $getComm['ident'];
                                                        break;
					}
				}
			}
			$data_getcredentail1 = getSelfCareCredentialsbyActID($can_id);	
			if(!empty($data_getcredentail1)){
				$CountArr   =  count(array_column($data_getcredentail1, 'contactNo'));
	                        if($CountArr == 0) $data_getcredentail[0] = $data_getcredentail1;
        	                else               $data_getcredentail = $data_getcredentail1;
				$BillTo['username'] = $data_getcredentail[0]['credentialKey'];
			}else{
				$BillTo['username'] = "";
			}
                        break;
                   case 2:
			$lname  = !empty($getContact['lastName']) ?  $getContact['lastName']:'';
			$ShipTo['contactId']     = $getContact['contactNo'];
                        $ShipTo['name']         = $getContact['firstName'].", ".$lname;
                        $ShipTo['address']      = $getContact['street'].", ".$getContact['pin'];
                        $getCommM             = getContactCommMedium($getContact['contactNo']);
                        if(!empty($getCommM)){
                                foreach($getCommM as $getComm){
                                        switch($getComm['commTypeNo']){
                                                case 2:
                                                        $ShipTo['mobile']   =  $getComm['ident'];
                                                        break;
                                                case 4:
                                                        $ShipTo['email']   =  $getComm['ident'];
                                                        break;
                                        }
                                }
                        }

                        break;
                   case 7:
			$lname  = !empty($getContact['lastName']) ?  $getContact['lastName']:'';
			$BillingTo['contactId']     = $getContact['contactNo'];
                        $BillingTo['name']         = $getContact['firstName'].", ".$lname;
                        $BillingTo['address']      = $getContact['street'].", ".$getContact['pin'];
                        $getCommM1             = getContactCommMedium($getContact['contactNo']);
	                $CountArr   =  count(array_column($getCommM1, 'commTypeNo'));
        	           if($CountArr == 0){
                	           $getCommM[0]    = $getCommM1;
                   	   }else{
                        	   $getCommM      = $getCommM1;
                   	  }

                        if(!empty($getCommM)){
                                foreach($getCommM as $getComm){
                                        switch($getComm['commTypeNo']){
                                                case 2:
                                                        $BillingTo['mobile']   =  $getComm['ident'];
                                                        break;
                                                case 4:
                                                        $BillingTo['email']   =  $getComm['ident'];
                                                        break;
                                        }
                                }
                        }
                        break;
                   case 8:
			$lname  = !empty($getContact['lastName']) ?  $getContact['lastName']:'';
                        $InstATo['contactId']     = $getContact['contactNo'];
                        $InstATo['name']         = $getContact['firstName'].", ".$lname;
                        $InstATo['address']      = $getContact['street'].", ".$getContact['pin'];
                        $getCommM             = getContactCommMedium($getContact['contactNo']);
                        if(!empty($getCommM)){
                                foreach($getCommM as $getComm){
                                        switch($getComm['commTypeNo']){
                                                case 2:
                                                        $InstATo['mobile']   =  $getComm['ident'];
                                                        break;
                                                case 4:
                                                        $InstATo['email']   =  $getComm['ident'];
                                                        break;
                                        }
                                }
                        }
                        break;
                   case 9:
			$lname  = !empty($getContact['lastName']) ?  $getContact['lastName']:'';
                        $InstBTo['contactId']     = $getContact['contactNo'];
                        $InstBTo['name']         = $getContact['firstName'].", ".$lname;
                        $InstBTo['address']      = $getContact['street'].", ".$getContact['pin'];
                        $getCommM             = getContactCommMedium($getContact['contactNo']);
                        if(!empty($getCommM)){
                                foreach($getCommM as $getComm){
                                        switch($getComm['commTypeNo']){
                                                case 2:
                                                        $InstBTo['mobile']   =  $getComm['ident'];
                                                        break;
                                                case 4:
                                                        $InstBTo['email']   =  $getComm['ident'];
                                                        break;
                                        }
                                }
                        }			
                        break;
	
                }
			$getProfile = array("billTo" => $BillTo, "shipTo"=> $ShipTo, "BillingTo" => $BillingTo, "installationA" =>$InstATo, "installationB" =>$InstBTo, "GSTN" =>$gstn, "TAN" =>$tan);
			$return = array("status"=>"success","response"=>$getProfile, "message"=>"Successfully Fetched");
		}// End of Foreach Loop
		}else{
			 $return = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
		}
	}else{
		 $return = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
	}
return $return;
}

function getCaseType(){
$data = array(array("case_id"=>"1", "case_desc"=>"My internet is not working"),
array("case_id"=>"2", "case_desc"=> "I am getting slow speed"),
array("case_id"=>"3", "case_desc"=> "I am getting frequent disconnection"),
array("case_id"=>"4", "case_desc"=> "I have a billing concern"),
array("case_id"=>"5", "case_desc"=> "I want to shift my connection within premises"),
array("case_id"=>"6", "case_desc"=> "I want to shift my connection outside premises"),
array("case_id"=>"7", "case_desc"=> "I want to re-activate my services"),
array("case_id"=>"8", "case_desc"=> "App related query"),
array("case_id"=>"9", "case_desc"=> "Others"),
array("case_id"=>"10", "case_desc"=> "Update billing address"),
);

return $data;
}

function create_sr($canid, $casetype, $comment){
	if($casetype == "1"){
		$req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"My Internet is not working: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
	}elseif($casetype == "2"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I am getting Slow Speed: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
	}elseif($casetype == "3"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I am getting frequent disconnection: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
	}elseif($casetype == "4"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I have a Billing concern: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
        }elseif($casetype == "5"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I want to shift my connection within Premises: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
        }elseif($casetype == "6"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I want to shift my connection outside Premises: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
	}elseif($casetype == "7"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"I want to re-activate my services: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
	}elseif($casetype == "8"){
		 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"App related query: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
        }elseif($casetype == "9"){
                 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"Others: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
        }elseif($casetype == "10"){
                 $req_data = array("Type"=>"T_106",      "SubType"=>"ST_413",    "SubSubType"=>"SST_650",  "CaseSource"=>"20",     "CaseCategory"=>"3",    "ComplaintDesc"=>"Update billing address: ".$comment,  "AccountID"=>$canid ,"Owner"=>"CS_CC_L1");
        }
	
                   $request=json_encode($req_data);
                   $data_string = $request;
                   $Create_SR_URL=crm_url."/CreateSR";
                   $output_reponse= Curl_CRM($data_string,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   return $Case_message	= trim($crm_data->Message);	
}

function validateDate($date, $format = 'Y-m-d'){
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function getAutopayStatus($canid){
  		require_once('dbepay.php');
    		$dbep = new dbcon();
		$getCustomer =  $dbep->getsistatus($canid);
		if(!empty($getCustomer)){
                $result= array("status"=>"success","response"=>array("siStatus"=>"Enable"),"message"=>"Successfully fetched");
                }else{
                $result= array("status"=>"success","response"=>array("siStatus"=>"Disable"),"message"=>"Successfully fetched");
                }

		return $result;
	}

function addTopup($canid, $amount, $topup_name, $type){
	$getData = getCustomerAccountDetail($canid);
#	print_r($getData); exit;
	$param['actno'] = $getData['accountNo'];
	$advanceBilling	= $getData['advanceBilling'];
	if(empty($getData['subsDetails']['subsNo'])){
		$return = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
		return $return;
	}
	$cycle = $getData['cycle'];
        $billStartDate = $getData['billStartDate'];
        $billEndDate = $getData['billEndDate'];
/*        $datetime1 = new DateTime($billStartDate);
        $datetime2 = new DateTime();
        $interval = $datetime1->diff($datetime2);
        $date_dif = $interval->format('%a');
*/
	$param['subsno'] = $getData['subsDetails']['subsNo'];

     if(strtoupper($type) == "NRC"){
        $getNRC = getNonRecChargesByBnrcId($topup_name);
        #print_r($getNRC); exit;
        $bnrcNo = $getNRC['bnrcNo'];

        $data1 = createAccountNRC($param['actno'],$bnrcNo,$amount,$param['subsno']);
        if(!empty($data1)){
			 /* Create OLR */
                                        $req_param['can_id'] = $canid;
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_864";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = "Topup - Success";
                                        if(substr($canid,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2334";
                                        $req_param['RC2'] = "RC2_3517";
                                        $req_param['RC3'] = "RC3_18754";
                                        $req_param['RFO'] = "Top-up";
                                        $olr = createOLR($req_param);

                        $data = array("status"=>"success","response"=>array("Id" => $data1['anno'], "srNumber" => $olr),"message"=>"Topup added successfully");

        }else{
	 		/* Create OLR */
                                        $req_param['can_id'] = $canid;
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_864";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = "Topup - Failure";
                                        if(substr($canid,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2334";
                                        $req_param['RC2'] = "RC2_3517";
                                        $req_param['RC3'] = "RC3_18755";
                                        $req_param['RFO'] = "Top-up";
                                        $olr = createOLR($req_param);
                $data = array("status"=>"failure","response"=>array(),"message"=>"Topup could not added");
        }

     }elseif(strtoupper($type) == "RC"){
		$getRC = getBillRecChargeByBrcid($topup_name);
		#print_r($getRC); exit;
		$brcno = $getRC['brcno'];
		$rateclass = getRateClassList($brcno);
	#	print_r($rateclass); exit;
		$amount = $amount * $cycle;
		$rcno = $rateclass['rcno'];
		$createDate = date("Y-m-d");
		$closeDate  = date("Y-m-d", strtotime($billStartDate));		
		$offset = 0;
		$iteration = 1;
		if($advanceBilling === false){
			$createDate = date("Y-m-d", strtotime($billStartDate));
                        $offset = 0;
                        $iteration = 0;
                        $return = createAccountRC($brcno,$rcno,$createDate,$amount,$param['actno'],$param['subsno'],$offset, $iteration);

		}else{
		
		
			$return = createAccountRC($brcno,$rcno,$createDate,$amount,$param['actno'],$param['subsno'],$offset, $iteration,$closeDate);
			$createDate = date("Y-m-d", strtotime($billStartDate));
			$offset = 0;
			$iteration = 0;
			$return = createAccountRC($brcno,$rcno,$createDate,$amount,$param['actno'],$param['subsno'],$offset, $iteration);
		}
		if(!empty($return['arno'])){
			/* Create OLR */
                                        $req_param['can_id'] = $canid;
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_864";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = "Topup - Success";
                                        if(substr($canid,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2334";
                                        $req_param['RC2'] = "RC2_3517";
                                        $req_param['RC3'] = "RC3_18754";
                                        $req_param['RFO'] = "Top-up";
                                        $olr = createOLR($req_param);
			
			 $data = array("status"=>"success","response"=>array("Id" => $return['arno'], "srNumber" => $olr),"message"=>"Topup added successfully");
		}else{
			 /* Create OLR */
                                        $req_param['can_id'] = $canid;
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_864";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = "Topup - Failure";
                                        if(substr($canid,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2334";
                                        $req_param['RC2'] = "RC2_3517";
                                        $req_param['RC3'] = "RC3_18755";
                                        $req_param['RFO'] = "Top-up";
                                        $olr = createOLR($req_param);
			
			$data = array("status"=>"failure","response"=>array(),"message"=>"Topup could not added");
		}
     }
	return $data;
}

function consumedTopup($canid){
        $getData = getCustomerAccountDetail($canid);
        #print_r($getData); exit;
        $accountNo = $getData['accountNo'];
        if(empty($getData['accountNo'])){
                $return = array("status"=>"failure","response"=>array(),"message"=>"CAN ID not found");
                return $return;
        }else{
//              $data = array();
                $getTopupList = getTopUpByActNo($accountNo);
#               	print_r($getTopupList); exit;
                foreach($getTopupList as $getTopup){
                if($getTopup['consumed'] == "false"){
			if(empty($getTopup['closeDate']) && $getTopup['type'] == 'RC'){
				$flag = 'true';
			}else{
				$flag = 'false';
			}
                	$response[] = array(
				"topup_id" => $getTopup['topUpId'], 
				"topup_name" => $getTopup['topUpId'], 
				"description" => $getTopup['topUpDescription'], 
				"created_date" => $getTopup['createddt'], 
				"price" => $getTopup['amount'], 
				"data_volume" => $getTopup['metaTagFup'],
				"type" => $getTopup['type'],
				"deactivateFlag" => $flag
				);
		 	}
                }

		if(!empty($response)){
                      $return = array("status"=>"success","response"=>$response,"message"=>"Successfully fetched.");
                }else{
                      $return = array("status"=>"success","response"=>array(), "message"=>"No Record Found");
                }
                return $return;
        }
}

function updateTAN($canid, $tan){
	$getData    =   getLedgerByAccountId($canid);
	if(empty($getData['ledgerActNo'])){
		$data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
	}else{
		$counter = false;
		$params['ledgerActNo'] = $getData['ledgerActNo'];
		$getdata =  getStatutoryData($getData['ledgerActNo']);
		if(!empty($getdata)){ // Update TAN Number
			foreach($getdata as $val){
				if($val['statutoryTypeNo'] == 9){
					$params['ledgerActNo'] = $getData['ledgerActNo'];
					$params['statutoryNo'] = $val['statutoryNo'];
					$params['statutoryTypeNo'] = $val['statutoryTypeNo'];
					$params['value'] = $tan;
					$data_SD   =  updateStatutoryData($params);
					if(!empty($data_SD)){
					$data = array("status"=>"success","response"=>$data_SD,"message"=>"TAN number updated successfully");
					}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
					}
				}
			}//End of Loop
			if($counter === false){
					$params['ledgerActNo'] = $getData['ledgerActNo'];
                                        $params['statutoryTypeNo'] = 9;
                                        $params['value'] = $tan;
                                        $data_SD   =  updateStatutoryData($params);
                                        if(!empty($data_SD)){
                                        $data = array("status"=>"success","response"=>$data_SD,"message"=>"TAN number updated successfully");
                                        }else{
                                        $data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
                                        }
	
			}
		}else{ // Add new TAN number
					$params['ledgerActNo'] = $getData['ledgerActNo'];
                                        $params['statutoryTypeNo'] = 9;
                                        $params['value'] = $tan;
                                        $data_SD   =  updateStatutoryData($params);
                                        if(!empty($data_SD)){
                                        $data = array("status"=>"success","response"=>$data_SD,"message"=>"TAN number updated successfully");
                                        }else{
                                        $data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
                                        }

		}
	}// End of Else
	return $data;
}

function updateGSTN($canid, $gstn){
	$getData    =   getLedgerByAccountId($canid);
	if(empty($getData['ledgerActNo'])){
		$data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
	}else{
		$counter = false;
		$params['ledgerActNo'] = $getData['ledgerActNo'];
		$getdata =  getStatutoryData($getData['ledgerActNo']);
#		print_r($getdata); exit;
		if(!empty($getdata)){ // Update GSTN
			foreach($getdata as $val){
				if($val['statutoryTypeNo'] == 13){
					$counter = true;
					$params['ledgerActNo'] = $getData['ledgerActNo'];
					$params['statutoryNo'] = $val['statutoryNo'];
					$params['statutoryTypeNo'] = $val['statutoryTypeNo'];
					$params['value'] = $gstn;
					$data_SD   =  updateStatutoryData($params);
					if(!empty($data_SD)){
					$data = array("status"=>"success","response"=>$data_SD,"message"=>"GST Number updated successfully");
					}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
					}
				}
			}//End of Loop
			if($counter === false){
				 	$params['ledgerActNo'] = $getData['ledgerActNo'];
                                        $params['statutoryTypeNo'] = 13;
                                        $params['value'] = $gstn;
                                        $data_SD   =  updateStatutoryData($params);
                                        if(!empty($data_SD)){
                                        $data = array("status"=>"success","response"=>$data_SD,"message"=>"GSTN updated successfully");
                                        }else{
                                        $data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
                                        }
			}
		}else{ // Add new GSTN
					$params['ledgerActNo'] = $getData['ledgerActNo'];
                                        $params['statutoryTypeNo'] = 13;
                                        $params['value'] = $gstn;
                                        $data_SD   =  updateStatutoryData($params);
                                        if(!empty($data_SD)){
                                        $data = array("status"=>"success","response"=>$data_SD,"message"=>"GSTN updated successfully");
                                        }else{
                                        $data = array("status"=>"failure","response"=>array(),"message"=>"Some thing is wrong.");
                                        }

		}
	}// End of Else
	return $data;
}


    /**
     * Validate Indian GSTIN number
     * This will work for both Permanent and Provisional GSTIN 
     * For more details please read http://13.232.24.141/validate-gstin-using-php/
     * @param string $gstin  The GSTIN number which you want to validate
     * @return boolean
     */   
	 function is_valid_gstin($gstin) {
        	$regex = "/^([0][1-9]|[1-2][0-9]|[3][0-7])([a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}[1-9a-zA-Z]{1}[zZ]{1}[0-9a-zA-Z]{1})+$/";
        	return preg_match($regex, $gstin);
    	}

	function validateTAN($tanVal){
 	   $regpan = "/^([a-zA-Z]){4}([0-9]){5}([a-zA-Z]){1}+$/";
		return preg_match($regpan, $tanVal);
	}

	function sendotpLinkAccount($can_id){
		$mobile   =  "";
		$getOrg = getOrgByActID($can_id);
		if(!empty($getOrg)){
                	$org_id = $getOrg['orgNo'];
                	$getContacts =getContactsByOrgId($org_id);
			foreach($getContacts as $getContact){
				if($getContact['contactTypeNo'] == 1){
					$getCommM             = getContactCommMedium($getContact['contactNo']);
                        if(!empty($getCommM)){
                                foreach($getCommM as $getComm){
                                        switch($getComm['commTypeNo']){
                                                case 2:
                                                        $mobile   =  $getComm['ident'];
                                                        break;
                                        }
                                }
                        }

				}
			}// End of Loop
			$otp    	= generateOTP();
                        $message	= SMS_sendotpLinkAC($otp);
                        $mes		= SendSMS($mobile,$message);
			if($mes['status'] == "success"){
                                $data = array("status"=>"success","response"=>array("mobileNo"=>$mobile, "OTP"=>$otp),"message"=>"OTP has been sent");
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"OTP has not sent");
                        }
		}else{
                 	$data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
        	}
		return $data;
	}

	function resendotpLinkAccount($mobileno, $otp){
		   $mo='fail';
                        $to = '';
                 if(is_numeric($mobileno) && strlen($mobileno)==10){
                        $mo='tru';
                   }
                  if(is_numeric($otp) && strlen($otp)==4){
                         $to='tru';
                    }
                if($mo=='tru' && $to == 'tru'){
                        $message=SMS_sendotpLinkAC($otp);
                        $mes=SendSMS($mobileno,$message);
                    if($mes['status'] == "success"){
                        $response = array("status"=>"success","response"=> array("mobileNo"=>$mobileno,"OTP"=>$otp), "message"=>"OTP has been sent.");
                    }else{
                        $response = array("status"=>"failure","response"=> array(), "message"=>"OTP has not sent.");
                   }
                }else{
                         $response = array("status"=>"failure","response"=> array(), "message"=>"Invalid mobile no or OTP.");

                }
         return $response;

	}

	function addLinkAccount($params){
		$base_canid = $params['base_canid'];
	 	$link_canid = $params['link_canid'];
		$username   = $params['username'];
		$mobileno   = $params['mobileno'];

		if(!empty($base_canid)){
                        $condition = " base_canid = '$base_canid' ";
                }

		if(!empty($username)){
			$condition = " username = '$username' "; 
		}

		if(!empty($mobileno)){
                        $condition = " mobile = '$mobileno' ";
                }

		$db_conn = dbConnection();	
		$query1 = "select * from link_account where status ='active' and link_canid='$link_canid' and ".$condition; 
                $stmt = $db_conn->prepare($query1);
                $stmt->execute();
                $row =$stmt->fetchAll(PDO::FETCH_ASSOC);
		if(!empty($row[0]['id'])){
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Link account already added");
                }else{
		$query = "insert into link_account (base_canid, username, mobile, link_canid, created_date, status) values('$base_canid','$username','$mobileno','$link_canid', now(), 'active')";
		$stmt = $db_conn->prepare($query);
		$stmt->execute();
		$last_id = $db_conn->lastInsertId();
		if($last_id > 0){
			$response = array("status"=>"success","response"=> array("id"=>$last_id), "message"=>"Link account added successfully");
		}else{
			$response = array("status"=>"failure","response"=> array(), "message"=>"Link account not added");
		}
		}
		return $response;
	}

	function getLinkAccount($params){
		$base_canid = $params['base_canid'];
                $username   = $params['username'];
                $mobileno   = $params['mobileno'];

                if(!empty($base_canid)){
                        $condition = " base_canid = '$base_canid' ";
                }

                if(!empty($username)){
                        $condition = " username = '$username' ";
                }

                if(!empty($mobileno)){
                        $condition = " mobile = '$mobileno' ";
                }

                $db_conn = dbConnection();
		$query = "select base_canid, username, mobile, link_canid from link_account where status='active' and ".$condition;
		$stmt = $db_conn->prepare($query);
                $stmt->execute();
		$row =$stmt->fetchAll(PDO::FETCH_ASSOC);
		if(!empty($row)){
			$response = array("status"=>"success","response"=>$row, "message"=>"Successfully Fetched");
		}else{
			$response = array("status"=>"failure","response"=> array(), "message"=>"No Record Found");
		}
		return $response;
	}
	
	 function removeLinkAccount($params){
                $base_canid = $params['base_canid'];
                $link_canid = $params['link_canid'];
                $username   = $params['username'];
                $mobileno   = $params['mobileno'];
		if(!empty($link_canid)){
                        $condition = " base_canid = '$base_canid' ";
                }

                if(!empty($username)){
                        $condition = " username = '$username' ";
                }

                if(!empty($mobileno)){
                        $condition = " mobile = '$mobileno' ";
                }

                $db_conn = dbConnection();
                $query = "select * from link_account where status='active' and link_canid='$link_canid' and ".$condition; 
                $stmt = $db_conn->prepare($query);
                $stmt->execute();
		$row =$stmt->fetchAll(PDO::FETCH_ASSOC);
#		print_r($row); exit;
		if(empty($row[0]['id'])){
                        $response = array("status"=>"failure","response"=> array(), "message"=>"No Record Found");
                }elseif($row[0]['status'] != "active"){
			$response = array("status"=>"failure","response"=> array(), "message"=>"Link account already removed");
		}else{
		$id = $row[0]['id'];
		$update = "update link_account set status='deactive', deactivated_date=now() where id ='$id'";
		$stmt = $db_conn->prepare($update);
                $stmt->execute();
                if($stmt->rowCount() == true){
                        $response = array("status"=>"success","response"=> array(), "message"=>"Link account removed successfully");
                }else{
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Link account not removed");
                }
		}
                return $response;
        }

	function getOrgName($canid){
		$getOrg = getOrgByActID($canid);
                if(!empty($getOrg)){
#			print_r($getOrg); exit;
                        $org_name = $getOrg['name'];
			$response = array("status"=>"success","response"=> array("name"=>$org_name), "message"=>"Successfully fetched");
		}else{
			$response = array("status"=>"failure","response"=> array(), "message"=>"No Record Found");
		}
	return $response;
	}
	
	function addContactDetail($params){
		$addcontact = array("AccountNo" =>$params['AccountNo'], "Request"=>array(array(
		"firstName"=> $params['firstName'],
		"lastName" => $params['lastName'],
		"jobTitle" => $params['jobTitle'],
		"email" => $params['email'],
		"mobilePhone" => $params['mobilePhone']
		)));
		$contact = updateContactDetails($addcontact);
		$data = json_decode($contact, true);
#		print_r($data);	
		if($data['AccountNo'] == $params['AccountNo']){
			$res = array("canID"=>$data['AccountNo'],
				"firstName"=>$data['Request']['0']['firstName'],
				"lastName"=>$data['Request']['0']['lastName'],
				"jobTitle"=>$data['Request']['0']['jobTitle'],
				"email"=>$data['Request']['0']['email'],
				"mobilePhone"=>$data['Request']['0']['mobilePhone'],
				);
			$response = array("status"=>"success","response"=> $res, "message"=>"Contact added successfully");
		}else{
			$response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
		}
		return $response;
	}
	
	 function updateContactDetail($params){
                $addcontact = array("AccountNo" =>$params['AccountNo'], "Request"=>array(array(
		"contactId"=> $params['contactId'],
                "firstName"=> $params['firstName'],
                "lastName" => $params['lastName'],
                "jobTitle" => $params['jobTitle'],
                "email" => $params['email'],
                "mobilePhone" => $params['mobilePhone']
                )));
                $contact = updateContactDetails($addcontact);
                $data = json_decode($contact, true);
#               print_r($data); 
                if($data['AccountNo'] == $params['AccountNo']){
                        $res = array("canID"=>$data['AccountNo'],
				"contactId"=> $data['Request']['0']['contactId'],
                                "firstName"=>$data['Request']['0']['firstName'],
                                "lastName"=>$data['Request']['0']['lastName'],
                                "jobTitle"=>$data['Request']['0']['jobTitle'],
                                "email"=>$data['Request']['0']['email'],
                                "mobilePhone"=>$data['Request']['0']['mobilePhone'],
                                );
                        $response = array("status"=>"success","response"=> $res, "message"=>"Contact updated successfully");
                }else{  
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");               
                }
                return $response;
        }

	function getContactDetail($params){
		$result = array();
		$getcontact = array("AccountNo" => $params['AccountNo'] );
		$contact = getContactDetails($getcontact);
                $data = json_decode($contact, true);
               #print_r($data); exit;
                if($data['AccountNo'] == $params['AccountNo']){
			foreach($data['Request'] as $val){
				$result[] = array("contactId"=> $val['contactId'],
                                "firstName"=>$val['firstName'],
                                "lastName"=>$val['lastName'],
                                "jobTitle"=>$val['jobTitle'],
                                "email"=>$val['email'],
                                "mobilePhone"=>$val['mobilePhone']);
//				array_push($result,$value);
			}
#			print_r($result); exit;
                        $res = array("canID"=>$data['AccountNo'],"results" => $result);
                        $response = array("status"=>"success","response"=> $res, "message"=>"Successfully fetched");
                }else{
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
                }
		
	return $response;
	}

	function getCity(){
		$db = referal_db();
		try{
		$query = "select city_id_crm, city_name from city where status='Active' order by city_name";
		$sth = $db->prepare($query);
                        $sth->execute();
                        $res = $sth->fetchAll(PDO::FETCH_ASSOC);
			$response = array("status"=>"success","response"=> $res, "message"=>"Successfully fetched");
                        }
                        catch (PDOException $e) {
                             //  return $e->getMessage();
			$response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
                        }
	return $response;
	}	

	function getArea($city_id){
                $db = referal_db();
                try{
                $query = "select area_id_crm, area_name  from area where city_id_crm = '$city_id' and status='Active' order by area_name";
                $sth = $db->prepare($query);
                        $sth->execute();
                        $res = $sth->fetchAll(PDO::FETCH_ASSOC);
                        $response = array("status"=>"success","response"=> $res, "message"=>"Successfully fetched");
                        }
                        catch (PDOException $e) {
                             //  return $e->getMessage();
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
                        }
        return $response;
        }

	function getSociety($area_id){
                $db = referal_db();
                try{
                $query = "select society_id_crm, society_name, domain_name  from society where area_id_crm = '$area_id' and status='Active' order by society_name";
                $sth = $db->prepare($query);
                        $sth->execute();
                        $res = $sth->fetchAll(PDO::FETCH_ASSOC);
                        $response = array("status"=>"success","response"=> $res, "message"=>"Successfully fetched");
                        }
                        catch (PDOException $e) {
                             //  return $e->getMessage();
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
                        }
        return $response;
        }

	function addReferal($param){
		$db = referal_db();
		$query = "select * from referal where ref_email = '".$param['ref_email']."' or ref_mobile = '".$param['ref_mobile']."'";
		 $sth = $db->prepare($query);
                 $sth->execute();
                 $res = $sth->fetchAll(PDO::FETCH_ASSOC);
		#print_r($res); exit;
                if(!empty($res)){
			$response = array("status"=>"failure","response"=> array(), "message"=>"You have already referred.");
			return $response;
                }
		 $society_flag = "true";
		 $getData = getCustomerAccountDetail($param['actid']); 
		 $param['actno'] = $getData['accountNo'];
	#	 print_r($param); exit;
		 $crm_status = postToCRM($param);
                 if(empty($crm_status) || $crm_status['status'] != 1){
                       $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
			return $response;
                 }
		 $sql = "INSERT INTO referal (username , actno, actid ,ref_name, ref_add,state, ref_city, ref_area, ref_society, ref_email, ref_mobile,crm_lead_id,created_on,society_flag) VALUES (
                                        '".$param['username']."',
                                        '".$param['actno']."',
                                        '".$param['actid']."',
                                        '".$param['ref_name']."',
                                        '".$param['address']."',
                                        '".$param['state']."',
                                        '".$param['city']."',
                                        '".$param['area']."',
                                        '".$param['society']."',
                                        '".$param['ref_email']."',
                                        '".$param['ref_mobile']."',
                                        '".$crm_status['crm_result']."',now(),'".$society_flag."'
                                        )";
				 $sth = $db->prepare($sql);
		                 $sth->execute();
				 $payment_id = $db->lastInsertId();
                                if($payment_id > 0){
                                         $response = array("status"=>"success","response"=> array("lead_id" => $crm_status['crm_result']), "message"=>"Your request submitted successfully");         
		                        return $response;
                                } else {
                                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");         
                        return $response;
                                }
	}
	
	function postToCRM($data){
	if(empty($data)){
		return false;
	}

	$crm_status=array();	
	try {
	    $options = array(
                'soap_version'=>SOAP_1_1,
                'trace'=>1
            );
	    
            $client = new SoapClient(CRM_LEAD, $options);
	    //$client = new SoapClient($this->crm_api, array('trace' => 1));
            $name = explode(' ', $data['ref_name']);
		$first_name = $name[0];
		$last_name = !empty($name[1] ) ?$name[1]  :'';
		$ip_address = $_SERVER['REMOTE_ADDR'];
		$a = array('USERNAME'=>'webleaduser', 
		'PASSWORD'=>'lead$web[#99',						
		'ReferalAccountID'=>$data['actid'],
		'Topic'=>'Referral from CAN ID '.$data['actid'],
		'Segment'=>'Home',
		'Sub_BusinessSegment'=>'FTTH',
		'Email_Id'=>$data['ref_email'],
		'ContactPersonName'=>$data['ref_name'],
		'SalutationID'=>'',
		'Mobile_No'=>$data['ref_mobile'],
		'Country'=>'10001',
		'City'=> $data['city'],
		'State'=> $data['state'],
		'Area'=> $data['area'],
		'FormName'=>'MGM',
		'PostalCode'=>'',
		'houseflatno'=>$data['address'],
		'Lead_First_Name'=>$first_name,
		'Lead_Last_Name'=>$last_name,
		'SocietyBuidling'=>$data['society'],
		'CreatedOnDate'=>date('Y-m-d'),
                'IPAddress'=>$ip_address
		);
		//--Log The Input parameter before sending it to CRM for lead creation
                $response = $client->__soapCall('CreateLeadForMGM', array($a));

		$fp = fopen("./logs/referral.txt","a+");
		$log_txt = "\n".date("Y-m-d H:i:s")."\n".$client->__getLastRequest();
                $log_txt .= "\n".date("Y-m-d H:i:s")."\n".$client->__getLastResponse();
                fwrite($fp,$log_txt);
                fclose($fp);
           	$crm_status['crm_result'] = $response->CreateLeadForMGMResult->Leadid;
                $crm_status['status']=$response->CreateLeadForMGMResult->status;

        	} catch (SoapFault $e) {
		$fp = fopen("./logs/referral.txt","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\n".$e;
                fwrite($fp,$log_txt);
                fclose($fp);
                $crm_status['status']=0;
	    }
	    return $crm_status;
		
	}  

	function getReferralList($actid){
                if(!empty($actid)){
			 $db = referal_db();
                         $query = "SELECT R.crm_lead_id,R.ref_name,R.ref_email,R.ref_mobile,R.status,R.created_on FROM `referal` R 
                                LEFT JOIN `city` C on R.ref_city=C.city_id_crm 
                                LEFT JOIN `area` A on R.ref_area= A.area_id_crm 
                                LEFT JOIN `society` S on R.ref_society = S.society_id_crm 
                                LEFT JOIN `state` St on R.state = St.crm_state_id
                                LEFT JOIN `referal_payment` rp on R.crm_lead_id = rp.lead_id
                                WHERE R.actid='".$actid."' group by R.id order by R.id desc";//group by R.crm_lead_id
//echo $query;
			$sth = $db->prepare($query);
	                $sth->execute();
	                $res = $sth->fetchAll(PDO::FETCH_ASSOC);
                        if(count($res) > 0 ){
                                $response = array("status"=>"success","response"=>$res, "message"=>"Successfully fetched");
                        }else {
  				$response = array("status"=>"failure","response"=> array(), "message"=>"No Records Found");                             
                        }
                } else {
                        $response = array("status"=>"failure","response"=> array(), "message"=>"Oops! something went wrong. Please try later.");
                }
	return $response;
    }

function createOLR($params){
	/* Create SR */
	$can_id = $params['can_id'];
	$type = $params['type'];
	$subType = $params['subType'];
	$subSubType = $params['subSubType'];
	$caseSource = $params['caseSource'];
	$caseCategory = $params['caseCategory'];
	$complaintDesc = $params['complaintDesc'];
	$owner = $params['owner'];
        $Case_message = Create_SR_CRM($can_id,$type,$subType,$subSubType,$caseSource,$caseCategory,$complaintDesc,$owner);
        /* Close SR */
	$RC1 = $params['RC1'];
	$RC2 = $params['RC2'];
	$RC3 = $params['RC3'];
	$RFO = $params['RFO'];

        $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>$RC1,  "Resolutioncode2"=>$RC2,   "Resolutioncode3"=>$RC3, "RFO" => $RFO, "OLR"=>"Yes");
        $data_string1 = json_encode($req_data);
        $Create_SR_URL=crm_url."/CloseSR";
        $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$can_id);
        $crm_data = json_decode($output_reponse[0]);
        $Case_message1 = trim($crm_data->Message);

return $Case_message;
}

function createSRFeedback($arr){
				    $req_param['can_id'] = $arr['canid'];
					print_r($response); exit;					
                                        $req_param['type'] = "T_115";
                                        $req_param['subType'] = "ST_460";
                                        $req_param['subSubType'] = "SST_862";
                                        $req_param['caseSource'] = "20";
                                        $req_param['caseCategory'] = "3";
                                        $req_param['complaintDesc'] = $arr['feedback'];
                                        if(substr($can_id,0,1) == "9"){
                                                $req_param['owner'] = "CS_SRM";
                                        }else{
                                               $req_param['owner'] = "CS_Home_Backend";
                                        }
                                        $req_param['RC1'] = "RC_2332";
                                        $req_param['RC2'] = "RC2_3515";
                                        $req_param['RC3'] = "RC3_18750";
                                        $req_param['RFO'] = "Password";
                                        $olr = createOLR($req_param);
                                $data = array("status"=>"success","response"=>array("srNumber"=>$olr),"message"=>"Your request has been submitted successfully");

return $data;
}

function checkSRStatus($param_arr){
	$param['SRNo'] = $param_arr['srNumber'];
	$param['CANID'] = $param_arr['canid'];
	$response = getSRStatusExt($param);
#  print_r($response['Message']); exit;
                if($response['Message'] == "No Records Found" || !empty($response['Message'])){
                        $data = array("status"=>"failure","response"=>array(),"message"=>"No Records Found");
                }else{
                $i = 0;
                foreach($response as $key =>$val){
                        $getSR[$i]['srNumber'] = $val['srNumber'];
                        $getSR[$i]['problemType'] = $val['creationType'];
                        $getSR[$i]['subType'] = $val['creationSubType'];
                        $getSR[$i]['subSubType'] = $val['creationSubSubType'];
                        $getSR[$i]['source'] = $val['casesource'];
			$temp = explode(" ",$val['createdon']);
                        $getSR[$i]['createdon'] = $temp[0]." ".date("h:i A", strtotime($temp[1]));
			$temp = explode(" ",$val['modifiedon']);
                        $getSR[$i]['lastUpdatedOn'] = $temp[0]." ".date("h:i A", strtotime($temp[1]));
                        $getSR[$i]['status'] = $val['statecode'];
			
			$getSR[$i]['ActionCode'] = $val['ActionCode'];
			$getSR[$i]['MessageTemplate'] = $val['MessageTemplate'];
			$getSR[$i]['EngineerName'] = $val['EngineerName'];
			$getSR[$i]['PreferredTime'] = $val['PreferredTime'];
#                	$i++;
			/************* Create OLR ***************/
		
			if(!empty($val['SRstatusETR'])){
                	$getSR[$i]['SRstatusETR'] = $getSR[$i]['ETR'] = $val['SRstatusETR'];
			
                        }else{
	                $getSR[$i]['SRstatusETR'] = $getSR[$i]['ETR'] = $val['ETR'];
                        }
			if(!empty($getSR[$i]['SRstatusETR'])){
				$getSR[$i]['SRstatusETR'] = date("d/m/Y h:i A", strtotime($getSR[$i]['SRstatusETR']));
				$getSR[$i]['SRETR'] = date("d-m-Y h:i A", strtotime($getSR[$i]['ETR']));
			}
			
#		echo $getSR[$i]['SRstatusETR'];
		if($getSR[$i]['status'] != "Resolved"){
			$etr_time =  date("YmdHi", strtotime($getSR[$i]['ETR']));
		
			$cur_time =  date("YmdHi");
			if($cur_time <= $etr_time){
				$case_sub_subtype = "Calling within TAT";
				if(empty($val['ActionCode'])){
					$getSR[$i]['ActionCode'] = '107';
					$getSR[$i]['MessageTemplate'] = 'Dear Customer, It will be resolved by ' . date("d-m-Y h:i A", strtotime($getSR[$i]['ETR']));
				}
			}else{
				$case_sub_subtype = "Calling outside TAT";
				$getSR[$i]['SRstatusETR'] = date("d/m/Y h:i A", strtotime("+4 hours"));
				$getSR[$i]['SRETR'] = date("d-m-Y h:i A", strtotime("+4 hours"));
				if(empty($val['ActionCode'])){
                                        $getSR[$i]['ActionCode'] = '108';
                                        $getSR[$i]['MessageTemplate'] = 'Dear Customer, Pls allow us some more time.';
                                }
			}
		}

			$strpos = strpos($val['MessageTemplate'],"<ETR>");
			if($strpos > 0){
				$getSR[$i]['MessageTemplate'] = str_replace("<ETR>",$getSR[$i]['SRETR'],$val['MessageTemplate']);
			}

/*			if($val['status'] == "On Hold - SLA clock Stop"){
                                $getSR[$i]['statuscode'] = $val['status'];
                                $getSR[$i]['SRstatusETR'] = '';
                        }
*/

		#	print_r($getSR); exit;
			$db = dbConnection();
						
			$sql_sr = "select * from case_master where case_subtype ='".$val['creationSubType']."' and case_sub_subtype='".$case_sub_subtype."'";
        		$stmt = $db->prepare($sql_sr);
         		$stmt->execute();
         		$row_sr = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(!empty($row_sr[0]['case_id'])){
				$case_complaint_desc = $row_sr[0]['case_complaint_desc']." ".$getSR[$i]['MessageTemplate'];
				$req_data = array("type"=>$row_sr[0]['case_type_id'],
		                "subType"=>$row_sr[0]['case_subtype_id'],
                		"subSubType"=>$row_sr[0]['case_sub_subtype_id'],
                 		"caseSource"=>$row_sr[0]['case_source_id'],
                 		"caseCategory"=>$row_sr[0]['case_category_id'],
                 		"complaintDesc"=> $case_complaint_desc,
                 		"can_id"=>$param_arr['canid'],
                 		"owner"=>$row_sr[0]['case_owner'],
                 		"RC1" => $row_sr[0]['case_rc1_id'],
                 		"RC2" => $row_sr[0]['case_rc2_id'],
                 		"RC3" => $row_sr[0]['case_rc3_id'],
                 		"RFO" => $row_sr[0]['case_rfo'],
         		);
        		$sr_no = createOLR($req_data);

			}
			

                }// End of Loop
                        $data = array("status"=>"success","response"=>$getSR, "message"=>"Successfully Fetched");
                }
                return $data;

}

function comparisonPlan($param){
	 $planid = "";
	 foreach($param as $val){
		$planid .= "'".$val."',";
	 }
	 $planid = substr($planid,0,-1);
	 $db = scp_topup_plan_db();
	 $query = "select rateplan_id, description, data, base_price, download, month, duration, extra_month from plan_mstr_web where rateplan_id in (".$planid.") order by field(rateplan_id, ".$planid.")"; 
	 $stmt = $db->prepare($query);
         $stmt->execute();
         $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	 if(!empty($row)){
 	 #print_r($row);exit;
	 $offers = array();
	 foreach($row as $val){
				$paid = $val['duration']-$val['extra_month'];
                                if($paid == 1) $paid_month = $paid." month";
                                else            $paid_month = $paid." months";

                                if($val['extra_month'] == 0){ $free_month = "";}
                                elseif($val['extra_month'] == 1) {$free_month = ", get ".$val['extra_month']." month free";}
                                else    {$free_month = ", get ".$val['extra_month']." months free";}

                                $frequency = $val['month']." (Pay for ".$paid_month.$free_month.")";

                                $offers1 = array("planid"=>$val['rateplan_id'],"description"=>$val['description'],"charges"=>$val['base_price'],"data"=>$val['data'], "speed"=>$val['download'], "frequency" => $frequency, "specialBenefit" => "NA");
				#print_r($offers1); exit;
                                array_push($offers,$offers1);

	 
	}
	 	$response = array("status"=>"success","response"=>$offers,"message"=>"Successfully fetched");
	}else{
		$response = array("status"=>"failure","response"=>array(),"message"=>"No Offer available!");
	}
	return $response;	
}

function proDataChargesForPlan($can_id, $plan_id){
		$getcust = getCustomerAccountDetail($can_id);
#		print_r($getcust); exit; 		
 		$cur_plan_id = $getcust['subsDetails']['pkgname'];
		$billEndDate = date("Y-m-d", strtotime($getcust['billStartDate']));
		$bill_day  = date("d", strtotime($getcust['billStartDate']));
                $bill_date = (int)date("Ymd", strtotime($billEndDate));
		$cur_date = (int)date("Ymd");
/*                if($bill_date < $cur_date){
                        $data = array("status"=>"failure","response"=>"","message"=>"Bill Start Date is less than current date.");
                        return $data;
                }
*/
		$cc_day = (int)date("d");
		$bill_day1  = (int)$bill_day;
                if($bill_day1 <= $cc_day){
                	$bill_startdate = date("Y-m-".$bill_day, strtotime("+1 month"));
                }else{
                        $bill_startdate = date("Y-m-".$bill_day);
                }
		$cycle   = $getcust['cycle'];
 		$billStartDate = date("Y-m-d", strtotime($billEndDate. " -".$cycle." months"));
		$curDate = date("Y-m-d");
		$no_of_days = date("n");
 		$total_days = date("t");

		$db = scp_topup_plan_db();
		
		$query = "select base_price, duration from plan_mstr_web where rateplan_id = '".$cur_plan_id."'";
#exit;
                $stmt = $db->prepare($query);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
#	print_r($row); exit;
                if(!empty($row)){
			$curBasePrice = $row[0]['base_price'];
                 	$curBasePrice_monthly = round($row[0]['base_price']/$row[0]['duration']);
			$curPlanPrice_perday    = round($curBasePrice_monthly/$total_days);
		}else{
			$response = array("status"=>"failure","response"=>array(),"message"=>"No charges available!");
			return $response;
		}
         	$query = "select base_price, duration from plan_mstr_web where rateplan_id = '".$plan_id."'";
         	$stmt = $db->prepare($query);
         	$stmt->execute();
         	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
         	if(!empty($row)){
			$newBasePrice = $row[0]['base_price'];
			$newBasePrice_monthly = round($row[0]['base_price']/$row[0]['duration']);
			$newPlanPrice_perday	= round($newBasePrice_monthly/$total_days);
		}else{
			$response = array("status"=>"failure","response"=>array(),"message"=>"No charges available!");
                        return $response;
		}
		
  		$datetime1 = new DateTime($billStartDate);
                $datetime2 = new DateTime($curDate);
                $interval = $datetime1->diff($datetime2);
                $date_dif = $interval->format('%a') ;
		$oldPlanCharges = round($date_dif * $curPlanPrice_perday);

		$datetime1 = new DateTime($curDate);
                $datetime2 = new DateTime($bill_startdate);
                $interval = $datetime1->diff($datetime2);
           	$date_dif = $interval->format('%a');
		$date_dif = $date_dif -1 ;
            	$newPlanCharges = round($date_dif * $newPlanPrice_perday);
		$oldPlanReversal = $curBasePrice - $oldPlanCharges;
#		$newPlanBillAmount = $newBasePrice + $newPlanCharges;
		$newPlanBillAmount = $newPlanCharges;
		$totalPayableAmount = $newPlanBillAmount - $oldPlanReversal;
		$pgDataCharges = round($totalPayableAmount * GST);
		$taxes = $pgDataCharges - $totalPayableAmount;

		$result = array("Current plan Activation date" => date("d/m/Y"), "Charges for Duration consumed" => $oldPlanCharges, "Balance amount to be reversed"=> $oldPlanReversal, "New Plan Charges" => $newPlanBillAmount, "Difference Amount Payable" => $totalPayableAmount, "taxes" => $taxes, "pgDataCharges" => $pgDataCharges); 
#print_r($result);
		$response = array("status"=>"success","response"=>$result,"message"=>"Successfully fetched");
		
return $response;
}

function proDataChargesForTopup($can_id, $topup_id){
		$getcust = getCustomerAccountDetail($can_id);
		#print_r($getcust); exit; 		
		$billEndDate = $getcust['billStartDate'];
		$db = scp_topup_plan_db();
        	$query = "select price, type from topup_mstr_web where topup_id = '".$topup_id."'";
         	$stmt = $db->prepare($query);
         	$stmt->execute();
         	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
         	if(!empty($row)){
		$type = $row[0]['type'];
		$basePrice = $row[0]['price'];
                $stDate =  date('Y-m-d');
                $enDate = date('Y-m-d', strtotime($billEndDate));
		if($type == "RC"){
            		$prodata_amount =  calculate_RCpgprice($basePrice, $stDate, $enDate); 
		}else{
			$prodata_amount =  $basePrice;
		}
		$pgDataCharges = round($prodata_amount * GST);
		$result = array("proDataCharges" => round($prodata_amount,2), "pgDataCharges" => $pgDataCharges); 
		$response = array("status"=>"success","response"=>$result,"message"=>"Successfully fetched");
		}else{
                	$response = array("status"=>"failure","response"=>array(),"message"=>"No charges available!");
	        }
		
return $response;
}

function deactivateTopupSR($can_id, $topup_id, $topup_type){
	$can_id = $can_id;
        $type = 'T_112';
        $subType = 'ST_200';
        $subSubType = 'SST_1062';
        $caseSource = '20';
        $caseCategory = '3';
        $complaintDesc = 'Top Up Deactivation'." ".$topup_type." ".$topup_id;
        $owner = 'Sales_U&R';
        $Case_message = Create_SR_CRM($can_id,$type,$subType,$subSubType,$caseSource,$caseCategory,$complaintDesc,$owner);
	if(substr($Case_message,0,2) == 'SR' || substr($Case_message,0,4) == 'Case' ){
		$response = array("status"=>"success","response"=>array("result" => "Topup deactivation request has been submitted successfully"),"message"=>"Successfully fetched");
                return $response;
	}
}// End of Function

/*
function deactivateTopup($can_id, $topup_id, $topup_type){
	$getData = getCustomerAccountDetail($can_id);
        $accountNo = $getData['accountNo'];
        if(empty($getData['accountNo'])){
                $return = array("status"=>"failure","response"=>array(),"message"=>"CAN ID not found");
                return $return;
        }else{
		if(strtoupper($topup_type) == "NRC"){
                        $return = array("status"=>"failure","response"=>array(),"message"=>"You can not deactivate the one time recurring topup.");
                        return $return;
                }
		elseif(strtoupper($topup_type) == "RC"){		
		$getRCList = getTopUpByActNo($accountNo);
		if(!empty($getRCList)){
                	foreach($getRCList as $getRC){
                		if($getRC['consumed'] == "false" && $getRC['topUpId'] == $topup_id && $getRC['type'] == 'RC'){ 
					if(strtotime($getRC['createddt']) > strtotime(date('Y-m-d'))){
						$result = removeAccountRC($getRC['arno']);
						if($result == 'MetaTagRCTopUp removed successfully')
						$response = array("status"=>"success","response"=>array("result" => "Topup deactivated successfully"),"message"=>"Successfully fetched");
						return $response;
					}
				}
			}//End of Loop	
				$return = array("status"=>"failure","response"=>array(),"message"=>"You can only delete top-up from future bill cycle");
                		return $return;
		     }
		}// End of Elseif

	}//End of Else

}// End of Function
*/

function knowMoreForPlan($plan_id){
	
	$contentText[] =  array("iconId" => "1",
                                        "title" => "Symmetric Speed",
                                        "content" => "1 Gbps upload and download speed"
                        	);
	$contentText[] =  array("iconId" => "2",
                                        "title" => "Dual-Band Gigabit Router",
                                        "content" => "Supports 2.4 Ghz & 5 Ghz bands for seamless experience of upto GigaBit speeds"
                                );
	$contentText[] =  array("iconId" => "3",
                                        "title" => "Carry forward your unused data",
                                        "content" => "Max of 2000 GB from the previous month"
                                );
	$contentText[] =  array("iconId" => "4",
                                        "title" => "Unlimited Data",
                                        "content" => "Great for connected homes, 4K Ultra HD videos, online gaming and more."
                                );

	$planDescription = 'Experience high speed internet with Unlimited data Save more with our annual subscription.';
	$response = array("status"=>"success",
			"response"=> array("planId" => $plan_id, 
                        		   "planDescription" => $planDescription,
 	                       		   "contentText" => $contentText
                        		),
			"message"=>"Successfully fetched"
			);

        return $response;
}// End of Function

function deviceSignin($param){
#		print_r($param); exit; 
		$db = dbConnection();
		$count = count($param['canid']); 
		for($i = 0; $i < $count; $i++){
			$canid = $param["canid"][$i]; 
			$token = $param["token"][$i];
			$type  = $param["type"][$i];
			$sql = "select * from device_token_master where can_id = '".$canid."' and device_token = '".$token."' and device_type = '".$type."'";
			$stmt = $db->prepare($sql);
	                $stmt->execute();
                	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(empty($row)){
			$query = "insert into device_token_master (can_id, device_token, device_type, create_date, status) values ('".$canid."','".$token."','".$type."',now(),'1') ";
			$stmt = $db->prepare($query);
                        $stmt->execute();
			}
		}// End of Loop
		$response = array("status"=>"success","response"=> array(),"message"=>"Device Sign In successfully");

        return $response;
}// End of Function

function deviceSignout($param){
#               print_r($param); exit; 
                $db = dbConnection();
                $count = count($param['canid']);
                for($i = 0; $i < $count; $i++){
                        $canid = $param["canid"][$i];
                        $token = $param["token"][$i];
                        $type  = $param["type"][$i];
                        $sql = "select * from device_token_master where can_id = '".$canid."' and device_token = '".$token."' and device_type = '".$type."'";
                        $stmt = $db->prepare($sql);
                        $stmt->execute();
                        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if(!empty($row)){
                        $query = "delete from device_token_master where can_id = '".$canid."' and device_token = '".$token."' and device_type = '".$type."'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        }
                }// End of Loop
                $response = array("status"=>"success","response"=> array(),"message"=>"Device Sign Out successfully");

        return $response;
}// End of Function

function pushNotification($title, $short_description, $detailed_description, $canids){
	$db = dbConnection();
	foreach($canids as $canid){
		$query = "insert into push_notification (canid, title, short_description, detailed_description, create_date) values ('".$canid."', '".$title."','".$short_description."','".$detailed_description."', now())";
		$stmt = $db->prepare($query);
                $stmt->execute();

	}
	$response = array("status"=>"success","response"=> array(),"message"=>"Notification has been pushed successfully");
	return $response;
}

function getchangeplanB2B($canid, $pkgname){
                 require_once('dbquery.php');
                 $db = new dbconnection();
                 $data=array("canId"=>$canid ,"pkgName"=>$pkgname);
                 $result=planchangeb2b($data);
#		 print_r($result);exit;
                 $datetime=date('Y-m-d H:i:s');
                 $sourceip=$db->get_client_ip();
                # $r=json_decode($result,true);
                 $status = $result['status'];
                 $message=$result['message'];
                 $sr_no = $result['srNo'];
                 $data=array('datetime'=>$datetime,'actid'=>$canid,'newplanid'=>$pkgname,'sr_number'=>$sr_no,'status'=>$status,                                    'source'=>'web','sourceip'=>$sourceip);
#		 print_r($data); exit;
                       $db->saveplan_changed($data);
                if($status == "Success"){
                        $response = array("status"=>"success","response"=>array("srNo"=>$sr_no),"message"=>"We have received your plan change request and will get back to you.");
                }elseif($message == "You can not request for Plan change with in 30 days"){
			$response = array("status"=>"failure","response"=>array(),"message"=>"Plan change is allowed only once in a month");
		}else{
                        $response = array("status"=>"failure","response"=>array(),"message"=>"Oops! Something has gone wrong. We are unable to take your request right now. Please try after sometime.");
                }

                return $response;
}// End Of Function

function planchangeb2b($param){
	$can_id = $param['canId'];
        $type = 'T_112';
        $subType = 'ST_162';
        $subSubType = 'SST_1061';
        $caseSource = '20';
        $caseCategory = '3';
        $complaintDesc = 'Call-Back for Plan Change.';
        $owner = 'Sales_U&R';
        $Case_message = Create_SR_CRM($can_id,$type,$subType,$subSubType,$caseSource,$caseCategory,$complaintDesc,$owner);
        if(substr($Case_message,0,2) == 'SR' || substr($Case_message,0,4) == 'Case' ){
                $response = array("status"=>"Success","srNo"=>$Case_message,"message"=>"Plan has been changed successfully");
                return $response;
        }else{
		$response = array("status"=>"Failure","srNo"=>"","message"=>"You can not request for Plan change with in 30 days");
                return $response;
	}

}// End Of Function

 
?>
