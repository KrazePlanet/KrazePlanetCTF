<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../database.php';

$result = $conn->query("SELECT a.id, s.full_name AS student, i.title AS internship, a.status
                        FROM applications a
                        JOIN students st ON a.student_id = st.id
                        JOIN users s ON st.user_id = s.id
                        JOIN internships i ON a.internship_id = i.id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="../css/manage_applications.css">
</head>
<body class="p-4">
<h2>Manage Applications</h2>
<a href="admin.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
<div class="table-responsive">
<table class="table">
<tr>
    <th>ID</th>
    <th>Student</th>
    <th>Internship</th>
    <th>Status</th>
</tr>
<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['student'] ?></td>
    <td><?= $row['internship'] ?></td>
    <td><?= $row['status'] ?></td>
</tr>
<?php } ?>
</table>
</div>
</body>
</html>
