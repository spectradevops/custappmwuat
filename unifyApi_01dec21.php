<?php
//define("Unify_URL","https://unifyuat.spectra.co/");

	function getXMLResponse($data,$api=0){
    		switch($api){
                        case 4:
                                $policyURL = Unify_URL.'unifyejb/ReportWSImpl';
                                break;

			case 3:
				$policyURL = Unify_URL.'unifyejb/BillingAPI';
				break;
			case 2:
				$policyURL = Unify_URL.'unifyejb/FinanceAPI';
                		break;
			case 1: 
				$policyURL = Unify_URL.'unifyejb/CRMAPI';
				break;
        		default:
				$policyURL = Unify_URL.'unifyejb/UnifyWS';
				break;
		}
 	   	$soapaction = "";
    		$headers = array(
        		"username: admin",
        		"password: admin",
        		"Content-Type: text/xml;charset=\"utf-8\"",
        		"Content-length: " . strlen($data),
        		"Authorization: Basic U3ltYmlvc3lzVXNlcjpQYXNzd29yZC0x"
    		);
		$timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$data;
                file_put_contents("logs/unify_xml.log","\n".$LOG, FILE_APPEND | LOCK_EX);

    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $policyURL);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		curl_setopt($ch, CURLOPT_VERBOSE, false);
    		$xmlResponse = curl_exec($ch);

		$timestamp = date("Y-m-d H:i:s");
                $LOG = $timestamp." ".$xmlResponse;
                file_put_contents("logs/unify_xml.log","\n".$LOG, FILE_APPEND | LOCK_EX);

    		$ch_info = curl_getinfo($ch);
    		curl_close($ch);
    		return $xmlResponse;
	  }
	function xml2array($contents, $get_attributes=1, $priority = 'tag')
    		{
        	if(!$contents) return array();
        	if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            	return array();
        	}
   	
        	//Get the XML parser of PHP - PHP must have this module for the parser to work
        	$parser = xml_parser_create('');
        	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        	xml_parse_into_struct($parser, trim($contents), $xml_values);
        	xml_parser_free($parser);
        	if(!$xml_values) return;//Hmm...
   
        //Initializations
        	$xml_array = array();
        	$parents = array();
        	$opened_tags = array();
        	$arr = array();
        	$current = &$xml_array; //Refference
   
        //Go through the tags.
        	$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        	foreach($xml_values as $data) {
           	 unset($attributes,$value);//Remove existing values, or there will be trouble
   
            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            	extract($data);//We could use the array by itself, but this cooler.
   
  	          $result = array();
        	  $attributes_data = array();
           
            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }
   
            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
   
            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
   
                    $current = &$current[$tag];
   
                } else { //There was another element with the same tag name
   
                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;
                       
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
   
                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }
   
            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
   
                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
   
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                       
                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;
   
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                               
                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }
                           
                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }
   
            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
     	   }
		
       		 return($xml_array);
    	}

	if (!function_exists('array_column')) {
    		function array_column(array $array, $columnKey, $indexKey = null)
    		{
        	$result = array();
        	foreach ($array as $subArray) {
            if (!is_array($subArray)) {
                continue;
            } elseif (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
                $result[] = $subArray[$columnKey];
            } elseif (array_key_exists($indexKey, $subArray)) {
                if (is_null($columnKey)) {
                    $result[$subArray[$indexKey]] = $subArray;
                } elseif (array_key_exists($columnKey, $subArray)) {
                    $result[$subArray[$indexKey]] = $subArray[$columnKey];
                }
            }
        }
        	return $result;
    	}
	}



	function getEntityCredentials($username){
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   				<soapenv:Header/>
   				<soapenv:Body>
      				<ws:getEntityCredentials>
         			<userName>'.$username.'</userName>
      				</ws:getEntityCredentials>
   				</soapenv:Body>
				</soapenv:Envelope>';
			$xmlResponse =  getXMLResponse($xmlData, 1);
			$xml         =  xml2array($xmlResponse);
			$dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getEntityCredentialsResponse'];
			if(!empty($dataArr['return'])){
				return $dataArr['return'];
			}else{
				return false;
			}
	}

	function getCustomerAccountDetail($actID){
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      	<getCustomerAccountDetail xmlns="http://ws.unifyv4.com/">
                                <serviceGroupId xmlns="">'.$actID.'</serviceGroupId>
                      	</getCustomerAccountDetail>
               	 </Body>
                </Envelope>';
		$xmlResponse =  getXMLResponse($xmlData, 1);
		$xml         =  xml2array($xmlResponse);
		$dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getCustomerAccountDetailResponse'];
        	if(!empty($dataArr['return'])){
                	return $dataArr['return'];
        	}else{
                	return false;
        	}
	}

	function getAccountDetailsByMobileNumber($mobileno){
			$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	   				<soapenv:Header/>
   					<soapenv:Body>
      					<ws:getAccountDetailsByMobileNumber>
         				<mobileNo>'.$mobileno.'</mobileNo>
      					</ws:getAccountDetailsByMobileNumber>
   					</soapenv:Body>
	  				</soapenv:Envelope>';
			$xmlResponse =  getXMLResponse($xmlData,1);
			$xml         =  xml2array($xmlResponse);
			$dataArr1     =  @$xml['soap:Envelope']['soap:Body']['ns2:getAccountDetailsByMobileNumberResponse']['return'];
			if(!empty($dataArr1)){
				$CountArr   =  count(array_column($dataArr1, 'actid'));
		    		if($CountArr == 0) $dataArr[0] = $dataArr1;
				else               $dataArr = $dataArr1;
                			return $dataArr;
        		}else{
                		return false;
        		}

}



	function saveVoucherCreditnote($MGM_ledgerno, $ledgerActNo, $amount, $narration){

		$date    =      date("Y-m-d");
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   				<soapenv:Header/>
   				<soapenv:Body>
      				<ws:saveVoucher>
         			<voucheri18nKey>unify.financial.voucher.type.creditnote</voucheri18nKey>
         			<tranxCurrency>50</tranxCurrency>
         			<isRefundVoucher>false</isRefundVoucher>
         			<Voucher>
	   		<remarks>'.$narration.'</remarks>
            		<!--Optional:-->
            		<!--Zero or more repetitions:-->
            		<journal>
               			<journalNo></journalNo>
               			<ledgerActNo>'.$MGM_ledgerno.'</ledgerActNo>
               			<narration>'.$narration.'</narration>
               			<amount>'.$amount.'</amount>
               			<orientation>0</orientation>
               			<transactionDate>'.$date.'</transactionDate>
            </journal>
             <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$ledgerActNo.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$amount.'</amount>
               <orientation>1</orientation>
               <transactionDate>'.$date.'</transactionDate>
             </journal>
            <voucherTransactionTypeNo>13</voucherTransactionTypeNo>
         	</Voucher>
         	<Objects>
         	</Objects>
         	<sessionObject>
            	<credentialId></credentialId>
            	<ipAddress>10.0.0.1</ipAddress>
            	<source>2</source>
            	<userName>onlinepayment</userName>
            	<userType>10643</userType>
            	<usrNo>2</usrNo>
         	</sessionObject>
      		</ws:saveVoucher>
   		</soapenv:Body>
		</soapenv:Envelope>';

	return $xmlData;
	}

	function getNonRCList($topUpId){
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">;
			<soapenv:Header/>
			<soapenv:Body>
			<ws:getNonRCList>
			<domNo></domNo>
			<status></status>
			<searchQuery>'.$topUpId.'</searchQuery>
			</ws:getNonRCList>
			</soapenv:Body>
			</soapenv:Envelope>';
	return $xmlData;
	}

	function createAccountNRC($actno,$bnrcno,$amt,$subsno){
		$date = date('Y-m-d H:i:s');
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">;
				<soapenv:Header/>
				<soapenv:Body>
				<ws:createAccountNRC>
 					<accountRC>
  					<anno></anno>
  					<actno>'.$actno.'</actno>
  					<bnrcno>'.$bnrcno.'</bnrcno>
  					<createddt>'.$date.'</createddt>
  					<amount>'.$amt.'</amount>
  					<taxno>1</taxno>
  					<subsno>'.$subsno.'</subsno>
 				</accountRC>
 				<sessionObject>
         			<ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
         			<userName>admin</userName>
         			<usrNo>1</usrNo>
 	 			</sessionObject>
	 			</ws:createAccountNRC>
	 			</soapenv:Body>
         			</soapenv:Envelope>';
		$xmlResponse    =  getXMLResponse($xmlData,3);
	        $xml            =  xml2array($xmlResponse);
        	$dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:createAccountNRCResponse'];
        	if(!empty($dataArr))    return $dataArr['return'];
             	else               return false;

		}

	function getNonRecChargesByBnrcId($topup){
	$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	   <soapenv:Header/>
	   <soapenv:Body>
	      <ws:getNonRecChargesByBnrcId>
        	 <bnrcId>'.$topup.'</bnrcId>
	      </ws:getNonRecChargesByBnrcId>
	   </soapenv:Body>
	</soapenv:Envelope>';
	$xmlResponse    =  getXMLResponse($xmlData,3);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:getNonRecChargesByBnrcIdResponse'];
        if(!empty($dataArr))    return $dataArr['return'];
             else               return false;
	
	}

	function getBillRecChargeByBrcid($rc_id){
	$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	   <soapenv:Header/>
	   <soapenv:Body>
	      <ws:getBillRecChargeByBrcid>
        	 <!--Optional:-->
	         <brcid>'.$rc_id.'</brcid>
	      </ws:getBillRecChargeByBrcid>
	   </soapenv:Body>
	</soapenv:Envelope>';

        $xmlResponse    =  getXMLResponse($xmlData,3);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:getBillRecChargeByBrcidResponse'];
        if(!empty($dataArr))    return $dataArr['return'];
             else               return false;
	}

	function createAccountRC($brcno,$rcno,$createDate,$amount,$actno,$subsno,$closeDate=""){
	$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	   <soapenv:Header/>
	   <soapenv:Body>
	      <ws:createAccountRC>
       		  <accountRC>
	            <brcno>'.$brcno.'</brcno>
        	    <rcno>'.$rcno.'</rcno>
	            <createDate>'.$createDate.'</createDate>
        	    <closeDate>'.$closeDate.'</closeDate>
	            <amount>'.$amount.'</amount>
        	    <taxno>1</taxno>
	            <actno>'.$actno.'</actno>
	            <subsno>'.$subsno.'</subsno>
        	 </accountRC>
	         <sessionObject>
        	    <ipAddress>10.158.105.4</ipAddress>
	            <userName>admin</userName>
        	    <usrNo>1</usrNo>
	         </sessionObject>
	      </ws:createAccountRC>
	   </soapenv:Body>
	</soapenv:Envelope>';
	$xmlResponse    =  getXMLResponse($xmlData,3);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:createAccountRCResponse'];
        if(!empty($dataArr))    return $dataArr['return'];
             else               return false;

	}

	function changesubscription($arr){
                $date = date('Y-m-d');
                $xml='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                        <soapenv:Header/>
                        <soapenv:Body>
                        <ws:changeSubscription>
                        <Subscription>
                        <subsNo>'.$arr['subsNo'].'</subsNo>
                        <serviceGroupNo>'.$arr['serviceGroupNo'].'</serviceGroupNo>
                        <externalid></externalid>
                        <ratePlanNo>'.$arr['servicePlanNo'].'</ratePlanNo>
                        <orgNo>'.$arr['orgid'].'</orgNo>
                        <startDt>'.$arr['startdate'].'</startDt>
			<billNow>'.$arr['billnow'].'</billNow>
                        </Subscription>';
                        $bnc='<Objects>';
                        $recch=''; $nonRecCh='';$i=0;
                for($i=0; $i<count($arr['bnr']); $i++){
                foreach($arr['bnr'][$i] as $key=> $val){
                if(isset($val['brcno']) && $key=='brcno'){
			  $recch.='<RecCharges>
                                        <acno></acno>
                                        <actno>'.$arr['serviceGroupNo'].'</actno>
                                        <amount>'.$arr['bnr'][$i]['rate'].'</amount>
                                        <arno></arno>
                                        <brcDesc></brcDesc>
                                        <brcId></brcId>
                                        <brcno>'.$arr['bnr'][$i]['brcno'].'</brcno>
                                        <closedt></closedt>
                                        <consumed></consumed>
                                        <createddt></createddt>
                                        <enableCustomValue></enableCustomValue>
                                        <iterations></iterations>
                                        <offSet></offSet>
                                        <rcno>'.$arr['bnr'][$i]['rcno'].'</rcno>
                                        <subsCloseDate></subsCloseDate>
                                        <subsno></subsno>
                                        <taxno>1</taxno>
                                        </RecCharges>';
			}elseif(isset($val['bnrcNo']) && $key=='bnrcNo'){
                                    $nonRecCh.='<NonRecCharges>
                                        <bnrcNo>'.$arr['bnr'][$i]['bnrcNo'].'</bnrcNo>
                                        <apply>true</apply>
                                        <rate>'.$arr['bnr'][$i]['rate'].'</rate>
                                        <taxNo>1</taxNo>
                                        <clabel></clabel>
                                        <enableCustomValues></enableCustomValues>
                                        </NonRecCharges>';
                                }

                        }        }
                                     $bj='</Objects>';
                                     $cred='<sessionObject>
                                           <credentialId></credentialId>
                                           <ipAddress>203.122.58.10</ipAddress>
                                           <source>2</source>
                                           <userName>admin</userName>
                                           <userType></userType>
                                           <usrNo>2</usrNo>
                                           </sessionObject>';
                                     $end='</ws:changeSubscription>
                                           </soapenv:Body>
                                           </soapenv:Envelope>';
                $xmlData=$xml.$bnc.$recch.$nonRecCh.$bj.$cred.$end;
                return $xmlData;

        }

	function changesubscriptionB2B($arr){
                $xml='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                        <soapenv:Header/>
                        <soapenv:Body>
                        <ws:changeSubscription>
                        <Subscription>
                        <subsNo>'.$arr['subsNo'].'</subsNo>
                        <serviceGroupNo>'.$arr['serviceGroupNo'].'</serviceGroupNo>
                        <externalid></externalid>
                        <ratePlanNo>'.$arr['servicePlanNo'].'</ratePlanNo>
                        <orgNo>'.$arr['orgid'].'</orgNo>
                        <startDt>'.$arr['startdate'].'</startDt>
			<billNow>'.$arr['billnow'].'</billNow>
			<delayCharge>'.$arr['freetrail'].'</delayCharge>
			<startCharging>'.$arr['chargeStartDate'].'</startCharging>
			<autoConfirmFreeTrial>'.$arr['autoConfirmFreeTrial'].'</autoConfirmFreeTrial>
                        </Subscription>';
                        $bnc='<Objects>';
                        $recch=''; $nonRecCh='';$i=0;
                for($i=0; $i<count($arr['bnr']); $i++){
                foreach($arr['bnr'][$i] as $key=> $val){
                if(isset($val['brcno']) && $key=='brcno'){
			  $recch.='<RecCharges>
                                        <acno></acno>
                                        <actno>'.$arr['serviceGroupNo'].'</actno>
                                        <amount>'.$arr['bnr'][$i]['rate'].'</amount>
                                        <arno></arno>
                                        <brcDesc></brcDesc>
                                        <brcId></brcId>
                                        <brcno>'.$arr['bnr'][$i]['brcno'].'</brcno>
                                        <closedt></closedt>
                                        <consumed></consumed>
                                        <createddt></createddt>
                                        <enableCustomValue></enableCustomValue>
                                        <iterations></iterations>
                                        <offSet></offSet>
                                        <rcno>'.$arr['bnr'][$i]['rcno'].'</rcno>
                                        <subsCloseDate></subsCloseDate>
                                        <subsno></subsno>
                                        <taxno>1</taxno>
                                        </RecCharges>';
			}elseif(isset($val['bnrcNo']) && $key=='bnrcNo'){
                                    $nonRecCh.='<NonRecCharges>
                                        <bnrcNo>'.$arr['bnr'][$i]['bnrcNo'].'</bnrcNo>
                                        <apply>true</apply>
                                        <rate>'.$arr['bnr'][$i]['rate'].'</rate>
                                        <taxNo>1</taxNo>
                                        <clabel></clabel>
                                        <enableCustomValues></enableCustomValues>
                                        </NonRecCharges>';
                                }

                        }        }
                                     $bj='</Objects>';
                                     $cred='<sessionObject>
                                           <credentialId></credentialId>
                                           <ipAddress>203.122.58.10</ipAddress>
                                           <source>2</source>
                                           <userName>admin</userName>
                                           <userType></userType>
                                           <usrNo>2</usrNo>
                                           </sessionObject>';
                                     $end='</ws:changeSubscription>
                                           </soapenv:Body>
                                           </soapenv:Envelope>';
                $xmlData=$xml.$bnc.$recch.$nonRecCh.$bj.$cred.$end;
                return $xmlData;

        }


	function getrateplanid($pkgname){
                $date = date('Y-m-d H:i:s');
                $xmlData='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                        <soapenv:Header/>
                        <soapenv:Body>
                                <ws:getRatePlanByRatePlanID>
                                <ratePlanID>'.$pkgname.'</ratePlanID>
                                </ws:getRatePlanByRatePlanID>
                                </soapenv:Body>
                        </soapenv:Envelope>';
		$xmlResponse =  getXMLResponse($xmlData, 1);
                $xml         =  xml2array($xmlResponse);
                $dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getRatePlanByRatePlanIDResponse'];
                if(!empty($dataArr['return'])){
                        return $dataArr['return'];
                }else{
                        return false;
                }
        }

	function getBillingContractForRatePlan($planNo){

                $xmlData='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                         <soapenv:Header/>
                         <soapenv:Body>
                                <ws:getBillingContractForRatePlan>
                                        <!--Optional:-->
                                <ratePlanNumber>'.$planNo.'</ratePlanNumber>
                                </ws:getBillingContractForRatePlan>
                         </soapenv:Body>
                         </soapenv:Envelope>';
                return $xmlData;
        }

	function getBillingChargesForBillingContract($billConNo){

                $xmlData='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                         <soapenv:Header/>
                         <soapenv:Body>
                         <ws:getBillingChargesForBillingContract>
                         <!--Optional:-->
                         <billContractNumber>'.$billConNo.'</billContractNumber>
                        </ws:getBillingChargesForBillingContract>
                        </soapenv:Body>
                        </soapenv:Envelope>';
                return $xmlData;
        }
	function getRateClassList($brcno){

                $xml='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                        <soapenv:Header/>
                        <soapenv:Body>
                        <ws:getRateClassList>
                        <!--Optional:-->
                        <brcNo>'.$brcno.'</brcNo>
                        </ws:getRateClassList>
                        </soapenv:Body>
                        </soapenv:Envelope>';
                $xmlResponse =  getXMLResponse($xml, 3);
                $xml         =  xml2array($xmlResponse);
                $dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getRateClassListResponse']['rateClassList'];
                if(!empty($dataArr['return'])){
                        return $dataArr['return'];
                }else{
                        return false;
                }

        }
        function getNonRC($bnrcno){
                $xml='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
                         <soapenv:Header/>
                         <soapenv:Body>
                         <ws:getNonRC>
                         <!--Optional:-->
                         <nonRCNo>'.$bnrcno.'</nonRCNo>
                         </ws:getNonRC>
                         </soapenv:Body>
                         </soapenv:Envelope>';
                        return $xml;

                }
        function LogRequestDetails($canid,$message){

                	//file_put_contents('../log/changeplan.log', $message, FILE_APPEND);
                	$fp = fopen("../log/changeplan.txt","a+");
                	$abc_txt = "\n".date("Y-m-d H:i:s")."\t".$canid."\t".$message."\t".$_SERVER['REMOTE_ADDR']."\tAUTH-SUCCESS";
                	fwrite($fp,$abc_txt);
                	fclose($fp);
                }


	function getSubscriptionListByOrgId($orgID)
		{
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getSubscriptionListByOrgId xmlns="http://ws.unifyv4.com/">
                                <organisationNo xmlns="">'.$orgID.'</organisationNo>
                      </getSubscriptionListByOrgId>
                </Body>
                </Envelope>';
		return $xmlData;
	}

	function searchInvoice($params){
		
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    			<Body>
        		<searchInvoice xmlns="http://ws.unifyv4.com/">
            		<!-- Optional -->
            		<searchCriteria xmlns="">
                		<start>0</start>
                		<limit>'.$params["limit"].'</limit>
                		<orgNo></orgNo>
                		<actNo></actNo>
                		<actId>'.$params["canid"].'</actId>
                		<domainNo></domainNo>
                		<invoiceNo></invoiceNo>
                		<billStartDate></billStartDate>
                		<billEndDate></billEndDate>
                		<invoiceDate></invoiceDate>
                		<dueDate></dueDate>
                		<physicalBill></physicalBill>
                		<settled></settled>
                		<includeContent>false</includeContent>
                		<cslno></cslno>
            			</searchCriteria>
        		</searchInvoice>
    			</Body>
			</Envelope>';
			$xmlResponse =  getXMLResponse($xmlData,3);
       			$xml     =  xml2array($xmlResponse);
			$dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:searchInvoiceResponse']['return']['results'];
	       		if(!empty($dataArr)){
                        	return $dataArr;
                	}else{
                        	return false;
                	}

		}

		function getInvoiceCont($invoiceno){

			        $xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   			<Body>
                      			<getInvoice xmlns="http://ws.unifyv4.com/">
                                	<arg0 xmlns="">'.$invoiceno.'</arg0>
                      			</getInvoice>
                			</Body>
                			</Envelope>';
    				$xmlResponse =  getXMLResponse($xmlData);
    				$xml         =  xml2array($xmlResponse);
				$dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getInvoiceResponse'];
	 	              	if(!empty($dataArr['return'])){
                		        return $dataArr['return'];
                		}else{
                 		       return false;
                		}

		}

	function getSessionhistoryByCanId($actID,$fromdate,$todate){
		
		 $xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                  	<Body>
                      	<getOrgByActID xmlns="http://ws.unifyv4.com/">
                        <actID xmlns="">'.$actID.'</actID>
                      	</getOrgByActID>
                	</Body>
                	</Envelope>';
    		$xmlResponse =  getXMLResponse($xmlData);
    		$xml         =  xml2array($xmlResponse);
    		if(!empty($xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'])){
            		$dataArrCM     =  $xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'];
        		$orgID = $dataArrCM['orgNo'];
			$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
        			    <soapenv:Header/>
        			    <soapenv:Body>
       				     <ws:getSessionHistoryByOrgNo>
                                        <organisationNo xmlns="">'.$orgID.'</organisationNo>
                                        <fromDate xmlns="">'.$fromdate.'</fromDate>
                                        <toDate xmlns="">'.$todate.'</toDate>
        			     </ws:getSessionHistoryByOrgNo>
        			    </soapenv:Body>
        		          </soapenv:Envelope>';
        		$xmlResponse =  getXMLResponseLive($xmlData);
        		$xml         =  xml2array($xmlResponse);
			if(!empty($xml['soap:Envelope']['soap:Body']['ns2:getSessionHistoryByOrgNoResponse']['SessionHistoryList'])){	
				return $xml['soap:Envelope']['soap:Body']['ns2:getSessionHistoryByOrgNoResponse']['SessionHistoryList']['return'];
			}else{
				return false;
			}
    		}else{
        		return false;
    		}
	    } 

	function getTransactionHistoryList($actID, $from_date, $to_date){
		  $xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                        <Body>
                        <getOrgByActID xmlns="http://ws.unifyv4.com/">
                        <actID xmlns="">'.$actID.'</actID>
                        </getOrgByActID>
                        </Body>
                        </Envelope>';
                $xmlResponse =  getXMLResponse($xmlData);
                $xml         =  xml2array($xmlResponse);
                if(!empty($xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'])){
                        $dataArrCM     =  $xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'];
                        $orgID = $dataArrCM['orgNo'];

		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getTransactionHistoryListByOrgNo xmlns="http://ws.unifyv4.com/">
                                <organisationNo xmlns="">'.$orgID.'</organisationNo>
                                <fromDate xmlns="">'.$from_date.'</fromDate>
                                <toDate xmlns="">'.$to_date.'</toDate>
                      </getTransactionHistoryListByOrgNo>
                </Body>
                </Envelope>';


		  $xmlResponse =  getXMLResponse($xmlData);
                  $xml         =  xml2array($xmlResponse);
			 if(!empty($xml['soap:Envelope']['soap:Body']['ns2:getTransactionHistoryListByOrgNoResponse']['TransactionHistoryList']['return'])){   
                                return $xml['soap:Envelope']['soap:Body']['ns2:getTransactionHistoryListByOrgNoResponse']['TransactionHistoryList']['return'];
                        }else{
                                return false;
                        }
		     }else{
                        return false;
	            }

		}

	function getOrgByActID($actID){
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getOrgByActID xmlns="http://ws.unifyv4.com/">
                                <actID xmlns="">'.$actID.'</actID>
                      </getOrgByActID>
                </Body>
                </Envelope>';
		 $xmlResponse =  getXMLResponse($xmlData);
                 $xml         =  xml2array($xmlResponse);
		if(!empty($xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse'])){   
                                return $xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'];
                        }else{
                                return false;
                        }
	}


	function getContact($contact_id){
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   		<soapenv:Header/>
   		<soapenv:Body>
      		<ws:getContact>
         	<contactId>'.$contact_id.'</contactId>
      		</ws:getContact>
   		</soapenv:Body>
		</soapenv:Envelope>';
		$xmlResponse =  getXMLResponse($xmlData,1);
                  $xml         =  xml2array($xmlResponse);

                  return $xml;
	}


	function getSelfCareCredentialsbyActID($actno){

		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
			<soapenv:Header/>
			<soapenv:Body>
        			<ws:selfCareCredentialsByActId>
                			<actId>'.$actno.'</actId>
        			</ws:selfCareCredentialsByActId>
			</soapenv:Body>
			</soapenv:Envelope>';

		$xmlResponse =  getXMLResponse($xmlData,1);
                $xml         =  xml2array($xmlResponse);
		if(!empty($xml['soap:Envelope']['soap:Body']['ns2:selfCareCredentialsByActIdResponse'])){   
                                return $xml['soap:Envelope']['soap:Body']['ns2:selfCareCredentialsByActIdResponse']['return'];
                        }else{
                                return false;
                        }

                  return $xml;
	}


	function getContactsByOrgId($orgID){
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getContactsByOrgId xmlns="http://ws.unifyv4.com/">
                                <OrgNo xmlns="">'.$orgID.'</OrgNo>
                      </getContactsByOrgId>
                </Body>
                </Envelope>';

		$xmlResponse =  getXMLResponse($xmlData);
                $xml         =  xml2array($xmlResponse);
		$dataArr     =  @$xml['soap:Envelope']['soap:Body']['ns2:getContactsByOrgIdResponse']['contactListByOrgId'];
                if(!empty($dataArr['return'])){
                 	return $dataArr['return'];
                }else{
                        return false;
                }

	}


	function findAllCityByCountry($countryno){
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <findAllCityByCountry xmlns="http://ws.unifyv4.com/">
                                <countryNo xmlns="">'.$countryno.'</countryNo>
                      </findAllCityByCountry>
                </Body>
                </Envelope>';
		 $xmlResponse =  getXMLResponse($xmlData);
                $xml         =  xml2array($xmlResponse);

                return $xml;

		}

	function getContactCommMedium($contactId){
		$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getContactCommMedium xmlns="http://ws.unifyv4.com/">
                                <contactId xmlns="">'.$contactId.'</contactId>
                      </getContactCommMedium>
                </Body>
                </Envelope>';
		$xmlResponse =  getXMLResponse($xmlData);
                $xml         =  xml2array($xmlResponse);
		$dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getContactCommMediumResponse']['CommMediumList'];
		if(!empty($dataArr)) 	return $dataArr['return'];
		else			return false;
                

	}
	function changepswd($contactno,$username,$newpassword){
	
		if(strlen($newpassword)>= 6 && strlen($newusername) < 11)
		{
		   $data = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
		   <soapenv:Header/>
		   <soapenv:Body>
			  <ws:changePasswordForEntityCredentials>
				 <entityCredentials>
					<!--Optional:-->
					<commTypeNo>4</commTypeNo>
					<!--Optional:-->
					<contactNo>'.$contactno.'</contactNo>
					<!--Optional:-->
					<credentialKey>'.$username.'</credentialKey>
					<credentialNo></credentialNo>
					<!--Optional:-->
					<credentialValue>'.$newpassword.'</credentialValue>
				 </entityCredentials>
				 <!--Optional:-->
				 <sessionObject>
					<!--Optional:-->
					<credentialId></credentialId>
					<!--Optional:-->
					<ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
					<!--Optional:-->
					<source></source>
					<!--Optional:-->
					<userName>admin</userName>
					<!--Optional:-->
					<userType></userType>
					<!--Optional:-->
					<usrNo>1</usrNo>
				 </sessionObject>
			  </ws:changePasswordForEntityCredentials>
		   </soapenv:Body>
		</soapenv:Envelope>';
			$xmlResponse 	=  getXMLResponse($data,1);
			$xml         	=  xml2array($xmlResponse);
			$dataArr     	=  $xml['soap:Envelope']['soap:Body']['ns2:changePasswordForEntityCredentialsResponse']['return'];

    		}else{
		$dataArr = array("status"=>"failure","response"=>array(),"message"=>"Password length should be 6 to 10.");
		}

		return $dataArr;
	
	}


	function GetCommMedium($contactNo){
		$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   			<soapenv:Header/>
   			<soapenv:Body>
      			<ws:getContactCommMedium>
         		<contactId>'.$contactNo.'</contactId>
      			</ws:getContactCommMedium>
   			</soapenv:Body>
			</soapenv:Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,1);
		$xml          =  xml2array($xmlResponse);
		return $xml;

	}

	function editCommMedium($commMediumNo, $contactNo, $commTypeNo, $value){
		$xmlData = '
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">;;
   		<soapenv:Header/>
   		<soapenv:Body>
      			<ws:editCommMedium>
        		 <communicationMedium>
           		 <commMediumNo>'.$commMediumNo.'</commMediumNo>
           		 <commSlotNo>0</commSlotNo>
           		 <commTypeNo>'.$commTypeNo.'</commTypeNo>
           		 <contactNo>'.$contactNo.'</contactNo>
           		 <dnc>false</dnc>
            		<ident>'.$value.'</ident>
            		<isDefault>false</isDefault>
         		</communicationMedium>
  			<sessionObject>
           		 <credentialId></credentialId>
            		<ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
            		<source>2</source>
            		<userName>admin</userName>
            		<userType>2</userType>
            		<usrNo>2</usrNo>
         		</sessionObject>
      			</ws:editCommMedium>
   			</soapenv:Body>
		</soapenv:Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,1);
                $xml          =  xml2array($xmlResponse);
                return $xml;

	}

	function save_promise_pay($invoiceNo,$date){
		
		$xmlData='<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    			<Body>
        		<savePromiseToPay xmlns="http://ws.unifyv4.com/">
            		<!-- Optional -->
            		<PromiseToPay xmlns="">
                		<invoiceNo>'.$invoiceNo.'</invoiceNo>
                		<promiseToPayDay>'.$date.'</promiseToPayDay>
                		<notificationStep></notificationStep>
                		<skipall>false</skipall>
                		<pauseDunning>true</pauseDunning>
                		<configType>REMIND_PAUSE</configType>
                		<id></id>
            		</PromiseToPay>
            		<!-- Optional -->
            		<sessionObject xmlns="">
                		<credentialId>1</credentialId>
                		<ipAddress>127.0.0.1</ipAddress>
                		<source>1</source>
                		<userName>admin</userName>
                		<userType>1</userType>
                		<usrNo>1</usrNo>
            			</sessionObject>
        		</savePromiseToPay>
    			</Body>
		</Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,1);
                $xml          =  xml2array($xmlResponse);
                return $xml;


	}
	
	function getpromistopay($actNo){

		$xmlData='<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    			 <Body>
        		<getPromiseToPayLog xmlns="http://ws.unifyv4.com/">
            			<serviceGroupNo xmlns="">'.$actNo.'</serviceGroupNo>
            			<serviceGroupId xmlns=""></serviceGroupId>
            			<latestLogCount xmlns="">2</latestLogCount>
        			</getPromiseToPayLog>
    			</Body>
			</Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,1);
                $xml          =  xml2array($xmlResponse);
                return $xml;

	}


	function getLedgerByAccountId($actID){
	$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	  <soapenv:Header/>
		    <soapenv:Body>
		        <ws:getLedgerByAccountId>
                	    <actId>'.$actID.'</actId>
	             </ws:getLedgerByAccountId>
      		 </soapenv:Body>
		</soapenv:Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,2);
                $xml          =  xml2array($xmlResponse);
		$dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getLedgerByAccountIdResponse'];
                if(!empty($dataArr))    return $dataArr['return'];
                else                    return false;
                
	}

	function getStatutoryData($ledgerActNo){
	$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getStatutoryData xmlns="http://ws.unifyv4.com/">
                                <ledgerAccountNo xmlns="">'.$ledgerActNo.'</ledgerAccountNo>
                      </getStatutoryData>
                </Body>
                </Envelope>';
		$xmlResponse  =  getXMLResponse($xmlData,2);
                $xml          =  xml2array($xmlResponse);
                $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getStatutoryDataResponse']['StatutoryDataList'];
                if(!empty($dataArr))    return $dataArr['return'];
                else                    return false;

	}

	function updateStatutoryData($params){
	$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
		   <soapenv:Header/>
		   <soapenv:Body>
		      <ws:updateStatutoryData>
		         <statutory>
		            <ledgerAccountId>'.$params["ledgerActNo"].'</ledgerAccountId>
			    <statutoryNo>'.$params["statutoryNo"].'</statutoryNo>
		            <statutoryTypeNo>'.$params["statutoryTypeNo"].'</statutoryTypeNo>
		            <value>'.$params["value"].'</value>
		         </statutory>
		         <sessionObject>
		            <ipAddress>127.0.0.1</ipAddress>
		            <source>2</source>
		            <userName>admin</userName>
		            <userType>2</userType>
		            <usrNo>2</usrNo>
		         </sessionObject>
		      </ws:updateStatutoryData>
		   </soapenv:Body>
		</soapenv:Envelope>';
	 	$xmlResponse  =  getXMLResponse($xmlData,2);
                $xml          =  xml2array($xmlResponse);
                $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:updateStatutoryDataResponse'];
                if(!empty($dataArr))    return $dataArr['return'];
                else                    return false;
	}


function billSetupwithDate($actno, $advanceBilling, $billcycleno, $billProfileNo, $cycle, $cycleDuration, $invoiceTemplateNo, $receiptTemplateNo, $DunningProfile, $bill_startdate, $first_invoice_date){
//$bill_startdate = date("Y-m-d");
$bill_enddate = date("Y-m-d", strtotime($bill_startdate. " + " .$cycle." month"));

$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:billSetup>
         <billSetup>
            <actNo>'.$actno.'</actNo>
            <advanceBilling>'.$advanceBilling.'</advanceBilling>
            <billCycleNo>'.$billcycleno.'</billCycleNo>
            <billEndDate>'.$bill_enddate.'</billEndDate>
            <billProfileNo>'.$billProfileNo.'</billProfileNo>
            <billStartDate>'.$bill_startdate.'</billStartDate>
            <cycle>'.$cycle.'</cycle>
            <cycleDuration>'.$cycleDuration.'</cycleDuration>
            <firstInvoiceDate>'.$first_invoice_date.'</firstInvoiceDate>
            <invoiceTemplateNo>'.$invoiceTemplateNo.'</invoiceTemplateNo>
            <receiptTemplateNo>'.$receiptTemplateNo.'</receiptTemplateNo>
            <runInvoice>false</runInvoice>
	    <domSegmentMapId>'.$DunningProfile.'</domSegmentMapId>
         </billSetup>
         <sessionObject>
           <credentialId></credentialId>
            <ipAddress>10.158.105.4</ipAddress>
            <source></source>
            <userName>admin</userName>
            <userType></userType>
            <usrNo>1</usrNo>
         </sessionObject>
      </ws:billSetup>
   </soapenv:Body>
</soapenv:Envelope>';
	$xmlResponse    =  getXMLResponse($xmlData,1);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:billSetupResponse'];
	if(!empty($dataArr))    return $dataArr['return'];
             else               return false;

}

function getbillSetup($actno){
$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getBillSetup>
         <serviceGroupNo>'.$actno.'</serviceGroupNo>
      </ws:getBillSetup>
   </soapenv:Body>
</soapenv:Envelope>';

        $xmlResponse    =  getXMLResponse($xmlData,1);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:getBillSetupResponse'];
        if(!empty($dataArr))    return $dataArr['BillSetupResponse'];
             else               return false;

}

function getRcByOrgId($orgID){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getRcByOrgId xmlns="http://ws.unifyv4.com/">
                                <orgId xmlns="">'.$orgID.'</orgId>
                      </getRcByOrgId>
                </Body>
                </Envelope>';
    $xmlResponseRC      =  getXMLResponse($xmlData);
    $xmlRC              =  xml2array($xmlResponseRC);
    $dataArr1           =  $xmlRC['soap:Envelope']['soap:Body']['ns2:getRcByOrgIdResponse']['getRcByOrgId']['return'];
    if(is_array($dataArr1)){
    $CountArr           =  count(array_column($dataArr1, 'brcDesc'));
    if($CountArr == 0)  $dataArrRC[0] = $dataArr1;
        else            $dataArrRC = $dataArr1;
    }

return $dataArrRC;
}

function getNrcByOrgId($orgID){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getNrcByOrgId xmlns="http://ws.unifyv4.com/">
                                <orgId xmlns="">'.$orgID.'</orgId>
                      </getNrcByOrgId>
                </Body>
                </Envelope>';
    $xmlResponseNRC     =  getXMLResponse($xmlData);
    $xmlNRC             =  xml2array($xmlResponseNRC);
    $dataArr1           =  $xmlNRC['soap:Envelope']['soap:Body']['ns2:getNrcByOrgIdResponse']['getNrcByOrgId']['return'];
    if(is_array($dataArr1)){
    $CountArr           =  count(array_column($dataArr1, 'bnrcDesc'));
    if($CountArr == 0)  $dataArrNRC[0] = $dataArr1;
        else            $dataArrNRC = $dataArr1;
    }

return $dataArrNRC;

}

function getTopUpByActNo($acno){
$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getActiveMetaTagTopUpByServiceGroupNo>
         <!--Optional:-->
         <serviceGroupNo>'.$acno.'</serviceGroupNo>
      </ws:getActiveMetaTagTopUpByServiceGroupNo>
   </soapenv:Body>
</soapenv:Envelope>';
    $xmlResponse     =  getXMLResponse($xmlData,3);
    $xmlNRC             =  xml2array($xmlResponse);
    $dataArr1           =  $xmlNRC['soap:Envelope']['soap:Body']['ns2:getActiveMetaTagTopUpByServiceGroupNoResponse']['metaTagTopUpList']['return'];
    if(is_array($dataArr1)){
    $CountArr           =  count(array_column($dataArr1, 'subsNo'));
    if($CountArr == 0)  $dataArr[0] = $dataArr1;
        else            $dataArr = $dataArr1;
    }

return $dataArr;

}


function removeAccountRC($arno){
        $xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:removeMetaTagRCTopUpByArno>
         <arno>'.$arno.'</arno>
	   <sessionObject>
            <credentialId></credentialId>
            <ipAddress>127.0.0.1</ipAddress>
            <source>2</source>
            <userName>onlinepayment</userName>
            <userType>10643</userType>
            <usrNo>2</usrNo>
         </sessionObject>
      </ws:removeMetaTagRCTopUpByArno>
   </soapenv:Body>
</soapenv:Envelope>';

        $xmlResponse    =  getXMLResponse($xmlData,3);
        $xml            =  xml2array($xmlResponse);
        $dataArr        =  @$xml['soap:Envelope']['soap:Body']['ns2:removeMetaTagRCTopUpByArnoResponse'];
        if(!empty($dataArr))    return $dataArr['return'];
             else               return false;
   }

function searchSMSLog($date){
    $xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:rep="http://report.ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <rep:searchSMSLog>
         <searchCriteria>
            <start>0</start>
            <limit>100</limit>
            <fromDate>'.$date.'</fromDate>
            <toDate></toDate>
            <domainNo></domainNo>
            <serviceGroupNo></serviceGroupNo>
            <afterSMSLogNo></afterSMSLogNo>
            <eventIds></eventIds>
            <eventIds></eventIds>
         </searchCriteria>
      </rep:searchSMSLog>
   </soapenv:Body>
</soapenv:Envelope>
';

    $xmlResponse =  getXMLResponse($xmlData,4);	
    $xml 	 =  xml2array($xmlResponse);
#    echo '<pre>';print_r($xml['soap:Envelope']);
   	if(!empty($xml['soap:Envelope']['soap:Body']['ns2:searchSMSLogResponse']['return'])){
		$data = $xml['soap:Envelope']['soap:Body']['ns2:searchSMSLogResponse']['return']['results'];
		return $data;
	}else{
		return false;
	}	
}

?>
