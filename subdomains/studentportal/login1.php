<?php
$servername = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username   = "root";
$password   = "";
$dbname     = "students";

// 1. Initial Connection to Ensure Database Exists
$init_con = @mysqli_connect($servername, $username, $password);
if ($init_con) {
    @mysqli_query($init_con, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_con);
}

// 2. Connect to students database
$con = @mysqli_connect($servername, $username, $password, $dbname);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($con, "SHOW TABLES LIKE 'userlogin'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Table: userlogin (Admin credentials)
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `userlogin` (
          `uname` varchar(20) NOT NULL PRIMARY KEY,
          `pname` varchar(20) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: faculty
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `faculty` (
          `name` varchar(50) NOT NULL,
          `department` varchar(20) NOT NULL,
          `designation` varchar(20) NOT NULL,
          `qualification` varchar(20) NOT NULL,
          `number` varchar(15) NOT NULL,
          `email` varchar(50) NOT NULL,
          `year_of_experiance` int(11) NOT NULL,
          `f_pass` varchar(50) NOT NULL,
          `f_id` varchar(20) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: register (Students)
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `register` (
          `name` varchar(50) NOT NULL,
          `age` int(11) NOT NULL,
          `gender` enum('m','f','o') NOT NULL,
          `semester` varchar(20) NOT NULL,
          `number` varchar(15) NOT NULL,
          `email` varchar(50) NOT NULL,
          `department` varchar(20) NOT NULL,
          `s_id` varchar(20) NOT NULL PRIMARY KEY,
          `s_pass` varchar(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: course
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `course` (
          `c_name` varchar(50) NOT NULL,
          `code` varchar(20) NOT NULL PRIMARY KEY,
          `duration` int(11) NOT NULL,
          `department` varchar(20) NOT NULL,
          `instructor` varchar(50) NOT NULL,
          `course_description` varchar(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: exam_marks
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `exam_marks` (
          `course` varchar(20) NOT NULL,
          `s_id` varchar(20) NOT NULL,
          `mark` int(11) NOT NULL,
          PRIMARY KEY (`s_id`, `course`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: e_register (Course enrollments)
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `e_register` (
          `std_id` varchar(20) NOT NULL,
          `course_id` varchar(20) NOT NULL,
          PRIMARY KEY (`std_id`, `course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: remarks
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `remarks` (
          `std_id` varchar(20) NOT NULL,
          `course_id` varchar(20) NOT NULL,
          `remark` varchar(50) NOT NULL,
          PRIMARY KEY (`std_id`, `course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Table: s_attendence
    @mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `s_attendence` (
          `std_id` varchar(20) NOT NULL,
          `course` varchar(20) NOT NULL,
          `total_class` int(11) NOT NULL,
          `attended_class` int(11) NOT NULL,
          PRIMARY KEY (`std_id`, `course`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Seed Admin Accounts (admin / admin & tom / jerry)
    @mysqli_query($con, "INSERT INTO `userlogin` (`uname`, `pname`) VALUES
        ('admin', 'admin'),
        ('tom', 'jerry')
        ON DUPLICATE KEY UPDATE `pname`=VALUES(`pname`);");

    // Seed Faculty Accounts (ktu12 / 345 & fac01 / 1234)
    @mysqli_query($con, "INSERT INTO `faculty` (`name`, `department`, `designation`, `qualification`, `number`, `email`, `year_of_experiance`, `f_pass`, `f_id`) VALUES
        ('Prof. Sarah Davis', 'IT', 'Professor', 'M.Tech', '9876543210', 'sarah@college.edu', 9, '345', 'ktu12'),
        ('Dr. Alan Turing', 'IT', 'HOD', 'Ph.D', '9876543211', 'alan@college.edu', 12, '1234', 'fac01')
        ON DUPLICATE KEY UPDATE `f_pass`=VALUES(`f_pass`);");

    // Seed Student Accounts (ktu1010 / 1234 & stu01 / 1234)
    @mysqli_query($con, "INSERT INTO `register` (`name`, `age`, `gender`, `semester`, `number`, `email`, `department`, `s_id`, `s_pass`) VALUES
        ('Harry Potter', 20, 'm', '4', '9876543220', 'harry@college.edu', 'IT', 'ktu1010', '1234'),
        ('Alex Johnson', 21, 'm', '6', '9876543221', 'alex@college.edu', 'IT', 'stu01', '1234')
        ON DUPLICATE KEY UPDATE `s_pass`=VALUES(`s_pass`);");

    // Seed Courses
    @mysqli_query($con, "INSERT INTO `course` (`c_name`, `code`, `duration`, `department`, `instructor`, `course_description`) VALUES
        ('Database Systems', '12', 12, 'IT', 'Prof. Sarah Davis', 'Relational database schema design and SQL query analysis'),
        ('Discrete Mathematics', '20', 1, 'EE', 'Dr. Alan Turing', 'Logic, graph theory and discrete algorithms'),
        ('Python Programming', 'pyt100', 4, 'IT', 'Dr. Alan Turing', 'Advanced Python programming and data structures')
        ON DUPLICATE KEY UPDATE `c_name`=VALUES(`c_name`);");

    // Seed Attendance & Marks
    @mysqli_query($con, "INSERT INTO `s_attendence` (`std_id`, `course`, `total_class`, `attended_class`) VALUES
        ('ktu1010', 'pyt100', 100, 92),
        ('stu01', '12', 50, 48)
        ON DUPLICATE KEY UPDATE `attended_class`=VALUES(`attended_class`);");

    @mysqli_query($con, "INSERT INTO `exam_marks` (`course`, `s_id`, `mark`) VALUES
        ('pyt100', 'ktu1010', 95),
        ('12', 'stu01', 88)
        ON DUPLICATE KEY UPDATE `mark`=VALUES(`mark`);");
}
?>
