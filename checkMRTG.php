<?php

	function checkUtilisation($data){
		$canId = $data['canID'];
		if($canId == '9019453'){
                        $problemDetails = array(
                                                "assigned_bandwidth"=> '50 mbps',
                                                "util_percentage"=> 84.67,
                                                "util_bandwidth"=> '40 mbps',
                                             );
			$response['response'] = $problemDetails;
			return $response;
                  }
	
		
		$data_string = json_encode($data);
		$fp       = fopen("logs/check_mrtg.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".mrtg_url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => mrtg_url."/checkMRTG.php",
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/check_mrtg.log","a+");
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




?>
