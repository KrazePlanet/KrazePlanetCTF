<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../database.php';


// Delete user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: manage_users.php");
    exit();
}

// Fetch all users
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="../css/manage_users.css">
</head>
<body class="p-4">
<h2>Manage Users</h2>
<a href="admin.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
<table class="table table-bordered">
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Action</th>
    </tr>
    <?php while ($user = $result->fetch_assoc()) { ?>
    <tr>
        <td><?= $user['id'] ?></td>
        <td><?= $user['full_name'] ?></td>
        <td><?= $user['email'] ?></td>
        <td><?= $user['role'] ?></td>
        <td>
            <a href="manage_users.php?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
        </td>
    </tr>
    <?php } ?>
</table>
</body>
</html>
