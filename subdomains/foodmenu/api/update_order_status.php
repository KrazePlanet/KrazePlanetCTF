<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['order_id']) || empty($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID or status.']);
    exit();
}

$order_id = (int)$data['order_id'];
$status = $data['status'];
$valid = ['Pending', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];

if (!in_array($status, $valid)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
    $stmt->execute([$status, $order_id]);
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
