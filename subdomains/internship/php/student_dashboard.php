<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT course, year, skills, resume_link FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/student_dashboard.css">
</head>
<body class="p-4">

    <div class="container">
        <h1 class="mb-4">Welcome Student: <?= htmlspecialchars($_SESSION['full_name']) ?></h1>

        <h3>Your Profile:</h3>
        <p><strong>Course:</strong> <?= htmlspecialchars($student['course'] ?? 'Not updated') ?></p>
        <p><strong>Year:</strong> <?= htmlspecialchars($student['year'] ?? 'Not updated') ?></p>
        <p><strong>Skills:</strong> <?= htmlspecialchars($student['skills'] ?? 'Not updated') ?></p>
        <p><strong>Resume:</strong>
            <?php if (!empty($student['resume_link'])): ?>
                <a href="<?= htmlspecialchars($student['resume_link']) ?>" target="_blank">View Resume</a>
            <?php else: ?>
                Not provided
            <?php endif; ?>
        </p>

        <div class="list-group mt-4">
            <a href="view_internships.php" class="list-group-item list-group-item-action">Browse Internships</a>
            <a href="my_applications.php" class="list-group-item list-group-item-action">My Applications</a>
            <a href="edit_profile.php" class="list-group-item list-group-item-action">Edit Profile</a>
        </div>

        <a href="logout.php" class="btn btn-danger mt-4">Logout</a>
    </div>

</body>
</html>
