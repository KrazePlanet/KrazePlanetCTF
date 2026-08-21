<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../database.php';


$result = $conn->query("SELECT c.id, u.full_name AS owner, c.company_name, c.description 
                        FROM companies c 
                        JOIN users u ON c.user_id = u.id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Companies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/manage_companies.css">
</head>
<body class="p-4">
<h2>Manage Companies</h2>
<a href="admin.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
<table class="table table-bordered">
<tr>
    <th>ID</th>
    <th>Owner</th>
    <th>Company Name</th>
    <th>Description</th>
</tr>
<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['owner'] ?></td>
    <td><?= $row['company_name'] ?></td>
    <td><?= $row['description'] ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>
