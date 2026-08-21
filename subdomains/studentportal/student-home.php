<?php
session_start();
include("login1.php");

// Get student details
$s_id = $_SESSION['username'];
$query = "SELECT * FROM register WHERE s_id='$s_id'";
$result = mysqli_query($con, $query);
$student = mysqli_fetch_array($result);

// Initialize variables for success/error messages
$reg_success = "";
$reg_error = "";

// Handle Exam Registration Form Submission
if (isset($_POST['register_exam'])) {
    $student_id = $_SESSION['username'];
    $course_id = $_POST['exam'] ?? '';
    
    // Validate input
    if (empty($course_id)) {
        $reg_error = "Please select a course";
    } else {
        // Check if already registered
        $check_query = "SELECT * FROM e_register WHERE std_id='$student_id' AND course_id='$course_id'";
        $check_result = mysqli_query($con, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $reg_error = "You have already registered for this exam";
        } else {
            // Insert registration record
            $insert_query = "INSERT INTO e_register (std_id, course_id) VALUES ('$student_id', '$course_id')";
            
            if (mysqli_query($con, $insert_query)) {
                $reg_success = "Successfully registered for the exam!";
            } else {
                $reg_error = "Registration failed: " . mysqli_error($con);
            }
        }
    }
}

// Set active tab based on GET parameter
$active_tab = $_GET['tab'] ?? 'attendance';

// Fetch courses from the database
$courses_query = "SELECT code, c_name FROM course";
$courses_result = mysqli_query($con, $courses_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --accent-color: #9b59b6;
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
        
        .student-id {
            font-size: 14px;
            color: #aaa;
            margin-top: 5px;
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
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .section p {
            color: #666;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: var(--secondary-color);
            color: white;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tr:hover {
            background-color: #f1f1f1;
        }
        
        form {
            display: grid;
            gap: 15px;
            max-width: 500px;
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
        
        .remarks {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .remark {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .remark h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .no-remarks {
            color: #777;
            font-style: italic;
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
                <h2>Student Portal</h2>
                <p>Welcome, <?php echo htmlspecialchars($student['name']); ?></p>
                <p class="student-id">ID: <?php echo htmlspecialchars($s_id); ?></p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="?tab=attendance" class="<?php echo $active_tab === 'attendance' ? 'active' : ''; ?>">Attendance</a></li>
                <li><a href="?tab=exam-marks" class="<?php echo $active_tab === 'exam-marks' ? 'active' : ''; ?>">Exam Marks</a></li>
                <li><a href="?tab=exam-registration" class="<?php echo $active_tab === 'exam-registration' ? 'active' : ''; ?>">Exam Registration</a></li>
                <li><a href="?tab=remarks" class="<?php echo $active_tab === 'remarks' ? 'active' : ''; ?>">Remarks</a></li>
                <li><a href="category.html">Logout</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h1>Student Dashboard</h1>
            </div>

            <!-- Attendance Section -->
            <section id="attendance" class="section <?php echo $active_tab === 'attendance' ? 'active' : ''; ?>">
                <h2>Attendance</h2>
                <p>View your class attendance records.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Classes Held</th>
                            <th>Classes Attended</th>
                            <th>Attendance Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query1 = "SELECT * FROM s_attendence WHERE std_id='$s_id'";
                        $result1 = mysqli_query($con, $query1);
                        
                        if ($result1 && mysqli_num_rows($result1) > 0) {
                            while ($row1 = mysqli_fetch_assoc($result1)) {
                                // Calculate attendance percentage
                                $total = (int)$row1['total_class'];
                                $attended = (int)$row1['attended_class'];
                                $percentage = ($total > 0) ? round(($attended / $total) * 100, 2) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row1['course']); ?></td>
                                    <td><?php echo htmlspecialchars($row1['total_class']); ?></td>
                                    <td><?php echo htmlspecialchars($row1['attended_class']); ?></td>
                                    <td><?php echo $percentage; ?>%</td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="4">No attendance records available.</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </section>

            <!-- Exam Marks Section -->
            <section id="exam-marks" class="section <?php echo $active_tab === 'exam-marks' ? 'active' : ''; ?>">
                <h2>Exam Marks</h2>
                <p>View your recent exam results.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query2 = "SELECT * FROM exam_marks WHERE s_id='$s_id'";
                        $result2 = mysqli_query($con, $query2);
                        
                        if ($result2 && mysqli_num_rows($result2) > 0) {
                            while ($row2 = mysqli_fetch_assoc($result2)) {
                                // Calculate grade based on marks
                                $mark = $row2['mark'];
                                $grade = '';
                                
                                if ($mark >= 90) {
                                    $grade = 'A+';
                                } elseif ($mark >= 80) {
                                    $grade = 'A';
                                } elseif ($mark >= 70) {
                                    $grade = 'B';
                                } elseif ($mark >= 60) {
                                    $grade = 'C';
                                } elseif ($mark >= 50) {
                                    $grade = 'D';
                                } else {
                                    $grade = 'F';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row2['course']); ?></td>
                                    <td><?php echo htmlspecialchars($row2['mark']); ?></td>
                                    <td>100</td>
                                    <td><?php echo $grade; ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="4">No exam marks available.</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </section>

            <!-- Exam Registration Section -->
            <section id="exam-registration" class="section <?php echo $active_tab === 'exam-registration' ? 'active' : ''; ?>">
                <h2>Exam Registration</h2>
                <p>Register for upcoming exams.</p>
                
                <?php if (!empty($reg_success)): ?>
                    <div class="success-message"><?php echo $reg_success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($reg_error)): ?>
                    <div class="error-message"><?php echo $reg_error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=exam-registration" method="POST">
                    <div>
                        <label for="exam">Select Course</label>
                        <select id="exam" name="exam" required>
                            <option value="">-- Select a course --</option>
                            <?php 
                            // Display courses from database
                            mysqli_data_seek($courses_result, 0); // Reset result pointer
                            if ($courses_result && mysqli_num_rows($courses_result) > 0) {
                                while ($course = mysqli_fetch_assoc($courses_result)) {
                                    echo '<option value="' . htmlspecialchars($course['code']) . '">' . 
                                         htmlspecialchars($course['c_name']) . '</option>';
                                }
                            } else {
                                // Fallback if no courses found
                                echo '<option value="maths">Maths 101</option>';
                                echo '<option value="science">Science 102</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="register_exam" class="submit-btn">Register for Exam</button>
                </form>
            </section>

            <!-- Remarks Section -->
            <section id="remarks" class="section <?php echo $active_tab === 'remarks' ? 'active' : ''; ?>">
                <h2>Remarks</h2>
                <p>View any remarks from your instructors.</p>
                <div class="remarks">
                    <?php
                    // Query to fetch remarks for this student
                    $remarks_query = "SELECT r.*, c.c_name 
                                     FROM remarks r 
                                     LEFT JOIN course c ON r.course_id = c.code
                                     WHERE r.std_id='$s_id'";
                    $remarks_result = mysqli_query($con, $remarks_query);
                    
                    // Check if there are any remarks
                    if ($remarks_result && mysqli_num_rows($remarks_result) > 0) {
                        // Display each remark
                        while ($row = mysqli_fetch_assoc($remarks_result)) {
                            $course_name = !empty($row['c_name']) ? $row['c_name'] : $row['course_id'];
                            echo '<div class="remark">';
                            echo '<h3>' . htmlspecialchars($course_name) . '</h3>';
                            echo '<p>' . htmlspecialchars($row['remark']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        // No remarks found
                        echo '<div class="no-remarks">No remarks available at this time.</div>';
                    }
                    ?>
                </div>
            </section>

            <footer>
                <p>&copy; <?php echo date('Y'); ?> XYZ College. All Rights Reserved.</p>
            </footer>
        </div>
    </div>
</body>
</html>