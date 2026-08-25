<?php
// Assuming you have already established a MySQL database connection
// Database configuration
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));     // Hostname
$user = "root";      // Username
$password = "";  // Password
$database = "cowork_db";    // Database name

// Create a connection
$connection = mysqli_connect($host, $user, $password, $database);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Check if the selected_option key is set in $_POST array
if (isset($_POST['selected_option'])) {
    $selectedOption = $_POST['selected_option'];
    $fullName = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phone_number'];
    $address = $_POST['address'];

    // Insert form data into the database
    $query = "INSERT INTO bookings (space_name, user_name, user_email, user_phone, user_id, space_id)
              SELECT '$selectedOption', '$fullName', '$email', '$phoneNumber', users.user_id, spaces.space_id
              FROM users, spaces
              WHERE users.name = '$fullName' AND spaces.name = '$selectedOption'";

    if ($connection->query($query) === TRUE) {
        // Registration successful, display alert and redirect to booking page
        echo '<script>alert("Information registered successfully. Please proceed to the booking page.");</script>';
        echo '<script>window.location.href = "booking.php";</script>';
        exit();
    } else {
        // Error occurred, display alert
        echo '<script>alert("Error: ' . $connection->error . '");</script>';
    }
} else {
    // selected_option key is not set, handle the error gracefully
    echo '<script>alert("Error: selected_option is missing.");</script>';
}

$connection->close();
?>
