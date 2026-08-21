<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];

// Get company ID
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $company_user_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    die("You must first create a company profile.");
}

// Handle status update
if (isset($_GET['update_status'])) {
    $app_id = intval($_GET['app_id']);
    $new_status = ucfirst(strtolower($_GET['update_status'])); // normalize

    if (in_array($new_status, ['Accepted', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $app_id);
        $stmt->execute();
        $message = "Application status updated!";
    }
}

// Fetch applicants for this company's internships
$sql = "SELECT a.id AS app_id, s.user_id AS student_id, u.full_name AS student_name, 
               s.resume_link, i.title AS internship_title, a.status
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE i.company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$applicants = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Applicants for My Internships</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_applicants.css">
</head>
<body class="p-4">
<div class="container">
    <h2>Applicants for My Internships</h2>
    <a href="company_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <?php if(isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>

    <?php if($applicants->num_rows > 0): ?>
        <table class="table table-bordered">
            <tr>
                <th>Student Name</th>
                <th>Internship</th>
                <th>Status</th>
                <th>Resume</th>
                <th>Action</th>
            </tr>
            <?php while($row = $applicants->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['internship_title']) ?></td>
                    <td>
                        <span class="badge 
                            <?php if($row['status']=='Pending') echo 'bg-warning text-dark';
                                  elseif($row['status']=='Accepted') echo 'bg-success';
                                  elseif($row['status']=='Rejected') echo 'bg-danger';
                                  else echo 'bg-secondary'; ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if(!empty($row['resume_link'])): ?>
                            <a href="<?= htmlspecialchars($row['resume_link']) ?>" target="_blank">View Resume</a>
                        <?php else: ?>
                            Not provided
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['status'] == 'Pending'): ?>
                            <a href="?app_id=<?= $row['app_id'] ?>&update_status=Accepted" class="btn btn-success btn-sm">Accept</a>
                            <a href="?app_id=<?= $row['app_id'] ?>&update_status=Rejected" class="btn btn-danger btn-sm">Reject</a>
                        <?php else: ?>
                            <span class="text-muted">No action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No applicants yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
