<?php
// navbar.php - Unified KrazePlanet Header Navigation with 5 Recent Notifications & Complete Dropdown Actions
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();

$current_page = basename($_SERVER['PHP_SELF']);

// Account details come from the session (filled at sign-in from the central
// login server); notifications come from the central server, cached briefly.
$user_email_display = $_SESSION['user_email'] ?? 'trainee@krazeplanet.com';
$user_avatar = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($_SESSION['username'] ?? 'User'));
$recent_notifications = [];
$unread_count = 0;

if (krazeLoggedIn()) {
    $recent_notifications = krazeRecentNotifications();
    foreach ($recent_notifications as $n) {
        if (empty($n['is_read'])) $unread_count++;
    }
}

$baseDomain = getKrazeBaseDomain();
?>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<style>
  /* The off-screen slide-in menu drawer must not create horizontal page scroll.
     overflow-x: clip prevents the scrollbar without breaking the sticky navbar. */
  html { overflow-x: clip; }

  /* Unified platform background: every page that includes this navbar matches the home page.
     Explicit ellipse size so the top glow looks the same at every screen width. */
  body {
    background: radial-gradient(ellipse 1400px 760px at 50% 0%, #1e293b 0%, #0f172a 58%, #090d16 100%) !important;
    background-attachment: fixed !important;
    background-color: #090d16 !important;
  }

  /* Brand lockup (matches programs.krazeplanet.com): logo + Kraze<em>Planet</em> + tagline */
  .kp-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #f1f5f9;
    flex-shrink: 0;
  }

  .kp-brand:hover {
    color: #f1f5f9;
  }

  /* The logo PNG has generous transparent padding, so let it overflow its box */
  .kp-brand-logo {
    position: relative;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
  }

  .kp-brand-logo img {
    position: absolute;
    inset: -12px;
    width: 68px;
    height: 68px;
    max-width: none;
    filter: drop-shadow(0 0 8px rgba(139, 124, 246, 0.25));
    transition: transform 0.4s ease;
  }

  .kp-brand:hover .kp-brand-logo img {
    transform: rotate(-8deg) scale(1.05);
  }

  .kp-brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
  }

  .kp-brand-name {
    font-family: 'Space Grotesk', 'Inter', -apple-system, 'Segoe UI', Arial, sans-serif;
    font-size: 21px;
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  .kp-brand-name em {
    font-style: normal;
    background: linear-gradient(135deg, #8b7cf6 0%, #b3a8ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .kp-brand-tag {
    margin-top: 3px;
    font-size: 11px;
    letter-spacing: 0.02em;
    color: #8d94a1;
  }

  @media (max-width: 400px) {
    .kp-brand-tag {
      display: none;
    }
    .kp-brand-name {
      font-size: 18px;
    }
  }

  /* Authoritative navbar look for every page (the nav element uses .vexium-navbar) */
  .krazeplanet-navbar,
  .vexium-navbar.navbar {
    background: rgba(15, 23, 42, 0.92) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-top: none !important;
    padding: 0.75rem 0 !important;
    z-index: 1030;
  }
  /* Strip any page-specific decoration (e.g. gradient top line) from the shared navbar */
  .vexium-navbar.navbar::before,
  .vexium-navbar.navbar::after {
    display: none !important;
  }

  /* Plain text links: no box, background or border */
  .nav-pill-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .nav-pill-link {
    color: #cbd5e1 !important;
    font-size: 0.95rem;
    font-weight: 500;
    padding: 6px 14px !important;
    border-radius: 0;
    text-decoration: none;
    transition: color 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
  }

  .nav-pill-link:hover {
    color: #ffffff !important;
  }

  .nav-pill-link.active {
    color: #ffffff !important;
    font-weight: 600;
  }

  /* Login looks like the other plain-text navbar links */
  .btn-vexium-login {
    background: none !important;
    border: none !important;
    box-shadow: none !important;
    color: #cbd5e1 !important;
    font-family: inherit;
    font-weight: 500;
    font-size: 0.95rem;
    line-height: inherit;
    padding: 6px 14px !important;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s ease-in-out;
    cursor: pointer;
    user-select: none;
  }

  .btn-vexium-login:hover,
  .btn-vexium-login:focus {
    color: #ffffff !important;
  }

  /* Notification Bell with Purple Dot */
  .btn-nav-notification {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    width: 38px;
    height: 38px;
    border-radius: 0;
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
    border-radius: 0 !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9), 0 0 25px rgba(56, 189, 248, 0.15) !important;
    padding: 6px !important;
    min-width: 210px !important;
  }

  .custom-nav-dropdown .dropdown-item {
    color: #cbd5e1 !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    padding: 9px 14px !important;
    border-radius: 0 !important;
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

  /* Below 1200px the menu is a drawer that slides in from the right */
  .vexium-navbar .navbar-toggler {
    border-radius: 0;
  }

  .vexium-navbar .navbar-toggler:focus {
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.45);
  }

  .nav-drawer-header,
  .nav-drawer-backdrop {
    display: none;
  }

  @media (max-width: 1199.98px) {
    /* backdrop-filter would trap the fixed drawer inside the navbar box */
    .vexium-navbar {
      backdrop-filter: none !important;
      -webkit-backdrop-filter: none !important;
      background: rgba(15, 23, 42, 0.97) !important;
    }

    body.nav-drawer-open .vexium-navbar {
      z-index: 1050;
    }

    body.nav-drawer-open {
      overflow: hidden;
    }

    .nav-drawer-backdrop {
      display: block;
      position: fixed;
      inset: 0;
      background: rgba(3, 7, 15, 0.6);
      backdrop-filter: blur(2px);
      -webkit-backdrop-filter: blur(2px);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    body.nav-drawer-open .nav-drawer-backdrop {
      opacity: 1;
      visibility: visible;
    }

    .vexium-navbar .navbar-collapse {
      display: flex !important;
      flex-direction: column;
      align-items: stretch;
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: min(300px, 85vw);
      padding: 0 14px 20px;
      background: rgba(11, 19, 36, 0.99);
      border-left: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: -8px 0 30px rgba(0, 0, 0, 0.55);
      overflow-y: auto;
      transform: translateX(105%);
      visibility: hidden;
      transition: transform 0.28s ease, visibility 0.28s ease;
    }

    .vexium-navbar .navbar-collapse.show {
      transform: translateX(0);
      visibility: visible;
    }

    /* No slide animation while the window is being resized across the breakpoint */
    body.kp-resizing .vexium-navbar .navbar-collapse,
    body.kp-resizing .nav-drawer-backdrop {
      transition: none !important;
    }

    .nav-drawer-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 2px 14px;
      margin-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .nav-drawer-close {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-size: 1.1rem;
      line-height: 1;
      padding: 4px;
    }

    .nav-drawer-close:hover {
      color: #ffffff;
    }

    .vexium-navbar .navbar-collapse .nav-links-area {
      width: 100%;
      margin: 0 !important;
    }

    .nav-pill-wrapper {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      width: 100%;
      gap: 2px;
      background: transparent;
      border: none;
      box-shadow: none;
      padding: 0;
    }

    .nav-pill-link {
      padding: 11px 14px !important;
      border-radius: 0;
      border: 1px solid transparent;
    }

    #navAuthArea {
      margin-top: 14px !important;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    #navAuthArea .btn-vexium-login {
      width: 100%;
      justify-content: flex-start;
      padding: 11px 14px !important;
    }
  }

  @media (max-width: 400px) {
    .notif-dropdown-menu-box {
      width: calc(100vw - 24px) !important;
    }
  }
</style>

<nav class="navbar navbar-expand-xl navbar-dark sticky-top vexium-navbar">
  <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between">
    
    <!-- Left: Brand Logo -->
    <a href="/index.php" class="kp-brand" aria-label="KrazePlanet home">
      <span class="kp-brand-logo"><img src="https://krazeplanet.com/favicon.png" alt="KrazePlanet logo" onerror="this.style.display='none'"></span>
      <span class="kp-brand-text">
        <span class="kp-brand-name">Kraze<em>Planet</em></span>
        <span class="kp-brand-tag">Hands-on web security labs</span>
      </span>
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler border-0" type="button" id="navDrawerToggle"
      aria-controls="navbarSupportedContent" aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Below 1200px: backdrop behind the right-side menu drawer (click to close) -->
    <div class="nav-drawer-backdrop" id="navDrawerBackdrop"></div>

    <!-- Center & Right Navigation (a right-side drawer below 1200px) -->
    <div class="collapse navbar-collapse" id="navbarSupportedContent">

      <div class="nav-drawer-header">
        <span style="color: #ffffff; font-weight: 800; font-size: 1.15rem; letter-spacing: -0.5px; font-family: 'Outfit', sans-serif;">Menu</span>
        <button type="button" class="nav-drawer-close" id="navDrawerClose" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
      </div>

      <!-- Right: Menu links -->
      <div class="nav-links-area ms-xl-auto my-2 my-xl-0">
        <div class="nav-pill-wrapper">
          <a class="nav-pill-link <?php echo ($current_page === 'index.php' || $current_page === '') ? 'active' : ''; ?>" href="/index.php">Home</a>
          <a class="nav-pill-link <?php echo ($current_page === 'assignments.php') ? 'active' : ''; ?>" href="/navbar/assignments.php">Assignments</a>
          <a class="nav-pill-link <?php echo ($current_page === 'scoreboard.php') ? 'active' : ''; ?>" href="/navbar/scoreboard.php">Scoreboard</a>
          <a class="nav-pill-link <?php echo ($current_page === 'https://academy.krazeplanet.com') ? 'active' : ''; ?>" href="https://academy.krazeplanet.com" target="_blank" rel="noopener noreferrer">Courses</a>
          <a class="nav-pill-link <?php echo ($current_page === 'ctf.php') ? 'active' : ''; ?>" href="/navbar/ctf.php">CTF</a>
          <a class="nav-pill-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="/navbar/about.php">About</a>
          <a class="nav-pill-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>" href="/navbar/contact.php">Contact</a>
        </div>
      </div>

      <!-- Right: 5 Latest Notifications & Exact Avatar Dropdown -->
      <div id="navAuthArea" class="d-flex align-items-center gap-3 ms-xl-1 mt-2 mt-xl-0">
        <?php if (krazeLoggedIn()): ?>
          
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
                <a href="/navbar/profile.php?tab=notifications" class="small text-info text-decoration-none fw-semibold" style="font-size: 11.5px;">
                  View All Notifications in Profile &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- 2. USER PROFILE AVATAR DROPDOWN -->
          <div class="dropdown">
            <button class="btn btn-avatar-nav-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>">
              <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" class="navbar-user-avatar">
            </button>

            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end custom-nav-dropdown">


              <!-- 1. Profile -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=profile">
                  <i class="bi bi-person-fill"></i> Profile
                </a>
              </li>

              <!-- 2. Continue (In-progress labs) -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=continue">
                  <i class="bi bi-clock-history"></i> Continue
                </a>
              </li>

              <!-- 3. Bookmark List -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=bookmarks">
                  <i class="bi bi-bookmark-fill"></i> Bookmark List
                </a>
              </li>

              <!-- 4. Notification (All notifications) -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=notifications">
                  <i class="bi bi-bell-fill"></i> Notification
                </a>
              </li>

              <!-- 5. List Import (Import & Export data) -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=import">
                  <i class="bi bi-box-arrow-in-right"></i> List Import
                </a>
              </li>

              <!-- 6. Settings -->
              <li>
                <a class="dropdown-item" href="/navbar/profile.php?tab=settings">
                  <i class="bi bi-gear-fill"></i> Settings
                </a>
              </li>

              <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>

              <!-- 7. Logout -->
              <li>
                <a class="dropdown-item text-danger" href="/sso/logout.php" id="logoutBtnNav" onclick="handleUserLogout(event)">
                  <i class="bi bi-box-arrow-right text-danger"></i> Logout
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>
          <!-- Sign in on the central KrazePlanet login server -->
          <a class="btn btn-vexium-login" href="<?= htmlspecialchars(krazeLoginUrl()) ?>" onclick="openLoginModal(event)">
            <span>Login</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>

<script>
// Expose the navbar height as --kp-nav-h so fixed elements (e.g. the category sidebar) sit right below it
(function () {
  const nav = document.currentScript.previousElementSibling;
  function setNavHeight() {
    if (nav && nav.offsetHeight) document.documentElement.style.setProperty('--kp-nav-h', nav.offsetHeight + 'px');
  }
  setNavHeight();
  window.addEventListener('resize', setNavHeight);
  window.addEventListener('load', setNavHeight);
})();

// Right-side menu drawer below 1200px. Closes on backdrop click, link click, Esc, or growing past 1200px.
// Other scripts can call window.kpNavDrawer.close() and listen for the 'kp:navdrawer-open' event.
(function () {
  const drawer = document.getElementById('navbarSupportedContent');
  const toggle = document.getElementById('navDrawerToggle');
  const backdrop = document.getElementById('navDrawerBackdrop');
  const closeBtn = document.getElementById('navDrawerClose');
  if (!drawer || !toggle) return;
  const isDrawerMode = () => window.matchMedia('(max-width: 1199.98px)').matches;

  function setOpen(open) {
    drawer.classList.toggle('show', open);
    document.body.classList.toggle('nav-drawer-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) document.dispatchEvent(new CustomEvent('kp:navdrawer-open'));
  }

  toggle.addEventListener('click', () => setOpen(!drawer.classList.contains('show')));
  if (backdrop) backdrop.addEventListener('click', () => setOpen(false));
  if (closeBtn) closeBtn.addEventListener('click', () => setOpen(false));
  drawer.addEventListener('click', (e) => {
    if (isDrawerMode() && e.target.closest('a.nav-pill-link, a.btn-vexium-login')) setOpen(false);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('show')) setOpen(false);
  });
  let resizingTimer = null;
  window.addEventListener('resize', () => {
    document.body.classList.add('kp-resizing');
    clearTimeout(resizingTimer);
    resizingTimer = setTimeout(() => document.body.classList.remove('kp-resizing'), 250);
    if (!isDrawerMode() && drawer.classList.contains('show')) setOpen(false);
  });

  window.kpNavDrawer = { open: () => setOpen(true), close: () => setOpen(false), isOpen: () => drawer.classList.contains('show') };
})();

function dismissNavNotif(e) {
  if (e) e.preventDefault();
  const dot = document.getElementById('navNotifDot');
  if (dot) dot.style.display = 'none';
  document.querySelectorAll('.notif-item-row.unread').forEach(item => item.classList.remove('unread'));
}

function handleUserLogout(e) {
  if (e && e.preventDefault) e.preventDefault();
  window.location.href = '/sso/logout.php';
}
</script>

<?php @include_once __DIR__ . '/../model/auth_modals.php'; ?>
<?php @include_once __DIR__ . '/../model/avatar_modal.php'; ?>