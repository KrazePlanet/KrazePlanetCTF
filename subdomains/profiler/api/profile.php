<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/profiles_data.php';

$id = isset($_GET['id']) ? trim((string) $_GET['id']) : '1024';
$profiles = get_all_member_profiles();

if ($id !== '' && isset($profiles[$id])) {
    echo json_encode($profiles[$id], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(404);
echo json_encode([
    'error' => 'Profile not found',
    'code' => 'PROFILE_NOT_FOUND',
], JSON_PRETTY_PRINT);
