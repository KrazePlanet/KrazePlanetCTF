<?php
// open_mailbox.php - Fast Provisioner & Gateway to User's Mailpit Container
require_once __DIR__ . '/../config/domain.php';
startKrazeSession();

$h = strtolower($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = strtolower(preg_replace('/:\d+$/', '', $h));
$domain = getKrazeBaseDomain($hostNoPort);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || 
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$proto = $isHttps ? 'https://' : 'http://';

// If logged in, use their username; if not, use 'newuser'
$rawUser = $_SESSION['username'] ?? 'newuser';
$cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($rawUser));
if (empty($cleanUser)) $cleanUser = 'newuser';

$targetUrl = "{$proto}{$cleanUser}-mailpit.{$domain}";
$containerName = "kp_{$cleanUser}_mailpit";

$isRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
if ($isRunning !== 'true') {
    @chmod('/var/run/docker.sock', 0666);
    shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
    shell_exec("docker run -d --name {$containerName} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1");
    usleep(250000);
}

header("Location: {$targetUrl}");
exit;