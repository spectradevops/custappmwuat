<?php class dbconnection
        {
                private $conn;
                public $pdo;

        public function __construct()
                       {
                        $dbname='scp_topup_plan_uat';
			#$dbname='referal';
                        $dbusername='root';
                        $dbpassword='';

                         try {
                          $this->pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $dbusername, $dbpassword,
                                        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
                         } catch (PDOException $e) {
                               echo 'Connection failed: ' . $e->getMessage();
                                // echo 'Connection failed: SQLSTATE[28000] [1045] Access denied';
                        }


                 }


 	public function checklastupdatedate($canid){
                 //echo $canid;
                        try{
//	echo $query = "select datetime,current_plan,current_subs_id,new_selected_plan,new_plan_id,new_plan_subs_id  from plan_changed where canid='$canid' order by id DESC limit 0,1";exit;

                        $sth = $this->pdo->prepare("select datetime,current_plan,current_subs_id,new_selected_plan,new_plan_id,new_plan_subs_id  from plan_changed where canid='$canid' order by id DESC limit 0,1");
                        $sth->execute();
                        $res = $sth->fetchAll(PDO::FETCH_ASSOC);
                        return $res;
                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }

                }


	public function checkPlabChange($chdt){
		$chdt=date('Y-m-d',strtotime($chdt));
		$d=explode('-',$chdt);
		$now=date('Y-m-d');
		$n=explode('-',$now);
		if($n[0]>$d[0]){
		$y=1;
		}else{
		$y=0;
		}
		if($n[1]>$d[1]){
		$m=1;
		}else{
		$m=0;
		}
		if($n[2]>$d[2]){
		$dy=1;
		}else{
		$dy=0;
		}
		if($y==0 && $m==0 && $dy==0){
		$res=false;
		}
		if($y==0 && $m==0 && $dy==1){
		$res=false;
		}
		if($y==0 && $m==1 && $dy==1){
		$res=true;
		}
		if($y==1 && $m==1 && $dy==1){
		$res=true;
		}
		return $res;
	}


	public function saveplan_changed($data){
		try{ 
           		$sql = "INSERT INTO plan_changed (datetime,
                                        canid,  
                                        current_plan,
                                        current_subs_id,
                                        new_selected_plan,
                                        new_plan_id,
                                        sr_number,
                                        status,
                                        source,
                                        source_ip) 
                                        VALUES (
                                        '".$data['datetime']."',
                                        '".$data['actid']."',
                                        '".$data['cplan']."',
                                        '".$data['csubsid']."',
                                        '".$data['newplanname']."',
                                        '".$data['newplanid']."',
                                        '".$data['sr_number']."',
                                        '".$data['status']."',
                                        '".$data['source']."',
                                        '".$data['sourceip']."'
                                        )";
				$this->pdo->prepare($sql)->execute();
                        	return  $this->pdo->lastInsertId();
				}
                        	catch (PDOException $e) {
                                return $e->getMessage();
                        	}
        }






          public function getplanCandid($canid) {
			try{
				$efdate=date('Y-m-d');
			//	echo "select * from plan_canid where effective_date<='$efdate' AND canid ='$canid'";
	#echo "select * from plan_canid where effective_date <= '$efdate' AND canid ='$canid' order by id desc limit 1 "; exit;
                                $sth = $this->pdo->prepare("select * from plan_canid where effective_date <= '$efdate' AND canid ='$canid' order by id desc limit 1 ");
				$sth->execute();
                        	$res = $sth->fetchAll(PDO::FETCH_ASSOC);
				$row=$res[0];
				$plan1 = !empty($row['plan1'])? $row['plan1']:'';
				$plan2 = !empty($row['plan2'])? $row['plan2']:'';
				$plan3 = !empty($row['plan3'])? $row['plan3']:'';

				if(!empty($plan1)){
					$plan_arr[] =	$plan1;
				}
				if(!empty($plan2)){
                                	$plan_arr[] =   $plan2;
                                }
				if(!empty($plan3)){
                                	$plan_arr[] =   $plan3;
                                }



			#	$plan_arr = explode(",", $plans);
#				print_r($plan_arr); exit;
			$response = array();
			foreach($plan_arr as $val){
				$plan_id = trim($val);
				if($plan_id != 'Best Plan' && $plan_id != ''){
	 			$sql ="select * from plan_mstr_web where rateplan_id in ($val)"; 
				$sth1 = $this->pdo->prepare($sql);
                                $sth1->execute();
                                $res1 = $sth1->fetchAll(PDO::FETCH_ASSOC);
				#print_r($res1[0]); exit;
				if(!empty($res1[0])){
					array_push($response, $res1[0]);
				}
				}
			}
                 	return $response;

                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }

        }


          public function getTopupCandid($canid) {
                        try{
                                $efdate=date("Y-m-d");
				$query = "select * from topup_canid where effective_date <= '$efdate' AND canid ='$canid' order by id desc limit 1";

                                $sth = $this->pdo->prepare($query);
                                $sth->execute();
                                $res = $sth->fetchAll(PDO::FETCH_ASSOC);
#print_r($res);exit;
				$row=$res[0];

				$plan1 = !empty($row['topup1'])? $row['topup1']:'';
                                $plan2 = !empty($row['topup2'])? $row['topup2']:'';
                                $plan3 = !empty($row['topup3'])? $row['topup3']:'';

                                if(!empty($plan1)){
                                        $plan_arr[] =   $plan1;
                                }
                                if(!empty($plan2)){
                                        $plan_arr[] =   $plan2;
                                }
                                if(!empty($plan3)){
                                        $plan_arr[] =   $plan3;
                                }

#	print_r($plan_arr); exit;

				$response = array();
                        foreach($plan_arr as $val){
                                $plan_id = trim($val);
                                if($plan_id != 'Best Plan' && $plan_id != ''){
                                $sql ="select  topup_id, topup_name, description, data_volume, price, type from topup_mstr_web  where topup_id = '$plan_id' and status='1'";
                                $sth1 = $this->pdo->prepare($sql);
                                $sth1->execute();
                                $res1 = $sth1->fetchAll(PDO::FETCH_ASSOC);
                                #print_r($res1[0]); exit;
                                	if(!empty($res1[0])){
                                        	array_push($response, $res1[0]);
                                	}
                                }
                        }

                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }
		return $response;
        }

 public function getBestOffer($plans) {

	 $sql ="select * from plan_mstr_web where rateplan_id in ('".$plans."')";             
	 $sth1 = $this->pdo->prepare($sql);
                                $sth1->execute();
                                $row = $sth1->fetchAll(PDO::FETCH_ASSOC);

                 return $row;

        }
public function getOffers($id){

		try{
		   $efdate=date('Y-m-d');

		   $sql = "select plan1,plan2,plan3,plan4,plan5,plan6,plan7,plan8,plan9,plan10 from best_offers  where effective_date <='$efdate' and base_plan='$id' and status=1";
//exit;	
		   $sth1 = $this->pdo->prepare($sql);
                                $sth1->execute();
                                $res = $sth1->fetchAll(PDO::FETCH_ASSOC);
                               // print_r($res); exit;
				$row=$res[0];
				$plans="'".$row['plan1']."'".","."'".$row['plan2']."'".","."'".$row['plan3']."'".","."'".$row['plan4']."'".","."'".$row['plan5']."'".","."'".$row['plan6']."'".","."'".$row['plan7']."'".","."'".$row['plan8']."'".","."'".$row['plan9']."'".","."'".$row['plan10']."'";
				$query = "select * from plan_mstr_web  where rateplan_id IN ($plans)";
				$sth = $this->pdo->prepare($query);
                                $sth->execute();
                                $res1 = $sth->fetchAll(PDO::FETCH_ASSOC);
 				return $res1;
                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }

        }



public function getTopups($id){

                try{
                   $efdate=date('d-m-Y');

                   $sql = "select topup1,topup2,topup3 from topup_basePlan  where effective_date<='$efdate' AND base_plan='$id'";
                   $sth1 = $this->pdo->prepare($sql);
                                $sth1->execute();
                                $res = $sth1->fetchAll(PDO::FETCH_ASSOC);
                                $row=$res[0];
                                $topups="'".$row['topup1']."'".","."'".$row['topup2']."'".","."'".$row['topup3']."'";
                                $query = "select  topup_id, topup_name, description, data_volume, price, type from topup_mstr_web  where name IN ($topups)";
                                $sth = $this->pdo->prepare($query);
                                $sth->execute();
                                $res1 = $sth->fetchAll(PDO::FETCH_ASSOC);
                                return $res1;
                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }

        }







	public function get_client_ip() {
    		$ipaddress = '';
    		if (getenv('HTTP_CLIENT_IP'))
        	$ipaddress = getenv('HTTP_CLIENT_IP');
    		else if(getenv('HTTP_X_FORWARDED_FOR'))
        	$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    		else if(getenv('HTTP_X_FORWARDED'))
        	$ipaddress = getenv('HTTP_X_FORWARDED');
    		else if(getenv('HTTP_FORWARDED_FOR'))
        	$ipaddress = getenv('HTTP_FORWARDED_FOR');
    		else if(getenv('HTTP_FORWARDED'))
       		$ipaddress = getenv('HTTP_FORWARDED');
    		else if(getenv('REMOTE_ADDR'))
        	$ipaddress = getenv('REMOTE_ADDR');
    		else
        	$ipaddress = 'UNKNOWN';
    		return $ipaddress;
	}





}
