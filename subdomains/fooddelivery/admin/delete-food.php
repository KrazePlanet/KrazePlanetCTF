<?php include('../config/constants.php'); 
if (isset($_GET['id'])AND isset($_GET['image_name']))
{
    $id = $_GET['id'];
    $image_name = $_GET['image_name'];
    if ($image_name!="")
    {
        $path = '../images/food/'.$image_name;
        $remove = unlink($path);
        if ($remove==FALSE)
        {
            $_SESSION['upload']="<div class='error'>Failed to remove image</div>";
            header('location:'.SITEURL.'admin/manage-food.php');
            die();
        }
    }
    $sql="DELETE FROM tbl_food1 WHERE  id=$id";
    $res = mysqli_query($conn,$sql);
    if ($res==TRUE)
    {
        $_SESSION['delete']="<div class='success'>Deleted Food Successfully.</div>";
        header('location:'.SITEURL.'admin/manage-food.php');
    }
    else
    {
        $_SESSION['delete']="<div class='error'>Failed to delete Food.</div>";
        header('location:'.SITEURL.'admin/manage-food.php');
    }
}
else
{
    $_SESSION['unauthorize']="<div class='error'>Unauthorized Access.</div>";
    header('location:'.SITEURL.'admin/manage-food.php');
}

?>