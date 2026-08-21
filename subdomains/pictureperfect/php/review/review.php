<?php
session_start();
$_SESSION['site'] = "review";
if (!isset($_SESSION['uname'])) {
  header('location: ../registration/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Write your heart out 💓</title>
</head>
<!-- fav-icon -->
<link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
<!-- loading scrren css -->
<link rel="stylesheet" href="../../css/Loader.css" />
<!-- css for current page -->
<link rel="stylesheet" href="../../css/review.css" />
<!-- Font Awesome Icon Library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
<!-- loading screen js -->
<script src="../../js/PreLoader.js" defer></script>
<!-- review page js -->
<script src="../../js/review.js" defer></script>

<body>
  <!-- loader html -->
  <div class="loader"></div>
  <div class="overlay"></div>
  <div class="modal">
    <h2>Write your review</h2>
    <h3 class="modal-error"></h3>
    <form>
      <label for="rating">Points / 5</label><br />
      <select id="rating" name="rating">
        <option value="5">5 Points</option>
        <option value="4">4 Points</option>
        <option value="3">3 Points</option>
        <option value="2">2 Points</option>
        <option value="1">1 Points</option>
      </select><br />
      <input type="text" id="review-title" placeholder="Title for your review" required /><br />
      <textarea id="review-summary" cols="30" rows="10" placeholder="Summary for your review" required></textarea><br />
      <input id="sendData" type="submit" value="save" />
      <input type="submit" class="cancel" value="cancel" />
    </form>
  </div>
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
        <a href="../bookMe/bookMe.php">
        <i class="fa fa-money" style="--i: 5"></i>
          <span style="--g: 4">BookMe</span>
        </a>
      </li>
      <li>
        <a href="../registration/logout.php">
          <i class="fa fa-arrow-right" style="--i: 5"></i>
          <span style="--g: 5">logout</span>
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
        <li><a href="../bookMe/bookMe.php" draggable="false">BookMe</a></li>
        <li>
          <a class="logout" href="../registration/logout.php" draggable="false">logout</a>
        </li>
      </ul>
    </nav>
  </header>
  <main>
    <div class="headline">
      <h1>Leave your precious review to help others decide...</h1>
    </div>
    <section class="add-review-container">
      <div class="add-review">
        <p>Picture Perfect</p>
        <div class="all-review-container">
          <div class="avg-rating-container">
            <div class="avg-rating">
              <p style="font-size: 2rem;">Avg Rating</p>
              <span id="avg-points">0.0</span>
              <span>/ 5</span>
              <div class="total-rating"><span>0</span> Reviews</div>
            </div>
          </div>
          <div class="spiecific-rating-container">
            <div class="specific-rating-sub-container">
              <div class="specific-rating">
                <span>5</span>
                <a href="#" class="rating-bar">
                  <p id="rating-5"></p>
                </a>
              </div>
              <div class="specific-rating">
                <span>4</span>
                <a href="#" class="rating-bar">
                  <p id="rating-4"></p>
                </a>
              </div>
              <div class="specific-rating">
                <span>3</span>
                <a href="#" class="rating-bar">
                  <p id="rating-3"></p>
                </a>
              </div>
              <div class="specific-rating">
                <span>2</span>
                <a href="#" class="rating-bar">
                  <p id="rating-2"></p>
                </a>
              </div>
              <div class="specific-rating">
                <span>1</span>
                <a href="#" class="rating-bar">
                  <p id="rating-1"></p>
                </a>
              </div>
            </div>
          </div>
          <div class="write-review-container">
            <div class="write-review">
              <p>Write Review Here</p>
              <button class="addReviewBtn">Review</button>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="all-reviews">
      <h2>All reviews</h2>
    </section>
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
</body>

</html>