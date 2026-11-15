<?php
// scoreboard.php - Who has the most points, this month or all time.
// Kept deliberately simple: rank, student, labs solved, points. Everything
// else about a student (streak, skills, activity, recent solves) lives on
// their profile (profile.php?user=NAME), which every row links to.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
startKrazeSession(); // public page: no sign-in needed
$catalog = krazeLabCatalog();

$current_page = 'scoreboard.php';

// ---- Filters ---------------------------------------------------------------
$curYear  = (int)date('Y');
$curMonth = (int)date('n');

// All time by default, so the board matches every profile's "All-time rank" and points.
// A month is shown only when one is picked in "When" (?year=&month=).
$isAllTime     = !isset($_GET['year']) && !isset($_GET['month']);
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : $curYear;
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : $curMonth;
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $curMonth;
if ($selectedYear < 2024 || $selectedYear > $curYear) $selectedYear = $curYear;

// No flag emoji: Windows shows those as plain letters ("IN India")
$countries = [
    'ALL' => 'All countries',
    'IN' => 'India',
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'DE' => 'Germany',
    'EG' => 'Egypt',
    'AU' => 'Australia',
    'TR' => 'Turkey',
    'VN' => 'Vietnam',
    'JP' => 'Japan',
    'AE' => 'United Arab Emirates',
    'BR' => 'Brazil',
    'PK' => 'Pakistan',
    'BD' => 'Bangladesh',
];
$selectedCountry = strtoupper(trim((string)($_GET['country'] ?? 'ALL')));
if (!isset($countries[$selectedCountry])) $selectedCountry = 'ALL';

$selectedCat = (string)($_GET['cat'] ?? '');
if (!isset($catalog['byCategory'][$selectedCat])) $selectedCat = '';

// "When" choices: this month, the 11 months before it, and all time.
$periodOptions = [];
for ($i = 0; $i < 12; $i++) {
    $t = mktime(0, 0, 0, $curMonth - $i, 1, $curYear);
    $y = (int)date('Y', $t);
    $m = (int)date('n', $t);
    $label = $i === 0 ? 'This month' : ($i === 1 ? 'Last month' : date('F Y', $t));
    $periodOptions[] = ['label' => $label, 'hint' => $i < 2 ? date('F Y', $t) : '', 'q' => ['year' => $y, 'month' => $m],
                        'active' => !$isAllTime && $y === $selectedYear && $m === $selectedMonth];
}
$periodOptions[] = ['label' => 'All time', 'hint' => '', 'q' => ['period' => 'all'], 'active' => $isAllTime];
$periodLabel = 'This month';
foreach ($periodOptions as $o) if ($o['active']) $periodLabel = $o['label'];
if (!$isAllTime && !array_filter($periodOptions, fn($o) => $o['active'])) {
    $periodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
}

$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 50);
$page   = max(1, (int)($_GET['page'] ?? 1));
// Students per page: 100 by default, 25 or 50 to choose from too.
$perOptions = [25, 50, 100];
$perPage = in_array((int)($_GET['per'] ?? 0), $perOptions, true) ? (int)$_GET['per'] : 100;

// Links keep the other filters (and the search). Changing a filter goes back to page 1.
function sbUrl(array $change = []) {
    global $isAllTime, $selectedYear, $selectedMonth, $selectedCountry, $selectedCat, $search, $perPage;
    $q = ($isAllTime ? ['period' => 'all'] : ['year' => $selectedYear, 'month' => $selectedMonth])
       + ['cat' => $selectedCat, 'country' => $selectedCountry, 'q' => $search, 'per' => $perPage];
    if (isset($change['period']) || isset($change['year'])) unset($q['period'], $q['year'], $q['month']);
    $q = $change + $q;
    foreach (['cat' => '', 'q' => '', 'country' => 'ALL', 'page' => 1, 'per' => 100] as $k => $default) {
        if (isset($q[$k]) && (string)$q[$k] === (string)$default) unset($q[$k]);
    }
    return 'scoreboard.php?' . http_build_query($q);
}

// ---- Rankings (central login server, live - no cache) ----------------------
header('Cache-Control: no-store, must-revalidate');
$req = ['period' => $isAllTime ? 'all' : 'month', 'year' => $selectedYear, 'month' => $selectedMonth, 'country' => $selectedCountry];
if ($selectedCat !== '') $req['lab_ids'] = $catalog['byCategory'][$selectedCat];
$board = krazeSsoCall('scoreboard', $req);
$boardOk = !empty($board['success']);
$me = $board['me'] ?? null;
$myId = $me['id'] ?? null;

$rankings = [];
$myRow = null;
$totalLabs = $totalPoints = 0;
foreach ($board['rankings'] ?? [] as $i => $r) {
    $r['rank'] = $i + 1;
    $rankings[] = $r;
    $totalLabs += (int)$r['solved_count'];
    $totalPoints += (int)$r['reputation_points'];
    if ($myId && (int)$r['id'] === (int)$myId) $myRow = $r;
}

// Top 3 get the podium (first page, no search); the table carries on from #4.
// Students with no solves are listed too (at the bottom), but never on the podium.
$showPodium = $search === '' && count($rankings) >= 3 && (int)$rankings[2]['reputation_points'] > 0;
if ($search !== '') {
    $list = array_values(array_filter($rankings, fn($r) => stripos($r['username'], $search) !== false));
} else {
    $list = $showPodium ? array_slice($rankings, 3) : $rankings;
}
$pages = max(1, (int)ceil(count($list) / $perPage));
$page = min($page, $pages);
$pageRows = array_slice($list, ($page - 1) * $perPage, $perPage);
if ($page > 1) $showPodium = false;

// Where is the signed-in student? On this page, or which page to jump to.
$myOnPage = false;
$myPage = null;
if ($myRow) {
    if ($showPodium && $myRow['rank'] <= 3) $myOnPage = true;
    foreach ($pageRows as $r) if ((int)$r['id'] === (int)$myId) $myOnPage = true;
    if (!$myOnPage && $search === '') {
        $idx = $myRow['rank'] - 1 - ($search === '' && count($rankings) >= 3 && (int)$rankings[2]['reputation_points'] > 0 ? 3 : 0);
        $myPage = $idx < 0 ? 1 : intdiv($idx, $perPage) + 1;
    }
}

// ---- Row pieces ------------------------------------------------------------
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }

function rankBadge($rank) {
    $tier = is_numeric($rank) && $rank <= 3 ? ' top-' . (int)$rank : '';
    return '<span class="rank-num' . $tier . '">' . e($rank) . '</span>';
}

// Small arrow when someone moved places in the last 7 days.
function moveBadge(array $r) {
    $c = $r['rank_change'] ?? null;
    if (!is_numeric($c) || (int)$c === 0) return '';
    $c = (int)$c;
    $up = $c > 0;
    $n = abs($c);
    return '<span class="move ' . ($up ? 'up' : 'down') . '" title="' . ($up ? 'Up' : 'Down') . " $n " . ($n === 1 ? 'place' : 'places') . ' this week">'
         . '<i class="bi bi-caret-' . ($up ? 'up' : 'down') . '-fill"></i>' . $n . '</span>';
}

function avatarUrl(array $r) {
    return !empty($r['avatar']) ? $r['avatar'] : 'https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($r['username']);
}

function playerUrl($username) { return 'profile.php?user=' . urlencode($username); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scoreboard - KrazePlanet Web Security</title>
  <link rel="icon" type="image/png" href="https://krazeplanet.com/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --bg: #070b14;
      --surface: #0b1324;
      --surface-2: #0d172e;
      --hover: #111d3a;
      --line: rgba(255, 255, 255, 0.07);
      --line-strong: rgba(255, 255, 255, 0.12);
      --text: #e2e8f0;
      --muted: #94a3b8;
      --faint: #64748b;
      --accent: #38bdf8;
      --gold: #fbbf24;
      --silver: #cbd5e1;
      --bronze: #f59e0b;
    }
    body { background: var(--bg); color: #cbd5e1; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
    .sb-wrap { max-width: 1320px; padding-left: 24px; padding-right: 24px; }
    /* Room under the table so the floating "You're #N" bar never covers the pages */
    .sb-wrap.has-bar { padding-bottom: 96px !important; }
    .mono { font-family: 'JetBrains Mono', monospace; }

    /* ---- Page header ---- */
    .sb-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 2.75rem 0 1.75rem; }
    .sb-title { font-family: 'Outfit', sans-serif; font-size: 2.4rem; font-weight: 800; color: #fff; letter-spacing: -0.6px; margin: 0 0 6px; }
    .sb-intro { color: var(--muted); font-size: 15px; margin: 0; }

    .sb-stats { display: flex; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
    .sb-stat { padding: 12px 22px; min-width: 140px; }
    .sb-stat + .sb-stat { border-left: 1px solid var(--line); }
    .sb-stat-label { font-size: 12px; color: var(--faint); font-weight: 600; }
    .sb-stat-value { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; color: #fff; line-height: 1.3; }

    /* ---- Podium ---- */
    .podium { display: grid; grid-template-columns: 1fr 1.12fr 1fr; gap: 16px; align-items: end; margin-bottom: 24px; }
    .pod {
      position: relative; display: flex; flex-direction: column; align-items: center; text-align: center;
      background: linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.01)), var(--surface);
      border: 1px solid var(--line); border-radius: 18px; padding: 26px 18px 20px; color: inherit; text-decoration: none;
      transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; overflow: hidden;
    }
    .pod::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px; background: var(--tier); }
    .pod:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--tier) 55%, transparent); box-shadow: 0 16px 40px rgba(0,0,0,.45); color: inherit; }
    .pod.p1 { --tier: var(--gold); padding-top: 34px; padding-bottom: 26px; background: radial-gradient(120% 70% at 50% 0%, rgba(251,191,36,.12), transparent 60%), var(--surface); }
    .pod.p2 { --tier: var(--silver); }
    .pod.p3 { --tier: var(--bronze); }
    .pod.is-me { box-shadow: 0 0 0 1px rgba(56,189,248,.55) inset; }
    .pod-place { position: absolute; top: 14px; left: 16px; font-weight: 700; font-size: 13px; color: var(--tier); }
    .pod-crown { position: absolute; top: 12px; right: 16px; color: var(--gold); font-size: 18px; }
    .pod-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; background: #1e293b; border: 3px solid var(--tier); margin-bottom: 12px; }
    .pod.p1 .pod-avatar { width: 88px; height: 88px; }
    .pod-name { font-weight: 700; color: #fff; font-size: 16px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pod-points { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #fff; line-height: 1.2; margin-top: 8px; }
    .pod.p1 .pod-points { font-size: 34px; }
    .pod-points small { font-size: 13px; color: var(--faint); font-weight: 600; margin-left: 4px; }
    .pod-sub { font-size: 13px; color: var(--muted); }
    .you-tag { display: inline-block; color: var(--muted); border: 1px solid rgba(255,255,255,.18); font-size: 10.5px; font-weight: 700; letter-spacing: .4px; border-radius: 5px; padding: 0 6px; margin-left: 8px; vertical-align: 2px; }

    /* ---- Table card ---- */
    /* No overflow:hidden here - it would cut off the filter menus. Corners are rounded on the first/last parts instead. */
    .sb-card { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.45); }
    .sb-card > :last-child, .sb-card > :last-child > :last-child { border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; }
    .sb-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 16px; border-bottom: 1px solid var(--line); }
    .sb-search { position: relative; flex: 1 1 260px; max-width: 340px; }
    .sb-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--faint); font-size: 14px; }
    .sb-search input {
      width: 100%; background: var(--bg); border: 1px solid var(--line-strong); color: #fff; border-radius: 10px;
      padding: 8px 12px 8px 34px; font-size: 14px; outline: none; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .sb-search input::placeholder { color: var(--faint); }
    .sb-search input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,.15); }
    .sb-filters { display: flex; gap: 8px; flex-wrap: wrap; }
    .sb-select {
      background: var(--bg); border: 1px solid var(--line-strong); color: #f1f5f9; font-size: 13.5px; font-weight: 500;
      border-radius: 10px; padding: 7px 12px; display: inline-flex; align-items: center; gap: 8px; transition: all .15s ease;
    }
    .sb-select .k { color: var(--faint); font-weight: 500; }
    .sb-select .v { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sb-select:hover, .sb-select[aria-expanded="true"] { border-color: var(--accent); color: #fff; }
    .sb-select.is-set { border-color: rgba(56,189,248,.5); background: rgba(56,189,248,.08); }
    .sb-menu {
      background: #0b1324; border: 1px solid var(--line-strong); border-radius: 12px; padding: 6px;
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.9); max-height: 340px; overflow-y: auto; min-width: 240px;
    }
    .sb-menu .dropdown-item { color: #cbd5e1; font-size: 13.5px; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .sb-menu .dropdown-item:hover { background: rgba(56, 189, 248, 0.1); color: #fff; }
    .sb-menu .dropdown-item.active { background: rgba(56, 189, 248, 0.16); color: var(--accent); font-weight: 600; }
    .sb-menu { z-index: 1050; scrollbar-width: thin; scrollbar-color: rgba(148,163,184,.35) transparent; }
    .sb-menu::-webkit-scrollbar { width: 8px; }
    .sb-menu::-webkit-scrollbar-track { background: transparent; }
    .sb-menu::-webkit-scrollbar-thumb { background: rgba(148,163,184,.3); border-radius: 8px; border: 2px solid #0b1324; }
    .sb-menu::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,.5); }
    .sb-menu .hint { font-size: 12px; color: var(--faint); }
    .sb-menu .dropdown-divider { border-color: var(--line); margin: 4px 0; }

    .sb-row { display: grid; grid-template-columns: 96px minmax(0, 1fr) 150px 150px 32px; align-items: center; gap: 12px; padding: 0 20px; min-height: 60px; }
    .sb-row + .sb-row { border-top: 1px solid var(--line); }
    .sb-row.head { min-height: 42px; background: var(--surface-2); font-size: 12px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; color: var(--faint); }
    a.sb-row { color: inherit; text-decoration: none; transition: background .12s ease; }
    a.sb-row:hover { background: var(--hover); }
    a.sb-row:hover .go { color: var(--accent); transform: translateX(2px); }
    .num { text-align: right; }

    .rank-cell { display: flex; align-items: center; gap: 10px; }
    .rank-num { min-width: 34px; height: 30px; padding: 0 8px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
      font-variant-numeric: tabular-nums; font-weight: 700; font-size: 14px; color: var(--text); background: rgba(255,255,255,.05); border: 1px solid var(--line); }
    .rank-num.top-1 { color: #1c1404; background: var(--gold); border-color: var(--gold); }
    .rank-num.top-2 { color: #0f172a; background: var(--silver); border-color: var(--silver); }
    .rank-num.top-3 { color: #1c0d02; background: var(--bronze); border-color: var(--bronze); }
    .move { font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; display: inline-flex; align-items: center; }
    .move.up { color: #34d399; }
    .move.down { color: #f87171; }

    .who { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .who img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #1e293b; border: 1px solid var(--line-strong); flex-shrink: 0; }
    .who .name { font-weight: 600; color: #f8fafc; font-size: 14.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .labs { font-variant-numeric: tabular-nums; font-weight: 500; color: var(--muted); font-size: 14.5px; }
    .points { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; font-size: 15px; }
    .points small { font-size: 11px; color: var(--faint); font-weight: 600; margin-left: 4px; }
    .go { color: var(--faint); text-align: right; transition: all .15s ease; }

    /* No solves yet: listed, but quieter than students with points */
    .sb-row.is-zero .who .name, .sb-row.is-zero .points { color: var(--muted); font-weight: 500; }
    .sb-row.is-zero .who img { opacity: .7; }
    .sb-row.me { background: rgba(56, 189, 248, 0.07); box-shadow: inset 3px 0 0 var(--accent); }
    a.sb-row.me:hover { background: rgba(56, 189, 248, 0.12); }

    .sb-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 20px; border-top: 1px solid var(--line); background: var(--surface-2); font-size: 13px; color: var(--muted); }
    .pager { display: flex; gap: 6px; }
    .pager a, .pager span { min-width: 34px; height: 32px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;
      border: 1px solid var(--line-strong); color: #cbd5e1; text-decoration: none; font-size: 13px; }
    .pager a:hover { border-color: var(--accent); color: #fff; }
    .pager .cur { background: rgba(56,189,248,.16); border-color: rgba(56,189,248,.5); color: var(--accent); font-weight: 700; }
    .pager .off { opacity: .35; }
    .pager .gap { border: 0; min-width: 20px; padding: 0; color: var(--faint); }
    .sb-count strong { color: var(--text); font-weight: 600; }
    .sb-pagination { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
    .sb-per { display: flex; align-items: center; gap: 8px; }
    .sb-per-label { font-size: 13px; color: var(--muted); white-space: nowrap; }
    .sb-per .sb-select { padding: 5px 10px; font-size: 13px; }
    .sb-per .sb-menu { min-width: 90px; }
    /* Opens below the button, like every other menu on the page */
    .sb-per .sb-menu { top: calc(100% + 6px); }
    .sb-per { position: relative; }

    .sb-empty { padding: 56px 20px; text-align: center; color: var(--muted); }
    .sb-empty i { font-size: 2rem; display: block; margin-bottom: 8px; color: var(--faint); }
    .sb-empty a { color: var(--accent); }

    /* ---- "Your position" bar ---- */
    .me-bar {
      position: fixed; left: 50%; bottom: 20px; transform: translate(-50%, 140%); z-index: 1040; transition: transform .25s ease;
      display: flex; align-items: center; gap: 14px; background: rgba(11,19,36,.96); backdrop-filter: blur(8px);
      border: 1px solid rgba(56,189,248,.45); border-radius: 14px; padding: 10px 12px 10px 14px; box-shadow: 0 16px 40px rgba(0,0,0,.6);
      max-width: calc(100vw - 32px); font-size: 14px; color: #e2e8f0;
    }
    .me-bar.show { transform: translate(-50%, 0); }
    .me-bar img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
    .me-bar strong { color: #fff; }
    .me-bar .btn-jump { background: var(--accent); color: #04121f; border: 0; font-weight: 700; font-size: 13px; border-radius: 9px; padding: 6px 12px; text-decoration: none; white-space: nowrap; }
    .me-bar .btn-jump:hover { background: #7dd3fc; }

    @media (max-width: 991px) {
      .sb-stats { width: 100%; }
      .sb-stat { flex: 1; min-width: 0; padding: 10px 14px; }
    }
    @media (max-width: 640px) {
      .sb-wrap { padding-left: 16px; padding-right: 16px; }
      .sb-head { padding: 1.75rem 0 1.25rem; gap: 16px; }
      .sb-title { font-size: 1.9rem; }
      .sb-stat-value { font-size: 18px; }
      .sb-stat-label { font-size: 11px; }
      .podium { gap: 8px; margin-bottom: 16px; }
      .pod { padding: 22px 6px 14px; border-radius: 14px; }
      .pod.p1 { padding-top: 26px; padding-bottom: 18px; }
      .pod-avatar, .pod.p1 .pod-avatar { width: 48px; height: 48px; border-width: 2px; margin-bottom: 8px; }
      .pod.p1 .pod-avatar { width: 58px; height: 58px; }
      .pod-name { font-size: 13px; }
      .pod-points, .pod.p1 .pod-points { font-size: 19px; margin-top: 4px; }
      .pod-points small, .pod-sub { display: none; }
      .pod-place { top: 8px; left: 10px; font-size: 11px; }
      .pod-crown { top: 6px; right: 10px; font-size: 14px; }
      .you-tag { margin-left: 4px; font-size: 9.5px; }
      .who .you-tag { display: none; } /* your row is highlighted already; keep the name readable */
      .sb-search { max-width: none; flex-basis: 100%; }
      .sb-filters { width: 100%; }
      .sb-filters .dropdown { flex: 1 1 calc(50% - 4px); min-width: 0; }
      .sb-filters .dropdown:first-child { flex-basis: 100%; }
      .sb-select { width: 100%; padding: 7px 10px; }
      .sb-select .v { max-width: 100%; flex: 1; text-align: left; }
      .sb-row { grid-template-columns: 50px minmax(0, 1fr) 46px 66px; gap: 8px; padding: 0 12px; min-height: 56px; }
      .sb-row .go { display: none; }
      .rank-cell { flex-direction: column; align-items: flex-start; gap: 2px; }
      .rank-num { min-width: 30px; height: 26px; font-size: 12.5px; padding: 0 6px; }
      .who { gap: 9px; }
      .who img { width: 30px; height: 30px; }
      .who .name { font-size: 14px; }
      .points { font-size: 14.5px; }
      .points small { display: none; }
      .sb-row.head { font-size: 10.5px; letter-spacing: .2px; }
      .sb-footer { flex-direction: column; justify-content: center; text-align: center; }
      .sb-pagination { justify-content: center; gap: 12px; }
      .me-bar { font-size: 13px; gap: 10px; bottom: 12px; }
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<div class="container sb-wrap flex-grow-1 pb-5<?= $boardOk && (($me && $search === '') || !krazeLoggedIn()) ? ' has-bar' : '' ?>">

  <div class="sb-head">
    <div>
      <h1 class="sb-title">Scoreboard</h1>
      <p class="sb-intro">Solve labs to earn points: Easy 20, Medium 50, Hard 100, Secure 150. Most points ranks first.</p>
    </div>
    <div class="sb-stats" aria-label="Board summary">
      <div class="sb-stat"><div class="sb-stat-label">Students</div><div class="sb-stat-value"><?= number_format(count($rankings)) ?></div></div>
      <div class="sb-stat"><div class="sb-stat-label">Labs solved</div><div class="sb-stat-value"><?= number_format($totalLabs) ?></div></div>
      <div class="sb-stat"><div class="sb-stat-label">Points earned</div><div class="sb-stat-value"><?= number_format($totalPoints) ?></div></div>
    </div>
  </div>

  <?php if ($boardOk && $showPodium): ?>
    <div class="podium">
      <?php foreach ([1, 0, 2] as $i): $r = $rankings[$i]; $place = $i + 1; $isMe = $myId && (int)$r['id'] === (int)$myId; ?>
        <a class="pod p<?= $place ?><?= $isMe ? ' is-me' : '' ?>" href="<?= e(playerUrl($r['username'])) ?>" <?= $isMe ? 'id="me-anchor"' : '' ?> title="See <?= e($r['username']) ?>'s profile">
          <span class="pod-place">#<?= $place ?></span>
          <?php if ($place === 1): ?><i class="bi bi-trophy-fill pod-crown"></i><?php endif; ?>
          <img class="pod-avatar" src="<?= e(avatarUrl($r)) ?>" alt="">
          <div class="pod-name"><?= e($r['username']) ?><?php if ($isMe): ?><span class="you-tag">YOU</span><?php endif; ?></div>
          <div class="pod-points"><?= number_format((int)$r['reputation_points']) ?><small>pts</small></div>
          <div class="pod-sub"><?= number_format((int)$r['solved_count']) ?> <?= (int)$r['solved_count'] === 1 ? 'lab' : 'labs' ?> solved</div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="sb-card">
    <div class="sb-toolbar">
      <form class="sb-search" method="get" action="scoreboard.php" role="search">
        <i class="bi bi-search"></i>
        <?php if ($isAllTime): ?><input type="hidden" name="period" value="all">
        <?php else: ?><input type="hidden" name="year" value="<?= $selectedYear ?>"><input type="hidden" name="month" value="<?= $selectedMonth ?>"><?php endif; ?>
        <?php if ($selectedCat !== ''): ?><input type="hidden" name="cat" value="<?= e($selectedCat) ?>"><?php endif; ?>
        <?php if ($selectedCountry !== 'ALL'): ?><input type="hidden" name="country" value="<?= e($selectedCountry) ?>"><?php endif; ?>
        <?php if ($perPage !== 100): ?><input type="hidden" name="per" value="<?= $perPage ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search students" aria-label="Search students" autocomplete="off">
      </form>

      <div class="sb-filters">
        <div class="dropdown">
          <button class="sb-select dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><span class="k">When</span><span class="v"><?= e($periodLabel) ?></span></button>
          <ul class="dropdown-menu dropdown-menu-end sb-menu">
            <?php foreach ($periodOptions as $o): ?>
              <?php if ($o['label'] === 'All time'): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
              <li><a class="dropdown-item <?= $o['active'] ? 'active' : '' ?>" href="<?= e(sbUrl($o['q'])) ?>">
                <span><?= e($o['label']) ?></span><?php if ($o['hint']): ?><span class="hint"><?= e($o['hint']) ?></span><?php endif; ?>
              </a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="dropdown">
          <button class="sb-select dropdown-toggle <?= $selectedCat !== '' ? 'is-set' : '' ?>" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><span class="k">Bug type</span><span class="v"><?= $selectedCat !== '' ? e($selectedCat) : 'All' ?></span></button>
          <ul class="dropdown-menu dropdown-menu-end sb-menu">
            <li><a class="dropdown-item <?= $selectedCat === '' ? 'active' : '' ?>" href="<?= e(sbUrl(['cat' => ''])) ?>"><span>All bug types</span></a></li>
            <li><hr class="dropdown-divider"></li>
            <?php foreach ($catalog['byCategory'] as $catName => $ids): ?>
              <li><a class="dropdown-item <?= $selectedCat === $catName ? 'active' : '' ?>" href="<?= e(sbUrl(['cat' => $catName])) ?>">
                <span><?= e($catName) ?></span><span class="hint"><?= count($ids) ?> labs</span>
              </a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="dropdown">
          <button class="sb-select dropdown-toggle <?= $selectedCountry !== 'ALL' ? 'is-set' : '' ?>" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><span class="k">Country</span><span class="v"><?= $selectedCountry === 'ALL' ? 'All' : e($countries[$selectedCountry]) ?></span></button>
          <ul class="dropdown-menu dropdown-menu-end sb-menu">
            <?php foreach ($countries as $code => $name): ?>
              <?php if ($code === 'IN'): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
              <li><a class="dropdown-item <?= $selectedCountry === $code ? 'active' : '' ?>" href="<?= e(sbUrl(['country' => $code])) ?>"><span><?= e($name) ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <div role="table" aria-label="Scoreboard">
      <div class="sb-row head" role="row">
        <span>Rank</span><span>Student</span><span class="num">Labs solved</span><span class="num">Points</span><span></span>
      </div>

      <?php if (!$boardOk): ?>
        <div class="sb-empty"><i class="bi bi-wifi-off"></i><div class="fw-semibold text-light">The scoreboard couldn't load</div><div class="small mt-1">Please refresh the page in a minute.</div></div>
      <?php elseif (!$rankings): ?>
        <div class="sb-empty">
          <i class="bi bi-trophy"></i>
          <div class="fw-semibold text-light">Nobody is on this board yet</div>
          <div class="small mt-1">Solve a lab<?= $selectedCat !== '' ? ' in ' . e($selectedCat) : '' ?> to be the first one here. <a href="/index.php">Go to labs</a></div>
        </div>
      <?php elseif (!$pageRows && $search !== ''): ?>
        <div class="sb-empty">
          <i class="bi bi-search"></i>
          <div class="fw-semibold text-light">No student matches “<?= e($search) ?>”</div>
          <div class="small mt-1"><a href="<?= e(sbUrl(['q' => ''])) ?>">Clear search</a></div>
        </div>
      <?php elseif (!$pageRows): ?>
        <div class="sb-empty small">Only the top 3 are on this board so far.</div>
      <?php endif; ?>

      <?php foreach ($pageRows as $r): $isMe = $myId && (int)$r['id'] === (int)$myId; ?>
        <a class="sb-row<?= $isMe ? ' me' : '' ?><?= (int)$r['solved_count'] === 0 ? ' is-zero' : '' ?>" role="row" href="<?= e(playerUrl($r['username'])) ?>" <?= $isMe ? 'id="me-anchor"' : '' ?> title="See <?= e($r['username']) ?>'s profile">
          <span class="rank-cell"><?= rankBadge($r['rank']) ?><?= moveBadge($r) ?></span>
          <span class="who">
            <img src="<?= e(avatarUrl($r)) ?>" alt="" loading="lazy">
            <span class="name"><?= e($r['username']) ?><?php if ($isMe): ?><span class="you-tag">YOU</span><?php endif; ?></span>
          </span>
          <span class="num labs"><?= number_format((int)$r['solved_count']) ?></span>
          <span class="num points"><?= number_format((int)$r['reputation_points']) ?><small>pts</small></span>
          <span class="go"><i class="bi bi-chevron-right"></i></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($boardOk && $rankings && ($search === '' || $pageRows)):
      // "Showing 1-9 of 9": page 1 counts the podium too
      $from = ($search === '' && $showPodium) ? 1 : ($pageRows ? ($search === '' ? $pageRows[0]['rank'] : ($page - 1) * $perPage + 1) : 1);
      $to = $pageRows ? ($search === '' ? end($pageRows)['rank'] : ($page - 1) * $perPage + count($pageRows)) : count($rankings);
      // Page numbers: first, last, and two either side of the current one, with … for gaps
      $nums = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages], fn($n) => $n >= 1 && $n <= $pages));
      sort($nums); ?>
      <div class="sb-footer">
        <span class="sb-count">
          <?php if ($search !== ''): ?>
            <?= count($list) ?> <?= count($list) === 1 ? 'match' : 'matches' ?> for “<?= e($search) ?>” · <a href="<?= e(sbUrl(['q' => ''])) ?>" class="text-info text-decoration-none">Clear</a>
          <?php else: ?>
            Showing <strong><?= $from ?>–<?= $to ?></strong> of <strong><?= number_format(count($rankings)) ?></strong> students
          <?php endif; ?>
        </span>
        <div class="sb-pagination">
          <div class="dropdown sb-per">
            <span class="sb-per-label">Rows per page</span>
            <button class="sb-select dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Rows per page"><span class="v"><?= $perPage ?></span></button>
            <ul class="dropdown-menu dropdown-menu-end sb-menu">
              <?php foreach ($perOptions as $n): ?>
                <li><a class="dropdown-item <?= $n === $perPage ? 'active' : '' ?>" href="<?= e(sbUrl(['per' => $n, 'page' => 1])) ?>"><span><?= $n ?></span></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <nav class="pager" aria-label="Pages">
            <?php if ($page > 1): ?><a href="<?= e(sbUrl(['page' => $page - 1])) ?>" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="off" aria-hidden="true"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
            <?php $prev = 0; foreach ($nums as $p): ?>
              <?php if ($p - $prev > 1): ?><span class="gap">…</span><?php endif; $prev = $p; ?>
              <?php if ($p === $page): ?><span class="cur" aria-current="page"><?= $p ?></span><?php else: ?><a href="<?= e(sbUrl(['page' => $p])) ?>"><?= $p ?></a><?php endif; ?>
            <?php endforeach; ?>
            <?php if ($page < $pages): ?><a href="<?= e(sbUrl(['page' => $page + 1])) ?>" aria-label="Next page"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="off" aria-hidden="true"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
          </nav>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($boardOk && $me && $search === ''): ?>
  <!-- Shows when your own row is off screen (or on another page) -->
  <div class="me-bar" id="meBar" role="status">
    <img src="<?= e(avatarUrl($me)) ?>" alt="">
    <?php if ($myRow): ?>
      <span>You're <strong>#<?= (int)$myRow['rank'] ?></strong> with <strong><?= number_format((int)$myRow['reputation_points']) ?> pts</strong></span>
      <?php if ($myOnPage): ?>
        <button type="button" class="btn-jump" id="meJump">Show me</button>
      <?php elseif ($myPage): ?>
        <a class="btn-jump" href="<?= e(sbUrl(['page' => $myPage])) ?>#me-anchor">Show me</a>
      <?php endif; ?>
    <?php else: ?>
      <span>You're not on this board yet</span>
      <a class="btn-jump" href="/index.php">Solve a lab</a>
    <?php endif; ?>
  </div>
  <script>
    (function () {
      var bar = document.getElementById('meBar');
      var mine = document.getElementById('me-anchor');
      if (!bar) return;
      if (!mine || !('IntersectionObserver' in window)) { bar.classList.add('show'); return; }
      new IntersectionObserver(function (entries) {
        bar.classList.toggle('show', !entries[0].isIntersecting);
      }).observe(mine);
      var jump = document.getElementById('meJump');
      if (jump) jump.addEventListener('click', function () {
        mine.scrollIntoView({ behavior: 'smooth', block: 'center' });
        mine.animate([{ background: 'rgba(56,189,248,.28)' }, { background: '' }], { duration: 1400 });
      });
      if (location.hash === '#me-anchor') mine.scrollIntoView({ block: 'center' });
    })();
  </script>
<?php elseif ($boardOk && !krazeLoggedIn()): ?>
  <!-- Visitors: invite them to join -->
  <div class="me-bar show" role="complementary">
    <span>Want your name on this board?</span>
    <a class="btn-jump" href="<?= e(krazeLoginUrl('/navbar/scoreboard.php', 'signup')) ?>">Create a free account</a>
  </div>
<?php endif; ?>

<script>
  // "Rows per page" opens downward; if that's below the screen, scroll just enough to show it.
  document.querySelectorAll('.sb-per [data-bs-toggle="dropdown"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.dropdown', function () {
      var menu = btn.parentElement.querySelector('.dropdown-menu');
      if (menu && menu.getBoundingClientRect().bottom > window.innerHeight - 90) {
        window.scrollBy({ top: menu.getBoundingClientRect().bottom - window.innerHeight + 100, behavior: 'smooth' });
      }
    });
  });
</script>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
