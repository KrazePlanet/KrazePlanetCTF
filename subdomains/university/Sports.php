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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports | World's Biggest University</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>

    <!-- ================= HEADER (SAME AS INDEX) ================= -->
    <section class="header" style="min-height: 60vh;">
        <nav>
            <a href="index.php">
                <img src="assets/images/logo.jpg" class="profile_img" alt="University Logo">
            </a>

            <div class="nav-links">
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                    <li><a href="sports.php" class="active">SPORTS</a></li>
                    <li><a href="course.php">COURSE</a></li>
                    <li><a href="contact.php">CONTACT</a></li>
                    <li><a href="logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>

        <div class="text-box">
            <h1>Sports & Athletics</h1>
            <p>
                Encouraging teamwork, discipline, and excellence through sports.
            </p>
        </div>
    </section>

    <!-- ================= SPORTS OVERVIEW ================= -->
    <section class="course">
        <h1>Our Sports Culture</h1>
        <p>
            We believe sports are an essential part of student development, promoting
            physical fitness, leadership, and team spirit.
        </p>

        <div class="row">
            <div class="course-col">
                <h3>Indoor Sports</h3>
                <p>
                    Chess, Table Tennis, Badminton, Carrom and other indoor games
                    to sharpen focus and strategy.
                </p>
            </div>

            <div class="course-col">
                <h3>Outdoor Sports</h3>
                <p>
                    Cricket, Football, Volleyball, Athletics and more to build
                    endurance and teamwork.
                </p>
            </div>

            <div class="course-col">
                <h3>Annual Sports Meet</h3>
                <p>
                    A grand sports festival featuring inter-department competitions,
                    awards, and celebrations.
                </p>
            </div>
        </div>
    </section>

    <!-- ================= SPORTS CAMPUSES / GROUNDS ================= -->
    <section class="campus">
        <h1>Sports Grounds</h1>
        <p>World-class sports infrastructure across our campuses.</p>

        <div class="row">
            <div class="campus-col">
                <img src="assets/images/LONDON.jpg" alt="London Sports Ground">
                <div class="layer">
                    <h3>LONDON</h3>
                </div>
            </div>

            <div class="campus-col">
                <img src="assets/images/Tokyo.jpg" alt="Tokyo Sports Ground">
                <div class="layer">
                    <h3>TOKYO</h3>
                </div>
            </div>

            <div class="campus-col">
                <img src="assets/images/Delhi.jpg" alt="Delhi Sports Ground">
                <div class="layer">
                    <h3>DELHI</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= SPORTS FACILITIES ================= -->
    <section class="facilities">
        <h1>Sports Facilities</h1>
        <p>Modern infrastructure supporting professional training and recreation.</p>

        <div class="row">
            <div class="facilities-col">
                <img src="assets/images/play.jpg" alt="Playground">
                <h3>Largest Playground</h3>
                <p>
                    Spacious and well-maintained playgrounds for multiple sports
                    and athletic activities.
                </p>
            </div>

            <div class="facilities-col">
                <img src="assets/images/gym.jpg" alt="Gymnasium">
                <h3>Modern Gymnasium</h3>
                <p>
                    Fully equipped gym with professional trainers for fitness
                    and strength conditioning.
                </p>
            </div>

            <div class="facilities-col">
                <img src="assets/images/stadium.jpg" alt="Stadium">
                <h3>Sports Stadium</h3>
                <p>
                    Large stadium for hosting tournaments, matches, and
                    university-level competitions.
                </p>
            </div>
        </div>
    </section>

    <!-- ================= CTA ================= -->
    <section class="cta">
        <h1>
            Train Hard, Play Fair,<br>
            Achieve Excellence
        </h1>
        <a href="contact.php" class="hero-btn">CONTACT SPORTS DEPARTMENT</a>
    </section>

    <!-- ================= FOOTER (SAME AS INDEX) ================= -->
    <section class="footer">
        <h4>© <?php echo date("Y"); ?> World's Biggest University</h4>

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