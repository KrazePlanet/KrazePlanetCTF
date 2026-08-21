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
   <title>contact</title>
   <style>
    a{
      color :#828282;
      font-style: italic;

    }
    a.hover{
      color:#fff;
    }
   </style>
   
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


   <div class="container">
      <h1>Thank you for contacting me. I will back to you as soon as possible!</h1>
      <p class="back">Go back to the <a href="home.php">homepage</a>.</p>
   </div></div>





<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>