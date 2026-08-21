<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?modal=login");
    exit;
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';

    if ($actionType === 'update_profile') {
        $new_email = trim($_POST['email'] ?? '');
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$new_email, $userId]);
            $_SESSION['user_email'] = $new_email;
            $success = "Profile updated successfully!";
        } else {
            $error = "Please provide a valid email address.";
        }
    } elseif ($actionType === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userPass = $stmt->fetchColumn();

        if (!password_verify($current_pass, $userPass)) {
            $error = "Incorrect current password.";
        } elseif (strlen($new_pass) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "New passwords do not match.";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hashed, $userId]);
            $success = "Password changed successfully!";
        }
    }
}

// Fetch current user details
$user = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}
$username = $user['username'] ?? $_SESSION['username'];
$email = $user['email'] ?? $_SESSION['user_email'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Settings - KrazePlanet</title>
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
    .settings-card {
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
      max-width: 650px;
      width: 100%;
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
      padding: 0.65rem 1.5rem;
      transition: all 0.3s;
    }
    .btn-cta:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      color: white;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>

  <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-5">
    <div class="settings-card">
      <h3 class="fw-bold text-light mb-4" style="font-family: 'Outfit';">
        <i class="bi bi-gear text-success me-2"></i>Account Settings
      </h3>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success py-2 mb-4" style="font-size: 0.88rem; border-radius: 10px;">
          <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 mb-4" style="font-size: 0.88rem; border-radius: 10px;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <!-- Profile Details Form -->
      <form action="settings.php" method="POST" class="mb-5 border-bottom border-secondary border-opacity-25 pb-4">
        <input type="hidden" name="action_type" value="update_profile">
        <h5 class="fw-semibold text-light mb-3">Profile Information</h5>
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold">Username</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled style="opacity: 0.7;">
          <div class="form-text text-muted small">Username cannot be changed.</div>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label text-muted small fw-semibold">Email Address</label>
          <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <button type="submit" class="btn btn-cta">Save Changes</button>
      </form>

      <!-- Change Password Form -->
      <form action="settings.php" method="POST">
        <input type="hidden" name="action_type" value="change_password">
        <h5 class="fw-semibold text-light mb-3">Security & Password</h5>
        <div class="mb-3">
          <label for="current_password" class="form-label text-muted small fw-semibold">Current Password</label>
          <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password" required>
        </div>
        <div class="mb-3">
          <label for="new_password" class="form-label text-muted small fw-semibold">New Password</label>
          <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
        </div>
        <div class="mb-4">
          <label for="confirm_password" class="form-label text-muted small fw-semibold">Confirm New Password</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
        </div>
        <button type="submit" class="btn btn-cta">Update Password</button>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../footer/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
