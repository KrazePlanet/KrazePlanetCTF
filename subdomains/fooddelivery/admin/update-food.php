<?php include('partials/menu.php'); ?>
<?php 
    if (isset($_GET['id']))
        {
                // echo 'yes';
                $id = $_GET['id'];
                $sql2= "SELECT * FROM tbl_food1 where id=$id";
                $res2 = mysqli_query($conn,$sql2);
                $row = mysqli_fetch_assoc($res2);
                $title = $row['title'];
                $description = $row['description'];
                $price = $row['price'];
                $current_image = $row['image_name'];
                $current_category = $row['category_id'];
                $featured = $row['featured'];
                $active = $row['active'];
                
            }
            else
            {
                header('location:'.SITEURL.'admin/manage-food.php');
            }
?>
<div class="main-content">
    <div class="wrapper">
        <h1>Update Food</h1>
        <br><br>
        <form action="update-food.php"  method="POST" enctype="multipart/form-data">
            <table class="tbl-30">
                <tr>
                    <td>Title: </td>
                    <td>
                        <input type="text" name='title' value='<?php echo $title; ?>' >
                    </td>
                </tr>
                <tr>
                    <td>Description: </td>
                    <td>
                    <textarea name="description" cols="30" rows="5" ><?php echo $description; ?></textarea>
                    </td>    
                </tr>
                <tr>
                    <td>Price: </td>
                    <td>
                        <input type="number" name='price' value='<?php echo $price; ?>' >
                    </td>
                </tr>

                <tr>
                    <td>Current_Image: </td>
                    <td>
                        <?php 
                            if($current_image=="")
                            {
                                echo "<div class='error'>Image not Added.</div>";
                            }
                            else
                            {
                                ?>
                                <img src="<?php echo SITEURL ?>images/food/<?php echo $current_image;?>" width='150px' >
                                <?php 
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Select_New_Image: </td>
                    <td>
                        <input type="file" name='image'>
                    </td>
                </tr>
                <tr>
                    <td>Category: </td>
                    <td>
                        <select name="category">
                            <?php
                                $sql = "SELECT * FROM tbl_category1 where active='Yes' ";
                                $res = mysqli_query($conn,$sql);
                                $count = mysqli_num_rows($res);
                                if ($count>0)
                                {
                                    while($row=mysqli_fetch_assoc($res))
                                    {
                                        $category_title = $row['title'];
                                        $category_id = $row['id'];
                                        // echo "<option value='$category_id'>$category_title</option>";
                                        ?>
                                        <option <?php if($current_category==$category_id){echo "Selected"; } ?> value="<?php echo $category_id; ?>"><?php echo $category_title; ?></option>
                                        <?php
                                    }
                                }
                                else
                                {
                                    echo "<option value='0'>Category Not Available.</option>";
                                }
                            ?>
                        </select>   
                    </td>
                </tr>

                <tr>
                    <td>Featured: </td>
                    <td>
                        <input <?php if($featured=="Yes"){echo "checked";}?> type="radio" name="featured" value="Yes"> Yes
                        <input <?php if($featured=="No"){echo "checked";}?> type="radio" name="featured" value="No"> No
                    </td>
                </tr>

                <tr>
                    <td>Active: </td>
                    <td>
                        <input <?php if($active=="Yes"){echo "checked";}?> type="radio" name="active" value="Yes"> Yes
                        <input <?php if($active=="No"){echo "checked";}?> type="radio" name="active" value="No"> No
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                    <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="submit" name="submit" value="Update Food" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form> 
        <?php
            if (isset($_POST['submit']))
            {
                // echo 'clicked';
                $id = $_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $current_image = $_POST['current_image'];
                $category = $_POST['category'];
                $featured = $_POST['featured'];
                $active = $_POST['active'];
                if (isset($_FILES['image']['name']))
                {
                    $image_name = $_FILES['image']['name'];
                    if ($image_name!="")
                    {
                        $ext=end(explode('.',$image_name));
                        $image_name="Food_Category_".rand(000,999).'.'.$ext;
                        $source_path=$_FILES['image']['tmp_name'];
                        $destination_path="../images/food/".$image_name;
                        $upload=move_uploaded_file($source_path,$destination_path);
                        if($upload==false)
                        {
                        //failed
                            $_SESSION['upload']="<div class='error'>Failed to Upload Image</div>";
                            header("location:".SITEURL.'admin/manage-food.php');
                            // die();
                        }
                        if ($current_image!="")
                        {
                            $remove_path = "../images/food/".$current_image;
                            $remove = unlink($remove_path);
                            if ($remove==FALSE)
                            {
                                $_SESSION['failed-remove']="<div class='error'>Failed to Remove Current Image</div>";
                                header("location:".SITEURL.'admin/manage-food.php');
                                // die();
                            }
                        }
                    }
                    else
                    {
                        $image_name = $current_image;
                    }
                }
                else
                {
                    $image_name = $current_image;
                }
                $sql3 = "UPDATE tbl_food1 SET 
                title = '$title',
                description = '$description',
                price = '$price',
                image_name = '$image_name',
                category_id = '$category',
                featured = '$featured',
                active = '$active'
                where id=$id ";

                $res3=mysqli_query($conn,$sql3);

                if ($res3==TRUE)
                {
                    $_SESSION['update'] = "<div class='success'>Food updated Successfully.</div>";
                    header('location:'.SITEURL.'admin/manage-food.php');
                    die();
                }
                else
                {
                    $_SESSION['update'] = "<div class='error'>Failed to update Food.</div>";
                    header('location:'.SITEURL.'admin/manage-food.php');
                    die();
                }
            }
        ?>
    </div>
</div>

<?php include('partials/footer.php'); ?>
