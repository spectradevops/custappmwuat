<?php
class Standard_Instruction_db
        {
                private $conn;
                public $pdo;
                public function __construct()
                       {
//                      $host='mysql:host=localhost;dbname=epay_db';
  //                    $user='epay_db';

                        try {
                                $this->pdo = new PDO('mysql:host=localhost;port=3306;dbname=epay_db;charset=utf8mb4','epay_db','Epay$987dB');
//                              $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        }
                        catch (PDOException $e) {
                                echo 'Database connection has failed. Contact system administrator to resolve this issue!<br>';
                                $e->getMessage();
                                die();

                        }
                        }
}

$db= new Standard_Instruction_db();
//global $db;
try {
        $stmt = $db->pdo->prepare("select * from payment_api where source='cust_app' and successTxn='SUCCESS' and unify_status is NULL");
#	$stmt = $db->pdo->prepare("select * from payment_api where source='cust_app' and successTxn='SUCCESS' and log_id='25183' ");
        $stmt->execute();
        $row =$stmt->fetchAll(PDO::FETCH_ASSOC);
#	print_r($row); exit;
	for($i = 0; $i < count($row); $i++){
		$id 	= $row[$i]['log_id'];
		$actID 		= $row[$i]['can_id'];
		$paid_amount 	= $row[$i]['paid_amount'];
		$ePGTxnID 	= $row[$i]['pgTxnNo'];
                $paymentmode ="razorpaypaymentgateway";
		$remarks = 'Spectra App: '.$ePGTxnID;
		if(substr($actID,0,1) == "9"){
			$segment = "Business";
		}else{
			$segment = "Home";
		}
		require_once("/var/www/html/ftthpay/mwapis_uat/UnifyApi.php");
		$result = addPayment($actID, $paid_amount, $paymentmode, $ePGTxnID, $remarks, $segment);
#		print_r($result);
                if($result['status'] == "1"){
                        $stmt = $db->pdo->prepare("update payment_api set unify_status ='posted'  where log_id = '$id'");
                        $stmt->execute();
                        $affected_rows = $stmt->rowCount();
                }else{
			$stmt = $db->pdo->prepare("update payment_api set unify_status ='error'  where log_id = '$id'");
                        $stmt->execute();
                        $affected_rows = $stmt->rowCount();
                }

	}
   } catch(PDOException $ex) {
   echo         $errormsg = $ex->getMessage();
   }


?>
