<?php 

function email_forgotpwd($data){
	    switch(auth_id){
              case 'generic':
		$mail_data = array();
		$mail_data['email_id'] = $data['email_id'];
		$uid		       = $data['uid'];
		$pwd		       = $data['pwd'];
                $mail_data['subject']  = "Spectra App - Username & Password";
                $mail_data['content'] = "Dear Customer, <br><br>
                Thanks for choosing Spectra.<br><br>
                We are pleased to introduce you to Spectra App where you can 
		manage your account - view bills, make payment, view usage and much more.<br>
                Please use the following log in detail.<br>                    
                Username - $uid<br>
                Password -  $pwd <br><br>
                Note: Do not forget to change your password on next log in.<br><br/>
                Team Spectra";
		$mail_data['bcc'] = "";
                $mail_data['from'] = "donotreply@spectra.co";
	      break;
	      
	      default:
		$mail_data = array();
                $mail_data['email_id'] = $data['email_id'];
                $uid                   = $data['uid'];
                $pwd                   = $data['pwd'];
                $mail_data['subject']  = "Spectra App - Username & Password";
                $mail_data['content'] = "Dear Customer, <br><br>
                Thanks for choosing Spectra.<br><br>
                We are pleased to introduce you to Spectra App where you can 
                manage your account - view bills, make payment, view usage and much more.<br>
                Please use the following log in detail.<br>                    
                Username - $uid<br>
                Password -  $pwd <br><br>
                Note: Do not forget to change your password on next log in.<br><br/>
                Team Spectra";
                $mail_data['bcc'] = "";
                $mail_data['from'] = "donotreply@spectra.co";
               break;

		}
	return $mail_data;
}


function updateEmail_sendotp($data){
	    switch(auth_id){
              case 'generic':
		$mail_data = array();
		$mail_data['email_id'] = $data['email_id'];
		$otp		       = $data['otp'];
                $mail_data['subject']  = "Spectra App - Update email id";
                $mail_data['content'] = 
		"Dear Customer, <br><br>
                Thanks for choosing Spectra.<br><br>
		Use ". $otp." as one time password (OTP) to update email id in to Spectra account.<br><br>
                Do not share this OTP with anyone for security reasons.<br><br>
                Team Spectra";
		$mail_data['bcc'] = "";
                $mail_data['from'] = "donotreply@spectra.co";
	      break;
	      
	      default:
		$mail_data = array();
                $mail_data['email_id'] = $data['email_id'];
                $otp                   = $data['otp'];
		$mail_data['subject']  = "Spectra App - Update email id";
                $mail_data['content'] =
                "Dear Customer, <br><br>
                Thanks for choosing Spectra.<br><br>
                Use ". $otp." as one time password (OTP) to update email id in to Spectra account.<br><br>
                Do not share this OTP with anyone for security reasons.<br><br>
                Team Spectra";

                $mail_data['bcc'] = "";
                $mail_data['from'] = "donotreply@spectra.co";
               break;

		}
	return $mail_data;
}

?>
