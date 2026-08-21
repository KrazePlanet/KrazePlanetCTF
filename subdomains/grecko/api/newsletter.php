<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';

$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO `newsletter_subscribers` (`email`) VALUES (?)");
    $stmt->execute([$email]);
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to Grecko News & Special Offers!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>