<?php
/**
 * includes/lang.php — Localization loader
 *
 * Usage:
 *   require_once __DIR__ . '/includes/lang.php';
 *   echo $L['toolbar']['upload'];           // PHP
 *   // In JS: Lang.toolbar.upload
 *
 * conf.ini: language = ru   (default: en)
 */
$_langCode = strtolower(trim($config['language'] ?? 'en'));
$_langDir  = __DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
$_langFile = $_langDir . $_langCode . '.json';

// Fallback 1: requested language not found — try en
if (!file_exists($_langFile)) {
    $_langCode = 'en';
    $_langFile = $_langDir . 'en.json';
}

// Fallback 2: en.json also not found — hard stop with a clear error message
if (!file_exists($_langFile)) {
    $missingDir = htmlspecialchars($_langDir);
    http_response_code(500);
    die(<<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Configuration error</title>
<style>body{font-family:sans-serif;padding:40px;color:#333}
code{background:#f4f4f4;padding:2px 6px;border-radius:3px}</style></head>
<body>
<h2>&#9888; Language files not found</h2>
<p>The localization directory <code>{$missingDir}</code> is missing or empty.</p>
<p>Please create at least <code>includes/lang/en.json</code>.<br>
   See the project documentation for details.</p>
</body></html>
HTML);
}

$_langJson = file_get_contents($_langFile);
$L = json_decode($_langJson, true);

// Fallback 3: file exists but JSON is invalid
if (!is_array($L)) {
    $badFile = htmlspecialchars($_langFile);
    http_response_code(500);
    die(<<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Configuration error</title>
<style>body{font-family:sans-serif;padding:40px;color:#333}
code{background:#f4f4f4;padding:2px 6px;border-radius:3px}</style></head>
<body>
<h2>&#9888; Invalid language file</h2>
<p>Could not parse <code>{$badFile}</code> as valid JSON.</p>
<p>Please check the file for syntax errors.</p>
</body></html>
HTML);
}

/**
 * Helper function — get a string by dot-separated path
 * and substitute {key} placeholders.
 *
 * Examples:
 *   __('toolbar.upload')
 *   __('upload_modal.too_large', ['size' => '10MB'])
 */
function __(string $key, array $vars = []): string {
    global $L;
    $parts = explode('.', $key);
    $val   = $L;
    foreach ($parts as $p) {
        if (!is_array($val) || !isset($val[$p])) return $key;
        $val = $val[$p];
    }
    if (!is_string($val)) return $key;
    foreach ($vars as $k => $v) {
        $val = str_replace('{' . $k . '}', $v, $val);
    }
    return $val;
}
