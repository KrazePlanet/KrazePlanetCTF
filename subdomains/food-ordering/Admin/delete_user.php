<?php
session_start();
if (!isset($_SESSION['adminloggedin'])) {
    http_response_code(403);
    exit();
}
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Delete reservation
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        echo 'User deleted successfully';
    } else {
        http_response_code(500);
        echo 'Error deleting reservation';
    }

    $stmt->close();
    $conn->close();
}
?>
