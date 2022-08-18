<?php
require_once("config.php");
$u = "customer_apps";
$p = "Cust123Spectra";
$salt = "Spectra@987";
$k = base64_encode($u) . '_' . md5($u);
$k = md5($k.$salt); 
//exit;
$s = "ACTIVE";
$db = dbConnection();
#var_dump($db);
try {
	
	$stmt = $db->prepare("select * from key_master where key = ?");
	$stmt->execute(array($u));
	$row =$stmt->fetchAll(PDO::FETCH_ASSOC);
	if(isset($row[0]['source'])){
	if($row[0]['source'] > 0){
		echo $result = "Channel ".$u." Already Exist!!!\r\n";
		exit;
	}
	}
	 $query = "insert into key_master (authkey, source, status, crm_case_source, crm_case_source_id, create_date) values ('$k','$u','$s','Cust_app','T_115',now())";
        $stmt1 = $db->prepare($query);
	if(!$stmt1){ print_r($db->errorInfo()); }
        $stmt1->execute();
	$id = $db->lastInsertId(); 
	if($id > 0){
		echo "Channel ".$u." created successfully. your key is ".$k;
	}
} catch(PDOException $ex) {
            $result = $ex->getMessage();
	
   }

?>
