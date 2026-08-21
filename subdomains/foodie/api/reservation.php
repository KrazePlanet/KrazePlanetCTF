<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Support both embedded footer form and modal form fields
$name = trim($_POST['full_name'] ?? $_POST['name'] ?? '');
$email = trim($_POST['email_address'] ?? $_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $phone = 'Not Provided';
}

$raw_guests = $_POST['total_person'] ?? $_POST['num_guests'] ?? '2';
$num_guests = intval(preg_replace('/[^0-9]/', '', $raw_guests));
if ($num_guests <= 0) {
    $num_guests = 2;
}

$reservation_date = trim($_POST['booking_date'] ?? $_POST['reservation_date'] ?? date('Y-m-d'));
if (empty($reservation_date)) {
    $reservation_date = date('Y-m-d');
}

$reservation_time = trim($_POST['reservation_time'] ?? '19:30');
$special_request = trim($_POST['message'] ?? $_POST['special_request'] ?? '');

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide your name for the table reservation.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO reservations (name, email, phone, num_guests, reservation_date, reservation_time, special_request, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
    $stmt->execute([$name, $email, $phone, $num_guests, $reservation_date, $reservation_time, $special_request]);

    echo json_encode([
        'status' => 'success',
        'message' => "Table booked successfully for {$num_guests} guests on {$reservation_date}! We look forward to welcoming you."
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save reservation: ' . $e->getMessage()]);
}
?>
