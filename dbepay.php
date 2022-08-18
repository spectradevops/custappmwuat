<?php class dbcon
        {
                private $conn;
                public $pdo;

        public function __construct()
                       {
                        $dbname='epay_db';
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


 	public function getsistatus($canid){
                        try{
                        $sth = $this->pdo->prepare("select * from razorpay_si where si_status='1' AND  canid='$canid' order by id DESC limit 0,1");
                        $sth->execute();
                        $res = $sth->fetchAll(PDO::FETCH_ASSOC);
                        return $res;
                        }
                        catch (PDOException $e) {
                               return $e->getMessage();
                        }

                }

}
