<?php
//require("/var/www/html/wifiportal_new/config_inc.php");
require("/var/www/html/custappmw/functions.php");
//require("get_billcycle_date.php");
$canid=225101;
$fromdate='2022-01-15';
$todate='2022-03-15';
$data1=getSessionhistoryByCanId($canid,$fromdate,$todate);
$total_bytes=0;
        foreach($data1 as $usage){
            // $start = date("Y-m-d", strtotime($usage['start']));
            //         $end   = date("Y-m-d", strtotime($usage['lastupd']));
            //         if($start == $date){
                            //  $in_bytes += $usage['bytesin'];
                            // $out_bytes += $usage['bytesout'];
                            $total_bytes += $usage['total'];
               
    
        }// End of foreach
    
    $ttl=$total_bytes/(1024*1024*1024);
echo $total_bytes."\n";
echo $ttl;
exit;
    
    
?>