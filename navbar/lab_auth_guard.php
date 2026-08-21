<?php
// lab_auth_guard.php - Global Authentication & Access Protection Guard for KrazePlanet Labs

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
$script = $_SERVER['SCRIPT_NAME'] ?? '';

// Public root files & paths that do NOT require login
$public_paths = [
    '/',
    '/index.php',
    '/auth_api.php',
    '/api/auth_api.php',
    '/instance_api.php',
    '/api/instance_api.php',
    '/cleanup_daemon.php',
    '/scripts/cleanup_daemon.php',
    '/lab_banner.php',
    '/navbar/lab_banner.php',
    '/about.php',
    '/navbar/about.php',
    '/contact.php',
    '/navbar/contact.php',
    '/leaderboard.php',
    '/navbar/leaderboard.php',
    '/ctf.php',
    '/navbar/ctf.php',
    '/portal.php',
    '/navbar/portal.php',
    '/profile.php',
    '/navbar/profile.php',
    '/settings.php',
    '/navbar/settings.php',
    '/assignments.php',
    '/navbar/assignments.php'
];

$parsed_path = parse_url($uri, PHP_URL_PATH);
$path_lower = rtrim(strtolower($parsed_path), '/');

// 1. Check if root public page
$is_public = false;
foreach ($public_paths as $p) {
    if ($path_lower === rtrim(strtolower($p), '/')) {
        $is_public = true;
        break;
    }
}

// 2. Allow HackerOneReport directory or phpmyadmin
if (strpos($path_lower, '/hackeronereport') === 0 || strpos($path_lower, '/phpmyadmin') === 0 || strpos($path_lower, '/phpmailer') === 0) {
    $is_public = true;
}

// 3. Allow static asset files (.css, .js, .png, etc.)
if (!$is_public) {
    $ext = pathinfo($path_lower, PATHINFO_EXTENSION);
    if (in_array($ext, ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'webp', 'map', 'mp4', 'webm', 'json'])) {
        $is_public = true;
    }
}

// 4. If request is for a protected lab folder / lab script
if (!$is_public) {
    $isLoggedIn = !empty($_SESSION['user_id']);

    if (!$isLoggedIn) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Authentication required',
                'message' => 'Please sign in to access this lab.',
                'redirect' => '/index.php?modal=login'
            ]);
            exit;
        }

        $redirectUrl = '/index.php?modal=login&redirect=' . urlencode($uri);
        header("Location: $redirectUrl", true, 302);
        exit;
    }

    // 5. If logged-in user requests /subdomains/<lab_name> directly on localhost or kzlabs.in, auto-redirect to isolated microcontainer subdomain
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $hostNoPort = explode(':', $host)[0];

    // If request is on the main portal host (not on a user sandbox subdomain)
    if (in_array($hostNoPort, ['localhost', '127.0.0.1', 'kzlabs.in', 'www.kzlabs.in'])) {
        if (preg_match('#^/subdomains/([a-zA-Z0-9_\-\.]+)(/.*)?$#i', $path_lower, $matches)) {
            $labSlug = trim($matches[1], '/');
            $rawUsername = $_SESSION['username'] ?? 'user';
            $cleanUser = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($rawUsername));
            $cleanLab = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($labSlug));

            $baseDomain = ($hostNoPort === 'kzlabs.in' || $hostNoPort === 'www.kzlabs.in') ? 'kzlabs.in' : 'localhost';
            $targetSubdomain = "{$cleanUser}-{$cleanLab}.{$baseDomain}";
            
            // Auto-provision container if needed
            $containerName = "kp_{$cleanUser}_{$cleanLab}";
            $checkRun = trim(shell_exec("docker inspect -f '{{.State.Running}}' {$containerName} 2>/dev/null") ?? '');
            if ($checkRun !== 'true') {
                // Call instance_api to provision container
                @file_get_contents("http://127.0.0.1/api/instance_api.php?action=launch_lab&lab_id=" . urlencode($labSlug), false, stream_context_create([
                    'http' => ['header' => "Cookie: " . session_name() . "=" . session_id() . "\r\n"]
                ]));
            }

            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $remainder = $matches[2] ?? '';
            header("Location: {$proto}{$targetSubdomain}{$remainder}", true, 302);
            exit;
        }
    }
}


// Automatically inject PortSwigger-style lab banner on instance subdomains
if (file_exists(__DIR__ . '/lab_banner.php')) {
    include_once __DIR__ . '/lab_banner.php';
}
