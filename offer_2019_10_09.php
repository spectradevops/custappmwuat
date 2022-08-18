<?php
/* 
This function will return best offer on the base of perticular CANID 
It works based on 2 logic:
1. it check the table: plan_canid where all offering for a perticular 
can id is stored with thier existing plan 
2. if offering is not available for that can id, it checks in another table
for the offer based on their existing rate_plan id
input: can id, base plan
output: can id, rate_plan id, plan details 

*/
function getOffers($ukey,$canid,$baseplan){
    require_once('dbquery.php'); 
    $db = new dbconnection();
	$arr=$db->checklastupdatedate($canid);        
			$validt=false;
			if(!empty($arr) && isset($chdt)){
			$chdt=$arr[0]['datetime'];
			 $validt=$db->checkPlabChange($chdt); /* will check last change subscription date if it's less than one month will return false*/ 
			}
			if($validt==true && $canid!=''){
			 $plansCanID=$db->getplanCandid($canid);
			}
                if(!empty($plansCanID) ){
		#	print_r($plansCanID);
                   	$best_offers=$plansCanID;
                }if((empty($plansCanID) || $canid=='') && $baseplan!=''){
                    $best_offers=$db->getOffers($baseplan);
                }
		if(!empty($best_offers)){
			#print_r($best_offers);exit;
			$offers = array();
			foreach($best_offers as $val){
				
				$offers1 = array("planid"=>$val['rateplan_id'],"description"=>$val['description'],"charges"=>$val['base_price'],"data"=>$val['data'], "speed"=>$val['download'], "frequency" => "Quaterly (Pay for 3 months, get 2 months free)");
				array_push($offers,$offers1);	
			}
#		 $res=$best_offers;
			 $response = array("status"=>"success","response"=>$offers,"message"=>"Successfully fetched");
		}else{
#		$res=array('Response'=>'No Offer available!');
			$response = array("status"=>"failure","response"=>array(),"message"=>"No Offer available!");
		}	
		return $response;
	}



function getchangeplan($canid, $pkgname){
                 require_once('crmApi.php');
                 require_once('dbquery.php');
                 $db = new dbconnection();
	//	 $pkgname = "BB_1000_450GB";
                 $data=array("canId"=>$canid ,"pkgName"=>$pkgname,"planChangeNow"=> "true","billNow"=> "true");
		 #print_r($data); exit;
                 $result=planchangeb2c($data);
                 $datetime=date('Y-m-d H:i:s');
                 $sourceip=$db->get_client_ip();
		 $r=json_decode($result,true);
		 $status = $r['status'];
                 $message=$r['message'];
		 $sr_no = $r['srNo'];
		 $data=array('datetime'=>$datetime,'actid'=>$canid,'newplanid'=>$pkgname,'sr_number'=>$sr_no,'status'=>$status,                                    'source'=>'web','sourceip'=>$sourceip);
                       $db->saveplan_changed($data);
		if($status == "Success"){
			$response = array("status"=>"success","response"=>array("srNo"=>$sr_no),"message"=>$message); 
		}else{
			$response = array("status"=>"failure","response"=>array(),"message"=>"Plan has not been changed.");
		}

                return $response;
        }



function gettopups($canid,$baseplan){
    require_once('dbquery.php');
    $db = new dbconnection();
                        $validt=false;
                        if($canid!=''){
			 $plansCanID=$db->getTopupCandid($canid);
                        }
                if(!empty($plansCanID) ){
                   $best_offers=$plansCanID;
                }if((empty($plansCanID) || $canid=='') && $baseplan!=''){
			$best_offers=$db->getTopups($baseplan);
                }
                if(!empty($best_offers)){
	                $res= array('status'=>'success', 'response'=>$best_offers, 'message'=>'Successfully fetched');
                }else{
        	        $res=array('status'=>'failure', 'response'=>array(), 'message'=>'No offer available!');
                }
                return $res;
        }


function getchangeplanB2C($canid,$pkgname,$planchangenow,$billnow,$cycle1){

/* Change Plan API*/
		$getdata = getCustomerAccountDetail($canid);
#		print_r($getdata);exit;
		if(empty($getdata['orgId'])){ 
			$data = array("status"=>"failure","response"=>array(),"message"=>"OrgId not found.");
			return $data;
		}

		$orgID  = $getdata['orgId'];
		$act_no = $getdata['accountNo'];
		$billStartDate = $getdata['billStartDate'];
		$billEndDate  = $getdata['billEndDate'];
		$bill_day  = date("d", strtotime($billStartDate));
		$bill_day1  = (int)$bill_day; 
		$subsno = $getdata['subsDetails']['subsNo'];
		$old_plan = $getdata['subsDetails']['pkgname'];
		$old_pkgNo  = $getdata['subsDetails']['pkgNo'];

		$cycle = ($cycle1)?$cycle1:$getdata['cycle'];
		$cycleDuration = 'M'; 
		$arrayBill = array("cycle"=>$cycle,"segment"=>"Home","bill_day"=>$bill_day1);		
		$billCycle = getBillCycleNo($arrayBill);
		$billCycleNo = $billCycle[0]['bill_no']; 
		if($planchangenow == "true"){
		#	$bill_startdate = date("Y-m-d");
	                $cc_day = (int)date("d");
        	        if($bill_day1 < $cc_day){
                	        $bill_startdate = date("Y-m-".$bill_day, strtotime("+1 month"));
                	}else{
				$bill_startdate = date("Y-m-".$bill_day);
			}

		}else{
			$bill_startdate = date("Y-m-d", strtotime($billStartDate));
#			$next_invoice_date = date("Y-m-d", strtotime($billStartDate));
		}
	

	
// Need to add get billsetup function to fetch first invoice date
		$getbillsetup = getbillSetup($act_no);
		#print_r($getbillsetup);	exit;	
		$advanceBilling = $getbillsetup['advanceBilling'];
                $billProfileNo = $getbillsetup['billProfileNo'];
                $invoiceTemplateNo = $getbillsetup['invoiceTemplateNo'];
		if(!empty($getbillsetup['receiptTemplateNo'])){
	                $receiptTemplateNo = $getbillsetup['receiptTemplateNo'];
		}else{
			$receiptTemplateNo = '1';
		}
                $DunningProfile = '50';
		$first_invoice_date = $getbillsetup['firstInvoiceDate'];

		$billsetup = billSetupwithDate($act_no, $advanceBilling, $billCycleNo, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, $bill_startdate, $first_invoice_date);	
//		$billsetup = billSetupwithDate($act_no, $advanceBilling, $billCycleNo, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, "2018-08-15");
		#print_r($billsetup); exit;
		$bnrcArr=array();
                $bnrcArr['orgid']=$orgID;
        	$bnrcArr['pkgname']=$pkgname;
		$bnrcArr['billnow']=$billnow;
		if($billnow == true){
                        $bnrcArr['startdate'] = date("Y-m-d");
                }else{
                        $bnrcArr['startdate']=$bill_startdate;
                }

//		echo $bnrcArr['startdate']; exit;
        	$planid_data = getrateplanid($pkgname);
		#print_r($planid_data); exit;
/*		$xmlResponse_pkid    =  getXMLResponse($pkid,1);
        	$xml_pkid =  xml2array($xmlResponse_pkid);
        	$planid_data =  $xml_pkid['soap:Envelope']['soap:Body']['ns2:getRatePlanByRatePlanIDResponse']['return'];
		print_r($planid_data); exit;
*/
        	$planNo = $planid_data['servicePlanNo'];
        	$bnrcArr['servicePlanNo']=$planNo;
        	$bnrcArr['subsNo']=$subsno;
        	$bnrcArr['serviceGroupNo']=$act_no;

        	$billRatePl=getBillingContractForRatePlan($planNo);
        	$xmlResponse_bill    =  getXMLResponse($billRatePl,1);
        	$xml_bill =  xml2array($xmlResponse_bill);
        	$billArray=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingContractForRatePlanResponse']['return'];
        	$contractNo=$billArray['cno'];
        	$bnrcArr['contractNo']=$contractNo;

        	$detail=getBillingChargesForBillingContract($contractNo);
        	$xmlResponse_detail    =  getXMLResponse($detail,1);
        	$xml_bill =  xml2array($xmlResponse_detail);
	              $billDetail=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingChargesForBillingContractResponse']['BillContractCharges']['return'];
        foreach($billDetail as  $val){
                if($val['brcno']!=0){
                $bno=$val['brcno'];
                }else{
                $bno=$val['bnrcno'];
                }
                $brcno[$val['chargetype']]=$bno;

        }
	$bnrcArr1=array();
	foreach($brcno as $key=>$val){
		if($key=='R'){
 			$brcno=getRateClassList($val);
			$xmlResponse    =  getXMLResponse($brcno,3);
        		$xmlarray  =  xml2array($xmlResponse);
        		$detail=$xmlarray['soap:Envelope']['soap:Body']['ns2:getRateClassListResponse']['rateClassList']['return'] ;
        		array_push($bnrcArr1,$detail);
		}
		if($key=='N'){
        		$bnrcno=getNonRC($val);
        		$xmlResponsed    =  getXMLResponse($bnrcno,3);
        		$xmld =  xml2array($xmlResponsed);
        		$details=$xmld['soap:Envelope']['soap:Body']['ns2:getNonRCResponse']['return'];
        		array_push($bnrcArr1,$details);
		}
		}
		$bnrcArr['bnr']=$bnrcArr1;
		#print_r($bnrcArr); exit;
		$chsubs=changesubscription($bnrcArr);
 		$xmlResponse_chsub    =  getXMLResponse($chsubs,1);
        	$xml_chsub                =  xml2array($xmlResponse_chsub);
		$subscription = $xml_chsub['soap:Envelope']['soap:Body']['ns2:changeSubscriptionResponse']['return']; 
		#print_r($subscription);
		$datetime = date('Y-m-d H:i:s',strtotime($subscription['startDt']));
		$sourceip = $_SERVER['REMOTE_ADDR'];
		$status = $subscription['status'];
		$response = array("status"=>"success","response"=>$subscription,"message"=>"Plan has been changed successfully"); 
		#print_r($data); exit;
//			$db->saveplan_changed($data);
		
		if(!empty($response))
			return $response;
		else
			echo false;
}


function getchangeplanB2B($param){
	//print_r($param); exit;
/* Change Plan API*/
		$getdata = getCustomerAccountDetail($param['canid']);
#		print_r($getdata);exit;
		if(empty($getdata['orgId'])){ 
			$data = array("status"=>"failure","response"=>array(),"message"=>"OrgId not found.");
			return $data;
		}

		$orgID  = $getdata['orgId'];
		$act_no = $getdata['accountNo'];
		$billStartDate = $getdata['billStartDate'];
		$billEndDate  = $getdata['billEndDate'];
		$bill_day  = date("d", strtotime($getdata['billStartDate']));
		$subsno = $getdata['subsDetails']['subsNo'];
		$old_plan = $getdata['subsDetails']['pkgname'];
		$old_pkgNo  = $getdata['subsDetails']['pkgNo'];

		$cycle = ($param['cycle'])?$param['cycle']:$getdata['cycle'];
		$cycleDuration = 'M'; 
		$arrayBill = array("cycle"=>$cycle,"segment"=>"Home","bill_day"=>$bill_day);		
		$billCycle = getBillCycleNo($arrayBill);
		$billCycleNo = $billCycle[0]['bill_no']; 
#		$next_invoice_date = date("Y-m-d", strtotime($billEndDate));
		if($planchangenow == "true"){
			$bill_startdate = date("Y-m-d",strtotime($param['startdate']));
		}else{
			$bill_startdate = date("Y-m-d", strtotime($billEndDate));
		}
		
// Need to add get billsetup function to fetch first invoice date
		$getbillsetup = getbillSetup($act_no);
#		print_r($getbillsetup);		
		$advanceBilling = $getbillsetup['advanceBilling'];
                $billProfileNo = $getbillsetup['billProfileNo'];
                $invoiceTemplateNo = $getbillsetup['invoiceTemplateNo'];
                $receiptTemplateNo = $getbillsetup['receiptTemplateNo'];
                $DunningProfile = '50';
		$first_invoice_date = $getbillsetup['firstInvoiceDate'];

		$billsetup = billSetupwithDate($act_no, $advanceBilling, $billCycleNo, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, $next_invoice_date, $first_invoice_date);	
//		$billsetup = billSetupwithDate($act_no, $advanceBilling, $billCycleNo, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, "2018-08-15");
	#	print_r($billsetup); exit;
		$bnrcArr=array();
                $bnrcArr['orgid']=$orgID;
        	$bnrcArr['pkgname']=$pkgname;
		$bnrcArr['billnow']=$billnow;
		if($billnow == true){
			$bnrcArr['startdate'] = date("Y-m-d");
		}else{
			$bnrcArr['startdate']=$bill_startdate;
		}
//		echo $bnrcArr['startdate'];exit;
        	$planid_data = getrateplanid($pkgname);
		#print_r($planid_data); exit;
/*		$xmlResponse_pkid    =  getXMLResponse($pkid,1);
        	$xml_pkid =  xml2array($xmlResponse_pkid);
        	$planid_data =  $xml_pkid['soap:Envelope']['soap:Body']['ns2:getRatePlanByRatePlanIDResponse']['return'];
		print_r($planid_data); exit;
*/
        	$planNo = $planid_data['servicePlanNo'];
        	$bnrcArr['servicePlanNo']=$planNo;
        	$bnrcArr['subsNo']=$subsno;
        	$bnrcArr['serviceGroupNo']=$act_no;

        	$billRatePl=getBillingContractForRatePlan($planNo);
        	$xmlResponse_bill    =  getXMLResponse($billRatePl,1);
        	$xml_bill =  xml2array($xmlResponse_bill);
        	$billArray=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingContractForRatePlanResponse']['return'];
        	$contractNo=$billArray['cno'];
        	$bnrcArr['contractNo']=$contractNo;

        	$detail=getBillingChargesForBillingContract($contractNo);
        	$xmlResponse_detail    =  getXMLResponse($detail,1);
        	$xml_bill =  xml2array($xmlResponse_detail);
	              $billDetail=$xml_bill['soap:Envelope']['soap:Body']['ns2:getBillingChargesForBillingContractResponse']['BillContractCharges']['return'];
        foreach($billDetail as  $val){
                if($val['brcno']!=0){
                $bno=$val['brcno'];
                }else{
                $bno=$val['bnrcno'];
                }
                $brcno[$val['chargetype']]=$bno;

        }
	$bnrcArr1=array();
/*	foreach($brcno as $key=>$val){
		if($key=='R'){
 			$brcno=getRateClassList($val);
			$xmlResponse    =  getXMLResponse($brcno,3);
        		$xmlarray  =  xml2array($xmlResponse);
        		$detail=$xmlarray['soap:Envelope']['soap:Body']['ns2:getRateClassListResponse']['rateClassList']['return'] ;
        		array_push($bnrcArr1,$detail);
		}
		if($key=='N'){
        		$bnrcno=getNonRC($val);
        		$xmlResponsed    =  getXMLResponse($bnrcno,3);
        		$xmld =  xml2array($xmlResponsed);
        		$details=$xmld['soap:Envelope']['soap:Body']['ns2:getNonRCResponse']['return'];
        		array_push($bnrcArr1,$details);
		}
	}// End foreach
*/		array_push($bnrcArr1,$param['rcArray']);
//		array_push($bnrcArr1,$param['nrcArray']);

		$bnrcArr['bnr']=$bnrcArr1;
//		print_r($bnrcArr); exit;
		$chsubs=changesubscription($bnrcArr);
 		$xmlResponse_chsub    =  getXMLResponse($chsubs,1);
        	$xml_chsub                =  xml2array($xmlResponse_chsub);
		$subscription = $xml_chsub['soap:Envelope']['soap:Body']['ns2:changeSubscriptionResponse']['return']; 
		#print_r($subscription);
		$datetime = date('Y-m-d H:i:s',strtotime($subscription['startDt']));
		$sourceip = $_SERVER['REMOTE_ADDR'];
		$status = $subscription['status'];
		$response = array("status"=>"success","response"=>$subscription,"message"=>"Plan has been changed successfully"); 
		#print_r($data); exit;
//			$db->saveplan_changed($data);
		
		if(!empty($response))
			return $response;
		else
			echo false;
}


?>


