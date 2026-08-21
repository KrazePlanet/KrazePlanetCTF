<?php
// includes/config.php
// 1. Path to config file
define('CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conf.ini');
function fatalConfigError(string $text) {
    echo '<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="fontawesome/css/all.min.css" rel="stylesheet">';
    echo '<link href="includes/styles.css" rel="stylesheet">';
    echo '<div class="container mt-5">';
    if (function_exists('showMessage')) {
        showMessage($text, 'danger', 'Configuration error');
    } else {
        echo "<div class='card shadow-sm border-danger'>
                <div class='card-header bg-danger text-white fw-bold'>
                    <i class='fa-solid fa-circle-xmark me-2'></i>Configuration error
                </div>
                <div class='card-body text-center py-4'>
                    <p class='fs-5'>$text</p>
                    <button onclick='location.reload()' class='btn btn-danger px-5 mt-3'>OK</button>
                </div>
              </div>";
    }
    echo '</div>';
    die();
}
if (!file_exists(CONFIG_PATH)) {
    fatalConfigError("Configuration file <b>conf.ini</b> not found. Please check the /config/ folder.");
}
if (!is_readable(CONFIG_PATH)) {
    fatalConfigError("Configuration file found but <b>cannot be read</b>.");
}
$configRaw = @parse_ini_file(CONFIG_PATH, true);
if ($configRaw === false) {
    fatalConfigError("Syntax error in <b>conf.ini</b>.");
}
$configRaw = array_change_key_case($configRaw, CASE_LOWER);
$requiredSections = ['general', 'options', 'exclude'];
foreach ($requiredSections as $sec) {
    if (!isset($configRaw[$sec])) {
        fatalConfigError("Required section missing in config file: <b>[" . strtoupper($sec) . "]</b>");
    }
}
$rawPatterns = $configRaw['exclude']['patterns'] ?? '';
$excludePatterns = [];
$filterError = null;
if (!empty($rawPatterns)) {
    $quoteCount = substr_count($rawPatterns, '"') + substr_count($rawPatterns, "'");
    if ($quoteCount % 2 !== 0) {
        $filterError = "Unmatched quote detected in file filters.";
    } else {
        $cleanStr = str_replace(['"', "'"], '', $rawPatterns);
        // Pattern case is preserved as defined in conf.ini —
        // case-insensitive comparison is performed in directory.php during filtering.
        $excludePatterns = array_values(array_filter(
            array_map('trim', explode(',', $cleanStr)),
            fn($v) => $v !== ''
        ));
    }
}
$config = [
    'base_dir'        => rtrim(realpath($configRaw['general']['base_dir'] ?? __DIR__), DIRECTORY_SEPARATOR),
    'title'           => $configRaw['general']['title'] ?? 'File Manager',
    'enable_search'   => filter_var($configRaw['options']['enable_search']   ?? true,  FILTER_VALIDATE_BOOLEAN),
    'enable_download' => filter_var($configRaw['options']['enable_download'] ?? true,  FILTER_VALIDATE_BOOLEAN),
    'enable_upload'   => filter_var($configRaw['options']['enable_upload']   ?? false, FILTER_VALIDATE_BOOLEAN),
    'enable_delete'   => filter_var($configRaw['options']['enable_delete']   ?? false, FILTER_VALIDATE_BOOLEAN),
    'max_upload_size' => $configRaw['options']['max_upload_size'] ?? '0',
    'date_format'     => $configRaw['general']['date_format'] ?? 'Y-m-d H:i',
    'background'      => $configRaw['general']['background'] ?? '',
	'language'        => $configRaw['general']['language']   ?? 'en',
    'debug'           => filter_var($configRaw['options']['debug'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'exclude_patterns' => $excludePatterns,
    'filter_error'     => $filterError
];
if (!is_dir($config['base_dir']) || !is_readable($config['base_dir'])) {
    fatalConfigError("The specified files directory is not accessible: <br><small>" . htmlspecialchars($config['base_dir']) . "</small>");
}
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
$restartHint = "restart your web server";
if (stripos($serverSoftware, 'Microsoft-IIS') !== false) {
    $restartHint = "restart <b>IIS</b>";
} elseif (stripos($serverSoftware, 'Apache') !== false) {
    $restartHint = "restart <b>Apache</b>";
} elseif (stripos($serverSoftware, 'nginx') !== false) {
    $restartHint = "restart <b>Nginx and PHP-FPM</b>";
}
$requiredExtensions = [
    'mbstring' => 'UTF-8 processing (CSV/Text)',
    'fileinfo' => 'MIME type detection (Video/Audio)',
    'iconv'    => 'encoding conversion (CSV)',
    'zip'      => 'archiving (Download selected)',
    'gd'       => 'image thumbnails'
];
foreach ($requiredExtensions as $ext => $description) {
    if (!extension_loaded($ext)) {
        fatalConfigError("Critical PHP extension <b>{$ext}</b> ({$description}) is not enabled.<br>Uncomment <code>extension={$ext}</code> in php.ini and {$restartHint}.");
    }
}
if (version_compare(PHP_VERSION, '8.5.0', '<')) {
    $config['filter_error'] = ($config['filter_error'] ? $config['filter_error'] . "<br>" : "") . "PHP 8.5.1+ is recommended. Current version: " . PHP_VERSION;
}
unset($configRaw);
