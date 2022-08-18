<?php
    require_once('dbquery.php'); 
    $db = new dbconnection();
    require_once('UnifyApi3.7.php');

    $key="ofFR7776de68TsEb23Bad3Edb1c";
    $json = file_get_contents('php://input');
    $post=json_decode($json,true);
 if($key==$post['secretKey']){
    $orgID=$post['orgID'];
    $cplan=$post['cplan'];
    $canid=$post['canid'];
    $pkgname=$post['pkgname'];
    $selplanname=$post['selplanname'];

/* Change Plan API*/

      		$data_sub =   getSubscriptionListByOrgId($orgID);
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
		$sourceip=get_client_ip();
		$status=$subscription['status'];
		$data=array('datetime'=>$datetime,'actno'=>$canid,'cplan'=>$cplan,'csubsid'=>$subsdata['subsNo'],'newplanname'=>$selplanname,
                                            'newplanid'=>$pkgname,'newplansubsid'=>$subscription['subsNo'],'status'=>$status,
					    'source'=>'web','sourceip'=>$sourceip);
			$db->saveplan_changed($data);
		
		if(!empty($subscription))
		echo json_encode($subscription);
		else
		echo false;


	}



function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}


































exit;




    $canid=$post['canID']?$post['canID']:'';
    $baseplan=$post['basePlane']?$post['basePlane']:'';
    $arr=$db->checklastupdatedate($canid);						/* 
											Array																			(
    										           [0] => Array
        										     (
            											[datetime] => 2019-04-17 03:30:00
            											[current_plan] => 
            											[current_subs_id] => 2312408
            											[new_selected_plan] => Spectra Fastest
            											[new_plan_id] => BBB_200_400GB_ME
            											[new_plan_subs_id] => 2312409
        										      )

											   )
											*/
			$chdt=$arr[0]['datetime'];$validt=false;
			if(!empty($arr) && isset($chdt)){
			 $validt=$db->checkPlabChange($chdt); /* will check last change subscription date if it's less than one month will return false*/ 
			}
			if($validt==true && $canid!=''){
			 $plansCanID=$db->getplanCandid($canid);
			
			}
                if(!empty($plansCanID) ){
                   $best_offers=$plansCanID;
                }if((empty($plansCanID) || $canid=='') && $baseplan!=''){
                    $best_offers=$db->getOffers($baseplan);
                }

		if(!empty($best_offers)){
		echo json_encode($best_offers);
		}else{
		$res=array('Response'=>'No Offer available!');
		
		echo json_encode($res);	
		}	
