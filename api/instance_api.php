<?php
// instance_api.php - True 100% Container-Level Per-User Lab Isolation Engine
require_once __DIR__ . '/../config/domain.php';
startKrazeSession();

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
$baseDomain = getKrazeBaseDomain($httpHost);
$proto = ($baseDomain === 'localhost') ? "http://" : "https://";


function terminateUserOtherLabs($userId, $username, $keepLabId = null) {
    global $pdo;
    $cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username));

    // 1. Terminate all previous active records in DB for this user (except $keepLabId)
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
            list($u, $l) = getCleanSubdomainTag($username, $oldInst['lab_id']);
            $oldCont = "kp_{$u}_{$l}";
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

    // 2. Sweep filesystem: destroy any lingering containers & remove folders for this user
    $currCleanLab = $keepLabId ? getCleanSubdomainTag($username, $keepLabId)[1] : '';
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

function handleLaunchLab($userId, $username, $rawLab, $labTitle, $forceFresh = false) {
    global $pdo, $baseDomain, $proto;

    $labId = sanitizeLabId($rawLab);
    if (empty($labId)) {
        sendInstanceJson(['status' => 400, 'success' => false, 'error' => 'Invalid lab identifier.'], 400);
    }

    list($cleanUser, $cleanLab) = getCleanSubdomainTag($username, $labId);

    // 0. Dedicated Per-User Isolated Mailpit Inbox Handler
    if ($cleanLab === 'mailpit' || $cleanLab === 'mail') {
        $userMailpitContainer = "kp_{$cleanUser}_mailpit";
        $checkMailpit = trim(shell_exec("docker inspect -f '{{.State.Running}}' {$userMailpitContainer} 2>/dev/null") ?? '');
        if ($checkMailpit !== 'true') {
            @chmod('/var/run/docker.sock', 0666);
            shell_exec("docker rm -f {$userMailpitContainer} 2>/dev/null");
            $mailpitCmd = "docker run -d --name {$userMailpitContainer} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1";
            shell_exec($mailpitCmd);
            usleep(300000);
        }

        $mailSubdomain = "{$cleanUser}-mailpit.{$baseDomain}";
        sendInstanceJson([
            'status' => 200,
            'success' => true,
            'message' => 'Private Mailpit inbox is ready!',
            'url' => $proto . $mailSubdomain,
            'alt_url' => $proto . $mailSubdomain,
            'subdomain' => $mailSubdomain,
            'expires_at' => date('Y-m-d H:i:s', time() + 7200),
            'seconds_left' => 7200,
            'lab_id' => 'mailpit',
            'lab_title' => 'Personal Mailbox',
            'container' => $userMailpitContainer,
            'is_existing' => true
        ]);
    }

    $containerName = "kp_{$cleanUser}_{$cleanLab}";
    $instanceFolder = "/opt/lampp/htdocs/instances/{$cleanUser}_{$cleanLab}";
    $dbName = "kp_{$cleanUser}_{$cleanLab}";
    
    // Subdomain format
    $subdomain = "{$cleanUser}-{$cleanLab}.{$baseDomain}";
    $altSubdomain = "{$cleanUser}.{$cleanLab}.{$baseDomain}";

    // 1. If already running and fresh restart NOT requested, return active instance
    if (!$forceFresh) {
        $checkRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}' " . escapeshellarg($containerName) . " 2>/dev/null") ?? '');
        if ($checkRunning === 'true') {
            $stmt = $pdo->prepare("SELECT id, expires_at, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_left FROM lab_instances WHERE user_id = ? AND lab_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId, $labId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['seconds_left'] > 0) {
                sendInstanceJson([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Connected to your existing active sandbox.',
                    'url' => $proto . $subdomain,
                    'alt_url' => $proto . $altSubdomain,
                    'subdomain' => $subdomain,
                    'expires_at' => $existing['expires_at'],
                    'seconds_left' => (int)$existing['seconds_left'],
                    'lab_id' => $labId,
                    'lab_title' => $labTitle ?: $cleanLab,
                    'container' => $containerName,
                    'is_existing' => true
                ]);
            }
        }
    }

    // 2. Strict 1-Lab-Per-User Policy: Terminate any previous lab containers for this user immediately
    terminateUserOtherLabs($userId, $username, $forceFresh ? null : $labId);
    shell_exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");

    // 3. Locate source template directory
    $candidatePaths = [
        "/opt/lampp/htdocs/subdomains/{$rawLab}",
        "/opt/lampp/htdocs/subdomains/{$cleanLab}",
        "/opt/lampp/htdocs/{$rawLab}",
        "/opt/lampp/htdocs/{$cleanLab}"
    ];

    $templateDir = null;
    foreach ($candidatePaths as $cand) {
        if (is_dir($cand)) {
            $templateDir = $cand;
            break;
        }
    }

    if (!$templateDir) {
        sendInstanceJson(['status' => 404, 'success' => false, 'error' => "Challenge template directory not found for: {$rawLab}"], 404);
    }

    // 4. Provision fresh instance folder
    @mkdir('/opt/lampp/htdocs/instances', 0777, true);
    if (is_dir($instanceFolder)) {
        recursiveDelete($instanceFolder);
    }
    @mkdir($instanceFolder, 0777, true);
    shell_exec("cp -r " . escapeshellarg($templateDir) . "/. " . escapeshellarg($instanceFolder) . "/");
    @chmod($instanceFolder, 0777);
    shell_exec("chmod -R 777 " . escapeshellarg($instanceFolder));

    // 5. Ensure Dedicated User Mailpit is running & point instance to it
    $userMailpitContainer = "kp_{$cleanUser}_mailpit";
    $checkMailpit = trim(shell_exec("docker inspect -f '{{.State.Running}}' {$userMailpitContainer} 2>/dev/null") ?? '');
    if ($checkMailpit !== 'true') {
        @chmod('/var/run/docker.sock', 0666);
        shell_exec("docker rm -f {$userMailpitContainer} 2>/dev/null");
        shell_exec("docker run -d --name {$userMailpitContainer} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1");
        usleep(250000);
    }

    // Auto-discover and import SQL schemas
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
            
            $pdoInstance = new PDO("mysql:host=localhost;dbname={$dbName};charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            ]);
            try {
                $pdoInstance->exec($sqlContent);
            } catch (Exception $e) {}
        }
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

    // Ensure PHPMailer and Vendor are available in instance
    if (!is_dir("{$instanceFolder}/PHPMailer") && is_dir("/opt/lampp/htdocs/PHPMailer")) {
        @mkdir("{$instanceFolder}/PHPMailer", 0777, true);
        shell_exec("cp -r /opt/lampp/htdocs/PHPMailer/* {$instanceFolder}/PHPMailer/ 2>/dev/null");
    }
    if (!is_dir("{$instanceFolder}/vendor") && is_dir("/opt/lampp/htdocs/subdomains/codeshackio/vendor")) {
        @mkdir("{$instanceFolder}/vendor", 0777, true);
        shell_exec("cp -r /opt/lampp/htdocs/subdomains/codeshackio/vendor/* {$instanceFolder}/vendor/ 2>/dev/null");
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