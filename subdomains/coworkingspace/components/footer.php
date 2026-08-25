<?php
// Assuming you have already established a MySQL database connection
// Database configuration
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));     // Hostname
$user = "root";      // Username
$password = "";  // Password
$database = "cowork_db";    // Database name

// Check if the form is submitted
if (isset($_POST['name'], $_POST['email'], $_POST['phone_number'], $_POST['address'])) {
    // Create a connection
    $connection = mysqli_connect($host, $user, $password, $database);
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    $fullName = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phone_number'];
    $address = $_POST['address'];

    // Insert form data into the database
    $query = "INSERT INTO users (name, email, phone, address) VALUES ('$fullName', '$email', '$phoneNumber', '$address')";

    if ($connection->query($query) === TRUE) {
        // Registration successful, display alert and redirect to booking page
        echo '<script>alert("Information registered successfully. Please if you are interested go to booking page.");</script>';
        echo '<script>window.location.href = "booking.php";</script>';
        exit();
    } else {
        // Error occurred, display alert
        echo '<script>alert("Error: ' . $connection->error . '");</script>';
    }

    $connection->close();
}
?>

<footer class="footer">
  <section class="footer-container">
    <h1 class="center-title">Let's get in touch.</h1>
    <p class="subtitle">Leave your details below and get a free trial or a tour</p>

    <div class="box-footer">
      <h2 class="box-title">1. Pick an option</h2>
      <ul class="options">
        <li class="active" onclick="selectOption(this)"><i class="fas fa-building"></i><span>Office Space</span></li>
        <li onclick="selectOption(this)"><i class="fas fa-users"></i><span>Coworking Membership</span></li>
        <li onclick="selectOption(this)"><i class="fas fa-laptop"></i><span>Dedicated Desk</span></li>
        <li onclick="selectOption(this)"><i class="fas fa-map-marker-alt"></i><span>Virtual Office</span></li>
        <li class="last-option" onclick="selectOption(this)"><span>I can't decide yet, I just want a tour</span></li>
      </ul>
    </div>

   <div class="box-footer">
      <h2 class="box-title">2. Fill in your details</h2>
      <form action="<?php htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
         <input type="text" name="name" placeholder="Full Name" required>
         <input type="email" name="email" placeholder="Email" required>
         <input type="phone" name="phone_number" placeholder="Phone Number" required>
         <input type="address" name="address" placeholder="your address" required>
         <input type="submit" value="Submit" class="btn" >
      </form>
   </div>
   </section>


<div class="footer-distributed">

   <div class="footer-left">
      <h3>WORK<span>VIBES</span></h3>

      <p class="footer-links">
         <a href="#">Home</a>
         |
         <a href="#">About</a>
         |
         <a href="#">Booking</a>
         |
         <a href="#">Contact</a>
      </p>

      <p class="footer-company-name">Copyright © 2023 <strong>WORKVIBES</strong> All rights reserved</p>
   </div>

   <div class="footer-center">
      <div>
      <i class="fa-solid fa-location-dot"></i>
         <p><span>Morocco</span>
               Rabat</p>
      </div>

      <div>
         <i class="fa fa-phone"></i>
         <p>+212 06xxxxxxxx</p>
      </div>
      <div>
         <i class="fa fa-envelope"></i>
         <p><a href="mailto:sagar00001.co@gmail.com">WORKVIBES@contact.ma</a></p>
      </div>
   </div>
   <div class="footer-right">
      <p class="footer-company-about">
         <span>About the Company</span>
         <strong>WORKVIBES</strong> is a premier
            coworking space provider, offering flexible work environments for professionals. 
            With a focus on fostering collaboration and productivity,
            WORKVIBES provides a vibrant and inspiring workspace for individuals and businesses. Join us and experience a supportive community, 
            modern amenities, and a
            dynamic work environment that meets your needs.
      </p>
      <div class="footer-icons">
         <a href="#"><i class="fa-brands fa-facebook"></i></a>
         <a href="#"><i class="fa-brands fa-instagram"></i></a>
         <a href="#"><i class="fa-brands fa-linkedin"></i></i></a>
         <a href="#"><i class="fa-brands fa-twitter"></i></a>
         <a href="#"><i class="fa-brands fa-youtube"></i></a>
      </div>
   </div>
</div>

</footer>

<script>
   function selectOption(option) {
      const options=document.querySelectorAll('.options li');
      options.forEach((opt) => opt.classList.remove('active'));
      option.classList.add('active');
   }
</script>

<!--<div class="loader">
   <img src="img/loader.gif" alt="">
</div>-->