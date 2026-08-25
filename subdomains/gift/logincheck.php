<?php
$unerror=$passerror="";
if(isset($_POST['submit']))
{
$username=$_POST['username'];
$password=$_POST['pwd'];
//connect to the server and select the database
$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `giftstore` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('giftstore');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
//query
$usercheck="SELECT * FROM signup where username= '$username'";
$result=mysqli_query($db,$usercheck);

if(mysqli_num_rows($result) == 0)
{ 
	$unerror="*User Does Not Exist";
}
else 
{
	$usercheck="SELECT * FROM signup where username= '$username' AND password='$password'";
	$result=mysqli_query($db,$usercheck);
	if(mysqli_num_rows($result) == 0)
	{
  	$passerror="*Invalid Password";
    }
else{
    	session_start();
		$_SESSION['userid']=$_POST['username'];
		if(empty($_SESSION['shopping_cart']))
		{
			$_SESSION['user_cart']=array();
			
		}
		
	header("Location:index.php");
    }
}
}
?>