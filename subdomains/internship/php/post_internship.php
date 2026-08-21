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
$result = $stmt->get_result();
$company = $result->fetch_assoc();

if (!$company) {
    die("You must first create a company profile.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $duration = $_POST['duration'];
    $stipend = $_POST['stipend'];

    $stmt = $conn->prepare("INSERT INTO internships (company_id, title, description, location, duration, stipend, posted_on) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssss", $company['id'], $title, $description, $location, $duration, $stipend);
    $stmt->execute();

    $message = "Internship posted successfully!";
}
?>
 <link rel="stylesheet" href="../css/post_internship.css">
 <div>
<h2>Post New Internship</h2>
<?php if(isset($message)) echo "<p style='color:green;'>$message</p>"; ?>

<form method="POST">
    <label>Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="4" required></textarea><br><br>

    <label>Location:</label><br>
    <input type="text" name="location" required><br><br>

    <label>Duration:</label><br>
    <input type="text" name="duration" required><br><br>

    <label>Stipend:</label><br>
    <input type="text" name="stipend" required><br><br>

    <input type="submit" value="Post Internship">
    
<a href="company_dashboard.php">Back to Dashboard</a>
</form>

</div>