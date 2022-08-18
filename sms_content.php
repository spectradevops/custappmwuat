<?php 

	 function SMS_forgotpwd($data){
		switch(auth_id){
		 	case 'generic':
			#$msg   = "Your Spectra Selfcare portal login details are: username -". $data['uid']." & password - ".$data['pwd'].".\n Team Spectra";
			 $msg   = "Your Spectra App login details have been sent on your registered Email id.\n Team Spectra";
			break;
			default:
			$msg   = "Your Spectra App login details are: username -". $data['uid']." & password - ".$data['pwd'].".\n Team Spectra";
	
		}
		return $msg;
	}


	function SMS_sendotp($otp){
                switch(auth_id){
		   case 'generic':
                        $msg   = "1107159911323757233**ID**Use ". $otp." as one time password (OTP) to login in to Spectra Account.";
                        $msg  .= " Do not share this OTP with anyone for security reasons.";
                        break;
                        default:
                        $msg   = "1107159911323757233**ID**Use ". $otp." as one time password (OTP) to login in to Spectra Account.";
                        $msg  .=" Do not share this OTP with anyone for security reasons.";

                }
                return $msg;
        }

	function updateMobile_sendotp($otp){
                switch(auth_id){
                        case 'generic':
                        $msg   = "Use ". $otp." as one time password (OTP) to update mobile number in to Spectra account.";
                        $msg  .= " Do not share this OTP with anyone for security reasons.";
                        break;

                        default:
                        $msg   = "Use ". $otp." as one time password (OTP) to update mobile number in to Spectra account.";
                        $msg  .=" Do not share this OTP with anyone for security reasons.";

                }
                return $msg;
        }

	function SMS_sendotpLinkAC($otp){
		switch(auth_id){
                        case 'generic':
                        $msg   = "Use ". $otp." as one time password (OTP) to link your account in to Spectra account.";
                        $msg  .= " Do not share this OTP with anyone for security reasons.";
                        break;

                        default:
                        $msg   = "Use ". $otp." as one time password (OTP) to link your account in to Spectra account.";
                        $msg  .=" Do not share this OTP with anyone for security reasons.";

                }
                return $msg;
	}

?>
