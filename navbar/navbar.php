<?php
// navbar.php - Unified KrazePlanet Header Navigation with 5 Recent Notifications & Complete Dropdown Actions
if (session_status() === PHP_SESSION_NONE) {
if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
if (!isset($pdo)) {
    @include_once __DIR__ . '/../config/db.php';
}

$current_page = basename($_SERVER['PHP_SELF']);

$user_avatar = '';
$user_email_display = 'trainee@krazeplanet.com';
$recent_notifications = [];
$unread_count = 0;

if (isset($_SESSION['user_id']) && isset($pdo) && $pdo) {
    try {
        $uId = $_SESSION['user_id'];
        $stmtU = $pdo->prepare("SELECT username, email, avatar, role FROM users WHERE id = ?");
        $stmtU->execute([$uId]);
        $userData = $stmtU->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $user_email_display = !empty($userData['email']) ? $userData['email'] : 'trainee@krazeplanet.com';
            $_SESSION['user_email'] = $user_email_display;
            
            if (!empty($userData['avatar'])) {
                $user_avatar = $userData['avatar'];
                $_SESSION['avatar'] = $user_avatar;
            } else {
                $user_avatar = 'https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($userData['username'] ?? 'User');
                $_SESSION['avatar'] = $user_avatar;
            }
        }

        // Fetch exactly the latest 5 notifications for the navbar bell
        $stmtNotif = $pdo->prepare("SELECT * FROM user_notifications WHERE (user_id IS NULL OR user_id = ?) ORDER BY created_at DESC LIMIT 5");
        $stmtNotif->execute([$uId]);
        $recent_notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recent_notifications as $n) {
            if (empty($n['is_read'])) $unread_count++;
        }
    } catch (Exception $e) {}
}

if (empty($user_avatar)) {
    $user_avatar = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($_SESSION['username'] ?? 'User'));
}
?>
<style>
  .krazeplanet-navbar {
    background: rgba(15, 23, 42, 0.92) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 0.75rem 0;
    z-index: 1030;
  }

  .nav-pill-wrapper {
    display: inline-flex;
    align-items: center;
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 4px 6px;
    gap: 4px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }

  .nav-pill-link {
    color: #94a3b8 !important;
    font-size: 0.9rem;
    font-weight: 500;
    padding: 6px 18px !important;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
  }

  .nav-pill-link:hover {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.06);
  }

  .nav-pill-link.active {
    color: #38bdf8 !important;
    background: rgba(56, 189, 248, 0.14) !important;
    border: 1px solid rgba(56, 189, 248, 0.28);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(56, 189, 248, 0.15);
  }

  .btn-vexium-login {
    background: linear-gradient(135deg, #0284c7, #0369a1);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #ffffff !important;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 7px 20px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
    transition: all 0.25s ease-in-out;
    cursor: pointer;
    user-select: none;
  }

  .btn-vexium-login:hover {
    background: linear-gradient(135deg, #0369a1, #0284c7);
    border-color: #38bdf8;
    color: #ffffff !important;
    transform: translateY(-1.5px);
    box-shadow: 0 6px 20px rgba(2, 132, 199, 0.45);
  }

  /* Notification Bell with Purple Dot */
  .btn-nav-notification {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.15rem;
  }

  .btn-nav-notification:hover, .btn-nav-notification[aria-expanded="true"] {
    background: rgba(56, 189, 248, 0.15);
    border-color: rgba(56, 189, 248, 0.4);
    color: #38bdf8;
    box-shadow: 0 0 14px rgba(56, 189, 248, 0.25);
  }

  .notif-badge-purple {
    position: absolute;
    top: 6px;
    left: 8px;
    width: 7px;
    height: 7px;
    background: #a855f7;
    border: 1.5px solid #0f172a;
    border-radius: 50%;
    box-shadow: 0 0 8px #a855f7;
    animation: pulseGlow 2s infinite ease-in-out;
  }

  @keyframes pulseGlow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
  }

  /* Profile Avatar Trigger Button */
  .btn-avatar-nav-trigger {
    background: transparent;
    border: none;
    padding: 0;
    position: relative;
    cursor: pointer;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
  }

  .btn-avatar-nav-trigger:hover, .btn-avatar-nav-trigger[aria-expanded="true"] {
    transform: scale(1.08);
  }

  .navbar-user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    background: #1e293b;
    border: 2px solid rgba(56, 189, 248, 0.45);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5), 0 0 12px rgba(56, 189, 248, 0.3);
    transition: all 0.2s ease;
  }

  .btn-avatar-nav-trigger:hover .navbar-user-avatar {
    border-color: #38bdf8;
    box-shadow: 0 0 16px rgba(56, 189, 248, 0.6);
  }

  /* Dropdown Menus matching Screenshot 1 */
  .custom-nav-dropdown {
    background: #0f172a !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 14px !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9), 0 0 25px rgba(56, 189, 248, 0.15) !important;
    padding: 6px !important;
    min-width: 210px !important;
  }

  .custom-nav-dropdown .dropdown-item {
    color: #cbd5e1 !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    padding: 9px 14px !important;
    border-radius: 9px !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    transition: all 0.15s ease !important;
  }

  .custom-nav-dropdown .dropdown-item i {
    font-size: 16px !important;
    color: #94a3b8 !important;
    width: 18px !important;
    text-align: center !important;
    transition: color 0.15s ease !important;
  }

  .custom-nav-dropdown .dropdown-item:hover {
    background: rgba(56, 189, 248, 0.12) !important;
    color: #ffffff !important;
  }

  .custom-nav-dropdown .dropdown-item:hover i {
    color: #38bdf8 !important;
  }

  .custom-nav-dropdown .dropdown-item.text-danger:hover {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #f87171 !important;
  }

  .custom-nav-dropdown .dropdown-item.text-danger:hover i {
    color: #f87171 !important;
  }

  .notif-dropdown-menu-box {
    width: 330px !important;
    padding: 0 !important;
    overflow: hidden !important;
  }

  .notif-item-row {
    display: flex;
    gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    text-decoration: none;
    transition: background 0.15s ease;
  }

  .notif-item-row:hover {
    background: rgba(255, 255, 255, 0.04);
  }

  .notif-item-row.unread {
    background: rgba(56, 189, 248, 0.06);
  }
</style>

<nav class="navbar navbar-expand-md navbar-dark sticky-top vexium-navbar">
  <div class="container d-flex align-items-center justify-content-between">
    
    <!-- Left: Brand Logo -->
    <a href="/index.php" class="logo text-decoration-none d-flex align-items-center gap-2">
      <img src="https://krazeplanet.com/favicon.png" alt="KrazePlanet Logo" style="height: 30px; width: 30px; object-fit: contain;">
      <span style="color: #ffffff; font-weight: 800; font-size: 1.35rem; letter-spacing: -0.5px; font-family: 'Outfit', sans-serif;">KrazePlanet</span>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Center & Right Navigation -->
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      
      <!-- Center: Clean Pill Menu -->
      <div class="mx-auto my-2 my-md-0">
        <div class="nav-pill-wrapper">
          <a class="nav-pill-link <?php echo ($current_page === 'index.php' || $current_page === '') ? 'active' : ''; ?>" href="/index.php">Home</a>
          <a class="nav-pill-link <?php echo ($current_page === 'assignments.php') ? 'active' : ''; ?>" href="/navbar/assignments.php">Assignments</a>
          <a class="nav-pill-link <?php echo ($current_page === 'leaderboard.php') ? 'active' : ''; ?>" href="/navbar/leaderboard.php">Leaderboard</a>
          <a class="nav-pill-link <?php echo ($current_page === 'https://academy.krazeplanet.com') ? 'active' : ''; ?>" href="https://academy.krazeplanet.com" target="_blank" rel="noopener noreferrer">Courses</a>
          <a class="nav-pill-link <?php echo ($current_page === 'ctf.php') ? 'active' : ''; ?>" href="/navbar/ctf.php">CTF</a>
          <a class="nav-pill-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="/navbar/about.php">About</a>
          <a class="nav-pill-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>" href="/navbar/contact.php">Contact</a>
          <?php
            $rawNavUser = $_SESSION['username'] ?? 'newuser';
            $mailNavUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($rawNavUser));
            $isKzNav = (strpos($_SERVER['HTTP_HOST'] ?? '', 'kzlabs.in') !== false);
            $mailNavProto = $isKzNav ? 'https://' : 'http://';
            $mailNavDomain = $isKzNav ? 'kzlabs.in' : 'localhost';
            $userMailpitUrl = "{$mailNavProto}{$mailNavUser}-mailpit.{$mailNavDomain}";
          ?>
          <a class="nav-pill-link" href="/navbar/open_mailbox.php" target="_blank" rel="noopener noreferrer" title="Open your private isolated Mailpit Inbox">
            <i class="bi bi-envelope-at me-1"></i>Email Client
          </a>
        </div>
      </div>

      <!-- Right: 5 Latest Notifications & Exact Avatar Dropdown (Screenshot 1) -->
      <div id="navAuthArea" class="d-flex align-items-center gap-3 ms-md-0 mt-2 mt-md-0">
        <?php if (isset($_SESSION['user_id'])): ?>
          
          <!-- 1. NOTIFICATION BELL (Shows only latest 5 notifications in popup) -->
          <div class="dropdown">
            <button class="btn btn-nav-notification" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
              <i class="bi bi-bell-fill"></i>
              <?php if ($unread_count > 0 || empty($recent_notifications)): ?>
                <span class="notif-badge-purple" id="navNotifDot"></span>
              <?php endif; ?>
            </button>

            <div class="dropdown-menu dropdown-menu-dark dropdown-menu-end custom-nav-dropdown notif-dropdown-menu-box">
              <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-white small d-flex align-items-center gap-2">
                  <i class="bi bi-bell-fill text-info"></i> Latest Notifications
                </div>
                <button type="button" class="btn btn-link p-0 text-secondary text-decoration-none small" style="font-size: 11px;" onclick="dismissNavNotif(event)">Mark read</button>
              </div>

              <div class="py-1" style="max-height: 280px; overflow-y: auto;">
                <?php if (!empty($recent_notifications)): ?>
                  <?php foreach ($recent_notifications as $n): ?>
                    <a href="<?= htmlspecialchars($n['link'] ?? 'assignments.php') ?>" class="notif-item-row <?= empty($n['is_read']) ? 'unread' : '' ?>">
                      <div class="rounded-circle <?= htmlspecialchars($n['icon_bg'] ?? 'bg-info bg-opacity-10 text-info') ?> p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 13px;">
                        <i class="bi <?= htmlspecialchars($n['icon'] ?? 'bi-bell-fill') ?>"></i>
                      </div>
                      <div class="flex-grow-1" style="overflow: hidden;">
                        <div class="text-light fw-semibold small text-truncate" style="font-size: 12.5px;"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="text-secondary small text-truncate" style="font-size: 11px;"><?= htmlspecialchars($n['message']) ?></div>
                        <div class="text-muted small mt-1" style="font-size: 10px;"><i class="bi bi-clock me-1"></i> <?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-3 text-secondary small">No notifications</div>
                <?php endif; ?>
              </div>

              <div class="p-2 border-top border-secondary border-opacity-25 text-center bg-dark bg-opacity-50">
                <a href="profile.php?tab=notifications" class="small text-info text-decoration-none fw-semibold" style="font-size: 11.5px;">
                  View All Notifications in Profile &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- 2. USER PROFILE AVATAR DROPDOWN (Exact structure matching Screenshot 1) -->
          <div class="dropdown">
            <button class="btn btn-avatar-nav-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars($_SESSION['username']); ?>">
              <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="<?php echo htmlspecialchars($_SESSION['username']); ?>" class="navbar-user-avatar">
            </button>

            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end custom-nav-dropdown">
              <!-- Mailpit Isolated Inbox -->
              <li>
                <a class="dropdown-item" href="//<?php echo preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($_SESSION['username'] ?? 'user')); ?>-mailpit.<?php echo (strpos($_SERVER['HTTP_HOST'] ?? '', 'kzlabs.in') !== false ? 'kzlabs.in' : 'localhost'); ?>" target="_blank">
                  <i class="bi bi-envelope-open text-info"></i> Mailpit Inbox
                </a>
              </li>

              <!-- 1. Profile -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=profile">
                  <i class="bi bi-person-fill"></i> Profile
                </a>
              </li>

              <!-- 2. Continue (In-progress labs) -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=continue">
                  <i class="bi bi-clock-history"></i> Continue
                </a>
              </li>

              <!-- 3. Bookmark List -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=bookmarks">
                  <i class="bi bi-bookmark-fill"></i> Bookmark List
                </a>
              </li>

              <!-- 4. Notification (All notifications) -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=notifications">
                  <i class="bi bi-bell-fill"></i> Notification
                </a>
              </li>

              <!-- 5. List Import (Import & Export data) -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=import">
                  <i class="bi bi-box-arrow-in-right"></i> List Import
                </a>
              </li>

              <!-- 6. Settings -->
              <li>
                <a class="dropdown-item" href="profile.php?tab=settings">
                  <i class="bi bi-gear-fill"></i> Settings
                </a>
              </li>

              <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>

              <!-- 7. Logout -->
              <li>
                <a class="dropdown-item text-danger" href="/api/auth_api.php?action=logout" id="logoutBtnNav" onclick="handleUserLogout(event)">
                  <i class="bi bi-box-arrow-right text-danger"></i> Logout
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>
          <!-- Modern Sleek Login Button Triggering Modal -->
          <button type="button" class="btn btn-vexium-login" onclick="openLoginModal(event)" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Login</span>
          </button>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>

<script>
function dismissNavNotif(e) {
  if (e) e.preventDefault();
  const dot = document.getElementById('navNotifDot');
  if (dot) dot.style.display = 'none';
  document.querySelectorAll('.notif-item-row.unread').forEach(item => item.classList.remove('unread'));
}

function handleUserLogout(e) {
  if (e && e.preventDefault) e.preventDefault();
  fetch('/api/auth_api.php?action=logout', { method: 'POST' })
    .finally(() => {
      window.location.href = '/';
    });
}
</script>

<?php @include_once __DIR__ . '/../model/auth_modals.php'; ?>
<?php @include_once __DIR__ . '/../model/avatar_modal.php'; ?>
