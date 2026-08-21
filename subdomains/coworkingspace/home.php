<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" type="" href="css/footer.css">
   <title>home</title>

</head>
<body>

<?php include 'components/user_header.php'; ?>
<!--HOME HEADING SECTION STARTS-->
<section class="home-hero">
   <div class="swiper hero-slider">
      <div class="swiper-wrapper">
         <div class="swiper-slide slide">
            <div class="home-content">
               <span>Booking Online</span>
               <h3>Perfect working spaces</h3>
               <a href="spaces.php" class="btn">see space</a>
            </div>
            <div class="image">
            </div>
         </div>
         <div class="swiper-slide slide">
            <div class="home-content">
               <span>Booking online</span>
               <h3>Meeting Room</h3>
               <a href="spaces.html" class="btn">see space</a>
            </div>
            <div class="image">
            </div>
         </div>
         <div class="swiper-slide slide">
            <div class="home-content">
               <span>Booking online</span>
               <h3>Hot desk</h3>
               <a href="spaces.html" class="btn">see space</a>
            </div>
            <div class="image">
            </div>
         </div>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>
<!--HOME HEADING SECTION ENDS-->


<!-- about section starts-->
<section class="about-section">
   <div class="about-section-content">
      <div class="image-section-container">
         <img src="img/bg.jpg" alt="WORKVIBES">
      </div>
      <div class="about-text-container">
         <h2>Who We Are</h2>
         <p> <strong>WORKVIBES</strong> is a premier coworking space provider, offering flexible work environments for professionals. With a focus on fostering collaboration and productivity, WORKVIBES provides a vibrant and inspiring workspace for individuals and businesses. Join us and experience a supportive community, modern amenities, and a dynamic work environment that meets your needs.
         </p>
         <a href="about.php" class="btn" id="about-btn">Read More</a>
      </div>
   </div>
</section>
<!-- about section ends-->




<!--Services section starts-->
<section class="section-services">
      <div class="section-service_row">
         <h2 class="title">Our Services</h2>
         </div>
         <div class="section-service_row">
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <i class="fa-solid fa-wifi"></i>
               </div>
               <h3>WIFI High Speed</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <img src="img/meeting-room.png" alt="">
               </div>
               <h3>Meeting Room</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <img src="img/office.png" alt="">
               </div>
               <h3>Virtual Office</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <img src="img/desk.png" alt="">
               </div>
               <h3>Dedicated Desk</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <img src="img/office-space.png" alt="">
               </div>
               <h3>Office Space</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         <div class="section-service_column">
            <div class="services-card">
               <div class="services-icon-wrapper">
               <img src="img/membership.png" alt="">
               </div>
               <h3>Co-working Membership</h3>
               <p>
               Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quisquam
               consequatur necessitatibus eaque.
               </p>
            </div>
         </div>
         </div>
</section>
<!--Services section ends-->




<!-- SPACES section STARTS-->
<section class="spaces-section">
   <h1 class="title">New Spaces</h1>
   <div class="spaces-box-container">
      <?php
         $db_name = 'mysql:host=localhost;dbname=cowork_db';
         $user_name = 'root';
         $user_password = '';
         
         $conn = new PDO($db_name, $user_name, $user_password);


         $select_spaces = $conn->prepare("SELECT * FROM `spaces` LIMIT 4");
         $select_spaces->execute();
         if($select_spaces->rowCount() > 0){
            while($fetch_spaces = $select_spaces->fetch(PDO::FETCH_ASSOC)){
      ?>
      <form action="booking.php" method="post" class="spaces-section-box">
         <input type="hidden" name="pid" value="<?= $fetch_spaces['id']; ?>">
         <input type="hidden" name="name" value="<?= $fetch_spaces['name']; ?>">
         <input type="hidden" name="price_per_day" value="<?= $fetch_spaces['price_per_day']; ?>">
         <input type="hidden" name="description" value="<?= $fetch_spaces['description']; ?>">
         <a href="spaces.php?pid=<?= $fetch_spaces['id']; ?>" class="fas fa-eye"></a>
         <!--<button type="submit" class="fas fa-shopping-cart" name="add_to_card"></button>-->
         <img src = "img/<?php echo $fetch_spaces['img']?>"/>
         <a href="booking.php?name=<?= $fetch_spaces['location']; ?>" class="cat"><?= $fetch_spaces['location']; ?></a>
         <div class="name"><?= $fetch_spaces['name']; ?></div>
         <div class="flex">
            <div style="font-size: 15px;" class="price_per_day"><span>MAD</span><?= $fetch_spaces['price_per_day']; ?></div>
         </div>
      </form>
      <?php
            }
         }else{
            echo '<p class="empty">no spaces added yet!</p>';
         }
      ?>

   </div>

   <div class="more-btn">
      <a href="booking.php" class="btn">veiw all</a>
   </div>

</section>
<!-- SPACES section ends-->

<?php include 'components/footer.php'; ?>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<script>
var swiper = new Swiper(".hero-slider", {
   loop:true,
   grabCursor: true,
   effect: "flip",
   pagination: {
      el: ".swiper-pagination",
      clickable:true,
   },
});
</script>

</body>
</html>