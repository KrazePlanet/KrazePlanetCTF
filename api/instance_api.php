<?php
// instance_api.php - True 100% Container-Level Per-User Lab Isolation Engine
if (session_status() === PHP_SESSION_NONE) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'kzlabs.in') !== false) {
        ini_set('session.cookie_domain', '.kzlabs.in');
    } elseif (strpos($host, 'localhost') !== false) {
        ini_set('session.cookie_domain', '.localhost');
    }
    @session_start();
}

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/db.php';

function sendInstanceJson($data, $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';

if (!$userId || !$username) {
    sendInstanceJson(['status' => 401, 'success' => false, 'error' => 'Please sign in to access and launch your private lab instance.'], 401);
}

// Helpers
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    @chmod($dst, 0777);
}

function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? recursiveDelete("$dir/$file") : @unlink("$dir/$file");
    }
    @rmdir($dir);
}

function sanitizeLabId($raw) {
    $cleaned = trim($raw, '/');
    $cleaned = preg_replace('#^subdomains/#i', '', $cleaned);
    $cleaned = preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', $cleaned);
    return strtolower($cleaned);
}

function getCleanSubdomainTag($username, $labId) {
    $cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username));
    $rawLab = preg_replace('#^subdomains/#i', '', strtolower($labId));
    $cleanLab = str_replace('/', '-', preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', $rawLab));
    return [$cleanUser, $cleanLab];
}

$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isKzLabs = (strpos($httpHost, 'kzlabs.in') !== false);
$baseDomain = $isKzLabs ? 'kzlabs.in' : 'localhost';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

function handleLaunchLab($userId, $username, $rawLab, $labTitle, $forceFresh = false) {
    global $pdo, $baseDomain, $proto;

    $labId = sanitizeLabId($rawLab);
    if (empty($labId)) {
        sendInstanceJson(['status' => 400, 'success' => false, 'error' => 'Invalid lab identifier.'], 400);
    }

    list($cleanUser, $cleanLab) = getCleanSubdomainTag($username, $labId);
    $containerName = "kp_{$cleanUser}_{$cleanLab}";
    $subdomain = "{$cleanUser}-{$cleanLab}.{$baseDomain}";
    $altSubdomain = "{$cleanUser}.{$cleanLab}.{$baseDomain}";
    $instanceFolder = "/opt/lampp/htdocs/instances/{$cleanUser}_{$cleanLab}";
    $dbName = "kp_{$cleanUser}_{$cleanLab}";
    $dbName = substr(preg_replace('/[^a-zA-Z0-9_]/', '_', $dbName), 0, 60);

    // 1. Check existing container status if not forcing reset
    if (!$forceFresh) {
        $checkRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' {$containerName} 2>/dev/null") ?? '');
        if ($checkRunning === 'true') {
            $stmt = $pdo->prepare("SELECT * FROM lab_instances WHERE user_id = ? AND lab_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$userId, $labId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $secondsLeft = max(0, strtotime($existing['expires_at']) - time());
                sendInstanceJson([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Active isolated container retrieved.',
                    'url' => $proto . $existing['subdomain'],
                    'alt_url' => $proto . $altSubdomain,
                    'subdomain' => $existing['subdomain'],
                    'expires_at' => $existing['expires_at'],
                    'seconds_left' => $secondsLeft,
                    'lab_id' => $labId,
                    'lab_title' => $existing['lab_title'] ?: $labTitle,
                    'container' => $containerName,
                    'is_existing' => true
                ]);
            }
        }
    }

    // 2. Kill and remove any previous container
    shell_exec("docker rm -f {$containerName} 2>/dev/null");

    // 3. Locate master lab template (checking /opt/lampp/htdocs/subdomains/ and root)
    $cleanLabId = preg_replace('#^subdomains/#i', '', $labId);
    $candidatePaths = [
        "/opt/lampp/htdocs/subdomains/" . $cleanLabId,
        "/opt/lampp/htdocs/subdomains/" . $labId,
        "/opt/lampp/htdocs/" . $cleanLabId,
        "/opt/lampp/htdocs/" . $labId,
    ];
    $templateDir = null;
    foreach ($candidatePaths as $cand) {
        if (is_dir($cand)) {
            $templateDir = $cand;
            break;
        }
    }
    if (!$templateDir) {
        $parts = explode('/', $cleanLabId);
        $tail = "/opt/lampp/htdocs/subdomains/" . end($parts);
        if (is_dir($tail)) {
            $templateDir = $tail;
        } else {
            sendInstanceJson(['status' => 404, 'success' => false, 'error' => "Lab template '{$labId}' not found."], 404);
        }
    }

    // 4. Clone template to private folder
    if (is_dir($instanceFolder)) {
        recursiveDelete($instanceFolder);
    }
    @mkdir('/opt/lampp/htdocs/instances', 0777, true);
    recursiveCopy($templateDir, $instanceFolder);
    @chmod($instanceFolder, 0777);

    // 5. Provision Isolated DB if needed
    $hasDb = false;
    $sqlFiles = glob("{$instanceFolder}/*.sql");
    if (!empty($sqlFiles) && $pdo) {
        $hasDb = true;
        try {
            $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`; CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            foreach ($sqlFiles as $sqlFile) {
                $sqlContent = file_get_contents($sqlFile);
                if (!empty($sqlContent)) {
                    $pdo->exec("USE `{$dbName}`;");
                    $pdo->exec($sqlContent);
                }
            }
            $configCandidates = ['db.php', 'config.php', 'database.php', 'connect.php', 'includes/config.php', 'includes/db.php'];
            foreach ($configCandidates as $cfgRel) {
                $cfgPath = "{$instanceFolder}/{$cfgRel}";
                if (file_exists($cfgPath)) {
                    $cfgStr = file_get_contents($cfgPath);
                    $cfgStr = preg_replace('/(\$db_name|\$dbname|\$database|DB_NAME)\s*=\s*[\'"][^\'"]+[\'"]/', "\$1 = '{$dbName}'", $cfgStr);
                    // Point db host to container's mariadb host (krazeplanet or 172.17.0.1)
                    $cfgStr = preg_replace('/(\$db_host|\$dbhost|\$hostname|DB_HOST)\s*=\s*[\'"][^\'"]+[\'"]/', "\$1 = 'krazeplanet'", $cfgStr);
                    file_put_contents($cfgPath, $cfgStr);
                }
            }
        } catch (Exception $e) {
            error_log("DB provision error: " . $e->getMessage());
        }
    }

    // Point localhost DB host to krazeplanet container inside micro-container
    $phpFiles = glob("{$instanceFolder}/*.php");
    $subPhpFiles = glob("{$instanceFolder}/*/*.php");
    $allPhpFiles = array_merge($phpFiles ?: [], $subPhpFiles ?: []);
    foreach ($allPhpFiles as $phpFile) {
        if (file_exists($phpFile)) {
            $code = file_get_contents($phpFile);
            // Replace localhost/127.0.0.1 in mysqli/PDO/mysql_connect calls
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

    // Ensure PHPMailer is available in instance
    if (!is_dir("{$instanceFolder}/PHPMailer") && is_dir("/opt/lampp/htdocs/subdomains/PHPMailer")) {
        @mkdir("{$instanceFolder}/PHPMailer", 0777, true);
        shell_exec("cp -r /opt/lampp/htdocs/subdomains/PHPMailer/* {$instanceFolder}/PHPMailer/ 2>/dev/null");
    }

    // 6. Launch Dedicated Micro-Container (Takes ~200ms)
    // Detect active Docker network
    $dockerNet = "htdocs_default";
    $netCheck = trim(shell_exec("docker network inspect htdocs_default -f '{{.Name}}' 2>/dev/null") ?? '');
    if (empty($netCheck)) {
        $dockerNet = "bridge";
    }

// Ensure lab-runtime image exists (fallback build if missing)
    $imgCheck = trim(shell_exec("docker image inspect rix4uni/krazeplanet:lab-runtime -f '{{.Id}}' 2>/dev/null") ?? '');
    $runtimeImage = "rix4uni/krazeplanet:lab-runtime";
    if (empty($imgCheck)) {
        if (file_exists('/opt/lampp/htdocs/Dockerfile.lab_runtime')) {
            shell_exec("docker build -f /opt/lampp/htdocs/Dockerfile.lab_runtime -t rix4uni/krazeplanet:lab-runtime /opt/lampp/htdocs 2>&1");
        } else {
            $runtimeImage = "rix4uni/krazeplanet:main";
        }
    }

    @chmod('/var/run/docker.sock', 0666);
    shell_exec("chmod -R 777 {$instanceFolder} 2>/dev/null");

    $dockerCmd = sprintf(
        "docker run -d --name %s --network %s -v %s:/var/www/html:rw -v /var/run/mysqld:/var/run/mysqld:rw --memory=128m --cpus=0.5 --pids-limit=100 --restart=no %s 2>&1",
        escapeshellarg($containerName),
        escapeshellarg($dockerNet),
        escapeshellarg($instanceFolder),
        escapeshellarg($runtimeImage)
    );

    $containerOutput = trim(shell_exec($dockerCmd) ?? '');
    // Brief pause (400ms) to ensure Apache socket in container is accepting connections
    usleep(400000);

    // Verify container is running
    $isRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
    if ($isRunning !== 'true') {
        error_log("Container launch failed for {$containerName}: {$containerOutput}");
        // If image failed, retry with main image
        if ($runtimeImage !== 'rix4uni/krazeplanet:main') {
            shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
            $fallbackCmd = sprintf(
                "docker run -d --name %s --network %s -v %s:/var/www/html:rw -v /var/run/mysqld:/var/run/mysqld:rw --memory=128m --cpus=0.5 --pids-limit=100 --restart=no rix4uni/krazeplanet:main 2>&1",
                escapeshellarg($containerName),
                escapeshellarg($dockerNet),
                escapeshellarg($instanceFolder)
            );
            $containerOutput = trim(shell_exec($fallbackCmd) ?? '');
            usleep(400000);
        }
    }

    // 7. Register Instance in DB
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 Hour TTL
    $pdo->prepare("UPDATE lab_instances SET status = 'destroyed' WHERE user_id = ? AND lab_id = ?")->execute([$userId, $labId]);

    $stmtIns = $pdo->prepare("
        INSERT INTO lab_instances (user_id, username, lab_id, lab_title, subdomain, instance_dir, db_name, status, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
    ");
    $stmtIns->execute([$userId, $username, $labId, $labTitle ?: $cleanLab, $subdomain, $instanceFolder, ($hasDb ? $dbName : null), $expiresAt]);

    sendInstanceJson([
        'status' => 200,
        'success' => true,
        'message' => 'Private container sandbox launched in milliseconds!',
        'url' => $proto . $subdomain,
        'alt_url' => $proto . $altSubdomain,
        'subdomain' => $subdomain,
        'expires_at' => $expiresAt,
        'seconds_left' => 3600,
        'lab_id' => $labId,
        'lab_title' => $labTitle ?: $cleanLab,
        'container' => $containerName,
        'container_id' => substr($containerId, 0, 12),
        'is_existing' => false
    ]);
}

// Actions
if ($action === 'launch_lab') {
    $rawLab = $_POST['lab_id'] ?? $_GET['lab_id'] ?? '';
    $labTitle = trim($_POST['lab_title'] ?? $_GET['lab_title'] ?? '');
    handleLaunchLab($userId, $username, $rawLab, $labTitle, false);
}

if ($action === 'restart_lab') {
    $rawLab = $_POST['lab_id'] ?? $_GET['lab_id'] ?? '';
    $labTitle = trim($_POST['lab_title'] ?? $_GET['lab_title'] ?? '');
    handleLaunchLab($userId, $username, $rawLab, $labTitle, true);
}

if ($action === 'extend_lab') {
    $rawLab = $_POST['lab_id'] ?? $_GET['lab_id'] ?? '';
    $labId = sanitizeLabId($rawLab);

    $stmt = $pdo->prepare("SELECT id, expires_at, created_at FROM lab_instances WHERE user_id = ? AND lab_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $labId]);
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inst) {
        sendInstanceJson(['status' => 404, 'success' => false, 'error' => 'No active instance found.'], 404);
    }

    $currentExpiry = strtotime($inst['expires_at']);
    $createdTime = strtotime($inst['created_at']);
    $maxExpiry = $createdTime + (3 * 3600);
    $newExpiryTime = min($maxExpiry, max(time(), $currentExpiry) + 3600);
    $newExpiry = date('Y-m-d H:i:s', $newExpiryTime);
    $secondsLeft = max(0, $newExpiryTime - time());

    $pdo->prepare("UPDATE lab_instances SET expires_at = ? WHERE id = ?")->execute([$newExpiry, $inst['id']]);

    sendInstanceJson([
        'status' => 200,
        'success' => true,
        'message' => 'Lab container lease extended by +60 minutes.',
        'expires_at' => $newExpiry,
        'seconds_left' => $secondsLeft
    ]);
}

if ($action === 'terminate_lab') {
    $rawLab = $_POST['lab_id'] ?? $_GET['lab_id'] ?? '';
    $labId = sanitizeLabId($rawLab);
    list($cleanUser, $cleanLab) = getCleanSubdomainTag($username, $labId);
    $containerName = "kp_{$cleanUser}_{$cleanLab}";
    $instanceFolder = "/opt/lampp/htdocs/instances/{$cleanUser}_{$cleanLab}";
    $dbName = "kp_{$cleanUser}_{$cleanLab}";

    shell_exec("docker rm -f {$containerName} 2>/dev/null");
    recursiveDelete($instanceFolder);
    if ($pdo) {
        @$pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`;");
    }
    $pdo->prepare("UPDATE lab_instances SET status = 'destroyed' WHERE user_id = ? AND lab_id = ?")->execute([$userId, $labId]);

    sendInstanceJson(['status' => 200, 'success' => true, 'message' => 'Container terminated cleanly.']);
}

if ($action === 'list_my_instances') {
    $stmt = $pdo->prepare("
        SELECT id, lab_id, lab_title, subdomain, expires_at, created_at,
               TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS seconds_left
        FROM lab_instances 
        WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendInstanceJson(['status' => 200, 'success' => true, 'instances' => $instances]);
}

sendInstanceJson(['status' => 400, 'success' => false, 'error' => 'Invalid action.'], 400);
