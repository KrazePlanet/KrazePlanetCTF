<?php
// profile.php - KrazePlanet User Dashboard with Profile, Continue, Bookmarks, Notifications, Settings & Import/Export
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?modal=login&redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'profile';
$allowedTabs = ['profile', 'continue', 'bookmarks', 'notifications', 'import', 'settings'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'profile';
}

$user = null;
$solvedLabs = [];
$bookmarkedLabs = [];
$continueLabs = [];
$allNotifications = [];

if ($pdo) {
    // 1. User Info
    $stmt = $pdo->prepare("SELECT id, username, fullname, email, phone, avatar, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Solved Labs
    $stmtSolved = $pdo->prepare("SELECT lab_id, difficulty, points, solved_at FROM user_solved_labs WHERE user_id = ? ORDER BY solved_at DESC");
    $stmtSolved->execute([$userId]);
    $solvedLabs = $stmtSolved->fetchAll(PDO::FETCH_ASSOC);

    // 3. Bookmarks
    $stmtBookmarked = $pdo->prepare("SELECT lab_id, created_at FROM user_bookmarks WHERE user_id = ? ORDER BY created_at DESC");
    $stmtBookmarked->execute([$userId]);
    $bookmarkedLabs = $stmtBookmarked->fetchAll(PDO::FETCH_ASSOC);

    // 4. Continue (In-progress labs visited but NOT marked as solved)
    $stmtContinue = $pdo->prepare("
        SELECT h.* 
        FROM user_lab_history h
        LEFT JOIN user_solved_labs s ON h.user_id = s.user_id AND h.lab_id = s.lab_id
        WHERE h.user_id = ? AND s.id IS NULL
        ORDER BY h.last_accessed_at DESC
    ");
    $stmtContinue->execute([$userId]);
    $continueLabs = $stmtContinue->fetchAll(PDO::FETCH_ASSOC);

    // 5. All Notifications
    $stmtNotifs = $pdo->prepare("
        SELECT * FROM user_notifications 
        WHERE (user_id IS NULL OR user_id = ?) 
        ORDER BY created_at DESC
    ");
    $stmtNotifs->execute([$userId]);
    $allNotifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);
}

$username = $user['username'] ?? $_SESSION['username'] ?? 'Trainee';
$fullname = $user['fullname'] ?? '';
$email = $user['email'] ?? $_SESSION['user_email'] ?? 'trainee@krazeplanet.com';
$phone = $user['phone'] ?? '';
$created_at = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'May 23, 2026';
$avatarUrl = !empty($user['avatar']) ? $user['avatar'] : (!empty($_SESSION['avatar']) ? $_SESSION['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($username)));

$solvedCount = count($solvedLabs);
$bookmarkCount = count($bookmarkedLabs);
$continueCount = count($continueLabs);
$totalLabsAvailable = 260;

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

    /* Continue / Bookmark Lab Item Cards */
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

    .lab-badge-pill {
      background: rgba(56, 189, 248, 0.12);
      border: 1px solid rgba(56, 189, 248, 0.25);
      color: #38bdf8;
      font-size: 11px;
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
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
          <span class="profile-stat-val text-warning"><?= $bookmarkCount ?></span>
        </div>

        <div class="profile-stat-row">
          <span class="profile-stat-label">In Progress</span>
          <span class="profile-stat-val text-info"><?= $continueCount ?></span>
        </div>

        <div class="profile-sidebar-actions">
          <a href="contact.php" class="btn-profile-subtle flex-grow-1 justify-content-center">
            <i class="bi bi-heart-fill text-danger"></i> Donate
          </a>
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
          <a href="profile.php?tab=continue" class="profile-tab-link <?= ($activeTab === 'continue') ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Continue
            <?php if ($continueCount > 0): ?>
              <span class="badge rounded-pill bg-info text-dark ms-1" style="font-size: 10px;"><?= $continueCount ?></span>
            <?php endif; ?>
          </a>
          <a href="profile.php?tab=bookmarks" class="profile-tab-link <?= ($activeTab === 'bookmarks') ? 'active' : '' ?>">
            <i class="bi bi-bookmark-fill"></i> Bookmark List
            <?php if ($bookmarkCount > 0): ?>
              <span class="badge rounded-pill bg-warning text-dark ms-1" style="font-size: 10px;"><?= $bookmarkCount ?></span>
            <?php endif; ?>
          </a>
          <a href="profile.php?tab=notifications" class="profile-tab-link <?= ($activeTab === 'notifications') ? 'active' : '' ?>">
            <i class="bi bi-bell-fill"></i> Notification
          </a>
          <a href="profile.php?tab=settings" class="profile-tab-link <?= ($activeTab === 'settings') ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i> Settings
          </a>
          <a href="profile.php?tab=import" class="profile-tab-link <?= ($activeTab === 'import') ? 'active' : '' ?>">
            <i class="bi bi-box-arrow-in-right"></i> Import / Export
          </a>
        </div>

        <!-- 1. PROFILE TAB (matching Screenshot 2) -->
        <?php if ($activeTab === 'profile'): ?>
          <div class="tab-pane-box">
            <div id="profileUpdateAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

            <form id="profileDetailsForm" onsubmit="handleProfileUpdate(event)">
              <div class="profile-field-row">
                <div class="profile-field-label">Join date</div>
                <div class="profile-field-control">
                  <input type="text" class="form-control custom-profile-input" value="<?= $created_at ?>" readonly>
                </div>
              </div>

              <div class="profile-field-row">
                <div class="profile-field-label">Email address</div>
                <div class="profile-field-control">
                  <input type="email" id="profileEmailInput" class="form-control custom-profile-input" value="<?= htmlspecialchars($email) ?>" required>
                </div>
              </div>

              <div class="profile-field-row">
                <div class="profile-field-label">Username</div>
                <div class="profile-field-control">
                  <input type="text" class="form-control custom-profile-input" value="<?= htmlspecialchars($username) ?>" readonly>
                </div>
              </div>

              <div class="profile-field-row">
                <div class="profile-field-label">Public profile</div>
                <div class="profile-field-control">
                  <div class="input-group">
                    <input type="text" id="publicProfileUrl" class="form-control custom-profile-input font-monospace small" value="http://localhost/profile.php?user=<?= htmlspecialchars($username) ?>" readonly style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;">
                    <button type="button" class="btn btn-outline-info px-3" onclick="copyProfileUrl()" style="border-top-left-radius: 0; border-bottom-left-radius: 0; font-size: 13px;">
                      <i class="bi bi-clipboard me-1"></i> Copy
                    </button>
                  </div>
                  <div class="text-secondary small mt-1" style="font-size: 11px;">Share this link so instructors and peers can view your solved labs progress.</div>
                </div>
              </div>

              <div class="profile-field-row">
                <div class="profile-field-label">Reading list visibility</div>
                <div class="profile-field-control">
                  <div class="text-secondary small mb-2" style="font-size: 12px;">Controls whether your completed and in-progress laboratories appear on your public profile.</div>
                  <div class="d-flex gap-4">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="profileVisibility" id="visPrivate" value="private">
                      <label class="form-check-label text-light small" for="visPrivate">Private</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="profileVisibility" id="visPublic" value="public" checked>
                      <label class="form-check-label text-light small" for="visPublic">Public</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="profile-field-row mb-4">
                <div class="profile-field-label"></div>
                <div class="profile-field-control">
                  <a href="profile.php?tab=settings" class="small text-info text-decoration-none fw-semibold">
                    <i class="bi bi-key-fill me-1"></i> Change password
                  </a>
                </div>
              </div>

              <div class="profile-field-row">
                <div class="profile-field-label"></div>
                <div class="profile-field-control">
                  <button type="submit" id="btnProfileUpdateSubmit" class="btn-update-profile">Update</button>
                </div>
              </div>
            </form>
          </div>
        <?php endif; ?>

        <!-- 2. CONTINUE TAB (In-Progress Labs User Started but Not Yet Solved) -->
        <?php if ($activeTab === 'continue'): ?>
          <div class="tab-pane-box">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div>
                <h5 class="fw-bold text-white mb-1"><i class="bi bi-clock-history text-info me-2"></i> Continue Learning</h5>
                <p class="text-secondary small mb-0">Labs you have accessed and are currently solving. Pick up right where you left off!</p>
              </div>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                <?= count($continueLabs) ?> In-Progress Labs
              </span>
            </div>

            <?php if (!empty($continueLabs)): ?>
              <div class="continue-labs-list">
                <?php foreach ($continueLabs as $cl): ?>
                  <div class="lab-progress-item">
                    <div class="d-flex align-items-center gap-3">
                      <span class="lab-badge-pill"><?= htmlspecialchars($cl['lab_badge'] ?? 'LAB') ?></span>
                      <div>
                        <div class="fw-bold text-light small"><?= htmlspecialchars($cl['lab_title'] ?? 'Security Laboratory') ?></div>
                        <div class="text-secondary small mt-1" style="font-size: 11px;">
                          <span class="text-muted me-2"><i class="bi bi-folder2 me-1"></i> <?= htmlspecialchars($cl['lab_category'] ?? 'Web Security') ?></span>
                          <span><i class="bi bi-clock me-1"></i> Last opened <?= date('M d, H:i', strtotime($cl['last_accessed_at'])) ?></span>
                        </div>
                      </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                      <a href="<?= htmlspecialchars($cl['lab_url'] ?? $cl['lab_id']) ?>" target="_blank" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 12.5px; border-radius: 8px;">
                        <i class="bi bi-play-circle-fill me-1"></i> Resume Lab
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="bi bi-check2-circle text-success fs-1 mb-2 d-block"></i>
                <div class="fw-bold text-light">All caught up!</div>
                <p class="text-secondary small mt-1">You have no pending in-progress labs. Open any laboratory from the catalog to practice.</p>
                <a href="index.php" class="btn btn-outline-info btn-sm mt-2 px-3 py-2" style="border-radius: 8px;">Explore Laboratories &rarr;</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- 3. BOOKMARK LIST TAB -->
        <?php if ($activeTab === 'bookmarks'): ?>
          <div class="tab-pane-box">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div>
                <h5 class="fw-bold text-white mb-1"><i class="bi bi-bookmark-fill text-warning me-2"></i> Bookmarked Laboratories</h5>
                <p class="text-secondary small mb-0">Quick access to labs you have starred for future study or review.</p>
              </div>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2">
                <?= count($bookmarkedLabs) ?> Bookmarks
              </span>
            </div>

            <?php if (!empty($bookmarkedLabs)): ?>
              <div class="bookmarks-list">
                <?php foreach ($bookmarkedLabs as $b): ?>
                  <div class="lab-progress-item">
                    <div class="d-flex align-items-center gap-3">
                      <i class="bi bi-star-fill text-warning fs-5"></i>
                      <div>
                        <div class="fw-bold text-light small"><?= htmlspecialchars($b['lab_id']) ?></div>
                        <div class="text-secondary small mt-1" style="font-size: 11px;">
                          <i class="bi bi-calendar-event me-1"></i> Bookmarked on <?= date('M d, Y', strtotime($b['created_at'])) ?>
                        </div>
                      </div>
                    </div>

                    <a href="<?= htmlspecialchars($b['lab_id']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-1 px-3" style="font-size: 12.5px; border-radius: 8px;">
                      <i class="bi bi-box-arrow-up-right me-1"></i> Open Lab
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="bi bi-bookmark-star text-secondary fs-1 mb-2 d-block"></i>
                <div class="fw-bold text-light">No bookmarked labs yet</div>
                <p class="text-secondary small mt-1">Click the star icon on any laboratory card to save it here for fast access.</p>
                <a href="index.php" class="btn btn-outline-info btn-sm mt-2 px-3 py-2" style="border-radius: 8px;">Browse Labs &rarr;</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- 4. NOTIFICATION TAB (All Notifications History) -->
        <?php if ($activeTab === 'notifications'): ?>
          <div class="tab-pane-box">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div>
                <h5 class="fw-bold text-white mb-1"><i class="bi bi-bell-fill text-info me-2"></i> Notifications Center</h5>
                <p class="text-secondary small mb-0">Full record of all your assignment notices, platform alerts, and lab updates.</p>
              </div>
              <span class="badge bg-primary bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                <?= count($allNotifications) ?> Total Notifications
              </span>
            </div>

            <?php if (!empty($allNotifications)): ?>
              <div class="notifications-history-list">
                <?php foreach ($allNotifications as $n): ?>
                  <div class="lab-progress-item">
                    <div class="d-flex align-items-start gap-3">
                      <div class="rounded-circle <?= htmlspecialchars($n['icon_bg'] ?? 'bg-info bg-opacity-10 text-info') ?> p-2 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 38px; height: 38px; font-size: 16px;">
                        <i class="bi <?= htmlspecialchars($n['icon'] ?? 'bi-bell-fill') ?>"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-light small" style="font-size: 13.5px;"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="text-secondary small mt-1" style="line-height: 1.5; font-size: 12px;"><?= htmlspecialchars($n['message']) ?></div>
                        <div class="text-muted small mt-1" style="font-size: 11px;"><i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($n['created_at'])) ?></div>
                      </div>
                    </div>

                    <?php if (!empty($n['link'])): ?>
                      <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-outline-info py-1 px-3 flex-shrink-0" style="font-size: 12.5px; border-radius: 8px;">
                        View <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="bi bi-bell-slash text-secondary fs-1 mb-2 d-block"></i>
                <div class="fw-bold text-light">No notifications</div>
                <p class="text-secondary small mt-1">You are all caught up on all platform alerts.</p>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- 5. SETTINGS TAB -->
        <?php if ($activeTab === 'settings'): ?>
          <div class="tab-pane-box">
            <h5 class="fw-bold text-white mb-1"><i class="bi bi-gear-fill text-secondary me-2"></i> Account Settings</h5>
            <p class="text-secondary small mb-4">Manage your personal profile information, contact data, and security credentials.</p>

            <div id="settingsAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

            <form id="accountSettingsForm" onsubmit="handleSettingsUpdate(event)">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Full Name</label>
                <input type="text" id="settingsFullname" class="form-control custom-profile-input" value="<?= htmlspecialchars($fullname) ?>" placeholder="John Doe">
              </div>

              <div class="mb-4">
                <label class="form-label small text-secondary fw-semibold">Email Address</label>
                <input type="email" id="settingsEmail" class="form-control custom-profile-input" value="<?= htmlspecialchars($email) ?>" required>
              </div>

              <hr class="border-secondary border-opacity-25 my-4">

              <h6 class="fw-bold text-white small mb-3"><i class="bi bi-shield-lock-fill text-warning me-2"></i> Change Password</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Current Password</label>
                  <input type="password" id="settingsCurrentPass" class="form-control custom-profile-input" placeholder="••••••••">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">New Password</label>
                  <input type="password" id="settingsNewPass" class="form-control custom-profile-input" placeholder="••••••••" minlength="6">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Confirm Password</label>
                  <input type="password" id="settingsConfirmPass" class="form-control custom-profile-input" placeholder="••••••••" minlength="6">
                </div>
              </div>

              <button type="submit" id="btnSettingsSubmit" class="btn-update-profile">Save Settings</button>
            </form>
          </div>
        <?php endif; ?>

        <!-- 6. IMPORT / EXPORT TAB (matching Screenshot 3) -->
        <?php if ($activeTab === 'import'): ?>
          <div class="tab-pane-box">
            
            <ul class="nav nav-pills mb-4" id="importExportTabs" role="tablist">
              <li class="nav-item">
                <button class="nav-link active px-4 py-2 small fw-semibold" id="pills-import-tab" data-bs-toggle="pill" data-bs-target="#pills-import" type="button" role="tab">Import</button>
              </li>
              <li class="nav-item">
                <button class="nav-link px-4 py-2 small fw-semibold" id="pills-export-tab" data-bs-toggle="pill" data-bs-target="#pills-export" type="button" role="tab">Export</button>
              </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
              
              <!-- IMPORT SUB-TAB (matching Screenshot 3) -->
              <div class="tab-pane fade show active" id="pills-import" role="tabpanel">
                <div class="text-secondary small mb-4" style="line-height: 1.7; font-size: 13px;">
                  Import your complete account progress, bookmarks, and in-progress laboratories from a JSON backup.<br>
                  • Your backup file will be validated securely.<br>
                  • If an imported laboratory exists in the platform, it will be restored immediately.<br>
                  • This process will take a moment, please be patient.
                </div>

                <div id="importAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

                <form id="importAccountForm" onsubmit="handleAccountImport(event)">
                  <div class="profile-field-row">
                    <div class="profile-field-label">Backup File (.json)</div>
                    <div class="profile-field-control">
                      <input type="file" id="importFileInput" class="form-control custom-profile-input" accept=".json">
                    </div>
                  </div>

                  <div class="profile-field-row">
                    <div class="profile-field-label">Or Paste JSON Data</div>
                    <div class="profile-field-control">
                      <textarea id="importJsonTextarea" class="form-control custom-profile-input font-monospace small" rows="4" placeholder='{"platform":"KrazePlanet Security", ...}'></textarea>
                    </div>
                  </div>

                  <div class="profile-field-row">
                    <div class="profile-field-label">Import mode</div>
                    <div class="profile-field-control">
                      <div class="d-flex gap-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="importModeRadio" id="modeMerge" value="merge" checked>
                          <label class="form-check-label text-light small" for="modeMerge">Merge</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="importModeRadio" id="modeReplace" value="replace">
                          <label class="form-check-label text-light small" for="modeReplace">Replace</label>
                        </div>
                      </div>
                      <div class="text-secondary small" style="font-size: 11.5px; line-height: 1.5;">
                        <strong>Merge:</strong> Merge your existing solved/bookmarked labs with the importing list.<br>
                        <strong>Replace:</strong> Delete your current list then use the importing list.
                      </div>
                    </div>
                  </div>

                  <div class="profile-field-row mt-4">
                    <div class="profile-field-label"></div>
                    <div class="profile-field-control">
                      <button type="submit" id="btnImportSubmit" class="btn-update-profile px-4">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Import
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <!-- EXPORT SUB-TAB (matching user requirement) -->
              <div class="tab-pane fade" id="pills-export" role="tabpanel">
                <div class="text-secondary small mb-4" style="line-height: 1.7; font-size: 13px;">
                  Export all your account information, profile settings, solved laboratory records, bookmarks, and in-progress tasks into a secure JSON backup.<br>
                  • Keep this file safe as a personal backup.<br>
                  • You can restore your entire progress onto another browser or computer at any time.
                </div>

                <div class="p-4 rounded-3 text-center" style="background: var(--card-inner); border: 1px dashed rgba(56, 189, 248, 0.3);">
                  <i class="bi bi-file-earmark-arrow-down-fill text-info fs-1 mb-2 d-block"></i>
                  <div class="fw-bold text-white mb-1">Export KrazePlanet Account Data</div>
                  <p class="text-secondary small mb-3">Backup contains <?= $solvedCount ?> solved labs, <?= $bookmarkCount ?> bookmarks, and your active avatar & profile settings.</p>
                  
                  <a href="/api/auth_api.php?action=export_account_data" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: 9px; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);">
                    <i class="bi bi-download me-1"></i> Download Backup JSON
                  </a>
                </div>
              </div>

            </div>

          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<script>
function copyProfileUrl() {
  const el = document.getElementById('publicProfileUrl');
  if (el) {
    el.select();
    navigator.clipboard.writeText(el.value);
    alert('Public profile link copied to clipboard!');
  }
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
  btn.disabled = true;
  btn.innerText = 'Updating...';

  const formData = new FormData();
  formData.append('action', 'update_profile');
  formData.append('email', document.getElementById('profileEmailInput').value.trim());

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerText = 'Update';
      if (data.success) {
        showAlert('profileUpdateAlert', 'Profile updated successfully!', true);
      } else {
        showAlert('profileUpdateAlert', data.error || 'Failed to update profile.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerText = 'Update';
      showAlert('profileUpdateAlert', 'Network error.', false);
    });
}

function handleSettingsUpdate(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSettingsSubmit');
  btn.disabled = true;
  btn.innerText = 'Saving...';

  const formData = new FormData();
  formData.append('action', 'update_settings');
  formData.append('fullname', document.getElementById('settingsFullname').value.trim());
  formData.append('email', document.getElementById('settingsEmail').value.trim());
  formData.append('current_password', document.getElementById('settingsCurrentPass').value);
  formData.append('new_password', document.getElementById('settingsNewPass').value);

  const confirmPass = document.getElementById('settingsConfirmPass').value;
  const newPass = document.getElementById('settingsNewPass').value;

  if (newPass && newPass !== confirmPass) {
    btn.disabled = false;
    btn.innerText = 'Save Settings';
    showAlert('settingsAlert', 'New passwords do not match.', false);
    return;
  }

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerText = 'Save Settings';
      if (data.success) {
        showAlert('settingsAlert', 'Account settings saved successfully!', true);
      } else {
        showAlert('settingsAlert', data.error || 'Failed to update settings.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerText = 'Save Settings';
      showAlert('settingsAlert', 'Network error.', false);
    });
}

function handleAccountImport(e) {
  e.preventDefault();
  const btn = document.getElementById('btnImportSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';

  const fileInput = document.getElementById('importFileInput');
  const jsonTextarea = document.getElementById('importJsonTextarea');
  const importMode = document.querySelector('input[name="importModeRadio"]:checked')?.value || 'merge';

  const formData = new FormData();
  formData.append('action', 'import_account_data');
  formData.append('import_mode', importMode);

  if (fileInput.files.length > 0) {
    formData.append('backup_file', fileInput.files[0]);
  } else if (jsonTextarea.value.trim()) {
    formData.append('json_data', jsonTextarea.value.trim());
  } else {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Import';
    showAlert('importAlert', 'Please select a backup file or paste JSON data.', false);
    return;
  }

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Import';
      if (data.success) {
        showAlert('importAlert', data.message, true);
        setTimeout(() => { location.reload(); }, 1500);
      } else {
        showAlert('importAlert', data.error || 'Import failed.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Import';
      showAlert('importAlert', 'Network error during import.', false);
    });
}
</script>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
