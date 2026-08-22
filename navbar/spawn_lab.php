<?php
// spawn_lab.php - Self-Healing Gateway & On-Demand Auto-Provisioner for Lab Subdomains
if (session_status() === PHP_SESSION_NONE) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'kzlabs.in') !== false) {
        @ini_set('session.cookie_domain', '.kzlabs.in');
    } elseif (strpos($host, 'localtest.me') !== false) {
        @ini_set('session.cookie_domain', '.localtest.me');
    } elseif (strpos($host, 'localhost') !== false) {
        @ini_set('session.cookie_domain', '.localhost');
    }
    @session_start();
}

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = preg_replace('/:\d+$/', '', $httpHost);

$baseDomain = 'localhost';
if (strpos($hostNoPort, 'kzlabs.in') !== false) $baseDomain = 'kzlabs.in';
elseif (strpos($hostNoPort, 'localtest.me') !== false) $baseDomain = 'localtest.me';

$isInstance = false;
$parsedUser = '';
$parsedLab = '';

if (preg_match('/^([a-zA-Z0-9_]+)-([a-zA-Z0-9_\-]+)\.(kzlabs\.in|localhost|localtest\.me|127\.0\.0\.1\.nip\.io|nip\.io)$/i', $hostNoPort, $m)) {
    $parsedUser = strtolower($m[1]);
    $parsedLab = strtolower($m[2]);
    $isInstance = true;
} elseif (preg_match('/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_\-]+)\.(kzlabs\.in|localhost|localtest\.me|127\.0\.0\.1\.nip\.io|nip\.io)$/i', $hostNoPort, $m)) {
    $parsedUser = strtolower($m[1]);
    $parsedLab = strtolower($m[2]);
    $isInstance = true;
}

if (!$isInstance || $parsedLab === 'mailpit' || $parsedLab === 'mail') {
    require_once __DIR__ . '/spawn_mailpit.php';
    exit;
}

// Database Connection
$pdo = null;
try {
    $pdo = new PDO("mysql:host=localhost;dbname=KrazePlanet;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {}

// Verify if user exists in KrazePlanet
$userExists = false;
$userId = 0;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmt->execute([$parsedUser]);
    $u = $stmt->fetch();
    if ($u) {
        $userExists = true;
        $userId = $u['id'];
    }
}

if (!$userExists) {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>404 - Lab Not Found</title>
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
        <h2>⚠️ Lab Not Found</h2>
        <p>The user <strong>' . htmlspecialchars($parsedUser) . '</strong> does not exist in KrazePlanet.</p>
        <a href="//' . htmlspecialchars($baseDomain) . '">← Return to Home</a>
      </div>
    </body>
    </html>';
    exit;
}

// Locate template folder
$candidatePaths = [
    "/opt/lampp/htdocs/subdomains/{$parsedLab}",
    "/opt/lampp/htdocs/{$parsedLab}",
];
$templateDir = null;
foreach ($candidatePaths as $cand) {
    if (is_dir($cand)) {
        $templateDir = $cand;
        break;
    }
}

if (!$templateDir) {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>404 - Lab Template Not Found</title>
      <style>
        body { background: #0b1120; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: #0f172a; border: 1px solid rgba(239, 68, 68, 0.4); padding: 32px 40px; border-radius: 14px; text-align: center; max-width: 450px; }
        h2 { color: #f87171; }
        p { color: #94a3b8; }
        a { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); padding: 8px 18px; border-radius: 8px; text-decoration: none; }
      </style>
    </head>
    <body>
      <div class="box">
        <h2>⚠️ Lab Not Found</h2>
        <p>Challenge template <strong>' . htmlspecialchars($parsedLab) . '</strong> is not available.</p>
        <a href="//' . htmlspecialchars($baseDomain) . '">← Return to Home</a>
      </div>
    </body>
    </html>';
    exit;
}

// Provision and Launch Container On-Demand
$containerName = "kp_{$parsedUser}_{$parsedLab}";
$instanceFolder = "/opt/lampp/htdocs/instances/{$parsedUser}_{$parsedLab}";

@mkdir('/opt/lampp/htdocs/instances', 0777, true);
if (!is_dir($instanceFolder)) {
    @mkdir($instanceFolder, 0777, true);
    shell_exec("cp -r " . escapeshellarg($templateDir) . "/. " . escapeshellarg($instanceFolder) . "/");
    @chmod($instanceFolder, 0777);
    shell_exec("chmod -R 777 " . escapeshellarg($instanceFolder));
}

$checkRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
if ($checkRunning !== 'true') {
    @chmod('/var/run/docker.sock', 0666);
    shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
    
    $startScript = "cp -a " . escapeshellarg($instanceFolder) . "/. /var/www/html/ && chmod -R 777 /var/www/html && apache2-foreground";
    $dockerCmd = sprintf(
        "docker run -d --name %s --network htdocs_default --volumes-from krazeplanet:rw -v /var/run/mysqld:/var/run/mysqld:rw --memory=128m --cpus=0.5 --pids-limit=100 --restart=no rix4uni/krazeplanet:lab-runtime bash -c %s 2>&1",
        escapeshellarg($containerName),
        escapeshellarg($startScript)
    );
    shell_exec($dockerCmd);
    usleep(600000);
}

// Render self-reloading screen
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Launching Lab Instance...</title>
  <style>
    body {
      background: #0b1120;
      color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }
    .card {
      background: #0f172a;
      border: 1px solid rgba(56, 189, 248, 0.3);
      padding: 36px 44px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      max-width: 420px;
    }
    .spinner {
      width: 44px;
      height: 44px;
      border: 4px solid rgba(56, 189, 248, 0.2);
      border-top-color: #38bdf8;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    h2 { font-size: 20px; margin: 0 0 10px; color: #38bdf8; }
    p { font-size: 13px; color: #94a3b8; line-height: 1.5; margin: 0; }
  </style>
</head>
<body>
  <div class="card">
    <div class="spinner"></div>
    <h2>🪐 Initializing Sandbox...</h2>
    <p>Starting dedicated container for <strong><?= htmlspecialchars($parsedLab) ?></strong>. Connecting you now...</p>
  </div>
  <script>
    setTimeout(function() {
      window.location.reload();
    }, 1000);
  </script>
</body>
</html>
