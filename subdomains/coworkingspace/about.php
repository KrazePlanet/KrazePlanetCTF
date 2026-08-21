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
   <title>about</title>
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/footer.css">
   <!-- magnific popup css cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
</head>
<body>
   
<!-- header section starts  -->
<?php include 'components/user_header.php'; ?>
<!-- header section ends -->

<div class="heading">
   <h3>about us</h3>
   <p><a href="home.php">home</a> <span> / about</span></p>
</div>

<!-- about section starts  -->

<section class="section-about-page">

   <div class="section-about-page-row">

      <div class="video">
      <video controls autoplay width="640" height="360">
         <source src="img/video.mp4" type="video/mp4">
      </video>
      </div>

      <div class="section-about-page-content">
         <h3>why choose us?</h3>
         <p>At WorkVibes, we provide the perfect coworking space for professionals like you. Why choose us? First, our modern and vibrant workspace fosters a collaborative and inspiring environment, fueling productivity and creativity. Second, our flexible membership options allow you to choose what works best for you, whether it's a dedicated desk or a hot-desking arrangement. Third, we offer top-notch amenities, including high-speed internet, meeting rooms, and complimentary refreshments, ensuring your workday is both comfortable and efficient. Fourth, our community is diverse and supportive, offering networking opportunities and potential collaborations. Lastly, our prime location in the heart of the city provides easy access to transportation and nearby amenities. Join us at WorkVibes for a professional, dynamic, and fulfilling coworking experience.</p>
      </div>

   </div>

</section>
<!-- about section ends -->

<!-- teams section starts  -->
<section class="section-team">
		<div class="center">
			<h1 class="title">Our Team</h1>
		</div>

		<div class="section-team-content">
			<div class="section-box-team">
				<img src="img/team1.jpg">
				<h3>Steph Jobs</h3>
				<h5>Asistant</h5>
				<div class="teams-icons">
               <a href="#"><i class="fa-brands fa-twitter"></i></a>
					<a href="#"><i class="fa-brands fa-facebook"></i></a>
               <a href="#"><i class="fa-brands fa-instagram"></i></a>
				</div>
			</div>

			<div class="section-box-team">
				<img src="img/team2.jpg">
				<h3>Selena Johns</h3>
				<h5>Manager</h5>
				<div class="teams-icons">
               <a href="#"><i class="fa-brands fa-twitter"></i></a>
					<a href="#"><i class="fa-brands fa-facebook"></i></a>
               <a href="#"><i class="fa-brands fa-instagram"></i></a>
				</div>
			</div>

			<div class="section-box-team">
				<img src="img/team3.jpg">
				<h3>Melis frank</h3>
				<h5>accountant</h5>
				<div class="teams-icons">
               <a href="#"><i class="fa-brands fa-twitter"></i></a>
					<a href="#"><i class="fa-brands fa-facebook"></i></a>
               <a href="#"><i class="fa-brands fa-instagram"></i></a>
				</div>
			</div>

			<div class="section-box-team">
				<img src="img/team4.jpg">
				<h3>Adam smith</h3>
				<h5>Owner</h5>
				<div class="teams-icons">
               <a href="#"><i class="fa-brands fa-twitter"></i></a>
					<a href="#"><i class="fa-brands fa-facebook"></i></a>
               <a href="#"><i class="fa-brands fa-instagram"></i></a>
				</div>
			</div>

		</div>
	</section>
<!-- teams section ends -->



<!-- reviews section starts  -->
<section class="reviews">

   <h1 class="title">Custumor's reviews</h1>

   <div class="swiper reviews-slider">

      <div class="swiper-wrapper">
      
            <div class="swiper-slide slide">
               <img src="img/pic-1.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>

            <div class="swiper-slide slide">
               <img src="img/pic-2.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>


            <div class="swiper-slide slide">
               <img src="img/pic-3.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>


            <div class="swiper-slide slide">
               <img src="img/pic-4.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>


            <div class="swiper-slide slide">
               <img src="img/pic-5.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>


            <div class="swiper-slide slide">
               <img src="img/pic-6.png" alt="">
               <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. P
                  raesentium impedit, consequuntur incidunt cum iusto explicabo
                  corrupti aliquid soluta magnam.</p>
                  <div class="stars">
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star"></i>
                     <i class="fas fa-star-half-alt"></i>
                  </div>
                  <h3>John Doe</h3>
            </div>

      </div>

      <!--<div class="swiper-pagination"></div>-->

   </div>

</section>
<!-- reviews section ends -->


<!--gallery section starts-->
<div class="gallery">

      <ul class="controls">
         <li class="buttons active" data-filter="all">all</li>
         <li class="buttons" data-filter="virtualOffice">Vitual Office</li>
         <li class="buttons" data-filter="MeetingRoom">Meeting Room</li>
         <li class="buttons" data-filter="dedicatedDesk">Dedicated desk</li>
         <li class="buttons" data-filter="officespace">Office Space</li>
         <li class="buttons" data-filter="coffeeTea">Coffee/Tea</li>
      </ul>

      <div class="image-container">

         <a href="img/virtual-ofice-co.jpg" class="image virtualOffice">
               <img src="img/virtual-ofice-co.jpg" alt="">
         </a>
         <a href="img/Virtual-Office2.jpg" class="image virtualOffice">
               <img src="img/Virtual-Office2.jpg" alt="">
         </a>
         <a href="img/virtual-office-3.jpg" class="image virtualOffice">
               <img src="img/virtual-office-3.jpg" alt="">
         </a>
         <a href="img/virtual-office-4.jpg" class="image virtualOffice">
               <img src="img/virtual-office-4.jpg" alt="">
         </a>

         <a href="img/meetingroom1.jpg" class="image MeetingRoom">
               <img src="img/meetingroom1.jpg" alt="">
         </a>
         <a href="img/meetingroom2.jpg" class="image MeetingRoom">
               <img src="img/meetingroom2.jpg" alt="">
         </a>
         <a href="img/meetingroom3.jpg"  class="image MeetingRoom">
               <img src="img/meetingroom3.jpg" alt="">
         </a>
         <a href="img/meetingroom4.jpg" class="image MeetingRoom">
               <img src="img/meetingroom4.jpg" alt="">
         </a>

         <a href="img/dedicateddesk1.jpg" class="image dedicatedDesk">
               <img src="img/dedicateddesk1.jpg" alt="">
         </a>
         <a href="img/dedicateddesk2.jpg" class="image dedicatedDesk">
               <img src="img/dedicateddesk2.jpg" alt="">
         </a>
         <a href="img/dedicateddesk3.jpg" class="image dedicatedDesk">
               <img src="img/dedicateddesk3.jpg" alt="">
         </a>
         <a href="img/dedicateddesk4.jpg" class="image dedicatedDesk">
               <img src="img/dedicateddesk4.jpg" alt="">
         </a>
         <a href="img/dedicateddesk5.jpg"  class="image dedicatedDesk">
               <img src="img/dedicateddesk5.jpg" alt="">
         </a>

         <a href="img/officespace1.jpg" class="image officespace">
               <img src="img/officespace1.jpg" alt="">
         </a>
         <a href="img/officespace2.jpg" class="image officespace">
               <img src="img/officespace2.jpg" alt="">
         </a>
         <a href="img/officespace3.jpg" class="image officespace">
               <img src="img/officespace3.jpg" alt="">
         </a>

         <a href="img/coffee1.png" class="image coffeeTea">
               <img src="img/coffee1.png" alt="">
         </a>
         <a href="img/tea1.jpg" class="image coffeeTea">
               <img src="img/tea1.jpg" alt="">
         </a>
         <a href="img/milkshake.jpg" class="image coffeeTea">
               <img src="img/milkshake.jpg" alt="">
         </a>
         <a href="img/Variated-Chocolate-Donuts.png" class="image coffeeTea">
               <img src="img/Variated-Chocolate-Donuts.png" alt="">
         </a>

      </div>

</div>
<!--gallery section ends-->



<!-- footer section starts  -->
<?php include 'components/footer.php'; ?>
<!-- footer section ends -->

<!-- jquery cdn link  -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- magnific popup js cdn link  -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>

<script>

$(document).ready(function(){

      $('.buttons').click(function(){

         $(this).addClass('active').siblings().removeClass('active');

         var filter = $(this).attr('data-filter')

         if(filter == 'all'){
               $('.image').show(400);
         }else{
               $('.image').not('.'+filter).hide(200);
               $('.image').filter('.'+filter).show(400);
         }

      });

      $('.gallery').magnificPopup({

         delegate:'a',
         type:'image',
         gallery:{
               enabled:true
         }

      });

});

</script>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<script>

var swiper = new Swiper(".reviews-slider", {
   loop:true,
   grabCursor: true,
   spaceBetween: 20,
   pagination: {
      el: ".swiper-pagination",
      clickable:true,
   },
   breakpoints: {
      0: {
      slidesPerView: 1,
      },
      700: {
      slidesPerView: 2,
      },
      1024: {
      slidesPerView: 3,
      },
   },
});

</script>

<script>
      document.getElementById("user-btn").addEventListener("click", function() {
         document.querySelector(".profile").classList.toggle("active");
      });
</script>
</body>
</html>