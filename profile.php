<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$user = null;
$solvedLabs = [];
$bookmarkedLabs = [];

if ($pdo) {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Fetch solved labs with timestamp
    $stmtSolved = $pdo->prepare("SELECT lab_id, solved_at FROM user_solved_labs WHERE user_id = ? ORDER BY solved_at DESC");
    $stmtSolved->execute([$userId]);
    $solvedLabs = $stmtSolved->fetchAll();

    // Fetch bookmarked labs
    $stmtBookmarked = $pdo->prepare("SELECT lab_id, created_at FROM user_bookmarks WHERE user_id = ? ORDER BY created_at DESC");
    $stmtBookmarked->execute([$userId]);
    $bookmarkedLabs = $stmtBookmarked->fetchAll();
}

$username = $user['username'] ?? $_SESSION['username'] ?? 'Trainee';
$email = $user['email'] ?? $_SESSION['user_email'] ?? 'trainee@vexiumctf.com';
$created_at = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Recently';

$solvedCount = count($solvedLabs);
$bookmarkCount = count($bookmarkedLabs);
$totalLabsAvailable = 217;
$completionPercentage = round(($solvedCount / $totalLabsAvailable) * 100, 1);

// Determine rank title
if ($solvedCount >= 50) {
    $rankTitle = "Cyber Security Elite";
    $rankBadge = "bg-danger";
} elseif ($solvedCount >= 20) {
    $rankTitle = "Penetration Tester";
    $rankBadge = "bg-warning text-dark";
} elseif ($solvedCount >= 5) {
    $rankTitle = "Vulnerability Hunter";
    $rankBadge = "bg-info text-dark";
} else {
    $rankTitle = "Security Trainee";
    $rankBadge = "bg-success";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Profile - <?php echo htmlspecialchars($username); ?> | VexiumCTF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #070b14;
      --accent-green: #10b981;
      --font-primary: 'Inter', sans-serif;
      --font-heading: 'Outfit', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
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
    .profile-card {
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
    }
    .avatar-circle {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: #ffffff;
      font-size: 2.2rem;
      font-weight: 800;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
      font-family: var(--font-heading);
    }
    .stat-box {
      background: rgba(7, 11, 20, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      padding: 1.25rem;
      text-align: center;
    }
    .stat-number {
      font-size: 2rem;
      font-weight: 800;
      font-family: var(--font-heading);
      background: linear-gradient(135deg, #34d399, #38bdf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .stat-label {
      font-size: 0.78rem;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 600;
    }
    .nav-tabs .nav-link {
      color: #94a3b8;
      border: none;
      border-bottom: 2px solid transparent;
      padding: 0.75rem 1.25rem;
      font-weight: 600;
      transition: all 0.2s;
    }
    .nav-tabs .nav-link.active {
      color: #10b981;
      background: transparent;
      border-bottom: 2px solid #10b981;
    }
    .table-dark-custom {
      background: rgba(7, 11, 20, 0.5);
      border-radius: 12px;
      overflow: hidden;
    }
    .table-dark-custom th {
      background: rgba(15, 23, 42, 0.9);
      color: #94a3b8;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1rem;
    }
    .table-dark-custom td {
      padding: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      color: #f1f5f9;
      vertical-align: middle;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="container flex-grow-1 py-5">
    <!-- Header Profile Banner Card -->
    <div class="profile-card mb-4">
      <div class="row align-items-center g-4">
        <div class="col-md-auto text-center">
          <div class="avatar-circle mx-auto">
            <?php echo strtoupper(substr($username, 0, 1)); ?>
          </div>
        </div>
        <div class="col-md">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h2 class="fw-bold text-light mb-0" style="font-family: 'Outfit';"><?php echo htmlspecialchars($username); ?></h2>
            <span class="badge <?php echo $rankBadge; ?> px-3 py-2 rounded-pill"><?php echo $rankTitle; ?></span>
          </div>
          <p class="text-muted mb-2"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($email); ?></p>
          <div class="d-flex gap-3 text-muted small">
            <span><i class="bi bi-calendar3 me-1 text-success"></i> Member Since: <?php echo $created_at; ?></span>
            <span><i class="bi bi-geo-alt me-1 text-info"></i> Platform: VexiumCTF</span>
          </div>
        </div>
        <div class="col-md-auto ms-md-auto">
          <a href="index.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600;">
            <i class="bi bi-controller me-2 text-success"></i> Continue CTF Training
          </a>
        </div>
      </div>
    </div>

    <!-- User Stats Overview -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div class="stat-number"><?php echo $solvedCount; ?></div>
          <div class="stat-label">Labs Solved</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div class="stat-number"><?php echo $bookmarkCount; ?></div>
          <div class="stat-label">Bookmarked Labs</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div class="stat-number"><?php echo $completionPercentage; ?>%</div>
          <div class="stat-label">Completion Rate</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div class="stat-number"><?php echo $totalLabsAvailable; ?></div>
          <div class="stat-label">Total Platform Labs</div>
        </div>
      </div>
    </div>

    <!-- Tabs: Solved & Bookmarked Labs -->
    <div class="profile-card">
      <ul class="nav nav-tabs border-bottom border-secondary border-opacity-25 mb-4" id="profileTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="solved-tab" data-bs-toggle="tab" data-bs-target="#solved-pane" type="button" role="tab" aria-controls="solved-pane" aria-selected="true">
            <i class="bi bi-check-circle-fill text-success me-2"></i>Solved Labs (<?php echo $solvedCount; ?>)
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="bookmarks-tab" data-bs-toggle="tab" data-bs-target="#bookmarks-pane" type="button" role="tab" aria-controls="bookmarks-pane" aria-selected="false">
            <i class="bi bi-star-fill text-warning me-2"></i>Bookmarked Labs (<?php echo $bookmarkCount; ?>)
          </button>
        </li>
      </ul>

      <div class="tab-content" id="profileTabContent">
        <!-- Solved Labs Tab Pane -->
        <div class="tab-pane fade show active" id="solved-pane" role="tabpanel" aria-labelledby="solved-tab">
          <?php if (empty($solvedLabs)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-journal-x display-4 d-block mb-3 opacity-50"></i>
              <h5>No Solved Labs Yet</h5>
              <p class="small">Head over to the homepage and start conquering CTF challenges!</p>
              <a href="index.php" class="btn btn-sm btn-success px-4 py-2 mt-2" style="border-radius: 10px;">Explore Labs</a>
            </div>
          <?php else: ?>
            <div class="table-responsive table-dark-custom">
              <table class="table table-dark table-hover mb-0">
                <thead>
                  <tr>
                    <th>Lab Link</th>
                    <th>Status</th>
                    <th>Date Solved</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($solvedLabs as $lab): ?>
                    <tr>
                      <td class="fw-bold" style="font-family: var(--font-mono); color: #34d399;">
                        <i class="bi bi-terminal me-2"></i><?php echo htmlspecialchars($lab['lab_id']); ?>
                      </td>
                      <td>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success px-2 py-1">
                          <i class="bi bi-check-circle-fill me-1"></i> Solved
                        </span>
                      </td>
                      <td class="text-muted small">
                        <?php echo date('M d, Y - H:i', strtotime($lab['solved_at'])); ?>
                      </td>
                      <td class="text-end">
                        <a href="<?php echo htmlspecialchars($lab['lab_id']); ?>" class="btn btn-sm btn-outline-success px-3" style="border-radius: 8px;">
                          Open Lab <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Bookmarked Labs Tab Pane -->
        <div class="tab-pane fade" id="bookmarks-pane" role="tabpanel" aria-labelledby="bookmarks-tab">
          <?php if (empty($bookmarkedLabs)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-star display-4 d-block mb-3 opacity-50"></i>
              <h5>No Bookmarked Labs</h5>
              <p class="small">Click the star icon on any lab card to save it for quick access later.</p>
              <a href="index.php" class="btn btn-sm btn-warning text-dark px-4 py-2 mt-2 fw-semibold" style="border-radius: 10px;">Browse Labs</a>
            </div>
          <?php else: ?>
            <div class="table-responsive table-dark-custom">
              <table class="table table-dark table-hover mb-0">
                <thead>
                  <tr>
                    <th>Lab Link</th>
                    <th>Date Starred</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookmarkedLabs as $lab): ?>
                    <tr>
                      <td class="fw-bold" style="font-family: var(--font-mono); color: #fbbf24;">
                        <i class="bi bi-star-fill me-2 text-warning"></i><?php echo htmlspecialchars($lab['lab_id']); ?>
                      </td>
                      <td class="text-muted small">
                        <?php echo date('M d, Y - H:i', strtotime($lab['created_at'])); ?>
                      </td>
                      <td class="text-end">
                        <a href="<?php echo htmlspecialchars($lab['lab_id']); ?>" class="btn btn-sm btn-outline-warning px-3 text-warning" style="border-radius: 8px;">
                          Launch Lab <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
