<?php
session_start();
include("login1.php");
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: admin_login.php');
    exit();
}

// Get admin name if available
$admin_username = $_SESSION['username'];
$admin_name = $admin_username;

// Try to get the admin's full name if available in a database table
// This assumes there might be an admin table with more details
// If not, we'll just use the username
try {
    $stmt = $con->prepare("SELECT name FROM admin WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $admin_name = $row['name'];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silently fail - we'll use the username instead
}

// Get stats for dashboard
$total_students = 0;
$total_faculty = 0;
$total_courses = 0;

// Get student count
$query = "SELECT COUNT(*) as count FROM register";
$result = mysqli_query($con, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_students = $row['count'];
}

// Get faculty count
$query = "SELECT COUNT(*) as count FROM faculty";
$result = mysqli_query($con, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_faculty = $row['count'];
}

// Get course count
$query = "SELECT COUNT(*) as count FROM course";
$result = mysqli_query($con, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_courses = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Portal</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #1abc9c;
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
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        .statistics-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h2 {
            font-size: 2.5rem;
            margin: 10px 0;
            color: var(--primary-color);
        }
        
        .stat-card p {
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .stat-card.students {
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card.faculty {
            border-top: 4px solid var(--success-color);
        }
        
        .stat-card.courses {
            border-top: 4px solid var(--warning-color);
        }
        
        .quick-actions {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h2 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: block;
            padding: 15px;
            border: none;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .add-student {
            background-color: var(--primary-color);
        }
        
        .add-student:hover {
            background-color: #2980b9;
        }
        
        .add-faculty {
            background-color: var(--success-color);
        }
        
        .add-faculty:hover {
            background-color: #27ae60;
        }
        
        .add-course {
            background-color: var(--warning-color);
        }
        
        .add-course:hover {
            background-color: #e67e22;
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
                <h2>Admin Portal</h2>
                <p>Welcome, <?php echo htmlspecialchars($admin_name); ?></p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="admin_home.php" class="active">Dashboard</a></li>
                <li><a href="manage_students.php">Manage Students</a></li>
                <li><a href="manage_faculty.php">Manage Faculty</a></li>
                <li><a href="manage_course.php">Manage Courses</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h1>Admin Dashboard</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <div class="statistics-container">
                <div class="stat-card students">
                    <p>Total Students</p>
                    <h2><?php echo number_format($total_students); ?></h2>
                </div>
                
                <div class="stat-card faculty">
                    <p>Total Faculty</p>
                    <h2><?php echo number_format($total_faculty); ?></h2>
                </div>
                
                <div class="stat-card courses">
                    <p>Total Courses</p>
                    <h2><?php echo number_format($total_courses); ?></h2>
                </div>
            </div>
            

            
            <footer>
                <p>&copy; <?php echo date('Y'); ?> XYZ College. All Rights Reserved.</p>
            </footer>
        </div>
    </div>
</body>
</html>