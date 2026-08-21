<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

// Include database connection
include("login1.php");

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$student_id = "";
$student_data = null;
$error_message = "";

// Validate and sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if student ID is provided in the URL
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = sanitizeInput($_GET['student_id']);
    
    // Prepare SQL statement to fetch student details
    $stmt = $con->prepare("SELECT * FROM register WHERE s_id = ?");
    
    if ($stmt === false) {
        $error_message = "Prepare failed: " . $con->error;
    } else {
        // Bind parameters
        $stmt->bind_param("s", $student_id);
        
        // Execute the statement
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $student_data = $result->fetch_assoc();
            } else {
                $error_message = "No student found with ID: " . $student_id;
            }
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    }
} else {
    $error_message = "Student ID is required";
}

// Get teacher name from session
$teacher_id = $_SESSION['username'] ?? '';
$teacher_name = 'Teacher';

if (!empty($teacher_id)) {
    $stmt = $con->prepare("SELECT name FROM faculty WHERE f_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $teacher_name = $row['name'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background-color: var(--primary-color);
            padding-left: 25px;
        }
        
        .content-area {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .logout-btn a {
            color: white;
            text-decoration: none;
        }
        
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .error-message {
            color: white;
            background-color: var(--danger-color);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .student-details table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .student-details table th, 
        .student-details table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .student-details table th {
            background-color: var(--light-color);
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .student-details table tr:hover {
            background-color: #f9f9f9;
        }
        
        .back-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #2980b9;
        }
        
        footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Teacher Portal</h2>
                <p>Welcome, <?php echo $teacher_name; ?></p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="faculty_home.php?tab=attendance">Insert Attendance</a></li>
                <li><a href="faculty_home.php?tab=exam-marks">Insert Exam Marks</a></li>
                <li><a href="faculty_home.php?tab=student-remarks">Insert Student Remarks</a></li>
                <li><a href="faculty_home.php?tab=view-students" class="active">View Student Details</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h1>Student Details</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="section">
                    <div class="error-message"><?php echo $error_message; ?></div>
                    <a href="faculty_home.php?tab=view-students" class="back-btn">Back to Dashboard</a>
                </div>
            <?php elseif ($student_data): ?>
                <div class="section student-details">
                    <h2>Details for Student ID: <?php echo htmlspecialchars($student_id); ?></h2>
                    <table>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td>Student ID</td>
                            <td><?php echo htmlspecialchars($student_data['s_id']); ?></td>
                        </tr>
                        <tr>
                            <td>Name</td>
                            <td><?php echo htmlspecialchars($student_data['name']); ?></td>
                        </tr>
                        <tr>
                            <td>Age</td>
                            <td><?php echo htmlspecialchars($student_data['age']); ?></td>
                        </tr>
                        <tr>
                            <td>Gender</td>
                            <td><?php echo htmlspecialchars($student_data['gender']); ?></td>
                        </tr>
                        <tr>
                            <td>Semester</td>
                            <td><?php echo htmlspecialchars($student_data['semester']); ?></td>
                        </tr>
                        <tr>
                            <td>Mobile Number</td>
                            <td><?php echo htmlspecialchars($student_data['number']); ?></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><?php echo htmlspecialchars($student_data['email']); ?></td>
                        </tr>
                        <tr>
                            <td>Department</td>
                            <td><?php echo htmlspecialchars($student_data['department']); ?></td>
                        </tr>
                    </table>
                    <a href="faculty_home.php?tab=view-students" class="back-btn">Back to Dashboard</a>
                </div>
            <?php endif; ?>

            <footer>
                <p>&copy; <?php echo date('Y'); ?> XYZ College. All Rights Reserved.</p>
            </footer>
        </div>
    </div>
</body>
</html>