<?php 
/*
Author:Harpreet Kaur,Amit Dubey
------------------------------------------------------------------------------
1)   Middle-ware API 
-------------------------------------------------------------------------------
1.1) Request by Consumer App will be land on index.php
1.2) Create log for each request and response.
1.3) On the type of requested param, each request would be responded at the same time.
1.4) In case face any issue related response, will throw error message.
-------------------------------------------------------------------------------
2)   List of API
-------------------------------------------------------------------------------
2.1) Login API - By username & Password
2.2) Generate OTP and send on Mobile no and return OTP
2.3) Login API - By  mobile no
2.4) Best Offers for you API - By CANID & Baseplan
2.5) Change Plan API - From Best offers user select plan and can change
2.6) Topup API - By CANDID & Baseplane
2.7) Get List of invoice base on each CAN ID 
2.8) Get Content(HTML) for each invoice on base of invoice number
2.9) Get SR Status on base of CAN ID or SR number
2.10) Get usage data (session history) on base of user ORGID
2.11) Send SMS to the user
2.12) Create SR in CRM base of CAN ID
2.13) Close SR on base of SR Number
2.14) Payment transaction details on orgid, setected to date and from date 
--------------------------------------------------------------------------------
3) List of Error
-------------------------------------------------------------------------------- 

*/
   	 header('Access-Control-Allow-Origin: *');
         header('Access-Control-Allow-Methods:  POST');
         header('Access-Control-Max-Age: 1000');
         header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');


	#require("config.php");
	require("functions.php");

	$json = file_get_contents('php://input');
	$timestamp = date("Y-m-d H:i:s");
	$LOG = $timestamp." ".$_SERVER['REMOTE_ADDR']." ".$json;
	file_put_contents("logs/request.log","\n".$LOG, FILE_APPEND | LOCK_EX);
	$obj = json_decode($json);
#	print_r($obj);exit;
	$action =trim($obj->Action);
 	$key    = trim($obj->Authkey);
//echo "\n";
	$authkey = 'AdgT68HnjkehEqlkd4';
	if($key == $authkey){
   	switch($action){
/*2.1*/ 	case 'getAccountByPassword': 
			$username = $obj->Username?trim($obj->Username):'';
        		$password = $obj->Password?base64_decode(trim($obj->Password)):'';
			if(!empty($username) && !empty($password)){
				$data = getAccountByPassword($username, $password);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
		break;

/*2.2*/		case 'generateOTP': 
			$data = generateOTP();
                break;

/*2.3*/		case 'getAccountByMobile':  
			$mobile  = $obj->Mobile?trim($obj->Mobile):'';
			if(!empty($mobile)){
				$data = getAccountByMobile($mobile);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
                break;

/*2.4*/		case 'getOffers': 
			$canid=$obj->canID?trim($obj->canID):'';
	        	$baseplan=$obj->basePlan?trim($obj->basePlan):'';
			$data=getoffers($key,$canid,$baseplan);
		break;

/*2.5*/		case 'changeplan': 
        		$canid = $obj->canId?trim($obj->canId):'';
        		$pkgname = $obj->pkgName?trim($obj->pkgName):'';
			if(!empty($canid) && !empty($pkgname)){
				$req_data = array("phone"=>'', "CANID"=>$canid);
	                        $output   = getMassoutage($req_data);
	                        $output = utf8_encode($output);
        	                $crm_dataArr = json_decode($output, true);
                        	foreach($crm_dataArr as $crm_data){
	                        	if($crm_data != "No Records found"){
		                        	$segment =  ($crm_data['Segment'])?$crm_data['Segment']:"";
					}
				}
				if($segment == "Home"){
					$data=getchangeplan($canid,$pkgname);
				}else{
					$data=getchangeplanB2B($canid,$pkgname);
				}
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
	        break; 

/*2.6*/  	case 'getTopup': 
               		$canid = $obj->canID?trim($obj->canID):'';
               		$baseplan = $obj->basePlan?trim($obj->basePlan):'';
	       		$data = gettopups($canid,$baseplan);
                break;

/*2.7*/	 	case 'getInvoiceList':
			$param['canid'] = $obj->canID?trim($obj->canID):'';
			$param['start_date'] = !empty($obj->startDate)?trim($obj->startDate):'';
			$param['end_date'] = !empty($obj->endDate)?trim($obj->endDate):'';
			if(!empty($param['canid'])){
				$data = getsearchInvoice($param);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");	
			}
		break;

/*2.8*/	 	case 'getInvoiceContent':
                	$invoiceno=$obj->invoiceNo?trim($obj->invoiceNo):'';
			if(!empty($invoiceno)){
	                	$data=getInvoiceContent($invoiceno);
#				print_r($data); exit; // Exit is used for return HTML format
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
		break;

/*2.9*/		case 'getSRstatus':
			$canid=$obj->canID?trim($obj->canID):'';
			$srno=$obj->srNumber?trim($obj->srNumber):'';
			if(empty($canid) && empty($srno)){
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank");
			}else{
				$param = array("SRNo"=>$srno, "CANID"=>$canid);
				$data=getSRStatusList($param);
			}
		break;

/*2.10*/	case 'getSessionhistory':
		      $canid=$obj->canID?trim($obj->canID):'';
		      $fromdate=$obj->fromDate?trim($obj->fromDate):''; // YYYY-mm-dd
		      $todate=$obj->toDate?trim($obj->toDate):''; // YYYY-mm-dd
		      if(empty($canid) || empty($fromdate) || empty($todate)){
                              $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                      }else{
		      if(validateDate($fromdate) !== true || validateDate($todate) !== true){
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Invalid Date.");
                      }else{
		      		$getUsage=getSessionhistoryByCanId($canid,$fromdate,$todate);
		      		if($getUsage == false){
					$data = array("status"=>"failure","response"=>array(),"message"=>"No Record Found");
				}else{
					$data = array("status"=>"success","response"=>$getUsage,"message"=>"Successfully fetched");
				}
			   }
		      }	
		break;

/*2.11*/	case 'sendSMS':
		      $mobile  = $obj->Mobile?trim($obj->Mobile):'';
		      $smsContent = $obj->smsContent?trim($obj->smsContent):'';
                      $data=SendSMS($mobile,$smsContent);
                break;
	
/*2.12	case 'createSR':
		      $canid  = $obj->canID?trim($obj->canID):'';
		      $type  = $obj->type?trim($obj->type):'';
		      $subType  = $obj->subType?trim($obj->subType):'';
		      $subSubType  = $obj->subSubType?trim($obj->subSubType):'';
		      $caseSource  = $obj->caseSource?trim($obj->caseSource):'';
		      $caseCategory  = $obj->caseCategory?trim($obj->caseCategory):'';
		      $complainDesc  = $obj->complainDesc?trim($obj->complainDesc):'';
		      $owner  = $obj->owner?trim($obj->owner):'';
		      
		      $data=Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
		break;
*/
		case 'createSR':
                      $canid  = $obj->canID?trim($obj->canID):'';
                      $casetype  = $obj->caseType?trim($obj->caseType):'';
		      $comment  = $obj->comment?trim($obj->comment):'';
		      if(empty($canid) || empty($casetype)){
                              $data = array("status"=>"failure","response"=>array(),"message"=>"Please select request type.");
                      }else{
			      $getCase = create_sr($canid, $casetype, $comment);	
			      if(!empty($getCase) && substr($getCase,0,2) == "SR"){
					$data = array("status"=>"success","response"=>$getCase,"message"=>"SR has been created");
			      }elseif(preg_match('/active/', $getCase)){
					$data = array("status"=>"failure","response"=>$getCase,"message"=>"SR already open");
				}else{
					$data = array("status"=>"failure","response"=>$getCase,"message"=>"SR does not created");
			      }
			}	
		break;

		case 'getcasetype':
			$getCase = getCaseType();
			if(!empty($getCase)){
				$data = array("status"=>"success","response"=>$getCase,"message"=>"Successfully Fetched");
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"No Record Found");
			}
		break;

/*2.13*/        case 'closeSR':
		      $srno=$obj->sr_number?$obj->sr_number:'';
		      $resolutioncode1=$obj->resolutioncode1?$obj->resolutioncode1:'';
		      $resolutioncode2=$obj->resolutioncode2?$obj->resolutioncode2:'';
 		      $resolutioncode3=$obj->resolutioncode3?$obj->resolutioncode3:'';
                      $rfo=$obj->rfo?$obj->rfo:'';
		      $data=Close_sr($srno,$resolutioncode1,$resolutioncode2,$resolutioncode3,$rfo);
		break;

/*2.14*/        case 'paymentTransactionDetail':
		      $canid=$obj->canID?$obj->canID:'';
                      $from_date=$obj->fromDate?$obj->fromDate:''; // YYYY-MM-DD
                      $to_date=$obj->toDate?$obj->toDate:''; // YYYY-MM-DD
		      if(empty($canid) || empty($from_date) || empty($to_date)){
                              $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                      }else{
			if(validateDate($from_date) !== true || validateDate($to_date) !== true){
				$data = array("status"=>"failure","response"=>array(),"message"=>"Invalid Date.");
			}else{	
				$getTrans=getTransactionHistoryList($canid, $from_date, $to_date);
				#print_r($getTrans); exit;
				if(empty($getTrans)){
                                        $data = array("status"=>"failure","response"=>array(),"message"=>"No Record Found");
                                }else{
				$CountArr   =  count(array_column($getTrans, 'srlNo'));
	                        if($CountArr == 0) $dataArr[0] = $getTrans;
        	                else               $dataArr = $getTrans;

				#print_r($dataArr); exit;
				$i = 0;
				$payData = array();
				foreach($dataArr as $val){
					#echo $val['srlNo']; exit;
					if($val['voucherTypeId'] != '5'){
					switch ($val['voucherTypeId']){
                                        case 3:
                                                $voucherType    = "Payment";
                                                break;
                                        case 7:
                                                $voucherType    = "Settlement (Debit)";
                                                break;
                                        case 8:
                                                $voucherType    = "Settlement (Credit)";
                                                break;
                                        default:
                                                $voucherType    = $val['voucherType'];
                                                break;
		                        }
							if(preg_match("/Dishonoured/",$val['narration'])){ 
								$voucherType = "Cheque Bounce";
							}
							$payData['type'] = $voucherType;
							$payData['transactionNo'] = $val['srlNo'];
							$payData['transactionDate'] = date("d-m-Y",strtotime($val['transactionDate']));
							$payData['amount'] = $val['amount'];
							if(isset($val['instrumentType'])){
								$payData['paymentMode'] = $val['instrumentType'];
								$payData['description'] = $val['instrumentDetail'];
							}else{	
								$payData['paymentMode'] = "";
                                                                $payData['description'] = $val['narration'];
							}
						$p_data[] = $payData;
					}
				}// End of Loop
					
	                        if(empty($p_data)){
          		              $data = array("status"=>"failure","response"=>array(),"message"=>"No Record Found");
                        	}else{
		                      $data = array("status"=>"success","response"=>$p_data,"message"=>"Successfully fetched");
                	        }
				}
			}
                      }
 
		break;

		case 'forgotpassword':
			$canID = $obj->canID?$obj->canID:'';
                        $username = $obj->username?$obj->username:'';
			if(empty($canID) && empty($username)){
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }else{
                                $param = array("username"=>$username, "CANID"=>$canID);
                                $pwd_sent = forgotpwd($param);
				if($pwd_sent === true){
					$data = array("status"=>"success","response"=>array("canID"=>$canID,"username"=>$username),"message"=>"Password has been sent on registered mobile and email id");
				}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Login credentials does not exist, Kindly login with your registered mobile number.");
				}
                        }

		break;		

		case 'updateemail':
			$canID = $obj->canID?$obj->canID:'';
                        $newEmailID = $obj->newEmailID?$obj->newEmailID:'';
			if(!empty($canID) && !empty($newEmailID)){	
	                        $data=CRM_update_email($newEmailID,$canID);
			}else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }


		break;
		case 'updatemobile':
			$canID = $obj->canID?$obj->canID:'';
                        $newMobile = $obj->newMobile?$obj->newMobile:'';
			if(!empty($canID) && !empty($newMobile)){
				$data=CRM_update_mobile($newMobile,$canID);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
		break;
		
		case 'updateEmailSendOTP':
                        $canID = $obj->canID?$obj->canID:'';
                        $newEmailID = $obj->newEmailID?$obj->newEmailID:'';
			$otp    = $obj->OTP?$obj->OTP:''; 
			if(!empty($otp) && !empty($newEmailID)){
				$data=update_email_resendotp($newEmailID,$otp);
                        }elseif(!empty($canID) && !empty($newEmailID)){
                                $data=update_email_sendotp($newEmailID,$canID);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }


                break;
                case 'updateMobileSendOTP':
                        $canID = $obj->canID?$obj->canID:'';
                        $newMobile = $obj->newMobile?$obj->newMobile:'';
			$otp    = $obj->OTP?$obj->OTP:'';
			if(!empty($otp) && !empty($newMobile)){
                                $data=update_mobile_resendotp($newMobile,$otp);
                        }elseif(!empty($canID) && !empty($newMobile)){
                                $data=update_mobile_sendotp($newMobile,$canID);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		case 'resetpassword':
			$param['canID'] 	  = $obj->canID?$obj->canID:'';
			$param['username'] 	  = $obj->userName?$obj->userName:'';
			$param['oldpassword']     = $obj->oldPassword?base64_decode($obj->oldPassword):'';
			$param['newpassword'] 	  = $obj->newPassword?base64_decode($obj->newPassword):'';
			if(!empty($param['oldpassword']) && !empty($param['newpassword'])){
				if(!empty($param['username']) || !empty($param['canID'])){
					$data=changepass($param);
				}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
				}
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}	
		break;
		case 'massoutage':
			 $phone = $obj->phone?$obj->phone:'';
			 $canID = $obj->canID?$obj->canID:'';
			 $data=array('phone'=>'phone','CANID'=>trim($canID));
	                 $res=getMassoutage($data);
		break;
		case 'sendotp':
		 	$mobileno = $obj->mobileNo?$obj->mobileNo:'';
			if(!empty($mobileno)){
				$data=sendotp($mobileno);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
		break;
		case 'checkservicebar':
			$canID = $obj->canID?$obj->canID:'';
			$data=checkServiceBar($canID);
		break;
		case 'getMRTGbycanid':
			$canID = $obj->canID?$obj->canID:'';
			if($canID == "9021358") $canID = "9021358a";
			if($canID == "9061534") $canID = "9061534a";

			$gtype = $obj->dateType?$obj->dateType:'';
			if(empty($canID) || empty($gtype)){
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}else{
				$getMRTG = getmrtg_graph($canID,$gtype);
				$data = array("status"=>"success","response"=>$getMRTG,"message"=>"Successfully fetched");
			}
		break;

		case 'getTDSByCanId':
                        $canID = $obj->canID?$obj->canID:'';
                        $data=getTDSByCanId($canID);
                break;	
	
		case 'getTransactionByCanId':
                      $canID=$obj->canID?$obj->canID:'';
                      $from_date=$obj->fromdate?$obj->fromdate:'';
                      $to_date=$obj->todate?$obj->todate:'';
			
                      $data=getTransactionByCanId($canID,  date("Y-m-d", strtotime($from_date)),  date("Y-m-d", strtotime($to_date)));
                break;

		case 'getAccountData':
                         $canID = $obj->canID?$obj->canID:'';
			 if(!empty($canID)){
				$len = strlen($canID); 
	                        if($len < 6 || $len > 8){
                                	$data = array("status"=>"failure","response"=>array(), "message"=>"No Records Found");
                        	}else{
	                         	$data1[] = getAccountData4($canID);	
					$data = array("status"=>"success","response"=>$data1,"message"=>"Successfully fetched");
				}
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
		break;

		case 'resendotp':
                        $mobileno = $obj->mobileNo?$obj->mobileNo:'';
			$otp    = $obj->OTP?$obj->OTP:'';
			if(!empty($mobileno) && !empty($otp)){
                        	$data=resendotp($mobileno,$otp);
			}else{
				$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
			}
                break;
		
		case 'getprofile':
                        $can_id = $obj->canID?$obj->canID:'';
                        if(!empty($can_id)){
                                $data = getProfile($can_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		case 'getrateplan':
                        $pkg_id = $obj->pkgID?$obj->pkgID:'';
                        if(!empty($pkg_id)){
                                $data = getRatePlan($pkg_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		case 'getrateplanByCanID':
                        $can_id = $obj->canID?$obj->canID:'';
                        if(!empty($can_id)){
                                $data = getRatePlanByCanID($can_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }

                break;
		
		case 'getStatusAutopay':
                        $can_id = $obj->canID?$obj->canID:'';
                        if(!empty($can_id)){
                                $data = getAutopayStatus($can_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;	
	
		case 'addTopup':
                        $canid = $obj->canID?trim($obj->canID):'';
			$amount = $obj->amount?trim($obj->amount):'';
			$topup_name = $obj->topupName?trim($obj->topupName):'';
			$topup_type = $obj->topupType?trim($obj->topupType):'';
                        if(!empty($canid)){
                                $data = addTopup($canid, $amount, $topup_name, $topup_type);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		case 'updateTAN':
                        $canid = $obj->canID?trim($obj->canID):'';
                        $tan = $obj->tanNumber?trim($obj->tanNumber):'';
                        if(!empty($canid) && !empty($tan)){
				$check = validateTAN($tan);
				if($check == 1){
		   $type = "T_115";
                   $subType = "ST_416";
                   $subSubType = "SST_666";
                   $caseSource = "20";
                   $caseCategory = "3";
                   $complainDesc = "Requested for TAN number ".$tan." update on APP via OTP";
		    if(substr($canid,0,1) == "9"){
                        $owner = "CS_SRM";
                   }else{
                        $owner = "CS_Home_Backend";
                   }
                   $Case_message = Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
                  # print_r($Case_message); exit;
                        if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){
                               	$data = updateTAN($canid, $tan);
		   //$req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_1509",  "Resolutioncode2"=>"RC2_2411", "Resolutioncode3"=>"RC3_16607", "RFO" => "Requested for TAN number updated successfully", "OLR"=>"Yes");
		    if($data['status'] == "failure"){
                    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_649",  "Resolutioncode2"=>"RC2_671", "Resolutioncode3"=>"RC3_17450", "RFO" => "TAN update failed", "OLR"=>"Yes");
                   }else{
                   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_649",  "Resolutioncode2"=>"RC2_671", "Resolutioncode3"=>"RC3_17449", "RFO" => "TAN successfully updated", "OLR"=>"Yes");
                    }

                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
                        }elseif(preg_match('/active/', $Case_message)){
                             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
                        }else{
                             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                        }
				}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Invalid TAN number.");
				}
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
		case 'updateGSTN':
                        $canid = $obj->canID?trim($obj->canID):'';
                        $gstn = $obj->gstNumber?trim($obj->gstNumber):'';
                        if(!empty($canid) && !empty($gstn)){
				$check = is_valid_gstin($gstn);
				if($check == 1){
		   $type = "T_115";
		   $subType = "ST_416";
                   $subSubType = "SST_666";
                   $caseSource = "20";
                   $caseCategory = "3";
                   $complainDesc = "Requested for GST number ".$gstn." update on APP via OTP";
		   if(substr($canid,0,1) == "9"){
                        $owner = "CS_SRM";
                   }else{
                        $owner = "CS_Home_Backend";
                   }

                   $Case_message = Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
                  # print_r($Case_message); exit;
                	if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){
                            	$data = updateGSTN($canid, $gstn);
		   if($data['status'] == "failure"){
		    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_648",  "Resolutioncode2"=>"RC2_670", "Resolutioncode3"=>"RC3_17448", "RFO" => "GST update failed", "OLR"=>"Yes");
		   }else{
		   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_648",  "Resolutioncode2"=>"RC2_670", "Resolutioncode3"=>"RC3_17447", "RFO" => "GST successfully updated", "OLR"=>"Yes");
		    }
                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
			}elseif(preg_match('/active/', $Case_message)){
                	     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
	                }else{
        	             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                	}

				
				}else{
					$data = array("status"=>"failure","response"=>array(),"message"=>"Invalid GSTIN number");
				}
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
                case 'sendOTPLinkAccount':
			$can_id = $obj->canID?$obj->canID:'';
                        if(!empty($can_id)){
                                $data = sendotpLinkAccount($can_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
		
		break;

		case 'reSendOTPLinkAccount':
                        $mobileno = $obj->mobileNo?$obj->mobileNo:'';
			$otp    = $obj->OTP?$obj->OTP:'';
                        if(!empty($mobileno) && !empty($otp)){
                                $data=resendotpLinkAccount($mobileno, $otp);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }

                break;

		case 'addLinkAccount':
			$params['base_canid'] = $obj->baseCanID?trim($obj->baseCanID):'';
			$params['link_canid'] = $obj->linkCanID?trim($obj->linkCanID):'';
			$params['username']   = $obj->userName?trim($obj->userName):'';
			$params['mobileno']   = $obj->mobileNo?trim($obj->mobileNo):'';
			//if((!empty($params['base_canid']) && !empty($params['username'])) || (!empty($params['base_canid']) && !empty($params['mobileno'])) || (!empty($params['mobileno']) && !empty($params['username']))){
			if((!empty($params['base_canid']) || !empty($params['username']) || !empty($params['mobileno'])) && !empty($params['link_canid'])){
				$data = addLinkAccount($params);
			}else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
		break;

		case 'getLinkAccount':
                        $params['base_canid'] = $obj->baseCanID?trim($obj->baseCanID):'';
			$params['username']   = $obj->userName?trim($obj->userName):'';
                        $params['mobileno']   = $obj->mobileNo?trim($obj->mobileNo):'';
			
			 //if((!empty($params['base_canid']) && !empty($params['username'])) || (!empty($params['base_canid']) && !empty($params['mobileno'])) || (!empty($params['mobileno']) && !empty($params['username']))){
			/*if(empty($params['base_canid'])){
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Please provide any one from baseCanID/userName/mobileNo");
                        }else*/
			if(!empty($params['base_canid']) || !empty($params['username']) || !empty($params['mobileno'])){
                                $data = getLinkAccount($params);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
	
		case 'removeLinkAccount':
                        $params['base_canid'] = $obj->baseCanID?trim($obj->baseCanID):'';
			$params['link_canid'] = $obj->linkCanID?trim($obj->linkCanID):'';
                        $params['username']   = $obj->userName?trim($obj->userName):'';
                        $params['mobileno']   = $obj->mobileNo?trim($obj->mobileNo):'';

			 //if((!empty($params['base_canid']) && !empty($params['username'])) || (!empty($params['base_canid']) && !empty($params['mobileno'])) || (!empty($params['mobileno']) && !empty($params['username']))){
                        if((!empty($params['base_canid']) || !empty($params['username']) || !empty($params['mobileno'])) && !empty($params['link_canid'])){
                                $data = removeLinkAccount($params);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
		case 'getOrgName':
                        $canid = $obj->canID?trim($obj->canID):'';
                         if(!empty($canid)){
                                $data =  getOrgName($canid);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		case 'trackOrder':
                        $canid = $obj->canID?trim($obj->canID):'';
                         if(!empty($canid)){
                                $jsonResponse =  getTrackOrder($canid);
				$custResp = json_decode($jsonResponse, true);
#				print_r($custResp); exit;
				if($custResp['CANID'] == $canid){
					$data = array("status"=>"success","response"=>$custResp,"message"=>"Successfully fetched.");
				}else{
					$data = array("status"=>"failure","response"=>$custResp,"message"=>"No Record Found");
				}
//				print_r($data); exit;
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
		case 'addContactDetails':
			$params['AccountNo'] = $obj->canID?trim($obj->canID):'';
                        $params['firstName'] = $obj->firstName?trim($obj->firstName):'';
                        $params['lastName']   = $obj->lastName?trim($obj->lastName):'';
                        $params['jobTitle']   = $obj->jobTitle?trim($obj->jobTitle):'';
			$params['email']   = $obj->email?trim($obj->email):'';
                        $params['mobilePhone']   = $obj->mobilePhone?trim($obj->mobilePhone):'';

                        if(!empty($params['firstName']) && !empty($params['lastName']) && !empty($params['jobTitle']) && !empty($params['email']) && !empty($params['mobilePhone']) && !empty($params['AccountNo'])){
			   $type = "T_115";
        	           $subType = "ST_416";
                	   $subSubType = "SST_667";
                	   $caseSource = "20";
	                   $caseCategory = "3";
        	           $complainDesc = "Requested for additional contact update on APP via OTP";
                   if(substr($canid,0,1) == "9"){
                        $owner = "CS_SRM";
                   }else{
                        $owner = "CS_Home_Backend";
                   }

                   $Case_message = Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
                  # print_r($Case_message); exit;
                    if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){
                                $data = addContactDetail($params);
			if($data['status'] == "failure"){
		    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_670",  "Resolutioncode2"=>"RC2_672", "Resolutioncode3"=>"RC3_17452", "RFO" => "Contact update failed", "OLR"=>"Yes");
		   }else{
		   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_670",  "Resolutioncode2"=>"RC2_672", "Resolutioncode3"=>"RC3_17451", "RFO" => "Contact successfully updated", "OLR"=>"Yes");
		    }
                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
			}elseif(preg_match('/active/', $Case_message)){
                	     $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
	                }else{
        	             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                	}

                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
		case 'updateContactDetails':
			$params['contactId'] = $obj->contactId?trim($obj->contactId):'';
                        $params['AccountNo'] = $obj->canID?trim($obj->canID):'';
                        $params['firstName'] = $obj->firstName?trim($obj->firstName):'';
                        $params['lastName']   = $obj->lastName?trim($obj->lastName):'';
                        $params['jobTitle']   = $obj->jobTitle?trim($obj->jobTitle):'';
                        $params['email']   = $obj->email?trim($obj->email):'';
                        $params['mobilePhone']   = $obj->mobilePhone?trim($obj->mobilePhone):'';

                         if(!empty($params['contactId']) && !empty($params['firstName']) && !empty($params['lastName']) && !empty($params['jobTitle']) && !empty($params['email']) && !empty($params['mobilePhone']) && !empty($params['AccountNo'])){
                           $type = "T_115";
                           $subType = "ST_416";
                           $subSubType = "SST_667";
                           $caseSource = "20";
                           $caseCategory = "3";
                           $complainDesc = "Requested for additional contact update on APP via OTP";
                   if(substr($canid,0,1) == "9"){
                        $owner = "CS_SRM";
                   }else{
                        $owner = "CS_Home_Backend";
                   }

                   $Case_message = Create_SR_CRM($canid,$type,$subType,$subSubType,$caseSource,$caseCategory,$complainDesc,$owner);
                  # print_r($Case_message); exit;
                    if(!empty($Case_message) && substr($Case_message,0,2) == "SR"){
                                $data = updateContactDetail($params);
				 if($data['status'] == "failure"){
                    $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_670",  "Resolutioncode2"=>"RC2_672", "Resolutioncode3"=>"RC3_17452", "RFO" => "Contact update failed", "OLR"=>"Yes");
                   }else{
                   $req_data = array("CaseID"=>$Case_message, "Resolutioncode1"=>"RC_670",  "Resolutioncode2"=>"RC2_672", "Resolutioncode3"=>"RC3_17451", "RFO" => "Contact successfully updated", "OLR"=>"Yes");
                    }
                   $request=json_encode($req_data);
                   $data_string1 = $request;
                   $Create_SR_URL=crm_url."/CloseSR";
                   $output_reponse= Curl_CRM($data_string1,$Create_SR_URL,$canid);
                   $crm_data = json_decode($output_reponse[0]);
                   $Case_message1 = trim($crm_data->Message);
                        }elseif(preg_match('/active/', $Case_message)){
                             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR already open");
                        }else{
                             $data = array("status"=>"failure","response"=>$Case_message,"message"=>"SR does not created");
                        }

                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;

		 case 'getContactDetails':
                        $params['AccountNo'] = $obj->canID?trim($obj->canID):'';
                         if(!empty($params['AccountNo'])){
                                $data = getContactDetail($params);
                        }else{
                                $data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
                        }
                break;
		
		case 'consumedTopup':
                                        $canid = $obj->canId?trim($obj->canId):'';
                                        if(!empty($canid)){
                                        	$data =  consumedTopup($canid);
                                        }else{
                                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                                        }
                 break;
		
		case 'checkSR':
                                        $srNumber = $obj->srNumber?trim($obj->srNumber):'';
					$canid = $obj->canId?trim($obj->canId):'';
                                        if(!empty($srNumber) && !empty($canid)){
                                                $arr=array('srNumber'=>$srNumber, 'canid' => $canid);
					        $data = checkSRStatus($arr);
                                        }else{
                                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                                        }
                break;		

		case 'srFeedback':
					$canid = $obj->canId?trim($obj->canId):'';
					$type_id = $obj->typeId?trim($obj->typeId):'';
					$sub_type_id = $obj->subtypeId?trim($obj->subtypeId):'';
					$sub_sub_type_id = $obj->subsubtypeId?trim($obj->subsubtypeId):'';
					$feedback = $obj->feedback?trim($obj->feedback):'';
                                        if(!empty($canid)){
                                                $arr=array('canid'=>$canid,"feedback" => $feedback, 'type_id' => $type_id, "sub_type_id" => $sub_type_id, "sub_sub_type_id" => $sub_sub_type_id);
                                                $data = createSRFeedback($arr);
                                        }else{
                                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                                        }
                break;
	
		case 'forceUpdate':
			$forceUpdate = $obj->forceUpdate ? trim($obj->forceUpdate) : 'false';
			
			$data = array("status"=>"success","response"=>array("forceUpdate"=>$forceUpdate), "message"=>"Successfully fetched.");
		break;	

/********** Sprint 1 Plan for me & Topup for me start ***********/
		case 'comparisonPlan':
                        $planIdList = $obj->planIdList;
			$count = count($planIdList);
			if(!empty($planIdList) && $count > 1){
				$data = comparisonPlan($planIdList);
			}else{
				$data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
			}
                break;
		
		case 'proDataChargesForPlan':
			$can_id = $obj->canId?trim($obj->canId):'';
                        $plan_id = $obj->planId?trim($obj->planId):'';
			if(!empty($can_id) && !empty($plan_id)){
				$data = proDataChargesForPlan($can_id, $plan_id);
			}else{
				$data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
			}
                break;

		case 'proDataChargesForTopup':
                        $can_id = $obj->canId?trim($obj->canId):'';
                        $topup_id = $obj->topupId?trim($obj->topupId):'';
                        if(!empty($can_id) && !empty($topup_id)){
                                $data = proDataChargesForTopup($can_id, $topup_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;	

		case 'deactivateTopup':
                        $can_id = $obj->canId?trim($obj->canId):'';
                        $topup_id = $obj->topupId?trim($obj->topupId):'';
			$topup_type = $obj->topupType?trim($obj->topupType):'';
                        if(!empty($can_id) && !empty($topup_id) && !empty($topup_type)){
                                $data = deactivateTopupSR($can_id, $topup_id, $topup_type);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;

		case 'knowMoreForPlan':
                        $plan_id = $obj->planId?trim($obj->planId):'';
                        if(!empty($plan_id)){
                                $data = knowMoreForPlan($plan_id);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;
/********** End ***********/

/********** Sprint 2 Notification start ***********/

		case 'deviceSignIn':
                        $deviceData = $obj->deviceData?trim($obj->deviceData):'';
			foreach($obj->deviceData->canId as $canid){
				$param['canid'][] = $canid; 
			}
			foreach($obj->deviceData->deviceToken as $token){
                                $param['token'][] = $token;
                        }
			foreach($obj->deviceData->deviceType as $type){
                                $param['type'][] = $type;
                        }
			
                        if(!empty($param)){
                                $data = deviceSignin($param);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;

		case 'deviceSignOut':
                        $deviceData = $obj->deviceData?trim($obj->deviceData):'';
                        foreach($obj->deviceData->canId as $canid){
                                $param['canid'][] = $canid;
                        }
                        foreach($obj->deviceData->deviceToken as $token){
                                $param['token'][] = $token;
                        }
                        foreach($obj->deviceData->deviceType as $type){
                                $param['type'][] = $type;
                        }

                        if(!empty($param)){
                                $data = deviceSignout($param);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;

		case 'pushNotification':
#			print_r($obj); exit;
                        $deviceData = $obj->notificationData?trim($obj->notificationData):'';
                        foreach($obj->canId as $canid){
                                $canids[] = $canid;
                        }
			$title = $obj->notificationData->title?trim($obj->notificationData->title):'';
			$short_description = $obj->notificationData->short_description?trim(addslashes($obj->notificationData->short_description)):'';
			$detailed_description = $obj->notificationData->detailed_description?trim(addslashes($obj->notificationData->detailed_description)):'';
			
                        if(!empty($title) && !empty($detailed_description) && !empty($canids)){
                                $data = pushNotification($title, $short_description, $detailed_description, $canids);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;

                case 'getDeviceToken':
#                       print_r($obj); exit;
                        foreach($obj->canId as $canid){
                                $canids[] = $canid;
                        }
                        if(!empty($canids)){
                                $data = getDeviceToken($canids);
                        }else{
                                $data = array("status"=>"failure","response"=>array(), "message"=>"Invalid request format");
                        }
                break;
				case 'getAccountDetails':  
					//echo "hi";
					$can_id  = $obj->can_id?trim($obj->can_id):'';
					//echo $can_id;
					//exit;
					if(!empty($can_id)){
						$data = getCustomerAccountDetail1($can_id);
					}else{
						$data = array("status"=>"failure","response"=>array(),"message"=>"Parameter is blank.");
					}
						break;

/********** End ***********/
	
		default:
			$data = array("status"=>"failure","response"=>array(),"message"=>"Action not define.");
		}
	}else{
                	$data = array("status"=>"failure","response"=>array(),"message"=>"Authentication failed.");
	}

			$timestamp = date("Y-m-d H:i:s");
			$LOG = $timestamp." ".json_encode($data);
			file_put_contents("logs/request.log","\n".$LOG, FILE_APPEND | LOCK_EX);
			header('Content-type: application/json');
			echo json_encode($data);

	?>
