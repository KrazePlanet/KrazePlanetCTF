<?php
session_start();
$_SESSION['site'] = "home";
include('php/registration/dbcon.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- fav-icon -->
  <link rel="icon" type="image/png" href="media/images/Review/fav-icon.ico" />
  <!-- loading scrren css -->
  <link rel="stylesheet" href="css/Loader.css" />
  <!-- mouse trail css -->
  <link rel="stylesheet" href="css/MouseTrailer.css" />
  <!-- home page css -->
  <link rel="stylesheet" href="css/Home.css" />
  <!-- fading animation css -->
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <!-- image gallery css -->
  <link rel="stylesheet" href="css/jquery.flipster.min.css" />
  <!-- Font Awesome Icon Library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <title>Picture Perfect 🏠</title>
  <!-- loading screen js -->
  <script src="js/PreLoader.js" defer></script>
  <!-- mouse trail js -->
  <script src="js/MouseTrailer.js" defer></script>
  <!-- home page related js -->
  <script src="js/Home.js" defer></script>
</head>

<body>
  <!-- loader html -->
  <div class="loader"></div>

  <!-- mouse trail html -->
  <div id="trailer"></div>
  <!-- nav bar for mobile devices -->
  <div class="container">
    <a class="toogleBox">
      <span class="icon"></span>
    </a>
    <ul class="navItems">
      <li>
        <a href="#">
          <i class="fa fa-home" style="--i: 1"></i>
          <span style="--g: 1">home</span>
        </a>
      </li>
      <li>
        <a href="php/Gallery/view.php">
          <i class="fa fa-image" style="--i: 2"></i>
          <span style="--g: 2">Gallery</span>
        </a>
      </li>
      <?php
      if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] == 1) {
          echo '<li><a href="php/admin/fileupload.php"> <i class="fa fa-dashboard" style="--i: 3"></i>
            <span style="--g: 2">Dashboard</span></a></li>';
        } elseif ($_SESSION['role'] == 2) {
          echo '<li><a href="php/admin/clientview.php"> <i class="fa fa-dashboard" style="--i: 3"></i>
            <span style="--g: 2">Dashboard</span></a></li>';
        }
      }
      ?>
      <?php
      if (isset($_SESSION['email'])) {
        echo '<li><a href="php/registration/logout.php"> <i class="fa fa-user" style="--i: 4"></i>
          <span style="--g: 2">Logout</span></a></li>';
      } else {
        echo '<li><a href="php/registration/login.php"> <i class="fa fa-user" style="--i: 4"></i>
          <span style="--g: 2">Login</span></a></li>';
      }
      ?>
      <li>
        <a href="php/review/review.php">
          <i class="fa fa-heart" style="--i: 5"></i>
          <span style="--g: 4">Review</span>
        </a>
      </li>
      <li>
        <a href="php/bookMe/bookMe.php">
          <i class="fa fa-money" style="--i: 6"></i>
          <span style="--g: 5">bookMe</span>
        </a>
      </li>
    </ul>
  </div>
  <!-- header html includes nav bar and hero section -->
  <header id="Home">
    <nav>
      <h1>Picture Perfect</h1>
      <ul class="nav-bar-big">
        <li><a href="php/Gallery/view.php">Gallery</a></li>
        <li><a href="php/review/review.php">Reviews</a></li>
        <li><a href="php/bookMe/bookMe.php">Book Me</a></li>
        <?php
        if (isset($_SESSION['role'])) {
          if ($_SESSION['role'] == 1) {
            echo '<li><a href="php/admin/fileupload.php">Dashboard</a></li>';
          } elseif ($_SESSION['role'] == 2) {
            echo '<li><a href="php/admin/clientview.php">Dashboard</a></li>';
          }
        }
        ?>
        <?php
        if (isset($_SESSION['email'])) {
          echo '<li><a href="php/registration/logout.php">Logout</a></li>';
        } else {
          echo '<li><a href="php/registration/login.php">Login</a></li>';
        }
        ?>
      </ul>
    </nav>
    <section>
      <div class="main-text">
        <p style="font-size:large;">

          <?php if (isset($_SESSION['uname'])) {
            echo "Hello" ?><span class="quote-author">
              <?php echo ucfirst($_SESSION['uname']) ?>
            </span>
          <?php echo " welcome back 😊!";
          }
          ?>
        </p>
        <p style="font-size:large;">
          <?php echo "Avg-rating from our Customer " ?><span class="quote-author">
            <?php
            $sql = "SELECT points FROM review";
            $result = mysqli_query($conn, $sql);
            $num = mysqli_num_rows($result);
            $total = 0;
            while ($data = mysqli_fetch_assoc($result)) {
              $total += intval($data['points']);
            }
            $avg = $total / $num;
            echo number_format($avg, 2) . " / 5 ";
            ?>
          </span>
        </p>
        <p class="main-quote">
          “Photography is an austere and blazing poetry of the real.” –
          <span class="quote-author">Ansel Adams</span>
        </p>
        <div>Capturing your</div>
        <div class="auto-type"></div>
      </div>
      <div class="main-img">
        <div class="card-group">
          <div class="little-card card"></div>
          <div class="big-card card"></div>
          <div class="little-card card"></div>
          <div class="big-card card"></div>
          <div class="little-card card"></div>
          <div class="big-card card"></div>
          <div class="little-card card"></div>
          <div class="big-card card"></div>
        </div>
      </div>
    </section>
  </header>

  <!-- small curver below hero section  -->
  <div class="spacer layer1"></div>
  <main>
    <!-- gallery section which got changed to category section at later point so dont get confused by attribute names -->
    <section id="Gallery" data-aos="fade-right">
      <h2 class="-heading">Category</h2>
      <h5 class="-sub-heading">Choose <span>your</span> Occasion</h5>
      <div class="image-container">
        <p class="dbl-click-msg">*double click to select Occasion</p>
        <div id="image-track">
          <img class="gallery-image" src="media/images/Header/image1.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image2.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image3.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image4.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image5.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image6.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image7.jpg" draggable="false" ondblclick="galImagedblclick()" />
          <img class="gallery-image" src="media/images/Header/image8.jpg" draggable="false" ondblclick="galImagedblclick()" />
        </div>
      </div>
      <!-- small curver below Category section  -->
      <div class="spacer layer1"></div>
    </section>

    <!-- about section of home page -->
    <section id="About">
      <article>
        <div class="about-image">
          <img src="media/images/about/image1.jpg" alt="Image of Photographer" />
        </div>
        <div class="about-content">
          <h2 class="-heading">About <span class="me-text">Me</span></h2>
          <h5 class="sub-heading">Photographer <span>&</span> Editor</h5>
          <p class="about-text" data-aos="fade-left">
            My name is Mugambo and i am the photographer you are hearing about
            from everyone. All my qualification are of no value as my work can
            talk for me, atleast thats what i believe. I am good with all
            types of Occasion both as a guest and as a photographer clicking
            your photos like a chad in the corner. I hope that you like to
            work with best people and so you will contact me
          </p>
          <a class="anchor-btn" href="html/contact.html" draggable="false">Contact Me</a>
        </div>
      </article>
    </section>

    <!-- static review section of page -->
    <section id="Reviews">
      <h2 class="-heading">Reviews</h2>
      <h5 class="-sub-heading">See what <span>our Clients</span> Says</h5>
      <div class="client-reviews">
        <div data-aos="fade-right">
          <div class="review">
            <div class="review-img"></div>
            <div class="reviewer-name">Harish Sharma</div>
            <div class="star">
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
            </div>
            <p class="reviewer-comment">
              Lorem ipsum dolor sit amet consectetur, adipisicing elit. A id
              odit eos? Voluptate enim ipsum id autem neque incidunt, eveniet
              odit dignissimos minus mollitia sunt aut a numquam dolorum quia
              doloribus quidem? Provident, hic itaque! Numquam in magnam
              aliquid corrupti dolore? Molestiae dolorem quod sapiente repella
            </p>
          </div>
        </div>
        <div data-aos="fade-left">
          <div class="review">
            <div class="review-img"></div>
            <div class="reviewer-name">Harsh Soni</div>
            <div class="star">
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star"></span>
            </div>
            <p class="reviewer-comment">
              Lorem ipsum dolor sit amet consectetur, adipisicing elit. A id
              odit eos? Voluptate enim ipsum id autem neque incidunt, eveniet
              odit dignissimos minus mollitia sunt aut a numquam dolorum quia
              doloribus quidem? Provident, hic itaque! Numquam in magnam
              aliquid corrupti dolore? Molestiae dolorem quod sapiente repella
            </p>
          </div>
        </div>
        <div data-aos="fade-right">
          <div class="review">
            <div class="review-img"></div>
            <div class="reviewer-name">Piyush Kumar</div>
            <div class="star">
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
            </div>
            <p class="reviewer-comment">
              Lorem ipsum dolor sit amet consectetur, adipisicing elit. A id
              odit eos? Voluptate enim ipsum id autem neque incidunt, eveniet
              odit dignissimos minus mollitia sunt aut a numquam dolorum quia
              doloribus quidem? Provident, hic itaque! Numquam in magnam
              aliquid corrupti dolore? Molestiae dolorem quod sapiente repella
            </p>
          </div>
        </div>
        <div data-aos="fade-left">
          <div class="review">
            <div class="review-img"></div>
            <div class="reviewer-name">Piyush Tak</div>
            <div class="star">
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star rating-checked"></span>
              <span class="fa fa-star"></span>
            </div>
            <p class="reviewer-comment">
              Lorem ipsum dolor sit amet consectetur, adipisicing elit. A id
              odit eos? Voluptate enim ipsum id autem neque incidunt, eveniet
              odit dignissimos minus mollitia sunt aut a numquam dolorum quia
              doloribus quidem? Provident, hic itaque! Numquam in magnam
              aliquid corrupti dolore? Molestiae dolorem quod sapiente repella
            </p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- footer section of home page -->
  <footer>
    <div>
      <img class="logo" src="media/images/Header/Logo.png" alt="picture perfect logo" />
    </div>
    <div>
      <a class="footer-href-design blog" href="./html/All-Blog.html"><i class="fa fa-question"></i> Faq's</a>
    </div>
    <div>
      <a class="footer-href-design contact-us" href="contact.html"><i class="fa fa-phone"></i> Contact us</a>
    </div>
    <div class="footer-content">
      &copy; Copyright 2022-2023 by Refsnes Data. All Rights Reserved. Picture
      Perfect is Powered by Perfect.corp.
    </div>
  </footer>
  <!-- hero section auto typing text js code -->
  <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
  <!-- fading animation through out page js code -->
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <!-- jquery is a dependency for image slider section -->
  <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
  <!-- category section image slider js code -->
  <script src="js/jquery.flipster.min.js"></script>
</body>

</html>