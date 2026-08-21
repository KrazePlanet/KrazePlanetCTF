<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch student id
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();

if (!$student) {
    die("Please complete your profile first.");
}

$student_id = $student['id'];

// Fetch all applications for this student
$sql = "SELECT a.id, i.title AS internship, u.full_name AS company, a.status
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN companies c ON i.company_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE a.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$applications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="../css/my_applications.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>My Applications</h2>
        <a href="student_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if ($applications->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Internship</th>
                    <th>Company</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $applications->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['internship']) ?></td>
                    <td><?= htmlspecialchars($row['company']) ?></td>
                    <td>
                        <?php
                        if ($row['status'] === 'Pending') {
                            echo "<span class='badge bg-warning text-dark'>Pending</span>";
                        } elseif ($row['status'] === 'accepted') {
                            echo "<span class='badge bg-success'>Accepted</span>";
                        } elseif ($row['status'] === 'rejected') {
                            echo "<span class='badge bg-danger'>Rejected</span>";
                        } else {
                            echo htmlspecialchars($row['status'] ?? 'N/A');
                        }
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>You haven’t applied to any internships yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
