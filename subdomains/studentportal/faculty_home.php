<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

include("login1.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$attendance_success = $attendance_error = $marks_success = $marks_error = $remarks_success = $remarks_error = "";

// Handle Attendance Form Submission
if (isset($_POST['submit_attendance'])) {
    $student_id = sanitizeInput($_POST['student-id'] ?? '');
    $course = sanitizeInput($_POST['course'] ?? '');
    $total_class = sanitizeInput($_POST['t_class'] ?? '');
    $attended_class = sanitizeInput($_POST['a_class'] ?? '');

    if (empty($student_id) || empty($course) || empty($total_class) || empty($attended_class)) {
        $attendance_error = "All fields are required";
    } elseif ((int)$attended_class > (int)$total_class) {
        $attendance_error = "Attended classes cannot be more than total classes";
    } else {
        $stmt = $con->prepare("INSERT INTO s_attendence (std_id, course, total_class, attended_class) VALUES (?, ?, ?, ?)");
        
        if ($stmt === false) {
            $attendance_error = "Prepare failed: " . $con->error;
        } else {
            $stmt->bind_param("ssss", $student_id, $course, $total_class, $attended_class);
            
            if ($stmt->execute()) {
                $attendance_success = "Attendance recorded successfully!";
            } else {
                $attendance_error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle Exam Marks Form Submission
if (isset($_POST['submit_marks'])) {
    $course = sanitizeInput($_POST['exam-course'] ?? '');
    $student_id = sanitizeInput($_POST['student-id'] ?? '');
    $mark = filter_var($_POST['marks'] ?? '', FILTER_VALIDATE_FLOAT);

    if (empty($course) || empty($student_id) || $mark === false || $mark < 0 || $mark > 100) {
        $marks_error = "All fields are required and marks must be between 0 and 100";
    } else {
        $stmt = $con->prepare("INSERT INTO exam_marks (course, s_id, mark) VALUES (?, ?, ?)");
        
        if ($stmt === false) {
            $marks_error = "Prepare failed: " . $con->error;
        } else {
            $stmt->bind_param("ssd", $course, $student_id, $mark);
            
            if ($stmt->execute()) {
                $marks_success = "Exam marks recorded successfully!";
            } else {
                $marks_error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle Student Remarks Form Submission
if (isset($_POST['submit_remarks'])) {
    $student_id = sanitizeInput($_POST['remarks-student-id'] ?? '');
    $course_id = sanitizeInput($_POST['remarks-course'] ?? '');
    $remarks = sanitizeInput($_POST['student-remarks'] ?? '');

    if (empty($student_id) || empty($course_id) || empty($remarks)) {
        $remarks_error = "All fields are required";
    } else {
        $stmt = $con->prepare("INSERT INTO remarks (std_id, course_id, remark) VALUES (?, ?, ?)");
        
        if ($stmt === false) {
            $remarks_error = "Prepare failed: " . $con->error;
        } else {
            $stmt->bind_param("sss", $student_id, $course_id, $remarks);
            
            if ($stmt->execute()) {
                $remarks_success = "Student remarks recorded successfully!";
            } else {
                $remarks_error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch courses from the database
$courses_query = "SELECT code, c_name FROM course";
$courses_result = $con->query($courses_query);
$courses = [];

if ($courses_result && $courses_result->num_rows > 0) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Fetch student IDs
$student_query = "SELECT s_id FROM register";
$student_result = $con->query($student_query);
$students = [];

if ($student_result && $student_result->num_rows > 0) {
    while ($row = $student_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get faculty name instead of just using username
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

$active_tab = $_GET['tab'] ?? 'attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        form {
            display: grid;
            gap: 15px;
        }
        
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        
        input, select, textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #2980b9;
        }
        
        .success-message {
            color: white;
            background-color: var(--success-color);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .error-message {
            color: white;
            background-color: var(--danger-color);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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
                <li><a href="?tab=attendance" class="<?php echo $active_tab === 'attendance' ? 'active' : ''; ?>">Insert Attendance</a></li>
                <li><a href="?tab=exam-marks" class="<?php echo $active_tab === 'exam-marks' ? 'active' : ''; ?>">Insert Exam Marks</a></li>
                <li><a href="?tab=student-remarks" class="<?php echo $active_tab === 'student-remarks' ? 'active' : ''; ?>">Insert Student Remarks</a></li>
                <li><a href="?tab=view-students" class="<?php echo $active_tab === 'view-students' ? 'active' : ''; ?>">View Student Details</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h1>Teacher Dashboard</h1>
            </div>

            <!-- Insert Attendance Section -->
            <section id="attendance" class="section <?php echo $active_tab === 'attendance' ? 'active' : ''; ?>">
                <h2>Insert Attendance</h2>
                
                <?php if (!empty($attendance_success)): ?>
                    <div class="success-message"><?php echo $attendance_success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($attendance_error)): ?>
                    <div class="error-message"><?php echo $attendance_error; ?></div>
                <?php endif; ?>
                
                <form id="attendance-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=attendance" method="POST">
                    <div>
                        <label for="student-id">Student ID</label>
                        <select id="student-id" name="student-id" required>
                            <option value="">Select Student ID</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['s_id']); ?>">
                                    <?php echo htmlspecialchars($student['s_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="course">Select Course</label>
                        <select id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['code']); ?>">
                                    <?php echo htmlspecialchars($course['c_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="t_class">Total Number of Classes</label>
                        <input type="number" id="t_class" name="t_class" required min="1">
                    </div>

                    <div>
                        <label for="a_class">Number of Classes Attended</label>
                        <input type="number" id="a_class" name="a_class" required min="0">
                    </div>

                    <button type="submit" name="submit_attendance" class="submit-btn">Submit Attendance</button>
                </form>
            </section>

            <!-- Insert Exam Marks Section -->
            <section id="exam-marks" class="section <?php echo $active_tab === 'exam-marks' ? 'active' : ''; ?>">
                <h2>Insert Exam Marks</h2>
                
                <?php if (!empty($marks_success)): ?>
                    <div class="success-message"><?php echo $marks_success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($marks_error)): ?>
                    <div class="error-message"><?php echo $marks_error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=exam-marks" method="POST">
                    <div>
                        <label for="exam-course">Select Course</label>
                        <select id="exam-course" name="exam-course" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['code']); ?>">
                                    <?php echo htmlspecialchars($course['c_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="student-id">Student ID</label>
                        <select id="student-id" name="student-id" required>
                            <option value="">Select Student ID</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['s_id']); ?>">
                                    <?php echo htmlspecialchars($student['s_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="marks">Marks Obtained</label>
                        <input type="number" id="marks" name="marks" required min="0" max="100" step="0.01">
                    </div>

                    <button type="submit" name="submit_marks" class="submit-btn">Submit Marks</button>
                </form>
            </section>

            <!-- Student Remarks Section -->
            <section id="student-remarks" class="section <?php echo $active_tab === 'student-remarks' ? 'active' : ''; ?>">
                <h2>Insert Student Remarks</h2>
                
                <?php if (!empty($remarks_success)): ?>
                    <div class="success-message"><?php echo $remarks_success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($remarks_error)): ?>
                    <div class="error-message"><?php echo $remarks_error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=student-remarks" method="POST">
                    <div>
                        <label for="remarks-student-id">Student ID</label>
                        <select id="remarks-student-id" name="remarks-student-id" required>
                            <option value="">Select Student ID</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['s_id']); ?>">
                                    <?php echo htmlspecialchars($student['s_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="remarks-course">Select Course</label>
                        <select id="remarks-course" name="remarks-course" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['code']); ?>">
                                    <?php echo htmlspecialchars($course['c_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="student-remarks">Remarks</label>
                        <textarea id="student-remarks" name="student-remarks" rows="4" required></textarea>
                    </div>

                    <button type="submit" name="submit_remarks" class="submit-btn">Submit Remarks</button>
                </form>
            </section>

            <!-- View Student Details Section -->
            <section id="view-students" class="section <?php echo $active_tab === 'view-students' ? 'active' : ''; ?>">
                <h2>View Student Details</h2>
                <form action="f_view_student.php" method="GET">
                    <div>
                        <label for="student-id-view">Enter Student ID</label>
                        <select id="student-id-view" name="student_id" required>
                            <option value="">Select Student ID</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['s_id']); ?>">
                                    <?php echo htmlspecialchars($student['s_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">View Details</button>
                </form>
            </section>

            <footer>
                <p>&copy; <?php echo date('Y'); ?> XYZ College. All Rights Reserved.</p>
            </footer>
        </div>
    </div>

    <script>
        // Validation for attendance form
        document.getElementById('attendance-form')?.addEventListener('submit', function(e) {
            const totalClasses = parseInt(document.getElementById('t_class').value);
            const attendedClasses = parseInt(document.getElementById('a_class').value);
            
            if(attendedClasses > totalClasses) {
                e.preventDefault();
                alert('Attended classes cannot be more than total classes');
            }
        });
    </script>
</body>
</html>