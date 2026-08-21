<?php
   require_once __DIR__ . '/db.php';
if (isset($_GET["id"])) {
    #if not exist then
        $id = $_GET['id'];
        
        $sql = "DELETE FROM employee WHERE id=$id"; 
        $result = $connection->query($sql);

        // if (!$result) {
        //     die("Invalid query : " . $connection->error);
        //     break;
        // }

}     
   header("location: ./index.php");
   exit;


?>