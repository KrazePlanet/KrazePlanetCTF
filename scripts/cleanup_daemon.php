<?php
// cleanup_daemon.php - Automated Garbage Collector for Expired & Orphaned Lab Sandboxes
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'kraze_secret_gc')) {
    die("Access denied. CLI or secret key required.\n");
}

require_once __DIR__ . '/../config/db.php';

function removeDir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir) ?: [], ['.', '..']);
    foreach ($files as $file) {
        $p = "$dir/$file";
        (is_dir($p)) ? removeDir($p) : @unlink($p);
    }
    @rmdir($dir);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Container Garbage Collection...\n";

if (!$pdo) {
    die("[!] Database connection failed.\n");
}

// 1. Find and destroy expired active instances in DB
$stmt = $pdo->query("
    SELECT id, username, lab_id, subdomain, instance_dir, db_name 
    FROM lab_instances 
    WHERE expires_at < NOW() AND status = 'active'
");
$expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cleanedCount = 0;
foreach ($expired as $inst) {
    $cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($inst['username']));
    $cleanLab = str_replace('/', '-', preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', strtolower($inst['lab_id'])));
    $containerName = "kp_{$cleanUser}_{$cleanLab}";

    echo "[+] Expiring instance #{$inst['id']}: Container {$containerName} ({$inst['subdomain']})\n";
    
    // Destroy Docker Container
    shell_exec("docker rm -f {$containerName} 2>/dev/null");
    echo "    - Removed container: {$containerName}\n";

    // Delete Folder
    if (!empty($inst['instance_dir']) && is_dir($inst['instance_dir'])) {
        removeDir($inst['instance_dir']);
        echo "    - Deleted directory: {$inst['instance_dir']}\n";
    }

    // Drop DB
    if (!empty($inst['db_name'])) {
        @$pdo->exec("DROP DATABASE IF EXISTS `{$inst['db_name']}`;");
        echo "    - Dropped database: {$inst['db_name']}\n";
    }

    // Update status
    $pdo->prepare("UPDATE lab_instances SET status = 'expired' WHERE id = ?")->execute([$inst['id']]);
    $cleanedCount++;
}

// 2. Reconcile filesystem: Remove orphaned instance folders whose containers are dead or not active
$activeDirs = [];
$activeStmt = $pdo->query("SELECT instance_dir FROM lab_instances WHERE status = 'active' AND expires_at > NOW()");
while ($row = $activeStmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['instance_dir'])) {
        $activeDirs[rtrim($row['instance_dir'], '/')] = true;
    }
}

$allDiskDirs = glob("/opt/lampp/htdocs/instances/*");
if ($allDiskDirs) {
    foreach ($allDiskDirs as $diskDir) {
        $cleanPath = rtrim($diskDir, '/');
        $dirName = basename($cleanPath);
        if ($dirName === '.' || $dirName === '..') continue;
        
        // If not listed as active in DB
        if (!isset($activeDirs[$cleanPath])) {
            $contName = "kp_" . $dirName;
            echo "[+] Cleaning orphaned disk instance: {$dirName}\n";
            shell_exec("docker rm -f {$contName} 2>/dev/null");
            removeDir($cleanPath);
            @$pdo->exec("DROP DATABASE IF EXISTS `kp_{$dirName}`;");
            $cleanedCount++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Garbage collection finished. Cleaned {$cleanedCount} expired/orphaned containers.\n";
