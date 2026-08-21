<?php
// ctf.php - KrazePlanet Annual Flagship CTF Championship
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

$current_page = 'ctf.php';
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

$jsonFile = __DIR__ . '/navbar/ctf_data.json';
$ctfCategories = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

$totalChallenges = 0;
$totalPointsPool = 0;
foreach ($ctfCategories as $cat) {
    foreach ($cat['challenges'] as $c) {
        $totalChallenges++;
        $totalPointsPool += $c['points'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annual CTF Championship 2026 — KrazePlanet</title>
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

    .ctf-hero-card {
      background: radial-gradient(circle at 50% 0%, rgba(56, 189, 248, 0.18) 0%, rgba(11, 19, 36, 0.96) 80%);
      border: 1px solid rgba(56, 189, 248, 0.35);
      border-radius: 20px;
      padding: 3rem 2.2rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
    }

    .ctf-hero-badge {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(244, 63, 94, 0.3));
      border: 1px solid rgba(239, 68, 68, 0.5);
      color: #fca5a5;
      font-size: 12px;
      font-weight: 800;
      padding: 5px 12px;
      border-radius: 8px;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      display: inline-flex;
      align-items: center;
    }

    .ctf-stat-card {
      background: rgba(15, 26, 48, 0.85);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 1.1rem 1.4rem;
      text-align: center;
      transition: all 0.2s ease;
    }
    .ctf-stat-card:hover {
      border-color: rgba(56, 189, 248, 0.4);
      transform: translateY(-2px);
    }

    .ctf-stat-number {
      font-size: 1.85rem;
      font-weight: 800;
      font-family: 'Outfit', sans-serif;
      line-height: 1.1;
    }

    .ctf-stat-label {
      font-size: 11.5px;
      color: #94a3b8;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.05em;
      margin-top: 4px;
    }

    /* Category Pill Selector Bar */
    .ctf-cat-nav {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      padding-bottom: 12px;
      margin-bottom: 2rem;
      scrollbar-width: thin;
      scrollbar-color: rgba(56, 189, 248, 0.3) rgba(11, 19, 36, 0.5);
    }

    .ctf-cat-pill {
      background: rgba(11, 19, 36, 0.7);
      border: 1px solid var(--border-color);
      color: #94a3b8;
      padding: 7px 16px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 7px;
    }
    .ctf-cat-pill:hover {
      color: #ffffff;
      border-color: rgba(56, 189, 248, 0.4);
      background: rgba(56, 189, 248, 0.08);
    }
    .ctf-cat-pill.active {
      background: #0284c7;
      color: #ffffff;
      border-color: #38bdf8;
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
    }

    /* Category Section & Title Header (Matching Screenshot 2) */
    .ctf-category-section {
      margin-bottom: 4rem;
      scroll-margin-top: 90px;
    }

    .ctf-category-header {
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    }

    .ctf-category-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding-left: 14px;
      border-left: 4px solid #38bdf8;
      letter-spacing: -0.2px;
    }

    /* CTF Challenge Cards (3 Per Line on Desktop) */
    .ctf-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      height: 100%;
      position: relative;
    }
    .ctf-card:hover {
      transform: translateY(-3px);
      border-color: rgba(56, 189, 248, 0.45);
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.55);
    }

    .ctf-diff-badge {
      font-size: 11px;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .ctf-diff-easy { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
    .ctf-diff-medium { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
    .ctf-diff-hard { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }
    .ctf-diff-insane { background: rgba(168, 85, 247, 0.18); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.35); }

    .ctf-port-box {
      background: rgba(7, 11, 20, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 9px;
      padding: 8px 12px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 12px;
      color: #38bdf8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-top: auto;
      margin-bottom: 14px;
    }

    .btn-copy-port {
      background: rgba(56, 189, 248, 0.12);
      border: none;
      color: #38bdf8;
      padding: 3px 8px;
      border-radius: 5px;
      font-size: 11.5px;
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-copy-port:hover {
      background: rgba(56, 189, 248, 0.25);
      color: #ffffff;
    }

    .btn-submit-flag {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      border: none;
      color: #ffffff;
      font-weight: 700;
      font-size: 13px;
      padding: 10px 14px;
      border-radius: 10px;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      width: 100%;
      text-decoration: none;
    }
    .btn-submit-flag:hover {
      background: linear-gradient(135deg, #0369a1, #0284c7);
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
      color: #ffffff;
    }

    /* Search Bar */
    .ctf-search-input {
      background: rgba(11, 19, 36, 0.8) !important;
      border: 1px solid var(--border-color) !important;
      color: #ffffff !important;
      border-radius: 12px !important;
      padding: 11px 18px !important;
      font-size: 14px !important;
    }
    .ctf-search-input:focus {
      border-color: var(--accent-cyan) !important;
      box-shadow: 0 0 14px rgba(56, 189, 248, 0.25) !important;
    }

    /* Modal Styling */
    .ctf-modal-content {
      background: #0b1324;
      border: 1px solid rgba(56, 189, 248, 0.3);
      border-radius: 18px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.8);
      color: #cbd5e1;
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<main class="container py-5 flex-grow-1">

  <!-- 1. ANNUAL CTF CHAMPIONSHIP HERO BANNER -->
  <div class="ctf-hero-card mb-5">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
          <span class="ctf-hero-badge">
            <i class="bi bi-trophy-fill me-1 text-warning"></i> Annual Flagship Event 2026
          </span>
          <span class="badge bg-purple bg-opacity-25 text-light border border-secondary px-3 py-1 fw-semibold" style="font-size: 12px;">
            <i class="bi bi-hdd-network-fill text-info me-1"></i> Hosted on Dedicated VPS
          </span>
        </div>

        <h1 class="display-6 fw-bold text-white mb-2" style="font-family: 'Outfit', sans-serif;">
          KrazePlanet Annual CTF Championship
        </h1>
        <p class="text-secondary mb-4" style="max-width: 700px; font-size: 15px; line-height: 1.6;">
          Our premier once-a-year Capture The Flag tournament. <strong><?= $totalChallenges ?> custom CTF challenges</strong> organized across 17 distinct cybersecurity domains on dedicated ports. Exploit the targets, capture flags, and win prestigious bounties, trophies, and certifications.
        </p>

        <!-- Rewards & Host Spec Info -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <div class="d-inline-flex align-items-center gap-2 py-1.5 px-3 rounded-3" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
            <i class="bi bi-award-fill text-success fs-5"></i>
            <div class="small"><strong class="text-white">Champion Rewards:</strong> Cash Bounties + Hall of Fame + Rewards</div>
          </div>
          <div class="d-inline-flex align-items-center gap-2 py-1.5 px-3 rounded-3" style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.25);">
            <i class="bi bi-server text-info fs-5"></i>
            <div class="small font-monospace"><strong class="text-white">Target VPS:</strong> 192.168.1.1:PORT</div>
          </div>
        </div>
      </div>

      <!-- Quick Metrics Counters -->
      <div class="col-lg-4">
        <div class="row g-3">
          <div class="col-6">
            <div class="ctf-stat-card">
              <div class="ctf-stat-number text-info"><?= count($ctfCategories) ?></div>
              <div class="ctf-stat-label">Categories</div>
            </div>
          </div>
          <div class="col-6">
            <div class="ctf-stat-card">
              <div class="ctf-stat-number text-success"><?= $totalChallenges ?></div>
              <div class="ctf-stat-label">Total CTFs</div>
            </div>
          </div>
          <div class="col-6">
            <div class="ctf-stat-card">
              <div class="ctf-stat-number text-warning"><?= number_format($totalPointsPool) ?></div>
              <div class="ctf-stat-label">Points Pool</div>
            </div>
          </div>
          <div class="col-6">
            <div class="ctf-stat-card">
              <div class="ctf-stat-number text-danger">65,535</div>
              <div class="ctf-stat-label">VPS Ports Cap</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. SEARCH & CATEGORY FILTER BAR -->
  <div class="row align-items-center justify-content-between g-3 mb-4">
    <div class="col-md-5">
      <div class="position-relative">
        <i class="bi bi-search position-absolute text-muted" style="top: 14px; left: 16px;"></i>
        <input type="text" id="ctfSearchInput" class="form-control ctf-search-input ps-5" placeholder="Search CTF challenges, categories, or port (e.g. 10001)..." onkeyup="filterCtfCards()">
      </div>
    </div>
    <div class="col-md-auto text-md-end">
      <a href="leaderboard.php" class="btn btn-outline-info px-3 py-2 fw-semibold" style="border-radius: 10px; font-size: 13.5px;">
        <i class="bi bi-trophy-fill me-1 text-warning"></i> View Championship Leaderboard
      </a>
    </div>
  </div>

  <!-- Category Filter Pills (Jump / Filter to Section) -->
  <div class="ctf-cat-nav" id="ctfCategoryBar">
    <button type="button" class="ctf-cat-pill active" onclick="selectCategory('ALL', this)">
      <i class="bi bi-grid-fill"></i> All Categories (<?= $totalChallenges ?>)
    </button>
    <?php foreach ($ctfCategories as $cat): ?>
      <button type="button" class="ctf-cat-pill" onclick="selectCategory('<?= htmlspecialchars($cat['category']) ?>', this)">
        <i class="bi <?= htmlspecialchars($cat['icon']) ?>" style="color: <?= htmlspecialchars($cat['color']) ?>;"></i>
        <?= htmlspecialchars($cat['category']) ?> (<?= count($cat['challenges']) ?>)
      </button>
    <?php endforeach; ?>
  </div>

  <!-- 3. CTF CATEGORIES SECTIONS (3 Challenges Per Line, Generous Section Spacing) -->
  <div id="ctfMainContainer">
    <?php foreach ($ctfCategories as $cat): ?>
      <?php 
        $catPoints = array_sum(array_column($cat['challenges'], 'points'));
        $catId = 'cat-' . preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($cat['category']));
      ?>
      <section class="ctf-category-section" id="<?= $catId ?>" data-category="<?= htmlspecialchars($cat['category']) ?>">
        
        <!-- Category Title Header (Styled matching Screenshot 2) -->
        <div class="ctf-category-header mb-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="ctf-category-title mb-0" style="border-left-color: <?= htmlspecialchars($cat['color']) ?>;">
              <i class="bi <?= htmlspecialchars($cat['icon']) ?>" style="color: <?= htmlspecialchars($cat['color']) ?>;"></i>
              <span><?= htmlspecialchars($cat['category']) ?></span>
            </h3>
            <span class="badge rounded-pill px-3 py-1.5" style="background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; border: 1px solid <?= $cat['color'] ?>40; font-size: 12px;">
              <?= count($cat['challenges']) ?> Challenges • <?= number_format($catPoints) ?> pts
            </span>
          </div>
        </div>

        <!-- 3 CTF Cards in a line (col-lg-4 col-md-6) -->
        <div class="row g-4">
          <?php foreach ($cat['challenges'] as $c): ?>
            <div class="col-lg-4 col-md-6 ctf-card-item" data-category="<?= htmlspecialchars($c['category']) ?>" data-name="<?= strtolower(htmlspecialchars($c['name'])) ?>" data-port="<?= $c['port'] ?>">
              <div class="ctf-card">
                
                <!-- Card Header -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge" style="background: <?= $cat['color'] ?>1a; color: <?= $cat['color'] ?>; border: 1px solid <?= $cat['color'] ?>40; font-size: 11px;">
                    <i class="bi <?= $cat['icon'] ?> me-1"></i> <?= htmlspecialchars($c['category']) ?>
                  </span>
                  <span class="ctf-diff-badge ctf-diff-<?= strtolower($c['difficulty']) ?>">
                    <?= $c['difficulty'] ?> • <?= $c['points'] ?> pts
                  </span>
                </div>

                <!-- Challenge Title -->
                <h5 class="fw-bold text-white mb-2" style="font-family: 'Outfit', sans-serif; font-size: 1.15rem;">
                  <?= htmlspecialchars($c['name']) ?>
                </h5>

                <div class="text-secondary small mb-3" style="font-size: 12px;">
                  <span class="me-3"><i class="bi bi-flag me-1 text-danger"></i> Format: <code>KPCTF{...}</code></span>
                  <span><i class="bi bi-check2-circle me-1 text-success"></i> <?= $c['solves'] ?> Solves</span>
                </div>

                <!-- Connection Box -->
                <div class="ctf-port-box">
                  <div class="d-flex align-items-center gap-1 text-truncate">
                    <span class="text-muted"><?= $c['protocol'] === 'nc' ? 'nc' : 'curl' ?></span>
                    <span class="text-white fw-bold">192.168.1.1:<?= $c['port'] ?></span>
                  </div>
                  <button type="button" class="btn-copy-port" onclick="copyConnString('<?= $c['protocol'] === 'nc' ? 'nc 192.168.1.1 ' . $c['port'] : 'http://192.168.1.1:' . $c['port'] ?>', this)" title="Copy connection string">
                    <i class="bi bi-clipboard"></i>
                  </button>
                </div>

                <!-- Submit Flag Button -->
                <button type="button" class="btn-submit-flag" onclick="openFlagModal('<?= htmlspecialchars(addslashes($c['name'])) ?>', '<?= htmlspecialchars($c['category']) ?>', <?= $c['points'] ?>, <?= $c['port'] ?>)">
                  <i class="bi bi-flag-fill"></i> Submit Flag
                </button>

              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </section>
    <?php endforeach; ?>
  </div>

  <!-- Empty Search Placeholder -->
  <div id="noCtfFoundMsg" class="text-center py-5" style="display: none;">
    <i class="bi bi-search display-4 text-secondary mb-3 d-block"></i>
    <h5 class="text-white">No CTF challenges found</h5>
    <p class="text-secondary small">Try adjusting your search keywords or select "All Categories".</p>
  </div>

</main>

<!-- FLAG SUBMISSION MODAL -->
<div class="modal fade" id="flagSubmitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ctf-modal-content">
      <div class="modal-header border-secondary border-opacity-25 pb-2">
        <div>
          <h5 class="modal-title fw-bold text-white" id="flagModalTitle">Submit Flag</h5>
          <div class="text-secondary small" id="flagModalSubtitle">Challenge Details</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3">
        <div id="flagAlertBox" style="display: none;" class="alert py-2 px-3 small mb-3 border-0"></div>

        <form id="flagSubmitForm" onsubmit="handleFlagSubmit(event)">
          <input type="hidden" id="flagChallengeName">
          <input type="hidden" id="flagChallengePoints">

          <div class="mb-3">
            <label class="form-label text-secondary small fw-bold">Target Connection</label>
            <div class="p-2 rounded-3 font-monospace small" style="background: rgba(7,11,20,0.8); border: 1px solid var(--border-color); color: #38bdf8;" id="flagModalConn">
              nc 192.168.1.1:10001
            </div>
          </div>

          <div class="mb-4">
            <label for="flagInputText" class="form-label text-secondary small fw-bold">Flag Value</label>
            <input type="text" id="flagInputText" class="form-control custom-profile-input font-monospace" placeholder="KPCTF{s0m3_fl4g_h3r3}" required autofocus>
            <div class="text-secondary small mt-1" style="font-size: 11px;">Example: <code>KPCTF{conquered_challenge_flag}</code></div>
          </div>

          <button type="submit" id="btnFlagSubmit" class="btn btn-primary w-100 py-2 fw-bold" style="box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4); border-radius: 10px;">
            <i class="bi bi-check-circle-fill me-1"></i> Verify & Claim Points
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>

<script>
let currentSelectedCat = 'ALL';

function selectCategory(catName, btnEl) {
  currentSelectedCat = catName;
  document.querySelectorAll('.ctf-cat-pill').forEach(p => p.classList.remove('active'));
  if (btnEl) btnEl.classList.add('active');

  const sections = document.querySelectorAll('.ctf-category-section');
  if (catName === 'ALL') {
    sections.forEach(sec => sec.style.display = 'block');
  } else {
    sections.forEach(sec => {
      const isMatch = (sec.getAttribute('data-category') === catName);
      sec.style.display = isMatch ? 'block' : 'none';
      if (isMatch) {
        sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  filterCtfCards();
}

function filterCtfCards() {
  const query = document.getElementById('ctfSearchInput').value.trim().toLowerCase();
  const sections = document.querySelectorAll('.ctf-category-section');
  let totalVisibleCards = 0;

  sections.forEach(sec => {
    const secCat = sec.getAttribute('data-category');
    const cards = sec.querySelectorAll('.ctf-card-item');
    let visibleInSec = 0;

    const matchesCatFilter = (currentSelectedCat === 'ALL' || secCat === currentSelectedCat);

    if (!matchesCatFilter) {
      sec.style.display = 'none';
      return;
    }

    cards.forEach(card => {
      const name = card.getAttribute('data-name');
      const port = card.getAttribute('data-port');

      const matchesQuery = (!query || name.includes(query) || secCat.toLowerCase().includes(query) || port.includes(query));

      if (matchesQuery) {
        card.style.display = 'block';
        visibleInSec++;
        totalVisibleCards++;
      } else {
        card.style.display = 'none';
      }
    });

    sec.style.display = (visibleInSec > 0) ? 'block' : 'none';
  });

  document.getElementById('noCtfFoundMsg').style.display = (totalVisibleCards === 0) ? 'block' : 'none';
}

function copyConnString(text, btnEl) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btnEl.innerHTML;
    btnEl.innerHTML = '<i class="bi bi-check2 text-success"></i>';
    setTimeout(() => { btnEl.innerHTML = orig; }, 1500);
  });
}

function openFlagModal(challengeName, category, points, port) {
  document.getElementById('flagModalTitle').textContent = challengeName;
  document.getElementById('flagModalSubtitle').textContent = category + ' • ' + points + ' Points';
  document.getElementById('flagModalConn').textContent = '192.168.1.1:' + port;
  document.getElementById('flagChallengeName').value = challengeName;
  document.getElementById('flagChallengePoints').value = points;
  document.getElementById('flagInputText').value = '';
  document.getElementById('flagAlertBox').style.display = 'none';

  const modalEl = document.getElementById('flagSubmitModal');
  const m = bootstrap.Modal.getOrCreateInstance(modalEl);
  m.show();
}

function handleFlagSubmit(e) {
  e.preventDefault();
  const flag = document.getElementById('flagInputText').value.trim();
  const points = document.getElementById('flagChallengePoints').value;
  const alertBox = document.getElementById('flagAlertBox');
  const btn = document.getElementById('btnFlagSubmit');

  if (!flag.startsWith('KPCTF{') || !flag.endsWith('}')) {
    alertBox.className = 'alert alert-warning py-2 px-3 small mb-3 border-0';
    alertBox.textContent = 'Invalid format. Flags must match the format KPCTF{...}';
    alertBox.style.display = 'block';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';

  setTimeout(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Verify & Claim Points';
    alertBox.className = 'alert alert-success py-2 px-3 small mb-3 border-0';
    alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> <strong>Flag Accepted!</strong> +' + points + ' points will be credited to your leaderboard ranking during the live tournament.';
    alertBox.style.display = 'block';
  }, 600);
}
</script>

</body>
</html>
