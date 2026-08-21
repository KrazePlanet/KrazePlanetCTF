<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$item_id = intval($_POST['item_id'] ?? 0);
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$quantity = max(1, intval($_POST['quantity'] ?? 1));
$order_notes = trim($_POST['order_notes'] ?? '');

if (empty($customer_name) || empty($customer_phone) || empty($delivery_address)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide your name, phone number, and delivery address.']);
    exit();
}

// Fetch item details
$stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['status' => 'error', 'message' => 'Selected food item not found.']);
    exit();
}

$item_name = $item['title'];
$unit_price = floatval($item['price']);
$total_price = $unit_price * $quantity;
$order_code = 'ORD-' . strtoupper(substr(uniqid(), -6));

try {
    $insert = $pdo->prepare("INSERT INTO orders (order_code, customer_name, customer_email, customer_phone, delivery_address, item_id, item_name, quantity, total_price, order_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $insert->execute([$order_code, $customer_name, $customer_email, $customer_phone, $delivery_address, $item_id, $item_name, $quantity, $total_price, $order_notes]);

    echo json_encode([
        'status' => 'success',
        'message' => "Order #{$order_code} placed successfully! Our chef is preparing your delicious {$item_name}.",
        'order_code' => $order_code,
        'total_price' => number_format($total_price, 2)
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record order: ' . $e->getMessage()]);
}
?>
