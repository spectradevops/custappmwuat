<?php
require("functions.php");
$unify_title = array(
        'onthefly' => 'Planned Maintenance',
        'invoicemessagejob' => 'Invoice generation',
        'servicebar' => 'Service Bar',
        'dunning.reminder.payment' => 'Payment Reminder 1',
        'dunning.notice.disconnection' => 'Post DD Notice 1',
        'fupalert' => '100% Data Consumed'
);

/*
$sql = "select * from device_token_master where can_id = '".$canid."'";
$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
*/
#$log_id = '5112245';
$date = date("Y-m-d 00:00:00");
$output = searchSMSLog($date);
#print_r($output); exit;
foreach($output as $var){
	if(!empty($var['content'])){
	$content = explode("**ID**", $var['content']);
	$title = $unify_title[$var['eventId']];
	$short_description = ($content[1])?$content[1]:$content[0];
	$detailed_description = $short_description;
#	$canids[] = $var['serviceGroupId'];
#	$short_description = "Dear Subscriber, your services have been barred due to non-payment. Kindly make payment to get your services resumed.";
#	$detailed_description = $short_description;
	$canids[] = '192922';
	$type = $unify_title[$var['eventId']];
	$result = pushNotification($title, $short_description, $detailed_description, $canids, $type);
	print_r($result);
	}
}
?>
