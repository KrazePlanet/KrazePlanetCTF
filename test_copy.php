<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$labId = 'tutorialrepublic';
$cleanLabId = preg_replace('#^subdomains/#i', '', $labId);
$candidatePaths = [
    "/opt/lampp/htdocs/subdomains/" . $cleanLabId,
    "/opt/lampp/htdocs/subdomains/" . $labId,
    "/opt/lampp/htdocs/" . $cleanLabId,
    "/opt/lampp/htdocs/" . $labId,
];
$templateDir = null;
foreach ($candidatePaths as $cand) {
    echo "Checking: $cand => " . (is_dir($cand) ? "EXISTS" : "NO") . "\n";
    if (is_dir($cand)) {
        $templateDir = $cand;
        break;
    }
}
echo "Selected templateDir: $templateDir\n";

function recursiveCopy($src, $dst) {
    echo "recursiveCopy from $src to $dst\n";
    if (!is_dir($src)) {
        echo "src is not a dir!\n";
        return;
    }
    @mkdir($dst, 0777, true);
    @chmod($dst, 0777);
    $dir = opendir($src);
    if (!$dir) {
        echo "cannot opendir $src\n";
        return;
    }
    while (false !== ($file = readdir($dir))) {
        echo "  Found item: '$file'\n";
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            $ok = copy($srcPath, $dstPath);
            echo "  Copy $srcPath -> $dstPath: " . ($ok ? "OK" : "FAILED") . "\n";
            @chmod($dstPath, 0777);
        }
    }
    closedir($dir);
}

$dst = "/opt/lampp/htdocs/instances/test_copy_tutorialrepublic";
recursiveCopy($templateDir, $dst);
