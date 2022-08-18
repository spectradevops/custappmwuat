<?php


 function sendSMS($mobile,$msg)
        {
                        $to = "91".$mobile;
        //              $msg = "Welcome to Spectra Wi-Fi Zone. Your login credentials are:- \nUsername: ".$netid_value."\nPassword: ".$netid_password;
                        $msg = urlencode($msg);
#                        $strURL = "http://127.0.0.1:13013/cgi-bin/sendsms?username=NagiosLocal&password=LocalNagios&from=SPCTNT&to=$to&text=$msg";
			$strURL="http://smsgw.spectra.co/sendsms.php?uid=wifi_scp&passcode=sP@E-8739tRa&to=$to&text=$msg";
                        $ch = curl_init($strURL);
                        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                        $output = curl_exec($ch);
                        curl_close($ch);
                        return "SMS sent to registered mobile number";

        }


?>
