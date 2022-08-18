<?php
require('razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

function createOrder($param){
			 #$keyId = 'rzp_live_OXG4iUDctFasFu'; // For Live
                        $keyId='rzp_test_zrullBk9rAHd75'; // For UAT
                        #$keySecret = 'bIyXd99g2gCTWhrAd8CdeV6O'; // For Live
                        $keySecret='sOf42jZd2aSZrxLvHF1ENyFe'; // For UAT
                        $displayCurrency = 'INR';
                        $UserID_DBsave = "amit.dubey@spectranet.in"; // Only to store in DB
                        $source_ip = $_SERVER['REMOTE_ADDR'];
                        $return_url = "return_url";
                        $segment = "APP";
                        $source = "cust_app";

 			$api = new Api($keyId, $keySecret);
                        $db  = epay_db();
                        $customerid = createcustomer($keyId,$keySecret,$param['name'],$param['emailid'],$param['mobileno']);
                        $orderData = array('receipt' => $param['can_id'],
                        'amount'          => $param['amount'] * 100, //  rupees in paise
                        'currency'        => 'INR',
                        'payment_capture' => 1 // auto capture
                        );

                        $razorpayOrder = $api->order->create($orderData);
return $razorpayOrder;
}
?>
