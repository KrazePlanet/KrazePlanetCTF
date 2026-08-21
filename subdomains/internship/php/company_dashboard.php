<?php
session_start();
require '../database.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];

// Check if company profile exists
$stmt = $conn->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $company_user_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

if (!$company) {
    // Redirect to create company profile if not exists
    header("Location: create_company.php");
    exit();
}

// Fetch internships posted by this company
$internships_sql = "SELECT id, title, location, duration, stipend, posted_on 
                    FROM internships 
                    WHERE company_id = ?";
$stmt = $conn->prepare($internships_sql);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$internships_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/company_dashboard.css">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>

        <h3>Company Info:</h3>
        <p><strong>Company Name:</strong> <?= htmlspecialchars($company['company_name'] ?? 'Not updated') ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($company['description'] ?? 'Not updated') ?></p>

        <div class="mb-4">
            <a href="post_internship.php" class="btn btn-primary">Post New Internship</a>
            <a href="view_applicants.php" class="btn btn-success">View Applicants</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <h3>My Internships:</h3>
        <?php if ($internships_result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Stipend</th>
                        <th>Posted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $internships_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['duration']) ?></td>
                            <td><?= htmlspecialchars($row['stipend']) ?></td>
                            <td><?= htmlspecialchars($row['posted_on']) ?></td>
                            <td>
                                <a href="edit_internship.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_internship.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this internship?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No internships posted yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
