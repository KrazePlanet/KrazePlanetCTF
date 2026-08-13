<?php
// ============================================================
// SQL Injection Lab 102 - Comment System (INSERT Injection)
// Vulnerability: INSERT statement SQL injection
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
function initializeLab102Database($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS lab102 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        comment MEDIUMTEXT NOT NULL
    )";
    mysqli_query($conn, $sql);
    
    // Insert sample data
    $check = mysqli_query($conn, "SELECT * FROM lab102 LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab102 (name, email, comment) VALUES 
            ('Testing', 'test1@mail.com', 'Hello Everyone.'),
            ('Testing2', 'test2@mail.com', 'Hello Everyone.')");
    }
}
initializeLab102Database($conn);

// --- Handle Comment Submission (VULNERABLE) ---
$message = '';
if (isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    // ===================================================================
    // VULNERABLE: Direct string interpolation in INSERT
    // SQL Injection payloads to test in any field:
    //   ', (SELECT GROUP_CONCAT(email) FROM lab102), 'injected')-- -
    //   '); DROP TABLE lab102;-- -
    //   ', (SELECT LOAD_FILE('/etc/passwd')), 'test')-- -
    // ===================================================================
    $sql = "INSERT INTO lab102 (name, email, comment) VALUES ('$name', '$email', '$comment')";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $message = '<div class="alert alert-success">Comment added successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INSERT SQL Injection - Comment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-dark: #1a1f36;
            --accent-green: #48bb78;
            --accent-blue: #4299e1;
            --accent-red: #f56565;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar { background: rgba(26, 31, 54, 0.95); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .navbar-brand { color: var(--accent-green) !important; font-weight: 600; }
        .hero-section {
            background: linear-gradient(135deg, rgba(72,187,120,0.1) 0%, rgba(66,153,225,0.1) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .hero-title { font-size: 2rem; font-weight: 700; color: white; text-align: center;}
        .hero-subtitle { color: #94a3b8; text-align: center;}
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
        }
        .card-header {
            background: rgba(26, 31, 54, 0.8);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
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
        .btn-primary { background: var(--accent-green); border: none; font-weight: 600; }
        .btn-primary:hover { background: #38a169; }
        .alert-danger { background: rgba(245,101,101,0.2); border-color: rgba(245,101,101,0.3); color: #f56565; }
        .alert-success { background: rgba(72,187,120,0.2); border-color: rgba(72,187,120,0.3); color: #48bb78; }
        .vuln-badge { background: rgba(245,101,101,0.2); color: #f56565; border: 1px solid rgba(245,101,101,0.3); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .code-block {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(245, 101, 101, 0.3);
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            color: #f56565;
        }
        .comment-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        footer {
            background: rgba(26, 31, 54, 0.8);
            border-top: 1px solid rgba(255,255,255,0.1);
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
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title">SQL Injection Lab 102</h1>
            <p class="hero-subtitle">INSERT Statement Injection - Comment System</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-chat-dots me-2"></i>Add Comment</span>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comment</label>
                                <textarea name="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Post Comment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-list me-2"></i>Comments</div>
                    <div class="card-body">
                        <?php
                        $result = mysqli_query($conn, "SELECT * FROM lab102 ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($result)):
                        ?>
                        <div class="comment-box">
                            <h6><?php echo htmlspecialchars($row['name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                            <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 VexiumCTF. SQL Injection Lab 102.</p>
        </div>
    </footer>
</body>
</html>
