<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE subscribed_at = CURRENT_TIMESTAMP");
    $stmt->execute([$email]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for subscribing to Foodie VIP newsletter! Check your inbox for exclusive discount perks.'
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Subscription failed: ' . $e->getMessage()]);
}
?>
