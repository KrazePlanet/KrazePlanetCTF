<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    }else{
    $user_id = '';
};


   /*if (isset($_POST["submit"])) {
      $username = $_POST["name"];
      $email = $_POST["email"];
      $phone = $_POST["phone"];
      $message = $_POST["message"];

      $to = $email;
      $subject = $message;

      $message = "Name: {$username} Email: {$email} Phone: {$phone}  Message: " . $message;

      // Always set content-type when sending HTML email
      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

      // More headers
      $headers .= 'From: soukainasbai77@gmail.com';

      ini_set('SMTP', 'localhost');
      ini_set('smtp_port', '25');

      if (mail($to,$subject,$message,$headers)) {
         echo "<script>alert('Mail Send.');</script>";
      }else {
         echo "<script>alert('Mail Not Send.');</script>";
      }
   }*/
   ?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>contact</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/footer.css">

   <script
      src="https://kit.fontawesome.com/64d58efce2.js"
      crossorigin="anonymous"
   ></script>
</head>
<body>
   
<!-- header section starts  -->
<?php include 'components/user_header.php'; ?>
<!-- header section ends -->

<div class="heading">
   <h3>contact us</h3>
   <p><a href="home.php">home</a> <span> / contact</span></p>
</div>

<!--CONTACTS START--> 
<section class = "contact-section">
      <!--CONTACT INFOS START--> 
      <div class = "contact-body">
        <div class = "contact-info">
          <div>
            <span><i class = "fas fa-mobile-alt"></i></span>
            <span>Phone Number</span>
            <span class = "text">+212 6xxxxxxx</span>
          </div>
          <div>
            <span><i class = "fas fa-envelope-open"></i></span>
            <span>E-mail</span>
            <span class = "text">WORKVIBES@contact.ma</span>
          </div>
          <div>
            <span><i class = "fas fa-map-marker-alt"></i></span>
            <span>Address</span>
            <span class = "text">WorkVibes-Angle 56 Avenue de France, 37 Avenue Ibn Sina (4ème étage) Agdal 10090 - Rabat, Maroc</span>
          </div>
          <div>
            <span><i class = "fas fa-clock"></i></span>
            <span>Opening Hours</span>
            <span class = "text">Monday - Sunday (10:00 AM to 8:00 PM)</span>
          </div>
        </div>
        <!--CONTACT INFOS END-->

        <!--CONTACT FORM START-->  
        <div class = "contact-form">
          <form action="mail.php" method="POST">
            <div>
              <input type = "text" name="name" class = "form-control" placeholder="Full Name">
              <input type = "text" name="Address" class = "form-control" placeholder="Your Address">
            </div>
            <div>
              <input type = "email" name="email" class = "form-control" placeholder="E-mail">
              <input type = "text" name="phone" class = "form-control" placeholder="Phone">
            </div>
            <textarea rows = "5" name="message" placeholder="Message" class = "form-control"></textarea>
            <input type = "submit" class = "send-btn" value = "send message">
          </form>
          <!--CONTACT FORM END-->

          <!--CONTACT IMAGE START-->  
          <div>
            <img src = "img/undraw_contact_us_re_4qqt.svg" alt = "CONTACT">
          </div>
        </div>
         <!--CONTACT IMAGE END-->
      <!--MAP START--> 
      <div class = "map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3307.848586825598!2d-6.853585789148728!3d33.99642272056453!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda76c8e086cbc95%3A0x7bcdf3610a1fde6a!2sArchiteo!5e0!3m2!1sen!2sma!4v1688249435090!5m2!1sen!2sma" width="100%" height="450" style="border:0;" allowfullscreen="" frameborder="0" aria-hideen="false" tabindex="0"></iframe>
      </div>
      <!--MAP END--> 
    </section>
<!--CONTACTS END-->



   <!-- footer section starts  -->
   <?php include 'components/footer.php'; ?>
   <!-- footer section ends -->






<script>
const inputs = document.querySelectorAll(".con-input");

function focusFunc() {
    let parent = this.parentNode;
    parent.classList.add("focus");
    }

    function blurFunc() {
    let parent = this.parentNode;
    if (this.value == "") {
        parent.classList.remove("focus");
    }
    }

    inputs.forEach((input) => {
    input.addEventListener("focus", focusFunc);
    input.addEventListener("blur", blurFunc);
});
</script>

<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>