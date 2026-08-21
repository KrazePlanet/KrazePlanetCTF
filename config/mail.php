<?php
// config/mail.php - Centralized Mailpit Mock SMTP Configuration
if (!function_exists('configureKrazeMailer')) {
    function configureKrazeMailer($mail, $fromEmail = 'noreply@krazeplanet.com', $fromName = 'KrazePlanet') {
        $mail->isSMTP();
        // Use mailpit host inside Docker network
        $mail->Host        = 'mailpit';
        $mail->SMTPAuth    = false;
        $mail->Port        = 1025;
        $mail->SMTPSecure  = '';
        $mail->SMTPAutoTLS = false;
        $mail->Timeout     = 3;
        $mail->setFrom($fromEmail, $fromName);
        return $mail;
    }
}
