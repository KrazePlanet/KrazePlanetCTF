<?php
session_start();
include("login1.php"); // Using the connection from the second file

if(isset($_POST['submit'])){
    $s_id = $_POST['student-id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $semester = $_POST['semester']; 
    $number = $_POST['number'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $s_pass = $_POST['student-password'];
    
    $query = "INSERT INTO register (s_id, name, age, gender, semester, number, email, department, s_pass) 
              VALUES ('$s_id', '$name', '$age', '$gender', '$semester', '$number', '$email', '$department', '$s_pass')";
    
    $result = mysqli_query($con, $query);
    
    if($result){
        echo "<script>alert('Student registration successful!');</script>";
        header('Location: manage_students.php'); // Redirect to table page
        exit();
    } else {
        echo "<script>alert('Registration failed. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student Details</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            background-color: lightblue;
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .radio-group {
            display: flex;
            gap: 15px;
        }
        .radio-option {
            display: flex;
            align-items: center;
        }
        .radio-option input {
            width: auto;
            margin-right: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        h1 {
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Student Registration</h1>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div class="form-group">
            <label>Student ID</label>
            <input type="text" name="student-id" required>
        </div>
        
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label>Age</label>
            <input type="number" name="age" required>
        </div>
        
        <div class="form-group">
            <label>Gender</label>
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" name="gender" value="m" required>
                    <label>Male</label>
                </div>
                <div class="radio-option">
                    <input type="radio" name="gender" value="f">
                    <label>Female</label>
                </div>
                <div class="radio-option">
                    <input type="radio" name="gender" value="o">
                    <label>Others</label>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Semester</label>
            <input type="text" name="semester" required>
        </div>
        
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="number" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="student-password" required>
        </div>
        
        <input type="submit" name="submit" value="Register Student">
    </form>
</body>
</html>