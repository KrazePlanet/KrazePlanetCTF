<?php
require_once __DIR__ . '/database.php';
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University website</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>

    <section class="header">
        <nav>
            <a href="index.php">
                <img src="assets/images/logo.jpg" class="profile_img" alt="University Logo">
            </a>

            <div class="nav-links">
                <ul>
                    <li><a href="index.php" class="active">HOME</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                    <li><a href="Sports.php">SPORTS</a></li>
                    <li><a href="course.php">COURSE</a></li>
                    <li><a href="contact.php">CONTACT</a></li>
                    <li><a href="logout.php">LOGOUT</a></li>

                </ul>
            </div>
        </nav>

        <div class="text-box">
            <h1>World's Biggest University</h1>
            <p>
                Making a website is now one of the easiest things in the world.
                You just need to learn HTML, CSS, and JavaScript.
            </p>
            <a href="index.php" class="hero-btn">Visit Us To Know More</a>
        </div>
    </section>

    <section class="course">
        <h1>Courses we offer</h1>
        <p>Explore our range of courses designed to help you succeed in your academic journey.</p>

        <div class="row">
            <div class="course-col">
                <h3>Intermediate</h3>
                <p>Prepare yourself with foundational knowledge and skills for advanced studies.</p>
            </div>

            <div class="course-col">
                <h3>Degree</h3>
                <p>Earn your degree in specialized fields with our comprehensive degree programs.</p>
            </div>
        </div>
    </section>

    <section class="campus">
        <h1>Our Global Campus</h1>
        <p>Experience our diverse and vibrant campuses across the globe.</p>

        <div class="row">
            <div class="campus-col">
                <img src="assets/images/LONDON.jpg" alt="London Campus">
                <div class="layer">
                    <h3>LONDON</h3>
                </div>
            </div>

            <div class="campus-col">
                <img src="assets/images/Tokyo.jpg" alt="Tokyo Campus">
                <div class="layer">
                    <h3>Tokyo</h3>
                </div>
            </div>

            <div class="campus-col">
                <img src="assets/images/Delhi.jpg" alt="Delhi Campus">
                <div class="layer">
                    <h3>Delhi</h3>
                </div>
            </div>
        </div>
    </section>

    <section class="facilities">
        <h1>Our Facilities</h1>
        <p>Discover our top-notch facilities designed to support your academic and extracurricular needs.</p>

        <div class="row">
            <div class="facilities-col">
                <img src="assets/images/library.jpg" alt="Library">
                <h3>World Class Library</h3>
                <p>Access a vast collection of resources for your research and study needs.</p>
            </div>

            <div class="facilities-col">
                <img src="assets/images/play.jpg" alt="Playground">
                <h3>Largest Play Ground</h3>
                <p>Engage in recreational activities and sports in our expansive playground areas.</p>
            </div>

            <div class="facilities-col">
                <img src="assets/images/food.jpg" alt="Cafeteria">
                <h3>Tasty and Healthy Food</h3>
                <p>Enjoy nutritious and delicious meals prepared by our expert chefs.</p>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <h1>What Our Students Say</h1>
        <p>Read testimonials from our satisfied students.</p>

        <div class="row">
            <div class="testimonial-col">
                <img src="assets/images/s1.jpg" alt="Student 1">
                <div>
                    <p>"Best University for Computer Engineering."</p>
                    <h3>Lila</h3>
                </div>
            </div>

            <div class="testimonial-col">
                <img src="assets/images/s2.jpg" alt="Student 2">
                <div>
                    <p>"Great learning environment and supportive faculty."</p>
                    <h3>Hettik Patel</h3>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <h1>
            Enroll For Our Various Online Courses<br>
            Anywhere From The World
        </h1>
        <a href="contact.php" class="hero-btn">CONTACT US</a>
    </section>

    <!-- ================= FOOTER  ================= -->
    <section class="footer">
        <h4>World's Biggest University</h4>

        <p>
            Empowering students through education, innovation, and excellence.
            Building future leaders with knowledge, skills, and values.
        </p>

        <p>
            © <?php echo date("Y"); ?> World's Biggest University.
            All Rights Reserved.
        </p>

        <p>
            Designed & Developed by
            <strong>Modasiya Jaydip</strong>
        </p>
    </section>

</body>

</html>