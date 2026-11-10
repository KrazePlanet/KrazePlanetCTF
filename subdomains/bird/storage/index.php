<?php

/*
 * Directory Index for /storage/
 * Mimics Apache mod_autoindex FancyIndexing output
 */

$dir = __DIR__;
$files = scandir($dir);
$items = [];

foreach ($files as $file) {
    if ($file === '.' || $file === 'index.php' || $file === '.htaccess') continue;
    $filePath = $dir . DIRECTORY_SEPARATOR . $file;
    $items[] = [
        'name' => $file,
        'size' => filesize($filePath),
        'mtime' => filemtime($filePath),
        'is_dir' => is_dir($filePath)
    ];
}

usort($items, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . 'K';
    return round($bytes / 1048576, 1) . 'M';
}

function getFileDescription($name) {
    if (str_ends_with($name, '.sql')) return 'MySQL Database Dump';
    if (str_ends_with($name, '.csv')) return 'PII Data Export';
    if (str_ends_with($name, '.txt')) return 'Text Document';
    if (str_ends_with($name, '.pdf')) return 'PDF Document';
    return 'File';
}

$requestUri = rtrim(explode('?', $_SERVER['REQUEST_URI'])[0], '/');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
<title>Index of <?= htmlspecialchars($requestUri) ?></title>
<script>
if (!window.location.pathname.endsWith('/')) {
    window.history.replaceState(null, '', window.location.pathname + '/' + window.location.search);
}
</script>
<style>
body { font-family: monospace; font-size: 14px; background: #fff; color: #000; padding: 20px; }
h1 { font-family: sans-serif; font-size: 22px; font-weight: normal; margin-bottom: 15px; }
table { width: 100%; max-width: 900px; border-collapse: collapse; }
th, td { text-align: left; padding: 6px 12px; }
th { border-bottom: 1px solid #aaa; }
hr { border: none; border-top: 1px solid #ccc; margin: 10px 0; }
a { color: #0000ee; text-decoration: none; }
a:hover { text-decoration: underline; }
.size { text-align: right; }
address { margin-top: 25px; font-size: 11px; color: #555; }
</style>
</head>
<body>

<h1>Index of <?= htmlspecialchars($requestUri) ?></h1>

<table>
    <tr>
        <th>Name</th>
        <th>Last modified</th>
        <th class="size">Size</th>
        <th>Description</th>
    </tr>
    <tr>
        <td><a href="../">Parent Directory</a></td>
        <td>-</td>
        <td class="size">-</td>
        <td>-</td>
    </tr>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><a href="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></a></td>
        <td><?= date('Y-m-d H:i', $item['mtime']) ?></td>
        <td class="size"><?= formatBytes($item['size']) ?></td>
        <td><?= getFileDescription($item['name']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr>
<address>Apache/2.4 Server at <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?> Port <?= htmlspecialchars($_SERVER['SERVER_PORT'] ?? '8080') ?></address>

</body>
</html>

