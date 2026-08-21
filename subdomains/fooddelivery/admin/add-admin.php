<?php include('partials/menu.php');?>
<div class="main-content"?>
    <div class="wrapper">
<h1> Add Admin </h1></br></br>
<?php 
if (isset($_SESSION['add']))
{
    echo $_SESSION['add'];
    unset($_SESSION['add']);
}

?>
<form action="" method="POST">
    <table class="tbl-30">
        <tr>
            <td>Full Name:</td>
            <td>
                <input type="text" name="full_name" placeholder="Enter your name">
            </td>
        </tr>

        <tr>
            <td>Username:</td>
            <td>
            <input type="text" name="username" placeholder="Your Username">
</td>
</tr>
        <tr>
            <td>Password:</td>
            <td>
                <input type="password" name="password" placeholder="Your password">
            </td>
        </tr>
        <tr>
            <td colspan="2">
        <input type="submit" name="submit" value="Add Admin" class="btn-secondary">


</td>
</tr>

</table>
</form>
</div>
</div>

<br><br>


<?php include('partials/footer.php');?>
<?php 
//*Process the value for form and add to database*//
// check whether submit is clicked or not
if (isset($_POST['submit']))
{
    //button clicked
    //echo "Button Clicked";
    //get the data from form
    $full_name=$_POST['full_name'];
    $username=$_POST['username'];
    $password=md5($_POST["password"]);

    //sql query to get into database
    $sql="INSERT into tbl_admin1 SET 
    full_name='$full_name',
    username='$username',
    password='$password'
    ";
    
    //3. execute and save data in database
   $res = mysqli_query($conn,$sql) or die(mysqli_error());

    //4. check data entered or not
    if ($res == TRUE)
    {
        // data entered
        // echo 'Data entered';
        $_SESSION['add'] = "<div class='success'>Admin Added Successfully.</div>";
        // redirect pg
        header ('location:'.SITEURL.'admin/manage-admin.php');
    }    
    else
    {
        // failed to enter
        $_SESSION['add'] = "<div class='error'>Failed to add admin.</div>";
        // redirect pg
        header ('location:'.SITEURL.'admin/manage-admin.php');
    }
}

?>