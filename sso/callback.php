<?php
// The central login server sends the browser back here after sign-in:
//   /sso/callback.php?code=...&state=...
// The one-time code is swapped (server to server) for the student's token.
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();

function ssoFail($msg) {
    http_response_code(400);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign-in failed</title>'
       . '<body style="background:#070b14;color:#e2e8f0;font-family:sans-serif;padding:40px">'
       . '<h2>Sign-in failed</h2><p>' . htmlspecialchars($msg) . '</p><p><a style="color:#38bdf8" href="/sso/login.php">Try again</a></p></body>';
    exit;
}

$state = (string)($_GET['state'] ?? '');
$pending = $_SESSION['sso_states'][$state] ?? null;
unset($_SESSION['sso_states'][$state]);
if (!$pending || $pending['time'] < time() - 600) {
    ssoFail('This sign-in link has expired or was opened in a different browser.');
}

$res = krazeSsoCall('token', ['code' => (string)($_GET['code'] ?? '')], false);
if (empty($res['success'])) {
    ssoFail($res['error'] ?? 'The login server rejected the request.');
}

session_regenerate_id(true);
$_SESSION['sso_token'] = $res['access_token'];
krazeSetSessionUser($res['user']);
header('Location: ' . krazeSafeReturn($pending['return']));
