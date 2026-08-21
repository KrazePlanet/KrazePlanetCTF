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
        body {
            background-image: none;
        }
    </style>
</head>
<body>
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

        <nav id="navigation">
            <a href="index.php">HOME</a>
            <a href="menu.php" class="active_menu">SPEISEKARTE</a>
            <a href="reservierung.php">RESERVIERUNG</a>
            <a href="anfahrt.php">ANFAHRT</a>
        </nav>
    </div>

    <script type="text/javascript">
    $(window).scroll(function(){
        if ($(this).scrollTop() > 0) {
        $('#navigation').addClass('change_color');
        } else {
        $('#navigation').removeClass('change_color');
        }
    });
    </script>
</body>