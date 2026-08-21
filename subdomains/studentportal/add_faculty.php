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
    $name = sanitizeInput($_POST['facultyName'] ?? '');
    $department = sanitizeInput($_POST['facultyDepartment'] ?? '');
    $designation = sanitizeInput($_POST['facultyDesignation'] ?? '');
    $qualification = sanitizeInput($_POST['facultyQualifications'] ?? '');
    $number = sanitizeInput($_POST['facultyContact'] ?? '');
    $email = filter_var($_POST['facultyEmail'] ?? '', FILTER_SANITIZE_EMAIL);
    $experience = filter_var($_POST['facultyExperience'] ?? '', FILTER_VALIDATE_INT);
    $f_id = sanitizeInput($_POST['faculty-id'] ?? '');
    $password = sanitizeInput($_POST['faculty-password'] ?? '');

    // Validate inputs
    if (empty($name)) $errors[] = "Name is required";
    if (empty($department)) $errors[] = "Department is required";
    if (empty($designation)) $errors[] = "Designation is required";
    if (empty($qualification)) $errors[] = "Qualification is required";
    if (empty($number)) $errors[] = "Phone Number is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if ($experience === false || $experience < 0) $errors[] = "Invalid years of experience";
    if (empty($f_id)) $errors[] = "Faculty ID is required";
    if (empty($password)) $errors[] = "Password is required";

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $con->prepare("INSERT INTO faculty (name, department, designation, qualification, number, email, year_of_experiance, f_id, f_pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $con->error;
        } else {
            // Bind parameters
            $stmt->bind_param("sssssssss", $name, $department, $designation, $qualification, $number, $email, $experience, $f_id, $password);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Faculty added successfully!";
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
    <title>Add Faculty - Faculty Portal</title>
    <link rel="stylesheet" href="add-faculty.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Add Faculty Details</h1>
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
                    <label for="faculty-id">Faculty ID</label>
                    <input type="text" id="faculty-id" name="faculty-id" placeholder="Enter faculty ID" required>
                </div>
                <div class="input-group">
                    <label for="facultyName">Faculty Name</label>
                    <input type="text" id="facultyName" name="facultyName" placeholder="Enter full name" required>
                </div>
                <div class="input-group">
                    <label for="facultyDepartment">Department</label>
                    <input type="text" id="facultyDepartment" name="facultyDepartment" placeholder="Enter department" required>
                </div>
                <div class="input-group">
                    <label for="facultyDesignation">Designation</label>
                    <input type="text" id="facultyDesignation" name="facultyDesignation" placeholder="Enter designation" required>
                </div>
                <div class="input-group">
                    <label for="facultyQualifications">Qualifications</label>
                    <input type="text" id="facultyQualifications" name="facultyQualifications" placeholder="Enter qualifications" required>
                </div>
                <div class="input-group">
                    <label for="facultyContact">Contact Number</label>
                    <input type="tel" id="facultyContact" name="facultyContact" placeholder="Enter contact number" required>
                </div>
                <div class="input-group">
                    <label for="facultyEmail">Email</label>
                    <input type="email" id="facultyEmail" name="facultyEmail" placeholder="Enter email" required>
                </div>
                <div class="input-group">
                    <label for="facultyExperience">Years of Experience</label>
                    <input type="number" id="facultyExperience" name="facultyExperience" placeholder="Enter experience" required>
                </div>
                <div class="input-group">
                    <label for="faculty-password">Faculty Password</label>
                    <input type="password" id="faculty-password" name="faculty-password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="submit-btn">Add Faculty</button>
                <a href="admin_home.php" class="btn btn-secondary">Back</a>
            </form>
        </section>
    </div>
</body>
</html>