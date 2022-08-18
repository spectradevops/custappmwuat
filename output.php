<?php
require("config.php");

$CANID = "9019579";
$data1 = getAccountData4($CANID);
print_r($data1);
function getAccountData4($CANID){
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
