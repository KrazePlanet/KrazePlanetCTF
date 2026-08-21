<?php
session_start();
include("login1.php");

// Check if form is submitted
if(isset($_POST['submit'])){
    // Prevent SQL Injection
    $u = mysqli_real_escape_string($con, $_POST['username']);
    $p = mysqli_real_escape_string($con, $_POST['password']);
    
    // Use proper prepared statement for better security
    $stmt = $con->prepare("SELECT * FROM userlogin WHERE uname=? AND pname=?");
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;
    
    if($count == 1){
        // Successful login
        $_SESSION['username'] = $u;
        header('Location: admin_home.php');
        exit();
    } else {
        // Failed login
        $err_m = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2b2d42;
            --secondary-color: #1a1a2e;
            --accent-color: #d90429;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #38b000;
            --error-color: #ef233c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            width: 90%;
            max-width: 900px;
            display: flex;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .welcome-section p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .feature-list {
            margin-top: 2rem;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .feature-list i {
            margin-right: 0.8rem;
            color: var(--accent-color);
        }
        
        .login-section {
            background-color: white;
            padding: 2.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-section h2 {
            color: var(--dark-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .error {
            background-color: rgba(239, 35, 60, 0.1);
            color: var(--error-color);
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .input-group i {
            position: absolute;
            right: 12px;
            bottom: 12px;
            color: #adb5bd;
        }
        
        .input-group input:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
        }
        
        .login-btn {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }
        
        .login-btn:hover {
            background-color: var(--accent-color);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .security-note {
            margin-top: 1.5rem;
            padding: 0.8rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #6c757d;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 2rem 0;
            }
            
            .welcome-section {
                padding: 2rem;
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .login-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <h1>Admin Portal</h1>
            <p>Welcome to the administrative control panel. Please log in to access system management tools and settings.</p>
            
            <ul class="feature-list">
                <li><i class="fas fa-users-cog"></i> Manage user accounts</li>
                <li><i class="fas fa-database"></i> Access system databases</li>
                <li><i class="fas fa-chart-line"></i> View analytics and reports</li>
                <li><i class="fas fa-shield-alt"></i> Security and permissions</li>
            </ul>
        </div>
        
        <div class="login-section">
            <h2>Administrator Login</h2>
            
            <?php 
            if(isset($err_m)) { 
                echo "<div class='error'><i class='fas fa-exclamation-circle'></i> $err_m</div>"; 
            } 
            ?>
            
            <form action="admin_login.php" method="post">
                <div class="input-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    <i class="fas fa-user-shield"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" name="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Secure Login
                </button>
            </form>
            
            <div class="security-note">
                <i class="fas fa-info-circle"></i> This portal is for authorized administrators only. All login attempts are monitored and logged.
            </div>
        </div>
    </div>
</body>
</html>