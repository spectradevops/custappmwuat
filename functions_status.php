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
		$data = array("errorcode"=>"102","errormsg"=>"No Records found.");	
	}else{
	if($getCredentials['credentialValue'] == $password){
		$actID = $getCredentials['actIds'];
		$getData = getAccountData4($actID);
		if(empty($getData)){
                	$data = array("errorcode"=>"102","errormsg"=>"No Records found.");      
        	}else{
			$data = $getData;
		}
		
	}else{
		$data = array("errorcode"=>"103","errormsg"=>"Invalid Username or Password.");
	}
	}
return $data;
}


	function getAccountByMobile($mobile){
        	$getDataArr = getAccountDetailsByMobileNumber($mobile);
			foreach($getDataArr as $getAcc){
				$getData[] = getAccountData4($getAcc['actid']);
			}
        		if(empty($getData)){
                		$data = array("errorcode"=>"102","errormsg"=>"No Records found.");
        		}else{
                		$data = $getData;
        		}
		return $data;
	}

	function getsearchInvoice($canid){
		$response=searchInvoice($canid);
		if(empty($response)){
			$data = array("errorcode"=>"102","errormsg"=>"No Records found.");
		}else{
			$data=$response;
		}
		return $data;
	}

	function getInvoiceContent($invoiceno){

		 $response=getInvoiceCont($invoiceno);
                if(empty($response)){
                        $data = array("errorcode"=>"105","errormsg"=>"No Records found.");
                }else{
                        $data=$response;
                }
                return $data;
	}

	 function generateOTP(){
		$otp = rand(1000,9999);//4 digit	
		return $otp;		
		}

	  /*This function is used to Send SMS*/
	function SendSMS($mobile_number,$message){
		$to = '91'.$mobile_number;
		$message   = urlencode($message);
		$strURL="http://smsgw.spectra.co/sendsms.php?uid=mwapi&passcode=sP@E-456tRa&to=$mobile_number&text=$message";
		$ch = curl_init($strURL);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$output_sms = curl_exec($ch);
		curl_close($ch);

		if (strpos($output_sms, 'Accepted for Delivery') !== false) {
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
	function forgotpwd($username, $can_id){
		if(!empty($can_id)){
			$data_getcredentail = getSelfCareCredentialsbyActID($can_id);
		}elseif(!empty($username)){			
			$data_getcredentail = getEntityCredentials($username);	
		}else{
			$data = array("errorcode"=>"107","errormsg"=>"No User found.");
			return $data;
		}

                if(!empty($data_getcredentail)){
			#print_r($data_getcredentail);
			$username = $data_getcredentail['credentialKey'];
			$password = $data_getcredentail['credentialValue'];
			$contactId = $data_getcredentail['contactNo'];
			#echo $username." ".$password;
			/*  Send SMS & Email */
			$getCommArr = getContactCommMedium($contactId);
#			print_r($getCommArr);
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
					echo $email_status = SendEmail($email_content);			
					}
				}	
			    }// End of Foreach Loop
			  return true;
		        }
		 }
	}

	function changepass($contactno,$username,$newpassword){
		if($contactno!='' && $username!='' && $newpassword!=''){
			$res=changepswd($contactno,$username,$newpassword);
		}else{
			$res=array('Error'=>'108','Message'=>'Blank Parameter.');
		}
		return $res;
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
		$otp=generateOTP();		
		$message=SMS_sendotp($otp);
		$mes=SendSMS($mobileno,$message);
		#print_r($mes); exit;
		if($mes['status'] == "success"){
			return $otp;
		}else{
			return $mes;
		}
	} 

	function resendotp($mobileno, $otp){
                $message=SMS_sendotp($otp);
                $mes=SendSMS($mobileno,$message);
                #print_r($mes); exit;
                if($mes['status'] == "success"){
                        $response = $data = array("status"=>"success","response"=>"OTP has been sent.");
                }else{
                        $response = $data = array("status"=>"failure","response"=>"OTP has not sent.");
                }
	 return $response;
        }
	
	function checkServiceBar($canID){
		$getData = getCustomerAccountDetail($canID);
		
                if(empty($getData)){
                        $data = array("errorcode"=>"102","errormsg"=>"No Records found.");
                }else{
                        $data = array("barringStatus" => $getData['barringFlag']);
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

	function getTransactionByCanId($canID, $from_date, $to_date){
		$getData = getOrgByActID($canID);
		#print_r($getData); exit;
                if(empty($getData)){
                        $data = array("errorcode"=>"102","errormsg"=>"No Records found.");
                }else{
                        $orgNo = $getData['orgNo'];
                        $p_data = getTransactionHistoryListByOrgNo($orgNo, $from_date, $to_date);
		//	print_r($data);exit;
                }
                if(empty($p_data)){
                        $data = array("errorcode"=>"102","errormsg"=>"No Records found.");
                }else{
                        $data = $p_data;
                }

                return $data;
}

		
	function getmrtg_graph($canid,$type){
		$data=get_startDate_endDate($type);
#		$url='http://192.168.16.237/cgi-bin/graph_wt.cgi?rrdfile='.$canid.'.rrd&start='.$data['start'].'&end='.$data['end'];
		$url='http://192.168.16.237/cgi-bin/graph_plain.cgi?rrdfile='.$canid.'.rrd&start='.$data['start'].'&end='.$data['end'];
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
                        $start_time = $end - (48 * 60 * 60);
                        $starD=date('d-m-Y H:i:s',$start_time);
                        $s=strtotime($starD);
			$data=array('end'=>$end,'start'=>$s);

		break;
		case '3': /* Yesterday or Last Day*/
                        $today=date('d-m-Y H:i:s');
                        $end=strtotime($today);
                        $start_time = $end - (24 * 60 * 60);
                        $starD=date('d-m-Y 00:00:00',$start_time);
			$endD=date('d-m-Y 23:59:59',$start_time);
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

                        $std=date('d-m-Y H:i:s',strtotime('first day of last month'));
                        $endD=date('d-m-Y 23:59:59' ,strtotime('last day of last month'));
                        $end=strtotime($endD);
                        $start=strtotime($std);
                        $data=array('end'=>$end,'start'=>$start);

                break;
		 case '10': /*Last 365 Days*/

                        $std=date('d-m-Y H:i:s',strtotime('-365 days'));
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
		case '12': /*Last 2 years*/

                        $std=date('01-01-Y H:i:s',strtotime('first day of this year'));
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
//		echo $CANID; exit;
		 if(!empty($CANID)){
                        $len = strlen($CANID);
                        if($len < 6 || $len > 8){
                                $data = array("errorcode"=>"104","errormsg"=>"No Records found.");
                                 header('Content-type: application/json');
                                 echo json_encode($data);
                                 exit;
                        }
	          }
		
			$req_data = array("phone"=>'', "CANID"=>$CANID);
			$output   = getMassoutage($req_data);
			$output = utf8_encode($output); 
			$crm_dataArr = json_decode($output, true);
		#	print_r($crm_dataArr);exit;
			foreach($crm_dataArr as $crm_data){
			$actID  = $crm_data['CANId']; 
			$IsEnableSOHO = $crm_data['IsEnableSOHO'];
			$segment =  $crm_data['Segment'];
			$ProductSegment = $crm_data['ProductSegment'];
			$actStatus = $crm_data['AccountStatus'];
			$AccountActivationdate = $crm_data['AccountActivationdate'];

			$SRNumber = $crm_data['SRNumber'];
			$SRcasecategory = $crm_data['SRcasecategory'];
			$SRCaseStatus = str_replace("\u00e2\u0080\u0093","-", $crm_data['SRCaseStatus']);
			$SRcreationTypeID = $crm_data['SRcreationTypeID'];
			$SRcreationType = $crm_data['SRcreationType'];
			$SRcreationSubTypeID = $crm_data['SRcreationSubTypeID'];
			$SRcreationSubType = $crm_data['SRcreationSubType'];
			$SRcreationSubSubTypeID = $crm_data['SRcreationSubSubTypeID'];
			$SRcreationSubSubType = $crm_data['SRcreationSubSubType'];
			$SRCreatedOn = $crm_data['SRCreatedOn'];
			if(!empty($SRCreatedOn)){
			$temp_date = explode(" ",$SRCreatedOn);
			$split_date = explode("/", $temp_date[0]);
			$SRCreatedOn	= $split_date[2]."-".$split_date[1]."-".$split_date[0]." ".$temp_date[1];
			} 
			$SRETR = $crm_data['SRETR'];
			$SRExETR = $crm_data['SRExETR'];
                        $SRExETRFlag = $crm_data['SRExETRFlag'];
			$OpenSRCount = $crm_data['OpenSRCount'];
			if($OpenSRCount > 0){
				$OpenSRFlag = "true";
			}else{
				$OpenSRFlag = "false";
			}
			$MassOutage = $crm_data['MassOutage'];
			$MOpenSRCount = trim($crm_data['MOpenSRCount']);
			if($OpenSRCount>1){
				$MultipleOpenSRFlag = "true";
			}else{
				$MultipleOpenSRFlag = "false";
			}
			$MSRNumber = $crm_data['MSRNumber'];
			$Mcasecategory = $crm_data['Mcasecategory'];
			$MCaseStatus = $crm_data['MCaseStatus'];
			$McreationTypeID = $crm_data['McreationTypeID'];
			$McreationType = $crm_data['McreationType'];
			$McreationSubTypeID = $crm_data['McreationSubTypeID'];
			$McreationSubType = $crm_data['McreationSubType'];
			$McreationSubSubTypeID = $crm_data['McreationSubSubTypeID'];
			$McreationSubSubType = $crm_data['McreationSubSubType'];
			$MSRCreatedOn = $crm_data['MSRCreatedOn'];
			$METR = $crm_data['METR'];
			$MExETR = $crm_data['MExETR'];
			$MExETRFlag = $crm_data['MExETRFlag'];
			$MExETRCount = $crm_data['MExETRCount'];
			$guid = $crm_data['guid'];

			$BandWidth         = $crm_data["BandWidth"];
                        $DownloadBandWidth = $crm_data["DownloadBandWidth"];
                        $UploadBandWidth   = $crm_data["UploadBandWidth"];
			}// End of Foreach

			$getCustomer =  getCustomerAccountDetail($CANID);
			if(!empty($getCustomer)){
				if($segment == "Home"){
                                        $verticalSegmentNo = $getCustomer['verticalSegmentNo'];
                                        if($verticalSegmentNo == "2" || $verticalSegmentNo == "3"){
                                                $segment = "Business";
						$ProductSegment = $verticalSegment[$verticalSegmentNo];
                                        }else{
						$ProductSegment = "HOME-Own";
					}
                                        
                                }else{
                                        $industryTypeNo = $getCustomer['industryTypeNo'];
                                        $ProductSegment = $industryType_master[$industryTypeNo];
                                }
				if(empty($ProductSegment)){
                                	if($segment == "Home") 
						$ProductSegment = "HOME-Own";
                                	else 
						$ProductSegment = "SMB";
                        	}
     
				$billfrequency = $getCustomer['billCycleName'];
				$OutStandingAmount = $getCustomer['balance'];
//echo $OutStandingAmount; exit;
				$BillStartDate = $getCustomer['billStartDate'];
				$BillEndDate = $getCustomer['billEndDate'];
				$invoiceAmount = $getCustomer['invoiceAmount'];
				$invoiceCreationDate = $getCustomer['invoiceCreationDate'];
				$invoiceCreationDate = date("d/m/Y", strtotime($invoiceCreationDate));		
				$invoiceDueDate = $getCustomer['invoiceDueDate'];
//				$invoiceDueDate = date("d/m/Y", strtotime($invoiceDueDate));
				$lastPaymentAmount = $getCustomer['lastPaymentAmount'];
				$lastPaymentDate = $getCustomer['lastPaymentDate'];
//				$lastPaymentDate = date("d/m/Y", strtotime($lastPaymentDate));
				$AccountActivationdate = $getCustomer['accountActivationDate'];
				$actName = $getCustomer['accountName'];
				$product = $getCustomer['subsList']['pkgname'];
				@$fupEnabled = $getCustomer['subsList']['fupEnabled'];
				$FUPFlag = "false";
				if($fupEnabled == "true"){
					$planFupTotal = $getCustomer['subsList']['planFupTotal'];
					$fupCounterTotal = $getCustomer['subsList']['fupCounterTotal'];
					$DataConsumptionBase = ($fupCounterTotal/$planFupTotal)*100;
					if($DataConsumptionBase > 49.1) {
						$FUPFlag = "true";
					}
					if($DataConsumptionBase > 49.1 && $DataConsumptionBase <= 79){
						$DataConsumption = 50;
					}elseif($DataConsumptionBase > 79.1 && $DataConsumptionBase <= 99){
						$DataConsumption = 80;
					}elseif($DataConsumptionBase  > 99){
						$DataConsumption = 100;
					}else{
						$DataConsumption = "";
					}
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

				if(isset($getCustomer['subsList']['status']) && $getCustomer['subsList']['status'] == 0){
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

			}//end of if 

			
	$data[] = array(
					"CANId" => $actID,
					"AccountName"=> empty($actName) ? '' : $actName, // Unify
					"Segment"=> empty($segment) ? 'Business' : $segment,
					"ProductSegment"=> empty($ProductSegment) ? 'SMB' : $ProductSegment, //In home same as 
					"Product"=> empty($product) ? '' : $product, // Unify
			#not required		"BandWidth"=> empty($BandWidth) ? '' : $BandWidth,
        		#not required		"DownloadBandWidth"=> empty($DownloadBandWidth) ? '' : $DownloadBandWidth,
        		#not required		"UploadBandWidth"=> empty($UploadBandWidth) ? '' : $UploadBandWidth,
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
                                        "SRExETRFlag"  => $SRExETRFlag,

					"OpenSRCount"  => empty($OpenSRCount) ? '' : $OpenSRCount,
					"OpenSRFlag" => empty($OpenSRFlag) ? '' : $OpenSRFlag,
					"MassOutage"=> empty($MassOutage) ? '' : $MassOutage,
					"MOpenSRCount"  => empty($MOpenSRCount) ? '' : $MOpenSRCount,
					"MultipleOpenSRFlag" => empty($MultipleOpenSRFlag) ? '' : $MultipleOpenSRFlag, // need to be added from CRM
					"MSRNumber"  => empty($MSRNumber) ? '' : $MSRNumber,
					"Mcasecategory"  => $Mcasecategory,
					"MCaseStatus"  => $MCaseStatus,
					"McreationTypeID"  => $McreationTypeID,
					"McreationType"  => $McreationType,
					"McreationSubTypeID"  => $McreationSubTypeID,
					"McreationSubType"  => $McreationSubType,
					"McreationSubSubTypeID"  => $McreationSubSubTypeID,
					"McreationSubSubType"  => $McreationSubSubType,
					"MSRCreatedOn"  => empty($MSRCreatedOn)?'':date("d/m/Y h:i a", strtotime($MSRCreatedOn)),
					"ETR"  => empty($METR)?'':date("d/m/Y h:i a", strtotime($METR)),
					"ExtendedETR"  => empty($MExETR)?'':date("d/m/Y h:i a", strtotime($MExETR)),
					"ExETRFlag"  => $MExETRFlag,
					"ExETRCount"  => $MExETRCount,
					"CancellationFlag" => $CancellationFlag,
					"PreCanceledFlag" => $PreCanceledFlag,
					"CancelledDate"=> empty($CancellationDate)?'':$CancellationDate,
					"PreBarredFlag" =>$PreBarredFlag,
					"BarringDate" => empty($BarringDate)?'':$BarringDate,
					"BarringFlag" => $BarringFlag,
					"InvoiceCreationDate" => empty($invoiceCreationDate) ? '' : $invoiceCreationDate,
					"InvoiceAmount" => empty($invoiceAmount) ? '0' : $invoiceAmount,
					"OutstandingBalanceFlag" => $OutstandingBalanceFlag,
					"OutStandingAmount" => empty($OutStandingAmount) ? '0' : $OutStandingAmount,
					"DueDate" => empty($invoiceDueDate) ? '' : date("d/m/Y", strtotime($invoiceDueDate)),
					"BillStartDate" => empty($BillStartDate) ? '' : date("d/m/Y", strtotime($BillStartDate)),
					"BillEndDate" =>  empty($BillEndDate) ? '' : date("d/m/Y", strtotime($BillEndDate)),
					"LastPaymentDate" => empty($lastPaymentDate) ? '' : date("d/m/Y", strtotime($lastPaymentDate)),
					"LastPayment" => empty($lastPaymentAmount) ? '' : $lastPaymentAmount,
					"FUPFlag" => $FUPFlag,
					//"FUPStartDate" => $fupStartDate,
					//"FUPNextResetDate" => $fupNextResetDate,
					"DataConsumption" => empty($DataConsumption) ? '' : $DataConsumption,
					"BabyCareFlag"  => $BabyCareFlag,
					"CallRestrictionFlag" 	=> "false",
					"guid"=> $guid
				);




	return $data;
} // End of function


?>
