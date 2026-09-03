<?php
// config/mail.php - Centralized SMTP Mailer Configuration
// Users only need to edit this file or set environment variables

if (!function_exists('configureKrazeMailer')) {
    function configureKrazeMailer($mail, $fromEmail = null, $fromName = 'KrazePlanet') {
        $smtp_host     = getenv('SMTP_HOST')     ?: 'mail.kzlabs.store';
        $smtp_port     = intval(getenv('SMTP_PORT') ?: 465);
        $smtp_user     = getenv('SMTP_USER')     ?: 'noreply@kzlabs.store';
        $smtp_pass     = getenv('SMTP_PASS')     ?: '+kR^^N670#5&c72#5$*6&!MkK17~';
        $smtp_secure   = getenv('SMTP_SECURE')   ?: 'ssl';
        $smtp_autotls  = getenv('SMTP_AUTOTLS')  !== 'true';
        $smtp_timeout  = intval(getenv('SMTP_TIMEOUT') ?: 5);
        $mail_from     = $fromEmail ?: (getenv('MAIL_FROM') ?: 'noreply@kzlabs.store');

        $mail->isSMTP();
        $mail->Host        = $smtp_host;
        $mail->Port        = $smtp_port;
        $mail->SMTPAuth    = (!empty($smtp_user) && !empty($smtp_pass));
        $mail->Username    = $smtp_user;
        $mail->Password    = $smtp_pass;
        $mail->SMTPSecure  = $smtp_secure;
        $mail->SMTPAutoTLS = $smtp_autotls;
        $mail->Timeout     = $smtp_timeout;
        $mail->setFrom($mail_from, $fromName);
        return $mail;
    }
}