<?php
session_start();
$_SESSION['site'] = "bookMe";
if (!isset($_SESSION['uname'])) {
  header('location: ../registration/login.php');
}
?>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- fav-icon -->
  <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
  <!-- loading scrren css -->
  <link rel="stylesheet" href="../../css/Loader.css" />
  <!-- css for current page -->
  <link rel="stylesheet" href="../../css/BookMe.css">
  <!-- Font Awesome Icon Library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <!-- loading screen js -->
  <script src="../../js/PreLoader.js" defer></script>
  <title>Picture Perfect 🛒</title>
</head>

<body>
  <?php
  if (isset($_POST['submit'])) {
    $_SESSION['prod-name'] = $_POST['product-Name'];
    $_SESSION['amount'] = $_POST['amount'];
    header('location:payment.php');
  }
  ?>
  <!-- loader html -->
  <div class="loader"></div>
  <!-- nav bar for mobile devices -->
  <div class="container">
    <a class="toogleBox">
      <span class="icon"></span>
    </a>
    <ul class="navItems">
      <li>
        <a href="../../index.php">
          <i class="fa fa-home" style="--i: 1"></i>
          <span style="--g: 1">home</span>
        </a>
      </li>
      <li>
        <a href="../Gallery/view.php">
          <i class="fa fa-image" style="--i: 2"></i>
          <span style="--g: 2">Gallery</span>
        </a>
      </li>
      <li>
        <a href="../review/review.php">
          <i class="fa fa-heart" style="--i: 4"></i>
          <span style="--g: 4">Review</span>
        </a>
      </li>
      <li>
        <a href="../registration/logout.php">
          <i class="fa fa-arrow-right" style="--i: 5"></i>
          <span style="--g: 5">Logout</span>
        </a>
      </li>
    </ul>
  </div>
  <!-- normal sized nav -->
  <header>
    <nav>
      <h1>Picture Perfect</h1>
      <ul class="nav-bar-big">
        <li><a href="../../index.php" draggable="false">Home</a></li>
        <li><a href="../Gallery/view.php" draggable="false">Gallery</a></li>
        <li><a href="../review/review.php" draggable="false">Reviews</a></li>
        <li><a class="logout" href="../registration/logout.php" draggable="false">logout</a></li>
      </ul>
    </nav>
  </header>
  <main>
    <!-- book me cards -->
    <div class="book-me-container-container">
      <div class="book-me-container">
        <div class="book-me-box">
          <div class="box-name">Real life. Memories. Captured.</div>
          <div class="circle-price">5000₹</div>
          <div class="box-content">
            <ul>
              <li>Party shoot's</li>
              <li>includes birthday parties and other occasions</li>
              <li>Baby shoot's</li>
              <li>Download all editied images</li>
              <li>Album with all selected images(200 selected images)</li>
              <li>500 Photos</li>
            </ul>
          </div>
          <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="product-Name" value="Real life. Memories. Captured">
            <input type="hidden" name="amount" value="5000">
            <input type="submit" name="submit" class="anchor-btn" value="Buy Now" />
          </form>
        </div>
        <div class="book-me-box">
          <div class="box-name">
            Defining your wedding moments with a fairytale
          </div>
          <div class="circle-price">25000₹</div>
          <div class="box-content">
            <ul>
              <li>Wedding Photography</li>
              <li>Pre-Wedding</li>
              <li>props included</li>
              <li>Download all editied images</li>
              <li>Album with all selected images(500 selected images)</li>
              <li>No limit in photos clicked</li>
            </ul>
          </div>
          <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="product-Name" value="Defining your wedding moments with a fairytale">
            <input type="hidden" name="amount" value="25000">
            <input type="submit" name="submit" class="anchor-btn" value="Buy Now" />
          </form>
        </div>
        <div class="book-me-box">
          <div class="box-name">Your Product Our Responsibility</div>
          <div class="circle-price">10000₹</div>
          <div class="box-content">
            <ul>
              <li>Product shoot's</li>
              <li>props included</li>
              <li>Download all editied images</li>
              <li>Album with all selected images</li>
              <li>No limit in photos clicked</li>
            </ul>
          </div>
          <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="product-Name" value="Your Product Our Responsibility">
            <input type="hidden" name="amount" value="10000">
            <input type="submit" name="submit" class="anchor-btn" value="Buy Now" />
          </form>
        </div>
      </div>
    </div>
  </main>
  <footer>
    <div>
      <img class="logo" src="../../media/images/Header/Logo.png" alt="picture perfect logo" />
    </div>
    <div>
      <a class="footer-href-design blog" href="../../html/All-Blog.html"><i class="fa fa-question"></i> Faq's</a>
    </div>
    <div>
      <a class="footer-href-design contact-us" href="../../contact.html"><i class="fa fa-phone"></i> Contact us</a>
    </div>
    <div class="footer-content">
      &copy; Copyright 2022-2023 by Refsnes Data. All Rights Reserved. Picture
      Perfect is Powered by Perfect.corp.
    </div>
  </footer>
  <script>
    //script for mobile nav 
    var toogleClick = document.querySelector(".toogleBox");
    var container = document.querySelector(".container");
    toogleClick.addEventListener("click", () => {
      if (toogleClick.classList.contains("active")) {
        toogleClick.classList.remove("active");
        container.classList.remove("active");
      } else {
        toogleClick.classList.add("active");
        container.classList.add("active");
      }
    });
  </script>
</body>

</html>