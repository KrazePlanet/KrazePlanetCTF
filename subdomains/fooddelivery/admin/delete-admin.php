<?php 
    include('../config/constants.php');
    $id = $_GET['id'];
// 2.create sql query
$sql = "DELETE FROM tbl_admin1 WHERE id=$id";
// executing
$res = mysqli_query($conn,$sql);
if ($res == TRUE)
{
    // echo 'Admin Deleted';
    $_SESSION['delete'] = "<div class='success'>Admin Deleted Successfully.</div>";
    header('location:'.SITEURL.'admin/manage-admin.php');
}
else {
    // echo 'Failed to delete admin';
    $_SESSION['delete'] = "<div class='error'>Failed to delete Admin.</div>";
    header('location:'.SITEURL.'admin/manage-admin.php');
}
?>