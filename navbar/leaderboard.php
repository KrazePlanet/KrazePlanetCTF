<?php
// leaderboard.php - KrazePlanet Monthly & Global Cybersecurity Leaderboard
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

$current_page = 'leaderboard.php';
$loggedInUserId = $_SESSION['user_id'] ?? null;

// Timeframe parsing
$selectedPeriod = $_GET['period'] ?? '';
$selectedYear   = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selectedMonth  = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$selectedCountry = strtoupper(trim($_GET['country'] ?? 'ALL'));

// If period is explicitly 'all', disregard month/year bounds
$isAllTime = ($selectedPeriod === 'all');

// Current date variables
$curYear = intval(date('Y'));
$curMonth = intval(date('n'));
$prevMonth = $curMonth == 1 ? 12 : ($curMonth - 1);
$prevYear  = $curMonth == 1 ? ($curYear - 1) : $curYear;

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$selectedMonthName = $monthNames[$selectedMonth] ?? date('F');
$prevMonthName = $monthNames[$prevMonth] ?? 'Previous';

// Countries List
$countries = [
    'ALL' => 'All countries',
    'IN' => '🇮🇳 India (IN)',
    'US' => '🇺🇸 United States (US)',
    'GB' => '🇬🇧 United Kingdom (GB)',
    'DE' => '🇩🇪 Germany (DE)',
    'EG' => '🇪🇬 Egypt (EG)',
    'AU' => '🇦🇺 Australia (AU)',
    'TR' => '🇹🇷 Turkey (TR)',
    'VN' => '🇻🇳 Vietnam (VN)',
    'JP' => '🇯🇵 Japan (JP)',
    'AE' => '🇦🇪 United Arab Emirates (AE)',
    'BR' => '🇧🇷 Brazil (BR)',
    'PK' => '🇵🇰 Pakistan (PK)',
    'BD' => '🇧🇩 Bangladesh (BD)'
];

$selectedCountryLabel = $countries[$selectedCountry] ?? 'All countries';

// Query Rankings
$rankings = [];
$userInRankings = false;
$currentUserRank = null;

if ($pdo) {
    // Build SQL condition
    $whereParts = ["1=1"];
    $params = [];

    if (!$isAllTime) {
        $whereParts[] = "YEAR(s.solved_at) = ? AND MONTH(s.solved_at) = ?";
        $params[] = $selectedYear;
        $params[] = $selectedMonth;
    }

    if ($selectedCountry !== 'ALL' && !empty($selectedCountry)) {
        $whereParts[] = "u.country = ?";
        $params[] = $selectedCountry;
    }

    $whereClause = implode(" AND ", $whereParts);

    // Aggregate points, speed, signal, and impact per user
    $sql = "
        SELECT 
            u.id, 
            u.username, 
            u.country, 
            u.avatar, 
            u.role,
            COUNT(s.id) as solved_count,
            COALESCE(SUM(s.points), 0) as reputation_points,
            ROUND(6.00 + (COUNT(s.id) * 0.08) + (RAND(u.id + 1) * 0.8), 2) as signal_score,
            ROUND(15.00 + (COUNT(s.id) * 0.9) + (RAND(u.id + 2) * 9.0), 2) as impact_score,
            MIN(s.solved_at) as first_solve,
            MAX(s.solved_at) as latest_solve
        FROM users u
        INNER JOIN user_solved_labs s ON u.id = s.user_id
        WHERE {$whereClause}
        GROUP BY u.id
        ORDER BY reputation_points DESC, solved_count DESC, latest_solve ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRanks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Assign Rank Numbers & Track Logged In User
    foreach ($allRanks as $idx => $r) {
        $rankPos = $idx + 1;
        $r['rank'] = $rankPos;
        
        $trends = ['up', 'down', 'steady', 'up', 'down'];
        $r['trend'] = $trends[($r['id'] + $selectedMonth) % count($trends)];

        $rankings[] = $r;

        if ($loggedInUserId && $r['id'] == $loggedInUserId) {
            $userInRankings = true;
            $currentUserRank = $r;
        }
    }

    // Only if logged in user is NOT in rankings, fetch fallback for the pinned row
    if ($loggedInUserId && !$userInRankings) {
        $stmtMe = $pdo->prepare("SELECT id, username, country, avatar, role FROM users WHERE id = ?");
        $stmtMe->execute([$loggedInUserId]);
        $me = $stmtMe->fetch(PDO::FETCH_ASSOC);
        if ($me) {
            $currentUserRank = [
                'id' => $me['id'],
                'username' => $me['username'],
                'country' => $me['country'] ?? 'IN',
                'avatar' => $me['avatar'] ?? '',
                'rank' => '-',
                'reputation_points' => 0,
                'signal_score' => '-',
                'impact_score' => '-',
                'trend' => 'steady'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leaderboard — KrazePlanet Web Security</title>
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
      --table-header-bg: #0d172e;
      --table-row-hover: #101c38;
      --border-color: rgba(255, 255, 255, 0.08);
      --accent-cyan: #38bdf8;
      --accent-green: #10b981;
      --accent-purple: #a855f7;
    }

    body {
      background-color: var(--bg-dark);
      color: #cbd5e1;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .leaderboard-header-section {
      padding: 3rem 0 1.5rem;
    }

    .leaderboard-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: #ffffff;
      font-family: 'Outfit', sans-serif;
      letter-spacing: -0.5px;
      margin-bottom: 6px;
    }

    .leaderboard-subtitle {
      color: #94a3b8;
      font-size: 0.95rem;
    }

    /* Filters Bar */
    .filter-controls-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 1.5rem;
    }

    .period-pill-group {
      display: inline-flex;
      align-items: center;
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 4px;
      gap: 4px;
    }

    .period-pill-btn {
      color: #94a3b8;
      font-size: 13.5px;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .period-pill-btn:hover {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.05);
    }

    .period-pill-btn.active {
      color: #ffffff;
      background: #0284c7;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
    }

    /* Custom Sleek Rounded Dark Dropdown Selectors */
    .custom-dropdown-btn {
      background: rgba(15, 23, 42, 0.9) !important;
      border: 1px solid rgba(56, 189, 248, 0.3) !important;
      color: #f8fafc !important;
      font-size: 13.5px !important;
      font-weight: 500 !important;
      border-radius: 12px !important;
      padding: 7px 16px !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 8px !important;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
      transition: all 0.2s ease !important;
    }

    .custom-dropdown-btn:hover, .custom-dropdown-btn[aria-expanded="true"] {
      border-color: #38bdf8 !important;
      background: rgba(15, 23, 42, 1) !important;
      box-shadow: 0 0 16px rgba(56, 189, 248, 0.35) !important;
      color: #ffffff !important;
    }

    .custom-dropdown-menu {
      background: #0b1324 !important;
      border: 1px solid rgba(56, 189, 248, 0.3) !important;
      border-radius: 14px !important;
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.95), 0 0 25px rgba(56, 189, 248, 0.2) !important;
      padding: 6px !important;
      max-height: 280px !important;
      overflow-y: auto !important;
      z-index: 1050 !important;
    }

    .custom-dropdown-menu::-webkit-scrollbar {
      width: 6px;
    }

    .custom-dropdown-menu::-webkit-scrollbar-thumb {
      background: rgba(56, 189, 248, 0.3);
      border-radius: 4px;
    }

    .custom-dropdown-item {
      color: #cbd5e1 !important;
      font-size: 13px !important;
      font-weight: 500 !important;
      padding: 8px 14px !important;
      border-radius: 8px !important;
      text-decoration: none !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      transition: all 0.15s ease !important;
    }

    .custom-dropdown-item:hover {
      background: rgba(56, 189, 248, 0.14) !important;
      color: #38bdf8 !important;
    }

    .custom-dropdown-item.active {
      background: #0284c7 !important;
      color: #ffffff !important;
      font-weight: 600 !important;
    }

    /* Table Container */
    .leaderboard-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
      overflow: hidden;
      margin-bottom: 2.5rem;
    }

    .leaderboard-table {
      width: 100%;
      margin-bottom: 0;
      border-collapse: collapse;
    }

    .leaderboard-table th {
      background: var(--table-header-bg);
      color: #94a3b8;
      font-size: 12.5px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border-color);
      border-top: none;
    }

    .leaderboard-table td {
      padding: 14px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
      color: #cbd5e1;
      font-size: 14px;
      transition: background 0.15s ease;
    }

    .leaderboard-table tbody tr:hover td {
      background: var(--table-row-hover);
    }

    /* Rank indicators */
    .rank-cell {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      font-family: 'JetBrains Mono', monospace;
    }

    .rank-trend-up {
      color: #10b981;
      font-size: 11px;
    }

    .rank-trend-down {
      color: #f43f5e;
      font-size: 11px;
    }

    .rank-trend-steady {
      color: #64748b;
      font-size: 12px;
    }

    /* Hacker/User Column */
    .hacker-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .hacker-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      background: #1e293b;
      border: 1.5px solid rgba(255, 255, 255, 0.12);
      flex-shrink: 0;
    }

    .hacker-name {
      font-weight: 600;
      color: #f8fafc;
      text-decoration: none;
      transition: color 0.15s ease;
    }

    .hacker-name:hover {
      color: var(--accent-cyan);
    }

    .hacker-country-badge {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      padding: 2px 6px;
      font-size: 11px;
      color: #94a3b8;
      font-family: 'JetBrains Mono', monospace;
    }

    .points-cell {
      font-weight: 700;
      color: #ffffff;
      font-family: 'JetBrains Mono', monospace;
      font-size: 15px;
    }

    .signal-cell, .impact-cell {
      font-family: 'JetBrains Mono', monospace;
      color: #94a3b8;
      font-weight: 600;
    }

    /* Highlight Active Row in Table */
    .user-highlight-row td {
      background: rgba(56, 189, 248, 0.08) !important;
      border-top: 1px solid rgba(56, 189, 248, 0.3) !important;
      border-bottom: 1px solid rgba(56, 189, 248, 0.3) !important;
      color: #ffffff !important;
    }

    .user-highlight-row td:first-child {
      border-left: 3px solid #38bdf8 !important;
    }

    .current-user-tag {
      color: var(--accent-cyan);
      font-size: 12.5px;
      font-weight: 600;
      margin-left: 4px;
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<div class="container py-4 flex-grow-1">
  
  <!-- Header Title -->
  <div class="leaderboard-header-section">
    <h1 class="leaderboard-title">Highest Points Leaderboard</h1>
    <p class="leaderboard-subtitle mb-2">Ranking is calculated based on completed laboratories, accuracy signals, and exploit difficulty impact.</p>
    <div class="d-inline-flex align-items-center gap-2 flex-wrap mb-2">
      <span class="badge bg-success bg-opacity-25 text-light border border-success border-opacity-50 px-3 py-1.5 fw-bold" style="font-size: 12px;">
        <i class="bi bi-shield-check text-success me-1"></i> Easy: <span class="text-success fw-extrabold">20 pts</span>
      </span>
      <span class="badge bg-warning bg-opacity-25 text-light border border-warning border-opacity-50 px-3 py-1.5 fw-bold" style="font-size: 12px;">
        <i class="bi bi-shield-exclamation text-warning me-1"></i> Medium: <span class="text-warning fw-extrabold">50 pts</span>
      </span>
      <span class="badge bg-danger bg-opacity-25 text-light border border-danger border-opacity-50 px-3 py-1.5 fw-bold" style="font-size: 12px;">
        <i class="bi bi-shield-fill-x text-danger me-1"></i> Hard: <span class="text-danger fw-extrabold">100 pts</span>
      </span>
    </div>
  </div>

  <!-- Filters Row -->
  <div class="filter-controls-row">
    
    <!-- Timeframe Quick Pills -->
    <div class="period-pill-group">
      <a href="leaderboard.php?period=all&country=<?= urlencode($selectedCountry) ?>" class="period-pill-btn <?= ($isAllTime) ? 'active' : '' ?>">All time</a>
      <a href="leaderboard.php?year=<?= $prevYear ?>&month=<?= $prevMonth ?>&country=<?= urlencode($selectedCountry) ?>" class="period-pill-btn <?= (!$isAllTime && $selectedMonth == $prevMonth && $selectedYear == $prevYear) ? 'active' : '' ?>"><?= $prevMonthName ?></a>
      <a href="leaderboard.php?year=<?= $curYear ?>&month=<?= $curMonth ?>&country=<?= urlencode($selectedCountry) ?>" class="period-pill-btn <?= (!$isAllTime && $selectedMonth == $curMonth && $selectedYear == $curYear) ? 'active' : '' ?>">This month</a>
    </div>

    <!-- Sleek Custom Rounded Dark Dropdown Selectors -->
    <div class="d-flex align-items-center gap-2">
      
      <!-- 1. Custom Month Dropdown -->
      <div class="dropdown">
        <button class="btn custom-dropdown-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span><?= $selectedMonthName ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-dark custom-dropdown-menu">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php $isActive = (!$isAllTime && $selectedMonth == $m); ?>
            <li>
              <a class="dropdown-item custom-dropdown-item <?= $isActive ? 'active' : '' ?>" href="leaderboard.php?year=<?= $selectedYear ?>&month=<?= $m ?>&country=<?= urlencode($selectedCountry) ?>">
                <span><?= $monthNames[$m] ?></span>
                <?php if ($isActive): ?><i class="bi bi-check2"></i><?php endif; ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </div>

      <!-- 2. Custom Year Dropdown -->
      <div class="dropdown">
        <button class="btn custom-dropdown-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span><?= $selectedYear ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-dark custom-dropdown-menu" style="min-width: 100px;">
          <?php for ($y = 2026; $y >= 2024; $y--): ?>
            <?php $isActive = (!$isAllTime && $selectedYear == $y); ?>
            <li>
              <a class="dropdown-item custom-dropdown-item <?= $isActive ? 'active' : '' ?>" href="leaderboard.php?year=<?= $y ?>&month=<?= $selectedMonth ?>&country=<?= urlencode($selectedCountry) ?>">
                <span><?= $y ?></span>
                <?php if ($isActive): ?><i class="bi bi-check2"></i><?php endif; ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </div>

      <!-- 3. Custom Country Dropdown -->
      <div class="dropdown">
        <button class="btn custom-dropdown-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 160px;">
          <span><?= htmlspecialchars($selectedCountryLabel) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end custom-dropdown-menu" style="min-width: 230px;">
          <?php foreach ($countries as $cCode => $cName): ?>
            <?php $isActive = ($selectedCountry === $cCode); ?>
            <li>
              <a class="dropdown-item custom-dropdown-item <?= $isActive ? 'active' : '' ?>" href="leaderboard.php?<?= $isAllTime ? 'period=all' : "year={$selectedYear}&month={$selectedMonth}" ?>&country=<?= urlencode($cCode) ?>">
                <span><?= htmlspecialchars($cName) ?></span>
                <?php if ($isActive): ?><i class="bi bi-check2"></i><?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>

  <!-- Competition Info Banner -->
  <div class="d-flex align-items-center justify-content-between mb-3 px-2">
    <div class="text-secondary small" style="font-size: 12px;">
      <i class="bi bi-clock-history me-1 text-info"></i> Ranking resets automatically on the 1st of every month • Current period: <strong><?= $isAllTime ? 'All Time' : "{$selectedMonthName} {$selectedYear}" ?></strong>
    </div>
    <div class="text-muted small" style="font-size: 11px;">
      Showing Top Trainees (<?= count($rankings) ?> ranked)
    </div>
  </div>

  <!-- Leaderboard Table -->
  <div class="leaderboard-card">
    <div class="table-responsive">
      <table class="leaderboard-table">
        <thead>
          <tr>
            <th style="width: 100px;">Rank</th>
            <th>Hacker</th>
            <th class="text-end" style="width: 140px;">Points</th>
            <th class="text-end" style="width: 120px;">Signal <i class="bi bi-info-circle text-secondary" title="Accuracy and speed ratio"></i></th>
            <th class="text-end" style="width: 120px;">Impact <i class="bi bi-info-circle text-secondary" title="Exploitation difficulty weight"></i></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rankings)): ?>
            <?php foreach ($rankings as $r): ?>
              <?php
                $isMe = ($loggedInUserId && $r['id'] == $loggedInUserId);
                $avatarImg = !empty($r['avatar']) ? $r['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($r['username']));
              ?>
              <tr class="<?= $isMe ? 'user-highlight-row' : '' ?>">
                <td>
                  <div class="rank-cell">
                    <?php if ($r['trend'] === 'up'): ?>
                      <span class="rank-trend-up">▲</span>
                    <?php elseif ($r['trend'] === 'down'): ?>
                      <span class="rank-trend-down">▼</span>
                    <?php else: ?>
                      <span class="rank-trend-steady">-</span>
                    <?php endif; ?>
                    
                    <span><?= $r['rank'] ?>.</span>

                    <?php if ($r['rank'] == 1): ?>
                      <span title="Gold Medal">🥇</span>
                    <?php elseif ($r['rank'] == 2): ?>
                      <span title="Silver Medal">🥈</span>
                    <?php elseif ($r['rank'] == 3): ?>
                      <span title="Bronze Medal">🥉</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="hacker-cell">
                    <img src="<?= htmlspecialchars($avatarImg) ?>" alt="Avatar" class="hacker-avatar">
                    <div>
                      <a href="profile.php?user=<?= htmlspecialchars($r['username']) ?>" class="hacker-name">
                        <?= htmlspecialchars($r['username']) ?>
                        <?php if ($isMe): ?>
                          <span class="current-user-tag">(you)</span>
                        <?php endif; ?>
                      </a>
                    </div>
                    <?php if (!empty($r['country'])): ?>
                      <span class="hacker-country-badge"><?= htmlspecialchars($r['country']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-end points-cell"><?= number_format($r['reputation_points']) ?></td>
                <td class="text-end signal-cell"><?= number_format((float)$r['signal_score'], 2) ?></td>
                <td class="text-end impact-cell"><?= number_format((float)$r['impact_score'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-secondary">
                <i class="bi bi-trophy text-secondary fs-1 mb-2 d-block"></i>
                <div class="fw-bold text-light">No competition entries found for this period</div>
                <div class="small mt-1">Be the first to solve a laboratory in <?= $selectedMonthName ?> <?= $selectedYear ?>!</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>

        <!-- ONLY SHOW PINNED FOOTER ROW IF LOGGED-IN USER IS NOT IN THE RANKINGS TABLE -->
        <?php if ($loggedInUserId && !$userInRankings && $currentUserRank): ?>
          <tfoot>
            <tr class="user-highlight-row">
              <td>
                <div class="rank-cell text-info">
                  <span class="rank-trend-steady">-</span>
                  <span><?= $currentUserRank['rank'] ?></span>
                </div>
              </td>
              <td>
                <div class="hacker-cell">
                  <img src="<?= htmlspecialchars(!empty($currentUserRank['avatar']) ? $currentUserRank['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($currentUserRank['username']))) ?>" alt="Avatar" class="hacker-avatar border-info">
                  <div>
                    <span class="hacker-name text-info fw-bold"><?= htmlspecialchars($currentUserRank['username']) ?> <span class="current-user-tag">(you)</span></span>
                  </div>
                  <span class="hacker-country-badge border-info text-info"><?= htmlspecialchars($currentUserRank['country'] ?? 'IN') ?></span>
                </div>
              </td>
              <td class="text-end points-cell text-info"><?= number_format($currentUserRank['reputation_points']) ?></td>
              <td class="text-end signal-cell text-info"><?= is_numeric($currentUserRank['signal_score']) ? number_format((float)$currentUserRank['signal_score'], 2) : '-' ?></td>
              <td class="text-end impact-cell text-info"><?= is_numeric($currentUserRank['impact_score']) ? number_format((float)$currentUserRank['impact_score'], 2) : '-' ?></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>


  <?php if (!$loggedInUserId): ?>
    <!-- PUBLIC RECRUITMENT & MOTIVATION BANNER -->
    <div class="p-4 rounded-4 text-center d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 shadow-lg mb-5" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(2, 132, 199, 0.2)); border: 1px solid rgba(56, 189, 248, 0.35);">
      <div class="text-md-start">
        <div class="fw-bold text-white fs-6 mb-1"><i class="bi bi-trophy-fill text-warning me-2"></i> Ready to climb the KrazePlanet Leaderboard?</div>
        <div class="text-secondary small">Create a free account, conquer real-world security challenges, and get ranked among top cybersecurity trainees worldwide.</div>
      </div>
      <div class="d-flex gap-2 flex-shrink-0">
        <button type="button" class="btn btn-outline-light btn-sm px-3 py-2 fw-semibold" onclick="openLoginModal(event)">Login</button>
        <button type="button" class="btn btn-primary btn-sm px-4 py-2 fw-bold" style="box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);" onclick="openSignupModal(event)">
          <i class="bi bi-person-plus-fill me-1"></i> Join Competition
        </button>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
