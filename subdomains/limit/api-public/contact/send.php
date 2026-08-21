<?php
require_once __DIR__ . '/../../mailer.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept-Language');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$email = '';
$topic = 'Feedback';
$message_text = '';

if (isset($data['responseData'])) {
    $email = trim($data['responseData']['email'] ?? '');
    $topic = trim($data['responseData']['topic'] ?? 'Feedback');
    $message_text = trim($data['responseData']['message'] ?? '');
} else {
    $email = trim($data['email'] ?? '');
    $topic = trim($data['topic'] ?? 'Feedback');
    $message_text = trim($data['message'] ?? '');
}

if (!empty($email) && !empty($message_text)) {
    // Send real email via SMTP without rate limiting (HackerOne #1166069)
    sendUpchieveContactEmail($email, $topic, $message_text);

    http_response_code(200);
    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'Your message has been sent successfully to the UPchieve team.',
        'data' => [
            'email' => $email,
            'topic' => $topic
        ]
    ], JSON_PRETTY_PRINT);
    exit;
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'success' => false,
        'error' => 'Email and message fields are required.'
    ], JSON_PRETTY_PRINT);
    exit;
}
