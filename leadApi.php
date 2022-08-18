<?php
define("lead_url", "https://wdev.spectra.co/spectra");

	//function UpdateLead($client_id,$secret_key,$first_name,$last_name,$lampleadid,$productcode,$quantity,$non_recommend_productcode,$non_recommend_quantity){
		function UpdateLead($data){

		$data_string = json_encode($data);
		$fp       = fopen("logs/updatelead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".lead_url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => lead_url."/update_lead",
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/updatelead.log","a+");
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

	function CreateLead($data){

		$data_string = json_encode($data);
		$fp       = fopen("logs/createlead.log","a+");
                $log_txt = "\n".date("Y-m-d H:i:s")."\t".lead_url."\t".$data_string;
                fwrite($fp,$log_txt);

		$curl = curl_init();
  		curl_setopt_array($curl, array(
  		CURLOPT_URL => lead_url."/create_lead",
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => "",
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 30,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => "POST",
  		CURLOPT_SSL_VERIFYPEER => "false",
  		CURLOPT_POSTFIELDS => $data_string,
  		CURLOPT_HTTPHEADER => array(
    			"username: User",
    			"password: User@123",
    			"authorization: Basic VXNlcjpVc2VyQDEyMw==",
    			"cache-control: no-cache",
    			"content-type: application/json"
  		),
		));
		$output = curl_exec($curl);
		 $fp       = fopen("logs/createlead.log","a+");
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
