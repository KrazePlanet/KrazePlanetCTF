<?php
// config/sso.php - Central login client.
// Every account and all lab progress live on the central login server
// (login.krazeplanetsecurity.com), shared by all KrazePlanet lab sites. This
// site keeps no user database: only a session holding the student's access
// token, which it sends with each API call.
//
// Environment (deploy/.env):
//   SSO_URL            https://login.krazeplanetsecurity.com  (what browsers open)
//   SSO_API_URL        same as SSO_URL; differs only when testing locally in Docker
//   SSO_CLIENT_ID      this site's id, e.g. pwnedbox.site
//   SSO_CLIENT_SECRET  from `php bin/site.php add` on the central server

require_once __DIR__ . '/domain.php';

function krazeSsoUrl() {
    return rtrim(getenv('SSO_URL') ?: 'https://login.krazeplanetsecurity.com', '/');
}

function krazeSsoAccountUrl() {
    return krazeSsoUrl() . '/account.php';
}

function krazeLoggedIn() {
    return !empty($_SESSION['sso_token']);
}

function krazeIsAdmin() {
    return krazeLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function krazeSetSessionUser(array $user) {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['avatar']     = $user['avatar'];
    $_SESSION['role']       = $user['role'];
}

function krazeLocalLogout() {
    foreach (['sso_token', 'user_id', 'username', 'user_email', 'avatar', 'role', 'notif_cache'] as $k) {
        unset($_SESSION[$k]);
    }
}

// Calls the central API. Returns the decoded JSON; on failure returns
// ['success' => false, 'error' => ...]. If the central server says the
// student's token is no longer valid (password changed, signed out
// everywhere, expired), the student is signed out here too.
function krazeSsoCall($action, array $data = [], $asUser = true) {
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    $sentToken = $asUser && krazeLoggedIn();
    if ($sentToken) {
        $headers[] = 'X-KP-User-Token: ' . $_SESSION['sso_token'];
    }
    $ch = curl_init(rtrim(getenv('SSO_API_URL') ?: krazeSsoUrl(), '/') . '/api.php');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['action' => $action] + $data),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_USERPWD        => (getenv('SSO_CLIENT_ID') ?: '') . ':' . (getenv('SSO_CLIENT_SECRET') ?: ''),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $res = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($res)) {
        error_log("SSO call '$action' failed: HTTP $status");
        return ['success' => false, 'error' => 'The KrazePlanet login server is unreachable right now. Please try again in a minute.'];
    }
    if ($status === 401 && $sentToken) {
        krazeLocalLogout();
    }
    return $res;
}

// Where to send the browser to sign in. $return must pass krazeSafeReturn().
function krazeLoginUrl($return = '', $screen = '') {
    $q = ['return' => $return !== '' ? $return : ($_SERVER['REQUEST_URI'] ?? '/')];
    if ($screen !== '') $q['screen'] = $screen;
    return '/sso/login.php?' . http_build_query($q);
}

// After login, only go back to a page on this site or one of its lab subdomains.
function krazeSafeReturn($target) {
    $target = (string)$target;
    if (preg_match('#^/(?![/\\\\])[^\s]*$#', $target)) return $target;
    $p = parse_url($target);
    $host = strtolower($p['host'] ?? '');
    $self = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $labBase = strtolower(getenv('LAB_BASE_DOMAIN') ?: '');
    $okHost = $host !== '' && ($host === $self || ($labBase !== '' && ($host === $labBase || substr($host, -strlen($labBase) - 1) === '.' . $labBase)));
    if ($okHost && in_array($p['scheme'] ?? '', ['http', 'https'], true)) return $target;
    return '/';
}

// Latest notifications for the navbar bell, cached for a minute so every page
// view doesn't wait on the central server.
function krazeRecentNotifications() {
    $cache = $_SESSION['notif_cache'] ?? null;
    if ($cache && $cache['at'] > time() - 60) return $cache['items'];
    $res = krazeSsoCall('notifications', ['limit' => 5]);
    $items = !empty($res['success']) ? $res['notifications'] : [];
    if (krazeLoggedIn()) $_SESSION['notif_cache'] = ['at' => time(), 'items' => $items];
    return $items;
}
