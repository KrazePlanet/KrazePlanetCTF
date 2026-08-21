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
    <title>About Us | World's Biggest University</title>

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
                    <li><a href="about.php" class="active">ABOUT</a></li>
                    <li><a href="Sports.php">SPORTS</a></li>
                    <li><a href="course.php">COURSE</a></li>
                    <li><a href="contact.php">CONTACT</a></li>
                    <li><a href="logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>

        <div class="text-box">
            <h1>About Our University</h1>
            <p>
                Learn more about our journey, mission, and commitment to academic excellence.
            </p>
        </div>
    </section>

    <!-- ================= ABOUT CONTENT ================= -->
    <section class="course">
        <h1>Who We Are</h1>
        <p>
            A global institution shaping the future through education, research, and innovation.
        </p>

        <div class="row">
            <div class="course-col">
                <h3>Our History</h3>
                <p>
                    World's Biggest University is a renowned institution dedicated to providing
                    high-quality education to students across the globe. With a legacy spanning
                    decades, we have consistently delivered academic excellence and innovation.
                </p>
            </div>

            <div class="course-col">
                <h3>Our Mission</h3>
                <p>
                    Our mission is to empower individuals through education, research, and
                    innovation. We nurture critical thinking, creativity, and leadership to
                    prepare students for global challenges.
                </p>
            </div>

            <div class="course-col">
                <h3>Our Vision</h3>
                <p>
                    We envision a future where education transcends boundaries, enabling
                    students to become responsible global citizens and leaders in their
                    respective fields.
                </p>
            </div>
        </div>
    </section>

    <!-- ================= CTA ================= -->
    <section class="cta">
        <h1>
            Join a Community of Learners<br>
            Shaping the Future
        </h1>
        <a href="contact.php" class="hero-btn">CONTACT US</a>
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