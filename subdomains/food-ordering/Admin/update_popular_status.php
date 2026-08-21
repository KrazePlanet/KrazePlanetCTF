<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = $_POST['itemId'];
    $isPopular = $_POST['is_popular'];

    $sql = "UPDATE menuitem SET is_popular = ? WHERE itemId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $isPopular, $itemId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
