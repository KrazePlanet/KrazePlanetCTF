<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/add_cart.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>search page</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/search.css">
   <link rel="stylesheet" href="css/footer.css">

</head>
<body>
   
<!-- header section starts  -->
<?php include 'components/user_header.php'; ?>
<!-- header section ends -->

<!-- search form section starts  -->

<section class="search-form">
   <form method="post" action="">
      <input type="text" name="search_box" placeholder="search here..." class="box">
      <button type="submit" name="search_btn" class="fas fa-search"></button>
   </form>
</section>

<!-- search form section ends -->


<section class="search-spaces-section">

<div class="search-spaces-box-container">

      <?php
         if(isset($_POST['search_box']) OR isset($_POST['search_btn'])){
         $search_box = $_POST['search_box'];
         $select_products = $conn->prepare("SELECT * FROM `spaces` WHERE name LIKE '%{$search_box}%'");
         $select_products->execute();
         if($select_products->rowCount() > 0){
            while($fetch_spaces = $select_products->fetch(PDO::FETCH_ASSOC)){
      ?>
         <form action="" method="post" class="box">
            <input type="hidden" name="pid" value="<?= $fetch_spaces['id']; ?>">
            <input type="hidden" name="name" value="<?= $fetch_spaces['name']; ?>">
            <input type="hidden" name="price_per_day" value="<?= $fetch_spaces['price_per_day']; ?>">
            <input type="hidden" name="description" value="<?= $fetch_spaces['description']; ?>">
            <a href="spaces.php?pid=<?= $fetch_spaces['id']; ?>" class="fas fa-eye"></a>
            <img src = "img/<?php echo $fetch_spaces['img']?>"/>
            <div class="name"><?= $fetch_spaces['name']; ?></div>
            <div class="flex">
               <div style="font-size: 15px;" class="price_per_day"><span>MAD</span><?= $fetch_spaces['price_per_day']; ?></div>
            </div>
            <div class="more-btn">
               <a href="booking.php" class="btn">See More</a>
            </div>
         </form>
      <?php
            }
         }else{
            echo '<p class="empty">no spaces added yet!</p>';
         }
      }
      ?>

   </div>

</section>











<!-- footer section starts  -->
<?php include 'components/footer.php'; ?>
<!-- footer section ends -->







<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>