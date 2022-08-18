<?php

	function dynamicNotification($data){

		$data_string = json_encode($data);
		$fp       = fopen("logs/affle_api.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".Affle_URL."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => Affle_URL."dynamicnotificationsent",
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
			"X-Source: SPECTRA",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/affle_api.log","a+");
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
