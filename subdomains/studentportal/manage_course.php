<?php
include 'login1.php'; // Database connection

// Fetch Courses
$query = "SELECT * FROM course";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="style.css"> <!-- Matching styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #1f72b8;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 10px;
            background-color: #1f72b8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn:hover {
            background-color: #155f8d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #1f72b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Courses</h2>
        <a href="add_course.php" class="btn">Add Course</a>
        <a href="admin_home.php" class="btn">Back to Admin Home</a>
        <table>
            <tr>
                <th>Course Name</th>
                <th>Code</th>
                <th>Duration</th>
                <th>Department</th>
                <th>Instructor</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row['c_name'] ?></td>
                <td><?= $row['code'] ?></td>
                <td><?= $row['duration'] ?></td>
                <td><?= $row['department'] ?></td>
                <td><?= $row['instructor'] ?></td>
                <td>
                    <a href="update_course.php?code=<?= $row['code'] ?>">Update</a> |
                    <a href="delete_course.php?code=<?= $row['code'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>
