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
	
	//echo $baseplan; exit;
    require_once('dbquery.php'); 
    $db = new dbconnection();
	//print_r($db); exit;
	$arr=$db->checklastupdatedate($canid);  
	//print_r($arr); exit;   
			$validt=false;
			if(!empty($arr)){
			$chdt=$arr[0]['datetime'];
			}
			if(!empty($arr) && isset($chdt)){
			 $validt=$db->checkPlabChange($chdt); /* will check last change subscription date if it's less than one month will return false*/ 
			}

		 $plansCanID=$db->getplanCandid($canid);
		 //print_r($plansCanID); exit;
                if(!empty($plansCanID)){
                   $best_offers=$plansCanID;
                }if((empty($plansCanID) || $canid=='') && $baseplan!=''){
					//echo $baseplan; exit;
                    $best_offers=$db->getOffers($baseplan);
                }
		//print_r($best_offers); exit;
		if(!empty($best_offers)){
			 $offers = array();
                        foreach($best_offers as $val){
			 	$paid = $val['duration']-$val['extra_month'];
				if($paid == 1) $paid_month = $paid." month";
				else		$paid_month = $paid." months";
				
				if($val['extra_month'] == 0){ $free_month = "";}
				elseif($val['extra_month'] == 1) {$free_month = ", get ".$val['extra_month']." month free";}
				else	{$free_month = ", get ".$val['extra_month']." months free";}

				$frequency = $val['month']." (Pay for ".$paid_month.$free_month.")";

                                $offers1 = array("planid"=>$val['rateplan_id'],"description"=>$val['description'],"charges"=>$val['base_price'],"data"=>$val['data'], "speed"=>$val['download'], "frequency" => $frequency);
                                array_push($offers,$offers1);
                        }

		 	$response = array("status"=>"success","response"=>$offers,"message"=>"Successfully fetched");
		}else{
			$response = array("status"=>"failure","response"=>array(),"message"=>"No Offer available!");
		}	
		return $response;
	}

	function getchangeplan($canid, $pkgname){
                 require_once('crmApi.php');
                 require_once('dbquery.php');
                 $db = new dbconnection();
        //       $pkgname = "BB_1000_450GB";
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
                        $response = array("status"=>"success","response"=>array("srNo"=>$sr_no),"message"=>"We have received your plan change request and we will send you a confirmation once your plan is activated.");
                }elseif($message == "You can not request for Plan change with in 30 days"){
			$response = array("status"=>"failure","response"=>array(),"message"=>"Plan change is allowed only once in a month");
		}else{
                        $response = array("status"=>"failure","response"=>array(),"message"=>"Oops! Something has gone wrong. We are unable to take your request right now. Please try after sometime.");
                }

                return $response;
        }



/*function getchangeplan($orgID,$cplan,$canid,$pkgname,$selplanname){
	// require_once('unifyApi.php');
	// require_once('dbquery.php');
    	//$db = new dbconnection();

/* if($key==$post['secretKey']){
    $orgID=$post['orgID'];
    $cplan=$post['cplan'];
    $canid=$post['canid'];
    $pkgname=$post['pkgname'];
    $selplanname=$post['selplanname'];

/* Change Plan API*/

      	 /*$data_sub =   getSubscriptionListByOrgId($orgID);
		$bnrcArr=array();
      		$bnrcArr['orgid']=$orgID;
      		$xmlResponse_sub    =  getXMLResponse($data_sub);
      		$xml_sub =  xml2array($xmlResponse_sub);
      		$subsdata =  $xml_sub['soap:Envelope']['soap:Body']['ns2:getSubscriptionListByOrgIdResponse']['SubscriptionListByOrgId']['return'];
        	$bnrcArr['pkgname']=$pkgname;
        	$pkid=getrateplanid($pkgname);
		$xmlResponse_pkid    =  getXMLResponse($pkid,1);
        	$xml_pkid =  xml2array($xmlResponse_pkid);

        	$planid_data =  $xml_pkid['soap:Envelope']['soap:Body']['ns2:getRatePlanByRatePlanIDResponse']['return'];
        	$planNo=$planid_data['servicePlanNo'];
        	$bnrcArr['servicePlanNo']=$planNo;
        	$bnrcArr['subsNo']=$subsdata['subsNo'];
        	$bnrcArr['serviceGroupNo']=$subsdata['serviceGroupNo'];
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
		$chsubs=changesubscription($bnrcArr);
 		$xmlResponse_chsub    =  getXMLResponse($chsubs,1);
        	$xml_chsub                =  xml2array($xmlResponse_chsub);
		$subscription=$xml_chsub['soap:Envelope']['soap:Body']['ns2:changeSubscriptionResponse']['return']; 
		$datetime=date('Y-m-d H:i:s',strtotime($subscription['startDt']));
		$sourceip=$db->get_client_ip();
		$status=$subscription['status'];
		$data=array('datetime'=>$datetime,'actno'=>$canid,'cplan'=>$cplan,'csubsid'=>$subsdata['subsNo'],'newplanname'=>$selplanname,
                                            'newplanid'=>$pkgname,'newplansubsid'=>$subscription['subsNo'],'status'=>$status,
					    'source'=>'web','sourceip'=>$sourceip);
			$db->saveplan_changed($data);
		
		if(!empty($subscription))
		return $subscription;
		else
		echo false;
}*/

function gettopups($canid,$baseplan){
    require_once('dbquery.php');
    $db = new dbconnection();
                        $validt=false;
                        if($canid!=''){
			 $plansCanID=$db->getTopupCandid($canid);
			#print_r($plansCanID);exit;
                        }
                if(!empty($plansCanID) ){
                   $best_offers=$plansCanID;
                }if((empty($plansCanID) || $canid=='') && $baseplan!=''){
			$best_offers=$db->getTopups($baseplan);
                }
		if(!empty($best_offers)){
                        $res= array('responseCode'=>'210', 'status'=>'success', 'response'=>$best_offers);
                }else{
                        $res=array('responseCode'=>'211', 'status'=>'success', 'response'=>'No offer available!');
                }

                return $res;
        }


function getchangeplanB2C($canid,$pkgname,$startdate,$billnow,$billCycleNo1,$cycle1,$cycleDuration1){

/* Change Plan API*/
//		$getdata = getOrgByActID($canid);
		$getdata = getCustomerAccountDetail($canid);
		#print_r($getdata);exit;
		if(empty($getdata['orgId'])){ 
			$data = array("status"=>"failure","response"=>array(),"message"=>"OrgId not found.");
			return $data;
		}

		$orgID  = $getdata['orgId'];
		$act_no = $getdata['accountNo'];
		$subsno = $getdata['subsDetails']['subsNo'];
		$old_plan = $getdata['subsDetails']['pkgname'];
		$old_pkgNo  = $getdata['subsDetails']['pkgNo'];
		$billCycleNo = ($billCycleNo1)?$billCycleNo1:$getdata['billCycleNo'];
		$cycle = ($cycle1)?$cycle1:$getdata['cycle'];
		$cycleDuration = ($cycleDuration1)?$cycleDuration1:$getdata['cycleDuration'];
		$bill_startdate = ($startdate)?$startdate:date("Y-m-d");
		$advanceBilling = 'true';
                $billProfileNo = '228';
                $invoiceTemplateNo = '72';
                $receiptTemplateNo = '1';
                $DunningProfile = '50';

		$billsetup = billSetupwithDate($act_no, $advanceBilling, $billCycleNo, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, $bill_startdate);	

		$bnrcArr=array();
                $bnrcArr['orgid']=$orgID;
        	$bnrcArr['pkgname']=$pkgname;
		$bnrcArr['billnow']=$billnow;
		$bnrcArr['startdate']=$bill_startdate;

        	$pkid = getrateplanid($pkgname);
		$xmlResponse_pkid    =  getXMLResponse($pkid,1);
        	$xml_pkid =  xml2array($xmlResponse_pkid);
        	$planid_data =  $xml_pkid['soap:Envelope']['soap:Body']['ns2:getRatePlanByRatePlanIDResponse']['return'];
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

?>


