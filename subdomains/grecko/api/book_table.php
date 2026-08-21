<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$date = trim($_POST['date'] ?? date('Y-m-d'));
$party_size = max(1, (int)($_POST['party_size'] ?? $_POST['number'] ?? 2));
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO `reservations` (`name`, `email`, `date`, `party_size`, `phone`, `message`, `status`) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$name, $email, $date, $party_size, $phone, $message]);
    echo json_encode(['success' => true, 'message' => 'Your reservation request has been received! We look forward to welcoming you at Grecko.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>