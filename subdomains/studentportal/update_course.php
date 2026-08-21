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

// Check if course code is provided in URL
if (isset($_GET['code'])) {
    $code = sanitizeInput($_GET['code']);
    
    // Get course details
    $stmt = $con->prepare("SELECT * FROM course WHERE code = ?");
    if ($stmt === false) {
        $errors[] = "Prepare failed: " . $con->error;
    } else {
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "Course not found";
        } else {
            $row = $result->fetch_assoc();
        }
        
        $stmt->close();
    }
} else {
    header('Location: manage_course.php');
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $courseName = sanitizeInput($_POST['courseName'] ?? '');
    $courseDuration = filter_var($_POST['courseDuration'] ?? '', FILTER_VALIDATE_INT);
    $courseDepartment = sanitizeInput($_POST['courseDepartment'] ?? '');
    $courseInstructor = sanitizeInput($_POST['courseInstructor'] ?? '');
    $courseDescription = sanitizeInput($_POST['courseDescription'] ?? '');

    // Validate inputs
    if (empty($courseName)) $errors[] = "Course Name is required";
    if ($courseDuration === false || $courseDuration <= 0) $errors[] = "Invalid course duration";
    if (empty($courseDepartment)) $errors[] = "Department is required";
    if (empty($courseInstructor)) $errors[] = "Instructor is required";
    if (empty($courseDescription)) $errors[] = "Course Description is required";

    // If no errors, proceed with database update
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $con->prepare("UPDATE course SET c_name=?, duration=?, department=?, instructor=?, course_description=? WHERE code=?");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $con->error;
        } else {
            // Bind parameters
            $stmt->bind_param("sissss", $courseName, $courseDuration, $courseDepartment, $courseInstructor, $courseDescription, $code);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Course updated successfully!";
                // Refresh course data
                $stmt = $con->prepare("SELECT * FROM course WHERE code = ?");
                $stmt->bind_param("s", $code);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
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
    <title>Update Course - Course Portal</title>
    <link rel="stylesheet" href="add-course.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Update Course Details</h1>
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
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?code=' . $code; ?>" method="POST">
                <div class="input-group">
                    <label for="courseName">Course Name</label>
                    <input type="text" id="courseName" name="courseName" value="<?php echo htmlspecialchars($row['c_name'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="courseCode">Course Code</label>
                    <input type="text" id="courseCode" value="<?php echo htmlspecialchars($row['code'] ?? ''); ?>" disabled>
                </div>
                <div class="input-group">
                    <label for="courseDuration">Duration (in weeks)</label>
                    <input type="number" id="courseDuration" name="courseDuration" value="<?php echo htmlspecialchars($row['duration'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="courseDepartment">Department</label>
                    <input type="text" id="courseDepartment" name="courseDepartment" value="<?php echo htmlspecialchars($row['department'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="courseInstructor">Instructor</label>
                    <input type="text" id="courseInstructor" name="courseInstructor" value="<?php echo htmlspecialchars($row['instructor'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="courseDescription">Course Description</label>
                    <textarea id="courseDescription" name="courseDescription" rows="4" required><?php echo htmlspecialchars($row['course_description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="submit-btn">Update Course</button>
                <a href="manage_course.php" class="btn btn-secondary">Back</a>
            </form>
        </section>
    </div>
</body>
</html>