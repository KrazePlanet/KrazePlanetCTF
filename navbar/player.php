<?php
// player.php - A student's public page: rank, points, streak, skills by bug
// type, solve activity and recent solves. Opened from the scoreboard or from
// the "Public profile" link on a student's own profile.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
krazeRequireLogin();

$current_page = 'scoreboard.php';
$catalog = krazeLabCatalog();
$username = trim((string)($_GET['user'] ?? ($_SESSION['username'] ?? '')));

header('Cache-Control: no-store, must-revalidate');
$res = $username !== '' ? krazeSsoCall('player', ['username' => $username]) : ['success' => false];
$p = !empty($res['success']) ? $res['player'] : null;
if (!$p) http_response_code(404);

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
$isMe = $p && strcasecmp($p['username'], (string)($_SESSION['username'] ?? '')) === 0;

function playerTimeAgo($when) {
    $t = $when ? strtotime($when) : 0;
    if (!$t) return '';
    $s = max(0, time() - $t);
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . 'm ago';
    if ($s < 86400) return floor($s / 3600) . 'h ago';
    if ($s < 86400 * 30) return floor($s / 86400) . 'd ago';
    return date('j M Y', $t);
}

if ($p) {
    $solved = $p['solved'];
    $diffCount = ['easy' => 0, 'medium' => 0, 'hard' => 0];
    $perDay = [];
    $solvedIds = [];
    foreach ($solved as $s) {
        $d = strtolower((string)$s['difficulty']);
        if (isset($diffCount[$d])) $diffCount[$d]++;
        $day = substr((string)$s['solved_at'], 0, 10);
        $perDay[$day] = ($perDay[$day] ?? 0) + 1;
        $solvedIds[$s['lab_id']] = true;
    }

    // Skills: solved / total for every bug type on this site.
    $skills = [];
    foreach ($catalog['byCategory'] as $cat => $ids) {
        $n = 0;
        foreach ($ids as $id) if (isset($solvedIds[$id])) $n++;
        $skills[] = ['name' => $cat, 'solved' => $n, 'total' => count($ids)];
    }
    usort($skills, fn($a, $b) => [$b['solved'] / max(1, $b['total']), $b['solved']] <=> [$a['solved'] / max(1, $a['total']), $a['solved']]);
    $siteSolved = count(array_intersect_key($solvedIds, $catalog['byId']));
    $siteTotal = count($catalog['byId']);

    // Activity: last 20 weeks, one square per day, Monday at the top.
    $weeks = 20;
    $end = strtotime('today');
    $start = strtotime('-' . ($weeks * 7 - 1) . ' days', $end);
    $start = strtotime('-' . ((int)date('N', $start) - 1) . ' days', $start);
    $activeDays = 0;
    foreach ($perDay as $day => $n) if (strtotime($day) >= $start) $activeDays++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $p ? $e($p['username']) . ' - Player' : 'Player not found' ?> - KrazePlanet Web Security</title>
  <link rel="icon" type="image/png" href="https://krazeplanet.com/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root { --bg-dark: #070b14; --card-bg: #0b1324; --border-color: rgba(255,255,255,.08); --accent: #38bdf8; }
    body { background: var(--bg-dark); color: #cbd5e1; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
    .pl-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; margin-bottom: 20px; box-shadow: 0 20px 50px rgba(0,0,0,.45); }
    .pl-card h2 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
    .pl-card h2 .sub { margin-left: auto; font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; color: #64748b; }
    .back-link { color: #94a3b8; text-decoration: none; font-size: 13px; display: inline-flex; gap: 6px; align-items: center; margin: 28px 0 16px; }
    .back-link:hover { color: var(--accent); }

    .pl-head { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; background: linear-gradient(135deg, rgba(15,23,42,.95), rgba(2,132,199,.14)); border-color: rgba(56,189,248,.28); }
    .pl-avatar { width: 76px; height: 76px; border-radius: 50%; object-fit: cover; background: #1e293b; border: 2px solid rgba(56,189,248,.45); }
    .pl-name { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #fff; line-height: 1.1; }
    .pl-meta { font-size: 13px; color: #94a3b8; display: flex; gap: 12px; flex-wrap: wrap; margin-top: 6px; }
    .pl-badge { font-size: 11px; font-family: 'JetBrains Mono', monospace; border: 1px solid rgba(255,255,255,.12); border-radius: 6px; padding: 1px 6px; color: #cbd5e1; }
    .you-tag { color: var(--accent); font-size: 14px; font-weight: 600; }

    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    .stat { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; }
    .stat-label { font-size: 11.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
    .stat-value { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: #fff; line-height: 1.25; }
    .stat-sub { font-size: 12px; color: #64748b; }
    .split { display: inline-flex; gap: 10px; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; }
    .c-easy { color: #34d399; } .c-medium { color: #fbbf24; } .c-hard { color: #f87171; }

    .skill-row { display: grid; grid-template-columns: minmax(150px, 1.2fr) 3fr 64px; gap: 12px; align-items: center; padding: 7px 0; font-size: 13.5px; }
    .skill-row + .skill-row { border-top: 1px solid rgba(255,255,255,.04); }
    .skill-bar { height: 8px; background: rgba(255,255,255,.07); border-radius: 999px; overflow: hidden; }
    .skill-bar span { display: block; height: 100%; background: linear-gradient(90deg, #0ea5e9, #34d399); border-radius: 999px; }
    .skill-num { text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 12.5px; color: #94a3b8; }
    .skill-row.is-zero { color: #64748b; }
    .skills-more { display: none; }
    .skills-more.open { display: block; }
    .link-btn { background: none; border: 0; color: var(--accent); font-size: 13px; padding: 8px 0 0; }

    .heat { display: grid; grid-auto-flow: column; grid-template-rows: repeat(7, 13px); grid-auto-columns: 13px; justify-content: start; gap: 3px; overflow-x: auto; padding-bottom: 4px; }
    .heat i { width: 13px; height: 13px; border-radius: 3px; background: rgba(255,255,255,.06); display: block; }
    .heat i.l1 { background: rgba(52,211,153,.35); } .heat i.l2 { background: rgba(52,211,153,.6); } .heat i.l3 { background: #34d399; }
    .heat i.future { visibility: hidden; }
    .heat-legend { display: flex; align-items: center; gap: 4px; font-size: 11px; color: #64748b; margin-top: 8px; justify-content: flex-end; }
    .heat-legend i { width: 11px; height: 11px; border-radius: 3px; display: inline-block; }

    .solve-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; font-size: 13.5px; }
    .solve-item + .solve-item { border-top: 1px solid rgba(255,255,255,.04); }
    .solve-title { color: #f1f5f9; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .solve-cat { font-size: 12px; color: #64748b; }
    .diff-chip { font-size: 11px; font-weight: 700; border-radius: 6px; padding: 2px 8px; border: 1px solid currentColor; white-space: nowrap; }
    .solve-pts { font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff; white-space: nowrap; }
    .solve-when { font-size: 12px; color: #64748b; white-space: nowrap; width: 72px; text-align: right; }

    @media (max-width: 768px) {
      .stat-grid { grid-template-columns: repeat(2, 1fr); }
      .skill-row { grid-template-columns: 1fr 64px; }
      .skill-row .skill-bar { grid-column: 1 / -1; grid-row: 2; }
      .solve-when { display: none; }
      .pl-name { font-size: 23px; }
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<div class="container flex-grow-1 pb-5" style="max-width: 1000px;">
  <a href="scoreboard.php?period=all" class="back-link"><i class="bi bi-arrow-left"></i> Back to scoreboard</a>

  <?php if (!$p): ?>
    <div class="pl-card text-center py-5">
      <i class="bi bi-person-x fs-1 text-secondary d-block mb-2"></i>
      <div class="fw-bold text-light">Player not found</div>
      <div class="small text-secondary mt-1">There's no KrazePlanet student called “<?= $e($username) ?>”.</div>
    </div>
  <?php else: ?>

    <div class="pl-card pl-head">
      <img src="<?= $e($p['avatar']) ?>" alt="" class="pl-avatar">
      <div>
        <div class="pl-name"><?= $e($p['username']) ?><?php if ($isMe): ?> <span class="you-tag">(you)</span><?php endif; ?></div>
        <div class="pl-meta">
          <?php if (!empty($p['country'])): ?><span class="pl-badge"><?= $e($p['country']) ?></span><?php endif; ?>
          <span><i class="bi bi-calendar3 me-1"></i>Joined <?= $e(date('M Y', strtotime($p['joined']))) ?></span>
          <?php if (!empty($solved)): ?><span><i class="bi bi-clock-history me-1"></i>Last solved <?= $e(playerTimeAgo($solved[0]['solved_at'])) ?></span><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat">
        <div class="stat-label">All-time rank</div>
        <div class="stat-value"><?= $p['rank'] ? '#' . (int)$p['rank'] : '—' ?></div>
        <div class="stat-sub"><?= $p['rank'] ? 'across all KrazePlanet sites' : 'no labs solved yet' ?></div>
      </div>
      <div class="stat">
        <div class="stat-label">Points</div>
        <div class="stat-value"><?= number_format((int)$p['points']) ?></div>
        <div class="stat-sub">Easy 20 · Medium 50 · Hard 100</div>
      </div>
      <div class="stat">
        <div class="stat-label">Labs solved</div>
        <div class="stat-value"><?= count($solved) ?></div>
        <div class="split"><span class="c-easy" title="easy">E <?= $diffCount['easy'] ?></span><span class="c-medium" title="medium">M <?= $diffCount['medium'] ?></span><span class="c-hard" title="hard">H <?= $diffCount['hard'] ?></span></div>
      </div>
      <div class="stat">
        <div class="stat-label">Solve streak</div>
        <div class="stat-value"><?= (int)$p['streak'] > 0 ? '🔥 ' : '' ?><?= (int)$p['streak'] ?> <span class="stat-sub"><?= (int)$p['streak'] === 1 ? 'day' : 'days' ?></span></div>
        <div class="stat-sub">Best: <?= (int)$p['best_streak'] ?> <?= (int)$p['best_streak'] === 1 ? 'day' : 'days' ?></div>
      </div>
    </div>

    <div class="pl-card">
      <h2><i class="bi bi-calendar3-week text-success"></i> Activity <span class="sub"><?= $activeDays ?> active <?= $activeDays === 1 ? 'day' : 'days' ?> in the last <?= $weeks ?> weeks</span></h2>
      <div class="heat" id="heat" aria-label="Labs solved per day">
        <?php for ($t = $start; $t <= strtotime('+' . (7 - (int)date('N', $end)) . ' days', $end); $t = strtotime('+1 day', $t)):
          $day = date('Y-m-d', $t);
          $n = $perDay[$day] ?? 0;
          $cls = $t > $end ? 'future' : ($n >= 4 ? 'l3' : ($n >= 2 ? 'l2' : ($n >= 1 ? 'l1' : ''))); ?>
          <i class="<?= $cls ?>" title="<?= $e(date('D j M', $t)) ?>: <?= $n ?> <?= $n === 1 ? 'lab' : 'labs' ?>"></i>
        <?php endfor; ?>
      </div>
      <script>(function(){ var h = document.getElementById('heat'); if (h) h.scrollLeft = h.scrollWidth; })();</script>
      <div class="heat-legend">Less <i style="background: rgba(255,255,255,.06)"></i><i style="background: rgba(52,211,153,.35)"></i><i style="background: rgba(52,211,153,.6)"></i><i style="background: #34d399"></i> More</div>
    </div>

    <div class="pl-card">
      <h2><i class="bi bi-bug text-info"></i> Skills by bug type <span class="sub"><?= $siteSolved ?> / <?= $siteTotal ?> labs on this site</span></h2>
      <?php foreach ($skills as $i => $s):
        if ($i === 8): ?><div class="skills-more" id="skillsMore"><?php endif;
        $pct = $s['total'] ? round($s['solved'] * 100 / $s['total']) : 0; ?>
        <div class="skill-row <?= $s['solved'] ? '' : 'is-zero' ?>">
          <div><?= $e($s['name']) ?></div>
          <div class="skill-bar"><span style="width: <?= $pct ?>%"></span></div>
          <div class="skill-num"><?= $s['solved'] ?>/<?= $s['total'] ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (count($skills) > 8): ?>
        </div>
        <button type="button" class="link-btn" onclick="const m=document.getElementById('skillsMore');m.classList.toggle('open');this.textContent=m.classList.contains('open')?'Show less':'Show all <?= count($skills) ?> bug types';">Show all <?= count($skills) ?> bug types</button>
      <?php endif; ?>
    </div>

    <div class="pl-card">
      <h2><i class="bi bi-check2-circle text-success"></i> Recently solved <span class="sub"><?= count($solved) ?> total</span></h2>
      <?php if (!$solved): ?>
        <div class="text-secondary small py-3 text-center"><?= $isMe ? 'You haven\'t solved a lab yet. Pick an easy one on the labs page to get started.' : 'No labs solved yet.' ?></div>
      <?php endif; ?>
      <?php foreach (array_slice($solved, 0, 15) as $s):
        $lab = $catalog['byId'][$s['lab_id']] ?? null;
        $title = $lab ? $lab['title'] : ucfirst(basename(str_replace('\\', '/', (string)$s['lab_id'])));
        $diff = strtolower((string)$s['difficulty']); ?>
        <div class="solve-item">
          <div class="flex-grow-1" style="min-width: 0;">
            <div class="solve-title" title="<?= $e($title) ?>"><?= $e($title) ?></div>
            <div class="solve-cat"><?= $lab ? $e($lab['category']) : 'Lab on another KrazePlanet site' ?></div>
          </div>
          <span class="diff-chip c-<?= $e($diff) ?>"><?= $e(ucfirst($diff)) ?></span>
          <span class="solve-pts">+<?= (int)$s['points'] ?></span>
          <span class="solve-when" title="<?= $e($s['solved_at']) ?>"><?= $e(playerTimeAgo($s['solved_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</div>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
