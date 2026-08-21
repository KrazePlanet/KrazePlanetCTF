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

// Initialize success message variable
$success_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $age = filter_var($_POST['age'] ?? '', FILTER_VALIDATE_INT);
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $semester = sanitizeInput($_POST['semester'] ?? '');
    $number = sanitizeInput($_POST['number'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $department = sanitizeInput($_POST['department'] ?? '');
    $s_id = sanitizeInput($_POST['student-id'] ?? '');
    $s_pass = sanitizeInput($_POST['student-password'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if ($age === false || $age <= 0) $errors[] = "Invalid age";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($semester)) $errors[] = "Semester is required";
    if (empty($number)) $errors[] = "Phone Number is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($department)) $errors[] = "Department is required";
    if (empty($s_id)) $errors[] = "Student ID is required";
    if (empty($s_pass)) $errors[] = "Password is required";

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $con->prepare("INSERT INTO register (name, age, gender, semester, number, email, department, s_id, s_pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $con->error;
        } else {
            // Bind parameters
            $stmt->bind_param("sisssssss", $name, $age, $gender, $semester, $number, $email, $department, $s_id, $s_pass);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Student registration successful!";
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
    <title>Add Student Details - Admin Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
       
        <!-- Main content -->
        <main class="main-content">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header text-center">
                                <h1>Student Registration</h1>
                            </div>
                            <div class="card-body">
                                <?php
                                // Display errors if any
                                if (!empty($errors)) {
                                    echo '<div class="alert alert-danger">';
                                    foreach ($errors as $error) {
                                        echo '<p>' . htmlspecialchars($error) . '</p>';
                                    }
                                    echo '</div>';
                                }

                                // Display success message
                                if (isset($success_message) && !empty($success_message)) {
                                    echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                                }
                                ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="form-group">
                                        <label for="student-id">Student ID</label>
                                        <input type="text" class="form-control" id="student-id" name="student-id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="age">Age</label>
                                        <input type="number" class="form-control" id="age" name="age" required min="1" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="male" value="m" required>
                                                <label class="form-check-label" for="male">Male</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="female" value="f">
                                                <label class="form-check-label" for="female">Female</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="others" value="o">
                                                <label class="form-check-label" for="others">Others</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="semester">Semester</label>
                                        <select class="form-control" id="semester" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="1">1st Semester</option>
                                            <option value="2">2nd Semester</option>
                                            <option value="3">3rd Semester</option>
                                            <option value="4">4th Semester</option>
                                            <option value="5">5th Semester</option>
                                            <option value="6">6th Semester</option>
                                            <option value="7">7th Semester</option>
                                            <option value="8">8th Semester</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="number">Phone Number</label>
                                        <input type="tel" class="form-control" id="number" name="number" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="CSE">Computer Science and Engineering</option>
                                            <option value="IT">Information Technology</option>
                                            <option value="ECE">Electronics and Communication Engineering</option>
                                            <option value="MECH">Mechanical Engineering</option>
                                            <option value="CIVIL">Civil Engineering</option>
                                            <option value="EEE">Electrical and Electronics Engineering</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="student-password">Password</label>
                                        <input type="password" class="form-control" id="student-password" name="student-password" required>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="manage_students.php" class="btn btn-secondary">Back</a>
                                        <button type="submit" class="btn btn-primary">Register Student</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>