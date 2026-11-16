<?php
// profile.php - A student's profile.
//   profile.php            your own profile: progress + account settings (sign-in needed)
//   profile.php?user=NAME  anyone's public profile: progress only (no sign-in needed;
//                          the scoreboard links here)
// Progress = all-time rank, points, labs solved, solve streak, activity, skills by bug
// type and recently solved labs, all from the central login server.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
startKrazeSession();
header('Cache-Control: no-store, must-revalidate');

$labCatalog = krazeLabCatalog();
$myName = (string)($_SESSION['username'] ?? '');
$askedFor = trim((string)($_GET['user'] ?? ''));
$isOwn = $askedFor === '' || (krazeLoggedIn() && strcasecmp($askedFor, $myName) === 0);
if ($isOwn) {
    krazeRequireLogin();
    $myName = (string)($_SESSION['username'] ?? '');
}
$target = $isOwn ? $myName : $askedFor;
$current_page = $isOwn ? 'profile.php' : 'scoreboard.php';

// Your own profile has two tabs: History (progress) and Settings (account).
// Other students' profiles show History only. Old links used ?tab=profile for the
// account cards (e.g. Write Report's "Add GitHub repo"), so that opens Settings.
$tab = strtolower((string)($_GET['tab'] ?? 'history'));
$tab = $isOwn && in_array($tab, ['settings', 'profile'], true) ? 'settings' : 'history';

// Public progress (same for own and other students)
$res = $target !== '' ? krazeSsoCall('player', ['username' => $target]) : ['success' => false];
$p = !empty($res['success']) ? $res['player'] : null;

// Account details: own profile only
$account = null;
if ($isOwn) {
    $bundle = krazeSsoCall('profile_bundle');
    if (!krazeLoggedIn()) { header('Location: ' . krazeLoginUrl($_SERVER['REQUEST_URI'] ?? '/navbar/profile.php')); exit; }
    $account = $bundle['user'] ?? null;
}
if (!$p) http_response_code(404);

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);

function profileTimeAgo($when) {
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
    // Difficulty from the lab list (so Secure labs show as Secure), else what was recorded
    $diffCount = ['easy' => 0, 'medium' => 0, 'hard' => 0, 'secure' => 0];
    $perDay = [];
    $solvedIds = [];
    foreach ($solved as $i => $s) {
        $d = strtolower($labCatalog['byId'][$s['lab_id']]['difficulty'] ?? (string)$s['difficulty']);
        $solved[$i]['diff'] = $d;
        if (isset($diffCount[$d])) $diffCount[$d]++;
        $day = substr((string)$s['solved_at'], 0, 10);
        $perDay[$day] = ($perDay[$day] ?? 0) + 1;
        $solvedIds[$s['lab_id']] = true;
    }

    // Skills: solved / total for every bug type on this site, best first
    $skills = [];
    foreach ($labCatalog['byCategory'] as $cat => $ids) {
        $n = 0;
        foreach ($ids as $id) if (isset($solvedIds[$id])) $n++;
        $skills[] = ['name' => $cat, 'solved' => $n, 'total' => count($ids)];
    }
    usort($skills, fn($a, $b) => [$b['solved'] / max(1, $b['total']), $b['solved']] <=> [$a['solved'] / max(1, $a['total']), $a['solved']]);
    $siteSolved = count(array_intersect_key($solvedIds, $labCatalog['byId']));
    $siteTotal = count($labCatalog['byId']);

    // Activity: the last year, one column per week (Monday at the top), like GitHub
    $end = strtotime('today');
    $start = strtotime('-364 days', $end);
    $start = strtotime('-' . ((int)date('N', $start) - 1) . ' days', $start);
    $gridEnd = strtotime('+' . (7 - (int)date('N', $end)) . ' days', $end);
    $weeks = (int)round(($gridEnd - $start) / 604800);
    $activeDays = 0;
    $yearSolved = 0;
    foreach ($perDay as $day => $n) if (strtotime($day) >= $start) { $activeDays++; $yearSolved += $n; }
    // Month labels: placed on the first week that starts in a new month
    $monthLabels = [];
    for ($w = 0; $w < $weeks; $w++) {
        $wk = strtotime('+' . ($w * 7) . ' days', $start);
        $m = date('Y-m', strtotime('+6 days', $wk));
        if ($w === 0 || $m !== date('Y-m', strtotime('+6 days', strtotime('-7 days', $wk)))) $monthLabels[$w] = date('M', strtotime('+6 days', $wk));
    }
    // Drop a label that would sit too close to the next one
    $keys = array_keys($monthLabels);
    foreach ($keys as $i => $w) if (isset($keys[$i + 1]) && $keys[$i + 1] - $w < 3) unset($monthLabels[$w]);

    $solvedCount = count($solved);
    $rankTitle = 'Trainee';
    if ($solvedCount >= 100) $rankTitle = 'CTF Grandmaster';
    elseif ($solvedCount >= 50) $rankTitle = 'Cyber Security Elite';
    elseif ($solvedCount >= 20) $rankTitle = 'Pro Hunter';
    elseif ($solvedCount >= 5) $rankTitle = 'Apprentice Hunter';
}

if ($isOwn && $account) {
    $fullname = (string)($account['fullname'] ?? '');
    $email = (string)($account['email'] ?? ($_SESSION['user_email'] ?? ''));
    $githubRepo = (string)($account['github_repo'] ?? krazeGithubRepo());
    $ghUser = krazeGithubUserFromRepo($githubRepo);
    // Saved before every repo was called KrazePlanet-Reports: still works, but we say so
    $legacyRepo = ($githubRepo !== '' && $githubRepo !== krazeReportsRepoUrl($ghUser)) ? $githubRepo : '';
    $newRepoUrl = 'https://github.com/new?name=' . KRAZE_REPORTS_REPO . '&visibility=public&description=' . rawurlencode('My KrazePlanet lab reports');
    // What the GitHub card shows: setup steps, "repository not found", or just the repo
    $ghState = 'none';
    if ($legacyRepo !== '') $ghState = 'legacy';
    elseif ($ghUser !== '') $ghState = ($tab === 'settings' && krazeRepoExists($ghUser) === false) ? 'missing' : 'ok';
    // Repo is there but has no README yet: offer a ready-made one (only shown when we're sure)
    $accountPageUrl = krazeSsoAccountUrl();
    $publicPath = '/navbar/profile.php?user=' . urlencode($myName);
    $publicUrl = krazeSiteUrl($publicPath);
}
// Country code (IN) -> "India" (no flag emoji: Windows shows those as plain letters)
function profileCountry($code) {
    $code = strtoupper(trim((string)$code));
    if (!preg_match('/^[A-Z]{2}$/', $code)) return $code;
    $names = ['IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom', 'DE' => 'Germany', 'EG' => 'Egypt',
        'AU' => 'Australia', 'TR' => 'Turkey', 'VN' => 'Vietnam', 'JP' => 'Japan', 'AE' => 'United Arab Emirates',
        'BR' => 'Brazil', 'PK' => 'Pakistan', 'BD' => 'Bangladesh'];
    $name = $names[$code] ?? (class_exists('Locale') ? Locale::getDisplayRegion('-' . $code, 'en') : '');
    return $name !== '' && $name !== $code ? $name : $code;
}
$avatarUrl = $p['avatar'] ??('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($target ?: 'student'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $p ? $e($p['username']) . ($isOwn ? ' - Your profile' : ' - Profile') : 'Student not found' ?> - KrazePlanet</title>
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
      --card: #0b1324;
      --inner: #0d1628;
      --line: rgba(255, 255, 255, 0.08);
      --muted: #94a3b8;
      --faint: #64748b;
      --accent: #38bdf8;
    }
    body { background: var(--bg); color: #cbd5e1; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
    .mono { font-family: 'JetBrains Mono', monospace; }

    /* ---- Left: who + key numbers ---- */
    .side-card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 2rem 1.5rem 1.5rem; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,.45); }
    .side-avatar-wrap { position: relative; display: inline-block; margin-bottom: 1rem; }
    .side-avatar { width: 108px; height: 108px; border-radius: 50%; object-fit: cover; background: #1e293b; border: 3px solid var(--accent); }
    .side-avatar.is-editable { cursor: pointer; }
    .side-avatar-edit { position: absolute; bottom: 4px; right: 4px; width: 28px; height: 28px; border-radius: 50%; background: #0284c7; color: #fff; border: 2px solid var(--card); display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; }
    .side-name { font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 700; color: #fff; line-height: 1.2; word-break: break-word; }
    .you-tag { display: inline-block; vertical-align: 3px; margin-left: 6px; font-family: 'Inter', sans-serif; font-size: 10.5px; font-weight: 700; letter-spacing: .4px; color: var(--muted); border: 1px solid rgba(255,255,255,.18); border-radius: 5px; padding: 0 6px; }
    .side-title { font-size: 13px; font-weight: 500; color: var(--muted); margin-top: 4px; }
    .side-stats { margin: 1.25rem 0 0; text-align: left; }
    .side-row { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.05); font-size: 13.5px; }
    .side-row:last-child { border-bottom: 0; }
    .side-row dt { color: var(--muted); font-weight: 500; }
    .side-row dd { margin: 0; color: #fff; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .side-row .sub { display: block; font-size: 11.5px; font-weight: 500; color: var(--faint); margin-top: 2px; }
    .side-row.is-stack { flex-wrap: wrap; }
    .split { flex-basis: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; list-style: none; margin: 8px 0 0; padding: 0; font-size: 12px; color: var(--muted); }
    .split li { display: flex; align-items: center; gap: 6px; }
    .split li b { margin-left: auto; color: #e2e8f0; font-weight: 600; font-variant-numeric: tabular-nums; }
    .split li.is-zero, .split li.is-zero b { color: var(--faint); }
    .split .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .streak-fire { color: #fb923c; }
    .c-easy { color: #34d399; } .c-medium { color: #fbbf24; } .c-hard { color: #f87171; } .c-secure { color: #7dd3fc; }
    .side-actions { margin-top: 1.25rem; }
    .side-btn { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 8px 14px; border-radius: 10px; font-size: 13.5px; font-weight: 600; text-decoration: none; background: rgba(255,255,255,.04); border: 1px solid var(--line); color: #cbd5e1; transition: all .15s ease; }
    .side-btn:hover { background: rgba(255,255,255,.08); color: #fff; }
    .side-btn.is-danger { color: #fca5a5; }
    .side-btn.is-danger:hover { color: #fff; border-color: rgba(248,113,113,.5); background: rgba(248,113,113,.12); }

    /* ---- Right: sections ---- */
    .main-col { display: flex; flex-direction: column; gap: 16px; }
    /* History / Settings tabs (own profile) */
    .pf-tabs { display: flex; gap: 6px; margin: -0.75rem 0 1.5rem; border-bottom: 1px solid var(--line); }
    .pf-tab { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; margin-bottom: -1px; border-bottom: 2px solid transparent; color: var(--muted); font-size: 14px; font-weight: 600; text-decoration: none; transition: color .15s ease, border-color .15s ease; }
    .pf-tab:hover { color: #fff; border-bottom-color: rgba(255,255,255,.2); }
    .pf-tab.is-active { color: #fff; border-bottom-color: var(--accent); }
    .pf-tab.is-active i { color: var(--accent); }
    @media (max-width: 640px) { .pf-tab { flex: 1; justify-content: center; } }
    .pf-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px 22px; }
    .pf-card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .pf-card-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #f8fafc; margin: 0; display: flex; align-items: center; gap: 8px; }
    .pf-card-title i { color: var(--muted); font-size: 15px; }
    .pf-card-desc { font-size: 14px; color: #a3b1c6; margin: 4px 0 0; line-height: 1.5; }
    .pf-card-sub { font-size: 12.5px; color: var(--faint); white-space: nowrap; }

    /* Activity heatmap */
    .heat-scroll { overflow-x: auto; padding: 14px 0 4px; }
    .heat-inner { display: grid; grid-template-columns: 30px minmax(0, 1fr); grid-template-rows: auto auto; column-gap: 6px; row-gap: 6px; min-width: calc(36px + var(--weeks) * 15px); }
    .heat-months { grid-column: 2; display: grid; grid-template-columns: repeat(var(--weeks), minmax(0, 1fr)); gap: 3px; font-size: 11px; color: var(--faint); line-height: 1; }
    .heat-months span { white-space: nowrap; }
    .heat-days { grid-column: 1; grid-row: 2; display: grid; grid-template-rows: repeat(7, minmax(0, 1fr)); gap: 3px; font-size: 11px; color: var(--faint); }
    .heat-days span { display: flex; align-items: center; line-height: 1; }
    .heat { grid-column: 2; grid-row: 2; display: grid; grid-auto-flow: column; grid-template-columns: repeat(var(--weeks), minmax(0, 1fr)); grid-template-rows: repeat(7, auto); gap: 3px; }
    .heat i { width: 100%; aspect-ratio: 1; border-radius: 3px; background: rgba(255,255,255,.06); display: block; }
    .heat i.l1 { background: rgba(52,211,153,.35); } .heat i.l2 { background: rgba(52,211,153,.6); } .heat i.l3 { background: #34d399; }
    .heat i.future { visibility: hidden; }
    .heat-legend { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--faint); margin-top: 8px; justify-content: flex-end; }
    .heat-legend i { width: 11px; height: 11px; border-radius: 3px; display: inline-block; }

    /* Skills */
    .skills { margin-top: 10px; }
    .skill-row { display: grid; grid-template-columns: minmax(150px, 1.2fr) 3fr 64px; gap: 12px; align-items: center; padding: 8px 0; font-size: 13.5px; color: #e2e8f0; }
    .skill-row + .skill-row { border-top: 1px solid rgba(255,255,255,.04); }
    .skill-bar { height: 8px; background: rgba(255,255,255,.07); border-radius: 999px; overflow: hidden; }
    .skill-bar span { display: block; height: 100%; background: #34d399; border-radius: 999px; }
    .skill-num { text-align: right; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; }
    .skill-row.is-zero { color: var(--faint); }
    .skills-more { display: none; }
    .skills-more.open { display: block; }
    .link-btn { background: none; border: 0; color: var(--accent); font-size: 13.5px; padding: 8px 0 0; }
    .link-btn:hover { text-decoration: underline; text-underline-offset: 3px; }

    /* Recently solved */
    .solves { margin-top: 6px; }
    .solve-item { display: flex; align-items: center; gap: 12px; padding: 11px 0; font-size: 13.5px; }
    .solve-item + .solve-item { border-top: 1px solid rgba(255,255,255,.05); }
    .solve-title { color: #f1f5f9; font-weight: 500; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.4; }
    .solve-cat { font-size: 12px; color: var(--faint); }
    .diff-chip { font-size: 11px; font-weight: 600; border-radius: 6px; padding: 1px 8px; border: 1px solid; white-space: nowrap; }
    .diff-chip.c-easy { background: rgba(52,211,153,.1); border-color: rgba(52,211,153,.3); }
    .diff-chip.c-medium { background: rgba(251,191,36,.1); border-color: rgba(251,191,36,.3); }
    .diff-chip.c-hard { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.3); }
    .diff-chip.c-secure { background: rgba(56,189,248,.1); border-color: rgba(56,189,248,.3); }
    .solve-pts { font-weight: 600; color: #e2e8f0; white-space: nowrap; font-variant-numeric: tabular-nums; min-width: 44px; text-align: right; }
    .empty-note { color: var(--muted); font-size: 14px; padding: 14px 0 4px; }
    .empty-note a { color: var(--accent); }

    /* Account (own profile only) */
    .pf-list { margin: 14px 0 0; border-top: 1px solid rgba(255,255,255,.07); }
    .pf-item { display: grid; grid-template-columns: 170px 1fr; gap: 16px; padding: 13px 0; border-bottom: 1px solid rgba(255,255,255,.07); }
    .pf-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .pf-item dt { font-size: 14px; font-weight: 500; color: #a3b1c6; }
    .pf-item dd { font-size: 15px; font-weight: 600; color: #f8fafc; margin: 0; overflow-wrap: anywhere; }
    .pf-empty { font-weight: 500; color: #8b9ab1; }
    .pf-copy { display: flex; align-items: center; gap: 10px; margin-top: 14px; background: #070b14; border: 1px solid rgba(255,255,255,.1); border-radius: 10px; padding: 6px 6px 6px 14px; }
    .pf-copy-url { flex: 1; min-width: 0; font-family: 'JetBrains Mono', monospace; font-size: 13.5px; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: nowrap; font-size: 14px; font-weight: 600; border-radius: 9px; padding: 8px 14px; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; }
    .pf-btn-ghost { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.14); color: #e2e8f0; }
    .pf-btn-ghost:hover { border-color: var(--accent); color: #fff; background: rgba(56,189,248,.08); }
    .pf-btn-primary { background: #0284c7; color: #fff; padding: 9px 20px; }
    .pf-btn-primary:hover { background: #0ea5e9; }
    .pf-btn:disabled { opacity: .6; cursor: wait; }
    .pf-status { flex-shrink: 0; font-size: 12.5px; font-weight: 700; border-radius: 999px; padding: 4px 11px; color: #fbbf24; background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.35); }
    .pf-status::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: currentColor; margin-right: 7px; vertical-align: 1px; }
    .pf-status.is-on { color: #34d399; background: rgba(52,211,153,.1); border-color: rgba(52,211,153,.35); }
    /* GitHub: two short steps */
    .gh-steps { list-style: none; counter-reset: gh; margin: 16px 0 0; padding: 0; }
    .gh-steps > li { counter-increment: gh; position: relative; padding: 0 0 0 38px; }
    .gh-steps > li + li { margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.07); }
    .gh-steps > li::before { content: counter(gh); position: absolute; left: 0; top: 0; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 12.5px; font-weight: 700; color: var(--accent); border: 1px solid rgba(56,189,248,.4); }
    .gh-steps > li + li::before { top: 18px; }
    .gh-steps > li:first-child { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .gh-step-text { font-size: 14.5px; color: #cbd5e1; line-height: 1.6; }
    .gh-step-text strong { color: #f1f5f9; font-weight: 600; }
    .gh-name { font-family: 'JetBrains Mono', monospace; font-size: 13.5px; color: #f1f5f9; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12); border-radius: 6px; padding: 1px 7px; }
    .gh-create { flex-shrink: 0; }
    .gh-steps .pf-form { margin-top: 10px; }
    .gh-input { flex: 1; min-width: 0; display: flex; align-items: stretch; background: #070b14; border: 1px solid rgba(255,255,255,.16); border-radius: 10px; transition: border-color .15s ease, box-shadow .15s ease; }
    .gh-input:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,.18); }
    .gh-prefix { display: flex; align-items: center; padding-left: 14px; color: var(--faint); font-size: 14.5px; white-space: nowrap; }
    .gh-input .pf-input { border: 0; background: transparent; box-shadow: none !important; padding-left: 2px; }
    .gh-lead { font-size: 14.5px; color: #cbd5e1; line-height: 1.6; margin: 14px 0 12px; }
    .gh-lead strong { color: #f1f5f9; font-weight: 600; }
    .gh-repo { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #070b14; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; padding: 10px 10px 10px 16px; }
    .gh-repo-link { display: inline-flex; align-items: center; gap: 9px; min-width: 0; color: #f1f5f9 !important; font-weight: 600; font-size: 15px; text-decoration: none; overflow-wrap: anywhere; }
    .gh-repo-link i { color: var(--muted); }
    .gh-repo-link:hover { color: var(--accent) !important; }
    .gh-repo-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .gh-flash { margin-top: 12px; }
    #ghEdit[hidden], #ghView[hidden] { display: none; }
    @media (max-width: 640px) {
      .gh-repo { flex-direction: column; align-items: stretch; padding: 12px; }
      .gh-repo-actions .pf-btn { flex: 1; }
    }
    .pf-msg.warn { display: block; color: #fcd34d; background: rgba(251,191,36,.08); border: 1px solid rgba(251,191,36,.3); }
    .pf-msg.warn strong { color: #fde68a; font-weight: 600; }
    .pf-form { margin-top: 14px; }
    .pf-label { display: block; font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px; }
    .pf-field-row { display: flex; gap: 10px; }
    .pf-input { flex: 1; min-width: 0; background: #070b14; border: 1px solid rgba(255,255,255,.16); color: #fff; border-radius: 10px; padding: 9px 14px; font-size: 14.5px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .pf-input::placeholder { color: var(--faint); }
    .pf-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,.18); }
    .pf-msg { display: none; margin-top: 12px; font-size: 14px; font-weight: 500; border-radius: 9px; padding: 9px 12px; }
    .pf-msg.ok { display: block; color: #6ee7b7; background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.35); }
    .pf-msg.err { display: block; color: #fda4af; background: rgba(244,63,94,.1); border: 1px solid rgba(244,63,94,.35); }

    .not-found { text-align: center; padding: 56px 20px; }
    .not-found i { font-size: 2.2rem; color: var(--faint); display: block; margin-bottom: 8px; }

    @media (max-width: 767.98px) {
      /* Phones: Settings goes straight to the settings (the stats are on History) */
      .side-col-settings { display: none; }
    }
    @media (max-width: 768px) {
      .skill-row { grid-template-columns: 1fr 64px; }
      .skill-row .skill-bar { grid-column: 1 / -1; grid-row: 2; }
    }
    @media (max-width: 640px) {
      .pf-card { padding: 16px; }
      .pf-card-head { flex-direction: column; align-items: flex-start; gap: 10px; }
      .pf-item { grid-template-columns: 1fr; gap: 2px; }
      .pf-field-row { flex-direction: column; }
      .pf-field-row .pf-btn { width: 100%; }
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<div class="container-xxl py-5 flex-grow-1">
<?php if (!$p): ?>
  <div class="pf-card not-found">
    <i class="bi bi-person-x"></i>
    <div class="fw-semibold text-light">Student not found</div>
    <div class="small text-secondary mt-1">There's no KrazePlanet student called “<?= $e($target) ?>”. <a href="scoreboard.php" class="text-info">Back to the scoreboard</a></div>
  </div>
<?php else: ?>
<?php if ($isOwn): ?>
  <nav class="pf-tabs" aria-label="Profile sections">
    <a href="profile.php?tab=history" class="pf-tab <?= $tab === 'history' ? 'is-active' : '' ?>" <?= $tab === 'history' ? 'aria-current="page"' : '' ?>><i class="bi bi-clock-history"></i> History</a>
    <a href="profile.php?tab=settings" class="pf-tab <?= $tab === 'settings' ? 'is-active' : '' ?>" <?= $tab === 'settings' ? 'aria-current="page"' : '' ?>><i class="bi bi-gear"></i> Settings</a>
  </nav>
<?php endif; ?>
  <div class="row g-4">

    <!-- LEFT: who + key numbers -->
    <div class="col-lg-3 col-md-4 <?= $tab === 'settings' ? 'side-col-settings' : '' ?>">
      <aside class="side-card">
        <div class="side-avatar-wrap">
<?php if ($isOwn): ?>
          <img src="<?= $e($avatarUrl) ?>" alt="Your avatar" class="side-avatar is-editable user-menu-avatar-img" onclick="openAvatarModal(event)" title="Change avatar">
          <div class="side-avatar-edit" onclick="openAvatarModal(event)" title="Change avatar"><i class="bi bi-pencil-fill"></i></div>
<?php else: ?>
          <img src="<?= $e($avatarUrl) ?>" alt="" class="side-avatar">
<?php endif; ?>
        </div>
        <div class="side-name"><?= $e($p['username']) ?><?php if ($isOwn): ?><span class="you-tag">YOU</span><?php endif; ?></div>
        <div class="side-title"><?= $e($rankTitle) ?></div>

        <dl class="side-stats">
          <div class="side-row">
            <dt>All-time rank</dt>
            <dd><?= $p['rank'] ? '#' . (int)$p['rank'] : '—' ?></dd>
          </div>
          <div class="side-row">
            <dt>Points</dt>
            <dd><?= number_format((int)$p['points']) ?></dd>
          </div>
          <div class="side-row is-stack">
            <dt>Labs solved</dt>
            <dd><?= $solvedCount ?></dd>
            <ul class="split" aria-label="Labs solved by difficulty">
              <?php foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'secure' => 'Secure'] as $k => $label): ?>
              <li class="<?= $diffCount[$k] ? '' : 'is-zero' ?>"><i class="dot c-<?= $k ?>"></i><?= $label ?> <b><?= $diffCount[$k] ?></b></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="side-row">
            <dt>Solve streak</dt>
            <dd><?= (int)$p['streak'] > 0 ? '<i class="bi bi-fire streak-fire"></i> ' : '' ?><?= (int)$p['streak'] ?> <?= (int)$p['streak'] === 1 ? 'day' : 'days' ?><span class="sub">Best: <?= (int)$p['best_streak'] ?> <?= (int)$p['best_streak'] === 1 ? 'day' : 'days' ?></span></dd>
          </div>
          <div class="side-row">
            <dt>Joined</dt>
            <dd><?= $e(date('M j, Y', strtotime($p['joined']))) ?></dd>
          </div>
<?php if (!empty($p['country'])): ?>
          <div class="side-row">
            <dt>Country</dt>
            <dd><?= $e(profileCountry($p['country'])) ?></dd>
          </div>
<?php endif; ?>
        </dl>

<?php if (!$isOwn): ?>
        <div class="side-actions">
          <a href="scoreboard.php?period=all" class="side-btn"><i class="bi bi-arrow-left"></i> Back to scoreboard</a>
        </div>
<?php endif; ?>
      </aside>
    </div>

    <!-- RIGHT: History (progress) / Settings (account, own profile only) -->
    <div class="col-lg-9 col-md-8">
      <div class="main-col">

<?php if ($tab === 'history'): ?>
        <section class="pf-card">
          <header class="pf-card-head">
            <h2 class="pf-card-title"><i class="bi bi-calendar3-week"></i> Activity</h2>
            <span class="pf-card-sub"><?= $yearSolved ?> <?= $yearSolved === 1 ? 'lab' : 'labs' ?> solved in the last year &middot; <?= $activeDays ?> active <?= $activeDays === 1 ? 'day' : 'days' ?></span>
          </header>
          <div class="heat-scroll" id="heat">
           <div class="heat-inner" style="--weeks: <?= $weeks ?>">
            <div class="heat-months" aria-hidden="true">
              <?php foreach ($monthLabels as $w => $label): ?><span style="grid-column: <?= $w + 1 ?> / span 3"><?= $label ?></span><?php endforeach; ?>
            </div>
            <div class="heat-days" aria-hidden="true"><span></span><span>Mon</span><span></span><span>Wed</span><span></span><span>Fri</span><span></span></div>
            <div class="heat" aria-label="Labs solved per day">
            <?php for ($t = $start; $t <= $gridEnd; $t = strtotime('+1 day', $t)):
              $day = date('Y-m-d', $t);
              $n = $perDay[$day] ?? 0;
              $cls = $t > $end ? 'future' : ($n >= 4 ? 'l3' : ($n >= 2 ? 'l2' : ($n >= 1 ? 'l1' : ''))); ?>
              <i class="<?= $cls ?>" title="<?= $e(date('D j M', $t)) ?>: <?= $n ?> <?= $n === 1 ? 'lab' : 'labs' ?>"></i>
            <?php endfor; ?>
            </div>
           </div>
          </div>
          <script>(function(){ var h = document.getElementById('heat'); if (h) h.scrollLeft = h.scrollWidth; })();</script>
          <div class="heat-legend">Less <i style="background: rgba(255,255,255,.06)"></i><i style="background: rgba(52,211,153,.35)"></i><i style="background: rgba(52,211,153,.6)"></i><i style="background: #34d399"></i> More</div>
        </section>

        <section class="pf-card">
          <header class="pf-card-head">
            <h2 class="pf-card-title"><i class="bi bi-bug"></i> Skills by bug type</h2>
            <span class="pf-card-sub"><?= $siteSolved ?> of <?= $siteTotal ?> labs solved</span>
          </header>
          <div class="skills">
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
        </section>

        <section class="pf-card">
          <header class="pf-card-head">
            <h2 class="pf-card-title"><i class="bi bi-check2-circle"></i> Recently solved</h2>
          </header>
          <?php if (!$solved): ?>
            <div class="empty-note"><?= $isOwn ? 'You haven\'t solved a lab yet. <a href="/index.php">Pick an easy one to start.</a>' : 'No labs solved yet.' ?></div>
          <?php else: ?>
          <div class="solves">
          <?php foreach (array_slice($solved, 0, 10) as $s):
            $lab = $labCatalog['byId'][$s['lab_id']] ?? null;
            $title = $lab ? $lab['title'] : ucfirst(basename(str_replace('\\', '/', (string)$s['lab_id']))); ?>
            <div class="solve-item">
              <div class="flex-grow-1" style="min-width: 0;">
                <div class="solve-title" title="<?= $e($title) ?>"><?= $e($title) ?></div>
                <div class="solve-cat"><?= $lab ? $e($lab['category']) : 'Lab on another KrazePlanet site' ?> <span title="<?= $e($s['solved_at']) ?>">&middot; <?= $e(profileTimeAgo($s['solved_at'])) ?></span></div>
              </div>
              <span class="diff-chip c-<?= $e($s['diff']) ?>"><?= $e(ucfirst($s['diff'])) ?></span>
              <span class="solve-pts">+<?= (int)$s['points'] ?></span>
            </div>
          <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </section>
<?php endif; ?>

<?php if ($tab === 'settings' && $account): ?>
        <section class="pf-card">
          <h2 class="pf-card-title">Account details</h2>
          <dl class="pf-list">
            <div class="pf-item">
              <dt>Full name</dt>
              <dd><?php if (trim($fullname) !== ''): ?><?= $e($fullname) ?><?php else: ?><span class="pf-empty">Not added</span><?php endif; ?></dd>
            </div>
            <div class="pf-item">
              <dt>Email address</dt>
              <dd><?= $e($email) ?></dd>
            </div>
          </dl>
        </section>

        <section class="pf-card">
          <header class="pf-card-head">
            <h2 class="pf-card-title">Public profile</h2>
            <a class="pf-btn pf-btn-ghost" href="<?= $e($publicPath) ?>"><i class="bi bi-box-arrow-up-right"></i> View</a>
          </header>
          <div class="pf-copy">
            <span class="pf-copy-url" id="publicProfileUrl"><?= $e($publicUrl) ?></span>
            <button type="button" class="pf-btn pf-btn-ghost" id="btnCopyProfile" onclick="copyProfileUrl()"><i class="bi bi-clipboard"></i> <span>Copy link</span></button>
          </div>
        </section>

<?php
          // The username box, used in the setup steps and behind "Change"
          ob_start(); ?>
              <form id="profileDetailsForm" class="pf-form" onsubmit="handleProfileUpdate(event)">
                <div class="pf-field-row">
                  <div class="gh-input">
                    <span class="gh-prefix">github.com/</span>
                    <input type="text" id="profileGithubRepo" class="pf-input" value="<?= $e($ghUser) ?>" placeholder="your-username" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="60" aria-label="GitHub username">
                  </div>
                  <button type="submit" id="btnProfileUpdateSubmit" class="pf-btn pf-btn-primary">Save</button>
                  <?php if ($ghState === 'ok' || $ghState === 'missing'): ?><button type="button" class="pf-btn pf-btn-ghost" onclick="toggleGhEdit(false)">Cancel</button><?php endif; ?>
                </div>
                <div id="profileUpdateAlert" class="pf-msg" role="status" aria-live="polite"></div>
              </form>
<?php     $ghForm = ob_get_clean(); ?>
        <section class="pf-card" id="github">
          <header class="pf-card-head">
            <h2 class="pf-card-title"><i class="bi bi-github"></i> GitHub repository</h2>
            <?php if ($ghState === 'ok'): ?><span class="pf-status is-on">Connected</span>
            <?php elseif ($ghState === 'missing'): ?><span class="pf-status">Repository not found</span>
            <?php else: ?><span class="pf-status">Not set up</span><?php endif; ?>
          </header>

<?php if ($ghState === 'ok' || $ghState === 'missing'): ?>
          <div id="ghView">
            <?php if ($ghState === 'ok'): ?>
            <p class="gh-lead">Your reports are saved to this repository.</p>
            <?php else: ?>
            <p class="gh-lead">We couldn't find this repository on GitHub. Create it as a <strong>public</strong> repository named <code class="gh-name"><?= KRAZE_REPORTS_REPO ?></code>, then refresh this page.</p>
            <?php endif; ?>
            <div class="gh-repo">
              <a class="gh-repo-link" href="<?= $e(krazeReportsRepoUrl($ghUser)) ?>" target="_blank" rel="noopener"><i class="bi bi-journal-code"></i> github.com/<?= $e($ghUser) ?>/<?= KRAZE_REPORTS_REPO ?></a>
              <div class="gh-repo-actions">
                <?php if ($ghState === 'missing'): ?>
                <a class="pf-btn pf-btn-primary" href="<?= $e($newRepoUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-plus-lg"></i> Create on GitHub</a>
                <?php else: ?>
                <a class="pf-btn pf-btn-ghost" href="<?= $e(krazeReportsRepoUrl($ghUser)) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                <a class="pf-btn pf-btn-ghost" href="<?= $e(krazeReadmeUrl($ghUser, $myName)) ?>" target="_blank" rel="noopener" title="Create README.md with your live progress card, ready to commit"><i class="bi bi-file-earmark-plus"></i> Create README</a>
                <?php endif; ?>
                <button type="button" class="pf-btn pf-btn-ghost" onclick="toggleGhEdit(true)"><i class="bi bi-pencil"></i> Change</button>
              </div>
            </div>
            <?php if ($ghState === 'ok' && ($_GET['gh'] ?? '') === 'saved'): ?>
            <div class="pf-msg ok gh-flash">Saved. Write Report now opens this repository.</div>
            <?php endif; ?>
          </div>
          <div id="ghEdit" hidden>
            <p class="gh-lead">Enter your GitHub username.</p>
            <?= $ghForm ?>
          </div>
<?php else: ?>
          <ol class="gh-steps">
            <li>
              <div class="gh-step-text">Create a <strong>public</strong> repository named <code class="gh-name"><?= KRAZE_REPORTS_REPO ?></code>.</div>
              <a class="pf-btn pf-btn-ghost gh-create" href="<?= $e($newRepoUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-plus-lg"></i> Create on GitHub</a>
            </li>
            <li>
              <div class="gh-step-text">Enter your GitHub username.</div>
              <?= $ghForm ?>
              <?php if ($legacyRepo !== ''): ?>
              <div class="pf-msg warn">You're using <strong><?= $e(preg_replace('#^https://#', '', $legacyRepo)) ?></strong>. Reports now go to a repository named <?= KRAZE_REPORTS_REPO ?>: create it, then click Save.</div>
              <?php endif; ?>
            </li>
          </ol>
<?php endif; ?>
        </section>

        <section class="pf-card">
          <header class="pf-card-head">
            <div>
              <h2 class="pf-card-title">Password</h2>
              <p class="pf-card-desc">Change the password you use to sign in to KrazePlanet.</p>
            </div>
            <a class="pf-btn pf-btn-ghost" href="<?= $e($accountPageUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-key"></i> Change password</a>
          </header>
        </section>
<?php endif; ?>

      </div>
    </div>
  </div>
<?php endif; ?>
</div>

<?php if ($tab === 'settings' && $account): ?>
<script>
// Arriving from a lab's "Write Report" (profile.php?tab=settings#github): focus the GitHub field.
// Runs after load so the browser's own jump to #github doesn't override it.
window.addEventListener('load', () => setTimeout(() => {
  if (location.hash !== '#github') return;
  const gh = document.getElementById('profileGithubRepo');
  if (!gh) return;
  if (!gh.offsetParent) { document.getElementById('github').scrollIntoView({ block: 'center' }); return; } // already set up: just show the card
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

// Pasting a full GitHub link works too: only the username is kept.
function ghUsernameFrom(value) {
  return value.trim().replace(/^@/, '').replace(/^(https?:\/\/)?(www\.)?github\.com\//i, '').split('/')[0];
}
document.getElementById('profileGithubRepo')?.addEventListener('paste', (e) => {
  setTimeout(() => { e.target.value = ghUsernameFrom(e.target.value); }, 0);
});

// "Saved" note: fades out after a few seconds, and is taken out of the address
// so a refresh doesn't show it again.
(function () {
  const flash = document.querySelector('.gh-flash');
  if (!flash) return;
  history.replaceState(null, '', 'profile.php?tab=settings' + location.hash);
  setTimeout(() => {
    flash.style.transition = 'opacity .4s ease';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 400);
  }, 4000);
})();

// "Change" swaps the repository view for the username box (and back on Cancel)
function toggleGhEdit(editing) {
  const view = document.getElementById('ghView');
  const edit = document.getElementById('ghEdit');
  if (!view || !edit) return;
  view.hidden = editing;
  edit.hidden = !editing;
  if (editing) document.getElementById('profileGithubRepo').focus();
}

// Saves the GitHub username (the only setting a student changes here)
function handleProfileUpdate(e) {
  e.preventDefault();
  const btn = document.getElementById('btnProfileUpdateSubmit');
  const msg = document.getElementById('profileUpdateAlert');
  const ghInput = document.getElementById('profileGithubRepo');
  const say = (text, kind) => { msg.className = 'pf-msg ' + kind; msg.textContent = text; };
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const formData = new FormData();
  formData.append('action', 'update_github_repo');
  formData.append('github_username', ghUsernameFrom(ghInput.value));

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Save';
      if (data.success) {
        // Reload so the card shows the right state: connected, or "repository not found"
        location.replace('profile.php?tab=settings&gh=saved&t=' + Date.now() + '#github');
      } else {
        say(data.error || 'Could not save. Please try again.', 'err');
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'Save';
      say('Network error. Please try again.', 'err');
    });
}
</script>
<?php endif; ?>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
