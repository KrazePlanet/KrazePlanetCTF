<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($pdo)) {
    @include_once __DIR__ . '/db.php';
}

$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_SESSION['user_id']) && isset($pdo) && $pdo) {
    try {
        $uId = $_SESSION['user_id'];
        if (!isset($_SESSION['user_email'])) {
            $stmtE = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmtE->execute([$uId]);
            $_SESSION['user_email'] = $stmtE->fetchColumn() ?: '';
        }
    } catch (Exception $e) {}
}

$user_initial = isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'U';
$user_email_display = !empty($_SESSION['user_email']) ? $_SESSION['user_email'] : 'trainee@vexiumctf.com';
?>
<nav class="navbar navbar-expand-md navbar-dark sticky-top">
  <div class="container">
    <a href="index.php" class="logo text-decoration-none d-flex align-items-center gap-2">
      <img src="https://krazeplanet.com/favicon.png" alt="KrazePlanet Logo" style="height: 32px; width: 32px; object-fit: contain;">
      <span style="color: #ffffff; font-weight: 700; font-size: 1.4rem; letter-spacing: -0.5px; font-family: 'Outfit', sans-serif;">VexiumCTF</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page === 'index.php' || $current_page === '') ? 'active' : ''; ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page === 'https://academy.krazeplanet.com') ? 'active' : ''; ?>" href="https://academy.krazeplanet.com" target="_blank">Courses</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact Us</a>
        </li>
      </ul>

      <!-- Auth Navigation Area -->
      <div id="navAuthArea" class="d-flex align-items-center gap-2 ms-auto">
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 py-1 px-3" type="button" data-bs-toggle="dropdown" style="border-radius: 10px; border-color: rgba(255,255,255,0.15);">
              <div class="rounded-circle bg-success text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.8rem;">
                <?php echo $user_initial; ?>
              </div>
              <span class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </button>

            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg p-2" style="background: rgba(15, 23, 42, 0.96); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 14px; backdrop-filter: blur(16px); min-width: 220px;">
              <!-- User Header -->
              <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-success text-dark fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <?php echo $user_initial; ?>
                  </div>
                  <div style="overflow: hidden;">
                    <div class="fw-bold text-light text-truncate" style="font-size: 0.95rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div class="text-muted small text-truncate" style="font-size: 0.76rem;"><?php echo htmlspecialchars($user_email_display); ?></div>
                  </div>
                </div>
              </li>

              <!-- Clean Standard Profile Dropdown Links -->
              <li>
                <a class="dropdown-item rounded py-2 px-3 small d-flex align-items-center gap-2 <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>" href="profile.php">
                  <i class="bi bi-person text-success"></i> Your Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item rounded py-2 px-3 small d-flex align-items-center gap-2 <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                  <i class="bi bi-gear text-info"></i> Settings
                </a>
              </li>

              <li>
                <hr class="dropdown-divider border-secondary border-opacity-25 my-1">
              </li>

              <!-- Sign Out -->
              <li>
                <a class="dropdown-item text-danger rounded py-2 px-3 small d-flex align-items-center gap-2" href="#" id="logoutBtnNav">
                  <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-light btn-sm px-3 <?php echo ($current_page === 'login.php') ? 'active' : ''; ?>" style="border-radius: 10px; font-weight: 600;">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </a>
        <?php endif; ?>
        <a href="https://discord.gg/Ujg69RM6qd" target="_blank" rel="noopener noreferrer" class="btn btn-cta btn-sm px-3 d-none d-lg-inline-flex ms-1" style="background: linear-gradient(135deg, #5865F2, #404EED);">
          <i class="bi bi-discord me-1"></i> Discord
        </a>
      </div>
    </div>
  </div>
</nav>
