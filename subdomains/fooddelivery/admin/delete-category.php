<?php 
    include('../config/constants.php');
    // echo "Delete Page";
    if (isset($_GET['id'])AND isset($_GET['image_name']))
    {
        $id = $_GET['id'];
        $image_name = $_GET['image_name'];
        if ($image_name!="")
        {
            $path = '../images/category/'.$image_name;
            $remove = unlink($path);
            if ($remove==FALSE)
            {
                $_SESSION['remove']="<div class='error'>Failed to remove image</div>";
                header('location:'.SITEURL.'admin/manage-category.php');
                die();
            }
        }
        $sql="DELETE FROM tbl_category1 WHERE  id=$id";
        $res = mysqli_query($conn,$sql);
        if ($res==TRUE)
        {
            $_SESSION['delete']="<div class='success'>Category Deleted Successfully.</div>";
            header('location:'.SITEURL.'admin/manage-category.php');
        }
        else
        {
            $_SESSION['delete']="<div class='error'>Failed to Delete Category.</div>";
            header('location:'.SITEURL.'admin/manage-category.php');
        }
    }
    else
    {
        header('location:'.SITEURL.'admin/manage-category.php');
    }

?>