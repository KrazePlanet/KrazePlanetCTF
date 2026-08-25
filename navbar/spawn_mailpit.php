<?php
// spawn_mailpit.php - Strict Security & Auto-Launcher for User Mailpit Containers
require_once __DIR__ . '/../config/domain.php';
startKrazeSession();

require_once __DIR__ . '/../config/db.php';

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$hostNoPort = preg_replace('/:\d+$/', '', strtolower($httpHost));
$baseDomain = getKrazeBaseDomain($hostNoPort);
$mailUser = '';

if (preg_match('/^([a-zA-Z0-9_]+)-mailpit\.(.+)$/i', $hostNoPort, $m)) {
    $mailUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($m[1]));
}

if (empty($mailUser)) {
    http_response_code(400);
    die("Invalid mailbox subdomain.");
}

// 1. If 'newuser', allow onboarding mailbox
if ($mailUser === 'newuser') {
    $containerName = "kp_newuser_mailpit";
    $isRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
    if ($isRunning !== 'true') {
        @chmod('/var/run/docker.sock', 0666);
        shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
        shell_exec("docker run -d --name {$containerName} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1");
        usleep(300000);
    }
} else {
    // 2. Validate user exists in database
    if (!$pdo) {
        http_response_code(500);
        die("Database connection error.");
    }
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmt->execute([$mailUser]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>404 - Mailbox Not Found</title>
          <style>
            body { background: #0b1120; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .box { background: #0f172a; border: 1px solid rgba(239, 68, 68, 0.4); padding: 32px 40px; border-radius: 14px; text-align: center; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
            h2 { color: #f87171; margin-top: 0; font-size: 22px; }
            p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
            a { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); padding: 8px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; }
          </style>
        </head>
        <body>
          <div class="box">
            <h2>⚠️ Mailbox Not Found</h2>
            <p>The user <strong><?php echo htmlspecialchars($mailUser); ?></strong> does not exist in KrazePlanet.</p>
            <a href="//<?php echo htmlspecialchars($baseDomain); ?>">← Return to Home</a>
          </div>
        </body>
        </html>
        <?php
        exit;
    }

    // 3. Check Authentication & Permissions
    $sessionUser = $_SESSION['username'] ?? '';
    $sessionRole = $_SESSION['role'] ?? '';
    if (empty($sessionUser) || (strtolower($sessionUser) !== strtolower($mailUser) && $sessionRole !== 'admin')) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        header("Location: {$proto}{$baseDomain}/index.php?modal=login");
        exit;
    }

    $containerName = "kp_{$mailUser}_mailpit";
    $isRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
    if ($isRunning !== 'true') {
        @chmod('/var/run/docker.sock', 0666);
        shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
        shell_exec("docker run -d --name {$containerName} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1");
        usleep(300000);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="1">
  <title>Opening Mailpit Inbox...</title>
  <style>
    body { background: #0b1120; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
    .card { background: #0f172a; border: 1px solid rgba(56, 189, 248, 0.3); padding: 32px 40px; border-radius: 16px; text-align: center; max-width: 420px; box-shadow: 0 20px 50px rgba(0,0,0,0.7); }
    .spinner { width: 40px; height: 40px; border: 3px solid rgba(56, 189, 248, 0.2); border-top-color: #38bdf8; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    h2 { font-size: 20px; margin: 0 0 8px; color: #ffffff; }
    p { font-size: 14px; color: #94a3b8; margin: 0; }
  </style>
</head>
<body>
  <div class="card">
    <div class="spinner"></div>
    <h2>Launching Mailbox</h2>
    <p>Opening private Mailpit inbox for <strong><?php echo htmlspecialchars($mailUser); ?></strong>...</p>
  </div>
  <script>
    setTimeout(function() { window.location.reload(); }, 800);
  </script>
</body>
</html>