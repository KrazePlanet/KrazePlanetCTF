<?php
require_once __DIR__ . '/../../../mailer.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRFToken, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$email = trim($data['email'] ?? $data['username'] ?? '');

if (!empty($email)) {
    // Send real SMTP email without rate limiting
    sendPasswordResetEmail($email);

    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'Password reset instructions have been sent to your email address.'
    ], JSON_PRETTY_PRINT);
    exit;
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'success' => false,
        'error' => 'Email address is required.'
    ], JSON_PRETTY_PRINT);
    exit;
}
