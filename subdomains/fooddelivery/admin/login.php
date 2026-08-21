<?php include('../config/constants.php');?>
<html>
<head>
    <title>Login - Food Ordering System</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="login">
        <h1 class="text-center">Login</h1>
        <br><br>
        <?php 
        if (isset($_SESSION['login']))
        {
            echo $_SESSION['login'];
            unset($_SESSION['login']);
        }
        if (isset($_SESSION['no-login-message']))
        {
            echo $_SESSION['no-login-message'];
            unset($_SESSION['no-login-message']);
        }
        ?>
        <form action="" method="POST" class="text-center">
        Username:<br>
        <input type="text" name="username" placeholder="Enter Username">
        <br><br>
        Password:<br>
        <input type="password" name="password" placeholder="Enter Password">
        <br><br>
        <input type='submit' name='submit' value='Login' class='btn-primary'>
        <br><br>
        </form>



        <p class="text-center">Created by <a href="">Fries</a></p>
    </div>
</body>

<?php 
if (isset($_POST['submit']))
{
    //button clicked
    //echo "Button Clicked";
    //get the data from form

    // $username=$_POST['username'];
    // $password=md5($_POST["password"]);

    $username=mysqli_real_escape_string($conn,$_POST['username']);
    $raw_password = md5($_POST['password']);
    $password=mysqli_real_escape_string($conn,$raw_password);

    //sql query to get into database
    $sql="SELECT * FROM tbl_admin1 WHERE 
    username='$username' AND
    password='$password'
    ";
    
    //execute and save data in database
    $res= mysqli_query($conn,$sql);
    $count=mysqli_num_rows($res);
    //check if inserted or not and display msg
    if($count==1)
    {
        $_SESSION['login']="<div class='success'>Login Successfull</div>";
        $_SESSION['user']=$username;
        header("location:".SITEURL.'admin/');

    }
    else{
        //failed
        $_SESSION['login']="<div class='error text-center'>Username or Password didnot match.</div>";
        header("location:".SITEURL.'admin/login.php');
    }
}

?>





</html>