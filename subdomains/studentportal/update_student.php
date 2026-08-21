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

// Initialize variables
$success_message = "";
$errors = [];
$student_data = [];

// Check if student ID is provided
if (isset($_GET['s_id'])) {
    $s_id = sanitizeInput($_GET['s_id']);
    
    // Prepare statement to prevent SQL injection
    $stmt = $con->prepare("SELECT * FROM register WHERE s_id = ?");
    
    if ($stmt === false) {
        $errors[] = "Prepare failed: " . $con->error;
    } else {
        // Bind parameter
        $stmt->bind_param("s", $s_id);
        
        // Execute the statement
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $student_data = $result->fetch_assoc();
            } else {
                $errors[] = "Student not found";
                header("Location: manage_students.php");
                exit();
            }
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    }
} else {
    header("Location: manage_students.php");
    exit();
}

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

    // Validate inputs
    if (empty($name)) $errors[] = "Name is required";
    if ($age === false || $age <= 0) $errors[] = "Invalid age";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($semester)) $errors[] = "Semester is required";
    if (empty($number)) $errors[] = "Phone Number is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($department)) $errors[] = "Department is required";

    // If no errors, proceed with database update
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $con->prepare("UPDATE register SET name=?, age=?, gender=?, semester=?, number=?, email=?, department=? WHERE s_id=?");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $con->error;
        } else {
            // Bind parameters
            $stmt->bind_param("sissssss", $name, $age, $gender, $semester, $number, $email, $department, $s_id);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Student information updated successfully!";
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
    <title>Update Student Details - Admin Portal</title>
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
                                <h1>Update Student Information</h1>
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
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?s_id=' . htmlspecialchars($s_id); ?>" method="post">
                                    <div class="form-group">
                                        <label for="student-id">Student ID</label>
                                        <input type="text" class="form-control" id="student-id" value="<?php echo htmlspecialchars($student_data['s_id'] ?? ''); ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student_data['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="age">Age</label>
                                        <input type="number" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($student_data['age'] ?? ''); ?>" required min="1" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="male" value="m" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'm') ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="male">Male</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="female" value="f" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'f') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="female">Female</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="others" value="o" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'o') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="others">Others</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="semester">Semester</label>
                                        <select class="form-control" id="semester" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="1" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '1') ? 'selected' : ''; ?>>1st Semester</option>
                                            <option value="2" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '2') ? 'selected' : ''; ?>>2nd Semester</option>
                                            <option value="3" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '3') ? 'selected' : ''; ?>>3rd Semester</option>
                                            <option value="4" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '4') ? 'selected' : ''; ?>>4th Semester</option>
                                            <option value="5" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '5') ? 'selected' : ''; ?>>5th Semester</option>
                                            <option value="6" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '6') ? 'selected' : ''; ?>>6th Semester</option>
                                            <option value="7" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '7') ? 'selected' : ''; ?>>7th Semester</option>
                                            <option value="8" <?php echo (isset($student_data['semester']) && $student_data['semester'] == '8') ? 'selected' : ''; ?>>8th Semester</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="number">Phone Number</label>
                                        <input type="tel" class="form-control" id="number" name="number" value="<?php echo htmlspecialchars($student_data['number'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="CSE" <?php echo (isset($student_data['department']) && $student_data['department'] == 'CSE') ? 'selected' : ''; ?>>Computer Science and Engineering</option>
                                            <option value="IT" <?php echo (isset($student_data['department']) && $student_data['department'] == 'IT') ? 'selected' : ''; ?>>Information Technology</option>
                                            <option value="ECE" <?php echo (isset($student_data['department']) && $student_data['department'] == 'ECE') ? 'selected' : ''; ?>>Electronics and Communication Engineering</option>
                                            <option value="MECH" <?php echo (isset($student_data['department']) && $student_data['department'] == 'MECH') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                            <option value="CIVIL" <?php echo (isset($student_data['department']) && $student_data['department'] == 'CIVIL') ? 'selected' : ''; ?>>Civil Engineering</option>
                                            <option value="EEE" <?php echo (isset($student_data['department']) && $student_data['department'] == 'EEE') ? 'selected' : ''; ?>>Electrical and Electronics Engineering</option>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="manage_students.php" class="btn btn-secondary">Back</a>
                                        <button type="submit" class="btn btn-primary">Update Student</button>
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