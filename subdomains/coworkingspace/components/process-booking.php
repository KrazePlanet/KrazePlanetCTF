<?php
// Assuming you already have a database connection established

// Retrieve form data
$fullName = $_POST['fullName'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$space = $_POST['space'];
$date = $_POST['date'];
$duration = $_POST['duration'];

// Perform necessary validation and processing

// Insert form data into the database
$query = "INSERT INTO bookings (user_id, space_id, start_date, end_date, total_price) 
          VALUES ((SELECT id FROM users WHERE email = '$email'), 
                  (SELECT id FROM spaces WHERE name = '$space'), 
                  '$date',
                  DATE_ADD('$date', INTERVAL $duration DAY),
                  (SELECT price_per_day * $duration FROM spaces WHERE name = '$space'))";

// Execute the query and handle success/failure
if (mysqli_query($conn, $query)) {
    // Booking successful
    echo 'Booking successful! Please proceed to payment.';
} else {
    // Error in query execution
    echo 'Error: ' . mysqli_error($conn);
}

// Close the database connection
mysqli_close($conn);
?>
