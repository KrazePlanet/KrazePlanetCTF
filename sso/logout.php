<?php
// Signs out here and on the central login server, then comes back to this
// site's home page.
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();

if (krazeLoggedIn()) {
    krazeSsoCall('logout');
}
krazeLocalLogout();
$_SESSION = [];
session_destroy();

header('Location: ' . krazeSsoUrl() . '/logout.php?client_id=' . urlencode(getenv('SSO_CLIENT_ID') ?: ''));
