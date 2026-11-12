<?php
$allowed = ['uploads', 'documents', 'images', 'file'];
$dir = isset($_GET['dir']) ? trim((string) $_GET['dir']) : '';
if (in_array($dir, $allowed, true)) {
    header('Location: /' . $dir . '/', true, 301);
    exit;
}
http_response_code(404);
