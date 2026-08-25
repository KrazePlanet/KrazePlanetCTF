<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to the database
    $host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
    $username = 'root';
    $password = '';
    $database = 'cowork_db';

    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $space = $_POST['spaces'];
    $address = $_POST['address'];
    $startDate = $_POST['date'];
    $phoneNumber = $_POST['number'];

    // Validate user existence
    $userQuery = "SELECT id FROM users WHERE name = ? AND email = ? AND phone = ? AND Address = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('ssss', $name, $email, $phoneNumber, $address);
    $userResult = $userStmt->execute();

    if (!$userResult) {
        die("Error executing the user query: " . $userStmt->error);
    }

    $userResult = $userStmt->get_result();
    if ($userResult && $userResult->num_rows > 0) {
        $userRow = $userResult->fetch_assoc();
        $userId = $userRow['id'];
    } else {
        // Insert data into users table if the user doesn't exist
        $insertUserQuery = "INSERT INTO users (name, email, phone, Address) VALUES (?, ?, ?, ?)";
        $insertUserStmt = $conn->prepare($insertUserQuery);
        $insertUserStmt->bind_param('ssss', $name, $email, $phoneNumber, $address);
        $insertUserResult = $insertUserStmt->execute();

        if (!$insertUserResult) {
            die("Error inserting user data: " . $insertUserStmt->error);
        }

        $userId = $insertUserStmt->insert_id;
    }

    // Prepare and execute the query to insert into bookings table
    $insertQuery = "INSERT INTO bookings (user_id, space_id, start_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('iis', $userId, $space, $startDate);
    $result = $stmt->execute();

    // Check if the insertion was successful
    if ($result) {
        // Booking successful, show success message
        echo '<script>alert("Booking Successful!");</script>';
    } else {
        // Booking failed, show error message
        echo '<script>alert("Booking Failed! Please try again later.");</script>';
    }

    // Close the prepared statements
    $userStmt->close();
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/book-now.css">
    <link rel="stylesheet" href="css/style.css">
    <title>Book Now</title>
</head>

<body class="main_bg">

    <div class="form">
        <div class="form-text">
            <h1><span><img src="img/art-1.png" alt=""></span> Book Now <span><img src="img/art-1.png" alt=""></span></h1>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quae, fugit.</p>
        </div>
        <div class="main-form">
            <form action="book-now.php" method="POST">
                <div>
                    <span>Your full name ?</span>
                    <input type="text" name="name" id="name" placeholder="Write your name here..." required>
                </div>
                <div>
                    <span>Your email ?</span>
                    <input type="email" name="email" id="name" placeholder="Write your email here..." required> 
                </div>
                <div>
                    <!-- <---this is the select option--->
                    <span>Choose space ?</span>
                    <select name="spaces" id="spaces" required>
                        <option value="" style="background-color:grey"><---Spaces---></option>
                        <option value="1" style="background-color:grey">Meeting Room</option>
                        <option value="2" style="background-color:grey">Virtual Office</option>
                        <option value="3" style="background-color:grey">Dedicated Desk</option>
                        <option value="4" style="background-color:grey">Coworking Membership</option>
                    </select>
                    <!-- <---this is the select option--->
                </div>
                <div>
                    <span>What's your Address ?</span>
                    <input type="text" name="address" id="name" placeholder="Address" required>
                </div>
                <div>
                    <span>Start Date ?</span>
                    <input type="date" name="date" id="date" placeholder="date" required>
                </div>
                <div>
                    <span>Your phone number ?</span>
                    <input type="number" name="number" id="number" placeholder="Write your number here..." required>
                </div>
                <div id="submit">
                    <input href='payment.php' type="submit" value="SUBMIT" id="submit">
                </div>


            </form>
        </div>
    </div>
</body>

</html>