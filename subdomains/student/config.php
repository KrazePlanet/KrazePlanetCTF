<?php

// ===== DATABASE =====
define('DB_HOST', (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1')));
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_db');

// ===== APP =====
define('APP_NAME', 'EduPro');

// Dynamic APP_URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('APP_URL', $protocol . '://' . $host . '/student');
define('APP_VERSION', '2.0');

// ===== UPLOADS =====
define('UPLOAD_PATH', __DIR__ . '/uploads/students/');
define('UPLOAD_URL', APP_URL . '/uploads/students/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Ensure uploads folder exists
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}

// ===== 1. INITIAL CONNECTION & AUTO-CREATE DATABASE =====
$init_conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// ===== 2. DATABASE CONNECTION =====
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// ===== 3. AUTO-PROVISION TABLES & SEED DATA IF EMPTY =====
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // 1. Admins Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Courses Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `courses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `code` VARCHAR(20) UNIQUE,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Students Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `students` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` VARCHAR(20) UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NULL,
            `phone` VARCHAR(20),
            `address` TEXT,
            `profile_image` VARCHAR(255) NULL,
            `status` ENUM('active','inactive','graduated') DEFAULT 'active',
            `date_of_birth` DATE NULL,
            `gender` ENUM('male','female','other') NULL,
            `course` VARCHAR(100) NOT NULL,
            `last_login` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Grades Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `grades` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `marks` DECIMAL(5,2) NOT NULL,
            `grade` VARCHAR(5),
            `semester` VARCHAR(20),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Attendance Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `attendance` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `date` DATE NOT NULL,
            `status` ENUM('present','absent','late') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6. Fees Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `fees` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `paid_amount` DECIMAL(10,2) DEFAULT 0,
            `due_date` DATE,
            `status` ENUM('paid','unpaid','partial') DEFAULT 'unpaid',
            `description` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 7. Activity Log Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `activity_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `action` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Admins (admin@edupro.com / password & admin)
    $pwd_password = password_hash('password', PASSWORD_DEFAULT);
    $pwd_admin = password_hash('admin', PASSWORD_DEFAULT);

    @mysqli_query($conn, "INSERT INTO `admins` (`id`, `name`, `email`, `password`) VALUES
        (1, 'Administrator', 'admin@edupro.com', '{$pwd_password}'),
        (2, 'Super Admin', 'admin@admin.com', '{$pwd_admin}')
        ON DUPLICATE KEY UPDATE `password`='{$pwd_password}';");

    // Seed Courses
    @mysqli_query($conn, "INSERT INTO `courses` (`id`, `name`, `code`, `description`) VALUES
        (1, 'Computer Science', 'CS101', 'Core computer science, algorithms and software engineering.'),
        (2, 'Software Engineering', 'SE201', 'Modern fullstack application development and architecture.'),
        (3, 'Data Science & AI', 'DS301', 'Machine learning, statistics and deep learning frameworks.'),
        (4, 'Cybersecurity & Defense', 'CY401', 'Network security, ethical hacking and application security.');");

    // Seed Students
    @mysqli_query($conn, "INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `password`, `phone`, `address`, `status`, `date_of_birth`, `gender`, `course`) VALUES
        (1, 'STU-2026-0001', 'Alex Vance', 'student@edupro.com', '{$pwd_password}', '+1 555-019-2831', '123 University Way, Tech City', 'active', '2004-05-14', 'male', 'Computer Science'),
        (2, 'STU-2026-0002', 'Sarah Connor', 'sarah@edupro.com', '{$pwd_password}', '+1 555-829-1049', '456 Resistance Blvd', 'active', '2003-11-20', 'female', 'Software Engineering'),
        (3, 'STU-2026-0003', 'Elena Rostova', 'elena@edupro.com', '{$pwd_password}', '+1 555-391-7720', '789 Academic Square', 'active', '2004-01-18', 'female', 'Data Science & AI');");

    // Seed Grades
    @mysqli_query($conn, "INSERT INTO `grades` (`student_id`, `course_id`, `marks`, `grade`, `semester`) VALUES
        (1, 1, 92.5, 'A+', 'Fall 2026'),
        (1, 2, 88.0, 'A', 'Fall 2026'),
        (2, 2, 95.0, 'A+', 'Fall 2026'),
        (3, 3, 89.5, 'A', 'Fall 2026');");

    // Seed Attendance
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    @mysqli_query($conn, "INSERT INTO `attendance` (`student_id`, `date`, `status`) VALUES
        (1, '{$yesterday}', 'present'),
        (1, '{$today}', 'present'),
        (2, '{$yesterday}', 'present'),
        (2, '{$today}', 'late'),
        (3, '{$yesterday}', 'present'),
        (3, '{$today}', 'present');");

    // Seed Fees
    $due_date = date('Y-m-d', strtotime('+30 days'));
    @mysqli_query($conn, "INSERT INTO `fees` (`student_id`, `amount`, `paid_amount`, `due_date`, `status`, `description`) VALUES
        (1, 5000.00, 5000.00, '{$due_date}', 'paid', 'Semester Tuition Fee'),
        (2, 5000.00, 2500.00, '{$due_date}', 'partial', 'Semester Tuition Fee (Installment 1)'),
        (3, 5000.00, 0.00, '{$due_date}', 'unpaid', 'Semester Tuition Fee');");

    // Seed Initial Activity Log
    @mysqli_query($conn, "INSERT INTO `activity_log` (`action`) VALUES
        ('System initialized and sample database provisioned successfully.');");
}

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== HELPER FUNCTIONS =====

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . APP_URL . "/" . $url);
    exit();
}

function logActivity($conn, $action) {
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_log (action) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $action);
    mysqli_stmt_execute($stmt);
}

function timeAgo($datetime) {
    $diff = abs(time() - strtotime($datetime));
    if ($diff < 60)    return $diff . ' seconds ago';
    if ($diff < 3600)  return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    return floor($diff/86400) . ' days ago';
}

function generateStudentID($conn) {
    $year = date('Y');
    $result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students WHERE student_id LIKE 'STU-$year-%' ORDER BY id DESC LIMIT 1"));
    
    if ($result && !empty($result['student_id'])) {
        $parts  = explode('-', $result['student_id']);
        $number = (int) end($parts) + 1;
    } else {
        $number = 1;
    }
    
    return "STU-$year-" . str_pad($number, 4, '0', STR_PAD_LEFT);
}

function calculateGrade($marks) {
    if ($marks >= 90) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    return 'F';
}

function calculateGPA($marks) {
    if ($marks >= 90) return 4.0;
    if ($marks >= 80) return 3.7;
    if ($marks >= 70) return 3.0;
    if ($marks >= 60) return 2.0;
    if ($marks >= 50) return 1.0;
    return 0.0;
}

// ===== AUTH FUNCTIONS =====

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isStudentLoggedIn() {
    return isset($_SESSION['student_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('login.php');
    }
}

function requireStudent() {
    if (!isStudentLoggedIn()) {
        redirect('student/login.php');
    }
}

// Update student last login
function updateStudentLastLogin($conn, $student_id) {
    $now  = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn, "UPDATE students SET last_login = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $now, $student_id);
    mysqli_stmt_execute($stmt);
}

// Get student attendance percentage
function getAttendancePercentage($conn, $student_id) {
    $total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $student_id"))['count'];
    $present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $student_id AND status = 'present'"))['count'];
    if ($total == 0) return 0;
    return round(($present / $total) * 100);
}

// Get student average marks
function getAverageMarks($conn, $student_id) {
    $result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(marks) as avg FROM grades WHERE student_id = $student_id"));
    return round($result['avg'] ?? 0, 1);
}

// Get student fee percentage paid
function getFeePercentage($conn, $student_id) {
    $result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, SUM(paid_amount) as paid FROM fees WHERE student_id = $student_id"));
    if (!$result['total'] || $result['total'] == 0) return 100;
    return round(($result['paid'] / $result['total']) * 100);
}
?>
