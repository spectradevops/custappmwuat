<?php

class Unify{

function getXMLResponse($data,$api=0){
    switch($api){
	 case 3:
#                $policyURL = 'https://unify.spectra.co/unifyejb/BillingAPI';
                $policyURL = 'https://unifyuat.spectra.co/unifyejb/BillingAPI';
                break;
        case 2:
#                $policyURL = 'https://unify.spectra.co/unifyejb/FinanceAPI';
               $policyURL = 'https://unifyuat.spectra.co/unifyejb/FinanceAPI';
                break;
        case 1:
#                $policyURL = 'https://unify.spectra.co/unifyejb/CRMAPI';
               $policyURL = 'https://unifyuat.spectra.co/unifyejb/CRMAPI';
                break;
        default:
#                $policyURL = 'https://unify.spectra.co/unifyejb/UnifyWS';
               $policyURL = 'https://unifyuat.spectra.co/unifyejb/UnifyWS';
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $policyURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    file_put_contents("/var/www/html/ftthpay/mwapis_uat/logs/payment.log","\n".date("Y-m-d H:i:s")." ".$data, FILE_APPEND | LOCK_EX);
    $xmlResponse = curl_exec($ch);
    $ch_info = curl_getinfo($ch);
    curl_close($ch);
   // print_r($xmlResponse);
   file_put_contents("/var/www/html/ftthpay/mwapis_uat/logs/payment.log","\n".date("Y-m-d H:i:s")." ".$xmlResponse, FILE_APPEND | LOCK_EX);

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

function getOrgByActID($actID){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getOrgByActID xmlns="http://ws.unifyv4.com/">
                                <actID xmlns="">'.$actID.'</actID>
                      </getOrgByActID>
                </Body>
                </Envelope>';

    $xmlResponse =  $this->getXMLResponse($xmlData);
    $xml         =  $this->xml2array($xmlResponse);
    if(is_array($xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'])){ 
    	$dataArrCM     =  $xml['soap:Envelope']['soap:Body']['ns2:getOrgByActIDResponse']['return'];
	    return $dataArrCM;

    }else{
            return $xml;
	}
}

function getTDSLedgerForCurrentFicalYear($orgNo){
     $xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
       <soapenv:Header/>
         <soapenv:Body>
            <ws:getTDSLedgerForCurrentFicalYear>
            <!--Optional:-->
            <orgNo>'.$orgNo.'</orgNo>
            </ws:getTDSLedgerForCurrentFicalYear>
        </soapenv:Body>
      </soapenv:Envelope>';
        $xmlResponse =  $this->getXMLResponse($xmlData,2);
        $xml         =  $this->xml2array($xmlResponse);
        $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getTDSLedgerForCurrentFicalYearResponse']['return'];

return $dataArr;

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

        $xmlResponse =  $this->getXMLResponse($xmlData,2);
        $xml         =  $this->xml2array($xmlResponse);
        $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getLedgerByAccountIdResponse']['return'];
return $dataArr;
}

function searchLedgerAccount($actID, $coa_no){

$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:searchLedgerAccount>
         <!--Optional:-->
         <searchCriteria>
            <!--Optional:-->
            <start></start>
            <!--Optional:-->
            <limit></limit>
            <!--Optional:-->
            <coaNo>'.$coa_no.'</coaNo>
            <!--Optional:-->
            <accountCode>'.$actID.'</accountCode>
            <!--Optional:-->
            <accountName></accountName>
            <!--Optional:-->
            <voucherTypeNo></voucherTypeNo>
         </searchCriteria>
      </ws:searchLedgerAccount>
   </soapenv:Body>
</soapenv:Envelope>';
        $xmlResponse =  $this->getXMLResponse($xmlData,2);
        $xml         =  $this->xml2array($xmlResponse);
        $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:searchLedgerAccountResponse']['ledgerAccountList']['return'];
return $dataArr;
}

function getCustomerAccountDetailList($OrgID){
 $xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getCustomerAccountDetailList xmlns="http://ws.unifyv4.com/">
                                <organisationNo xmlns="">'.$OrgID.'</organisationNo>
                      </getCustomerAccountDetailList>
                </Body>
                </Envelope>';

    $xmlResponse =  $this->getXMLResponse($xmlData);	
    $xml 	 =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getCustomerAccountDetailListResponse']['CustomerAccountDetailList']['return'];
    return $dataArr;
}

function getContactCommMedium($contactId){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getContactCommMedium xmlns="http://ws.unifyv4.com/">
                                <contactId xmlns="">'.$contactId.'</contactId>
                      </getContactCommMedium>
                </Body>
                </Envelope>';
    $xmlResponse =  $this->getXMLResponse($xmlData);
    $xml         =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getContactCommMediumResponse']['CommMediumList']['return'];
    return $dataArr;
}

function getContactsByOrgId($orgID){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getContactsByOrgId xmlns="http://ws.unifyv4.com/">
                                <OrgNo xmlns="">'.$orgID.'</OrgNo>
                      </getContactsByOrgId>
                </Body>
                </Envelope>';
    $xmlResponse =  $this->getXMLResponse($xmlData);
    $xml         =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getContactsByOrgIdResponse']['contactListByOrgId']['return'];
    return $dataArr;
}

function getInvoiceByOrgNo($orgID){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getInvoiceByOrgNo xmlns="http://ws.unifyv4.com/">
                                <orgno xmlns="">'.$orgID.'</orgno>
                      </getInvoiceByOrgNo>
                </Body>
                </Envelope>';
    $xmlResponse =  $this->getXMLResponse($xmlData);
    $xml         =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getInvoiceByOrgNoResponse']['InvoiceList']['return'];
    return $dataArr;
}

function getStatutoryData($ledgerActNo){
$xmlData = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
                   <Body>
                      <getStatutoryData xmlns="http://ws.unifyv4.com/">
                                <ledgerAccountNo xmlns="">'.$ledgerActNo.'</ledgerAccountNo>
                      </getStatutoryData>
                </Body>
                </Envelope>';
    $xmlResponse =  $this->getXMLResponse($xmlData,2);
    $xml         =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getStatutoryDataResponse']['StatutoryDataList']['return'];
return $dataArr;
}

function getLedgerByOrgNo($orgID){
$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
	   <soapenv:Header/>
	   <soapenv:Body>
	      <ws:getLedgerByOrgNo>
	         <orgNo>'.$orgID.'</orgNo>
	      </ws:getLedgerByOrgNo>
	   </soapenv:Body>
	</soapenv:Envelope>';
     $xmlResponse =  $this->getXMLResponse($xmlData,2);
     $xml         =  $this->xml2array($xmlResponse);
     $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:getLedgerByOrgNoResponse']['ledgerAccountList']['return'];
     if(isset($dataArr['ledgerActNo']) && $dataArr['ledgerActNo'] != ""){
	     $ledgerActNo    = $dataArr['ledgerActNo'];
     }else{
		$ledgerActNo    = "";
	}
return $ledgerActNo;
}

function postVoucher($voucherID){
$xmlData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:postVoucher>
         <voucherId>'.$voucherID.'</voucherId>
         <!--Optional:-->
        <sessionObject>
            <!--Optional:-->
            <credentialId></credentialId>
            <!--Optional:-->
            <ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
            <!--Optional:-->
            <source>2</source>
            <!--Optional:-->
            <userName>admin</userName>
            <!--Optional:-->
            <userType>2</userType>
            <!--Optional:-->
            <usrNo>2</usrNo>
         </sessionObject>
      </ws:postVoucher>
   </soapenv:Body>
</soapenv:Envelope>';
    $xmlResponse =  $this->getXMLResponse($xmlData,2);
    $xml         =  $this->xml2array($xmlResponse);
    $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:postVoucherResponse']['return'];
return $dataArr;
}

function  saveVoucherwithtds($tds_ledgerno, $tds_amount, $Bank_ledgerno, $ledgerActNo, $amount, $narration, $pgrefno, $paymentmode, $remarks){
$date    =      date("Y-m-d");
$total_amount = $tds_amount + $amount;

$xmlData = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:saveVoucher>
         <voucheri18nKey>unify.financial.voucher.type.receipt</voucheri18nKey>
         <tranxCurrency>50</tranxCurrency>
         <isRefundVoucher>false</isRefundVoucher>
         <Voucher>
            <!--Zero or more repetitions:-->
	    <remarks>'.$remarks.'</remarks>
            <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$Bank_ledgerno.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$amount.'</amount>
               <orientation>0</orientation>
                <postedDate>'.$date.'</postedDate>
               <transactionDate>'.$date.'</transactionDate>
            </journal>
	    <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$tds_ledgerno.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$tds_amount.'</amount>
               <orientation>0</orientation>
                <postedDate>'.$date.'</postedDate>
               <transactionDate>'.$date.'</transactionDate>
            </journal>
             <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$ledgerActNo.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$total_amount.'</amount>
               <orientation>1</orientation>
                <postedDate>'.$date.'</postedDate>
               <transactionDate>'.$date.'</transactionDate>
             </journal>
            <voucherTransactionTypeNo>22</voucherTransactionTypeNo>
         </Voucher>
         <Objects>

           <VoucherInstrument>
               <instrumentNo></instrumentNo>
               <refData>'.$pgrefno.'</refData>
               <instrumentI18nKey>unify.finance.instrument.type.'.$paymentmode.'</instrumentI18nKey>
               <narration>'.$narration.'</narration>
               <bankNo>1</bankNo>
               <bankBranch>Delhi</bankBranch>
               <amount>'.$amount.'</amount>
               <instrumentDate>'.$date.'</instrumentDate>
               <instrumentTypeNo>1</instrumentTypeNo>
            </VoucherInstrument>

              <Journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$Bank_ledgerno.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$amount.'</amount>
               <orientation>0</orientation>
            </Journal>
	     <Journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$tds_ledgerno.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$tds_amount.'</amount>
               <orientation>0</orientation>
            </Journal>
              <Journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$ledgerActNo.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$total_amount.'</amount>
               <orientation>1</orientation>
            </Journal>
         </Objects>
         <sessionObject>
            <credentialId></credentialId>
            <ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
            <source>2</source>
            <userName>onlinepayment</userName>
            <userType>2</userType>
            <usrNo>10643</usrNo>
         </sessionObject>
      </ws:saveVoucher>
   </soapenv:Body>
</soapenv:Envelope>';
	$xmlResponse =  $this->getXMLResponse($xmlData,2);
	$xml         =  $this->xml2array($xmlResponse);
	$dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:saveVoucherResponse']['return'];

return $dataArr;
}

function  saveVoucher($Bank_ledgerno, $ledgerActNo, $amount, $narration, $pgrefno, $paymentmode, $remarks)  {
$date    =      date("Y-m-d");

$xmlData = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.unifyv4.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:saveVoucher>
         <voucheri18nKey>unify.financial.voucher.type.receipt</voucheri18nKey>
         <tranxCurrency>50</tranxCurrency>
         <isRefundVoucher>false</isRefundVoucher>
         <Voucher>
            <!--Zero or more repetitions:-->
	    <remarks>'.$remarks.'</remarks>	
            <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$Bank_ledgerno.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$amount.'</amount>
               <orientation>0</orientation>
                <postedDate>'.$date.'</postedDate>
               <transactionDate>'.$date.'</transactionDate>
            </journal>
             <journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$ledgerActNo.'</ledgerActNo>
               <narration>'.$narration.'</narration>
               <amount>'.$amount.'</amount>
               <orientation>1</orientation>
                <postedDate>'.$date.'</postedDate>
               <transactionDate>'.$date.'</transactionDate>
             </journal>
            <voucherTransactionTypeNo>22</voucherTransactionTypeNo>
         </Voucher>
         <Objects>
           <VoucherInstrument>
               <instrumentNo></instrumentNo>
               <refData>'.$pgrefno.'</refData>
               <instrumentI18nKey>unify.finance.instrument.type.'.$paymentmode.'</instrumentI18nKey>
               <narration>'.$narration.'</narration>
               <bankNo>1</bankNo>
               <bankBranch>Delhi</bankBranch>
               <amount>'.$amount.'</amount>
               <instrumentDate>'.$date.'</instrumentDate>
               <instrumentTypeNo>1</instrumentTypeNo>
            </VoucherInstrument>
                <Journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$Bank_ledgerno.'</ledgerActNo>
               <!--Optional:-->
               <narration>'.$narration.'</narration>
               <!--Optional:-->
               <amount>'.$amount.'</amount>
               <!--Optional:-->
               <orientation>0</orientation>
            </Journal>
              <Journal>
               <journalNo></journalNo>
               <ledgerActNo>'.$ledgerActNo.'</ledgerActNo>
               <!--Optional:-->
               <narration>'.$narration.'</narration>
                <!--Optional:-->
               <amount>'.$amount.'</amount>
               <orientation>1</orientation>
            </Journal>
         </Objects>
         <sessionObject>
            <credentialId></credentialId>
            <ipAddress>'.$_SERVER['REMOTE_ADDR'].'</ipAddress>
            <source>2</source>
            <userName>onlinepayment</userName>
            <userType>2</userType>
            <usrNo>10643</usrNo>
         </sessionObject>
      </ws:saveVoucher>
   </soapenv:Body>
</soapenv:Envelope>';

 	$xmlResponse =  $this->getXMLResponse($xmlData,2);
        $xml         =  $this->xml2array($xmlResponse);
        $dataArr     =  $xml['soap:Envelope']['soap:Body']['ns2:saveVoucherResponse']['return'];

return $dataArr;
}



}//End of Class

function getAccountData($actID){
//$gstin_arr = array("");
$obj = new Unify();
//$actID = "90.1585";
$getOrg = $obj->getOrgByActID($actID);
$orgID  = $getOrg['orgNo'];
$ledgerActNo    =  $getOrg['ledgerAccountNo'];
if(is_numeric($orgID)){
$data = $obj->getCustomerAccountDetailList($orgID);
//print_r($data); exit;
    if(is_array($data)){
    $dataArrC =  $obj->getContactsByOrgId($orgID);    
#	print_r($dataArrC); exit;
    for($i=0; $i<count($dataArrC); $i++ ){
	if($dataArrC[$i]['contactTypeNo'] == 1){
                $Billto_contactId     = $dataArrC[$i]['contactNo'];
        }
    }
//	echo $Billto_contactId;exit;
	$dataArrCM        =  $obj->getContactCommMedium($Billto_contactId);
	for($k=0; $k<count($dataArrC); $k++ ){
        switch($dataArrCM[$k]['commTypeNo']){
           case 2:
                if(!empty($dataArrCM[$k]['ident'])){
                        $mobileno   =  $dataArrCM[$k]['ident'];
                }
                        break;
           case 4:
                if(!empty($dataArrCM[$k]['ident'])){
                        $email   =  $dataArrCM[$k]['ident'];
                }
                break;
        }
    }
	
    $actname 	 =  $data['accountName'];
    $outstandingAmount = $data['balance'];
	$dataArr_SD        	= $obj->getStatutoryData($ledgerActNo);
	if(is_array($dataArr_SD)){
    	 $CountArr       =  count($obj->array_column($dataArr_SD, 'statutoryTypeNo'));
	    if($CountArr == 0){
    		$dataArr_SD1[0]    = $dataArr_SD;
		}
	    else{
        	$dataArr_SD1      = $dataArr_SD;
	        }
                   foreach($dataArr_SD1 as $StatutoryData){
                      if($StatutoryData['statutoryTypeNo'] == 9){  $TAN = $StatutoryData['value'];}
                      if($StatutoryData['statutoryTypeNo'] == 12){  $TDS_slab = $StatutoryData['value'];}
                   }
     	   }else{
			$TAN 		= "";
			$TDS_slab 	= "";
		}

//	if(!empty($TAN) && empty($TDS_slab)){
	if(!empty($TAN)){
                $TDS_slab = "10";
        }

	$result = array("actid"=>$actID, "name"=>$actname, 'outstandingAmount'=>$outstandingAmount,  "email"=>$email, "mobileno"=>$mobileno, 'TAN'=>$TAN, 'tdsSlab'=>$TDS_slab);
	}else{
        	$result = array("error"=>"108","errormsg"=>"No such Account");
        }
//	print_r($result);exit;
  }else{
	$result = array("error"=>"108","errormsg"=>"No such Account");	
  }
return $result;
}

function addPaymentwithTDS($actID, $amount, $tds_amount, $paymentmode, $pgtxnno ,$remarks, $segment){
global $Bank_ledgerHome;
global $Bank_ledgerBus;
	$obj            = new Unify();
	        $getOrg = $obj->getOrgByActID($actID); // To get Org NO and Axis-2262 for Bus and Axis-9618 for Home
                $orgID  = $getOrg['orgNo'];
                $ledgerActNo    =  $getOrg['ledgerAccountNo'];
                $Bank_ledgerno  =  $getOrg['receiptLedgerAccountNo'];
/*	if($paymentmode == "ezetapcheque" && strtoupper($segment) == "HOME"){ // Axis-2262
                $getOrg = $obj->getLedgerByAccountId($actID);
                $ledgerActNo    =  $getOrg['ledgerActNo'];
                $coaNo          =  $getOrg['coaNo'];
                $Bank_ledgerno = $Bank_ledgerHome[$coaNo];
        }elseif($paymentmode != "ezetapcheque" && strtoupper($segment) == "BUSINESS"){ // Axis-9618 
                $getOrg = $obj->getLedgerByAccountId($actID);
                $ledgerActNo    =  $getOrg['ledgerActNo'];
                $coaNo          =  $getOrg['coaNo'];
                $Bank_ledgerno = $Bank_ledgerBus[$coaNo];
        }elseif($paymentmode == "techprocess" && strtoupper($segment) == "HOME"){ // Axis-2262
                $getOrg = $obj->getLedgerByAccountId($actID);
                $ledgerActNo    =  $getOrg['ledgerActNo'];
                $coaNo          =  $getOrg['coaNo'];
                $Bank_ledgerno = $Bank_ledgerHome[$coaNo];
        }                                                                
*/
//echo $Bank_ledgerno;
//exit;
	if(!is_numeric($ledgerActNo)){
		$result = array("status"=>'0',"errormsg"=>serialize($ledgerActNo));       
                return $result;
	}
		$narration      =       "Online Payment";
		$tdsdata        = $obj->getTDSLedgerForCurrentFicalYear($orgID);
		$tds_ledgerno	= $tdsdata['ledgerActNo'];
		$voucherID      =  $obj->saveVoucherwithtds($tds_ledgerno, $tds_amount, $Bank_ledgerno, $ledgerActNo, $amount, $narration, $pgtxnno, $paymentmode, $remarks);
		if(!is_numeric($voucherID)){
			$result = array("status"=>'0',"errormsg"=>serialize($voucherID));	
			return $result;
		}
		if($paymentmode == "ezetapcheque"){
			$posted = "posted";
                        $result = array("status"=>'1',"errormsg"=>$posted);
                }else{
		$posted		=  $obj->postVoucher($voucherID);
		if($posted == "posted")
			$result = array("status"=>'1',"errormsg"=>$posted);
		else
			$result = array("status"=>'0',"errormsg"=>serialize($posted));
		}
return $result;
}

function addPayment($actID, $amount, $paymentmode, $pgtxnno, $remarks, $segment){
global $Bank_ledgerHome;
global $Bank_ledgerBus;
	$obj            = new Unify();
/*	if($paymentmode == "ezetapcheque" && strtoupper($segment) == "HOME"){ // Axis-2262
		$getOrg = $obj->getLedgerByAccountId($actID);
        	$ledgerActNo    =  $getOrg['ledgerActNo'];
        	$coaNo          =  $getOrg['coaNo'];
        	$Bank_ledgerno = $Bank_ledgerHome[$coaNo];
	}elseif($paymentmode != "ezetapcheque" && strtoupper($segment) == "BUSINESS"){ // Axis-9618 
		$getOrg = $obj->getLedgerByAccountId($actID);
                $ledgerActNo    =  $getOrg['ledgerActNo'];
                $coaNo          =  $getOrg['coaNo'];
                $Bank_ledgerno = $Bank_ledgerBus[$coaNo];
	}elseif($paymentmode == "techprocess" && strtoupper($segment) == "HOME"){ // Axis-2262
                $getOrg = $obj->getLedgerByAccountId($actID);
                $ledgerActNo    =  $getOrg['ledgerActNo'];
                $coaNo          =  $getOrg['coaNo'];
                $Bank_ledgerno = $Bank_ledgerHome[$coaNo];
        }else{									// Axis-2262 for Bus and Axis-9618 for Home
*/
		$getOrg = $obj->getOrgByActID($actID);
        	$orgID  = $getOrg['orgNo'];
        	$ledgerActNo    =  $getOrg['ledgerAccountNo'];
        	$Bank_ledgerno  =  $getOrg['receiptLedgerAccountNo'];
//	}
//echo $Bank_ledgerno; 
//exit;

        if(!is_numeric($ledgerActNo)){
                $result = array("status"=>'0',"errormsg"=>serialize($ledgerActNo));
                return $result;
        }
		$narration      =       "Online Payment";
		$voucherID      =  $obj->saveVoucher($Bank_ledgerno, $ledgerActNo, $amount, $narration, $pgtxnno, $paymentmode, $remarks);
		if(!is_numeric($voucherID)){
                        $result = array("status"=>'0',"errormsg"=>serialize($voucherID));
                        return $result;
                }

		if($paymentmode == "ezetapcheque"){
			$posted = "posted";
			$result = array("status"=>'1',"errormsg"=>$posted);
		}else{
		$posted		=  $obj->postVoucher($voucherID);
		if($posted == "posted")
                        $result = array("status"=>'1',"errormsg"=>$posted);
                else
                        $result = array("status"=>'0',"errormsg"=>serialize($posted));
		}
return $result;
}

?>
