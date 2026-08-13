<?php
// ============================================================
// SQL Injection Lab 103 - Book CRUD Application
// Vulnerabilities: INSERT, UPDATE, DELETE, SELECT injection
// ============================================================

session_start();

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
function initializeLab3Database($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS lab3 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(128) NOT NULL,
        author VARCHAR(128) NOT NULL,
        type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL
    )";
    mysqli_query($conn, $sql);
    
    // Insert sample book data
    $check = mysqli_query($conn, "SELECT * FROM lab3 LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $books = [
            ["Bug Bounty Bootcamp", "Vickie Li", "Hacking & BugBounty", "Bug Bounty Bootcamp teaches you how to hack web applications."],
            ["Mastering Modern Web Penetration Testing", "Prakhar Prasad", "Hacking & BugBounty", "Master the art of conducting modern pen testing attacks."],
            ["The Web Application Hacker's Handbook", "Dafydd Stuttard", "Hacking & BugBounty", "Web applications are the front door to most organizations."],
            ["Hacking: The Art of Exploitation", "Jon Erickson", "Hacking & BugBounty", "Hacking is the art of creative problem solving."],
            ["Real-World Bug Hunting", "Peter Yaworski", "Hacking & BugBounty", "Learn how people break websites and how you can, too."],
            ["Hacking APIs", "Corey J. Ball", "Hacking & BugBounty", "Hacking APIs is a crash course on web API security testing."]
        ];
        foreach ($books as $book) {
            $title = mysqli_real_escape_string($conn, $book[0]);
            $author = mysqli_real_escape_string($conn, $book[1]);
            $type = mysqli_real_escape_string($conn, $book[2]);
            $desc = mysqli_real_escape_string($conn, $book[3]);
            mysqli_query($conn, "INSERT INTO lab3 (title, author, type, description) VALUES ('$title', '$author', '$type', '$desc')");
        }
    }
}
initializeLab3Database($conn);

// --- Get Action ---
$action = $_GET['action'] ?? 'list';
$message = '';

// --- Process Form Submissions ---
if (isset($_POST['create'])) {
    // ===================================================================
    // VULNERABLE INSERT - String interpolation
    // Payload: '); DROP TABLE lab3;-- -  or  ',(SELECT password FROM lab3),'x')-- -
    // ===================================================================
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $sql = "INSERT INTO lab3 (title, author, type, description) VALUES ('$title', '$author', '$type', '$description')";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = '<div class="alert alert-success">Book added successfully!</div>';
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
    }
    header("Location: index.php");
    exit();
}

if (isset($_POST['edit'])) {
    // ===================================================================
    // VULNERABLE UPDATE - String interpolation in WHERE clause
    // Payload: 1 OR 1=1  or  1; DROP TABLE lab3;-- -
    // ===================================================================
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $sql = "UPDATE lab3 SET title = '$title', author = '$author', type = '$type', description = '$description' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = '<div class="alert alert-success">Book updated!</div>';
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
    }
    header("Location: index.php");
    exit();
}

if ($action === 'delete' && isset($_GET['id'])) {
    // ===================================================================
    // VULNERABLE DELETE - String interpolation
    // Payload: 1 OR 1=1  or  1; DROP TABLE lab3;-- -
    // ===================================================================
    $id = $_GET['id'];
    $sql = "DELETE FROM lab3 WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = '<div class="alert alert-success">Book deleted!</div>';
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
    }
    header("Location: index.php");
    exit();
}

// Show message from session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get book for edit/view
$editBook = null;
$viewBook = null;
if ($action === 'edit' && isset($_GET['id'])) {
    // VULNERABLE SELECT
    $id = $_GET['id'];
    $sql = "SELECT * FROM lab3 WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $editBook = mysqli_fetch_assoc($result);
}
if ($action === 'view' && isset($_GET['id'])) {
    // VULNERABLE SELECT
    $id = $_GET['id'];
    $sql = "SELECT * FROM lab3 WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $viewBook = mysqli_fetch_assoc($result);
}

// List all books
$listResult = mysqli_query($conn, "SELECT * FROM lab3");

$bookTypes = ["Dystopian", "Action & Adventure", "Crime", "Fantasy", "Science Fiction", "Horror", 
              "Hacking & BugBounty", "Mystery", "Thriller & Suspense", "Historical Fiction", "Romance",
              "Graphic Novel", "Non-fiction", "Young Adult", "Classic", "Biography"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD SQL Injection - Book Management</title>
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
            padding: 2rem 0;
            margin-bottom: 2rem;
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
            color: white;
            font-weight: 600;
        }
        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255,255,255,0.2);
            color: #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: var(--accent-green);
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(72, 187, 120, 0.25);
        }
        .btn-primary { background: var(--accent-green); border: none; }
        .btn-primary:hover { background: #38a169; }
        .btn-info { background: var(--accent-blue); border: none; color: white; }
        .btn-warning { background: var(--accent-orange); border: none; color: white; }
        .btn-danger { background: var(--accent-red); border: none; }
        .table { color: #e2e8f0; }
        .table th { color: #94a3b8; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table td { border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
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
            <?php if ($action !== 'list'): ?>
            <a href="index.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-list me-1"></i>Book List
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title">SQL Injection Lab 103</h1>
            <p class="hero-subtitle">CRUD Application - Multiple SQL Injection Points</p>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
        <!-- LIST VIEW -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-book me-2"></i>Book List</span>
                <div>
                    <a href="?action=create" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus me-1"></i>Add Book
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = mysqli_fetch_assoc($listResult)): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($book['type']); ?></span></td>
                                <td>
                                    <a href="?action=view&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="?action=edit&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="?action=delete&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($action === 'create'): ?>
        <!-- CREATE FORM -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add New Book</div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Author</label>
                                <input type="text" name="author" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($bookTypes as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="create" class="btn btn-primary">Add Book</button>
                            <a href="index.php" class="btn btn-outline-light ms-2">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($action === 'edit' && $editBook): ?>
        <!-- EDIT FORM -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Book</div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo $editBook['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editBook['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Author</label>
                                <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($editBook['author']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    <?php foreach ($bookTypes as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $editBook['type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($editBook['description']); ?></textarea>
                            </div>
                            <button type="submit" name="edit" class="btn btn-primary">Update Book</button>
                            <a href="index.php" class="btn btn-outline-light ms-2">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($action === 'view' && $viewBook): ?>
        <!-- VIEW BOOK -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-book-open me-2"></i>Book Details</div>
                    <div class="card-body">
                        <h4><?php echo htmlspecialchars($viewBook['title']); ?></h4>
                        <p class="text-muted">by <?php echo htmlspecialchars($viewBook['author']); ?></p>
                        <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($viewBook['type']); ?></span>
                        <hr>
                        <p><?php echo nl2br(htmlspecialchars($viewBook['description'])); ?></p>
                        <a href="index.php" class="btn btn-outline-light">Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">© 2024 VexiumCTF. SQL Injection Lab 103.</p>
        </div>
    </footer>
</body>
</html>
