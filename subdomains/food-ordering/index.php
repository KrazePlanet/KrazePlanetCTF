<?php
session_start();

// Include database connection file
include 'db_connection.php';

// Check if database connection was successful
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// Prepare query to fetch popular items
$sql = "SELECT itemName, image, price FROM menuitem WHERE is_popular = 1";

// Check if query was successful
if ($result = $conn->query($sql)) {
  // Initialize array to store popular items
  $popularItems = [];

  // Fetch and store query results
  while ($row = $result->fetch_assoc()) {
    $popularItems[] = $row;
  }

  // Close query result
  $result->close();
} else {
  // Display error message if query fails
  echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--Bootstrap CSS-->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
  <!--poppins-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <!--Icon-->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css' />
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css' />
  <link href="https://fonts.googleapis.com/css2?family=Allura&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/assets/owl.carousel.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Chewy Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Chewy&display=swap" rel="stylesheet">
  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="index.css">
  <title>Home</title>
</head>

<body>
  <?php
  if (isset($_SESSION['userloggedin']) && $_SESSION['userloggedin']) {
    include 'nav-logged.php';
  } else {
    include 'navbar.php';
  }
  ?>

  <div class="main">
    <section>
      <div class="container mt-3">
        <div class="row d-flex justify-content-start align-items-start main-container">
          <div class="col-md-5 col-sm-12 col-lg-5 reveal main-text mb-4 text-align-justify mt-5" data-aos="fade-up">
            <h2>Welcome to <span style="color: #fb4a36;"> Grill 'N' Chill,</span></h2>
            <h4 style="color: gray; font-weight: 450;">"Where Hot Flavors Meet Cool Comfort."</h4>
            <p style="font-size: 18px; text-align: justify;">
              Dive into a culinary celebration where every dish bursts with
              flavor. At Grill 'N' Chill, we believe in making every meal an
              unforgettable experience. Whether you're here for a casual meal or a
              special occasion, our vibrant dishes will leave a lasting
              impression.
            </p>
            <div class="buttondiv">
              <div>
                <a href="login.php">
                  <button class="button">
                    Start Order
                    <svg class="cartIcon" viewBox="0 0 576 512">
                      <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"></path>
                    </svg>
                  </button>
                </a>
              </div>
              <div>
                <a class="button1" href="menu.php">
                  <span class="button__icon-wrapper">
                    <svg width="10" class="button__icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 15">
                      <path fill="currentColor" d="M13.376 11.552l-.264-10.44-10.44-.24.024 2.28 6.96-.048L.2 12.56l1.488 1.488 9.432-9.432-.048 6.912 2.304.024z"></path>
                    </svg>
                    <svg class="button__icon-svg button__icon-svg--copy" xmlns="http://www.w3.org/2000/svg" width="10" fill="none" viewBox="0 0 14 15">
                      <path fill="currentColor" d="M13.376 11.552l-.264-10.44-10.44-.24.024 2.28 6.96-.048L.2 12.56l1.488 1.488 9.432-9.432-.048 6.912 2.304.024z"></path>
                    </svg>
                  </span>
                  Explore Menu
                </a>
              </div>
            </div>
          </div>
          <div class="col-md-7 col-sm-12 col-lg-7 d-flex justify-content-center align-items-start slide-in-right main-image">
            <img src="images/Pizza.png" class="img" style=" width: 85%; height: 80%;">
          </div>
        </div>
        <div class="row">
          <!-- Menu Section -->
          <section>
            <div class="menu-section">
              <div class="container-fluid">
                <div class="row">
                  <div class="row d-flex justify-content-center align-items-center mb-4 font-weight-bold" id="text">
                    <h1>OUR <span>MENU</span></h1>
                  </div>
                  <div class="col-lg-3 col-md-6 mb-4">
                    <div class="category-card" style="background-image: url('images/appe-index.avif');" data-aos="fade-up">
                      <div class="card-overlay">
                        <div class="overlay-content">
                          <h3>Appetizer</h3>
                          <p>Start your meal with our delicious appetizers that set the tone for a delightful dining experience.</p>
                          <a href="menu.php#appetizer">
                            <button class="explore-btn">Explore Variety</button></a>
                        </div>
                      </div>
                      <div class="card-bottom">
                        <h3>Appetizer</h3>
                        <a href="menu.php#appetizer">
                          <button class="explore-btn">Explore Variety</button></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 mb-4">
                    <div class="category-card" style="background-image: url('images/index-pizza.jpg');" data-aos="fade-up">
                      <div class="card-overlay">
                        <div class="overlay-content">
                          <h3>Pizza</h3>
                          <p>Indulge in our wide variety of pizzas, each crafted with the finest ingredients and baked to perfection.</p>
                          <a href="menu.php#pizza">
                            <button class="explore-btn">Explore Variety</button></a>
                        </div>
                      </div>
                      <div class="card-bottom">
                        <h3>Pizza</h3>
                        <a href="menu.php#pizza">
                          <button class="explore-btn">Explore Variety</button></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 mb-4">
                    <div class="category-card" style="background-image: url('images/index-burger.avif');" data-aos="fade-up">
                      <div class="card-overlay">
                        <div class="overlay-content">
                          <h3>Burger</h3>
                          <p>Savor our juicy burgers, loaded with fresh toppings and bursting with flavor in every bite.</p>
                          <a href="menu.php#burger">
                            <button class="explore-btn">Explore Variety</button></a>
                        </div>
                      </div>
                      <div class="card-bottom">
                        <h3>Burger</h3>
                        <a href="menu.php#burger">
                          <button class="explore-btn">Explore Variety</button></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 mb-4">
                    <div class="category-card" style="background-image: url('images/bev-index.jpeg');" data-aos="fade-up">
                      <div class="card-overlay">
                        <div class="overlay-content">
                          <h3>Beverage</h3>
                          <p>Quench your thirst with our selection of refreshing beverages, perfect for any meal.</p>
                          <a href="menu.php#beverage">
                            <button class="explore-btn">Explore Variety</button></a>
                        </div>
                      </div>
                      <div class="card-bottom">
                        <h3>Beverage</h3>
                        <a href="menu.php#beverage">
                          <button class="explore-btn">Explore Variety</button></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </section>
  </div>

  <!-- Why Choose Us Section  -->
  <section class="why-choose-us" id="why-choose-us">
    <div class="container">
      <div class="row why-us-content">
        <div class="col-md-12 col-lg-6 col-sm-12 col-xs-12 mt-5 reveal d-flex justify-content-start align-items-start" data-aos="fade-up">
          <img src="images/Why-Us.png" width="100%" height="auto" loading="lazy" alt="delivery boy" class="w-100 delivery-img" data-delivery-boy>
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12 d-flex flex-column justify-content-center reveal" data-aos="fade-up">
          <h1>WHY <span>CHOOSE US?</span></h1>
          <p class="content">Our restaurant offers the best food delivery service with fresh and high-quality ingredients.</p>
          <ul class="why-choose-us-list">
            <li data-aos="fade-up">
              <div class="image-wrapper mt-1">
                <img src="icons/delivery-man.png" alt="Fast Delivery">
              </div>
              <div class="feature-content">
                <h4>Fast Delivery</h4>
                <p>Enjoy prompt and reliable delivery to your doorstep.</p>
              </div>
            </li>
            <li data-aos="fade-up">
              <div class="image-wrapper">
                <img src="icons/vegetables.png" alt="Fresh Ingredients">
              </div>
              <div class="feature-content">
                <h4>Fresh Ingredients</h4>
                <p>We use only the freshest and highest quality ingredients.</p>
              </div>
            </li>
            <li data-aos="fade-up">
              <div class="image-wrapper">
                <img src="icons/waiter (1).png" alt="Friendly Service" class="why-us-image">
              </div>
              <div class="feature-content">
                <h4>Friendly Service</h4>
                <p>Experience warm and welcoming customer service.</p>
              </div>
            </li>
            <li data-aos="fade-up">
              <div class="image-wrapper">
                <img src="icons/tasty.png" alt="Exceptional Taste">
              </div>
              <div class="feature-content">
                <h4>Exceptional Taste</h4>
                <p>Indulge in flavors that are truly exceptional.</p>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Top picks section -->
      <div class="popular reveal" data-aos="fade-up">
        <h1 class="text-center mt-3">OUR <span>TOP PICKS</span></h1>
        <P class="text-center" style="font-size: 1.3rem;">~Handpicked meals that are a hit with everyone.</P>

        <div id="cardCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="8000" data-aos="fade-up">
          <div class="carousel-inner">

            <div id="toast" class="toast">
              <button class="toast-btn toast-close">&times;</button>
              <span class="pt-3"><strong>You must log in to add items to the cart.</strong></span>
              <button class="toast-btn toast-ok">Okay</button>
            </div>
            <?php
            $chunkedItems = array_chunk($popularItems, 3); // Group items into chunks of 3
            $isActive = true; // To set the first carousel item as active

            foreach ($chunkedItems as $items) {
              echo '<div class="carousel-item' . ($isActive ? ' active' : '') . '" >';
              echo '<div class="d-flex justify-content-center">';

              foreach ($items as $item) {
                echo '<div class="card" >';
                echo '<img src="uploads/' . $item['image'] . '" class="card-img-top" alt="' . $item['itemName'] . '">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title text-center">' . $item['itemName'] . '</h5>';
                echo '<p class="card-text text-center">Rs ' . $item['price'] . '</p>';
                echo '<a class="button-cart" onclick="addToCart()">Add to Cart</a>';
                echo '</div>';
                echo '</div>';
              }

              echo '</div>';
              echo '</div>';
              $isActive = false; // Only the first item should be active
            }
            ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#cardCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#cardCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- About Us section -->
  <div class="aboutus" id="About-Us" style="background-image: url(images/about-bg.png); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <section class="our-story-section p-5">
      <div class="container ">
        <div class="row" data-aos="fade-up">
          <h1 style="text-align: center;"><span style="color: #fb4a36;">ABOUT </span>US</h1>
          <h4 style="text-align: center;" class="mb-5">Crafting Memorable Meals!</h4>
        </div>
        <div class="story-content row mb-2">
          <div class="story-text col-lg-6 col-md-6 col-sm-12 reveal mt-2" data-aos="fade-up" data-os-interval="300">
            <p>At <strong>Grill 'N' Chill</strong>, we are passionate about celebrating food. Our chefs bring a touch of creativity to every dish, ensuring a feast for your senses. Join us for an extraordinary dining experience that celebrates flavor and joy.</p>
            <p>Founded in [2020], Grill 'N' Chill has been at the forefront of culinary innovation. Our commitment to using the freshest ingredients, combined with our chefs' expertise, has earned us a reputation for excellence. We believe that dining is not just about eating; it's about experiencing the art of food.</p>
            <p>Whether you're looking for a romantic dinner, a family gathering, or a place to celebrate special occasions, Grill 'N' Chill offers the perfect ambiance and exquisite cuisine to make your visit unforgettable. Come and experience the joy of flavor with us!</p>
            <a href="menu.php" class="about_btn">
              <i class="fa-solid fa-burger"></i>Order Now
            </a>
          </div>
          <div class="story-image col-lg-6 col-md-6 col-sm-12 d-flex justify-content-end align-items-start slide-in-right" data-aos="fade-up">
            <img src="images/Burger.png" alt="Crafting Memorable Meals" style="width: 100%; height: auto;">
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Table Reservation -->
  <section class="table-reservation" id="Reservation">
    <div class="row text-center ms-4" data-aos="fade-up">
      <h1 class="mb-2">TABLE <span style="color: #fb4a36;">RESERVATION</span></h1>
      <h5 class="mb-5">Book your dining experience with us and enjoy a delightful meal.</h5>
    </div>
    <div class="table ms-4 me-5" data-aos="fade-up">
      <div class="reservation row reveal">
        <div class="reservation-image col-lg-7 col-md-6 col-sm-12" style="background: none !important; padding: 0 !important;">
          <img src="images/table.jpg" alt="Reservation" style="background: none ; width: 100%; height: 100%; padding: 0 !important;" class=" w-100 h-100">
        </div>
        <div class="reservation-section col-lg-5 col-md-6 col-sm-12">
          <h2 style="background-color: #feead4;">Reserve Now!</h2>
          <form id="reservation-form" action="reservations.php" method="POST">
            <div class="form-row">
              <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
              </div>
              <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="phone">Contact:</label>
                <input type="tel" id="phone" name="contact" required>
              </div>
              <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="reservedDate" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="reservedTime">Time:</label>
                <input type="time" id="time" name="reservedTime" required>
              </div>
              <div class="form-group">
                <label for="guests">Number of Guests:</label>
                <input type="number" id="guests" name="noOfGuests" required min="1">
              </div>
            </div>
            <button type="submit" value="submit">Reserve Now</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Review  -->
  <section class="testimonial" id="review">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
          <div class="text-center mb-5" data-aos="fade-up">
            <h1>Hear From Our <span>Happy Customers!</span></h1>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="clients-carousel owl-carousel" data-aos="fade-up">
          <div class="single-box">
            <div class="img-area"><img alt="" class="img-fluid" src="uploads/user-girl.png"></div>
            <div class="content">
              <p>"The food was fresh, and the flavors were incredible. I loved the variety on the menu. A great place for family dinners."</p>
              <h4>-Ritika Singh</h4>
            </div>
          </div>
          <div class="single-box">
            <div class="img-area"><img alt="" class="img-fluid" src="uploads/user-boy.jpg"></div>
            <div class="content">
              <p>"The online ordering process was seamless and easy to navigate. My food arrived hot and on time. The delivery service was very professional."</p>
              <h4>-Zidnan</h4>
            </div>
          </div>
          <div class="single-box">
            <div class="img-area"><img alt="" class="img-fluid" src="uploads/default.jpg"></div>
            <div class="content">
              <p>"Fantastic place! The burgers are juicy, and the pizzas are loaded with toppings. The staff is super friendly, and the service is quick. A new favorite spot!"</p>
              <h4>-Dave Wood</h4>
            </div>
          </div>
          <div class="single-box">
            <div class="img-area"><img alt="" class="img-fluid" src="uploads/default.jpg"></div>
            <div class="content">
              <span class="rating-star"><i class="icofont-star"></i><i class="icofont-star"></i><i class="icofont-star"></i><i class="icofont-star"></i><i class="icofont-star"></i></span>
              <p>"The online ordering system is fantastic. It’s easy to customize my order, and the delivery is always prompt. The food arrives hot and tasty every time."</p>
              <h4>-jimmy kimmel</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- footer -->
  <footer>
    <div class="footer-container">
      <div class="footer-row">
        <div class="footer-col" id="contact">
          <h4>Contact Us</h4>
          <p>123 Galle Road, Colombo 04</p>
          <p>Email: info@grillnchill.com</p>
          <p>Phone: +94 77 123 4567</p>
        </div>
        <div class="footer-col">
          <h4>Follow Us</h4>
          <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Subscribe</h4>
          <form action="#">
            <input type="email" placeholder="Your email address" required style="background-color: #f9f9f9; color: #333; margin-top: 12px;">
            <button type="submit">Subscribe</button>
          </form>
        </div>
      </div>
      <div class="footer-bottom">
        <h4>&copy; 2024 Authored by Asna Assalam. All Rights Reserved.</h4>
      </div>
    </div>
  </footer>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js">
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/owl.carousel.min.js">
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js">
  </script>
  <!-- AOS -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script>
    $(document).ready(function() {
      console.log('Page is ready. Calling load_cart_item_number.');
      load_cart_item_number();

      function load_cart_item_number() {
        $.ajax({
          url: 'action.php',
          method: 'get',
          data: {
            cartItem: "cart_item"
          },
          success: function(response) {
            $("#cart-item").html(response);
          }
        });
      }
    });
  </script>
  <script>
    $('.clients-carousel').owlCarousel({
      loop: true,
      nav: false,
      autoplay: true,
      autoplayTimeout: 5000,
      animateOut: 'fadeOut',
      animateIn: 'fadeIn',
      smartSpeed: 450,
      margin: 30,
      responsive: {
        0: {
          items: 1
        },
        768: {
          items: 2
        },
        991: {
          items: 2
        },
        1200: {
          items: 2
        },
        1920: {
          items: 2
        }
      }
    });
  </script>
  <script>
    function addToCart() {
      var userLoggedIn = <?php echo isset($_SESSION['userloggedin']) ? 'true' : 'false'; ?>;

      if (!userLoggedIn) {
        showToast();
      } else {
        // Add to cart logic goes here
      }
    }

    function showToast() {
      var toast = document.getElementById("toast");
      toast.className = "toast show";

      // Handle "Okay" button click
      document.querySelector('.toast-ok').onclick = function() {
        window.location.href = 'login.php'; // Redirect to login page
      };

      // Handle "Close (X)" button click
      document.querySelector('.toast-close').onclick = function() {
        toast.className = toast.className.replace("show", "hide");
      };
    }
  </script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- Bootstrap JS and dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const elements = document.querySelectorAll('.animate-on-scroll');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('reveal');
          }
        });
      }, {
        threshold: 0.1
      });

      elements.forEach(element => {
        observer.observe(element);
      });
    });
  </script>


</body>
</html>