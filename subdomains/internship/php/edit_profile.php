<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch existing student info
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = $_POST['course'];
    $year = $_POST['year'];
    $skills = $_POST['skills'];
    $resume_link = $_POST['resume_link'] ?? '';

    if ($student) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE students SET course=?, year=?, skills=?, resume_link=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $course, $year, $skills, $resume_link, $user_id);
        $stmt->execute();
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO students (user_id, course, year, skills, resume_link) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $course, $year, $skills, $resume_link);
        $stmt->execute();
    }

    $student = ['course'=>$course, 'year'=>$year, 'skills'=>$skills, 'resume_link'=>$resume_link]; // refresh data
    $message = "Profile updated successfully!";
}
?>
<link rel="stylesheet" href="../css/edit_profile.css">
<div class="form-container">
<h2>Edit Profile</h2>
<?php if(isset($message)) echo "<p style='color:green;'>$message</p>"; ?>

<form method="POST">
    <label>Course:</label><br>
    <input type="text" name="course" value="<?= htmlspecialchars($student['course'] ?? '') ?>"><br><br>

    <label>Year:</label><br>
    <input type="text" name="year" value="<?= htmlspecialchars($student['year'] ?? '') ?>"><br><br>

    <label>Skills:</label><br>
    <input type="text" name="skills" value="<?= htmlspecialchars($student['skills'] ?? '') ?>"><br><br>

    <label>Resume Link:</label><br>
    <input type="url" name="resume_link" value="<?= htmlspecialchars($student['resume_link'] ?? '') ?>" placeholder="Enter your resume URL"><br><br>

    <input type="submit" value="Save" class="btn btn-primary">
</form>

<a href='student_dashboard.php'>Back to Dashboard</a>
</div>