<?php
// ============================================================
// Database Configuration
// ============================================================
$host = 'localhost';
$dbname = 'KrazePlanet_DB';
$username = 'root';
$password = '';
$hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];

// Table Names Configuration
$table_users = 'members_users';
$table_products = 'members_products';
$table_reviews = 'members_reviews';


$pdo = null;
$lastException = null;
foreach ($hosts as $h) {
    try {
        $pdo = new PDO("mysql:host=$h;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $pdo->exec("USE `$dbname`");
        break;
    } catch(PDOException $e) {
        $lastException = $e;
    }
}
if (!$pdo) {
    die("Connection failed: " . ($lastException ? $lastException->getMessage() : "Unknown error"));
}

// Initialize Product Reviews database tables
function initializeDatabase($pdo) {
    global $table_users, $table_products, $table_reviews;
    // Ensure products table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_products} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(500),
            category VARCHAR(50) DEFAULT 'Electronics',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Ensure category column exists in products table if table was created previously without it
    try {
        $pdo->exec("ALTER TABLE {$table_products} ADD COLUMN category VARCHAR(50) DEFAULT 'Electronics'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }

    // Ensure users table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_users} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255),
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Reviews table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_reviews} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL,
            title VARCHAR(255),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default products if none exist
    $check = $pdo->query("SELECT COUNT(*) FROM {$table_products}");
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_products} (name, description, price, image_url, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'QuantumSound ANC Headphones',
            'Industry leading active noise cancelling headphones with premium sound fidelity, 30 hours battery life, and comfortable ergonomic over-ear design.',
            299.99,
            'images.jpg',
            'Electronics'
        ]);
        $stmt->execute([
            'NovaWatch Pro Smartwatch',
            'Track your health, receive notifications, and run native applications with this sleek high-end smartwatch featuring a vibrant AMOLED display.',
            199.99,
            'images2.jpg',
            'Electronics'
        ]);
        $stmt->execute([
            'AeroBook Air Ultra Laptop',
            'Ultra thin, ultra light engineering powerhouse featuring an 8-core CPU, 16GB RAM, 512GB SSD, and stunning 14-inch retina display.',
            999.99,
            'images3.jpg',
            'Electronics'
        ]);
        $stmt->execute([
            'PixelStream 4K Capture Card',
            'Broadcast and record your console gameplay in flawless 4K resolution at 60 FPS. Ultra low latency stream performance with pass-through capability.',
            149.99,
            'images4.jpg',
            'Electronics'
        ]);
        $stmt->execute([
            'Organix Pure Onion Shampoo',
            'Formulated with organic red onion extract to strengthen hair follicles, minimize breakage, and promote healthy growth. Cruelty-free formula.',
            15.50,
            'images5.jpg',
            'Beauty'
        ]);
        $stmt->execute([
            'GlowEssence Hydra-Serum',
            'Premium Hyaluronic Acid serum infused with Vitamin C and Niacinamide. Hydrates, plumps skin cells, and brightens overall skin tone.',
            29.99,
            'images6.jpg',
            'Beauty'
        ]);
        $stmt->execute([
            'Classic Leather Chronograph',
            'Waterproof wrist watch featuring a genuine hand-stitched leather strap, black dial with rose gold details, and Japanese quartz movement.',
            125.00,
            'images7.jpg',
            'Fashion'
        ]);
        $stmt->execute([
            'Ergonomic Mesh Office Chair',
            'Adjustable lumbar support with high-density mesh backrest, 3D armrests, and dynamic tilt-lock mechanism for ultimate comfort.',
            249.99,
            'shopping.jpg',
            'Home'
        ]);
    }

    // Insert default admin user if none exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name, role) VALUES (?, ?, ?, 'Administrator', 'admin')");
        $stmt->execute([
            'admin',
            'admin@reviews.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}
initializeDatabase($pdo);

// Migrate any existing seeded Unsplash URLs to local images
try {
    $localImagesByProduct = [
        'QuantumSound ANC Headphones'   => 'images.jpg',
        'NovaWatch Pro Smartwatch'      => 'images2.jpg',
        'AeroBook Air Ultra Laptop'     => 'images3.jpg',
        'PixelStream 4K Capture Card'   => 'images4.jpg',
        'Organix Pure Onion Shampoo'    => 'images5.jpg',
        'GlowEssence Hydra-Serum'       => 'images6.jpg',
        'Classic Leather Chronograph'   => 'images7.jpg',
        'Ergonomic Mesh Office Chair'   => 'shopping.jpg',
    ];
    $migStmt = $pdo->prepare("UPDATE {$table_products} SET image_url = ? WHERE name = ? AND (image_url LIKE '%unsplash%' OR image_url LIKE 'http%')");
    foreach ($localImagesByProduct as $prodName => $localFile) {
        $migStmt->execute([$localFile, $prodName]);
    }
} catch (Exception $e) {
    // non-fatal: ignore migration errors
}

// Session management
session_start();

// Authentication Helpers
function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['member_user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['member_user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['member_user_id']);
        unset($_SESSION['member_username']);
        unset($_SESSION['member_role']);
        return null;
    }
    return $u;
}

function loginUser($pdo, $username, $password) {
    global $table_users;
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['member_user_id'] = $user['id'];
        $_SESSION['member_username'] = $user['username'];
        $_SESSION['member_role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['member_user_id']);
    unset($_SESSION['member_username']);
    unset($_SESSION['member_role']);
    header("Location: index.php");
    exit();
}

// --- Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

// --- Register ---
$authError = ''; $authSuccess = '';
if (!isset($_SESSION['member_user_id']) && isset($_POST['register'])) {
    $u  = trim($_POST['reg_username']  ?? '');
    $e  = trim($_POST['reg_email']     ?? '');
    $p  =      $_POST['reg_password']  ?? '';
    $c  =      $_POST['reg_confirm']   ?? '';
    $fn = trim($_POST['reg_full_name'] ?? '');
    if (empty($u) || empty($e) || empty($p)) {
        $authError = 'Username, email and password are required';
    } elseif ($p !== $c) {
        $authError = 'Passwords do not match';
    } elseif (strlen($p) < 6) {
        $authError = 'Password must be at least 6 characters';
    } else {
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE username = ? OR email = ?");
            $chk->execute([$u, $e]);
            if ($chk->fetchColumn()) {
                $authError = 'Username or email already exists';
            } else {
                $ins = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                $ins->execute([$u, $e, password_hash($p, PASSWORD_DEFAULT), $fn]);
                $authSuccess = 'Account created! You can now login.';
            }
        } catch (PDOException $ex) { $authError = 'Registration failed: ' . $ex->getMessage(); }
    }
}

// --- Login ---
if (!isset($_SESSION['member_user_id']) && isset($_POST['login'])) {
    if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php'); exit();
    } else {
        $authError = 'Invalid username or password';
    }
}

// --- Auth Gate ---
if (!isset($_SESSION['member_user_id'])) {
    $authView = $_GET['view'] ?? 'login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FlipKart Clone - Reviews Lab</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: #f1f3f6;
      color: #212121;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-box {
      background: #ffffff;
      border-radius: 4px;
      padding: 2.5rem;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .fk-blue-header {
      background-color: #2874f0;
      color: white;
      padding: 1.5rem;
      margin: -2.5rem -2.5rem 2rem -2.5rem;
      border-top-left-radius: 4px;
      border-top-right-radius: 4px;
      text-align: center;
    }
    .fk-logo {
      font-weight: 700;
      font-size: 1.6rem;
      font-style: italic;
      color: #fff;
    }
    .fk-logo span {
      color: #ffe500;
    }
    .form-control {
      border: 1px solid #e0e0e0;
      border-radius: 2px;
      padding: 0.65rem 0.75rem;
      font-size: 0.9rem;
    }
    .form-control:focus {
      border-color: #2874f0;
      box-shadow: none;
    }
    .btn-login {
      background-color: #fb641b;
      border: none;
      color: white;
      font-weight: 500;
      padding: 0.75rem;
      border-radius: 2px;
      width: 100%;
      text-transform: uppercase;
      font-size: 0.95rem;
      box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1);
      transition: background-color 0.2s;
    }
    .btn-login:hover {
      background-color: #e05310;
    }
    .btn-register-switch {
      background-color: #fff;
      border: 1px solid #e0e0e0;
      color: #2874f0;
      font-weight: 500;
      padding: 0.75rem;
      border-radius: 2px;
      width: 100%;
      text-align: center;
      text-decoration: none;
      font-size: 0.95rem;
      display: block;
      margin-top: 1rem;
      box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
    }
    .alert {
      font-size: 0.85rem;
      border-radius: 2px;
    }
    .demo-info {
      background-color: #f9f9f9;
      border: 1px dashed #2874f0;
      padding: 0.75rem;
      font-size: 0.8rem;
      border-radius: 2px;
      color: #666;
    }
  </style>
</head>
<body>
<div class="auth-box">
  <div class="fk-blue-header">
    <div class="fk-logo">Flip<span>kart</span></div>
    <div class="small opacity-75 mt-1"><?php echo $authView === 'register' ? 'Join our marketplace' : 'Login to review purchased items'; ?></div>
  </div>

  <?php if ($authError): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($authError); ?></div>
  <?php endif; ?>
  <?php if ($authSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($authSuccess); ?></div>
  <?php endif; ?>

  <?php if ($authView === 'register'): ?>
  <form method="POST">
    <div class="mb-3">
      <input type="text" class="form-control" name="reg_username" placeholder="Username *" required>
    </div>
    <div class="mb-3">
      <input type="email" class="form-control" name="reg_email" placeholder="Email Address *" required>
    </div>
    <div class="mb-3">
      <input type="text" class="form-control" name="reg_full_name" placeholder="Full Name">
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" name="reg_password" placeholder="Password *" required>
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" name="reg_confirm" placeholder="Confirm Password *" required>
    </div>
    <button type="submit" name="register" class="btn btn-login">Create Account</button>
    <a href="index.php" class="btn-register-switch">Existing User? Log in</a>
  </form>
  <?php else: ?>
  <form method="POST">
    <div class="mb-3">
      <input type="text" class="form-control" name="username" placeholder="Enter Email/Username" required>
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" name="password" placeholder="Enter Password" required>
    </div>
    <button type="submit" name="login" class="btn btn-login">Login</button>
    <div class="demo-info mt-3 text-center">
      <strong>Demo Credentials:</strong> admin / admin123
    </div>
    <a href="index.php?view=register" class="btn-register-switch">New to Flipkart? Create an account</a>
  </form>
  <?php endif; ?>
</div>
</body></html>
<?php exit(); }

// --- Logged In Dashboard & Store Logic ---
$user = getCurrentUser($pdo);
$error = '';
$success = '';

// Retrieve products & handle optional search
$search_query = trim($_GET['q'] ?? '');
if (!empty($search_query)) {
    $stmt = $pdo->prepare("SELECT * FROM {$table_products} WHERE name LIKE ? OR category LIKE ?");
    $stmt->execute(["%$search_query%", "%$search_query%"]);
    $products = $stmt->fetchAll();
} else {
    $products = $pdo->query("SELECT * FROM {$table_products}")->fetchAll();
}

// Determine view mode: home store vs. product details
$active_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$active_product = null;
if ($active_product_id) {
    $stmt = $pdo->prepare("SELECT * FROM {$table_products} WHERE id = ?");
    $stmt->execute([$active_product_id]);
    $active_product = $stmt->fetch();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_fk_review']) && $active_product) {
    $rating = intval($_POST['rating'] ?? 5);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5 stars.';
    } elseif (empty($title) || empty($comment)) {
        $error = 'Review title and description comments are required.';
    } else {
        try {
            // Vulnerable: Stored unescaped to trigger Stored XSS
            $ins = $pdo->prepare("INSERT INTO {$table_reviews} (product_id, user_id, rating, title, comment) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$active_product_id, $user['id'], $rating, $title, $comment]);
            $success = 'Review submitted successfully! Thank you for your feedback.';
        } catch (PDOException $e) {
            $error = 'Failed to submit review: ' . $e->getMessage();
        }
    }
}

// Fetch active product reviews & count aggregates
$reviews = [];
$avg_rating = 0;
$rating_counts = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];

if ($active_product) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.full_name 
        FROM {$table_reviews} r 
        JOIN {$table_users} u ON r.user_id = u.id 
        WHERE r.product_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$active_product_id]);
    $reviews = $stmt->fetchAll();

    if (count($reviews) > 0) {
        $sum = 0;
        foreach ($reviews as $rev) {
            $sum += $rev['rating'];
            $rating_counts[$rev['rating']]++;
        }
        $avg_rating = round($sum / count($reviews), 1);
    }
}

// Helper: Calculate average rating for product card in grid
function getProductAverageRating($pdo, $pid) {
    global $table_reviews;
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM {$table_reviews} WHERE product_id = ?");
    $stmt->execute([$pid]);
    $res = $stmt->fetch();
    return [
        'avg' => $res['avg_r'] ? round($res['avg_r'], 1) : '0',
        'count' => $res['cnt']
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Flipkart - Customer Reviews Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f1f3f6;
      color: #212121;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    /* Flipkart Header Navbar */
    .fk-header {
      background-color: #2874f0;
      padding: 10px 0;
      position: sticky;
      top: 0;
      z-index: 1030;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .fk-brand {
      color: white;
      font-weight: 700;
      font-size: 1.4rem;
      font-style: italic;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      line-height: 1;
    }
    .fk-brand span {
      color: #ffe500;
    }
    .fk-brand-sub {
      font-size: 0.65rem;
      font-weight: 500;
      font-style: italic;
      color: #f1f3f6;
      margin-top: 2px;
      display: flex;
      align-items: center;
      gap: 2px;
    }
    .fk-search-bar {
      background-color: #ffffff;
      border-radius: 2px;
      overflow: hidden;
      display: flex;
      box-shadow: 0 2px 4px 0 rgba(0,0,0,0.08);
      width: 100%;
      max-width: 560px;
    }
    .fk-search-input {
      border: none;
      outline: none;
      padding: 8px 16px;
      width: 100%;
      font-size: 0.9rem;
    }
    .fk-search-btn {
      background: none;
      border: none;
      color: #2874f0;
      padding: 0 16px;
      cursor: pointer;
    }
    .btn-fk-login {
      background-color: #ffffff;
      color: #2874f0;
      border-radius: 2px;
      border: 1px solid #dbdbdb;
      font-weight: 600;
      font-size: 0.95rem;
      padding: 4px 24px;
      text-decoration: none;
      transition: background-color 0.2s;
    }
    .btn-fk-login:hover {
      background-color: #f1f3f6;
      color: #2874f0;
    }
    .fk-user-text {
      color: white;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    /* E-commerce Categories Nav Row */
    .fk-categories-row {
      background-color: #ffffff;
      box-shadow: 0 1px 1px 0 rgba(0,0,0,0.1);
      padding: 10px 0;
      margin-bottom: 16px;
    }
    .cat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #212121;
      font-weight: 600;
      font-size: 0.85rem;
      transition: color 0.2s;
    }
    .cat-item:hover {
      color: #2874f0;
    }
    .cat-icon-circle {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background-color: #f1f3f6;
      color: #2874f0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      margin-bottom: 6px;
    }
    
    /* Deal Ads/Promo Banners */
    .fk-promo-banner {
      background: linear-gradient(90deg, #1d4ed8 0%, #1e40af 100%);
      color: white;
      border-radius: 4px;
      padding: 1.75rem 2rem;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    .fk-promo-banner::after {
      content: "";
      position: absolute;
      right: -50px;
      top: -20px;
      width: 250px;
      height: 250px;
      background: rgba(255,255,255,0.06);
      border-radius: 50%;
    }
    .promo-badge {
      background-color: #ffe500;
      color: #212121;
      font-weight: 700;
      padding: 4px 10px;
      font-size: 0.8rem;
      text-transform: uppercase;
      border-radius: 2px;
      display: inline-block;
      margin-bottom: 8px;
    }
    .promo-title {
      font-weight: 700;
      font-size: 1.6rem;
      margin-bottom: 4px;
    }
    .promo-sub {
      color: #bfdbfe;
      font-size: 0.95rem;
    }

    /* Product Grid Cards */
    .product-card {
      background-color: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      overflow: hidden;
      transition: box-shadow 0.2s;
      height: 100%;
      display: flex;
      flex-direction: column;
      text-decoration: none;
      color: inherit;
    }
    .product-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .product-card-img-wrapper {
      width: 100%;
      height: 200px;
      background-color: #fcfcfc;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      position: relative;
    }
    .product-card-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .product-card-body {
      padding: 12px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }
    .product-card-category {
      font-size: 0.75rem;
      color: #878787;
      font-weight: 500;
      text-transform: uppercase;
    }
    .product-card-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #212121;
      margin: 4px 0 8px 0;
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      height: 2.6rem;
    }
    .fk-rating-badge {
      background-color: #388e3c;
      color: white;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 3px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }
    .fk-rating-count {
      font-size: 0.8rem;
      color: #878787;
      margin-left: 6px;
    }
    .product-card-price {
      font-size: 1.15rem;
      font-weight: 700;
      color: #212121;
      margin-top: 10px;
    }

    /* Product Details Page Layout */
    .fk-container-white {
      background-color: #ffffff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      border-radius: 4px;
      padding: 20px;
      margin-bottom: 16px;
    }
    .fk-breadcrumb {
      font-size: 0.8rem;
      color: #878787;
      margin-bottom: 16px;
    }
    .fk-breadcrumb a {
      color: #878787;
      text-decoration: none;
    }
    .fk-breadcrumb a:hover {
      color: #2874f0;
    }
    .fk-p-title {
      font-size: 1.45rem;
      font-weight: 500;
      color: #212121;
      margin-bottom: 8px;
    }
    .fk-p-price {
      font-size: 1.75rem;
      font-weight: 700;
      color: #212121;
      margin-bottom: 15px;
    }
    .fk-p-badge {
      background-color: #e0f2fe;
      color: #0369a1;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 3px;
      text-transform: uppercase;
      display: inline-block;
      margin-bottom: 15px;
    }
    
    /* Interactive ratings stars selector */
    .fk-interactive-stars .bi {
      font-size: 1.6rem;
      color: #ccc;
      cursor: pointer;
      margin-right: 4px;
    }
    .fk-interactive-stars .bi.active {
      color: #ffc200;
    }
    .btn-fk-submit {
      background-color: #fb641b;
      border: none;
      color: white;
      font-weight: 600;
      font-size: 0.95rem;
      padding: 10px 24px;
      border-radius: 2px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.15);
      transition: background-color 0.2s;
    }
    .btn-fk-submit:hover {
      background-color: #e05310;
    }

    /* Star rating aggregates progress bar styling */
    .fk-bar-row {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.85rem;
      margin-bottom: 6px;
    }
    .fk-bar-container {
      flex-grow: 1;
      height: 8px;
      background-color: #f0f0f0;
      border-radius: 4px;
      overflow: hidden;
    }
    .fk-bar-fill {
      height: 100%;
      background-color: #388e3c;
      border-radius: 4px;
    }
    .fk-bar-fill.warning { background-color: #ff9f00; }
    .fk-bar-fill.danger { background-color: #ff6161; }

    /* Submitted Reviews Feed Card */
    .fk-review-card {
      border-bottom: 1px solid #f0f0f0;
      padding: 16px 0;
    }
    .fk-review-card:last-child {
      border-bottom: none;
    }
    .fk-review-stars {
      background-color: #388e3c;
      color: white;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 1px 5px;
      border-radius: 3px;
      display: inline-flex;
      align-items: center;
      gap: 2px;
      margin-bottom: 8px;
    }
    .fk-review-stars.warning { background-color: #ff9f00; }
    .fk-review-stars.danger { background-color: #ff6161; }
    .fk-review-title {
      font-weight: 700;
      color: #212121;
      margin-left: 8px;
      font-size: 0.95rem;
    }
    .fk-review-body {
      font-size: 0.9rem;
      color: #212121;
      line-height: 1.45;
      margin-bottom: 8px;
    }
    .fk-review-author {
      font-size: 0.75rem;
      color: #878787;
      font-weight: 500;
    }

    /* Footer styling */
    .fk-footer {
      background-color: #172337;
      color: #fff;
      font-size: 0.8rem;
      padding: 40px 0 24px 0;
      margin-top: auto;
      border-top: 1px solid #23354e;
    }
    .fk-footer-heading {
      color: #878787;
      font-weight: 500;
      margin-bottom: 15px;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }
    .fk-footer-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .fk-footer-links li {
      margin-bottom: 8px;
    }
    .fk-footer-links a {
      color: #fff;
      text-decoration: none;
      transition: color 0.1s;
    }
    .fk-footer-links a:hover {
      color: #ffe500;
    }
    .fk-footer-bottom {
      border-top: 1px solid #23354e;
      padding-top: 20px;
      margin-top: 30px;
      color: #878787;
      font-size: 0.75rem;
    }
  </style>
</head>
<body>

  <!-- Flipkart Style Top Navbar -->
  <header class="fk-header">
    <div class="container d-flex align-items-center justify-content-between">
      
      <!-- Brand Logo -->
      <a href="index.php" class="fk-brand">
        <div>Flip<span>kart</span></div>
        <div class="fk-brand-sub">
          Explore <span>Plus</span> <i class="bi bi-plus-lg" style="font-size:8px; color:#ffe500;"></i>
        </div>
      </a>

      <!-- Search Bar -->
      <form action="index.php" method="GET" class="fk-search-bar mx-3 d-none d-md-flex">
        <input type="text" name="q" class="fk-search-input" placeholder="Search for products, brands and more" value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="fk-search-btn"><i class="bi bi-search"></i></button>
      </form>

      <!-- Right menu -->
      <div class="d-flex align-items-center gap-4">
        <?php if ($user): ?>
          <a href="#" class="fk-user-text">
            <i class="bi bi-person-circle fs-5"></i>
            <span><?php echo htmlspecialchars($user['username']); ?></span>
          </a>
          <a href="index.php?action=logout" class="btn-fk-login">Logout</a>
        <?php else: ?>
          <a href="index.php" class="btn-fk-login">Login</a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <!-- Categories Navigation Row -->
  <section class="fk-categories-row">
    <div class="container d-flex justify-content-around align-items-center flex-wrap gap-3">
      <a href="index.php" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-grid-fill"></i></div>
        <span>All Items</span>
      </a>
      <a href="index.php?q=Electronics" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-laptop"></i></div>
        <span>Electronics</span>
      </a>
      <a href="index.php?q=Fashion" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-tag-fill"></i></div>
        <span>Fashion</span>
      </a>
      <a href="index.php?q=Beauty" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-flower1"></i></div>
        <span>Beauty</span>
      </a>
      <a href="index.php?q=Home" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-house-door-fill"></i></div>
        <span>Home</span>
      </a>
      <a href="#" class="cat-item">
        <div class="cat-icon-circle"><i class="bi bi-cart4"></i></div>
        <span>Grocery</span>
      </a>
    </div>
  </section>

  <!-- Main Store Layout / Content -->
  <main class="container my-3 flex-grow-1">
    
    <?php if (!$active_product): ?>
      <!-- HOME STORE VIEW: Banners & Product Grid -->

      <!-- Promo Deals Banner -->
      <div class="fk-promo-banner">
        <div>
          <span class="promo-badge">Independence Day Sale</span>
          <h2 class="promo-title">Glow start with skincare under ₹299</h2>
          <p class="promo-sub mb-0">Shop shampoos, face serums, headphones and much more with zero delivery fee.</p>
        </div>
        <div class="d-none d-lg-block">
          <i class="bi bi-percent text-warning" style="font-size:4rem; opacity:0.8;"></i>
        </div>
      </div>

      <!-- Products Grid Section -->
      <h4 class="mb-3 fw-bold">Deals of the Day</h4>
      <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3 mb-5">
        <?php if (empty($products)): ?>
          <div class="col-12 text-center py-5">
            <h5 class="text-muted">No products found matching "<?php echo htmlspecialchars($search_query); ?>"</h5>
            <a href="index.php" class="btn btn-primary mt-2">Clear Search</a>
          </div>
        <?php else: ?>
          <?php foreach ($products as $p): 
            $rating_info = getProductAverageRating($pdo, $p['id']);
          ?>
            <div class="col">
              <a href="index.php?product_id=<?php echo $p['id']; ?>" class="product-card">
                <div class="product-card-img-wrapper">
                  <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="product-card-img" alt="product img">
                </div>
                <div class="product-card-body">
                  <div class="product-card-category"><?php echo htmlspecialchars($p['category']); ?></div>
                  <h5 class="product-card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                  <div class="d-flex align-items-center mt-auto">
                    <span class="fk-rating-badge">
                      <?php echo $rating_info['avg']; ?> <i class="bi bi-star-fill text-white" style="font-size: 8px;"></i>
                    </span>
                    <span class="fk-rating-count">(<?php echo $rating_info['count']; ?> reviews)</span>
                  </div>
                  <div class="product-card-price">$<?php echo htmlspecialchars($p['price']); ?></div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- PRODUCT DETAIL & REVIEWS HUB VIEW -->

      <!-- Breadcrumbs -->
      <div class="fk-breadcrumb">
        <a href="index.php">Home</a> &gt; 
        <a href="index.php?q=<?php echo urlencode($active_product['category']); ?>"><?php echo htmlspecialchars($active_product['category']); ?></a> &gt; 
        <span><?php echo htmlspecialchars($active_product['name']); ?></span>
      </div>

      <div class="row g-3 mb-5">
        <!-- Left Side: Product Details Column -->
        <div class="col-md-5">
          <div class="fk-container-white text-center">
            <img src="<?php echo htmlspecialchars($active_product['image_url']); ?>" class="img-fluid rounded mb-3" style="max-height: 380px; object-fit: contain;" alt="product view">
            <h3 class="fk-p-title text-start"><?php echo htmlspecialchars($active_product['name']); ?></h3>
            <div class="text-start">
              <span class="fk-p-badge"><?php echo htmlspecialchars($active_product['category']); ?></span>
            </div>
            <div class="fk-p-price text-start">$<?php echo htmlspecialchars($active_product['price']); ?></div>
            <p class="text-muted text-start small mb-0"><?php echo htmlspecialchars($active_product['description']); ?></p>
          </div>
        </div>

        <!-- Right Side: Ratings & Product Review Submissions -->
        <div class="col-md-7">
          
          <!-- Ratings Aggregate Summary -->
          <div class="fk-container-white">
            <h5 class="fw-bold mb-4">Ratings & Reviews Summary</h5>
            <div class="row align-items-center g-3">
              <div class="col-md-4 text-center border-end">
                <div class="display-4 fw-bold text-dark"><?php echo $avg_rating; ?> <i class="bi bi-star-fill text-warning fs-3"></i></div>
                <div class="text-muted small mt-1"><?php echo count($reviews); ?> Ratings &amp; Reviews</div>
              </div>
              <div class="col-md-8 px-md-4">
                <?php foreach([5, 4, 3, 2, 1] as $star): 
                  $percent = count($reviews) > 0 ? round(($rating_counts[$star] / count($reviews)) * 100) : 0;
                  $bar_class = $star >= 4 ? '' : ($star >= 2 ? 'warning' : 'danger');
                ?>
                  <div class="fk-bar-row">
                    <span style="width: 45px; font-weight: 500;"><?php echo $star; ?> Star</span>
                    <div class="fk-bar-container">
                      <div class="fk-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                    <span class="text-muted" style="width: 35px; text-align: right;"><?php echo $percent; ?>%</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Add Review Form -->
          <div class="fk-container-white">
            <h5 class="fw-bold mb-3">Write a Customer Review</h5>
            <?php if ($error): ?>
              <div class="alert alert-danger p-2"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success p-2"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label d-block text-muted small fw-bold">Select Star Rating</label>
                <div class="fk-interactive-stars d-flex gap-2">
                  <?php for($i=1; $i<=5; $i++): ?>
                    <i class="bi bi-star-fill active" id="star-<?php echo $i; ?>" onclick="updateFormRating(<?php echo $i; ?>)"></i>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="5">
              </div>
              <div class="mb-3">
                <label for="title" class="form-label text-muted small fw-bold">Headline / Short Title</label>
                <input type="text" class="form-control" name="title" id="title" placeholder="e.g. Excellent build quality, sounds amazing!" required>
              </div>
              <div class="mb-3">
                <label for="comment" class="form-label text-muted small fw-bold">Detailed Review Comments</label>
                <textarea class="form-control" name="comment" id="comment" rows="4" placeholder="Share your experience using this product (design, performance, features)..." required></textarea>
              </div>
              <button type="submit" name="submit_fk_review" class="btn btn-fk-submit w-100">Submit Product Review</button>
            </form>
          </div>

          <!-- Reviews Feed List -->
          <div class="fk-container-white">
            <h5 class="fw-bold mb-4">Customer Feedbacks</h5>
            <?php if (empty($reviews)): ?>
              <p class="text-muted text-center py-4">No reviews written for this product yet. Share your thoughts!</p>
            <?php else: ?>
              <?php foreach ($reviews as $rev): 
                $badge_class = $rev['rating'] >= 4 ? '' : ($rev['rating'] >= 2 ? 'warning' : 'danger');
              ?>
                <div class="fk-review-card">
                  <div class="d-flex align-items-center mb-1">
                    <span class="fk-review-stars <?php echo $badge_class; ?>">
                      <?php echo $rev['rating']; ?> <i class="bi bi-star-fill text-white" style="font-size: 8px;"></i>
                    </span>
                    <span class="fk-review-title"><?php echo htmlspecialchars($rev['title']); ?></span>
                  </div>
                  <div class="fk-review-body">
                    <?php echo $rev['comment']; // Vulnerable: Direct unescaped output to trigger CTF Stored XSS alert popups ?>
                  </div>
                  <div class="fk-review-author">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    Certified Buyer &bull; By <?php echo htmlspecialchars($rev['full_name'] ?: $rev['username']); ?> &bull; <?php echo date('M j, Y g:i A', strtotime($rev['created_at'])); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>
    <?php endif; ?>

  </main>

  <!-- Flipkart Style Footer -->
  <footer class="fk-footer">
    <div class="container">
      <div class="row g-4">
        
        <div class="col-6 col-md-3">
          <h6 class="fk-footer-heading">ABOUT</h6>
          <ul class="fk-footer-links">
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Flipkart Stories</a></li>
            <li><a href="#">Corporate Information</a></li>
          </ul>
        </div>

        <div class="col-6 col-md-3">
          <h6 class="fk-footer-heading">HELP</h6>
          <ul class="fk-footer-links">
            <li><a href="#">Payments</a></li>
            <li><a href="#">Shipping</a></li>
            <li><a href="#">Cancellation & Returns</a></li>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Report Infringement</a></li>
          </ul>
        </div>

        <div class="col-6 col-md-3">
          <h6 class="fk-footer-heading">CONSUMER POLICY</h6>
          <ul class="fk-footer-links">
            <li><a href="#">Cancellation & Returns</a></li>
            <li><a href="#">Terms Of Use</a></li>
            <li><a href="#">Security</a></li>
            <li><a href="#">Privacy</a></li>
            <li><a href="#">Sitemap</a></li>
          </ul>
        </div>

        <div class="col-6 col-md-3">
          <h6 class="fk-footer-heading">SOCIAL</h6>
          <ul class="fk-footer-links">
            <li><a href="#">Facebook</a></li>
            <li><a href="#">Twitter</a></li>
            <li><a href="#">YouTube</a></li>
          </ul>
        </div>

      </div>

      <div class="fk-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center text-center">
        <p class="mb-0">&copy; 2026 Flipkart Clone. All rights reserved. Designed for Security Sandbox testing.</p>
        <p class="mb-0 mt-2 mt-md-0"><i class="bi bi-shield-fill text-warning me-1"></i>XSS Stored Vulnerability Lab Environment</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateFormRating(rating) {
      document.getElementById('ratingInput').value = rating;
      for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (i <= rating) {
          star.classList.add('active');
        } else {
          star.classList.remove('active');
        }
      }
    }
  </script>
</body>
</html>
