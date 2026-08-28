<?php

/*
 * ==========================================================
 * RIVERVIEW COURSE CATALOG
 * SQL Injection Training Lab
 * ==========================================================
 *
 * Single-file lab.
 *
 * On first run this file:
 *   1. Creates the database
 *   2. Creates the courses table
 *   3. Imports the sample data
 *
 * The course lookup is intentionally vulnerable to SQLi.
 *
 * FOR AUTHORIZED TRAINING/LAB ENVIRONMENTS ONLY.
 */


/* ==========================================================
   DATABASE CONFIGURATION
   ========================================================== */

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "college_lab";


/* ==========================================================
   CONNECT TO MYSQL SERVER
   ========================================================== */

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_pass
);

if ($conn->connect_error) {
    die("Database connection failed.");
}


/* ==========================================================
   CREATE DATABASE
   ========================================================== */

$create_db = "
    CREATE DATABASE IF NOT EXISTS `$db_name`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
";

if (!$conn->query($create_db)) {
    die("Unable to initialize database.");
}


/* ==========================================================
   SELECT DATABASE
   ========================================================== */

if (!$conn->select_db($db_name)) {
    die("Unable to select database.");
}


/* ==========================================================
   CREATE COURSES TABLE
   ========================================================== */

$create_table = "

CREATE TABLE IF NOT EXISTS courses (

    id INT PRIMARY KEY AUTO_INCREMENT,

    code VARCHAR(20) NOT NULL,

    name VARCHAR(150) NOT NULL,

    department VARCHAR(100) NOT NULL,

    instructor VARCHAR(100) NOT NULL,

    credits INT NOT NULL,

    description TEXT NOT NULL

)

";

if (!$conn->query($create_table)) {
    die("Unable to initialize course catalog.");
}


/* ==========================================================
   IMPORT SAMPLE DATA
   ========================================================== */

$count_result = $conn->query(
    "SELECT COUNT(*) AS total FROM courses"
);

$count = $count_result->fetch_assoc();


if ((int)$count['total'] === 0) {

    $insert = "

    INSERT INTO courses
    (
        code,
        name,
        department,
        instructor,
        credits,
        description
    )

    VALUES

    (
        'CSE101',
        'Introduction to Computer Science',
        'Computer Science & Engineering',
        'Dr. Aarav Mehta',
        4,
        'An introduction to computational thinking, programming fundamentals, algorithms and problem solving.'
    ),

    (
        'CSE204',
        'Database Systems',
        'Computer Science & Engineering',
        'Dr. Priya Sharma',
        4,
        'Fundamentals of relational databases, SQL, database design, transactions and data management.'
    ),

    (
        'ECE210',
        'Digital Electronics',
        'Electronics & Communication',
        'Dr. Kenji Ito',
        3,
        'Study of digital logic, combinational circuits, sequential systems and digital design.'
    ),

    (
        'MEC301',
        'Advanced Manufacturing',
        'Mechanical Engineering',
        'Dr. Daniel Wilson',
        3,
        'Modern manufacturing processes, automation, production systems and industrial applications.'
    ),

    (
        'BUS202',
        'Principles of Management',
        'Management Studies',
        'Dr. Leila Khan',
        3,
        'Introduction to organizational management, leadership, planning and decision making.'
    )

    ";

    $conn->query($insert);
}


/* ==========================================================
   INTENTIONALLY VULNERABLE COURSE LOOKUP
   ========================================================== */

$id = $_GET['id'] ?? '1';


/*
 * IMPORTANT:
 *
 * This is intentionally vulnerable for the SQL injection lab.
 *
 * DO NOT parameterize this query if you are using this exact
 * version as the vulnerable training target.
 */

$sql = "
    SELECT
        id,
        code,
        name,
        department,
        instructor,
        credits,
        description

    FROM courses

    WHERE id = '$id'
";


$result = $conn->query($sql);


/* ==========================================================
   SQL ERROR
   ========================================================== */

$sql_error = "";

if ($result === false) {
    $sql_error = $conn->error;
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Course Catalog — Riverview Institute
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f6f8;

    color: #243b53;
}


/* HEADER */

header {
    background: #102a43;

    color: white;

    border-bottom:
        4px solid #18b77e;
}

.header-inner {
    max-width: 1120px;

    margin: auto;

    padding:
        18px 25px;

    display: flex;

    justify-content:
        space-between;

    align-items: center;
}

.brand {
    font-size: 19px;

    font-weight: bold;
}

.brand small {
    display: block;

    margin-top: 4px;

    color: #9fb3c8;

    font-size: 10px;

    font-weight: normal;
}

nav a {
    color: #d9e2ec;

    text-decoration: none;

    margin-left: 22px;

    font-size: 13px;
}

nav a:hover {
    color: white;
}


/* CONTENT */

.container {
    max-width: 1120px;

    margin: 45px auto;

    padding:
        0 25px;
}

.breadcrumb {
    color: #718096;

    font-size: 12px;

    margin-bottom: 15px;
}

h1 {
    margin: 0;

    color: #102a43;

    font-size: 30px;
}

.intro {
    color: #718096;

    font-size: 14px;

    line-height: 1.6;

    margin-top: 8px;

    margin-bottom: 28px;
}


/* SEARCH */

.search-box {
    background: white;

    border:
        1px solid #dce3ea;

    border-radius: 7px;

    padding: 18px;

    margin-bottom: 25px;
}

.search-box form {
    display: flex;

    gap: 10px;
}

.search-box input {
    flex: 1;

    padding: 12px;

    border:
        1px solid #cbd5e0;

    border-radius: 5px;

    outline: none;

    font-size: 13px;
}

.search-box input:focus {
    border-color: #1769aa;
}

.search-box button {
    padding:
        0 22px;

    border: 0;

    border-radius: 5px;

    background: #1769aa;

    color: white;

    font-weight: bold;

    cursor: pointer;
}


/* COURSE */

.course {
    background: white;

    border:
        1px solid #dce3ea;

    border-radius: 8px;

    padding: 28px;

    box-shadow:
        0 3px 12px
        rgba(16,42,67,.04);
}

.course-code {
    display: inline-block;

    padding:
        5px 9px;

    border-radius: 4px;

    background: #e6f7f1;

    color: #087f5b;

    font-size: 11px;

    font-weight: bold;
}

.course h2 {
    margin:
        13px 0 7px;

    color: #102a43;

    font-size: 24px;
}

.department {
    color: #1769aa;

    font-size: 13px;

    font-weight: bold;
}

.details {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin:
        25px 0;
}

.detail {
    background: #f8fafc;

    padding: 15px;

    border-radius: 5px;
}

.detail-label {
    color: #718096;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.detail-value {
    margin-top: 5px;

    color: #243b53;

    font-size: 13px;

    font-weight: bold;
}

.description {
    color: #52606d;

    line-height: 1.7;

    font-size: 14px;
}


/* NOT FOUND */

.not-found {
    background: white;

    border:
        1px solid #dce3ea;

    border-radius: 8px;

    padding: 35px;

    text-align: center;
}

.not-found h2 {
    margin-top: 0;

    color: #102a43;
}

.not-found p {
    color: #718096;

    font-size: 13px;
}


/* SQL ERROR */

.sql-error {
    margin-top: 25px;

    padding: 15px;

    border-left:
        4px solid #d64545;

    background: #fff5f5;

    color: #9b2c2c;

    font-family: monospace;

    font-size: 12px;

    white-space: pre-wrap;

    text-align: left;
}


/* FOOTER */

footer {
    margin-top: 80px;

    padding: 28px;

    background: #102a43;

    color: #9fb3c8;

    text-align: center;

    font-size: 11px;
}


/* RESPONSIVE */

@media(max-width: 700px) {

    .header-inner {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    nav a {
        margin-left: 0;

        margin-right: 15px;
    }

    .search-box form {
        flex-direction: column;
    }

    .search-box button {
        padding: 12px;
    }

    .details {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<header>

    <div class="header-inner">

        <div class="brand">

            RIVERVIEW INSTITUTE

            <small>
                COURSE CATALOG · ACADEMIC YEAR 2026–27
            </small>

        </div>


        <nav>

            <a href="/">
                Home
            </a>

            <a href="/course.php">
                Courses
            </a>

            <a href="#">
                Departments
            </a>

            <a href="#">
                Contact
            </a>

        </nav>

    </div>

</header>


<main class="container">


    <div class="breadcrumb">
        Home / Academic Catalog / Course Details
    </div>


    <h1>
        Course Catalog
    </h1>


    <p class="intro">

        Browse courses offered by Riverview Institute
        of Technology for the current academic year.

    </p>


    <div class="search-box">

        <form method="GET">

            <input
                type="text"
                name="id"
                placeholder="Enter course ID"
                value="<?= $id ?>"
            >

            <button type="submit">
                View Course
            </button>

        </form>

    </div>


<?php if ($sql_error): ?>


    <div class="not-found">

        <h2>
            Unable to load course
        </h2>

        <p>
            The requested course could not be processed.
        </p>


        <div class="sql-error">

            Database error:

            <?= $sql_error ?>

        </div>

    </div>


<?php elseif ($result && $result->num_rows > 0): ?>


    <?php while ($course = $result->fetch_assoc()): ?>


        <article class="course">


            <span class="course-code">
                <?= $course['code'] ?>
            </span>


            <h2>
                <?= $course['name'] ?>
            </h2>


            <div class="department">
                <?= $course['department'] ?>
            </div>


            <div class="details">


                <div class="detail">

                    <div class="detail-label">
                        Instructor
                    </div>

                    <div class="detail-value">
                        <?= $course['instructor'] ?>
                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Credits
                    </div>

                    <div class="detail-value">
                        <?= $course['credits'] ?>
                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Course ID
                    </div>

                    <div class="detail-value">
                        <?= $course['id'] ?>
                    </div>

                </div>


            </div>


            <div class="description">

                <?= $course['description'] ?>

            </div>


        </article>


    <?php endwhile; ?>


<?php else: ?>


    <div class="not-found">

        <h2>
            Course Not Found
        </h2>

        <p>
            We couldn't find a course matching
            the requested ID.
        </p>

    </div>


<?php endif; ?>


</main>


<footer>

    Riverview Institute of Technology ·
    Office of Academic Affairs

</footer>


</body>

</html>
