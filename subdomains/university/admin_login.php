<?php
session_start();
require_once "database.php";

/* If admin already logged in */
if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_id = trim($_POST['admin_id']);
    $password = trim($_POST['password']);

    if ($admin_id && $password) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE admin_id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid Admin ID or Password.";
            }
        } else {
            $error = "Invalid Admin ID or Password.";
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
    <title>Admin Login | World's Biggest University</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- Page UI CSS -->
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
            color: #ffffff;
        }

        .admin-login-box {
            max-width: 440px;
            margin: -80px auto 60px;
            background: #ffffff;
            padding: 34px;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
        }

        .admin-login-box input {
            width: 100%;
            padding: 13px;
            margin-bottom: 14px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        .admin-login-box a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .admin-login-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .error-msg {
            background: #ffe5e5;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
        }

        .admin-section {
            width: 80%;
            margin: auto;
            text-align: center;
            padding: 60px 0;
        }

        .admin-cards {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 40px;
        }

        .admin-card {
            flex-basis: 31%;
            background: #fff3f3;
            padding: 22px;
            border-radius: 12px;
        }

        .admin-card h3 {
            margin-bottom: 10px;
        }

        .security-note {
            width: 70%;
            margin: auto;
            background: #f8f9fa;
            padding: 25px;
            border-left: 5px solid var(--primary);
            margin-bottom: 80px;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <section class="admin-header">
        <div>
            <h1>Admin Login</h1>
            <p>Authorized access only</p>
        </div>
    </section>

    <!-- LOGIN CARD -->
    <div class="admin-login-box">
        <h2 style="text-align:center;">Administrator Login</h2>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="admin_id" placeholder="Admin ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="admin-login-btn">Login</button>
        </form>

        <p style="text-align:center;margin-top:14px;font-size:0.9rem;">
            New admin? <a href="admin_register.php">Register here</a>
        </p>
    </div>

    <!-- EXTRA CONTENT -->
    <section class="admin-section">
        <h1>Administrator Responsibilities</h1>
        <p>
            The admin panel is designed to help university administrators
            manage system operations securely and efficiently.
        </p>

        <div class="admin-cards">
            <div class="admin-card">
                <h3>User Management</h3>
                <p>
                    Monitor registered students and manage user accounts
                    to ensure proper system usage.
                </p>
            </div>

            <div class="admin-card">
                <h3>Contact Messages</h3>
                <p>
                    Review and respond to messages submitted through
                    the Contact Us form.
                </p>
            </div>

            <div class="admin-card">
                <h3>System Maintenance</h3>
                <p>
                    Maintain data integrity, manage content,
                    and keep the platform secure.
                </p>
            </div>
        </div>
    </section>

    <!-- SECURITY NOTICE -->
    <section class="security-note">
        <h3>Security Notice</h3>
        <p>
            All admin activities are logged for security purposes.
            Unauthorized access attempts may result in account suspension
            or legal action as per university policy.
        </p>
    </section>

    <!-- FOOTER -->
    <section class="footer">
        <h4>World's Biggest University</h4>
        <p>© <?php echo date("Y"); ?> World's Biggest University. All Rights Reserved.</p>
        <p>Designed & Developed by <strong>Modasiya Jaydip</strong></p>
    </section>

</body>

</html>