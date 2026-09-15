<?php
$unerror=$passerror="";
if(isset($_POST['submit']))
{
$username=$_POST['username'];
$password=$_POST['pwd'];
//connect to the server and select the database
$hosts   = [getenv('DB_HOST') ?: 'localhost'];
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, $db_user, $db_pass);
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