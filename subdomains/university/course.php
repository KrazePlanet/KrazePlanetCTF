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
    <title>Courses | World's Biggest University</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="./assets/css/main.css">
</head>

<body>

    <!-- ================= HEADER (SAME AS INDEX) ================= -->
    <section class="header" style="min-height: 60vh;">
        <nav>
            <a href="index.php">
                <img src="./assets/images/logo.jpg" class="profile_img" alt="University Logo">
            </a>

            <div class="nav-links">
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                    <li><a href="sports.php">SPORTS</a></li>
                    <li><a href="course.php" class="active">COURSE</a></li>
                    <li><a href="contact.php">CONTACT</a></li>
                    <li><a href="logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>

        <div class="text-box">
            <h1>Our Academic Programs</h1>
            <p>
                Industry-focused courses designed to build skills, knowledge,
                and career readiness.
            </p>
        </div>
    </section>

    <!-- ================= COURSES INTRO ================= -->
    <section class="course">
        <h1>Courses We Offer</h1>
        <p>
            Our programs combine academic excellence with practical exposure,
            preparing students for global careers.
        </p>

        <div class="row">
            <div class="course-col">
                <h3>Undergraduate Programs</h3>
                <p>
                    Comprehensive bachelor’s programs focused on strong foundations,
                    hands-on learning, and industry exposure.
                </p>
            </div>

            <div class="course-col">
                <h3>Postgraduate Programs</h3>
                <p>
                    Advanced master’s programs emphasizing research, innovation,
                    and leadership development.
                </p>
            </div>

            <div class="course-col">
                <h3>Certification Courses</h3>
                <p>
                    Short-term professional courses designed to upgrade skills
                    and enhance employability.
                </p>
            </div>
        </div>
    </section>

    <!-- ================= COURSE CATALOG ================= -->
    <section class="facilities">
        <h1>Popular Courses</h1>
        <p>
            Explore some of our most in-demand academic and professional programs.
        </p>

        <div class="row">
            <div class="facilities-col">
                <img src="./assets/images/Web Development.jpg" alt="Web Development">
                <h3>Web Development</h3>
                <p>
                    Learn HTML, CSS, JavaScript, PHP, and modern frameworks
                    to build dynamic web applications.
                </p>
            </div>

            <div class="facilities-col">
                <img src="./assets/images/data science.jpg" alt="Data Science">
                <h3>Data Science</h3>
                <p>
                    Master data analysis, Python, machine learning, and
                    real-world data-driven problem solving.
                </p>
            </div>

            <div class="facilities-col">
                <img src="./assets/images/Mobile App Development.jpg" alt="Mobile App Development">
                <h3>Mobile App Development</h3>
                <p>
                    Build Android and iOS apps using Flutter, Kotlin,
                    Swift, and React Native.
                </p>
            </div>

            <div class="facilities-col">
                <img src="./assets/images/Digital Marketing Course.jpg" alt="Digital Marketing">
                <h3>Digital Marketing</h3>
                <p>
                    Learn SEO, social media marketing, content strategy,
                    and online brand building.
                </p>
            </div>

            <div class="facilities-col">
                <img src="./assets/images/Graphic Design.png" alt="Graphic Design">
                <h3>Graphic Design</h3>
                <p>
                    Develop creativity with design principles, typography,
                    and tools like Photoshop and Illustrator.
                </p>
            </div>

            <div class="facilities-col">
                <img src="./assets/images/Photography.jpeg" alt="Photography">
                <h3>Photography</h3>
                <p>
                    Learn composition, lighting techniques, camera handling,
                    and professional photo editing.
                </p>
            </div>
        </div>
    </section>

    <!-- ================= CTA ================= -->
    <section class="cta">
        <h1>
            Build Your Career With the Right Course<br>
            Start Learning Today
        </h1>
        <a href="contact.php" class="hero-btn">ENROLL NOW</a>
    </section>

    <!-- ================= FOOTER (UPDATED, SAME EVERYWHERE) ================= -->
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