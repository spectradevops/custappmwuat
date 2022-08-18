<?php

class Mailer 
{		

	public function sentMail($data=array()){
		include_once('/var/www/html/PHPMailer/PHPMailerAutoload.php');
		//echo $_SERVER['DOCUMENT_ROOT']; exit;
		if(!empty($data)){
			$from = $data['from'];
			$email_id = $data['email_id'];
			$bcc = $data['bcc'];
			$subject = $data['subject'];
			$content = $data['content'];
			$mail = new PHPMailer;
                    $mail->isSMTP();
                    $mail->SMTPDebug = 0;
                    //$mail->Host = "smtp.mandrillapp.com";
                    $mail->Host = "in-v3.mailjet.com";
		    $mail->Port = 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = "f31c2a88bac02a221e75448de31a02f0";
                    $mail->Password = "3c2b4f6555815670a04d09e5c37b10c7";
                    $mail->setFrom($from, 'Spectra');
                    $mail->addAddress($email_id);
                    $mail->addAddress($bcc);
                    $mail->Subject = $subject;
                    $mail->msgHTML($content);
                    if (!$mail->send()) {
                         $status = "failure";
		        } else {			
                 	 $status = "success";
			}
                  $fp          = fopen("logs/mail.txt", "a+");
                  $mail_text   = "\n" . date("Y-m-d H:i:s") . "\t" . @$_SERVER['REMOTE_ADDR'] . "\t" . $email_id . "\t" . $status;
                  fwrite($fp, $mail_text);
                  fclose($fp);
                  return $status;
	       	}	
		
     
    } 
    
	
}

