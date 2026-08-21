<?php
session_start();
require_once "database.php";


/* If admin already logged in */
if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}


$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_id = trim($_POST['admin_id']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (!$admin_id || !$password || !$confirm) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if admin already exists
        $check = $conn->prepare("SELECT id FROM admin WHERE admin_id = ?");
        $check->bind_param("s", $admin_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Admin ID already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare(
                "INSERT INTO admin (admin_id, password) VALUES (?, ?)"
            );
            $insert->bind_param("ss", $admin_id, $hashedPassword);

            if ($insert->execute()) {
                $success = "Admin registered successfully. You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Registration | World's Biggest University</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- Inline Admin UI CSS -->
    <style>
        .admin-header {
            min-height: 35vh;
            background:
                linear-gradient(rgba(8, 23, 56, 0.85), rgba(8, 23, 56, 0.85)),
                url("assets/images/bs.jpg") center / cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
        }

        .admin-header h1 {
            font-size: 2.2rem;
            margin-bottom: 6px;
        }

        .admin-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .admin-register-box {
            max-width: 460px;
            margin: -80px auto 80px;
            background: #ffffff;
            padding: 34px;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-register-box h2 {
            text-align: center;
            margin-bottom: 22px;
            font-weight: 600;
        }

        .admin-register-box input {
            width: 100%;
            padding: 13px 14px;
            margin-bottom: 16px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.25s ease;
        }

        .admin-register-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 77, 162, 0.15);
        }

        .admin-register-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .admin-register-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .success-msg {
            background: #e6fffa;
            color: #065f46;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 500;
        }

        .error-msg {
            background: #ffe5e5;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 500;
        }

        .admin-info {
            margin-top: 30px;
            font-size: 0.95rem;
            color: #555;
            line-height: 1.6;
        }

        .admin-info ul {
            padding-left: 20px;
            margin-top: 10px;
        }

        .admin-info a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <!-- ===== HEADER ===== -->
    <section class="admin-header">
        <div>
            <h1>Admin Registration</h1>
            <p>Create a new administrator account</p>
        </div>
    </section>

    <!-- ===== REGISTRATION CARD ===== -->
    <div class="admin-register-box">
        <h2>Register Administrator</h2>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="admin_id" placeholder="Admin ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit" class="admin-register-btn">Register Admin</button>
        </form>

        <!-- ===== LOGIN INFO ===== -->
        <div class="admin-info">
            <hr>

            <h3 style="text-align:center;">Admin Login Information</h3>

            <p>
                This registration page is intended only for authorized university administrators.
                After successful registration, you can log in using the same
                <strong>Admin ID</strong> and <strong>Password</strong>.
            </p>

            <ul>
                <li>Admin credentials are securely stored using password hashing.</li>
                <li>Only registered admins can access the admin dashboard.</li>
                <li>All admin activities are monitored for security.</li>
            </ul>

            <p style="text-align:center; margin-top:16px;">
                Already registered?
                <a href="admin_login.php">Go to Admin Login</a>
            </p>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <section class="footer">
        <h4>World's Biggest University</h4>

        <p>
            Empowering students through education, innovation, and excellence.
        </p>

        <p>
            © <?php echo date("Y"); ?> World's Biggest University. All Rights Reserved.
        </p>

        <p>
            Designed & Developed by <strong>Modasiya Jaydip</strong>
        </p>
    </section>

</body>

</html>