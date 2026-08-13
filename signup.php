<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$pdo) {
        $error = 'Database connection failed: ' . ($db_error ?? 'Check MySQL server.');
    } else {
        // Check for duplicates
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or Email is already taken.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$username, $email, $hashed]);
                $userId = $pdo->lastInsertId();

                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['user_email'] = $email;

                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                $error = 'Failed to create user account.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up - VexiumCTF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #070b14;
      --accent-green: #10b981;
      --font-primary: 'Inter', sans-serif;
      --font-heading: 'Outfit', sans-serif;
    }
    body {
      background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #070b14 70%);
      background-attachment: fixed;
      color: #f1f5f9;
      min-height: 100vh;
      font-family: var(--font-primary);
      display: flex;
      flex-direction: column;
    }
    .navbar {
      background: rgba(7, 11, 20, 0.85) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
      padding: 0.85rem 0;
    }
    .navbar-nav .nav-link {
      color: #94a3b8 !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      border-radius: 0.5rem;
      transition: all 0.3s;
    }
    .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
      color: #f8fafc !important;
      background: rgba(255, 255, 255, 0.05);
    }
    .auth-card {
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
      width: 100%;
      max-width: 460px;
    }
    .form-control {
      background: rgba(7, 11, 20, 0.6) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      color: #f1f5f9 !important;
      border-radius: 12px;
      padding: 0.7rem 1rem;
    }
    .form-control:focus {
      border-color: #10b981 !important;
      box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.15) !important;
    }
    .btn-cta {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      font-weight: 700;
      border-radius: 12px;
      padding: 0.75rem;
      transition: all 0.3s;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }
    .btn-cta:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      color: white;
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-5">
    <div class="auth-card">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-person-plus" style="font-size: 1.8rem;"></i>
        </div>
        <h3 class="fw-bold text-light" style="font-family: 'Outfit';">Create Account</h3>
        <p class="text-muted small">Sign up to track your CTF progress & bookmarked labs</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size: 0.88rem; border-radius: 10px;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form action="signup.php" method="POST">
        <div class="mb-3">
          <label for="username" class="form-label text-muted small fw-semibold">Username</label>
          <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label text-muted small fw-semibold">Email Address</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label text-muted small fw-semibold">Password</label>
          <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
        </div>
        <div class="mb-4">
          <label for="confirm_password" class="form-label text-muted small fw-semibold">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
        </div>
        <button type="submit" class="btn btn-cta w-100 mb-3">Create Free Account</button>
      </form>

      <div class="text-center pt-3 border-top border-secondary border-opacity-25">
        <span class="text-muted small">Already have an account? 
          <a href="login.php" class="text-success fw-bold text-decoration-none ms-1">Login</a>
        </span>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
