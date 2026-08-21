<?php include('partials-front/menu.php'); ?>

    <!-- CAtegories Section Starts Here -->
    <section class="categories">
        <div class="container">
            <h2 class="text-center">Explore Foods</h2>
            <?php
                $sql = "SELECT * FROM tbl_category1 where active='Yes' ";
                $res = mysqli_query($conn,$sql);
                $count = mysqli_num_rows($res);

                if ($count>0)
                {
                    while($row = mysqli_fetch_assoc($res))
                    {
                        $id = $row['id'];
                        $title = $row['title'];
                        $image_name = $row['image_name'];
                        ?>
                        <a href="<?php echo SITEURL; ?>category-foods.php?category_id=<?php echo $id; ?>">
                        <div class="box-3 float-container">
                        <?php 

                            if ($image_name=="")
                            {
                                echo "<div class='error'>Image Not Available.</div>";
                            }
                            else
                            {
                                ?>
                                <img src="<?php echo SITEURL; ?>images/category/<?php echo $image_name ; ?>" alt="Pizza" class="img-responsive img-curve">
                                <?php
                            }
                            ?>
                            <h3 class="float-text text-white"><?php echo $title ; ?></h3>
                        </div>
                        </a>
                        
                        <?php
                    }
                }
                else
                {
                    echo "<div class='error'>Category Not Found.</div>";
                }
            ?>

            

            <div class="clearfix"></div>
        </div>
    </section>
    <!-- Categories Section Ends Here -->


    <!-- social Section Starts Here -->
   
    <!-- footer Section Ends Here -->

</body>
</html>
<?php include('partials-front/footer.php'); ?>

