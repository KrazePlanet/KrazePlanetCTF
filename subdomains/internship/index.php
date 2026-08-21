<?php
session_start();
require_once __DIR__ . '/database.php';

// Route if already logged in
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] == "admin") {
        header("Location: php/admin.php");
    } elseif ($_SESSION["role"] == "student") {
        header("Location: php/student_dashboard.php");
    } elseif ($_SESSION["role"] == "company") {
        header("Location: php/company_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/index.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 20px;
        }
        .main-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }
        .brand-icon {
            font-size: 48px;
            color: #3b82f6;
            margin-bottom: 20px;
        }
        .title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #f8fafc;
        }
        .subtitle {
            font-size: 1rem;
            color: #94a3b8;
            margin-bottom: 32px;
        }
        .btn-group-custom {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-custom {
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-login {
            background: #3b82f6;
            color: #fff;
            border: none;
        }
        .btn-login:hover {
            background: #2563eb;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
        }
        .btn-register {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-register:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateY(-2px);
        }
        .credentials-card {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 10px;
            padding: 16px;
            margin-top: 32px;
            text-align: left;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .credentials-card h6 {
            color: #3b82f6;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .cred-item {
            display: flex;
            justify-content: space-between;
            color: #cbd5e1;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .cred-item:last-child { border-bottom: none; }
        .cred-item code {
            color: #60a5fa;
            background: rgba(59,130,246,0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="brand-icon">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h1 class="title">Internship Management Portal</h1>
        <p class="subtitle">Connect aspiring students with high-growth companies and manage applications seamlessly.</p>
        
        <div class="btn-group-custom">
            <a href="php/login.php" class="btn-custom btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
            <a href="php/registration.php" class="btn-custom btn-register">
                <i class="fa-solid fa-user-plus"></i> Register
            </a>
        </div>

        <div class="credentials-card">
            <h6><i class="fa-solid fa-key me-1"></i> Demo Credentials (Password: <code>admin</code>)</h6>
            <div class="cred-item">
                <span>🛡️ Admin Portal:</span>
                <code>admin@internship.com</code>
            </div>
            <div class="cred-item">
                <span>🏢 Company Portal:</span>
                <code>company@gmail.com</code>
            </div>
            <div class="cred-item">
                <span>🎓 Student Portal:</span>
                <code>test@example.com</code>
            </div>
        </div>
    </div>
</body>
</html>
