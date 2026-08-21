<?php
require 'components/connect.php'; // Include the database connection

use PHPMailer\PHPMailer\PHPMailer;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';;

// Use PHPMailer to send the email

$mail = new PHPMailer();

// Gmail SMTP configuration
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'soukainasbai77@gmail.com';
$mail->Password = 'jltaynfdmrvhoenr';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$name = $_POST['name'];
$Address = $_POST['Address'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$message = $_POST['message'];

// Email content
$mail->setFrom($email, $name);
$mail->addAddress('soukainasbai77@gmail.com');
$mail->isHTML(true);
$mail->Subject = 'Contact Form Submission';
$mail->Body = "Name: {$name}\nAddress: {$Address}\nEmail: {$email}\nPhone: {$phone}\nMessage: {$message}";

// Try to send the email
if ($mail->send()) {
    echo '<script>alert("Mail sent successfully!");</script>';
} else {
    echo '<script>alert("Mail not sent. Error: ' . $mail->ErrorInfo . '");</script>';
}

?>


<!--echo '

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>contact</title>
    <style>
        a{
        color :#828282;
        font-style: italic;

        }
        a.hover{
        color:#fff;
        }
    </style>
    
    <!-- font awesome cdn link  -
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- custom css file link  -
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">

    <script
        src="https://kit.fontawesome.com/64d58efce2.js"
        crossorigin="anonymous"
    ></script>
    </head>
    <body>
    
    <!-- header section starts  -
    <!-- header section ends -


    <div class="container">
        <h1>Thank you for contacting me. I will back to you as soon as possible!</h1>
        <p class="back">Go back to the <a href="home.php">homepage</a>.</p>
    </div></div>

<!-- custom js file link  -
<script src="js/script.js"></script>

</body>
</html>


';

?>-->