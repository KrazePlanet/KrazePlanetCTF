<?php
// Include database connection
include 'db_connection.php';

// Get the input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$orderId = $data['orderId'];
$email = $data['email'];

if (empty($orderId) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Prepare the SQL statement
$stmt = $conn->prepare("DELETE FROM reviews WHERE order_id = ? AND email = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed']);
    exit();
}

// Bind parameters
$stmt->bind_param('is', $orderId, $email);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting review']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
