<?php
// Open Redirect endpoint - Part of the SecureAuth SSRF lab
// This simulates an open redirect vulnerability on secureauth.io
// Students can use this as the initial "allowed" URL that then redirects to an internal target

$target = isset($_GET['to']) ? $_GET['to'] : '';

if (empty($target)) {
    http_response_code(400);
    echo "Missing 'to' parameter. Usage: redirect.php?to=http://TARGET_URL";
    exit;
}

// Simulate a real open redirect (no validation - intentionally vulnerable)
header('Location: ' . $target, true, 302);
exit;
?>
