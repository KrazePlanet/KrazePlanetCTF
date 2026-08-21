<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];
$internship_id = $_GET['id'] ?? 0;

// Get company ID
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $company_user_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) die("You must first create a company profile.");

// Fetch internship
$stmt = $conn->prepare("SELECT * FROM internships WHERE id = ? AND company_id = ?");
$stmt->bind_param("ii", $internship_id, $company['id']);
$stmt->execute();
$internship = $stmt->get_result()->fetch_assoc();

if (!$internship) die("Internship not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $duration = $_POST['duration'];
    $stipend = $_POST['stipend'];

    $stmt = $conn->prepare("UPDATE internships SET title=?, description=?, location=?, duration=?, stipend=? WHERE id=?");
    $stmt->bind_param("sssssi", $title, $description, $location, $duration, $stipend, $internship_id);
    $stmt->execute();

    $message = "Internship updated successfully!";
    // Refresh data
    $internship = ['title'=>$title, 'description'=>$description, 'location'=>$location, 'duration'=>$duration, 'stipend'=>$stipend];
}
?>
<link rel="stylesheet" href="../css/edit_internship.css">
<div class="form-container">
<h2>Edit Internship</h2>
<?php if(isset($message)) echo "<p style='color:green;'>$message</p>"; ?>

<form method="POST">
    <label>Title:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($internship['title']) ?>" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="4" required><?= htmlspecialchars($internship['description']) ?></textarea><br><br>

    <label>Location:</label><br>
    <input type="text" name="location" value="<?= htmlspecialchars($internship['location']) ?>" required><br><br>

    <label>Duration:</label><br>
    <input type="text" name="duration" value="<?= htmlspecialchars($internship['duration']) ?>" required><br><br>

    <label>Stipend:</label><br>
    <input type="text" name="stipend" value="<?= htmlspecialchars($internship['stipend']) ?>" required><br><br>

    <input type="submit" value="Update Internship">
</form>

<a href="company_dashboard.php">Back to Dashboard</a>
</div>