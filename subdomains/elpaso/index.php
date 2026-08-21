<?php
	session_start();
	include 'admin/connect.php';
?>
<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
    <!--<meta http-equiv="content-language" content="hr-HR"/>-->
    <meta name="keywords" content=""/>
    <meta name="description" content=""/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="El Paso"/>
    <meta property="og:description" content=""/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="fontawesome/css/all.css">
    <script src="https://kit.fontawesome.com/9f52e31018.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="show.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <title>El Paso</title>
    <link rel="shortcut icon" href="img/favicon.png" />
      <style>
        /*body {
          background-image: url("img/slide_3.jpg");
          background-position: center;
          background-repeat: no-repeat;
          background-attachment: fixed;
          background-size: cover;
          transition: 0.2s;
        }*/
        .mySlides {display:none;}
    </style>
</head>
<body>
    <div id="loader" class="loader-wrapper animate__animated animate__fadeOut animate__delay-3s">
        <div class="loader-box">
            <div class="icon">
            <img src="img/logo.svg" />
            </div>
        </div>
    </div>

    <video autoplay muted loop id="myVideo">
      <source src="img/video_show.mp4" type="video/mp4">
    </video>

    <div id="page" class="animate__animated animate__fadeIn"> <!-- animate__delay-4s -->
      <input type="checkbox" id="active">
      <label for="active" class="menu-btn"><i class="fas fa-bars"></i></label>
      <div class="wrapper">
        <ul>
          <li><a href="index.php">HOME</a></li>
          <li><a href="menu.php">SPEISEKARTE</a></li>
          <li><a href="reservierung.php">RESERVIERUNG</a></li>
          <li><a href="anfahrt.php">ANFAHRT</a></li>
        </ul>
      </div>
      
        <div class="load_page">
            <nav id="navigation">
                <a href="menu.php">SPEISEKARTE</a>
                <a href="reservierung.php">RESERVIERUNG</a>
                <a href="anfahrt.php">ANFAHRT</a>
            </nav>
            <h1>HERZLICH WILLKOMMEN IM RESTAURANT<br/><span class="name">&bull; EL PASO &bull;</span></h1>
        </div>

        <div class="about">
            <div class="js-scroll slide-left">
            <h1>ÜBER UNS</h1>
            <?php
					    $query = "SELECT about_text FROM about";
					    $result = mysqli_query($dbc, $query);
					    while($row = mysqli_fetch_array($result)) {
						    echo "<h2 style='white-space: pre-line'>".$row['about_text']."</h2>";
              }
            ?>
            </div>
            <div class="about_gallery js-scroll slide-right">
                <img src="img/rest_1.jpg" class="animated" />
                <img src="img/rest_2.jpg" class="animated about_img_small" />
                <img src="img/rest_4.jpg" class="animated about_img_small about_img_push" />
                <img src="img/rest_5.jpg" class="animated" />
            </div>

            <div class="card js-scroll slide-right">
              <div class="card_part card_part-one"></div>
              <div class="card_part card_part-two"></div>
              <div class="card_part card_part-three"></div>
              <div class="card_part card_part-four"></div>
            </div>
        </div>

        <div class="food">
            <h1>ANGEBOT</h1>
            <hr/>
            <div class="food_wrapper">
                <div class="food_column js-scroll fade-in-bottom">
                    <img src="img/food.jpg" />
                    <h2>Rumpsteak</h2>
                    <h3>16,90 €</h3>
                    <h4>Auch Roastbeef genannt, wird aus dem Zwischenrippenstück mit einem Fettrand geschnitten</h4>
                </div>
                <div class="food_column js-scroll fade-in-bottom">
                    <img src="img/food.jpg" />
                    <h2>Filetsteak</h2>
                    <h3>20,90 €</h3>
                    <h4>Das edelste und zarteste Steak, völlig fett-und sehnenfrei, aus der Ochsenlende</h4>
                </div>
                <div class="food_column js-scroll fade-in-bottom">
                    <img src="img/food.jpg" />
                    <h2>Pfeffersteak “El Paso”</h2>
                    <h3>31,90 €</h3>
                    <h4>Filetsteak mit pikanten und scharfen Madagaskar - Pfefferrahmsauce</h4>
                </div>
                <div class="food_column js-scroll fade-in-bottom">
                    <img src="img/food.jpg" />
                    <h2>Pfefferhüftsteak</h2>
                    <h3>17,90 €</h3>
                    <h4>Hüftsteak in einer pikanten, scharfen Madagaskar Pfefferrahmsauce</h4>
                </div>
              </div>
              <a class="food_btn" href="menu.php">SPEISEKARTE</a>
        </div>

        <div class="service">
          <div class="food_wrapper js-scroll fade-in">
          <!--<p style="font-size:186px;text-align:center;padding-top:50px;padding-bottom:50px">ISKUSTVA</p>-->
          <div class="mySlides">
            <!--<h2><b>Super Service Hervorragende Fleischqualität-Alle begeistert</b></h2>-->
            <h3><i>"Alle waren mit der Qualität unds dem Geschmack super zufrieden. Ich kann nur sagen weiter so, wir kommen gerne wieder!"</i></h3>
            <h4>- Tripadvisor</h4>
          </div>

          <div class="mySlides">
            <!--<h2><b>Tom Tomer</b></h2>-->
            <h3><i>"Ob Rumpsteak, Raznjici oder Schnitzel, alles war gut zubereitet und geschmacklich sehr gut. Portionen sind ordentlich groß. Gerne mal wieder."</i></h3>
            <h4>- Tripadvisor</h4>
          </div>

          <div class="mySlides">
            <!--<h2><b>Tom Tomer</b></h2>-->
            <h3><i>"Die Qualität unserer Speisen war hervorragend und wir freuen uns schon auf das nächste Mal!"</i></h3>
            <h4>- Tripadvisor</h4>
          </div>
          </div>
        </div>

        <footer>
            <div class="footer_section address">
                <h4>HIER FINDEN SIE UNS</h4>
                <p>Herzogstraße 61A<br/>63263 Neu-Isenburg</p>
            </div>
            <div class="footer_section contact">
                <h4>KONTAKT</h4>
                <p>+49 (0)6102 31355</p>
            </div>
            <div class="footer_section hours">
                <h4>ÖFFNUNGSZEITEN</h4>
                <p>MON - SAM &bull; <b>17 - 22:30</b></br>SON &bull; <b>11:30 - 14:30</b> und auch <b>17 - 22:30</b></p>
            </div>
        </footer>
    </div>

    <script type="text/javascript">
    window.setTimeout("closeLoader();", 4500);

    function closeLoader(){
      document.getElementById("loader").style.display=" none";
    }

    $(document).ready(function() {
      $("#page").delay(5000).fadeIn(500);
    });

    $(window).scroll(function(){
        if ($(this).scrollTop() > 0) {
        $('#navigation').addClass('change_color');
        } else {
        $('#navigation').removeClass('change_color');
        }
    });

    const scrollElements = document.querySelectorAll(".js-scroll");
    const elementInView = (el, dividend = 1) => {
      const elementTop = el.getBoundingClientRect().top;
      return (
        elementTop <=
        (window.innerHeight || document.documentElement.clientHeight) / dividend
      );
    };

    const elementOutofView = (el) => {
      const elementTop = el.getBoundingClientRect().top;

      return (
        elementTop > (window.innerHeight || document.documentElement.clientHeight)
      );
    };

    const displayScrollElement = (element) => {
      element.classList.add("scrolled");
    };

    const hideScrollElement = (element) => {
      element.classList.remove("scrolled");
    };

    const handleScrollAnimation = () => {
      scrollElements.forEach((el) => {
        if (elementInView(el, 1.25)) {
          displayScrollElement(el);
        } else if (elementOutofView(el)) {
          hideScrollElement(el)
        }
      })
    }

    window.addEventListener("scroll", () => { 
      handleScrollAnimation();
    });

    var slideIndex = 0;
    carousel();

    function carousel() {
      var i;
      var x = document.getElementsByClassName("mySlides");
      for (i = 0; i < x.length; i++) {
        x[i].style.display = "none"; 
      }
      slideIndex++;
      if (slideIndex > x.length) {slideIndex = 1} 
      x[slideIndex-1].style.display = "block"; 
      setTimeout(carousel, 5000); 
    }
    </script>
</body>