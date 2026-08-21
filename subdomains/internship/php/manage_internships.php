<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../database.php';


$result = $conn->query("SELECT i.id, i.title, i.description, i.location, i.duration, i.stipend, i.posted_on, u.full_name AS company
                        FROM internships i
                        JOIN companies c ON i.company_id = c.id
                        JOIN users u ON c.user_id = u.id");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Internships</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/manage_internships.css">
</head>
<body class="p-4">
<h2>Manage Internships</h2>
<a href="admin.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
<table class="table table-bordered">
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Location</th>
    <th>Stipend</th>
    <th>Company</th>
</tr>
<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['title'] ?></td>
    <td><?= $row['location'] ?></td>
    <td><?= $row['stipend'] ?></td>
    <td><?= $row['company'] ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>
