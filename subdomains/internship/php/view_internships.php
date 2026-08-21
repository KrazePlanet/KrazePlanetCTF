<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student's ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();

$student_id = $student['id'] ?? null;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $internship_id = intval($_POST['internship_id']);

    if ($student_id) {
        // Check if already applied
        $check = $conn->prepare("SELECT id FROM applications WHERE internship_id=? AND student_id=?");
        $check->bind_param("ii", $internship_id, $student_id);
        $check->execute();
        $check_res = $check->get_result();

        if ($check_res->num_rows == 0) {
            // Insert with default status 'Pending'
            $stmt = $conn->prepare("INSERT INTO applications (student_id, internship_id, status) VALUES (?, ?, 'Pending')");
            $stmt->bind_param("ii", $student_id, $internship_id);
            $stmt->execute();
            $message = "✅ Applied successfully!";
        } else {
            $message = "⚠️ You have already applied to this internship.";
        }
    } else {
        $message = "⚠️ Please complete your profile before applying.";
    }
}

// Fetch all internships with company info
$sql = "SELECT i.id, i.title, i.description, i.location, i.duration, i.stipend, u.full_name AS company
        FROM internships i
        JOIN companies c ON i.company_id = c.id
        JOIN users u ON c.user_id = u.id
        ORDER BY i.posted_on DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Internships</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_internships.css">
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Available Internships</h2>
        <a href="student_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Duration</th>
                    <th>Stipend</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['company']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['duration']) ?></td>
                    <td><?= htmlspecialchars($row['stipend']) ?></td>
                    <td>
                        <?php if ($student_id): ?>
                            <?php
                            // Check if student already applied
                            $appliedCheck = $conn->prepare("SELECT id FROM applications WHERE internship_id=? AND student_id=?");
                            $appliedCheck->bind_param("ii", $row['id'], $student_id);
                            $appliedCheck->execute();
                            $isApplied = $appliedCheck->get_result()->num_rows > 0;
                            ?>
                            <?php if ($isApplied): ?>
                                <button class="btn btn-secondary btn-sm" disabled>Already Applied</button>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="internship_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="apply" class="btn btn-primary btn-sm">Apply</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-danger">Update profile to apply</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No internships available right now.</p>
        <?php endif; ?>
    </div>
</body>
</html>
