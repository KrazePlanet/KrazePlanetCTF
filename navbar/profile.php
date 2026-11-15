<?php
// profile.php - KrazePlanet User Dashboard: Profile, Bookmarks and Import/Export
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
krazeRequireLogin();
$labCatalog = krazeLabCatalog();

$userId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'profile';
$allowedTabs = ['profile', 'bookmarks', 'import'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'profile';
}

// Profile, solved labs and bookmarks all come
// from the central login server in one call, fresh on every load, so a
// bookmark or solve made anywhere shows up straight away. Browsers must not
// keep a copy either (back button, tab restore).
header('Cache-Control: no-store, must-revalidate');
$bundle = krazeSsoCall('profile_bundle');
if (!krazeLoggedIn()) {
    header('Location: ' . krazeLoginUrl($_SERVER['REQUEST_URI']));
    exit;
}
$user = $bundle['user'] ?? null;
$solvedLabs = $bundle['solved'] ?? [];
$bookmarkedLabs = $bundle['bookmarks'] ?? [];
$accountPageUrl = krazeSsoAccountUrl();

$username = $user['username'] ?? $_SESSION['username'] ?? 'Trainee';
$fullname = $user['fullname'] ?? '';
$email = $user['email'] ?? $_SESSION['user_email'] ?? 'trainee@krazeplanet.com';
$phone = $user['phone'] ?? '';
$githubRepo = $user['github_repo'] ?? krazeGithubRepo();
$created_at = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'May 23, 2026';
$avatarUrl = !empty($user['avatar']) ? $user['avatar'] : (!empty($_SESSION['avatar']) ? $_SESSION['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($username)));

$solvedCount = count($solvedLabs);
$bookmarkCount = count($bookmarkedLabs);
$totalLabsAvailable = 350;

// Stats calculation - Sum exact points from solved labs (Easy=20, Medium=50, Hard=100)
$totalXP = 0;
foreach ($solvedLabs as $sl) {
    $totalXP += intval($sl['points'] ?? 20);
}
if ($totalXP === 0 && $solvedCount > 0) {
    $totalXP = $solvedCount * 20;
}
$rankTitle = "Trainee";
if ($solvedCount >= 100) $rankTitle = "CTF Grandmaster";
elseif ($solvedCount >= 50) $rankTitle = "Cyber Security Elite";
elseif ($solvedCount >= 20) $rankTitle = "Pro Hunter";
elseif ($solvedCount >= 5) $rankTitle = "Apprentice Hunter";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard - KrazePlanet</title>
  <link rel="icon" type="image/png" href="https://krazeplanet.com/favicon.png">
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    :root {
      --bg-dark: #070b14;
      --card-bg: #0b1324;
      --card-inner: #0f1a30;
      --border-color: rgba(255, 255, 255, 0.08);
      --accent-cyan: #38bdf8;
      --accent-green: #10b981;
    }

    body {
      background-color: var(--bg-dark);
      color: #cbd5e1;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Left Profile Card */
    .profile-sidebar-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 18px;
      padding: 2rem 1.5rem;
      text-align: center;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    }

    .profile-avatar-wrapper {
      position: relative;
      display: inline-block;
      margin-bottom: 1.25rem;
    }

    .profile-avatar-img {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      object-fit: cover;
      background: #1e293b;
      border: 3px solid var(--accent-cyan);
      box-shadow: 0 0 25px rgba(56, 189, 248, 0.4);
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .profile-avatar-img:hover {
      transform: scale(1.05);
      box-shadow: 0 0 35px rgba(56, 189, 248, 0.6);
    }

    .profile-avatar-edit-btn {
      position: absolute;
      bottom: 4px;
      right: 4px;
      background: #0284c7;
      color: #ffffff;
      border: 2px solid var(--card-bg);
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }

    .profile-username {
      font-size: 1.35rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 2px;
      font-family: 'Outfit', sans-serif;
    }

    .profile-rank-badge {
      font-size: 12px;
      color: #38bdf8;
      font-weight: 600;
      margin-bottom: 1.5rem;
      display: inline-block;
    }

    .profile-stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 9px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 13.5px;
    }

    .profile-stat-label {
      color: #94a3b8;
    }

    .profile-stat-val {
      font-weight: 600;
      color: #ffffff;
      font-family: 'JetBrains Mono', monospace;
    }

    .profile-sidebar-actions {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 1.75rem;
    }

    .btn-profile-subtle {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-color);
      color: #cbd5e1;
      padding: 7px 14px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-profile-subtle:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
    }

    /* Right Tabs Container */
    .profile-content-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 18px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
      overflow: hidden;
    }

    /* Navigation Bar matching Screenshot 2 */
    .profile-tabs-nav {
      display: flex;
      flex-wrap: wrap;
      background: rgba(7, 11, 20, 0.6);
      border-bottom: 1px solid var(--border-color);
      padding: 0.5rem 1rem 0;
      gap: 4px;
    }

    .profile-tab-link {
      padding: 10px 18px;
      color: #94a3b8;
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 600;
      border-radius: 10px 10px 0 0;
      border: 1px solid transparent;
      border-bottom: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
    }

    .profile-tab-link:hover {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.04);
    }

    .profile-tab-link.active {
      color: #38bdf8;
      background: var(--card-bg);
      border-color: var(--border-color);
      border-bottom: 2px solid var(--card-bg);
      margin-bottom: -1px;
    }

    .tab-pane-box {
      padding: 2.25rem 2rem;
    }

    /* Form Inputs matching Screenshot 2 */
    .profile-field-row {
      display: flex;
      align-items: center;
      margin-bottom: 1.25rem;
    }

    .profile-field-label {
      width: 170px;
      color: #94a3b8;
      font-size: 14px;
      font-weight: 500;
      flex-shrink: 0;
    }

    .profile-field-control {
      flex-grow: 1;
      max-width: 480px;
    }

    .custom-profile-input {
      background: rgba(7, 11, 20, 0.6) !important;
      border: 1px solid var(--border-color) !important;
      color: #ffffff !important;
      border-radius: 9px !important;
      padding: 9px 14px !important;
      font-size: 13.5px !important;
    }

    .custom-profile-input:focus {
      border-color: var(--accent-cyan) !important;
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.25) !important;
    }

    /* ---- Profile tab: settings cards ---- */
    .pf { display: flex; flex-direction: column; gap: 16px; max-width: 760px; }
    .pf-card { background: #0d1628; border: 1px solid rgba(255, 255, 255, 0.09); border-radius: 14px; padding: 20px 22px; }
    .pf-card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .pf-card-head .pf-card-title:only-child { margin-bottom: 0; }
    .pf-card-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #f8fafc; margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
    .pf-card-desc { font-size: 14px; color: #a3b1c6; margin: 0; line-height: 1.5; }
    .pf-card-desc strong { color: #e2e8f0; font-weight: 600; }

    .pf-list { margin: 16px 0 0; border-top: 1px solid rgba(255, 255, 255, 0.07); }
    .pf-item { display: grid; grid-template-columns: 170px 1fr; gap: 16px; padding: 13px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.07); }
    .pf-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .pf-empty { font-weight: 500; color: #8b9ab1; }
    .pf-empty a { color: #38bdf8; text-decoration: none; }
    .pf-empty a:hover { text-decoration: underline; }
    .pf-item dt { font-size: 14px; font-weight: 500; color: #a3b1c6; }
    .pf-item dd { font-size: 15px; font-weight: 600; color: #f8fafc; margin: 0; overflow-wrap: anywhere; }

    .pf-copy { display: flex; align-items: center; gap: 10px; margin-top: 14px; background: #070b14; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 6px 6px 6px 14px; }
    .pf-copy-url { flex: 1; min-width: 0; font-family: 'JetBrains Mono', monospace; font-size: 13.5px; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .pf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: nowrap; font-size: 14px; font-weight: 600; border-radius: 9px; padding: 8px 14px; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; }
    .pf-btn-ghost { background: rgba(255, 255, 255, 0.04); border-color: rgba(255, 255, 255, 0.14); color: #e2e8f0; }
    .pf-btn-ghost:hover { border-color: #38bdf8; color: #fff; background: rgba(56, 189, 248, 0.08); }
    .pf-btn-primary { background: #0284c7; color: #fff; padding: 9px 20px; }
    .pf-btn-primary:hover { background: #0ea5e9; }
    .pf-btn:disabled { opacity: .6; cursor: wait; }

    .pf-status { flex-shrink: 0; font-size: 12.5px; font-weight: 700; border-radius: 999px; padding: 4px 11px; color: #fbbf24; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.35); }
    .pf-status::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: currentColor; margin-right: 7px; vertical-align: 1px; }
    .pf-status.is-on { color: #34d399; background: rgba(52, 211, 153, 0.1); border-color: rgba(52, 211, 153, 0.35); }

    .pf-form { margin-top: 16px; }
    .pf-label { display: block; font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px; }
    .pf-field-row { display: flex; gap: 10px; }
    .pf-input { flex: 1; min-width: 0; background: #070b14; border: 1px solid rgba(255, 255, 255, 0.16); color: #fff; border-radius: 10px; padding: 9px 14px; font-size: 14.5px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .pf-input::placeholder { color: #64748b; }
    .pf-input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18); }
    .pf-hint { font-size: 13px; color: #8b9ab1; margin: 8px 0 0; line-height: 1.5; }
    .pf-hint code { color: #cbd5e1; background: rgba(255, 255, 255, 0.06); padding: 1px 6px; border-radius: 5px; font-size: 12.5px; }
    .pf-msg { display: none; margin-top: 12px; font-size: 14px; font-weight: 500; border-radius: 9px; padding: 9px 12px; }
    .pf-msg.ok { display: block; color: #6ee7b7; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.35); }
    .pf-msg.err { display: block; color: #fda4af; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.35); }

    /* Bookmarks */
    .bm-count { font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 700; color: #cbd5e1; background: rgba(255, 255, 255, 0.08); border-radius: 999px; padding: 2px 9px; }
    .bm-list { list-style: none; margin: 16px 0 0; padding: 0; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; overflow: hidden; }
    .bm-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 16px; background: #0a1222; transition: background .15s ease, opacity .2s ease; }
    .bm-row + .bm-row { border-top: 1px solid rgba(255, 255, 255, 0.07); }
    .bm-row:hover { background: #0f1a30; }
    .bm-row.is-removing { opacity: .4; pointer-events: none; }
    .bm-main { min-width: 0; }
    .bm-title { display: block; font-size: 15px; font-weight: 600; color: #f8fafc; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bm-title:hover { color: #38bdf8; }
    .bm-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 5px; font-size: 13px; color: #94a3b8; }
    .bm-dot { color: #475569; }
    .bm-diff { font-size: 11.5px; font-weight: 700; border-radius: 6px; padding: 1px 8px; border: 1px solid; }
    .bm-diff.is-easy { color: #34d399; background: rgba(52, 211, 153, 0.1); border-color: rgba(52, 211, 153, 0.35); }
    .bm-diff.is-medium { color: #fbbf24; background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.35); }
    .bm-diff.is-hard { color: #f87171; background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.35); }
    .bm-solved { font-size: 12.5px; font-weight: 600; color: #34d399; display: inline-flex; align-items: center; gap: 4px; }
    .bm-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .bm-open { padding: 6px 12px; font-size: 13.5px; }
    .bm-remove { width: 34px; height: 34px; border-radius: 9px; border: 1px solid transparent; background: transparent; color: #8b9ab1; display: inline-flex; align-items: center; justify-content: center; transition: all .15s ease; }
    .bm-remove:hover { color: #f87171; background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.3); }
    .bm-empty { text-align: center; padding: 36px 16px 20px; color: #a3b1c6; }
    .bm-empty > i { font-size: 30px; color: #64748b; }
    .bm-empty-title { font-size: 16px; font-weight: 700; color: #f1f5f9; margin: 8px 0 4px; }
    .bm-empty p { font-size: 14px; margin: 0; }
    @media (max-width: 640px) {
      .bm-row { flex-direction: column; align-items: stretch; gap: 10px; }
      .bm-title { white-space: normal; }
      .bm-actions .bm-open { flex: 1; }
    }

    /* Import / Export */
    .ie-file { flex: 1; min-width: 0; display: flex; align-items: center; gap: 12px; cursor: pointer; background: #070b14; border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 10px; padding: 5px 12px 5px 5px; transition: border-color .15s ease; }
    .ie-file:hover, .ie-file.is-over { border-color: #38bdf8; }
    .ie-file-btn { flex-shrink: 0; font-size: 13.5px; font-weight: 600; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.14); border-radius: 7px; padding: 5px 12px; }
    .ie-file-name { font-size: 14px; color: #8b9ab1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ie-file.has-file .ie-file-name { color: #f1f5f9; }
    .ie-check { display: flex; align-items: flex-start; gap: 9px; margin-top: 14px; font-size: 14px; color: #e2e8f0; cursor: pointer; width: fit-content; }
    .ie-check input { margin-top: 3px; width: 16px; height: 16px; accent-color: #f87171; flex-shrink: 0; }
    .ie-check-hint { color: #8b9ab1; }

    @media (max-width: 640px) {
      .tab-pane-box.pf { padding: 1.25rem 1rem; }
      .pf-card { padding: 16px; }
      .pf-card-head { flex-direction: column; gap: 12px; }
      .pf-item { grid-template-columns: 1fr; gap: 2px; }
      .pf-field-row { flex-direction: column; }
      .pf-field-row .pf-btn { width: 100%; }
    }

    .btn-update-profile {
      background: #0284c7;
      border: none;
      color: #ffffff;
      font-weight: 600;
      font-size: 14px;
      padding: 9px 24px;
      border-radius: 9px;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
    }

    .btn-update-profile:hover {
      background: #0369a1;
      transform: translateY(-1px);
    }

    /* Bookmark list items */
    .lab-progress-item {
      background: var(--card-inner);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      transition: border-color 0.2s ease;
    }

    .lab-progress-item:hover {
      border-color: rgba(56, 189, 248, 0.3);
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<div class="container py-5 flex-grow-1">
  <div class="row g-4">
    
    <!-- LEFT SIDEBAR CARD (matching Screenshot 2) -->
    <div class="col-lg-3 col-md-4">
      <div class="profile-sidebar-card">
        
        <div class="profile-avatar-wrapper">
          <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="profile-avatar-img user-menu-avatar-img" onclick="openAvatarModal(event)" title="Click to change avatar">
          <div class="profile-avatar-edit-btn" onclick="openAvatarModal(event)" title="Change Avatar">
            <i class="bi bi-pencil-fill"></i>
          </div>
        </div>

        <div class="profile-username"><?= htmlspecialchars($username) ?></div>
        <div class="profile-rank-badge"><?= htmlspecialchars($rankTitle) ?></div>

        <div class="profile-stat-row">
          <span class="profile-stat-label">Points</span>
          <span class="profile-stat-val text-info"><?= $totalXP ?></span>
        </div>

        <div class="profile-stat-row">
          <span class="profile-stat-label">Join date</span>
          <span class="profile-stat-val"><?= $created_at ?></span>
        </div>

        <div class="profile-stat-row">
          <span class="profile-stat-label">Solved Labs</span>
          <span class="profile-stat-val text-success"><?= $solvedCount ?></span>
        </div>

        <div class="profile-stat-row">
          <span class="profile-stat-label">Bookmarks</span>
          <span class="profile-stat-val text-warning" id="sideBookmarkCount"><?= $bookmarkCount ?></span>
        </div>

        <div class="profile-sidebar-actions">
          <a href="#" class="btn-profile-subtle flex-grow-1 justify-content-center text-danger" onclick="handleUserLogout(event)">
            <i class="bi bi-box-arrow-right"></i> Log out
          </a>
        </div>

      </div>
    </div>

    <!-- RIGHT MAIN CONTENT TABS (matching Screenshot 2 & 3) -->
    <div class="col-lg-9 col-md-8">
      <div class="profile-content-card">
        
        <!-- Tab Navigation Bar -->
        <div class="profile-tabs-nav">
          <a href="profile.php?tab=profile" class="profile-tab-link <?= ($activeTab === 'profile') ? 'active' : '' ?>">
            <i class="bi bi-person-fill"></i> Profile
          </a>
          <a href="profile.php?tab=bookmarks" class="profile-tab-link <?= ($activeTab === 'bookmarks') ? 'active' : '' ?>">
            <i class="bi bi-bookmark-fill"></i> Bookmarks
            <span class="badge rounded-pill bg-warning text-dark ms-1" id="tabBookmarkCount" style="font-size: 10px;<?= $bookmarkCount > 0 ? '' : ' display:none;' ?>"><?= $bookmarkCount ?></span>
          </a>
          <a href="profile.php?tab=import" class="profile-tab-link <?= ($activeTab === 'import') ? 'active' : '' ?>">
            <i class="bi bi-arrow-down-up"></i> Import / Export
          </a>
        </div>

        <!-- 1. PROFILE TAB (matching Screenshot 2) -->
        <?php if ($activeTab === 'profile'): ?>
          <?php $playerPath = '/navbar/player.php?user=' . urlencode($username); $playerUrl = krazeSiteUrl($playerPath); ?>
          <div class="tab-pane-box pf">

            <!-- Account details (read-only) -->
            <section class="pf-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Account details</h2>
                </div>
              </header>
              <dl class="pf-list">
                <div class="pf-item">
                  <dt>Full name</dt>
                  <dd><?php if (trim((string)$fullname) !== ''): ?><?= htmlspecialchars($fullname) ?><?php else: ?><span class="pf-empty">Not added</span><?php endif; ?></dd>
                </div>
                <div class="pf-item">
                  <dt>Email address</dt>
                  <dd><?= htmlspecialchars($email) ?></dd>
                </div>
                <div class="pf-item">
                  <dt>Member since</dt>
                  <dd><?= $created_at ?></dd>
                </div>
              </dl>
            </section>

            <!-- Public profile -->
            <section class="pf-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Public profile</h2>
                </div>
                <a class="pf-btn pf-btn-ghost" href="<?= htmlspecialchars($playerPath) ?>"><i class="bi bi-box-arrow-up-right"></i> View</a>
              </header>
              <div class="pf-copy">
                <span class="pf-copy-url" id="publicProfileUrl"><?= htmlspecialchars($playerUrl) ?></span>
                <button type="button" class="pf-btn pf-btn-ghost" id="btnCopyProfile" onclick="copyProfileUrl()"><i class="bi bi-clipboard"></i> <span>Copy link</span></button>
              </div>
            </section>

            <!-- GitHub repository (the only thing students change here) -->
            <section class="pf-card" id="github">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title"><i class="bi bi-github"></i> GitHub repository</h2>
                </div>
                <span class="pf-status <?= $githubRepo !== '' ? 'is-on' : '' ?>" id="githubStatus"><?= $githubRepo !== '' ? 'Connected' : 'Not connected' ?></span>
              </header>
              <form id="profileDetailsForm" class="pf-form" onsubmit="handleProfileUpdate(event)">
                <label class="pf-label" for="profileGithubRepo">Repository link</label>
                <div class="pf-field-row">
                  <input type="text" inputmode="url" id="profileGithubRepo" class="pf-input" value="<?= htmlspecialchars($githubRepo) ?>" placeholder="https://github.com/your-name/BugBountyReports" autocomplete="off" spellcheck="false">
                  <button type="submit" id="btnProfileUpdateSubmit" class="pf-btn pf-btn-primary">Save</button>
                </div>
                <div id="profileUpdateAlert" class="pf-msg" role="status" aria-live="polite"></div>
              </form>
            </section>

            <!-- Password (changed on the central KrazePlanet account page) -->
            <section class="pf-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Password</h2>
                  <p class="pf-card-desc">Change the password you use to sign in to KrazePlanet.</p>
                </div>
                <a class="pf-btn pf-btn-ghost" href="<?= htmlspecialchars($accountPageUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-key"></i> Change password</a>
              </header>
            </section>

          </div>
        <?php endif; ?>

        <!-- 3. BOOKMARK LIST TAB -->
        <?php if ($activeTab === 'bookmarks'): ?>
          <?php $solvedSet = array_flip(array_column($solvedLabs, 'lab_id')); ?>
          <div class="tab-pane-box pf">
            <section class="pf-card bm-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Bookmarks <span class="bm-count" id="bmCount"><?= count($bookmarkedLabs) ?></span></h2>
                  <p class="pf-card-desc">Labs you saved to come back to.</p>
                </div>
                <a class="pf-btn pf-btn-ghost" href="/index.php"><i class="bi bi-grid"></i> Browse labs</a>
              </header>

              <ul class="bm-list" id="bmList"<?= empty($bookmarkedLabs) ? ' hidden' : '' ?>>
                <?php foreach ($bookmarkedLabs as $b):
                  $bLab = $labCatalog['byId'][$b['lab_id']] ?? null;
                  $diff = $bLab['difficulty'] ?? '';
                  $diff = in_array($diff, ['easy', 'medium', 'hard'], true) ? $diff : '';
                  $isSolved = isset($solvedSet[$b['lab_id']]); ?>
                  <li class="bm-row">
                    <div class="bm-main">
                      <a class="bm-title" href="<?= htmlspecialchars(krazeLabUrl($b['lab_id'])) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($bLab['title'] ?? ucfirst(basename(str_replace('\\', '/', $b['lab_id'])))) ?></a>
                      <div class="bm-meta">
                        <?php if ($diff): ?><span class="bm-diff is-<?= $diff ?>"><?= ucfirst($diff) ?></span><?php endif; ?>
                        <?php if ($isSolved): ?><span class="bm-solved"><i class="bi bi-check-circle-fill"></i> Solved</span><?php endif; ?>
                        <span><?= htmlspecialchars($bLab['category'] ?? 'Another KrazePlanet site') ?></span>
                        <span class="bm-dot">·</span>
                        <span title="<?= htmlspecialchars($b['created_at']) ?>">Saved <?= date('M j, Y', strtotime($b['created_at'])) ?></span>
                      </div>
                    </div>
                    <div class="bm-actions">
                      <a class="pf-btn pf-btn-ghost bm-open" href="<?= htmlspecialchars(krazeLabUrl($b['lab_id'])) ?>" target="_blank" rel="noopener">Open lab <i class="bi bi-arrow-up-right"></i></a>
                      <button type="button" class="bm-remove" title="Remove bookmark" aria-label="Remove bookmark" data-lab-id="<?= htmlspecialchars($b['lab_id']) ?>"><i class="bi bi-x-lg"></i></button>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>

              <div class="bm-empty" id="bmEmpty"<?= empty($bookmarkedLabs) ? '' : ' hidden' ?>>
                <i class="bi bi-bookmark"></i>
                <div class="bm-empty-title">No bookmarks yet</div>
                <p>On the labs page, open a lab's <strong>⋯</strong> menu and choose <strong>Bookmark</strong>.</p>
              </div>
            </section>
          </div>
        <?php endif; ?>


        <!-- 6. IMPORT / EXPORT TAB (matching Screenshot 3) -->
        <?php if ($activeTab === 'import'): ?>
          <div class="tab-pane-box pf">

            <!-- Export -->
            <section class="pf-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Export</h2>
                  <p class="pf-card-desc">Download your solved labs and bookmarks as a JSON file.</p>
                </div>
                <a class="pf-btn pf-btn-ghost" href="/api/auth_api.php?action=export_account_data"><i class="bi bi-download"></i> Export</a>
              </header>
            </section>

            <!-- Import -->
            <section class="pf-card">
              <header class="pf-card-head">
                <div>
                  <h2 class="pf-card-title">Import</h2>
                  <p class="pf-card-desc">Upload a JSON file you exported from KrazePlanet.</p>
                </div>
              </header>

              <form id="importAccountForm" class="pf-form" onsubmit="handleAccountImport(event)">
                <div class="pf-field-row">
                  <label class="ie-file" for="importFileInput" id="importDrop">
                    <span class="ie-file-btn">Choose file</span>
                    <span class="ie-file-name" id="importFileName">No file chosen</span>
                    <input type="file" id="importFileInput" accept=".json,application/json" hidden>
                  </label>
                  <button type="submit" id="btnImportSubmit" class="pf-btn pf-btn-primary"><i class="bi bi-upload"></i> Import</button>
                </div>
                <label class="ie-check">
                  <input type="checkbox" id="importReplace">
                  <span>Replace my current progress <span class="ie-check-hint">(deletes your solved labs and bookmarks first)</span></span>
                </label>
                <div id="importAlert" class="pf-msg" role="status" aria-live="polite"></div>
              </form>
            </section>

          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<script>
// Arriving from a lab's "Write Report" (profile.php?tab=profile#github): focus the GitHub field
// Runs after load so the browser's own jump to #github doesn't override it.
window.addEventListener('load', () => setTimeout(() => {
  if (location.hash !== '#github') return;
  const gh = document.getElementById('profileGithubRepo');
  if (!gh) return;
  gh.scrollIntoView({ block: 'center' });
  gh.focus({ preventScroll: true });
  gh.style.boxShadow = '0 0 0 3px rgba(56, 189, 248, 0.45)';
  gh.addEventListener('blur', () => { gh.style.boxShadow = ''; }, { once: true });
}, 60));

function copyProfileUrl() {
  const el = document.getElementById('publicProfileUrl');
  const label = document.querySelector('#btnCopyProfile span');
  if (!el) return;
  navigator.clipboard.writeText(el.textContent.trim()).then(() => {
    if (!label) return;
    label.textContent = 'Copied!';
    setTimeout(() => { label.textContent = 'Copy link'; }, 1600);
  });
}

function showAlert(id, msg, isSuccess = false) {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = `alert py-2 px-3 small mb-3 border-0 ${isSuccess ? 'alert-success' : 'alert-danger'}`;
  el.style.backgroundColor = isSuccess ? 'rgba(16, 185, 129, 0.2)' : 'rgba(244, 63, 94, 0.2)';
  el.style.color = isSuccess ? '#34d399' : '#fb7185';
  el.style.border = `1px solid ${isSuccess ? 'rgba(16, 185, 129, 0.4)' : 'rgba(244, 63, 94, 0.4)'}`;
  el.innerText = msg;
  el.style.display = 'block';
}

function handleProfileUpdate(e) {
  e.preventDefault();
  const btn = document.getElementById('btnProfileUpdateSubmit');
  const msg = document.getElementById('profileUpdateAlert');
  const status = document.getElementById('githubStatus');
  const ghInput = document.getElementById('profileGithubRepo');
  const say = (text, ok) => { msg.className = 'pf-msg ' + (ok ? 'ok' : 'err'); msg.textContent = text; };
  btn.disabled = true;
  btn.textContent = 'Saving...';

  // The Profile tab only saves the GitHub repository; everything else on it is read-only.
  const formData = new FormData();
  formData.append('action', 'update_github_repo');
  formData.append('github_repo', ghInput.value.trim());

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Save';
      if (data.success) {
        const repo = data.github_repo || '';
        ghInput.value = repo; // show the cleaned-up link
        status.textContent = repo ? 'Connected' : 'Not connected';
        status.classList.toggle('is-on', !!repo);
        say(repo ? 'Saved. Write Report will now use this repository.' : 'Disconnected. Write Report is turned off until you add a repository.', true);
        fetch('/navbar/portal.php?action=clear_profile_cache');
      } else {
        say(data.error || 'Could not save. Please try again.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'Save';
      say('Network error. Please try again.', false);
    });
}

// Bookmarks: remove one without leaving the page.
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.bm-remove');
  if (!btn) return;
  const row = btn.closest('.bm-row');
  row.classList.add('is-removing');
  const fd = new FormData();
  fd.append('lab_id', btn.dataset.labId);
  fetch('/navbar/portal.php?action=toggle_bookmark', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success || res.isBookmarked) {
        // It was already removed elsewhere (toggle added it back): undo that, then drop the row.
        if (res.success && res.isBookmarked) return fetch('/navbar/portal.php?action=toggle_bookmark', { method: 'POST', body: fd }).then(r => r.json());
        throw new Error(res.message || 'failed');
      }
      return res;
    })
    .then(res => {
      row.remove();
      const n = (res.data && res.data.bookmarkedLabs) ? res.data.bookmarkedLabs.length : document.querySelectorAll('.bm-row').length;
      document.getElementById('bmCount').textContent = n;
      const tab = document.getElementById('tabBookmarkCount');
      if (tab) { tab.textContent = n; tab.style.display = n ? '' : 'none'; }
      const side = document.getElementById('sideBookmarkCount');
      if (side) side.textContent = n;
      if (!n) { document.getElementById('bmList').hidden = true; document.getElementById('bmEmpty').hidden = false; }
      // Let an open labs page in another tab update too.
      try {
        const key = 'kp_lab_state_<?= (int)($_SESSION['user_id'] ?? 0) ?>';
        const s = JSON.parse(localStorage.getItem(key) || '{}');
        localStorage.setItem(key, JSON.stringify({ solvedLabs: res.data ? res.data.solvedLabs : (s.solvedLabs || []), bookmarkedLabs: res.data ? res.data.bookmarkedLabs : [] }));
      } catch (_) {}
    })
    .catch(() => {
      row.classList.remove('is-removing');
      alert('Could not remove this bookmark. Please try again.');
    });
});

// Backup file picker: show the chosen file's name, and accept drag & drop.
(function () {
  const drop = document.getElementById('importDrop');
  const input = document.getElementById('importFileInput');
  const nameEl = document.getElementById('importFileName');
  if (!drop || !input) return;
  const show = () => {
    const f = input.files[0];
    nameEl.textContent = f ? f.name : 'No file chosen';
    drop.classList.toggle('has-file', !!f);
  };
  input.addEventListener('change', show);
  ['dragenter', 'dragover'].forEach(t => drop.addEventListener(t, e => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'drop'].forEach(t => drop.addEventListener(t, () => drop.classList.remove('is-over')));
  drop.addEventListener('drop', e => {
    e.preventDefault();
    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(); }
  });
})();

function handleAccountImport(e) {
  e.preventDefault();
  const btn = document.getElementById('btnImportSubmit');
  const msg = document.getElementById('importAlert');
  const say = (text, ok) => { msg.className = 'pf-msg ' + (ok ? 'ok' : 'err'); msg.textContent = text; };
  const idle = '<i class="bi bi-upload"></i> Import';

  const fileInput = document.getElementById('importFileInput');
  const importMode = document.getElementById('importReplace').checked ? 'replace' : 'merge';

  if (!fileInput.files.length) {
    say('Choose a JSON file to import.', false);
    return;
  }
  if (importMode === 'replace' && !confirm('Delete your current solved labs and bookmarks and import this file instead? This can\'t be undone.')) {
    return;
  }

  const formData = new FormData();
  formData.append('action', 'import_account_data');
  formData.append('import_mode', importMode);
  formData.append('backup_file', fileInput.files[0]);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing...';
  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = idle;
      if (data.success) {
        say(data.message + ' Refreshing...', true);
        setTimeout(() => { location.reload(); }, 1500);
      } else {
        say(data.error || 'Could not import this file.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = idle;
      say('Network error. Please try again.', false);
    });
}
</script>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
