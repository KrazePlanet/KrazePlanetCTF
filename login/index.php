<?php
// ============================================================
// SQL Injection Lab 101 - Single File
// Vulnerability: SQL Injection in email field (line ~85)
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

// Connect without database first
$conn = mysqli_connect($server, $username, $password);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");

// Select the database
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab101Database($conn) {
    // Create lab101 table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS lab101 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )";
    mysqli_query($conn, $sql);
    
    // Check if admin exists
    $check = mysqli_query($conn, "SELECT * FROM lab101 WHERE email = 'admin@kzlabs.local'");
    if (mysqli_num_rows($check) == 0) {
        // Insert default admin user
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO lab101 (full_name, email, password) VALUES 
            ('Administrator', 'admin@kzlabs.local', '$hash')");
    }
}
initializeLab101Database($conn);

// --- Session Management ---
session_start();

// --- Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- Registration ---
$authError = '';
$authSuccess = '';
$authView = $_GET['view'] ?? 'login';

if (isset($_POST['register'])) {
    $fullName = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $passRepeat = $_POST['repeat_password'] ?? '';
    
    $errors = [];
    
    if (empty($fullName) || empty($email) || empty($pass) || empty($passRepeat)) {
        $errors[] = "All fields are required";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid";
    }
    if (strlen($pass) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($pass !== $passRepeat) {
        $errors[] = "Password does not match";
    }
    
    // Check if email exists
    $sql = "SELECT * FROM lab101 WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already exists!";
    }
    
    if (count($errors) > 0) {
        $authError = implode("<br>", $errors);
        $authView = 'register';
    } else {
        // Insert new user (secure parameterized query)
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO lab101 (full_name, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $fullName, $email, $hash);
            mysqli_stmt_execute($stmt);
            $authSuccess = "You are registered successfully. Please login.";
            $authView = 'login';
        } else {
            $authError = "Something went wrong";
            $authView = 'register';
        }
    }
}

// --- Login (VULNERABLE TO SQL INJECTION) ---
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ===================================================================
    // VULNERABLE SQL QUERY - Direct interpolation of user input
    // SQL Injection payloads to test:
    //   ' OR '1'='1
    //   ' OR 1=1 -- -
    //   ' UNION SELECT * FROM lab101 -- -
    // ===================================================================
    $sql = "SELECT * FROM lab101 WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["user"] = $user["email"];
            $_SESSION["full_name"] = $user["full_name"];
            header("Location: index.php");
            exit();
        } else {
            $authError = "Password does not match";
        }
    } else {
        $authError = "Email does not match";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Injection - Login Bypass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-dark: #1a1f36;
            --accent-green: #48bb78;
            --accent-blue: #4299e1;
            --accent-orange: #ed8936;
            --accent-red: #f56565;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: rgba(26, 31, 54, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .navbar-brand {
            color: var(--accent-green) !important;
            font-weight: 600;
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1) 0%, rgba(66, 153, 225, 0.1) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .hero-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .hero-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .container {
            text-align: center;
        }
        
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background: rgba(26, 31, 54, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 600;
        }
        
        .form-control {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #e2e8f0;
        }
        
        .form-control:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: var(--accent-green);
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(72, 187, 120, 0.25);
        }
        
        .btn-primary {
            background: var(--accent-green);
            border: none;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #38a169;
        }
        
        .btn-outline-primary {
            border-color: var(--accent-green);
            color: var(--accent-green);
        }
        
        .btn-outline-primary:hover {
            background: var(--accent-green);
            color: white;
        }
        
        .alert-danger {
            background: rgba(245, 101, 101, 0.2);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: #f56565;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid rgba(72, 187, 120, 0.3);
            color: #48bb78;
        }
        
        .vuln-badge {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
            border: 1px solid rgba(245, 101, 101, 0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .payload-example {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(245, 101, 101, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
        }
        
        .payload-example code {
            color: #f56565;
            font-size: 0.95rem;
        }
        
        .code-block {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            color: #f56565;
            font-size: 0.9rem;
        }
        
        .nav-link {
            color: #94a3b8 !important;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--accent-green) !important;
        }
        
        footer {
            background: rgba(26, 31, 54, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 0;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../">
                <i class="bi bi-arrow-left me-2"></i>Back to Labs
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../contact">Contact Us</a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?action=logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title">SQL Injection Lab 101</h1>
            <p class="hero-subtitle">Authentication Bypass via SQL Injection</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['user'])): ?>
            <!-- DASHBOARD VIEW -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-person-circle me-2"></i>User Dashboard
                        </div>
                        <div class="card-body text-center">
                            <h3 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</h3>
                            <p class="text-muted">You have successfully logged in.</p>
                            <p class="text-muted">Email: <?php echo htmlspecialchars($_SESSION['user']); ?></p>
                            <a href="?action=logout" class="btn btn-warning">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- LOGIN/REGISTER VIEW -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <!-- Alerts -->
                    <?php if ($authError): ?>
                        <div class="alert alert-danger"><?php echo $authError; ?></div>
                    <?php endif; ?>
                    <?php if ($authSuccess): ?>
                        <div class="alert alert-success"><?php echo $authSuccess; ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <?php if ($authView === 'login'): ?>
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                <?php else: ?>
                                    <i class="bi bi-person-plus me-2"></i>Register
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($authView === 'login'): ?>
                                <!-- LOGIN FORM -->
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="text" name="email" class="form-control" placeholder="Enter email...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Enter password...">
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="login" class="btn btn-primary">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                        </button>
                                    </div>
                                </form>
                                <hr class="my-4">
                                <p class="text-center mb-0">
                                    Not registered? <a href="?view=register" class="text-decoration-none" style="color: var(--accent-green);">Register here</a>
                                </p>
                            <?php else: ?>
                                <!-- REGISTER FORM -->
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="fullname" class="form-control" placeholder="Enter full name...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="Enter email...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Enter password (min 8 chars)...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="repeat_password" class="form-control" placeholder="Confirm password...">
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="register" class="btn btn-primary">
                                            <i class="bi bi-person-plus me-2"></i>Register
                                        </button>
                                    </div>
                                </form>
                                <hr class="my-4">
                                <p class="text-center mb-0">
                                    Already registered? <a href="?view=login" class="text-decoration-none" style="color: var(--accent-green);">Login here</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Default Credentials -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="bi bi-info-circle me-2"></i>Default Credentials
                        </div>
                        <div class="card-body">
                            <p class="mb-2">A default admin user exists for testing:</p>
                            <ul class="list-unstyled mb-0">
                                <li><strong>Email:</strong> admin@kzlabs.local</li>
                                <li><strong>Password:</strong> admin123</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 VexiumCTF. SQL Injection Lab 101.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
