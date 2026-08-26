<?php
// spawn_lab.php - Self-Healing Gateway & On-Demand Auto-Provisioner for Lab Subdomains
require_once __DIR__ . '/../config/domain.php';
startKrazeSession();

require_once __DIR__ . '/../config/db.php';

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = preg_replace('/:\d+$/', '', $httpHost);
$baseDomain = getKrazeBaseDomain($hostNoPort);

$isInstance = false;
$parsedUser = '';
$parsedLab = '';

// Match hyphenated {username}-{lab}.{domain} or nested {username}.{lab}.{domain}
if (preg_match('/^([a-zA-Z0-9_]+)-([a-zA-Z0-9_\-]+)\.(.+)$/i', $hostNoPort, $m)) {
    $parsedUser = strtolower($m[1]);
    $parsedLab = strtolower($m[2]);
    $isInstance = true;
} elseif (preg_match('/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_\-]+)\.(.+)$/i', $hostNoPort, $m)) {
    $parsedUser = strtolower($m[1]);
    $parsedLab = strtolower($m[2]);
    $isInstance = true;
}

if (!$isInstance || $parsedLab === 'mailpit' || $parsedLab === 'mail') {
    require_once __DIR__ . '/spawn_mailpit.php';
    exit;
}

// Helpers
function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir) ?: [], ['.', '..']);
    foreach ($files as $file) {
        $p = "$dir/$file";
        (is_dir($p)) ? recursiveDelete($p) : @unlink($p);
    }
    @rmdir($dir);
}

function terminateUserOtherLabs($userId, $username, $keepLabId = null) {
    global $pdo;
    $cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username));

    // 1. Terminate other active records in DB for this user
    if ($pdo) {
        $query = "SELECT id, lab_id, instance_dir, db_name FROM lab_instances WHERE user_id = ? AND status = 'active'";
        $params = [$userId];
        if ($keepLabId !== null) {
            $query .= " AND lab_id != ?";
            $params[] = $keepLabId;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $oldList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($oldList as $oldInst) {
            $rawLab = preg_replace('#^subdomains/#i', '', strtolower($oldInst['lab_id']));
            $cleanLab = str_replace('/', '-', preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', $rawLab));
            $oldCont = "kp_{$cleanUser}_{$cleanLab}";
            shell_exec("docker rm -f " . escapeshellarg($oldCont) . " 2>/dev/null");
            if (!empty($oldInst['instance_dir']) && is_dir($oldInst['instance_dir'])) {
                recursiveDelete($oldInst['instance_dir']);
            }
            if (!empty($oldInst['db_name'])) {
                @$pdo->exec("DROP DATABASE IF EXISTS `{$oldInst['db_name']}`;");
            }
        }

        $updQuery = "UPDATE lab_instances SET status = 'destroyed' WHERE user_id = ?";
        $updParams = [$userId];
        if ($keepLabId !== null) {
            $updQuery .= " AND lab_id != ?";
            $updParams[] = $keepLabId;
        }
        $pdo->prepare($updQuery)->execute($updParams);
    }

    // 2. Sweep filesystem: remove any lingering folders for this user in /opt/lampp/htdocs/instances/
    $currCleanLab = $keepLabId ? str_replace('/', '-', preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', strtolower($keepLabId))) : '';
    $userDirs = glob("/opt/lampp/htdocs/instances/{$cleanUser}_*");
    if ($userDirs) {
        foreach ($userDirs as $uDir) {
            $baseDirName = basename($uDir);
            $labPart = substr($baseDirName, strlen($cleanUser) + 1);
            if ($labPart === 'mailpit' || $labPart === 'mail' || ($currCleanLab && $labPart === $currCleanLab)) {
                continue;
            }
            $lingeringCont = "kp_{$cleanUser}_{$labPart}";
            shell_exec("docker rm -f " . escapeshellarg($lingeringCont) . " 2>/dev/null");
            recursiveDelete($uDir);
            if ($pdo) {
                $lingeringDb = "kp_{$cleanUser}_{$labPart}";
                @$pdo->exec("DROP DATABASE IF EXISTS `{$lingeringDb}`;");
            }
        }
    }
}

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

// STRICT 1-LAB-PER-USER: Terminate all other active lab containers and folders for this user
terminateUserOtherLabs($userId, $parsedUser, $parsedLab);

$containerName = "kp_{$parsedUser}_{$parsedLab}";
$instanceFolder = "/opt/lampp/htdocs/instances/{$parsedUser}_{$parsedLab}";
$dbName = "kp_{$parsedUser}_{$parsedLab}";

@mkdir('/opt/lampp/htdocs/instances', 0777, true);
if (!is_dir($instanceFolder)) {
    @mkdir($instanceFolder, 0777, true);
    shell_exec("cp -r " . escapeshellarg($templateDir) . "/. " . escapeshellarg($instanceFolder) . "/");
    @chmod($instanceFolder, 0777);
    shell_exec("chmod -R 777 " . escapeshellarg($instanceFolder));

    // Discover SQL files and import
    $sqlFiles = glob("{$instanceFolder}/*.sql");
    $hasDb = false;
    if (!empty($sqlFiles) && $pdo) {
        $hasDb = true;
        @$pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`;");
        @$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        foreach ($sqlFiles as $sqlFile) {
            $sqlContent = file_get_contents($sqlFile);
            $sqlContent = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?[a-zA-Z0-9_]+`?/i', '', $sqlContent);
            $sqlContent = preg_replace('/USE\s+`?[a-zA-Z0-9_]+`?;?/i', '', $sqlContent);
            try {
                $pdoInstance = new PDO("mysql:host=127.0.0.1;dbname={$dbName};charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]);
                $pdoInstance->exec($sqlContent);
            } catch (Exception $e) {}
        }
    }
}

// Point localhost to krazeplanet container and SMTP host to mailpit
$phpFiles = glob("{$instanceFolder}/*.php");
$subPhpFiles = glob("{$instanceFolder}/*/*.php");
$allPhpFiles = array_merge($phpFiles ?: [], $subPhpFiles ?: []);
foreach ($allPhpFiles as $phpFile) {
    if (file_exists($phpFile)) {
        $code = file_get_contents($phpFile);
        $updatedCode = preg_replace("/(new\s+mysqli\s*\(\s*['\"])localhost(['\"])/i", "$1krazeplanet$2", $code);
        $updatedCode = preg_replace("/(mysqli_connect\s*\(\s*['\"])localhost(['\"])/i", "$1krazeplanet$2", $updatedCode);
        $updatedCode = preg_replace("/(host\s*=\s*)localhost/i", "${1}krazeplanet", $updatedCode);
        $updatedCode = preg_replace("/(DB_HOST\s*,\s*['\"])localhost(['\"])/i", "$1krazeplanet$2", $updatedCode);
        $updatedCode = preg_replace("/(\$[a-zA-Z0-9_]*host[a-zA-Z0-9_]*|\$servername)\s*=\s*['\"]localhost['\"]/i", "$1 = 'krazeplanet'", $updatedCode);
        if ($updatedCode !== $code) {
            file_put_contents($phpFile, $updatedCode);
        }
    }
}

// Register in database
if ($pdo) {
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare("UPDATE lab_instances SET status = 'destroyed' WHERE user_id = ? AND lab_id = ?")->execute([$userId, $parsedLab]);
    $subdomain = "{$parsedUser}-{$parsedLab}.{$baseDomain}";
    $stmtIns = $pdo->prepare("
        INSERT INTO lab_instances (user_id, username, lab_id, lab_title, subdomain, instance_dir, db_name, status, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
    ");
    $stmtIns->execute([$userId, $parsedUser, $parsedLab, $parsedLab, $subdomain, $instanceFolder, $dbName, $expiresAt]);
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