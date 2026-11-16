<?php
// api/card.php - A live progress card (SVG image) for a student's GitHub README:
//   <img src="https://<site>/api/card.php?user=NAME">
// Drawn from the student's real numbers every time it's requested (GitHub re-fetches
// it every few minutes), so the README always shows their current progress.
// Public data only: the same numbers as their profile and the scoreboard.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=300'); // GitHub refreshes it about every 5 minutes
header('X-Content-Type-Options: nosniff');

$x = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1);
$user = mb_substr(trim((string)($_GET['user'] ?? '')), 0, 255);
$res = $user !== '' ? krazeSsoCall('player', ['username' => $user], false) : null;
$p = !empty($res['success']) ? $res['player'] : null;

// Labs on this site, per difficulty, and how many of them the student solved
$catalog = krazeLabCatalog();
$levels = ['easy' => ['Easy', '#34d399'], 'medium' => ['Medium', '#fbbf24'], 'hard' => ['Hard', '#f87171'], 'secure' => ['Secure', '#7dd3fc']];
$totals = array_fill_keys(array_keys($levels), 0);
foreach ($catalog['byId'] as $lab) if (isset($totals[$lab['difficulty']])) $totals[$lab['difficulty']]++;
$labTotal = count($catalog['byId']);
$done = array_fill_keys(array_keys($levels), 0);
$solvedHere = 0;
foreach ($p['solved'] ?? [] as $s) {
    if (!isset($catalog['byId'][$s['lab_id']])) continue; // a lab on another KrazePlanet site
    $d = $catalog['byId'][$s['lab_id']]['difficulty'];
    if (isset($done[$d])) $done[$d]++;
    $solvedHere++;
}
$pct = $labTotal ? (int)floor($solvedHere * 100 / $labTotal) : 0;
$ring = $labTotal ? round($solvedHere * 100 / $labTotal, 2) : 0;

$solvedAll = count($p['solved'] ?? []);
$title = 'Trainee'; // same titles as the profile page
if ($solvedAll >= 100) $title = 'CTF Grandmaster';
elseif ($solvedAll >= 50) $title = 'Cyber Security Elite';
elseif ($solvedAll >= 20) $title = 'Pro Hunter';
elseif ($solvedAll >= 5) $title = 'Apprentice Hunter';

$site = (string)parse_url(krazeSiteUrl('/'), PHP_URL_HOST);
$font = "font-family=\"'Segoe UI', Ubuntu, 'Helvetica Neue', Helvetica, Arial, sans-serif\"";

if (!$p) {
    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="880" height="120" viewBox="0 0 880 120" role="img" aria-label="KrazePlanet student not found">
  <rect x="0.5" y="0.5" width="879" height="119" rx="16" fill="#0b1324" stroke="#1f2a40"/>
  <text x="32" y="54" {$font} font-size="13" font-weight="700" letter-spacing="1.5" fill="#7dd3fc">KRAZEPLANET LABS</text>
  <text x="32" y="84" {$font} font-size="18" fill="#94a3b8">Student not found</text>
</svg>
SVG;
    exit;
}

$name = $x($p['username']);
$rank = $p['rank'] ? '#' . (int)$p['rank'] : 'Unranked';
$points = number_format((int)$p['points']);
$streak = (int)$p['streak'] . ((int)$p['streak'] === 1 ? ' day' : ' days');

// Difficulty bars on the right
$bars = '';
$y = 74;
foreach ($levels as $k => [$label, $color]) {
    $w = $totals[$k] ? round(248 * $done[$k] / $totals[$k], 1) : 0;
    $bars .= "<text x=\"600\" y=\"{$y}\" {$font} font-size=\"15\" font-weight=\"600\" fill=\"{$color}\">{$label}</text>"
           . "<text x=\"848\" y=\"{$y}\" {$font} font-size=\"15\" text-anchor=\"end\" fill=\"#94a3b8\"><tspan font-weight=\"700\" fill=\"#ffffff\">{$done[$k]}</tspan> / {$totals[$k]}</text>"
           . '<rect x="600" y="' . ($y + 10) . '" width="248" height="8" rx="4" fill="#1e293b"/>'
           . ($w > 0 ? '<rect x="600" y="' . ($y + 10) . "\" width=\"{$w}\" height=\"8\" rx=\"4\" fill=\"{$color}\"/>" : '');
    $y += 50;
}

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="880" height="300" viewBox="0 0 880 300" role="img" aria-label="{$name} on KrazePlanet Labs: {$solvedHere} of {$labTotal} labs solved, {$points} points, rank {$rank}">
  <defs>
    <linearGradient id="kpRing" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#38bdf8"/>
      <stop offset="100%" stop-color="#34d399"/>
    </linearGradient>
  </defs>
  <rect x="0.5" y="0.5" width="879" height="299" rx="16" fill="#0b1324" stroke="#1f2a40"/>

  <!-- Who -->
  <text x="32" y="48" {$font} font-size="12" font-weight="700" letter-spacing="1.5" fill="#7dd3fc">KRAZEPLANET LABS</text>
  <text x="32" y="86" {$font} font-size="30" font-weight="800" fill="#ffffff">{$name}</text>
  <text x="32" y="114" {$font} font-size="15" fill="#94a3b8">{$x($title)}</text>

  <!-- Key numbers -->
  <text x="32" y="168" {$font} font-size="13" fill="#64748b">Rank</text>
  <text x="32" y="196" {$font} font-size="24" font-weight="700" fill="#fbbf24">{$rank}</text>
  <text x="152" y="168" {$font} font-size="13" fill="#64748b">Points</text>
  <text x="152" y="196" {$font} font-size="24" font-weight="700" fill="#38bdf8">{$points}</text>
  <text x="272" y="168" {$font} font-size="13" fill="#64748b">Streak</text>
  <text x="272" y="196" {$font} font-size="24" font-weight="700" fill="#ffffff">{$streak}</text>
  <text x="32" y="262" {$font} font-size="13" fill="#64748b">Free, hands-on web security labs · {$x($site)}</text>

  <!-- Progress ring: labs solved on this site -->
  <circle cx="470" cy="150" r="70" fill="none" stroke="#1e293b" stroke-width="12"/>
  <circle cx="470" cy="150" r="70" fill="none" stroke="url(#kpRing)" stroke-width="12" stroke-linecap="round"
          pathLength="100" stroke-dasharray="{$ring} 100" transform="rotate(-90 470 150)"/>
  <text x="470" y="152" {$font} font-size="34" font-weight="800" fill="#ffffff" text-anchor="middle">{$solvedHere}</text>
  <text x="470" y="174" {$font} font-size="13" fill="#94a3b8" text-anchor="middle">of {$labTotal}</text>
  <text x="470" y="194" {$font} font-size="13" font-weight="700" fill="#38bdf8" text-anchor="middle">{$pct}% solved</text>

  <!-- Per difficulty -->
  {$bars}
</svg>
SVG;
