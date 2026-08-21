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
   <title>quick view</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="quick-view">

   <h1 class="title">quick view</h1>

   <?php
            //CONNECTION DATABASE
            $db_name = 'mysql:host=localhost;dbname=cowork_db';
            $user_name = 'root';
            $user_password = '';
            
            $conn = new PDO($db_name, $user_name, $user_password);


            
      $pid = $_GET['pid'];
      $select_spaces = $conn->prepare("SELECT * FROM `spaces` WHERE id = ?");
      $select_spaces->execute([$pid]);
      if($select_spaces->rowCount() > 0){
         while($fetch_spaces = $select_spaces->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $fetch_spaces['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_spaces['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_spaces['price_per_day']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_spaces['img']; ?>">
      <img src="img/<?= $fetch_spaces['img']; ?>" alt="">
      <a href="booking.php?booking=<?= $fetch_spaces['booking']; ?>" class="cat"><?= $fetch_spaces['booking']; ?></a>
      <div class="name"><?= $fetch_spaces['name']; ?></div>
      <div class="flex">
         <div class="price_per_day"><span>$</span><?= $fetch_spaces['price_per_day']; ?></div>
         <input type="number" name="qty" class="qty" min="1" max="99" value="1" maxlength="2">
      </div>
      <button type="submit" name="add_to_cart" class="cart-btn">add to cart</button>
   </form>
   <?php
         }
      }else{
         echo '<p class="empty">no spaces added yet!</p>';
      }
   ?>

</section>
















<?php include 'components/footer.php'; ?>


<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<!-- custom js file link  -->
<script src="js/script.js"></script>


</body>
</html>