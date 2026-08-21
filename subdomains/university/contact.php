<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name && $email && $message) {
        $stmt = $conn->prepare(
            "INSERT INTO contact (name, email, message) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            $success = "Thank you! Your message has been sent successfully.";
        } else {
            $error = "Database error. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact Us | World's Biggest University</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts + Main CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- 🔴 INLINE FIXES (IMPORTANT) -->
    <style>
        /* force form to be clickable */
        .contact-form-wrapper,
        .contact-form-wrapper * {
            position: relative !important;
            z-index: 50 !important;
            pointer-events: auto !important;
        }

        .contact-form-wrapper {
            background: #ffffff;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        }

        .contact-form-wrapper h3 {
            margin-bottom: 16px;
        }

        .contact-form-wrapper input,
        .contact-form-wrapper textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 14px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .contact-form-wrapper textarea {
            height: 130px;
            resize: none;
        }

        .contact-submit-btn {
            width: 100%;
            padding: 14px;
            background: #0a4da2;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .contact-submit-btn:hover {
            background: #083b7a;
        }

        /* contact info card */
        .contact-info-box {
            background: #ffffff;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
            text-align: left;
        }

        .info-item {
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
        }

        .info-item span {
            font-size: 1.4rem;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <section class="header" style="min-height:60vh;">
        <nav>
            <a href="index.php">
                <img src="assets/images/logo.jpg" class="profile_img" alt="Logo">
            </a>
            <div class="nav-links">
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                    <li><a href="sports.php">SPORTS</a></li>
                    <li><a href="course.php">COURSE</a></li>
                    <li><a href="contact.php" class="active">CONTACT</a></li>
                    <li><a href="logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>

        <div class="text-box">
            <h1>Contact Us</h1>
            <p>We’re here to help you</p>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="course">
        <h1>Get in Touch</h1>
        <p>Send us a message and we’ll respond shortly.</p>

        <?php if ($success): ?>
            <p style="color:green;font-weight:600;"><?php echo $success; ?></p>
        <?php elseif ($error): ?>
            <p style="color:red;font-weight:600;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="row">
            <!-- FORM -->
            <div class="course-col contact-form-wrapper">
                <h3>Send a Message</h3>

                <form method="post">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <textarea name="message" placeholder="Your Message" required></textarea>
                    <button type="submit" class="contact-submit-btn">Send Message</button>
                </form>
            </div>

            <!-- INFO -->
            <div class="course-col contact-info-box">
                <h3>Contact Information</h3>

                <div class="info-item">
                    <span>📍</span>
                    <p><strong>Address</strong><br>Ahmedabad, India</p>
                </div>

                <div class="info-item">
                    <span>📞</span>
                    <p><strong>Phone</strong><br>+91 98765 43210</p>
                </div>

                <div class="info-item">
                    <span>📧</span>
                    <p><strong>Email</strong><br>info@wbu.edu</p>
                </div>

                <div class="info-item">
                    <span>🕒</span>
                    <p><strong>Hours</strong><br>Mon–Fri, 9AM–5PM</p>
                </div>
            </div>
        </div>
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