<?php
// Sends the browser to the central login server.
//   /sso/login.php?return=/navbar/profile.php[&screen=signup|forgot]
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();

$return = krazeSafeReturn($_GET['return'] ?? '/');
if (krazeLoggedIn()) {
    header('Location: ' . $return);
    exit;
}

// Remember a few in-flight logins (one per tab), each valid for 10 minutes.
$state = bin2hex(random_bytes(24));
$states = array_filter($_SESSION['sso_states'] ?? [], fn($s) => $s['time'] > time() - 600);
$states[$state] = ['return' => $return, 'time' => time()];
$_SESSION['sso_states'] = array_slice($states, -5, null, true);

$screen = in_array($_GET['screen'] ?? '', ['signup', 'forgot'], true) ? $_GET['screen'] : 'login';
header('Location: ' . krazeSsoUrl() . '/authorize.php?' . http_build_query([
    'client_id' => getenv('SSO_CLIENT_ID') ?: '',
    'state'     => $state,
    'screen'    => $screen,
]));
