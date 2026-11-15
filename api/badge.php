<?php
// api/badge.php - Live badges for a student's GitHub README (KrazePlanet-Reports).
// Returns shields.io "endpoint" JSON, so a README can show:
//   https://img.shields.io/endpoint?url=<site>/api/badge.php?user=NAME&type=points
// type: points | solved | rank. Public data only (the same numbers as the scoreboard).
require_once __DIR__ . '/../config/sso.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');

$user = mb_substr(trim((string)($_GET['user'] ?? '')), 0, 255);
$type = (string)($_GET['type'] ?? 'points');
$labels = ['points' => 'points', 'solved' => 'labs solved', 'rank' => 'rank'];
if (!isset($labels[$type])) $type = 'points';

$badge = ['schemaVersion' => 1, 'label' => $labels[$type], 'message' => '-', 'color' => '64748b', 'cacheSeconds' => 300];

$res = $user !== '' ? krazeSsoCall('player', ['username' => $user], false) : null;
if (!empty($res['success'])) {
    $p = $res['player'];
    if ($type === 'points') {
        $badge['message'] = number_format((int)$p['points']);
        $badge['color'] = '38bdf8';
    } elseif ($type === 'solved') {
        $badge['message'] = (string)count($p['solved']);
        $badge['color'] = '34d399';
    } else {
        $badge['message'] = $p['rank'] ? '#' . (int)$p['rank'] : 'unranked';
        $badge['color'] = 'fbbf24';
    }
}
echo json_encode($badge);
