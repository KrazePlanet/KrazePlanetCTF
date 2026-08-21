<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit();
}

try {
    $customer_name = trim($data['customer_name'] ?? 'Guest Diner');
    $customer_phone = trim($data['customer_phone'] ?? 'N/A');
    $table_number = trim($data['table_number'] ?? 'Takeaway');
    $order_type = in_array($data['order_type'] ?? '', ['Dine-In', 'Takeaway', 'Delivery']) ? $data['order_type'] : 'Dine-In';
    $payment_method = trim($data['payment_method'] ?? 'Cash');
    $special_instructions = trim($data['special_instructions'] ?? '');

    $order_code = 'ORD-' . strtoupper(substr(uniqid(), -5));

    $total_amount = 0.00;
    foreach ($data['items'] as $item) {
        $price = (float)$item['price'];
        $qty = max(1, (int)$item['qty']);
        $total_amount += ($price * $qty);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO `orders` (`order_code`, `table_number`, `customer_name`, `customer_phone`, `order_type`, `total_amount`, `status`, `payment_method`, `payment_status`, `special_instructions`)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, 'Paid', ?)
    ");
    $stmt->execute([
        $order_code,
        $table_number,
        $customer_name,
        $customer_phone,
        $order_type,
        $total_amount,
        $payment_method,
        $special_instructions
    ]);
    $order_id = $pdo->lastInsertId();

    $item_stmt = $pdo->prepare("
        INSERT INTO `order_items` (`order_id`, `item_id`, `item_name`, `price`, `quantity`, `subtotal`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $item_id = (int)$item['id'];
        $item_name = $item['name'];
        $price = (float)$item['price'];
        $qty = max(1, (int)$item['qty']);
        $subtotal = $price * $qty;

        $item_stmt->execute([
            $order_id,
            $item_id,
            $item_name,
            $price,
            $qty,
            $subtotal
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_code' => $order_code,
        'order_id' => $order_id,
        'total' => number_format($total_amount, 2),
        'table' => $table_number,
        'message' => 'Order placed successfully! Kitchen has received your ticket.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
}
?>
