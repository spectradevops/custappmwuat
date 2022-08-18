<?
require("functions.php");

        $json = file_get_contents('php://input');
        $timestamp = date("Y-m-d H:i:s");
        $LOG = $timestamp." ".$json;
        file_put_contents("logs/request.log","\n".$LOG, FILE_APPEND | LOCK_EX);
        $obj = json_decode($json);
#       print_r($obj);exit;
        $action =trim($obj->Action);
        $key    = trim($obj->Authkey);
//echo "\n";
        $authkey = 'AdgT68HnjkehEqlkd4';
        if($key == $authkey){
        switch($action){
/*2.1*/         case 'Payment':
			$session = $obj->session?$obj->session:'';
		break;
		 default:
                        $data = array("status"=>"failure","response"=>array(),"message"=>"Action not define.");
                }
	}
	}else{
                        $data = array("status"=>"failure","response"=>array(),"message"=>"Authentication failed.");
        }

                        $timestamp = date("Y-m-d H:i:s");
                        $LOG = $timestamp." ".json_encode($data);
                        file_put_contents("logs/request.log","\n".$LOG, FILE_APPEND | LOCK_EX);
                        header('Content-type: application/json');
                        echo json_encode($data);



?>
