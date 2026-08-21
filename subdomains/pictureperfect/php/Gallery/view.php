<?php
session_start();
require "../registration/dbcon.php";
 ?>
<!DOCTYPE html>
<html>
<head>
    <!-- fav-icon -->
    <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
    <!-- loading scrren css -->
    <link rel="stylesheet" href="../../css/Loader.css" />
      <!-- loading screen js -->
  <script src="../../js/PreLoader.js" defer></script>
	<title>Gallery 📷</title>
	<style>
  :root {
  font-size: 62.5%;
  --website-background-default-color: rgb(0, 0, 0);
  --website-text-default-color: white;
  --black: black;
  --linear-grad: linear-gradient(#000000, var(--changing-text-color));
  --nav-item-color: rgb(255, 255, 255);
  --main-image-height: 90vh;
  --main-heading-font-size: 4rem;
  --nav-components: 2.2rem;
  --changing-text-color: #f8b704;
  --changing-text-size: 5rem;
  --border: 0.1rem;
  --border-radius: 0.5rem;
  --secondary-headings: 3rem;
  --special-margin: 5rem;
  --aspect-ratio: 16/9;
  --font-itim: "Itim", cursive;
  --font-currsive: "Dancing Script", cursive;
  --font-Nunito: "Nunito", sans-serif;
  --width-100vw: 100vw;
  --letter-spacing: 5px;
  --gap: 1vmin;
  --text-size: 3rem;
  --word-spacing: 0.5rem;
  --medium: medium;
}
body{
  background-color: #9f1f69;
  background-image: linear-gradient(225deg, #9f1f69 0%, black 50%, #2B86C5 100%);
  overflow-x: hidden;
}

/* scrollbar design code here */
body::-webkit-scrollbar {
  width: 0.3rem;
}

body::-webkit-scrollbar-track {
  background-color: var(--changing-text-color);
}

body::-webkit-scrollbar-thumb {
  background-color: rgb(45, 85, 104);
  border-radius: var(--border-radius);
}

a{
  text-decoration: none;
  color: white;
}

  nav {
  position: absolute;
  top: 0;
  left: 0;
  width: var(--width-100vw);
  display: flex;
  justify-content: space-around;
  z-index: 2;
}

nav h1 {
  cursor: pointer;
  background-image: linear-gradient(
    45deg,
    rgb(255, 255, 255),
    rgb(255, 255, 255)
  );
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  font-family: "Merienda", cursive;
  font-size: var(--main-heading-font-size);
  z-index: 2;
}

nav ul {
  width: 60vw;
  list-style-type: none;
  display: flex;
  justify-content: space-around;
  align-items: center;
  font-size: var(--nav-components);
}

nav ul li {
  color: var(--nav-item-color);
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  position: relative;
}

nav h1:hover {
  background-image: linear-gradient(
    45deg,
    rgb(255, 255, 255),
    var(--changing-text-color)
  );
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

nav ul li::after {
  content: "";
  width: 0;
  height: 0;
  position: absolute;
  top: 100%;
  left: 50%;
  background-color: var(--changing-text-color);
  opacity: 0;
  transition: all 0.2s ease-in-out;
}

nav ul li:hover::after {
  content: "";
  width: 98%;
  height: 0.1rem;
  transform: translateX(-50%);
  background-color: var(--changing-text-color);
  opacity: 1;
}

nav ul li a:hover {
  color: var(--changing-text-color);
  transform: scale(1.1, 1.1);
}

.alb {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  margin:12rem 3rem;
  grid-gap: 10px;
}

.alb .image-container {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 100%; /* Set the aspect ratio here (1:1 in this example) */
  overflow: hidden;
  border:.5px solid black;
  transition: all .5s ease-in-out;
}

.alb .image-container img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover; /* Scale the image to cover the container */
  transition: all 0.5s ease-in-out;

}

.alb .image-container:hover{
  border:.5px solid gold;
}

.alb .image-container img:hover{
	transform: scale(1.01);
}
footer {
  display: flex;
  flex-flow: column;
  justify-content: center;
  align-items: center;
  margin-top: 10rem;
  height: 20vh;
  background-color: gold;
  position: relative;
}

footer > div {
  display: flex;
  justify-content: center;
}

.logo {
  max-width: clamp(200px, 30%, 30vw);
  height: auto;
}

.footer-href-design{
  font-size: 2rem;
  background-color: #0000004b;
  padding: .5rem 2rem;
  border-radius: 10px;
  transition: all 0.2s ease-in-out;
}


.footer-href-design:hover{
  background-color: #0000009d;
}

.blog{
  position: absolute;
  top: 5%;
  left: 5%;
}

.contact-us{
  position: absolute;
  top: 5%;
  right: 5%;
}


footer .footer-content {
  color: black;
  font-size: medium;
  margin: 0 0.5rem;
}
</style>
</head>
<body>
    <!-- loader html -->
    <div class="loader"></div>
  <!-- Navbar -->
  <nav>
  <h1>Picture Perfect</h1>
    <ul class="nav-bar-big">
      <li>
        <a href="../../index.php">Home</a>
      </li>
      <li>
        <a href="../review/review.php">Review</a>
      </li>
      <li>
        <a href="../bookMe/bookMe.php">Book Me</a>
      </li>
	  <?php
	 	if(isset($_SESSION['email'])){
			echo ' <li>
			<a href="../registration/logout.php">logout</a>
		  </li>';
		}else{
			echo '<li>
			<a href="../registration/login.php">login</a>
		  </li>';
		}
	  ?>
    </ul>
  </nav>
<div class="alb">
<div class="image-container">
<img src="../../media/images/Header/image1.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image2.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image3.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image4.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image15.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image16.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image17.jpg">
</div>
<div class="image-container">
<img src="../../media/images/Header/image18.jpg">
</div>
     <?php 
          $sql = "SELECT * FROM gallery ORDER BY id DESC";
          $res = mysqli_query($conn,  $sql);

          if (mysqli_num_rows($res) > 0) {
          	while ($images = mysqli_fetch_assoc($res)) {  ?>
             
             <div class="image-container">
             	<img src="../../media/images/gallery/<?=$images['image_url']?>">
				 </div>
    <?php } }?>
			
	</div>	
	  <!-- footer section of home page -->
	  <footer>
    <div>
      <img class="logo" src="../../media/images/Header/logo.png" alt="picture perfect logo" />
    </div>
    <div>
      <a class="footer-href-design blog" href="../../html/All-Blog.html"><i class="fa fa-question"></i> Faq's</a>
    </div>
    <div>
      <a class="footer-href-design contact-us" href="../../html/contact.html"><i class="fa fa-phone"></i> Contact us</a>
    </div>
    <div class="footer-content">
      &copy; Copyright 2022-2023 by Refsnes Data. All Rights Reserved. Picture
      Perfect is Powered by Perfect.corp.
    </div>
  </footer>
  <script>
    
  </script>
</body>
</html>