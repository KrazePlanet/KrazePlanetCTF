<?php
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$user = "root";
$pass = "";
$db   = "internship_management";

// 1. Ensure Database Exists
$init_conn = @new mysqli($host, $user, $pass);
if ($init_conn && !$init_conn->connect_error) {
    @$init_conn->query("CREATE DATABASE IF NOT EXISTS `" . $db . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @$init_conn->close();
}

// 2. Connect to internship_management database
$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// 3. Auto-Provision Schema if empty
$chk = @$conn->query("SHOW TABLES LIKE 'users'");
if (!$chk || $chk->num_rows == 0) {

    // Tables DDL
    $conn->query("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `full_name` varchar(128) NOT NULL,
          `email` varchar(255) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL,
          `role` enum('admin','student','company') NOT NULL DEFAULT 'student'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS `companies` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` int(11) DEFAULT NULL,
          `company_name` varchar(100) DEFAULT NULL,
          `location` varchar(100) DEFAULT NULL,
          `description` text DEFAULT NULL,
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS `students` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` int(11) DEFAULT NULL,
          `course` varchar(100) DEFAULT NULL,
          `year` int(11) DEFAULT NULL,
          `skills` text DEFAULT NULL,
          `resume_link` varchar(255) DEFAULT NULL,
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS `internships` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `company_id` int(11) DEFAULT NULL,
          `title` varchar(100) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `location` varchar(100) DEFAULT NULL,
          `duration` varchar(50) DEFAULT NULL,
          `stipend` varchar(50) DEFAULT NULL,
          `posted_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS `applications` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `internship_id` int(11) DEFAULT NULL,
          `student_id` int(11) DEFAULT NULL,
          `status` varchar(20) NOT NULL DEFAULT 'Pending',
          `applied_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
          FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed default users (Admin, Student, Company) with password 'admin'
    $pwd_admin = password_hash('admin', PASSWORD_DEFAULT);
    
    $conn->query("INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`) VALUES
        (1, 'System Administrator', 'admin@internship.com', '{$pwd_admin}', 'admin'),
        (2, 'Tech Corp', 'company@gmail.com', '{$pwd_admin}', 'company'),
        (3, 'Alex Vance', 'test@example.com', '{$pwd_admin}', 'student'),
        (4, 'InnovateSoft', 'company2@gmail.com', '{$pwd_admin}', 'company'),
        (5, 'Sarah Connor', 'student2@example.com', '{$pwd_admin}', 'student')
        ON DUPLICATE KEY UPDATE `password`='{$pwd_admin}';");

    // Seed companies
    $conn->query("INSERT INTO `companies` (`id`, `user_id`, `company_name`, `location`, `description`) VALUES
        (1, 2, 'Tech Corp Global', 'Bangalore', 'Leading enterprise cloud and fullstack software development studio.'),
        (2, 4, 'InnovateSoft Labs', 'Pune', 'AI, frontend engineering, and mobile development team.');");

    // Seed students
    $conn->query("INSERT INTO `students` (`id`, `user_id`, `course`, `year`, `skills`, `resume_link`) VALUES
        (1, 3, 'Computer Science Engineering', 3, 'HTML, CSS, JavaScript, React.js, PHP, MySQL', 'https://google.com'),
        (2, 5, 'Software Engineering', 2, 'Python, Django, API Security, Linux', 'https://google.com');");

    // Seed internships
    $conn->query("INSERT INTO `internships` (`id`, `company_id`, `title`, `description`, `location`, `duration`, `stipend`, `posted_on`) VALUES
        (1, 1, 'Fullstack Web Developer', 'Build and optimize modern client portals with PHP and React.', 'Bangalore', '3 months', '25,000 / mo', NOW()),
        (2, 1, 'Frontend UI/UX Intern', 'Craft accessible, fast, and responsive user interfaces.', 'Bangalore', '6 months', '20,000 / mo', NOW()),
        (3, 2, 'Cybersecurity & Pentesting Intern', 'Vulnerability assessments, code review, and web app hardening.', 'Pune', '3 months', '30,000 / mo', NOW());");

    // Seed sample applications
    $conn->query("INSERT INTO `applications` (`id`, `internship_id`, `student_id`, `status`, `applied_on`) VALUES
        (1, 1, 1, 'Accepted', NOW()),
        (2, 3, 1, 'Pending', NOW()),
        (3, 2, 2, 'Pending', NOW());");
}
?>
