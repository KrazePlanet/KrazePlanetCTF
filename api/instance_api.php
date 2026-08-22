<?php
// instance_api.php - True 100% Container-Level Per-User Lab Isolation Engine
if (session_status() === PHP_SESSION_NONE) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'kzlabs.in') !== false) {
        ini_set('session.cookie_domain', '.kzlabs.in');
    } elseif (strpos($host, 'localhost') !== false || strpos($host, 'localtest.me') !== false) {
        ini_set('session.cookie_domain', (strpos($host, 'localtest.me') !== false ? '.localtest.me' : '.localhost'));
    }
    @session_start();
}

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
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
    if (!is_dir($src)) return;
    @mkdir($dst, 0777, true);
    @chmod($dst, 0777);
    $dir = opendir($src);
    if (!$dir) return;
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
            @chmod($dstPath, 0777);
        }
    }
    closedir($dir);
    @chmod($dst, 0777);
}

function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir) ?: [], ['.', '..']);
    foreach ($files as $file) {
        $p = "$dir/$file";
        (is_dir($p)) ? recursiveDelete($p) : @unlink($p);
    }
    @rmdir($dir);
}


function getHostWorkspacePath() {
    static $hostPath = null;
    if ($hostPath !== null) return $hostPath;
    
    $inspectJson = shell_exec("docker inspect krazeplanet -f '{{json .Mounts}}' 2>/dev/null");
    if (!empty($inspectJson)) {
        $mounts = json_decode($inspectJson, true);
        if (is_array($mounts)) {
            foreach ($mounts as $m) {
                if (isset($m['Destination']) && $m['Destination'] === '/opt/lampp/htdocs' && !empty($m['Source'])) {
                    $hostPath = rtrim($m['Source'], '/');
                    return $hostPath;
                }
            }
        }
    }
    $hostPath = '/opt/lampp/htdocs';
    return $hostPath;
}

function getDockerNetwork() {
    static $netName = null;
    if ($netName !== null) return $netName;
    
    $inspectNet = shell_exec("docker inspect krazeplanet -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' 2>/dev/null");
    $net = trim($inspectNet ?? '');
    if (!empty($net)) {
        $netName = $net;
        return $netName;
    }
    $netName = 'htdocs_default';
    return $netName;
}

function sanitizeLabId($raw) {
    $cleaned = trim($raw, '/');
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
$baseDomain = (strpos($httpHost, 'kzlabs.in') !== false) ? 'kzlabs.in' : ((strpos($httpHost, 'localtest.me') !== false) ? 'localtest.me' : 'localhost');
$proto = "https://";
if ($baseDomain === 'localhost') {
    $proto = "http://";
}

function handleLaunchLab($userId, $username, $rawLab, $labTitle, $forceFresh = false) {
    global $pdo, $baseDomain, $proto;

    $labId = sanitizeLabId($rawLab);
    if (empty($labId)) {
        sendInstanceJson(['status' => 400, 'success' => false, 'error' => 'Invalid lab identifier.'], 400);
    }

    list($cleanUser, $cleanLab) = getCleanSubdomainTag($username, $labId);

    // 0. Dedicated Per-User Isolated Mailpit Inbox Handler
    if ($cleanLab === 'mailpit' || $cleanLab === 'mail') {
        $containerName = "kp_{$cleanUser}_mailpit";
        $subdomain = "{$cleanUser}-mailpit.{$baseDomain}";
        $altSubdomain = "{$cleanUser}.mailpit.{$baseDomain}";

        if (!$forceFresh) {
            $checkRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
            if ($checkRunning === 'true') {
                $stmt = $pdo->prepare("SELECT * FROM lab_instances WHERE user_id = ? AND lab_id = 'mailpit' AND status = 'active' AND expires_at > NOW() LIMIT 1");
                $stmt->execute([$userId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    $secondsLeft = max(0, strtotime($existing['expires_at']) - time());
                    sendInstanceJson([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Active isolated Mailpit inbox retrieved.',
                        'url' => $proto . $existing['subdomain'],
                        'alt_url' => $proto . $altSubdomain,
                        'subdomain' => $existing['subdomain'],
                        'expires_at' => $existing['expires_at'],
                        'seconds_left' => $secondsLeft,
                        'lab_id' => 'mailpit',
                        'lab_title' => 'Mailpit Inbox',
                        'container' => $containerName,
                        'is_existing' => true
                    ]);
                }
            }
        }

        shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");

        $dockerNet = "htdocs_default";
        $netCheck = trim(shell_exec("docker network inspect htdocs_default -f '{{.Name}}' 2>/dev/null") ?? '');
        if (empty($netCheck)) {
            $dockerNet = "bridge";
        }

        @chmod('/var/run/docker.sock', 0666);
        $dockerCmd = sprintf(
            "docker run -d --name %s --network %s --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest 2>&1",
            escapeshellarg($containerName),
            escapeshellarg($dockerNet)
        );

        shell_exec($dockerCmd);
        usleep(250000);

        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $pdo->prepare("UPDATE lab_instances SET status = 'destroyed' WHERE user_id = ? AND lab_id = 'mailpit'")->execute([$userId]);

        $stmtIns = $pdo->prepare("
            INSERT INTO lab_instances (user_id, username, lab_id, lab_title, subdomain, instance_dir, db_name, status, expires_at)
            VALUES (?, ?, 'mailpit', 'Mailpit Inbox', ?, '', null, 'active', ?)
        ");
        $stmtIns->execute([$userId, $username, $subdomain, $expiresAt]);

        sendInstanceJson([
            'status' => 200,
            'success' => true,
            'message' => 'Isolated Mailpit inbox launched for 1 hour!',
            'url' => $proto . $subdomain,
            'alt_url' => $proto . $altSubdomain,
            'subdomain' => $subdomain,
            'expires_at' => $expiresAt,
            'seconds_left' => 3600,
            'lab_id' => 'mailpit',
            'lab_title' => 'Mailpit Inbox',
            'container' => $containerName,
            'is_existing' => false
        ]);
    }

    // 1. Regular Lab Sandbox Provisioning
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
        shell_exec("rm -rf " . escapeshellarg($instanceFolder));
    }
    @mkdir($instanceFolder, 0777, true);
    shell_exec("cp -r " . escapeshellarg($templateDir) . "/. " . escapeshellarg($instanceFolder) . "/");
    recursiveCopy($templateDir, $instanceFolder);
    @chmod($instanceFolder, 0777);
    shell_exec("chmod -R 777 " . escapeshellarg($instanceFolder));

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
                    $cfgStr = preg_replace('/(\$db_host|\$dbhost|\$hostname|DB_HOST)\s*=\s*[\'"][^\'"]+[\'"]/', "\$1 = 'krazeplanet'", $cfgStr);
                    file_put_contents($cfgPath, $cfgStr);
                }
            }
        } catch (Exception $e) {
            error_log("DB provision error: " . $e->getMessage());
        }
    }

    // Ensure user isolated Mailpit container is running for email labs
    $userMailpitContainer = "kp_{$cleanUser}_mailpit";
    $mailpitCheck = trim(shell_exec("docker inspect -f '{{.State.Running}}' {$userMailpitContainer} 2>/dev/null") ?? '');
    if ($mailpitCheck !== 'true') {
        $dockerNet = "htdocs_default";
        $netCheck = trim(shell_exec("docker network inspect htdocs_default -f '{{.Name}}' 2>/dev/null") ?? '');
        if (empty($netCheck)) $dockerNet = "bridge";
        shell_exec("docker rm -f {$userMailpitContainer} 2>/dev/null");
        shell_exec("docker run -d --name {$userMailpitContainer} --network {$dockerNet} --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest 2>/dev/null");
    }

    // Point localhost DB host to krazeplanet container and SMTP host to user Mailpit
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
            $mailIp = gethostbyname($userMailpitContainer);
            $cHost = ($mailIp !== $userMailpitContainer) ? $mailIp : $userMailpitContainer;
            $updatedCode = preg_replace('/(\$mail->Host\s*=\s*[\'"])mailpit([\'\"])/i', '${1}' . $cHost . '${2}', $updatedCode);
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

    // 6. Launch Dedicated Micro-Container with Universal Volume Sharing
    $dockerNet = getDockerNetwork();
    $imgCheck = trim(shell_exec("docker image inspect rix4uni/krazeplanet:lab-runtime -f '{{.Id}}' 2>/dev/null") ?? '');
    $runtimeImage = "rix4uni/krazeplanet:lab-runtime";
    if (empty($imgCheck)) {
        $runtimeImage = "php:8.2-apache";
    }

    @chmod('/var/run/docker.sock', 0666);
    shell_exec("chmod -R 777 {$instanceFolder} 2>/dev/null");

    $startScript = "cp -a " . escapeshellarg($instanceFolder) . "/. /var/www/html/ && chmod -R 777 /var/www/html && apache2-foreground";

    $dockerCmd = sprintf(
        "docker run -d --name %s --network %s --volumes-from krazeplanet:rw -v /var/run/mysqld:/var/run/mysqld:rw --memory=128m --cpus=0.5 --pids-limit=100 --restart=no %s bash -c %s 2>&1",
        escapeshellarg($containerName),
        escapeshellarg($dockerNet),
        escapeshellarg($runtimeImage),
        escapeshellarg($startScript)
    );

    $containerOutput = trim(shell_exec($dockerCmd) ?? '');
    usleep(400000);

    // Verify container is running
    $isRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
    if ($isRunning !== 'true') {
        error_log("Container launch failed for {$containerName}: {$containerOutput}");
        if ($runtimeImage !== 'rix4uni/krazeplanet:main') {
            shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
            $fallbackCmd = sprintf(
                "docker run -d --name %s --network %s -v %s:/var/www/html:rw -v /var/run/mysqld:/var/run/mysqld:rw --memory=128m --cpus=0.5 --pids-limit=100 --restart=no rix4uni/krazeplanet:main 2>&1",
                escapeshellarg($containerName),
                escapeshellarg($dockerNet),
                escapeshellarg($instanceFolder)
            );
            shell_exec($fallbackCmd);
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
    if ($cleanLab !== 'mailpit' && $cleanLab !== 'mail') {
        recursiveDelete($instanceFolder);
        if ($pdo) {
            @$pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`;");
        }
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
