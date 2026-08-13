<?php
// ============================================================
// SQL Injection Lab 107 - Referer Header Blind SQLi
// Vulnerability: HTTP Referer header SQL injection
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
function initializeLab107Database($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS lab107 (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        Username VARCHAR(50) NOT NULL,
        Referer VARCHAR(255) NOT NULL
    )";
    mysqli_query($conn, $sql);
    
    $check = mysqli_query($conn, "SELECT * FROM lab107 LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab107 (Username, Referer) VALUES 
            ('John Doe', 'http://example.com')");
    }
}
initializeLab107Database($conn);

// --- Process Referer Header ---
$startTime = microtime(true);
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// ===================================================================
// VULNERABLE: Referer header directly in SQL
// Payload: ' OR SLEEP(5)-- -  (use Burp/curl to set Referer header)
// Payload: ' UNION SELECT Username,Password FROM lab107-- -
// ===================================================================
$sql = "SELECT * FROM lab107 WHERE Referer = '$referer'";
$result = mysqli_query($conn, $sql);
$execTime = microtime(true) - $startTime;

$foundUsers = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $foundUsers[] = $row['Username'];
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referer Header Blind SQL Injection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root { --primary-dark: #1a1f36; --accent-green: #48bb78; --accent-red: #f56565; --accent-orange: #ed8936; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0; min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar { background: rgba(26, 31, 54, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .navbar-brand { color: var(--accent-green) !important; font-weight: 600; }
        .hero-section {
            background: linear-gradient(135deg, rgba(237,137,54,0.1) 0%, rgba(245,101,101,0.1) 100%);
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
        .vuln-badge { background: rgba(245,101,101,0.2); color: #f56565; border: 1px solid rgba(245,101,101,0.3); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .header-display {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(237, 137, 54, 0.3);
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
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
        .timer-value { font-size: 2rem; font-weight: 700; color: var(--accent-orange); }
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
            <h1 class="hero-title">SQL Injection Lab 107</h1>
            <p class="hero-subtitle">Referer Header - Blind Time-based SQLi</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-link-45deg me-2"></i>Your Referer Header</span>
                    </div>
                    <div class="card-body">
                        <div class="header-display">
                            <?php echo $referer ? htmlspecialchars($referer) : '<em class="text-muted">No Referer header sent</em>'; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-person me-2"></i>Query Results</div>
                    <div class="card-body">
                        <?php if (count($foundUsers) > 0): ?>
                            <?php foreach ($foundUsers as $user): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>Found User: <strong><?php echo htmlspecialchars($user); ?></strong>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No matching user found for this Referer</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 VexiumCTF. SQL Injection Lab 107.</p>
        </div>
    </footer>
</body>
</html>
