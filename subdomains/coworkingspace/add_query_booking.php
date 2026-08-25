<?php
// Connect to the database
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = 'root';
$password = '';
$database = 'cowork_db';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start the session
//session_start();

if (isset($_POST['reserve'])) {
    $fullname = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $start_date = $_POST['start_date'];
    $spaceName = $_POST['spaceName'];

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Please log in to make a reservation.')</script>";
        exit();
    }
    $user_id = $_SESSION['user_id'];

    // Check if the selected space is available
    $query2 = $conn->query("SELECT * FROM `bookings` WHERE `start_date` = '$start_date' AND `space_id` = '$_REQUEST[space_id]' AND `status` = 'available'") or die("error");
    $row = $query2->num_rows;
    if ($start_date < date("Y-m-d", strtotime('+8 HOURS'))) {
        echo "<script>alert('Reservation date must be the present date or later.')</script>";
    } else {
        if ($row > 0) {
            echo "<div class='col-md-4'>
                    <label style='color:#ff0000;'>Not Available Date</label><br />";
            $q_date = $conn->query("SELECT * FROM `bookings` WHERE `status` = 'available'") or die("error");
            while ($f_date = $q_date->fetch_array()) {
                echo "<ul>
                        <li>
                            <label class='alert-danger'>" . date("M d, Y", strtotime($f_date['start_date'] . "+8HOURS")) . "</label>
                        </li>
                    </ul>";
            }
            "</div>";
        } else {
            // Insert the reservation into the bookings table
            $conn->query("INSERT INTO `bookings`(user_id, space_id, status, start_date) VALUES('$user_id', '$_REQUEST[space_id]', 'reserved', '$start_date')") or die("error");
            header("location:reply_booking.php");
            exit(); // Ensure no further code is executed after the redirect
        }
    }
}
?>
