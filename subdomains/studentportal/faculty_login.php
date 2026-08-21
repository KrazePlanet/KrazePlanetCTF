<?php
session_start();
include("login1.php");

// Check if form is submitted
if(isset($_POST['submit'])){
    // Prevent SQL Injection
    $u = mysqli_real_escape_string($con, $_POST['username']);
    $p = mysqli_real_escape_string($con, $_POST['password']);
    
    // Use proper prepared statement for better security
    $stmt = $con->prepare("SELECT * FROM faculty WHERE f_id=? AND f_pass=?");
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;
    
    if($count == 1){
        // Successful login
        $_SESSION['username'] = $u;
        header('Location: faculty_home.php');
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
    <title>Faculty Portal | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2a6f97;
            --secondary-color: #014f86;
            --accent-color: #61a5c2;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #52b788;
            --error-color: #e63946;
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
            color: var(--success-color);
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
            background-color: rgba(230, 57, 70, 0.1);
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
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 111, 151, 0.1);
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
            background-color: var(--secondary-color);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .footer-text a {
            color: var(--primary-color);
            text-decoration: none;
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
            <h1>Faculty Portal</h1>
            <p>Welcome back! Log in to access your teaching dashboard, student records, and course management tools.</p>
            
            <ul class="feature-list">
                <li><i class="fas fa-chalkboard-teacher"></i> Manage your courses</li>
                <li><i class="fas fa-clipboard-list"></i> Track assignments and grades</li>
                <li><i class="fas fa-calendar-alt"></i> Schedule classes and office hours</li>
                <li><i class="fas fa-comment-dots"></i> Communicate with students</li>
            </ul>
        </div>
        
        <div class="login-section">
            <h2>Faculty Sign In</h2>
            
            <?php 
            if(isset($err_m)) { 
                echo "<div class='error'><i class='fas fa-exclamation-circle'></i> $err_m</div>"; 
            } 
            ?>
            
            <form action="faculty_login.php" method="post">
                <div class="input-group">
                    <label for="username">Faculty ID</label>
                    <input type="text" id="username" name="username" placeholder="Enter your faculty ID" required>
                    <i class="fas fa-user-tie"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" name="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>