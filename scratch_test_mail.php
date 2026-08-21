<?php

require_once '/opt/lampp/htdocs/subdomains/PHPMailer/Exception.php';
require_once '/opt/lampp/htdocs/subdomains/PHPMailer/PHPMailer.php';
require_once '/opt/lampp/htdocs/subdomains/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host        = 'mailpit';
$mail->SMTPAuth    = false;
$mail->Port        = 1025;
$mail->SMTPSecure  = '';
$mail->SMTPAutoTLS = false;
$mail->setFrom('noreply@krazeplanet.com', 'KrazePlanet');
$mail->addAddress('student@example.com');
$mail->Subject = 'Test Mailpit';
$mail->Body    = 'Hello from Mailpit mock server!';
if ($mail->send()) {
    echo "MAIL_SENT_SUCCESS";
} else {
    echo "MAIL_FAILED";
}
