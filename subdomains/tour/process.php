<?php

if (isset($_POST['name'])) {
    $server = "localhost";
    $username = "root";
    $password = "";

    // database connection started
    $con = mysqli_connect($server, $username, $password);

    if (!$con) {
        die("Could not connect to" . mysqli_connect_error());
    }
    // echo "Connecting to...";

    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $other = $_POST['other'];


    $sql = "INSERT INTO `tour`.`tour` (`name`, `age`, `gender`, `email`, `phone`, `other`, `date`) VALUES ('$name', '$age', '$gender', '$email', '$phone', '$other', current_timestamp());";

    //echo $sql;

    if ($con->query($sql) == true) {
        //echo "Successfully incerted";
    } else {
        echo "Error $sql <br> $con->error";
    }

    // database connection turned off
    $con->close();



    // show message

    // Retrieve the name from the form submission
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : "Guest";

    // Define the occasion and personalized message
    $occasion = "submitting for the Cox's Bazar tour";
    $personalizedMessage = "It's going to be an amazing adventure filled with golden sands, blue waves, and wonderful memories!";

    // Generate the congratulatory message
    function generateCongratsMessage($name, $occasion, $message)
    {
        return "
        <div style='
            font-family: \"Arial, sans-serif\"; 
            text-align: center; 
            padding: 20px; 
            background: linear-gradient(to bottom right, #f9e7ff, #d4d9ff); 
            border-radius: 15px; 
            border: 2px solid #8c89d9; 
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            margin: 20px auto;'>
            
            <h1 style='color: #5a4d8a; font-size: 2em; margin-bottom: 10px;'>🌊 Congratulations! 🌴</h1>
            <p style='color: #333; font-size: 1.2em;'>
                Dear <strong>$name</strong>, <br><br>
                Congratulations on <em>$occasion</em>! 
            </p>
            <p style='color: #555; font-size: 1em; margin-top: 15px;'>
                $message
            </p>
            <footer style='margin-top: 20px; color: #888; font-size: 0.9em;'>Best wishes, <br><strong>Abdullah Shishir</strong></footer>
            
            <div style='margin-top: 30px;'>
                <a href='index.html' style='
                    display: inline-block; 
                    padding: 10px 20px; 
                    background-color: #5a4d8a; 
                    color: #fff; 
                    text-decoration: none; 
                    font-size: 1em; 
                    border-radius: 8px; 
                    transition: background-color 0.3s ease;'>
                    Back to Form
                </a>
            </div>
        </div>
        ";
    }

    // Display the message
    echo generateCongratsMessage($name, $occasion, $personalizedMessage);
}
