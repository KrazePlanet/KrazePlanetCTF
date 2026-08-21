<?php
// cleanup_daemon.php - Automated Garbage Collector for Expired Docker Containers & Sandboxes
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'kraze_secret_gc')) {
    die("Access denied. CLI or secret key required.\n");
}

require_once __DIR__ . '/../config/db.php';

function removeDir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? removeDir("$dir/$file") : @unlink("$dir/$file");
    }
    @rmdir($dir);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Container Garbage Collection...\n";

if (!$pdo) {
    die("[!] Database connection failed.\n");
}

// Find expired active instances
$stmt = $pdo->query("
    SELECT id, username, lab_id, subdomain, instance_dir, db_name 
    FROM lab_instances 
    WHERE expires_at < NOW() AND status = 'active'
");
$expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cleanedCount = 0;
foreach ($expired as $inst) {
    $cleanUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($inst['username']));
    $cleanLab = str_replace('/', '-', preg_replace('/[^a-zA-Z0-9_\-\/]/', '', strtolower($inst['lab_id'])));
    $containerName = "kp_{$cleanUser}_{$cleanLab}";

    echo "[+] Expiring instance #{$inst['id']}: Container {$containerName} ({$inst['subdomain']})\n";
    
    // 1. Destroy Docker Container
    shell_exec("docker rm -f {$containerName} 2>/dev/null");
    echo "    - Removed container: {$containerName}\n";

    // 2. Delete Folder
    if (!empty($inst['instance_dir']) && is_dir($inst['instance_dir'])) {
        removeDir($inst['instance_dir']);
        echo "    - Deleted directory: {$inst['instance_dir']}\n";
    }

    // 3. Drop DB
    if (!empty($inst['db_name'])) {
        $pdo->exec("DROP DATABASE IF EXISTS `{$inst['db_name']}`;");
        echo "    - Dropped database: {$inst['db_name']}\n";
    }

    // 4. Update status
    $pdo->prepare("UPDATE lab_instances SET status = 'expired' WHERE id = ?")->execute([$inst['id']]);
    $cleanedCount++;
}

echo "[" . date('Y-m-d H:i:s') . "] Garbage collection finished. Cleaned {$cleanedCount} expired containers.\n";
