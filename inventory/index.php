<?php
// ============================================================
// SQL Injection Lab 104 - Time-based Blind SQLi
// Vulnerability: SLEEP() based time delay injection
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab104Database($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS lab104 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL,
        password VARCHAR(30) NOT NULL,
        user_agent VARCHAR(255) NOT NULL
    )";
    mysqli_query($conn, $sql);
    
    $check = mysqli_query($conn, "SELECT * FROM lab104 LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab104 (username, password, user_agent) VALUES 
            ('user1', 'password1', 'Mozilla/5.0'),
            ('user2', 'password2', 'Firefox/54.0'),
            ('user3', 'password3', 'Chrome/51.0')");
    }
}
initializeLab104Database($conn);

// --- Handle Search ---
$result = null;
$searchTime = 0;
$username = $_GET['username'] ?? '';

if ($username !== '') {
    $start = microtime(true);
    // ===================================================================
    // VULNERABLE: Time-based blind SQLi with SLEEP()
    // Payload: ' AND SLEEP(5)-- -  (delays 5 seconds if true)
    // Payload: ' AND IF(SUBSTRING(password,1,1)='a',SLEEP(5),0)-- -
    // ===================================================================
    $sql = "SELECT * FROM lab104 WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    $searchTime = microtime(true) - $start;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time-based Blind SQL Injection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root { --primary-dark: #1a1f36; --accent-green: #48bb78; --accent-red: #f56565; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0; min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar { background: rgba(26, 31, 54, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .navbar-brand { color: var(--accent-green) !important; font-weight: 600; }
        .hero-section {
            background: linear-gradient(135deg, rgba(72,187,120,0.1) 0%, rgba(66,153,225,0.1) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 2rem 0; margin-bottom: 2rem;
            text-align: center;
        }
        .hero-title { font-size: 1.8rem; font-weight: 700; color: white; text-align: center;}
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
        }
        .card-header {
            background: rgba(26, 31, 54, 0.8);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white; font-weight: 600;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255,255,255,0.2);
            color: #e2e8f0;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: var(--accent-green);
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(72, 187, 120, 0.25);
        }
        .btn-primary { background: var(--accent-green); border: none; }
        .vuln-badge { background: rgba(245,101,101,0.2); color: #f56565; border: 1px solid rgba(245,101,101,0.3); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .code-block {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(245, 101, 101, 0.3);
            border-radius: 8px;
            padding: 0.75rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #f56565;
        }
        .timer-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .timer-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-green);
        }
        footer {
            background: rgba(26, 31, 54, 0.8);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1.5rem 0; margin-top: 3rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../">
                <i class="bi bi-arrow-left me-2"></i>Back to Labs
            </a>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title">SQL Injection Lab 104</h1>
            <p class="hero-subtitle">Time-based Blind SQL Injection</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-search me-2"></i>Search User</span>
                    </div>
                    <div class="card-body">
                        <form method="get">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter username...">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($result): ?>
                <div class="card">
                    <div class="card-header"><i class="bi bi-person me-2"></i>Results</div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="mb-3 pb-3 border-bottom border-secondary">
                                <strong>Username:</strong> <?php echo htmlspecialchars($row['username']); ?><br>
                                <strong>Password:</strong> <?php echo htmlspecialchars($row['password']); ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No users found</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 VexiumCTF. SQL Injection Lab 104.</p>
        </div>
    </footer>
</body>
</html>
