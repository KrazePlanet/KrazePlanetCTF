<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: admin_login.php');
    exit();
}

// Include database connection
include("login1.php");

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate and sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize variables for success/error messages
$success_message = "";
$errors = [];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $courseName = sanitizeInput($_POST['courseName'] ?? '');
    $courseCode = sanitizeInput($_POST['courseCode'] ?? '');
    $courseDuration = filter_var($_POST['courseDuration'] ?? '', FILTER_VALIDATE_INT);
    $courseDepartment = sanitizeInput($_POST['courseDepartment'] ?? '');
    $courseInstructor = sanitizeInput($_POST['courseInstructor'] ?? '');
    $courseDescription = sanitizeInput($_POST['courseDescription'] ?? '');

    // Validate inputs
    if (empty($courseName)) $errors[] = "Course Name is required";
    if (empty($courseCode)) $errors[] = "Course Code is required";
    if ($courseDuration === false || $courseDuration <= 0) $errors[] = "Invalid course duration";
    if (empty($courseDepartment)) $errors[] = "Department is required";
    if (empty($courseInstructor)) $errors[] = "Instructor is required";
    if (empty($courseDescription)) $errors[] = "Course Description is required";

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $con->prepare("INSERT INTO course (c_name, code, duration, department, instructor, course_description) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $con->error;
        } else {
            // Bind parameters
            $stmt->bind_param("ssisss", $courseName, $courseCode, $courseDuration, $courseDepartment, $courseInstructor, $courseDescription);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Course added successfully!";
            } else {
                $errors[] = "Error: " . $stmt->error;
            }

            // Close statement
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - Course Portal</title>
    <link rel="stylesheet" href="add-course.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Add Course Details</h1>
        </header>

        <section class="form-section">
            <?php
            // Display success message if any
            if (!empty($success_message)) {
                echo '<div class="success-message">' . $success_message . '</div>';
            }
            
            // Display error messages if any
            if (!empty($errors)) {
                echo '<div class="error-message"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ul></div>';
            }
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="input-group">
                    <label for="courseName">Course Name</label>
                    <input type="text" id="courseName" name="courseName" placeholder="Enter course name" required>
                </div>
                <div class="input-group">
                    <label for="courseCode">Course Code</label>
                    <input type="text" id="courseCode" name="courseCode" placeholder="Enter course code" required>
                </div>
                <div class="input-group">
                    <label for="courseDuration">Duration (in weeks)</label>
                    <input type="number" id="courseDuration" name="courseDuration" placeholder="Enter course duration" required>
                </div>
                <div class="input-group">
                    <label for="courseDepartment">Department</label>
                    <input type="text" id="courseDepartment" name="courseDepartment" placeholder="Enter department" required>
                </div>
                <div class="input-group">
                    <label for="courseInstructor">Instructor</label>
                    <input type="text" id="courseInstructor" name="courseInstructor" placeholder="Enter instructor's name" required>
                </div>
                <div class="input-group">
                    <label for="courseDescription">Course Description</label>
                    <textarea id="courseDescription" name="courseDescription" rows="4" placeholder="Enter course description" required></textarea>
                </div>

                <button type="submit" class="submit-btn">Add Course</button>
                <a href="admin_home.php" class="btn btn-secondary">Back</a>
            </form>
        </section>
    </div>
</body>
</html>